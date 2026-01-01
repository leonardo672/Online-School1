@extends('layout')
@section('content')

<!-- Main Card for Editing Review -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h2 class="mb-0 me-3"><i class="fas fa-edit"></i> Edit Review</h2>
                <div class="badge bg-light text-dark px-3 py-2">
                    <i class="fas fa-hashtag"></i> #{{ $review->id }}
                </div>
            </div>
            <div class="d-flex align-items-center">
                <!-- Action Buttons -->
                <a href="{{ url('/reviews') }}" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <a href="{{ url('/reviews/' . $review->id) }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-eye"></i> View
                </a>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Display Errors -->
        @if ($errors->any())
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <h6 class="mb-1">Please fix the following errors:</h6>
                        <ul class="mb-0 ps-3">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Success Message -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <!-- Edit Form -->
        <form method="POST" action="{{ url('/reviews/' . $review->id) }}" id="editReviewForm">
            @csrf
            @method('PUT')

            <div class="row">
                <!-- Left Column: Basic Information -->
                <div class="col-lg-8">
                    <!-- Basic Information Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Basic Information</h5>
                        </div>
                        <div class="card-body">
                            <!-- User Selection -->
                            <div class="mb-4">
                                <label for="user_id" class="form-label">
                                    <i class="fas fa-user me-1"></i> User <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('user_id') is-invalid @enderror" 
                                        id="user_id" name="user_id" required>
                                    <option value="">Select a user...</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" 
                                                {{ old('user_id', $review->user_id) == $user->id ? 'selected' : '' }}>
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select the user who wrote this review</small>
                            </div>

                            <!-- Course Selection -->
                            <div class="mb-4">
                                <label for="course_id" class="form-label">
                                    <i class="fas fa-book me-1"></i> Course <span class="text-danger">*</span>
                                </label>
                                <select class="form-select @error('course_id') is-invalid @enderror" 
                                        id="course_id" name="course_id" required>
                                    <option value="">Select a course...</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" 
                                                {{ old('course_id', $review->course_id) == $course->id ? 'selected' : '' }}>
                                            {{ $course->title }} 
                                            @if($course->code)
                                                ({{ $course->code }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                @error('course_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select the course being reviewed</small>
                            </div>

                            <!-- Rating Selection -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-star me-1"></i> Rating <span class="text-danger">*</span>
                                </label>
                                <div class="rating-select">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <div class="d-flex">
                                            @for($i = 1; $i <= 5; $i++)
                                                <div class="star-container me-2">
                                                    <input type="radio" 
                                                           id="rating-{{ $i }}" 
                                                           name="rating" 
                                                           value="{{ $i }}" 
                                                           class="d-none rating-input"
                                                           {{ old('rating', $review->rating) == $i ? 'checked' : '' }}>
                                                    <label for="rating-{{ $i }}" 
                                                           class="star-label {{ old('rating', $review->rating) >= $i ? 'active' : '' }}"
                                                           onclick="selectRating({{ $i }})"
                                                           onmouseover="hoverRating({{ $i }})"
                                                           onmouseout="resetRating()">
                                                        <i class="fas fa-star fa-2x"></i>
                                                        <span class="rating-text">{{ $i }}</span>
                                                    </label>
                                                </div>
                                            @endfor
                                        </div>
                                        <div class="rating-display">
                                            <span class="rating-value">
                                                <span id="currentRating">{{ old('rating', $review->rating) }}</span>/5
                                            </span>
                                            <div class="rating-description small text-muted" id="ratingDescription">
                                                @php
                                                    $ratingDescriptions = [
                                                        1 => 'Poor - Did not meet expectations',
                                                        2 => 'Below Average - Needs improvement',
                                                        3 => 'Average - Met basic requirements',
                                                        4 => 'Good - Met most expectations',
                                                        5 => 'Excellent - Exceeded all expectations'
                                                    ];
                                                @endphp
                                                {{ $ratingDescriptions[old('rating', $review->rating)] ?? 'Select a rating' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @error('rating')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Click on stars to select a rating from 1 to 5</small>
                            </div>

                            <!-- Approval Status -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-check-circle me-1"></i> Approval Status
                                </label>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" 
                                           id="approved" name="approved" 
                                           value="1" 
                                           {{ old('approved', $review->approved) ? 'checked' : '' }}
                                           style="width: 3em; height: 1.5em;">
                                    <label class="form-check-label" for="approved">
                                        <span id="statusText">{{ old('approved', $review->approved) ? 'Approved' : 'Pending' }}</span>
                                    </label>
                                </div>
                                <small class="text-muted">Toggle to approve or disapprove this review</small>
                            </div>
                        </div>
                    </div>

                    <!-- Comment Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-comment"></i> Review Comment</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="comment" class="form-label">
                                    <i class="fas fa-pen me-1"></i> Comment
                                </label>
                                <textarea class="form-control @error('comment') is-invalid @enderror" 
                                          id="comment" 
                                          name="comment" 
                                          rows="8" 
                                          placeholder="Enter your review comment here...">{{ old('comment', $review->comment) }}</textarea>
                                @error('comment')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <span id="charCount">{{ strlen(old('comment', $review->comment)) }}</span>/1000 characters
                                </small>
                                <div class="btn-group">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="formatText('bold')">
                                        <i class="fas fa-bold"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="formatText('italic')">
                                        <i class="fas fa-italic"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" onclick="insertEmoji('😊')">
                                        <i class="fas fa-smile"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Preview & Actions -->
                <div class="col-lg-4">
                    <!-- Preview Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="fas fa-eye"></i> Live Preview</h5>
                        </div>
                        <div class="card-body">
                            <div class="preview-content">
                                <div class="preview-header mb-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Review Preview</h6>
                                        <small class="text-muted">Live Update</small>
                                    </div>
                                </div>
                                
                                <div class="preview-rating mb-3">
                                    <div class="d-flex align-items-center">
                                        <div class="stars-preview" id="starsPreview">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star{{ $i <= old('rating', $review->rating) ? ' text-warning' : ' text-muted' }}"></i>
                                            @endfor
                                        </div>
                                        <span class="ms-2" id="previewRatingValue">{{ old('rating', $review->rating) }}/5</span>
                                    </div>
                                </div>
                                
                                <div class="preview-user mb-3">
                                    <small class="text-muted d-block mb-1">User:</small>
                                    <strong id="previewUserName">
                                        @php
                                            $selectedUser = $users->firstWhere('id', old('user_id', $review->user_id));
                                        @endphp
                                        {{ $selectedUser->name ?? 'Select a user' }}
                                    </strong>
                                    <br>
                                    <small class="text-muted" id="previewUserEmail">
                                        {{ $selectedUser->email ?? '' }}
                                    </small>
                                </div>
                                
                                <div class="preview-course mb-3">
                                    <small class="text-muted d-block mb-1">Course:</small>
                                    <strong id="previewCourseTitle">
                                        @php
                                            $selectedCourse = $courses->firstWhere('id', old('course_id', $review->course_id));
                                        @endphp
                                        {{ $selectedCourse->title ?? 'Select a course' }}
                                    </strong>
                                    <br>
                                    <small class="text-muted" id="previewCourseCode">
                                        {{ $selectedCourse->code ?? '' }}
                                    </small>
                                </div>
                                
                                <div class="preview-comment">
                                    <small class="text-muted d-block mb-1">Comment Preview:</small>
                                    <div class="comment-preview bg-light p-3 rounded" style="max-height: 200px; overflow-y: auto;">
                                        <p id="previewComment" class="mb-0">
                                            @if(old('comment', $review->comment))
                                                {{ Str::limit(old('comment', $review->comment), 150) }}
                                            @else
                                                <span class="text-muted">No comment yet</span>
                                            @endif
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="preview-status mt-3">
                                    <small class="text-muted d-block mb-1">Status:</small>
                                    <span class="badge bg-{{ old('approved', $review->approved) ? 'success' : 'warning' }}" id="previewStatusBadge">
                                        {{ old('approved', $review->approved) ? 'Approved' : 'Pending' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions Card -->
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save me-2"></i> Save Changes
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                    <i class="fas fa-undo me-2"></i> Reset Form
                                </button>
                                <button type="button" class="btn btn-outline-warning" onclick="previewChanges()">
                                    <i class="fas fa-search me-2"></i> Preview Changes
                                </button>
                                <a href="{{ url('/reviews/' . $review->id) }}" class="btn btn-outline-danger">
                                    <i class="fas fa-times me-2"></i> Cancel
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Card -->
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Edit Statistics</h5>
                        </div>
                        <div class="card-body">
                            <div class="stats-list">
                                <div class="stat-item d-flex justify-content-between mb-2">
                                    <span class="text-muted">Last Updated:</span>
                                    <strong>{{ $review->updated_at->diffForHumans() }}</strong>
                                </div>
                                <div class="stat-item d-flex justify-content-between mb-2">
                                    <span class="text-muted">Created:</span>
                                    <strong>{{ $review->created_at->format('M d, Y') }}</strong>
                                </div>
                                <div class="stat-item d-flex justify-content-between mb-2">
                                    <span class="text-muted">Comment Length:</span>
                                    <strong>{{ strlen($review->comment ?? '') }} chars</strong>
                                </div>
                                <div class="stat-item d-flex justify-content-between">
                                    <span class="text-muted">Edit Count:</span>
                                    <strong>{{ $review->updated_at != $review->created_at ? '1+' : '0' }}</strong>
                                </div>
                            </div>
                            <div class="mt-3 text-center">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle"></i> 
                                    This review was created {{ $review->created_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden Fields -->
            <input type="hidden" id="original_rating" value="{{ $review->rating }}">
            <input type="hidden" id="original_user_id" value="{{ $review->user_id }}">
            <input type="hidden" id="original_course_id" value="{{ $review->course_id }}">
            <input type="hidden" id="original_approved" value="{{ $review->approved }}">
            <input type="hidden" id="original_comment" value="{{ $review->comment }}">
        </form>
    </div>
</div>

<!-- Preview Changes Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-search"></i> Preview Changes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">Original Values</h6>
                        <div class="original-values">
                            <div class="value-item mb-3">
                                <small class="text-muted d-block">Rating:</small>
                                <div class="d-flex align-items-center">
                                    @for($i = 1; $i <= 5; $i++)
                                        <i class="fas fa-star{{ $i <= $review->rating ? ' text-warning' : ' text-muted' }}"></i>
                                    @endfor
                                    <span class="ms-2">{{ $review->rating }}/5</span>
                                </div>
                            </div>
                            <div class="value-item mb-3">
                                <small class="text-muted d-block">User:</small>
                                <strong>{{ $review->user->name ?? 'N/A' }}</strong>
                            </div>
                            <div class="value-item mb-3">
                                <small class="text-muted d-block">Course:</small>
                                <strong>{{ $review->course->title ?? 'N/A' }}</strong>
                            </div>
                            <div class="value-item mb-3">
                                <small class="text-muted d-block">Status:</small>
                                <span class="badge bg-{{ $review->approved ? 'success' : 'warning' }}">
                                    {{ $review->approved ? 'Approved' : 'Pending' }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-muted mb-3">New Values</h6>
                        <div class="new-values" id="newValuesPreview">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
                <div class="mt-4">
                    <h6 class="text-muted mb-2">Comment Comparison</h6>
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">Original:</small>
                            <div class="border rounded p-2 bg-light" style="max-height: 150px; overflow-y: auto;">
                                <p class="mb-0">{{ $review->comment ?? 'No comment' }}</p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block mb-1">New:</small>
                            <div class="border rounded p-2 bg-light" style="max-height: 150px; overflow-y: auto;">
                                <p class="mb-0" id="newCommentPreview">{{ old('comment', $review->comment) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="alert alert-info mt-4">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <small>This preview shows the changes you're about to make. Click "Save Changes" to apply them.</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveChanges()">
                    <i class="fas fa-save me-2"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Inherit base styles from other review pages */
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
    
    .header-bg {
        background: linear-gradient(145deg, #f39c12, #e67e22);
    }
    
    .card-body {
        padding: 30px;
    }

    /* Form Elements */
    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }
    
    .form-control, .form-select {
        border-radius: 8px;
        border: 1px solid #ced4da;
        padding: 12px 15px;
        font-size: 14px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #f39c12;
        box-shadow: 0 0 0 0.25rem rgba(243, 156, 18, 0.25);
    }
    
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc3545;
    }
    
    .form-control.is-invalid:focus, .form-select.is-invalid:focus {
        box-shadow: 0 0 0 0.25rem rgba(220, 53, 69, 0.25);
    }

    /* Rating Stars */
    .rating-select .star-label {
        cursor: pointer;
        position: relative;
        transition: all 0.3s ease;
    }
    
    .rating-select .star-label i {
        color: #ddd;
        transition: all 0.3s ease;
    }
    
    .rating-select .star-label.active i,
    .rating-select .star-label:hover i {
        color: #f39c12;
        transform: scale(1.1);
    }
    
    .rating-select .rating-text {
        position: absolute;
        top: -20px;
        left: 50%;
        transform: translateX(-50%);
        font-size: 12px;
        font-weight: bold;
        color: #666;
        opacity: 0;
        transition: opacity 0.3s ease;
    }
    
    .rating-select .star-label:hover .rating-text {
        opacity: 1;
    }
    
    .rating-value {
        font-size: 24px;
        font-weight: bold;
        color: #f39c12;
    }
    
    .rating-description {
        margin-top: 5px;
        font-style: italic;
    }

    /* Form Switch */
    .form-check-input:checked {
        background-color: #2ecc71;
        border-color: #2ecc71;
    }
    
    .form-check-input:focus {
        border-color: #2ecc71;
        box-shadow: 0 0 0 0.25rem rgba(46, 204, 113, 0.25);
    }

    /* Textarea */
    textarea.form-control {
        resize: vertical;
        min-height: 150px;
    }

    /* Button Styles */
    .btn {
        font-size: 14px;
        padding: 12px 20px;
        border-radius: 8px;
        font-weight: 500;
        transition: all 0.3s ease;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    .btn-success {
        background: linear-gradient(145deg, #2ecc71, #27ae60);
        border: none;
        color: white;
    }
    
    .btn-success:hover {
        background: linear-gradient(145deg, #27ae60, #219653);
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
        color: white;
    }
    
    .btn-outline-secondary {
        border: 2px solid #6c757d;
        color: #6c757d;
    }
    
    .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: white;
    }
    
    .btn-outline-warning {
        border: 2px solid #f39c12;
        color: #f39c12;
    }
    
    .btn-outline-warning:hover {
        background-color: #f39c12;
        color: white;
    }
    
    .btn-outline-danger {
        border: 2px solid #dc3545;
        color: #dc3545;
    }
    
    .btn-outline-danger:hover {
        background-color: #dc3545;
        color: white;
    }

    /* Alert Styles */
    .alert {
        border: none;
        border-radius: 10px;
        padding: 15px 20px;
    }
    
    .alert-danger {
        background-color: #fde8e8;
        color: #c53030;
        border-left: 4px solid #dc3545;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #2ecc71;
    }
    
    .alert-info {
        background-color: #e8f4fd;
        color: #31708f;
        border-left: 4px solid #3498db;
    }

    /* Preview Styling */
    .preview-content {
        padding: 15px;
    }
    
    .stars-preview i {
        font-size: 18px;
    }
    
    .comment-preview {
        font-size: 13px;
        line-height: 1.5;
    }

    /* Statistics */
    .stats-list .stat-item {
        padding: 8px 0;
        border-bottom: 1px dashed #e9ecef;
    }
    
    .stats-list .stat-item:last-child {
        border-bottom: none;
    }

    /* Modal Styling */
    .modal-content {
        border-radius: 15px;
        border: none;
    }
    
    .modal-header {
        background: linear-gradient(145deg, #f39c12, #e67e22);
        color: white;
        border-bottom: none;
    }
    
    .original-values, .new-values {
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        border: 1px solid #e9ecef;
    }

    /* Animation for changes */
    @keyframes highlightChange {
        0% { background-color: #fff3cd; }
        100% { background-color: transparent; }
    }
    
    .changed {
        animation: highlightChange 2s ease;
    }

    /* Character counter */
    #charCount {
        font-weight: bold;
        color: #3498db;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize form with current values
        initializeForm();
        
        // Add event listeners for live preview
        document.getElementById('user_id').addEventListener('change', updatePreview);
        document.getElementById('course_id').addEventListener('change', updatePreview);
        document.getElementById('approved').addEventListener('change', updatePreview);
        document.getElementById('comment').addEventListener('input', updatePreview);
        
        // Initialize character counter
        updateCharCount();
        
        // Add form validation
        document.getElementById('editReviewForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
                showToast('Please fix the errors in the form', 'error');
            }
        });
    });

    // Rating functions
    let currentRating = {{ old('rating', $review->rating) }};
    let hoverRating = 0;

    function selectRating(rating) {
        currentRating = rating;
        updateRatingDisplay();
        
        // Update hidden radio button
        document.querySelector(`#rating-${rating}`).checked = true;
        
        // Update live preview
        updatePreview();
    }

    function hoverRating(rating) {
        hoverRating = rating;
        updateRatingDisplay(true);
    }

    function resetRating() {
        hoverRating = 0;
        updateRatingDisplay();
    }

    function updateRatingDisplay(isHovering = false) {
        const ratingToShow = isHovering ? hoverRating : currentRating;
        const ratingDescriptions = {
            1: 'Poor - Did not meet expectations',
            2: 'Below Average - Needs improvement',
            3: 'Average - Met basic requirements',
            4: 'Good - Met most expectations',
            5: 'Excellent - Exceeded all expectations'
        };
        
        // Update stars
        document.querySelectorAll('.star-label').forEach((label, index) => {
            const starIndex = index + 1;
            if (starIndex <= ratingToShow) {
                label.classList.add('active');
            } else {
                label.classList.remove('active');
            }
        });
        
        // Update rating display
        document.getElementById('currentRating').textContent = currentRating;
        document.getElementById('ratingDescription').textContent = ratingDescriptions[currentRating] || 'Select a rating';
    }

    // Approval status toggle
    document.getElementById('approved').addEventListener('change', function() {
        document.getElementById('statusText').textContent = this.checked ? 'Approved' : 'Pending';
        updatePreview();
    });

    // Comment character counter
    document.getElementById('comment').addEventListener('input', function() {
        updateCharCount();
        updatePreview();
    });

    function updateCharCount() {
        const comment = document.getElementById('comment').value;
        document.getElementById('charCount').textContent = comment.length;
        
        // Show warning if approaching limit
        if (comment.length > 900) {
            document.getElementById('charCount').style.color = '#dc3545';
        } else if (comment.length > 700) {
            document.getElementById('charCount').style.color = '#f39c12';
        } else {
            document.getElementById('charCount').style.color = '#3498db';
        }
    }

    // Text formatting functions
    function formatText(type) {
        const textarea = document.getElementById('comment');
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const selectedText = textarea.value.substring(start, end);
        
        let formattedText = '';
        switch(type) {
            case 'bold':
                formattedText = `**${selectedText}**`;
                break;
            case 'italic':
                formattedText = `*${selectedText}*`;
                break;
            default:
                formattedText = selectedText;
        }
        
        textarea.value = textarea.value.substring(0, start) + formattedText + textarea.value.substring(end);
        textarea.focus();
        textarea.setSelectionRange(start + formattedText.length, start + formattedText.length);
        updatePreview();
    }

    function insertEmoji(emoji) {
        const textarea = document.getElementById('comment');
        const start = textarea.selectionStart;
        textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(start);
        textarea.focus();
        textarea.setSelectionRange(start + emoji.length, start + emoji.length);
        updatePreview();
    }

    // Live preview update
    function updatePreview() {
        // Get current form values
        const userId = document.getElementById('user_id').value;
        const courseId = document.getElementById('course_id').value;
        const rating = currentRating;
        const approved = document.getElementById('approved').checked;
        const comment = document.getElementById('comment').value;
        
        // Update rating preview
        const starsPreview = document.getElementById('starsPreview');
        starsPreview.innerHTML = '';
        for (let i = 1; i <= 5; i++) {
            const star = document.createElement('i');
            star.className = `fas fa-star${i <= rating ? ' text-warning' : ' text-muted'}`;
            starsPreview.appendChild(star);
        }
        document.getElementById('previewRatingValue').textContent = `${rating}/5`;
        
        // Update user preview
        const userSelect = document.getElementById('user_id');
        const selectedUserOption = userSelect.options[userSelect.selectedIndex];
        document.getElementById('previewUserName').textContent = selectedUserOption.text.split(' (')[0] || 'Select a user';
        document.getElementById('previewUserEmail').textContent = selectedUserOption.text.includes('(') 
            ? selectedUserOption.text.split('(')[1].replace(')', '')
            : '';
        
        // Update course preview
        const courseSelect = document.getElementById('course_id');
        const selectedCourseOption = courseSelect.options[courseSelect.selectedIndex];
        const courseText = selectedCourseOption.text || '';
        const courseParts = courseText.split(' (');
        document.getElementById('previewCourseTitle').textContent = courseParts[0] || 'Select a course';
        document.getElementById('previewCourseCode').textContent = courseParts[1] 
            ? courseParts[1].replace(')', '')
            : '';
        
        // Update comment preview
        const commentPreview = document.getElementById('previewComment');
        if (comment.trim()) {
            commentPreview.textContent = comment.length > 150 ? comment.substring(0, 150) + '...' : comment;
            commentPreview.classList.remove('text-muted');
        } else {
            commentPreview.textContent = 'No comment yet';
            commentPreview.classList.add('text-muted');
        }
        
        // Update status preview
        const statusBadge = document.getElementById('previewStatusBadge');
        statusBadge.className = `badge bg-${approved ? 'success' : 'warning'}`;
        statusBadge.textContent = approved ? 'Approved' : 'Pending';
        
        // Highlight changes
        highlightChanges();
    }

    // Highlight changes from original
    function highlightChanges() {
        const originalRating = parseInt(document.getElementById('original_rating').value);
        const originalUserId = document.getElementById('original_user_id').value;
        const originalCourseId = document.getElementById('original_course_id').value;
        const originalApproved = document.getElementById('original_approved').value === '1';
        const originalComment = document.getElementById('original_comment').value;
        
        const currentRating = parseInt(document.querySelector('input[name="rating"]:checked')?.value || originalRating);
        const currentUserId = document.getElementById('user_id').value;
        const currentCourseId = document.getElementById('course_id').value;
        const currentApproved = document.getElementById('approved').checked;
        const currentComment = document.getElementById('comment').value;
        
        // Highlight changed elements
        if (currentRating !== originalRating) {
            document.getElementById('previewRatingValue').classList.add('changed');
        }
        
        if (currentUserId !== originalUserId) {
            document.getElementById('previewUserName').classList.add('changed');
        }
        
        if (currentCourseId !== originalCourseId) {
            document.getElementById('previewCourseTitle').classList.add('changed');
        }
        
        if (currentApproved !== originalApproved) {
            document.getElementById('previewStatusBadge').classList.add('changed');
        }
        
        if (currentComment !== originalComment) {
            document.getElementById('previewComment').classList.add('changed');
        }
    }

    // Form validation
    function validateForm() {
        let isValid = true;
        
        // Check required fields
        const userId = document.getElementById('user_id').value;
        const courseId = document.getElementById('course_id').value;
        const rating = document.querySelector('input[name="rating"]:checked');
        
        if (!userId) {
            markInvalid('user_id', 'Please select a user');
            isValid = false;
        } else {
            markValid('user_id');
        }
        
        if (!courseId) {
            markInvalid('course_id', 'Please select a course');
            isValid = false;
        } else {
            markValid('course_id');
        }
        
        if (!rating) {
            showToast('Please select a rating', 'error');
            isValid = false;
        }
        
        // Check comment length
        const comment = document.getElementById('comment').value;
        if (comment.length > 1000) {
            markInvalid('comment', 'Comment must be less than 1000 characters');
            isValid = false;
        } else {
            markValid('comment');
        }
        
        return isValid;
    }

    function markInvalid(fieldId, message) {
        const field = document.getElementById(fieldId);
        const feedback = field.nextElementSibling?.classList?.contains('invalid-feedback') 
            ? field.nextElementSibling 
            : null;
        
        field.classList.add('is-invalid');
        if (feedback) {
            feedback.textContent = message;
        }
    }

    function markValid(fieldId) {
        const field = document.getElementById(fieldId);
        field.classList.remove('is-invalid');
        field.classList.add('is-valid');
    }

    // Form reset
    function resetForm() {
        if (confirm('Are you sure you want to reset the form? All changes will be lost.')) {
            document.getElementById('editReviewForm').reset();
            currentRating = {{ $review->rating }};
            updateRatingDisplay();
            updatePreview();
            updateCharCount();
            showToast('Form reset to original values', 'info');
        }
    }

    // Preview changes modal
    function previewChanges() {
        if (!validateForm()) {
            showToast('Please fix the errors before previewing changes', 'error');
            return;
        }
        
        // Populate new values preview
        const newValuesPreview = document.getElementById('newValuesPreview');
        newValuesPreview.innerHTML = `
            <div class="value-item mb-3">
                <small class="text-muted d-block">Rating:</small>
                <div class="d-flex align-items-center">
                    ${getStarsHTML(currentRating)}
                    <span class="ms-2">${currentRating}/5</span>
                </div>
            </div>
            <div class="value-item mb-3">
                <small class="text-muted d-block">User:</small>
                <strong>${document.getElementById('previewUserName').textContent}</strong>
            </div>
            <div class="value-item mb-3">
                <small class="text-muted d-block">Course:</small>
                <strong>${document.getElementById('previewCourseTitle').textContent}</strong>
            </div>
            <div class="value-item mb-3">
                <small class="text-muted d-block">Status:</small>
                <span class="badge bg-${document.getElementById('approved').checked ? 'success' : 'warning'}">
                    ${document.getElementById('approved').checked ? 'Approved' : 'Pending'}
                </span>
            </div>
        `;
        
        document.getElementById('newCommentPreview').textContent = document.getElementById('comment').value || 'No comment';
        
        const modal = new bootstrap.Modal(document.getElementById('previewModal'));
        modal.show();
    }

    function getStarsHTML(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            stars += `<i class="fas fa-star${i <= rating ? ' text-warning' : ' text-muted'}"></i>`;
        }
        return stars;
    }

    function saveChanges() {
        document.getElementById('editReviewForm').submit();
    }

    // Initialize form
    function initializeForm() {
        updateRatingDisplay();
        updatePreview();
    }

    // Toast notification
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
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            document.getElementById('editReviewForm').submit();
        }
        
        // Ctrl/Cmd + P to preview
        if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
            e.preventDefault();
            previewChanges();
        }
        
        // Escape to go back
        if (e.key === 'Escape') {
            window.history.back();
        }
    });
</script>
@endsection