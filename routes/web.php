<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LessonProgressController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Home/Dashboard Route
Route::get('/', function () {
    return view('layout');
});

// User Management Routes
Route::resource('users', UserController::class);

// Category Management Routes
Route::resource('categories', CategoryController::class);

// Course Management Routes
Route::resource('courses', CourseController::class);

// Lesson Management Routes
Route::resource('lessons', LessonController::class);

// Enrollment Management Routes
Route::resource('enrollments', EnrollmentController::class);

// Lesson Progress Management Routes
Route::resource('lesson-progress', LessonProgressController::class);

// Additional routes for Lesson Progress AJAX actions
Route::post('/lesson-progress/{id}/toggle', [LessonProgressController::class, 'toggle'])
    ->name('lesson-progress.toggle');

Route::post('/lesson-progress/bulk-complete', [LessonProgressController::class, 'bulkComplete'])
    ->name('lesson-progress.bulk-complete');

Route::get('/lesson-progress/stats', [LessonProgressController::class, 'stats'])
    ->name('lesson-progress.stats');

// Certificate Management Routes
Route::resource('certificates', CertificateController::class);

// Payment Management Routes
Route::resource('payments', PaymentController::class);

// Review Management Routes
Route::resource('reviews', ReviewController::class);

// Additional Routes for Reviews
// Single review approval (PUT method for RESTful)
Route::put('/reviews/{review}/approve', [ReviewController::class, 'approve'])
    ->name('reviews.approve');

// Single review disapproval
Route::put('/reviews/{review}/disapprove', [ReviewController::class, 'disapprove'])
    ->name('reviews.disapprove');

// Bulk approval
Route::post('/reviews/bulk-approve', [ReviewController::class, 'bulkApprove'])
    ->name('reviews.bulk-approve');

// Bulk delete
Route::post('/reviews/bulk-delete', [ReviewController::class, 'bulkDelete'])
    ->name('reviews.bulk-delete');

// Update rating
Route::put('/reviews/{review}/rating', [ReviewController::class, 'updateRating'])
    ->name('reviews.update-rating');

// Export reviews
Route::get('/reviews/export/{format?}', [ReviewController::class, 'export'])
    ->name('reviews.export');

// Stats
Route::get('/reviews/stats', [ReviewController::class, 'stats'])
    ->name('reviews.stats');

// Additional Routes for Course Enrollment (Student-facing)
Route::middleware(['auth'])->group(function () {
    // Student enroll in a course
    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'enroll'])
        ->name('courses.enroll');
    
    // Student dashboard
    Route::get('/student/dashboard', function () {
        return view('student.dashboard');
    })->name('student.dashboard');
    
    // Instructor dashboard
    Route::get('/instructor/dashboard', function () {
        return view('instructor.dashboard');
    })->name('instructor.dashboard');
    
    // Mark lesson as completed
    Route::post('/lessons/{lesson}/complete', [LessonProgressController::class, 'markComplete'])
        ->name('lessons.complete');
    
    // Mark lesson as incomplete
    Route::post('/lessons/{lesson}/incomplete', [LessonProgressController::class, 'markIncomplete'])
        ->name('lessons.incomplete');
    
    // Submit review for a course
    Route::post('/courses/{course}/review', [ReviewController::class, 'store'])
        ->name('courses.review.store');
    
    // Student-specific review routes
    Route::get('/student/reviews', [ReviewController::class, 'studentIndex'])
        ->name('student.reviews.index');
    
    Route::post('/student/reviews', [ReviewController::class, 'studentStore'])
        ->name('student.reviews.store');
});

// Authentication Routes (if not using Laravel Breeze/Jetstream)
Route::get('/login', function () {
    return view('auth.login');
})->name('login');

Route::get('/register', function () {
    return view('auth.register');
})->name('register');

// Optional: API-like routes for AJAX requests
Route::prefix('api')->group(function () {
    // Course API routes
    Route::get('/courses/{id}/lessons', [CourseController::class, 'getLessons'])
        ->name('api.courses.lessons');
    
    Route::get('/courses/{id}/stats', [CourseController::class, 'getStats'])
        ->name('api.courses.stats');
    
    // User API routes
    Route::get('/users/{id}/progress', [UserController::class, 'getProgress'])
        ->name('api.users.progress');
    
    // Lesson Progress API routes
    Route::get('/users/{id}/lesson-progress', [LessonProgressController::class, 'getUserProgress'])
        ->name('api.users.lesson-progress');
    
    Route::get('/lessons/{id}/progress-stats', [LessonProgressController::class, 'getLessonProgressStats'])
        ->name('api.lessons.progress-stats');
    
    Route::get('/check-progress-duplicate/{userId}/{lessonId}', [LessonProgressController::class, 'checkDuplicate'])
        ->name('api.check-progress-duplicate');
    
    // Review API routes
    Route::get('/reviews/stats', [ReviewController::class, 'apiStats'])
        ->name('api.reviews.stats');
    
    Route::get('/courses/{id}/reviews', [ReviewController::class, 'getCourseReviews'])
        ->name('api.courses.reviews');
    
    Route::get('/users/{id}/reviews', [ReviewController::class, 'getUserReviews'])
        ->name('api.users.reviews');
    
    Route::post('/reviews/{id}/toggle-approve', [ReviewController::class, 'toggleApprove'])
        ->name('api.reviews.toggle-approve');
    
    // Quick approval for reviews (AJAX)
    Route::post('/reviews/{id}/quick-approve', [ReviewController::class, 'quickApprove'])
        ->name('api.reviews.quick-approve');
});