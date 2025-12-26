<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Course;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    /**
     * Display a listing of payments.
     */
    public function index(Request $request)
    {
        try {
            $query = Payment::query();

            // Eager load relationships if requested
            if ($request->has('with')) {
                $with = explode(',', $request->with);
                $allowedRelations = ['user', 'course', 'course.instructor', 'course.category'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            // Filter by user
            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            // Filter by user email
            if ($request->has('user_email') && !empty($request->user_email)) {
                $user = User::where('email', $request->user_email)->first();
                if ($user) {
                    $query->where('user_id', $user->id);
                }
            }

            // Filter by course
            if ($request->has('course_id') && !empty($request->course_id)) {
                $query->where('course_id', $request->course_id);
            }

            // Filter by course slug
            if ($request->has('course_slug') && !empty($request->course_slug)) {
                $course = Course::where('slug', $request->course_slug)->first();
                if ($course) {
                    $query->where('course_id', $course->id);
                }
            }

            // Filter by status
            if ($request->has('status') && !empty($request->status)) {
                $query->where('status', $request->status);
            }

            // Filter by payment method
            if ($request->has('payment_method') && !empty($request->payment_method)) {
                $query->where('payment_method', $request->payment_method);
            }

            // Filter by currency
            if ($request->has('currency') && !empty($request->currency)) {
                $query->where('currency', $request->currency);
            }

            // Filter by amount range
            if ($request->has('amount_min') && !empty($request->amount_min)) {
                $query->where('amount', '>=', $request->amount_min);
            }
            if ($request->has('amount_max') && !empty($request->amount_max)) {
                $query->where('amount', '<=', $request->amount_max);
            }

            // Filter by transaction ID
            if ($request->has('transaction_id') && !empty($request->transaction_id)) {
                $query->where('transaction_id', 'like', '%' . $request->transaction_id . '%');
            }

            // Filter by date range
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }
            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            // Filter by recent payments (last 24 hours)
            if ($request->has('recent') && $request->recent) {
                $query->where('created_at', '>=', now()->subDay());
            }

            // Ordering
            $orderBy = $request->get('order_by', 'created_at');
            $orderDirection = $request->get('order_direction', 'desc');
            
            if (in_array($orderBy, ['created_at', 'updated_at', 'amount', 'status'])) {
                $query->orderBy($orderBy, $orderDirection);
            }

            // Include counts if requested
            $withCounts = $request->get('with_counts', false);
            if ($withCounts) {
                // You can add counts here if needed
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $payments = $perPage === 'all' 
                ? $query->get()
                : $query->paginate($perPage);

            // Add computed attributes if requested
            if ($request->has('include_computed') && $request->include_computed) {
                $payments->transform(function ($payment) {
                    $payment->formatted_amount = $payment->formatted_amount;
                    $payment->status_text = $payment->status_text;
                    $payment->method_text = $payment->method_text;
                    $payment->formatted_date = $payment->formatted_date;
                    $payment->time_ago = $payment->time_ago;
                    $payment->is_recent = $payment->isRecent();
                    $payment->is_completed = $payment->isCompleted();
                    $payment->is_pending = $payment->isPending();
                    $payment->is_failed = $payment->isFailed();
                    $payment->is_refunded = $payment->isRefunded();
                    return $payment;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $payments,
                'message' => 'Payments retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Payment index error: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payments.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Store a newly created payment.
     */
    public function store(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required|exists:users,id',
                'course_id' => 'nullable|exists:courses,id',
                'amount' => 'required|numeric|min:0.01',
                'status' => 'required|in:pending,completed,failed,refunded,cancelled',
                'payment_method' => 'required|in:stripe,paypal,manual,bank_transfer,card',
                'transaction_id' => 'nullable|string|max:100|unique:payments,transaction_id',
                'currency' => 'nullable|string|max:3',
                'description' => 'nullable|string|max:500',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if user has already paid for the course
            if ($request->course_id && Payment::hasUserPaidForCourse($request->user_id, $request->course_id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'User has already paid for this course.'
                ], 409);
            }

            // Verify course price if course_id is provided
            if ($request->course_id) {
                $course = Course::find($request->course_id);
                if ($course->price != $request->amount && !$request->has('force')) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Payment amount does not match course price. Use force option to proceed anyway.',
                        'course_price' => $course->price,
                        'payment_amount' => $request->amount
                    ], 422);
                }
            }

            $payment = Payment::create($request->all());

            DB::commit();

            // Load relationships if needed
            if ($request->has('load_relations')) {
                $payment->load(['user', 'course']);
            }

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Payment created successfully.'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment store error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Create a payment for current user.
     */
    public function createPaymentForSelf(Request $request)
    {
        DB::beginTransaction();

        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $validator = Validator::make($request->all(), [
                'course_id' => 'required|exists:courses,id',
                'payment_method' => 'required|in:stripe,paypal,manual,bank_transfer,card',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $course = Course::find($request->course_id);

            // Check if course is free
            if ($course->isFree()) {
                return response()->json([
                    'success' => false,
                    'message' => 'This course is free. No payment required.'
                ], 422);
            }

            // Check if user has already paid for the course
            if (Payment::hasUserPaidForCourse($user->id, $course->id)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You have already paid for this course.'
                ], 409);
            }

            // Check if user is already enrolled (but hasn't paid)
            if ($course->hasUserEnrolled($user->id) && !$request->has('force')) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are already enrolled in this course. Use force option to make payment anyway.'
                ], 409);
            }

            // Get current price (considering discount)
            $amount = $course->current_price;

            // Create payment
            $payment = Payment::createPayment(
                $user->id,
                $course->id,
                $amount,
                $request->payment_method,
                'pending',
                $request->metadata ?? []
            );

            // Process payment based on payment method
            $paymentResult = $this->processPayment($payment, $request->all());
            
            if (!$paymentResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => $paymentResult['message'],
                    'error' => $paymentResult['error'] ?? null
                ], 400);
            }

            DB::commit();

            $payment->refresh();
            $payment->load(['course']);

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Payment created successfully. ' . ($payment->isCompleted() ? 'Payment completed.' : 'Payment is pending.')
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Create payment for self error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to create payment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Display the specified payment.
     */
    public function show($id)
    {
        try {
            $query = Payment::query();

            // Load relationships if requested
            if (request()->has('with')) {
                $with = explode(',', request()->with);
                $allowedRelations = ['user', 'course', 'course.instructor', 'course.category'];
                $query->with(array_intersect($with, $allowedRelations));
            }

            $payment = $query->findOrFail($id);

            // Add computed attributes
            $payment->formatted_amount = $payment->formatted_amount;
            $payment->status_text = $payment->status_text;
            $payment->method_text = $payment->method_text;
            $payment->formatted_date = $payment->formatted_date;
            $payment->time_ago = $payment->time_ago;
            $payment->is_recent = $payment->isRecent();

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Payment retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Payment show error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Payment not found.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 404);
        }
    }

    /**
     * Update the specified payment.
     */
    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $payment = Payment::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'status' => 'nullable|in:pending,completed,failed,refunded,cancelled',
                'payment_method' => 'nullable|in:stripe,paypal,manual,bank_transfer,card',
                'transaction_id' => 'nullable|string|max:100',
                'currency' => 'nullable|string|max:3',
                'description' => 'nullable|string|max:500',
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Check if transaction_id is being changed and is unique
            if ($request->has('transaction_id') && $request->transaction_id !== $payment->transaction_id) {
                $exists = Payment::where('transaction_id', $request->transaction_id)
                    ->where('id', '!=', $payment->id)
                    ->exists();
                
                if ($exists) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaction ID already exists.',
                        'errors' => ['transaction_id' => ['Transaction ID already exists.']]
                    ], 422);
                }
            }

            // Update payment
            $payment->update($request->all());

            DB::commit();

            $payment->refresh();

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Payment updated successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment update error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to update payment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Remove the specified payment.
     */
    public function destroy($id)
    {
        DB::beginTransaction();

        try {
            $payment = Payment::findOrFail($id);

            // Check if payment is completed
            if ($payment->isCompleted() && !request()->has('force')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete completed payment. Use force option to delete anyway.'
                ], 422);
            }

            $payment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment deleted successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment destroy error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete payment.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark payment as completed.
     */
    public function markAsCompleted(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $payment = Payment::findOrFail($id);

            if ($payment->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is already completed.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'metadata' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $payment->markAsCompleted($request->metadata ?? []);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark payment as completed.'
                ], 400);
            }

            // If payment is for a course, enroll the user
            if ($payment->course_id) {
                $enrollment = $payment->course->enrollments()->firstOrCreate([
                    'user_id' => $payment->user_id,
                ], [
                    'enrolled_at' => now(),
                ]);
            }

            DB::commit();

            $payment->refresh();

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Payment marked as completed successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark payment as completed error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark payment as completed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark payment as failed.
     */
    public function markAsFailed(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $payment = Payment::findOrFail($id);

            if ($payment->isFailed()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is already marked as failed.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $success = $payment->markAsFailed($request->reason);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark payment as failed.'
                ], 400);
            }

            DB::commit();

            $payment->refresh();

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Payment marked as failed successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark payment as failed error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark payment as failed.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Mark payment as refunded.
     */
    public function markAsRefunded(Request $request, $id)
    {
        DB::beginTransaction();

        try {
            $payment = Payment::findOrFail($id);

            if ($payment->isRefunded()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment is already marked as refunded.'
                ], 400);
            }

            if (!$payment->isCompleted()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only completed payments can be refunded.'
                ], 400);
            }

            $validator = Validator::make($request->all(), [
                'refund_id' => 'nullable|string|max:100',
                'reason' => 'nullable|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Add reason to metadata if provided
            $metadata = [];
            if ($request->reason) {
                $metadata['refund_reason'] = $request->reason;
            }

            $success = $payment->markAsRefunded($request->refund_id, $metadata);

            if (!$success) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark payment as refunded.'
                ], 400);
            }

            // Unenroll user from course if payment is refunded
            if ($payment->course_id) {
                $payment->course->enrollments()
                    ->where('user_id', $payment->user_id)
                    ->delete();
            }

            DB::commit();

            $payment->refresh();

            return response()->json([
                'success' => true,
                'data' => $payment,
                'message' => 'Payment marked as refunded successfully.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Mark payment as refunded error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark payment as refunded.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Process payment (simulate payment processing).
     */
    private function processPayment(Payment $payment, array $data): array
    {
        try {
            // This is a simplified payment processing simulation
            // In a real application, you would integrate with payment gateways like Stripe, PayPal, etc.
            
            $paymentMethod = $payment->payment_method;
            
            switch ($paymentMethod) {
                case 'manual':
                    // Manual payments are typically marked as pending for admin approval
                    $payment->update(['status' => 'pending']);
                    return ['success' => true, 'message' => 'Manual payment created. Awaiting approval.'];
                    
                case 'stripe':
                case 'paypal':
                case 'card':
                    // Simulate successful payment processing
                    // In reality, you would make API calls to the payment gateway
                    $payment->markAsCompleted([
                        'gateway_response' => 'Simulated successful payment',
                        'gateway_transaction_id' => 'SIM_' . uniqid(),
                    ]);
                    return ['success' => true, 'message' => 'Payment processed successfully.'];
                    
                case 'bank_transfer':
                    // Bank transfers are typically pending until confirmed
                    $payment->update(['status' => 'pending']);
                    return ['success' => true, 'message' => 'Bank transfer initiated. Awaiting confirmation.'];
                    
                default:
                    return [
                        'success' => false,
                        'message' => 'Unsupported payment method.',
                        'error' => 'Payment method not implemented.'
                    ];
            }
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Payment processing failed.',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get payment statistics.
     */
    public function statistics(Request $request)
    {
        try {
            $query = Payment::query();

            // Apply filters if any
            if ($request->has('date_from') && !empty($request->date_from)) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->has('date_to') && !empty($request->date_to)) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->has('course_id') && !empty($request->course_id)) {
                $query->where('course_id', $request->course_id);
            }

            if ($request->has('user_id') && !empty($request->user_id)) {
                $query->where('user_id', $request->user_id);
            }

            $totalPayments = $query->count();
            $totalRevenue = $query->completed()->sum('amount');
            $todayRevenue = $query->completed()->whereDate('created_at', today())->sum('amount');
            $weekRevenue = $query->completed()->where('created_at', '>=', now()->subDays(7))->sum('amount');
            $monthRevenue = $query->completed()->where('created_at', '>=', now()->subDays(30))->sum('amount');

            // Get status distribution
            $statusDistribution = Payment::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->status => $item->count];
                });

            // Get payment method distribution
            $methodDistribution = Payment::select('payment_method', DB::raw('count(*) as count'))
                ->groupBy('payment_method')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [$item->payment_method => $item->count];
                });

            // Get revenue by date for the last 30 days
            $revenueByDate = Payment::select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as revenue'),
                    DB::raw('COUNT(*) as transactions')
                )
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get top courses by revenue
            $topCourses = Course::withSum(['payments as revenue' => function ($query) {
                    $query->where('status', 'completed');
                }])
                ->has('payments')
                ->orderBy('revenue', 'desc')
                ->limit(10)
                ->get();

            // Get top users by spending
            $topUsers = User::withSum(['payments as total_spent' => function ($query) {
                    $query->where('status', 'completed');
                }])
                ->has('payments')
                ->orderBy('total_spent', 'desc')
                ->limit(10)
                ->get();

            // Get recent payments
            $recentPayments = Payment::with(['user', 'course'])
                ->latest()
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_payments' => $totalPayments,
                    'total_revenue' => $totalRevenue,
                    'formatted_total_revenue' => '$' . number_format($totalRevenue, 2),
                    'today_revenue' => $todayRevenue,
                    'week_revenue' => $weekRevenue,
                    'month_revenue' => $monthRevenue,
                    'status_distribution' => $statusDistribution,
                    'method_distribution' => $methodDistribution,
                    'revenue_by_date' => $revenueByDate,
                    'top_courses' => $topCourses,
                    'top_users' => $topUsers,
                    'recent_payments' => $recentPayments,
                    'analytics' => Payment::getAnalytics(),
                ],
                'message' => 'Payment statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Payment statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve payment statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get course payment statistics.
     */
    public function courseStatistics($courseId)
    {
        try {
            $course = Course::findOrFail($courseId);

            $totalPayments = $course->payments()->count();
            $totalRevenue = $course->payments()->completed()->sum('amount');
            $completedPayments = $course->payments()->completed()->count();
            $pendingPayments = $course->payments()->pending()->count();
            $failedPayments = $course->payments()->failed()->count();
            $refundedPayments = $course->payments()->refunded()->count();

            // Get payment trend
            $paymentTrend = $course->payments()
                ->select(
                    DB::raw('DATE(created_at) as date'),
                    DB::raw('COUNT(*) as count'),
                    DB::raw('SUM(CASE WHEN status = "completed" THEN amount ELSE 0 END) as revenue')
                )
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            // Get recent payments for this course
            $recentPayments = $course->payments()
                ->with('user')
                ->latest()
                ->limit(10)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'course' => $course->only(['id', 'title', 'slug', 'price', 'current_price']),
                    'total_payments' => $totalPayments,
                    'total_revenue' => $totalRevenue,
                    'formatted_total_revenue' => '$' . number_format($totalRevenue, 2),
                    'completed_payments' => $completedPayments,
                    'pending_payments' => $pendingPayments,
                    'failed_payments' => $failedPayments,
                    'refunded_payments' => $refundedPayments,
                    'payment_trend' => $paymentTrend,
                    'recent_payments' => $recentPayments,
                ],
                'message' => 'Course payment statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Course payment statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve course payment statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get user payment statistics.
     */
    public function userStatistics($userId)
    {
        try {
            $user = User::findOrFail($userId);

            $totalPayments = $user->payments()->count();
            $totalSpent = $user->payments()->completed()->sum('amount');
            $completedPayments = $user->payments()->completed()->count();
            $pendingPayments = $user->payments()->pending()->count();
            $failedPayments = $user->payments()->failed()->count();
            $refundedPayments = $user->payments()->refunded()->count();

            // Get payment history
            $paymentHistory = $user->payments()
                ->with('course')
                ->latest()
                ->paginate(10);

            // Get courses paid for
            $paidCourses = $user->payments()
                ->completed()
                ->with('course')
                ->get()
                ->pluck('course')
                ->filter()
                ->unique('id')
                ->values();

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => $user->only(['id', 'name', 'email']),
                    'total_payments' => $totalPayments,
                    'total_spent' => $totalSpent,
                    'formatted_total_spent' => '$' . number_format($totalSpent, 2),
                    'completed_payments' => $completedPayments,
                    'pending_payments' => $pendingPayments,
                    'failed_payments' => $failedPayments,
                    'refunded_payments' => $refundedPayments,
                    'payment_history' => $paymentHistory,
                    'paid_courses' => $paidCourses,
                ],
                'message' => 'User payment statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('User payment statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user payment statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get current user's payment statistics.
     */
    public function selfStatistics(Request $request)
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated.'
                ], 401);
            }

            $totalPayments = $user->payments()->count();
            $totalSpent = $user->payments()->completed()->sum('amount');
            $completedPayments = $user->payments()->completed()->count();
            $pendingPayments = $user->payments()->pending()->count();
            $failedPayments = $user->payments()->failed()->count();
            $refundedPayments = $user->payments()->refunded()->count();

            // Get payment history
            $paymentHistory = $user->payments()
                ->with('course')
                ->latest()
                ->paginate(10);

            // Get recent payments
            $recentPayments = $user->payments()
                ->with('course')
                ->latest()
                ->limit(5)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_payments' => $totalPayments,
                    'total_spent' => $totalSpent,
                    'formatted_total_spent' => '$' . number_format($totalSpent, 2),
                    'completed_payments' => $completedPayments,
                    'pending_payments' => $pendingPayments,
                    'failed_payments' => $failedPayments,
                    'refunded_payments' => $refundedPayments,
                    'payment_history' => $paymentHistory,
                    'recent_payments' => $recentPayments,
                ],
                'message' => 'Your payment statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Self payment statistics error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve your payment statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Check if user has paid for a course.
     */
    public function checkPaymentStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'user_id' => 'required_without:user_email|exists:users,id',
                'user_email' => 'required_without:user_id|email|exists:users,email',
                'course_id' => 'required_without:course_slug|exists:courses,id',
                'course_slug' => 'required_without:course_id|exists:courses,slug',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Get user ID
            $userId = $request->user_id;
            if (!$userId && $request->user_email) {
                $user = User::where('email', $request->user_email)->first();
                $userId = $user->id;
            }

            // Get course ID
            $courseId = $request->course_id;
            if (!$courseId && $request->course_slug) {
                $course = Course::where('slug', $request->course_slug)->first();
                $courseId = $course->id;
            }

            $hasPaid = Payment::hasUserPaidForCourse($userId, $courseId);
            $payment = null;

            if ($hasPaid) {
                $payment = Payment::where('user_id', $userId)
                    ->where('course_id', $courseId)
                    ->where('status', 'completed')
                    ->first();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'has_paid' => $hasPaid,
                    'payment' => $payment,
                    'user_id' => $userId,
                    'course_id' => $courseId,
                ],
                'message' => 'Payment status retrieved.'
            ]);

        } catch (\Exception $e) {
            Log::error('Check payment status error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk update payments.
     */
    public function bulkUpdate(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:payments,id',
                'action' => 'required|in:complete,fail,refund',
                'data' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $payments = Payment::whereIn('id', $request->ids)->get();
            $updatedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($payments as $payment) {
                try {
                    switch ($request->action) {
                        case 'complete':
                            if (!$payment->isCompleted()) {
                                $payment->markAsCompleted($request->data['metadata'] ?? []);
                                $updatedCount++;
                            }
                            break;
                        case 'fail':
                            if (!$payment->isFailed()) {
                                $payment->markAsFailed($request->data['reason'] ?? null);
                                $updatedCount++;
                            }
                            break;
                        case 'refund':
                            if ($payment->isCompleted() && !$payment->isRefunded()) {
                                $payment->markAsRefunded(
                                    $request->data['refund_id'] ?? null,
                                    $request->data['metadata'] ?? []
                                );
                                $updatedCount++;
                            }
                            break;
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to update payment {$payment->transaction_id}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk update completed.',
                'data' => [
                    'updated_count' => $updatedCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                    'action' => $request->action,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk update payments error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk update.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Bulk delete payments.
     */
    public function bulkDestroy(Request $request)
    {
        DB::beginTransaction();

        try {
            $validator = Validator::make($request->all(), [
                'ids' => 'required|array',
                'ids.*' => 'exists:payments,id',
                'force' => 'boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $payments = Payment::whereIn('id', $request->ids)->get();
            $deletedCount = 0;
            $failedCount = 0;
            $errors = [];

            foreach ($payments as $payment) {
                try {
                    if ($request->force || !$payment->isCompleted()) {
                        $payment->delete();
                        $deletedCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "Payment {$payment->transaction_id} is completed and cannot be deleted.";
                    }
                } catch (\Exception $e) {
                    $failedCount++;
                    $errors[] = "Failed to delete payment {$payment->transaction_id}: " . $e->getMessage();
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Bulk delete completed.',
                'data' => [
                    'deleted_count' => $deletedCount,
                    'failed_count' => $failedCount,
                    'errors' => $errors,
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Bulk delete payments error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to perform bulk delete.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get revenue statistics by period.
     */
    public function revenueStats(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'period' => 'nullable|in:daily,weekly,monthly,yearly',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date|after_or_equal:start_date',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors()
                ], 422);
            }

            $period = $request->period ?? 'monthly';
            $stats = Payment::getRevenueStats($period);

            return response()->json([
                'success' => true,
                'data' => $stats,
                'message' => 'Revenue statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Revenue stats error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve revenue statistics.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Get instructor revenue.
     */
    public function instructorRevenue($instructorId)
    {
        try {
            $instructor = User::findOrFail($instructorId);

            $revenue = Payment::getInstructorRevenue($instructorId);
            $courses = Course::where('instructor_id', $instructorId)->get();

            $courseRevenues = [];
            foreach ($courses as $course) {
                $courseRevenue = Payment::getCourseRevenue($course->id);
                $courseRevenues[] = [
                    'course' => $course->only(['id', 'title', 'slug']),
                    'revenue' => $courseRevenue,
                    'formatted_revenue' => '$' . number_format($courseRevenue, 2),
                ];
            }

            // Sort by revenue descending
            usort($courseRevenues, function ($a, $b) {
                return $b['revenue'] <=> $a['revenue'];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'instructor' => $instructor->only(['id', 'name', 'email']),
                    'total_revenue' => $revenue,
                    'formatted_total_revenue' => '$' . number_format($revenue, 2),
                    'course_revenues' => $courseRevenues,
                    'total_courses' => $courses->count(),
                ],
                'message' => 'Instructor revenue statistics retrieved successfully.'
            ]);

        } catch (\Exception $e) {
            Log::error('Instructor revenue error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve instructor revenue.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
}