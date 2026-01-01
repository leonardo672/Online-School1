@extends('layout')

@section('content')

<!-- Main Card for Review Creation -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-star"></i> Create New Review</h2>
            <a href="{{ url('/reviews') }}" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Reviews
            </a>
        </div>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>Oops! Something went wrong.</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <!-- Review Form -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-edit"></i> Review Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ url('/reviews') }}" id="reviewForm">
                            @csrf
                            
                            <!-- User Selection -->
                            <div class="mb-4">
                                <label for="user_id" class="form-label">
                                    <i class="fas fa-user me-1"></i> User *
                                </label>
                                <select name="user_id" id="user_id" 
                                        class="form-select @error('user_id') is-invalid @enderror" required>
                                    <option value="">Select a User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" 
                                            {{ old('user_id') == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select the user who is submitting this review.</small>
                            </div>

                            <!-- Course Selection -->
                            <div class="mb-4">
                                <label for="course_id" class="form-label">
                                    <i class="fas fa-book me-1"></i> Course *
                                </label>
                                <select name="course_id" id="course_id" 
                                        class="form-select @error('course_id') is-invalid @enderror" required>
                                    <option value="">Select a Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" 
                                            {{ old('course_id') == $course->id ? 'selected' : '' }}>
                                            {{ $course->title }} ({{ $course->code ?? 'No Code' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('course_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select the course being reviewed.</small>
                            </div>

                            <!-- Rating Selection -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-star me-1"></i> Rating *
                                </label>
                                <div class="rating-input mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="stars-display me-3">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star fa-2x star-selectable" 
                                                   data-rating="{{ $i }}" 
                                                   style="cursor: pointer; color: #ddd; margin-right: 5px;"
                                                   onmouseover="hoverStar(this)"
                                                   onmouseout="resetStars()"
                                                   onclick="selectRating({{ $i }})"></i>
                                            @endfor
                                        </div>
                                        <div>
                                            <span id="rating-text" class="text-muted">Click to rate</span>
                                            <div class="rating-labels">
                                                <small class="text-muted" id="rating-label">Not rated yet</small>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="rating" id="rating" 
                                           value="{{ old('rating') }}" required>
                                    @error('rating')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <small class="text-muted">Select a rating from 1 (poor) to 5 (excellent).</small>
                            </div>

                            <!-- Comment -->
                            <div class="mb-4">
                                <label for="comment" class="form-label">
                                    <i class="fas fa-comment me-1"></i> Review Comment
                                </label>
                                <textarea name="comment" id="comment" 
                                          class="form-control @error('comment') is-invalid @enderror" 
                                          rows="5" 
                                          placeholder="Share your thoughts about this course...">{{ old('comment') }}</textarea>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted">Optional but recommended for detailed feedback.</small>
                                    <small class="text-muted"><span id="charCount">0</span>/1000 characters</small>
                                </div>
                                @error('comment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                                <a href="{{ url('/reviews') }}" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-add-review">
                                    <i class="fas fa-save"></i> Create Review
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Guidelines Card -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Review Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><i class="fas fa-star text-warning"></i> Rating Guidelines:</h6>
                            <ul class="list-unstyled">
                                <li><small><strong>5 Stars:</strong> Excellent - Exceeded all expectations</small></li>
                                <li><small><strong>4 Stars:</strong> Good - Met most expectations</small></li>
                                <li><small><strong>3 Stars:</strong> Average - Met basic requirements</small></li>
                                <li><small><strong>2 Stars:</strong> Below Average - Needs improvement</small></li>
                                <li><small><strong>1 Star:</strong> Poor - Did not meet expectations</small></li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-comment text-primary"></i> Writing Tips:</h6>
                            <ul class="list-unstyled">
                                <li><small>• Be specific and detailed</small></li>
                                <li><small>• Focus on course content and delivery</small></li>
                                <li><small>• Share both strengths and areas for improvement</small></li>
                                <li><small>• Be constructive and respectful</small></li>
                            </ul>
                        </div>
                        
                        <div>
                            <h6><i class="fas fa-shield-alt text-success"></i> Note:</h6>
                            <p class="small mb-0">
                                Reviews should provide honest, helpful feedback that benefits both 
                                course instructors and future students.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- User Stats Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> User Review Stats</h5>
                    </div>
                    <div class="card-body">
                        <div id="userStats">
                            <div class="text-center py-4">
                                <i class="fas fa-user-circle fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Select a user to see their review statistics</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Course Stats Card -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-book"></i> Course Review Stats</h5>
                    </div>
                    <div class="card-body">
                        <div id="courseStats">
                            <div class="text-center py-4">
                                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                                <p class="text-muted">Select a course to see review statistics</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Inherit styles from index page */
    .card {
        border: none;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        border-radius: 15px;
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(145deg, #f39c12, #e67e22);
        color: white;
        padding: 20px 30px;
        border-bottom: none;
    }
    
    .card-header h2 {
        margin: 0;
        font-weight: 600;
        font-size: 1.8rem;
    }
    
    .header-bg {
        background: linear-gradient(145deg, #f39c12, #e67e22);
    }
    
    .card-body {
        padding: 30px;
    }

    /* Form Styles */
    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }
    
    .form-control, .form-select {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px 15px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #f39c12;
        box-shadow: 0 0 0 0.25rem rgba(243, 156, 18, 0.25);
    }
    
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc3545;
    }
    
    textarea.form-control {
        min-height: 120px;
        resize: vertical;
    }

    /* Rating Stars */
    .star-selectable {
        transition: all 0.2s ease;
    }
    
    .star-selectable:hover {
        transform: scale(1.2);
    }
    
    .star-selected {
        color: #f39c12 !important;
    }
    
    .star-hover {
        color: #f1c40f !important;
    }

    /* Stats Cards */
    .stats-card {
        padding: 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }
    
    .stats-card h6 {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 5px;
    }
    
    .stats-card h4 {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
    }

    /* Alert */
    .alert {
        border: none;
        border-radius: 10px;
        padding: 15px 20px;
    }
    
    .alert-danger {
        background-color: #fde8e8;
        color: #c53030;
        border-left: 4px solid #c53030;
    }

    /* Button Styles - Consistent with index */
    .btn {
        font-size: 14px;
        padding: 10px 20px;
        margin: 5px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        outline: none;
        transition: all 0.3s ease-in-out;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-transform: capitalize;
    }

    .btn-add-review {
        background: linear-gradient(145deg, #f39c12, #e67e22);
        color: white;
        font-weight: 600;
        padding: 12px 25px;
    }

    .btn-add-review:hover {
        background: linear-gradient(145deg, #e67e22, #d35400);
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(243, 156, 18, 0.4);
        color: white;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        color: white;
    }

    .btn-outline-light {
        border: 1px solid rgba(255, 255, 255, 0.5);
        color: white;
    }

    .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: white;
        color: white;
    }

    /* Guidelines */
    .list-unstyled li {
        padding: 3px 0;
        border-bottom: 1px dashed #eee;
    }
    
    .list-unstyled li:last-child {
        border-bottom: none;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Character counter for comment
        const commentTextarea = document.getElementById('comment');
        const charCount = document.getElementById('charCount');
        
        commentTextarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = length;
            
            // Change color if approaching limit
            if (length > 800) {
                charCount.style.color = '#f39c12';
            } else if (length > 950) {
                charCount.style.color = '#e74c3c';
            } else {
                charCount.style.color = '#666';
            }
        });

        // Initialize character count
        charCount.textContent = commentTextarea.value.length;

        // Initialize rating if there's an old value
        const ratingValue = document.getElementById('rating').value;
        if (ratingValue) {
            selectRating(ratingValue);
        }

        // User selection change - load user stats
        const userSelect = document.getElementById('user_id');
        userSelect.addEventListener('change', function() {
            loadUserStats(this.value);
        });

        // Course selection change - load course stats
        const courseSelect = document.getElementById('course_id');
        courseSelect.addEventListener('change', function() {
            loadCourseStats(this.value);
        });

        // Form validation
        const form = document.getElementById('reviewForm');
        form.addEventListener('submit', function(e) {
            const rating = document.getElementById('rating').value;
            const userId = document.getElementById('user_id').value;
            const courseId = document.getElementById('course_id').value;
            
            let isValid = true;
            let errorMessage = '';
            
            if (!userId) {
                isValid = false;
                errorMessage = 'Please select a user';
                document.getElementById('user_id').focus();
            } else if (!courseId) {
                isValid = false;
                errorMessage = 'Please select a course';
                document.getElementById('course_id').focus();
            } else if (!rating) {
                isValid = false;
                errorMessage = 'Please select a rating';
            }
            
            if (!isValid) {
                e.preventDefault();
                showToast(errorMessage, 'error');
                return false;
            }
            
            // Check for duplicate review (optional)
            checkDuplicateReview(userId, courseId).then(isDuplicate => {
                if (isDuplicate) {
                    const confirmDuplicate = confirm(
                        'This user has already reviewed this course. Do you want to continue?'
                    );
                    if (!confirmDuplicate) {
                        e.preventDefault();
                    }
                }
            });
        });

        // Prevent double form submission
        let isSubmitting = false;
        form.addEventListener('submit', function() {
            if (isSubmitting) {
                event.preventDefault();
                return false;
            }
            isSubmitting = true;
            
            // Disable submit button
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
        });
    });

    // Star rating functions
    let currentRating = 0;
    let tempRating = 0;

    function hoverStar(star) {
        const rating = parseInt(star.getAttribute('data-rating'));
        tempRating = rating;
        updateStarDisplay(rating, false);
    }

    function resetStars() {
        updateStarDisplay(currentRating, false);
    }

    function selectRating(rating) {
        currentRating = rating;
        document.getElementById('rating').value = rating;
        updateStarDisplay(rating, true);
        
        // Update rating text
        const ratingText = document.getElementById('rating-text');
        const ratingLabel = document.getElementById('rating-label');
        ratingText.textContent = rating + '/5';
        
        // Set descriptive label
        const labels = {
            1: 'Poor - Did not meet expectations',
            2: 'Below Average - Needs improvement',
            3: 'Average - Met basic requirements',
            4: 'Good - Met most expectations',
            5: 'Excellent - Exceeded all expectations'
        };
        ratingLabel.textContent = labels[rating];
        
        // Show success feedback
        showToast(`Rating set to ${rating} stars`, 'success');
    }

    function updateStarDisplay(rating, isSelected) {
        const stars = document.querySelectorAll('.star-selectable');
        stars.forEach((star, index) => {
            const starRating = parseInt(star.getAttribute('data-rating'));
            
            if (isSelected) {
                // Permanent selection
                if (starRating <= rating) {
                    star.classList.add('star-selected');
                    star.classList.remove('star-hover');
                } else {
                    star.classList.remove('star-selected', 'star-hover');
                }
            } else {
                // Temporary hover state
                if (starRating <= rating) {
                    star.classList.add('star-hover');
                    star.classList.remove('star-selected');
                } else {
                    star.classList.remove('star-hover');
                }
            }
        });
    }

    // Load user statistics
    async function loadUserStats(userId) {
        if (!userId) {
            document.getElementById('userStats').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-user-circle fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Select a user to see their review statistics</p>
                </div>
            `;
            return;
        }
        
        try {
            const response = await fetch(`/api/users/${userId}/review-stats`);
            const data = await response.json();
            
            document.getElementById('userStats').innerHTML = `
                <div class="stats-card bg-light">
                    <h6>Total Reviews</h6>
                    <h4 class="text-primary">${data.total_reviews || 0}</h4>
                </div>
                <div class="stats-card bg-light">
                    <h6>Average Rating</h6>
                    <h4 class="text-warning">${data.average_rating ? data.average_rating.toFixed(1) : 'N/A'}</h4>
                </div>
                <div class="stats-card bg-light">
                    <h6>Last Review</h6>
                    <h4 class="text-success">${data.last_review ? data.last_review : 'Never'}</h4>
                </div>
            `;
        } catch (error) {
            console.error('Error loading user stats:', error);
        }
    }

    // Load course statistics
    async function loadCourseStats(courseId) {
        if (!courseId) {
            document.getElementById('courseStats').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Select a course to see review statistics</p>
                </div>
            `;
            return;
        }
        
        try {
            const response = await fetch(`/api/courses/${courseId}/review-stats`);
            const data = await response.json();
            
            document.getElementById('courseStats').innerHTML = `
                <div class="stats-card bg-light">
                    <h6>Total Reviews</h6>
                    <h4 class="text-primary">${data.total_reviews || 0}</h4>
                </div>
                <div class="stats-card bg-light">
                    <h6>Average Rating</h6>
                    <h4 class="text-warning">${data.average_rating ? data.average_rating.toFixed(1) : 'N/A'}</h4>
                </div>
                <div class="stats-card bg-light">
                    <h6>Rating Distribution</h6>
                    <div class="mt-2">
                        <small class="d-block">5★: ${data.rating_5 || 0}</small>
                        <small class="d-block">4★: ${data.rating_4 || 0}</small>
                        <small class="d-block">3★: ${data.rating_3 || 0}</small>
                        <small class="d-block">2★: ${data.rating_2 || 0}</small>
                        <small class="d-block">1★: ${data.rating_1 || 0}</small>
                    </div>
                </div>
            `;
        } catch (error) {
            console.error('Error loading course stats:', error);
        }
    }

    // Check for duplicate review
    async function checkDuplicateReview(userId, courseId) {
        if (!userId || !courseId) return false;
        
        try {
            const response = await fetch(`/api/check-duplicate-review?user_id=${userId}&course_id=${courseId}`);
            const data = await response.json();
            return data.duplicate || false;
        } catch (error) {
            console.error('Error checking duplicate review:', error);
            return false;
        }
    }

    // Toast notification function (same as in index)
    function showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
        toast.style.zIndex = '9999';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : 'fa-info-circle'} me-2"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.remove(), 3000);
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S to submit form
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            document.getElementById('reviewForm').submit();
        }
        
        // Esc to cancel/go back
        if (e.key === 'Escape') {
            window.location.href = '/reviews';
        }
    });
</script>

@endsection