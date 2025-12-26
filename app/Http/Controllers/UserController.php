<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Course;
use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\Review;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // ====================
    // Public API Methods
    // ====================

    /**
     * List all users with filters and pagination
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query();

        // Apply filters
        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('verified')) {
            $query->verified();
        }

        if ($request->has('active')) {
            $query->active();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        // Apply sorting
        $sortField = $request->get('sort_by', 'created_at');
        $sortDirection = $request->get('sort_dir', 'desc');
        $query->orderBy($sortField, $sortDirection);

        // Get paginated results
        $perPage = $request->get('per_page', 20);
        $users = $query->paginate($perPage);

        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
            ]
        ]);
    }

    /**
     * Show a single user with relationships
     */
    public function show(Request $request, User $user): JsonResponse
    {
        $includes = explode(',', $request->get('include', ''));

        $load = [];
        foreach ($includes as $include) {
            $include = trim($include);
            if (in_array($include, [
                'enrolledCourses',
                'taughtCourses',
                'reviews',
                'payments',
                'certificates',
                'lessonProgress'
            ])) {
                $load[] = $include;
            }
        }

        if (!empty($load)) {
            $user->load($load);
        }

        return response()->json([
            'data' => $user,
            'stats' => $user->dashboard_stats,
            'achievements' => $user->achievements,
        ]);
    }

    /**
     * Create a new user
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'sometimes|in:admin,instructor,student',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'bio' => 'sometimes|string|max:1000',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
            'website' => 'sometimes|url|max:255',
        ]);

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        // Hash password
        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'message' => 'User created successfully',
            'data' => $user
        ], 201);
    }

    /**
     * Update a user
     */
    public function update(Request $request, User $user): JsonResponse
    {
        // Authorization check (only admin or user themselves can update)
        if (!auth()->user()->isAdmin() && auth()->id() !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users')->ignore($user->id)
            ],
            'password' => 'sometimes|string|min:8|confirmed',
            'role' => 'sometimes|in:admin,instructor,student',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            'bio' => 'sometimes|string|max:1000',
            'phone' => 'sometimes|string|max:20',
            'address' => 'sometimes|string|max:500',
            'website' => 'sometimes|url|max:255',
            'current_password' => 'sometimes|required_with:password|string',
        ]);

        // Verify current password if changing password
        if (isset($validated['password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 422);
            }
            $validated['password'] = Hash::make($validated['password']);
            unset($validated['current_password']);
        }

        // Handle avatar upload/update
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        // Prevent non-admins from changing role
        if (!auth()->user()->isAdmin() && isset($validated['role'])) {
            unset($validated['role']);
        }

        $user->update($validated);

        return response()->json([
            'message' => 'User updated successfully',
            'data' => $user
        ]);
    }

    /**
     * Delete a user
     */
    public function destroy(User $user): JsonResponse
    {
        // Delete avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully'
        ]);
    }

    // ====================
    // User Profile Methods
    // ====================

    /**
     * Get current user's profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user();
        $includes = explode(',', $request->get('include', ''));

        $load = [];
        foreach ($includes as $include) {
            $include = trim($include);
            if (in_array($include, [
                'enrolledCourses',
                'taughtCourses',
                'reviews',
                'payments',
                'certificates',
                'lessonProgress'
            ])) {
                $load[] = $include;
            }
        }

        if (!empty($load)) {
            $user->load($load);
        }

        return response()->json([
            'data' => $user,
            'profile_completion' => $user->profile_completion,
            'stats' => $user->dashboard_stats,
            'achievements' => $user->achievements,
        ]);
    }

    /**
     * Update current user's profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        return $this->update($request, $user);
    }

    /**
     * Upload user avatar
     */
    public function uploadAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $user = $request->user();

        // Delete old avatar if exists
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->avatar = $path;
        $user->save();

        return response()->json([
            'message' => 'Avatar uploaded successfully',
            'avatar_url' => $user->avatar_url
        ]);
    }

    /**
     * Delete user avatar
     */
    public function deleteAvatar(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->avatar) {
            return response()->json(['message' => 'No avatar to delete'], 404);
        }

        if (Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->avatar = null;
        $user->save();

        return response()->json([
            'message' => 'Avatar deleted successfully'
        ]);
    }

    // ====================
    // Enrollment Methods
    // ====================

    /**
     * Get user's enrolled courses
     */
    public function enrolledCourses(Request $request, User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();
        
        // Authorization check
        if (!$request->user()->canViewProgress($targetUser->id, 0)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = $targetUser->publishedEnrolledCourses();

        if ($request->has('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $courses = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $courses->items(),
            'meta' => [
                'total' => $courses->total(),
                'current_page' => $courses->currentPage(),
                'per_page' => $courses->perPage(),
                'last_page' => $courses->lastPage(),
            ]
        ]);
    }

    /**
     * Check if user is enrolled in a course
     */
    public function checkEnrollment(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        $isEnrolled = $user->isEnrolledIn($course->id);

        return response()->json([
            'is_enrolled' => $isEnrolled,
            'can_access' => $user->canAccessCourse($course->id)
        ]);
    }

    /**
     * Enroll user in a course
     */
    public function enroll(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        if ($user->isEnrolledIn($course->id)) {
            return response()->json(['message' => 'Already enrolled in this course'], 409);
        }

        if (!$course->published && !$user->canEditCourse($course)) {
            return response()->json(['message' => 'Course is not available for enrollment'], 403);
        }

        $enrollment = $user->enrollIn($course->id);

        if (!$enrollment) {
            return response()->json(['message' => 'Failed to enroll in course'], 500);
        }

        return response()->json([
            'message' => 'Successfully enrolled in course',
            'data' => $enrollment
        ], 201);
    }

    /**
     * Unenroll user from a course
     */
    public function unenroll(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        if (!$user->isEnrolledIn($course->id)) {
            return response()->json(['message' => 'Not enrolled in this course'], 404);
        }

        $success = $user->unenrollFrom($course->id);

        if (!$success) {
            return response()->json(['message' => 'Failed to unenroll from course'], 500);
        }

        return response()->json([
            'message' => 'Successfully unenrolled from course'
        ]);
    }

    // ====================
    // Progress Methods
    // ====================

    /**
     * Get user's course progress
     */
    public function courseProgress(Request $request, Course $course, User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();
        
        // Authorization check
        if (!$request->user()->canViewProgress($targetUser->id, $course->id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $progress = $targetUser->getCourseProgress($course->id);
        $nextLesson = $targetUser->getNextIncompleteLesson($course->id);

        return response()->json([
            'progress' => $progress,
            'next_lesson' => $nextLesson,
            'has_completed_course' => $targetUser->hasCompletedCourse($course->id)
        ]);
    }

    /**
     * Get all user's course progress
     */
    public function allProgress(Request $request, User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();
        
        // Authorization check
        if (!$request->user()->canViewProgress($targetUser->id, 0)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $progress = $targetUser->getAllCourseProgress();

        return response()->json([
            'data' => $progress,
            'stats' => [
                'completed_lessons' => $targetUser->completed_lessons_count,
                'completion_streak' => $targetUser->completion_streak,
            ]
        ]);
    }

    /**
     * Mark lesson as completed
     */
    public function completeLesson(Request $request, Lesson $lesson): JsonResponse
    {
        $user = $request->user();

        // Check if user can access the course
        if (!$user->canAccessCourse($lesson->course_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $progress = $user->completeLesson($lesson->id);

        return response()->json([
            'message' => 'Lesson marked as completed',
            'data' => $progress
        ]);
    }

    /**
     * Mark lesson as incomplete
     */
    public function incompleteLesson(Request $request, Lesson $lesson): JsonResponse
    {
        $user = $request->user();

        // Check if user can access the course
        if (!$user->canAccessCourse($lesson->course_id)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $success = $user->incompleteLesson($lesson->id);

        if (!$success) {
            return response()->json(['message' => 'Failed to mark lesson as incomplete'], 500);
        }

        return response()->json([
            'message' => 'Lesson marked as incomplete'
        ]);
    }

    /**
     * Get recently completed lessons
     */
    public function recentCompletions(Request $request, User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();
        
        // Authorization check
        if (!$request->user()->canViewProgress($targetUser->id, 0)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $limit = $request->get('limit', 10);
        $lessons = $targetUser->recentlyCompletedLessons($limit);

        return response()->json([
            'data' => $lessons
        ]);
    }

    // ====================
    // Review Methods
    // ====================

    /**
     * Get user's reviews
     */
    public function reviews(Request $request, User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();
        
        // Authorization check
        if ($user && !$request->user()->isAdmin() && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = $targetUser->reviews()->with('course');

        if ($request->has('rating')) {
            $query->where('rating', $request->rating);
        }

        $reviews = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $reviews->items(),
            'meta' => [
                'total' => $reviews->total(),
                'average_rating' => $targetUser->average_rating_given,
            ]
        ]);
    }

    /**
     * Create or update a review
     */
    public function submitReview(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        if (!$user->isEnrolledIn($course->id)) {
            return response()->json(['message' => 'Must be enrolled to review'], 403);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'sometimes|string|max:1000',
        ]);

        $review = $user->createOrUpdateReview(
            $course->id,
            $validated['rating'],
            $validated['comment'] ?? null
        );

        return response()->json([
            'message' => 'Review submitted successfully',
            'data' => $review
        ], 201);
    }

    /**
     * Delete a review
     */
    public function deleteReview(Request $request, Review $review): JsonResponse
    {
        $user = $request->user();

        if (!$user->canDeleteReview($review)) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $success = $user->deleteReview($review->course_id);

        if (!$success) {
            return response()->json(['message' => 'Failed to delete review'], 500);
        }

        return response()->json([
            'message' => 'Review deleted successfully'
        ]);
    }

    // ====================
    // Certificate Methods
    // ====================

    /**
     * Get user's certificates
     */
    public function certificates(Request $request, User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();
        
        // Authorization check
        if ($user && !$request->user()->isAdmin() && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = $targetUser->certificates()->with('course.instructor');

        if ($request->has('valid_only')) {
            $query->valid();
        }

        $certificates = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $certificates->items(),
            'analytics' => $targetUser->certificate_analytics,
            'meta' => [
                'total' => $certificates->total(),
                'valid_count' => $targetUser->valid_certificates_count,
            ]
        ]);
    }

    /**
     * Check if user is eligible for certificate
     */
    public function checkCertificateEligibility(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $eligible = $user->isEligibleForCertificate($course->id);
        $reason = '';

        if (!$eligible) {
            if ($user->hasCertificateForCourse($course->id)) {
                $reason = 'Already has certificate for this course';
            } elseif (!$course->certificate_available) {
                $reason = 'Course does not offer certificates';
            } elseif (!$user->hasCompletedCourse($course->id)) {
                $reason = 'Course not completed';
            }
        }

        return response()->json([
            'eligible' => $eligible,
            'reason' => $reason
        ]);
    }

    /**
     * Request certificate for a course
     */
    public function requestCertificate(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        if (!$user->isEligibleForCertificate($course->id)) {
            return response()->json(['message' => 'Not eligible for certificate'], 403);
        }

        $certificate = $user->requestCertificate($course->id, [
            'requested_at' => now(),
            'requested_by_user' => true,
        ]);

        if (!$certificate) {
            return response()->json(['message' => 'Failed to issue certificate'], 500);
        }

        return response()->json([
            'message' => 'Certificate issued successfully',
            'data' => $certificate
        ], 201);
    }

    /**
     * Get certificate for a specific course
     */
    public function getCourseCertificate(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        $certificate = $user->getCertificateForCourse($course->id);

        if (!$certificate) {
            return response()->json(['message' => 'No certificate found for this course'], 404);
        }

        return response()->json([
            'data' => $certificate
        ]);
    }

    // ====================
    // Payment Methods
    // ====================

    /**
     * Get user's payments
     */
    public function payments(Request $request, User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();
        
        // Authorization check
        if ($user && !$request->user()->isAdmin() && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $query = $targetUser->payments()->with('course');

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $payments = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $payments->items(),
            'stats' => [
                'total_spent' => $targetUser->total_spent,
                'monthly_spending' => $targetUser->monthly_spending,
            ],
            'meta' => [
                'total' => $payments->total(),
            ]
        ]);
    }

    /**
     * Check if user has paid for a course
     */
    public function checkPayment(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();
        $hasPaid = $user->hasPaidForCourse($course->id);

        return response()->json([
            'has_paid' => $hasPaid,
            'course_price' => $course->price
        ]);
    }

    /**
     * Create a payment for a course
     */
    public function createPayment(Request $request, Course $course): JsonResponse
    {
        $user = $request->user();

        if ($user->hasPaidForCourse($course->id)) {
            return response()->json(['message' => 'Already paid for this course'], 409);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'status' => 'sometimes|in:pending,completed,failed,refunded',
            'metadata' => 'sometimes|array',
        ]);

        $payment = $user->createPayment(
            $course->id,
            $validated['amount'],
            $validated['payment_method'],
            $validated['status'] ?? 'pending',
            $validated['metadata'] ?? []
        );

        return response()->json([
            'message' => 'Payment created successfully',
            'data' => $payment
        ], 201);
    }

    // ====================
    // Instructor Methods
    // ====================

    /**
     * Get user's taught courses
     */
    public function taughtCourses(Request $request, User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();
        
        if (!$targetUser->isInstructor() && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'User is not an instructor'], 403);
        }

        $query = $targetUser->taughtCourses();

        if ($request->has('published_only')) {
            $query->published();
        }

        $courses = $query->paginate($request->get('per_page', 10));

        return response()->json([
            'data' => $courses->items(),
            'stats' => [
                'total_courses' => $targetUser->published_courses_count,
                'total_students' => $targetUser->total_students,
                'average_rating' => $targetUser->instructor_rating,
                'total_revenue' => $targetUser->instructor_revenue,
            ],
            'meta' => [
                'total' => $courses->total(),
            ]
        ]);
    }

    /**
     * Get instructor's students
     */
    public function instructorStudents(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isInstructor()) {
            return response()->json(['message' => 'User is not an instructor'], 403);
        }

        $courseIds = $user->taughtCourses()->pluck('id');
        
        $query = User::whereHas('enrollments', function ($q) use ($courseIds) {
            $q->whereIn('course_id', $courseIds);
        })->distinct();

        $students = $query->paginate($request->get('per_page', 20));

        return response()->json([
            'data' => $students->items(),
            'total_students' => $user->total_students,
            'meta' => [
                'total' => $students->total(),
                'current_page' => $students->currentPage(),
            ]
        ]);
    }

    /**
     * Get instructor's revenue analytics
     */
    public function instructorRevenue(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->isInstructor() && !$user->isAdmin()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $timeframe = $request->get('timeframe', 'monthly'); // daily, weekly, monthly, yearly
        
        $revenueData = Payment::getInstructorRevenueAnalytics(
            $user->isAdmin() ? null : $user->id,
            $timeframe
        );

        return response()->json([
            'total_revenue' => $user->instructor_revenue,
            'formatted_revenue' => $user->formatted_instructor_revenue,
            'analytics' => $revenueData,
            'timeframe' => $timeframe
        ]);
    }

    // ====================
    // Admin Methods
    // ====================

    /**
     * Get dashboard statistics (admin only)
     */
    public function adminStats(Request $request): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Admin access required'], 403);
        }

        $stats = [
            'total_users' => User::count(),
            'total_instructors' => User::instructors()->count(),
            'total_students' => User::students()->count(),
            'total_courses' => Course::count(),
            'published_courses' => Course::where('published', true)->count(),
            'total_enrollments' => Enrollment::count(),
            'active_enrollments' => Enrollment::where('enrolled_at', '>=', now()->subDays(30))->count(),
            'total_certificates' => Certificate::count(),
            'valid_certificates' => Certificate::valid()->count(),
            'total_payments' => Payment::count(),
            'completed_payments' => Payment::where('status', 'completed')->count(),
            'total_revenue' => Payment::getTotalRevenue(),
            'monthly_revenue' => Payment::getMonthlyRevenue(),
        ];

        return response()->json($stats);
    }

    /**
     * Change user role (admin only)
     */
    public function changeRole(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Admin access required'], 403);
        }

        $validated = $request->validate([
            'role' => 'required|in:admin,instructor,student',
        ]);

        $oldRole = $user->role;
        $user->role = $validated['role'];
        $user->save();

        return response()->json([
            'message' => 'User role updated successfully',
            'old_role' => $oldRole,
            'new_role' => $user->role,
            'role_name' => $user->role_name,
        ]);
    }

    /**
     * Verify user email (admin only)
     */
    public function verifyEmail(Request $request, User $user): JsonResponse
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'Admin access required'], 403);
        }

        $user->email_verified_at = now();
        $user->save();

        return response()->json([
            'message' => 'User email verified successfully',
            'verified_at' => $user->email_verified_at,
        ]);
    }

    /**
     * Get user activity timeline
     */
    public function activityTimeline(Request $request, User $user = null): JsonResponse
    {
        $targetUser = $user ?? $request->user();
        
        // Authorization check
        if ($user && !$request->user()->isAdmin() && $request->user()->id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $limit = $request->get('limit', 30);
        $activities = $targetUser->getActivityTimeline($limit);

        return response()->json([
            'data' => $activities,
            'total_activities' => $activities->count(),
        ]);
    }

    // ====================
    // Search & Filter Methods
    // ====================

    /**
     * Search users
     */
    public function search(Request $request): JsonResponse
    {
        $query = User::query();

        if ($request->has('q')) {
            $search = $request->q;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('role')) {
            $query->where('role', $request->role);
        }

        if ($request->has('verified')) {
            $query->verified();
        }

        $users = $query->limit($request->get('limit', 20))->get();

        return response()->json([
            'data' => $users,
            'total' => $users->count(),
        ]);
    }

    /**
     * Get user statistics for dashboard
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'learning_stats' => $user->learning_stats,
            'profile_completion' => $user->profile_completion,
            'achievements' => $user->achievements,
        ]);
    }
}