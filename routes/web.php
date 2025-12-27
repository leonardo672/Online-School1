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
// Route::get('/', function () {
//     return view('dashboard');
// })->name('dashboard');

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

// Certificate Management Routes
Route::resource('certificates', CertificateController::class);

// Payment Management Routes
Route::resource('payments', PaymentController::class);

// Review Management Routes
Route::resource('reviews', ReviewController::class);

// Additional Routes for Course Enrollment (Student-facing)
Route::middleware(['auth'])->group(function () {
    // Student enroll in a course
    Route::post('/courses/{course}/enroll', [EnrollmentController::class, 'enroll'])->name('courses.enroll');
    
    // Student dashboard
    Route::get('/student/dashboard', function () {
        return view('student.dashboard');
    })->name('student.dashboard');
    
    // Instructor dashboard
    Route::get('/instructor/dashboard', function () {
        return view('instructor.dashboard');
    })->name('instructor.dashboard');
    
    // Mark lesson as completed
    Route::post('/lessons/{lesson}/complete', [LessonProgressController::class, 'markComplete'])->name('lessons.complete');
    
    // Submit review for a course
    Route::post('/courses/{course}/review', [ReviewController::class, 'store'])->name('courses.review.store');
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
    Route::get('/courses/{id}/lessons', [CourseController::class, 'getLessons'])->name('api.courses.lessons');
    Route::get('/users/{id}/progress', [UserController::class, 'getProgress'])->name('api.users.progress');
    Route::get('/courses/{id}/stats', [CourseController::class, 'getStats'])->name('api.courses.stats');
});