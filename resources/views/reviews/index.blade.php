@extends('layout')
@section('content')

<!-- Main Card with Updated Styling -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-star"></i> Reviews Management</h2>
            <div class="d-flex">
                <!-- Rating Filter -->
                <div class="me-3">
                    <select id="ratingFilter" class="form-select form-select-sm" onchange="filterByRating(this.value)">
                        <option value="">All Ratings</option>
                        <option value="5" {{ request('rating') == '5' ? 'selected' : '' }}>★★★★★ (5)</option>
                        <option value="4" {{ request('rating') == '4' ? 'selected' : '' }}>★★★★☆ (4)</option>
                        <option value="3" {{ request('rating') == '3' ? 'selected' : '' }}>★★★☆☆ (3)</option>
                        <option value="2" {{ request('rating') == '2' ? 'selected' : '' }}>★★☆☆☆ (2)</option>
                        <option value="1" {{ request('rating') == '1' ? 'selected' : '' }}>★☆☆☆☆ (1)</option>
                    </select>
                </div>
                
                <!-- User Filter -->
                @if(isset($users) && $users->isNotEmpty())
                <div class="me-3">
                    <select id="userFilter" class="form-select form-select-sm" onchange="filterByUser(this.value)">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <!-- Course Filter -->
                @if(isset($courses) && $courses->isNotEmpty())
                <div class="me-3">
                    <select id="courseFilter" class="form-select form-select-sm" onchange="filterByCourse(this.value)">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                            {{ Str::limit($course->title, 25) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <!-- Status Filter -->
                <div class="me-3">
                    <select id="statusFilter" class="form-select form-select-sm" onchange="filterByStatus(this.value)">
                        <option value="">All Status</option>
                        <option value="1" {{ request('approved') == '1' ? 'selected' : '' }}>Approved</option>
                        <option value="0" {{ request('approved') == '0' ? 'selected' : '' }}>Pending</option>
                    </select>
                </div>
                
                <!-- Sort Options -->
                <div class="me-3">
                    <select id="sortFilter" class="form-select form-select-sm" onchange="sortReviews(this.value)">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        <option value="highest" {{ request('sort') == 'highest' ? 'selected' : '' }}>Highest Rating</option>
                        <option value="lowest" {{ request('sort') == 'lowest' ? 'selected' : '' }}>Lowest Rating</option>
                    </select>
                </div>
                
                <!-- Search Form -->
                <form action="{{ url('/reviews') }}" method="GET" class="d-inline">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search reviews..." 
                               value="{{ request('search') }}" style="max-width: 200px;">
                        <input type="hidden" name="rating" id="searchRating" value="{{ request('rating') }}">
                        <input type="hidden" name="user_id" id="searchUserId" value="{{ request('user_id') }}">
                        <input type="hidden" name="course_id" id="searchCourseId" value="{{ request('course_id') }}">
                        <input type="hidden" name="approved" id="searchStatus" value="{{ request('approved') }}">
                        <input type="hidden" name="sort" id="searchSort" value="{{ request('sort', 'newest') }}">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Button to Add New Review -->
        <a href="{{ url('/reviews/create') }}" class="btn btn-add-review mb-4" title="Add New Review">
            <i class="fas fa-plus-circle" aria-hidden="true" style="margin-right: 8px;"></i> Add New Review
        </a>
        
        <!-- Reviews Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white p-3 rounded">
                    <h6 class="mb-1">Total Reviews</h6>
                    <h3 class="mb-0">{{ $reviewsCount }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h6 class="mb-1">Average Rating</h6>
                    <h3 class="mb-0">
                        @if($averageRating > 0)
                            {{ number_format($averageRating, 1) }} ★
                        @else
                            0.0 ★
                        @endif
                    </h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h6 class="mb-1">Approved Reviews</h6>
                    <h3 class="mb-0">{{ $approvedReviews }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h6 class="mb-1">Pending Reviews</h6>
                    <h3 class="mb-0">{{ $pendingReviews }}</h3>
                </div>
            </div>
        </div>
        
        <!-- Rating Distribution Chart -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Rating Distribution</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="rating-bars">
                            @for($i = 5; $i >= 1; $i--)
                                @php
                                    $ratingCount = $ratingDistribution[$i] ?? 0;
                                    $percentage = $reviewsCount > 0 ? ($ratingCount / $reviewsCount) * 100 : 0;
                                @endphp
                                <div class="rating-bar-item mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="rating-stars">
                                            @for($j = 1; $j <= 5; $j++)
                                                <i class="fas fa-star{{ $j <= $i ? ' text-warning' : ' text-muted' }}"></i>
                                            @endfor
                                            <span class="ms-2">({{ $i }})</span>
                                        </span>
                                        <span class="rating-count">{{ $ratingCount }} reviews</span>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: {{ $percentage }}%;" 
                                             aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                            {{ number_format($percentage, 1) }}%
                                        </div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="rating-summary text-center">
                            <div class="average-rating-display mb-4">
                                <h1 class="display-4 text-warning mb-0">
                                    @if($averageRating > 0)
                                        {{ number_format($averageRating, 1) }}
                                    @else
                                        0.0
                                    @endif
                                </h1>
                                <div class="rating-stars-large mb-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= floor($averageRating))
                                            <i class="fas fa-star fa-2x text-warning"></i>
                                        @elseif($i - 0.5 <= $averageRating)
                                            <i class="fas fa-star-half-alt fa-2x text-warning"></i>
                                        @else
                                            <i class="far fa-star fa-2x text-warning"></i>
                                        @endif
                                    @endfor
                                </div>
                                <p class="text-muted mb-0">Based on {{ $reviewsCount }} reviews</p>
                            </div>
                            <div class="reviewers-count">
                                <h5>{{ $uniqueReviewers ?? 0 }}</h5>
                                <p class="text-muted mb-0">Unique Reviewers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Action Buttons -->
        <div class="mb-4">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="selectAllReviews()">
                    <i class="fas fa-check-square"></i> Select All
                </button>
                <button type="button" class="btn btn-outline-success btn-sm" onclick="bulkApprove()">
                    <i class="fas fa-check-circle"></i> Approve Selected
                </button>
                <button type="button" class="btn btn-outline-danger btn-sm" onclick="bulkDelete()">
                    <i class="fas fa-trash"></i> Delete Selected
                </button>
            </div>
        </div>
        
        <!-- Reviews Table -->
        <div class="table-responsive">
            <form id="bulkActionForm" action="{{ route('reviews.bulk-approve') }}" method="POST">
                @csrf
                <table class="table table-striped table-hover">
                    <thead>
                        <tr>
                            <th width="50">
                                <input type="checkbox" id="selectAll" onclick="toggleSelectAll(this)">
                            </th>
                            <th>#</th>
                            <th>Rating</th>
                            <th>User</th>
                            <th>Course</th>
                            <th>Review</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reviews as $item)
                            <tr>
                                <td>
                                    <input type="checkbox" name="review_ids[]" value="{{ $item->id }}" class="review-checkbox">
                                </td>
                                <td>
                                    @if(method_exists($reviews, 'currentPage'))
                                        {{ ($reviews->currentPage() - 1) * $reviews->perPage() + $loop->iteration }}
                                    @else
                                        {{ $loop->iteration }}
                                    @endif
                                </td>
                                <td>
                                    <div class="rating-display">
                                        <div class="stars">
                                            @for($i = 1; $i <= 5; $i++)
                                                <i class="fas fa-star{{ $i <= $item->rating ? ' text-warning' : ' text-muted' }}"></i>
                                            @endfor
                                        </div>
                                        <div class="rating-text">
                                            <span class="badge bg-{{ $item->rating >= 4 ? 'success' : ($item->rating >= 3 ? 'warning' : 'danger') }}">
                                                {{ $item->rating }}/5
                                            </span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-2">
                                            <i class="fas fa-user-circle" style="color: #3498db; font-size: 24px;"></i>
                                        </div>
                                        <div>
                                            <strong>{{ $item->user->name ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $item->user->email ?? '' }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($item->course)
                                    <div class="course-info">
                                        <a href="{{ url('/courses/' . $item->course_id) }}" class="text-decoration-none">
                                            <div class="d-flex align-items-center">
                                                <div class="course-icon me-2">
                                                    <i class="fas fa-book" style="color: #2ecc71;"></i>
                                                </div>
                                                <div>
                                                    <strong class="text-primary">{{ Str::limit($item->course->title, 30) }}</strong>
                                                    <br>
                                                    <small class="text-muted">{{ $item->course->code ?? 'N/A' }}</small>
                                                </div>
                                            </div>
                                        </a>
                                    </div>
                                    @else
                                    <span class="text-danger">No Course</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="review-comment">
                                        @if($item->comment)
                                            <div class="comment-preview" data-bs-toggle="tooltip" title="{{ $item->comment }}">
                                                {{ Str::limit($item->comment, 60) }}
                                            </div>
                                        @else
                                            <span class="text-muted">No comment</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    @if($item->approved)
                                        <span class="badge bg-success">Approved</span>
                                    @else
                                        <span class="badge bg-warning">Pending</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="date-display">
                                        <small class="text-muted">{{ $item->created_at->format('M d, Y') }}</small>
                                        <br>
                                        <small class="text-muted">{{ $item->created_at->format('h:i A') }}</small>
                                    </div>
                                </td>
                                <td>
                                    <div class="btn-group" role="group">
                                        <!-- View Button -->
                                        <a href="{{ url('/reviews/' . $item->id) }}" title="View Review" class="btn btn-view btn-sm">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>

                                        <!-- Edit Button -->
                                        <a href="{{ url('/reviews/' . $item->id . '/edit') }}" title="Edit Review" class="btn btn-edit btn-sm">
                                            <i class="fas fa-edit" aria-hidden="true"></i>
                                        </a>

                                        <!-- Quick Rating Update -->
                                        <div class="dropdown d-inline">
                                            <button class="btn btn-rating btn-sm dropdown-toggle" type="button" 
                                                    data-bs-toggle="dropdown" title="Change Rating">
                                                <i class="fas fa-star"></i>
                                            </button>
                                            <ul class="dropdown-menu dropdown-menu-end">
                                                <li><h6 class="dropdown-header">Update Rating</h6></li>
                                                @for($i = 5; $i >= 1; $i--)
                                                <li>
                                                    <a class="dropdown-item" href="#" onclick="updateRating({{ $item->id }}, {{ $i }})">
                                                        @for($j = 1; $j <= 5; $j++)
                                                            <i class="fas fa-star{{ $j <= $i ? ' text-warning' : ' text-muted' }}"></i>
                                                        @endfor
                                                        <span class="ms-2">({{ $i }} stars)</span>
                                                    </a>
                                                </li>
                                                @endfor
                                            </ul>
                                        </div>

                                        <!-- Status Toggle -->
                                        @if($item->approved)
                                            <form method="POST" action="{{ route('reviews.approve', $item->id) }}" class="d-inline approve-form">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="0">
                                                <button type="button" class="btn btn-warning btn-sm disapprove-btn" 
                                                        title="Disapprove" 
                                                        data-review-id="{{ $item->id }}"
                                                        data-action="disapprove">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('reviews.approve', $item->id) }}" class="d-inline approve-form">
                                                @csrf
                                                @method('PUT')
                                                <input type="hidden" name="status" value="1">
                                                <button type="button" class="btn btn-success btn-sm approve-btn" 
                                                        title="Approve" 
                                                        data-review-id="{{ $item->id }}"
                                                        data-action="approve">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @endif

                                        <!-- Delete Button -->
                                        <form method="POST" action="{{ url('/reviews/' . $item->id) }}" accept-charset="UTF-8" style="display:inline">
                                            @method('DELETE')
                                            @csrf
                                            <button type="submit" class="btn btn-delete btn-sm" title="Delete Review" 
                                                    onclick="return confirm('Are you sure you want to delete this review?')">
                                                <i class="fas fa-trash" aria-hidden="true"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center py-5">
                                    <i class="fas fa-star fa-3x text-muted mb-3"></i>
                                    <h4 class="text-muted">No reviews found</h4>
                                    <p class="text-muted">Reviews provide valuable feedback for courses</p>
                                    <a href="{{ url('/reviews/create') }}" class="btn btn-add-review">
                                        <i class="fas fa-plus-circle" aria-hidden="true"></i> Create First Review
                                    </a>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </form>
        </div>
        
        <!-- Pagination - Only show if pagination is available -->
        @if(method_exists($reviews, 'hasPages') && $reviews->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $reviews->withQueryString()->links() }}
            </div>
        @endif
        
        <!-- Reviews Summary -->
        @if($reviews->isNotEmpty())
        <div class="row mt-4">
            <!-- Top Reviewers -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-trophy"></i> Top Reviewers</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @foreach($topReviewers as $index => $reviewer)
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-primary me-2">{{ $index + 1 }}</span>
                                            <div>
                                                <h6 class="mb-0">{{ $reviewer->name }}</h6>
                                                <small class="text-muted">{{ $reviewer->email }}</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-info">{{ $reviewer->review_count }} reviews</span>
                                            <div class="small text-muted">
                                                Avg: {{ number_format($reviewer->average_rating ?? 0, 1) }} ★
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Most Reviewed Courses -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Most Reviewed Courses</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            @foreach($mostReviewedCourses as $index => $course)
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div class="d-flex align-items-center">
                                            <span class="badge bg-success me-2">{{ $index + 1 }}</span>
                                            <div>
                                                <h6 class="mb-0">{{ Str::limit($course->title, 40) }}</h6>
                                                <small class="text-muted">{{ $course->code ?? 'No Code' }}</small>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <span class="badge bg-warning">{{ $course->review_count }} reviews</span>
                                            <div class="small text-muted">
                                                Avg: {{ number_format($course->average_rating ?? 0, 1) }} ★
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Export & Summary -->
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Reviews Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="summary-item text-center">
                            <h6 class="text-muted">Most Reviewed Course</h6>
                            <h4 class="text-primary">
                                @if($mostReviewedCourse)
                                    {{ Str::limit($mostReviewedCourse->title, 25) }}
                                @else
                                    N/A
                                @endif
                            </h4>
                            <p class="text-muted mb-0">
                                {{ $mostReviewedCourseCount ?? 0 }} reviews
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-item text-center">
                            <h6 class="text-muted">Most Active Reviewer</h6>
                            <h4 class="text-success">
                                @if($mostActiveReviewer)
                                    {{ $mostActiveReviewer->name }}
                                @else
                                    N/A
                                @endif
                            </h4>
                            <p class="text-muted mb-0">
                                {{ $mostActiveReviewerCount ?? 0 }} reviews
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-item text-center">
                            <h6 class="text-muted">Export Reviews</h6>
                            <div class="mt-2">
                                <a href="{{ url('/reviews/export/csv') . '?' . http_build_query(request()->query()) }}" 
                                   class="btn btn-outline-primary me-2">
                                    <i class="fas fa-file-csv"></i> CSV
                                </a>
                                <a href="{{ url('/reviews/export/pdf') . '?' . http_build_query(request()->query()) }}" 
                                   class="btn btn-outline-danger">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Comment Preview Modal -->
<div class="modal fade" id="commentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Review Comment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="review-details mb-3">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>User:</strong> <span id="modalUserName"></span></p>
                            <p class="mb-1"><strong>Course:</strong> <span id="modalCourseName"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Rating:</strong> <span id="modalRating"></span></p>
                            <p class="mb-1"><strong>Date:</strong> <span id="modalDate"></span></p>
                        </div>
                    </div>
                </div>
                <div class="comment-content p-3 bg-light rounded">
                    <p id="modalComment"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* General Styles */
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
    
    .card-body {
        padding: 30px;
    }

    /* Button Styles */
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

    .btn-sm {
        padding: 6px 10px;
        font-size: 12px;
        min-width: 36px;
    }

    .btn-group .btn {
        margin: 0 2px;
        border-radius: 6px;
    }

    /* Add New Review Button */
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

    /* Action Buttons */
    .btn-view {
        background-color: #3498db;
        color: white;
    }

    .btn-view:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
    }

    .btn-edit {
        background-color: #2ecc71;
        color: white;
    }

    .btn-edit:hover {
        background-color: #27ae60;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
    }

    .btn-rating {
        background-color: #9b59b6;
        color: white;
    }

    .btn-rating:hover {
        background-color: #8e44ad;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(155, 89, 182, 0.3);
    }

    .btn-delete {
        background-color: #dc3545;
        color: white;
    }

    .btn-delete:hover {
        background-color: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }

    /* Table Styling */
    .table {
        width: 100%;
        margin-bottom: 1rem;
        background-color: white;
        border-radius: 10px;
        overflow: hidden;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-striped tbody tr:nth-child(odd) {
        background-color: #f8f9fa;
    }

    .table-hover tbody tr:hover {
        background-color: #fff8e1;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    .table th {
        background: linear-gradient(145deg, #f39c12, #e67e22);
        color: white;
        padding: 15px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
        border: none;
    }

    .table td {
        padding: 15px;
        text-align: left;
        font-size: 14px;
        color: #444;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }

    .table-responsive {
        overflow-x: auto;
        border-radius: 10px;
    }

    /* Statistics Cards */
    .stat-card {
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    /* Rating Display */
    .rating-display {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    .stars {
        font-size: 16px;
        margin-bottom: 5px;
    }

    .rating-text .badge {
        font-size: 11px;
        padding: 4px 8px;
    }

    /* Rating Bars */
    .rating-bars {
        padding: 10px 0;
    }

    .rating-bar-item .progress {
        border-radius: 10px;
        overflow: hidden;
    }

    .rating-bar-item .progress-bar {
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        padding: 0 10px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
    }

    .rating-stars {
        display: flex;
        align-items: center;
    }

    .rating-stars-large {
        margin: 10px 0;
    }

    .average-rating-display {
        padding: 20px;
        background-color: #f8f9fa;
        border-radius: 10px;
        border: 1px solid #e9ecef;
    }

    /* Review Comment */
    .review-comment {
        max-width: 200px;
    }

    .comment-preview {
        cursor: pointer;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        padding: 5px;
        border-radius: 4px;
        transition: background-color 0.2s;
    }

    .comment-preview:hover {
        background-color: #f8f9fa;
    }

    /* User, Course Icons */
    .user-avatar, .course-icon {
        width: 30px;
        text-align: center;
    }

    /* Date Display */
    .date-display {
        min-width: 100px;
    }

    /* Filters */
    .form-select-sm {
        width: 150px;
        border-radius: 6px;
        border: 1px solid #ced4da;
    }

    /* Summary Items */
    .summary-item {
        padding: 15px;
        border-radius: 8px;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        height: 100%;
    }

    .summary-item h4 {
        margin: 10px 0;
        font-weight: 600;
    }

    /* List Group Items */
    .list-group-item {
        border: none;
        border-bottom: 1px solid #e9ecef;
        padding: 15px;
    }

    .list-group-item:last-child {
        border-bottom: none;
    }

    /* Dropdown Menu */
    .dropdown-menu {
        border: none;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        padding: 8px 0;
    }

    .dropdown-item {
        padding: 8px 16px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    /* Pagination */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        color: #f39c12;
        border: 1px solid #dee2e6;
        margin: 0 3px;
        border-radius: 6px;
    }

    .page-item.active .page-link {
        background: linear-gradient(145deg, #f39c12, #e67e22);
        border-color: #f39c12;
        color: white;
    }

    .page-link:hover {
        color: #e67e22;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    /* Empty State */
    .fa-3x {
        font-size: 4rem;
    }

    /* Modal */
    .modal-content {
        border-radius: 12px;
        border: none;
    }

    .modal-header {
        background: linear-gradient(145deg, #f39c12, #e67e22);
        color: white;
        border-bottom: none;
    }

    /* Star Animation */
    @keyframes starPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }

    .star-pulse {
        animation: starPulse 0.5s ease;
    }

    /* Checkbox styling */
    .review-checkbox {
        cursor: pointer;
    }

    #selectAll {
        cursor: pointer;
    }
</style>

<!-- JavaScript for Enhanced Functionality -->
<!-- Complete JavaScript Code -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Handle single review delete with confirmation
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const form = this.closest('form');
                const reviewId = form.action.split('/').pop();
                showDeleteConfirmation(reviewId, form);
            });
        });
        
        // Handle approve/disapprove buttons
        document.querySelectorAll('.approve-btn, .disapprove-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const reviewId = this.getAttribute('data-review-id');
                const action = this.getAttribute('data-action');
                const form = this.closest('.approve-form');
                
                showApprovalConfirmation(reviewId, action, form);
            });
        });
        
        // Add hover effect to table rows
        const tableRows = document.querySelectorAll('.table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.2s ease';
            });
        });
        
        // Star hover animation
        const stars = document.querySelectorAll('.stars i');
        stars.forEach(star => {
            star.addEventListener('mouseenter', function() {
                this.classList.add('star-pulse');
            });
            
            star.addEventListener('mouseleave', function() {
                this.classList.remove('star-pulse');
            });
        });
        
        // Comment preview click
        const commentPreviews = document.querySelectorAll('.comment-preview');
        commentPreviews.forEach(preview => {
            preview.addEventListener('click', function() {
                const reviewRow = this.closest('tr');
                const userName = reviewRow.querySelector('strong').textContent;
                const courseName = reviewRow.querySelector('.course-info strong')?.textContent || 'N/A';
                const rating = reviewRow.querySelector('.rating-text span')?.textContent || 'N/A';
                const date = reviewRow.querySelector('.date-display')?.textContent.trim() || 'N/A';
                const comment = this.getAttribute('data-bs-title') || this.textContent;
                
                document.getElementById('modalUserName').textContent = userName;
                document.getElementById('modalCourseName').textContent = courseName;
                document.getElementById('modalRating').textContent = rating;
                document.getElementById('modalDate').textContent = date;
                document.getElementById('modalComment').textContent = comment;
                
                const modal = new bootstrap.Modal(document.getElementById('commentModal'));
                modal.show();
            });
        });
        
        // Rating bar hover effect
        const ratingBars = document.querySelectorAll('.rating-bar-item');
        ratingBars.forEach(bar => {
            bar.addEventListener('mouseenter', function() {
                const progressBar = this.querySelector('.progress-bar');
                progressBar.style.transform = 'scaleX(1.02)';
                progressBar.style.transition = 'transform 0.2s ease';
            });
            
            bar.addEventListener('mouseleave', function() {
                const progressBar = this.querySelector('.progress-bar');
                progressBar.style.transform = 'scaleX(1)';
            });
        });
    });
    
    // Filter functions
    function filterByRating(rating) {
        document.getElementById('searchRating').value = rating;
        const form = document.getElementById('searchRating').closest('form');
        form.submit();
    }
    
    function filterByUser(userId) {
        document.getElementById('searchUserId').value = userId;
        const form = document.getElementById('searchUserId').closest('form');
        form.submit();
    }
    
    function filterByCourse(courseId) {
        document.getElementById('searchCourseId').value = courseId;
        const form = document.getElementById('searchCourseId').closest('form');
        form.submit();
    }
    
    function filterByStatus(status) {
        document.getElementById('searchStatus').value = status;
        const form = document.getElementById('searchStatus').closest('form');
        form.submit();
    }
    
    function sortReviews(sortValue) {
        document.getElementById('searchSort').value = sortValue;
        const form = document.getElementById('searchSort').closest('form');
        form.submit();
    }
    
    // Bulk actions
    function toggleSelectAll(source) {
        const checkboxes = document.querySelectorAll('.review-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = source.checked;
        });
    }
    
    function selectAllReviews() {
        const checkboxes = document.querySelectorAll('.review-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        document.getElementById('selectAll').checked = true;
    }
    
    function bulkApprove() {
        const selectedReviews = getSelectedReviewIds();
        if (selectedReviews.length === 0) {
            showToast('Please select at least one review', 'warning');
            return;
        }
        
        if (confirm(`Are you sure you want to approve ${selectedReviews.length} selected review(s)? Approved reviews will be visible to all users.`)) {
            // Submit bulk approval
            const form = document.getElementById('bulkActionForm');
            form.action = "{{ route('reviews.bulk-approve') }}";
            
            // Add status field if not exists
            if (!document.querySelector('#bulkActionForm input[name="status"]')) {
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = '1';
                form.appendChild(statusInput);
            }
            
            // Show loading state
            const button = event.target;
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
            button.disabled = true;
            
            // Submit the form
            form.submit();
        }
    }
    
    function bulkDelete() {
        const selectedReviews = getSelectedReviewIds();
        if (selectedReviews.length === 0) {
            showToast('Please select at least one review', 'warning');
            return;
        }
        
        if (confirm(`Are you sure you want to delete ${selectedReviews.length} selected review(s)? This action cannot be undone.`)) {
            // Submit bulk delete
            const form = document.getElementById('bulkActionForm');
            form.action = "{{ route('reviews.bulk-delete') }}";
            
            // Show loading state
            const button = event.target;
            const originalHtml = button.innerHTML;
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
            button.disabled = true;
            
            // Submit the form
            form.submit();
        }
    }
    
    function getSelectedReviewIds() {
        const checkboxes = document.querySelectorAll('.review-checkbox:checked');
        const selectedIds = [];
        checkboxes.forEach(checkbox => {
            selectedIds.push(checkbox.value);
        });
        return selectedIds;
    }
    
    // Show approval confirmation dialog
    function showApprovalConfirmation(reviewId, action, form) {
        const actionText = action === 'approve' ? 'approve' : 'disapprove';
        const title = action === 'approve' ? 'Approve Review' : 'Disapprove Review';
        const message = action === 'approve' 
            ? 'Are you sure you want to approve this review? Approved reviews will be visible to all users.'
            : 'Are you sure you want to disapprove this review? Disapproved reviews will not be visible to users.';
        
        // Use browser confirmation dialog
        if (confirm(message)) {
            // Disable button to prevent double submission
            const button = form.querySelector('button');
            const originalHtml = button.innerHTML;
            const originalText = button.title;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            button.title = 'Processing...';
            
            // Submit the form
            form.submit();
        }
    }
    
    // Show delete confirmation dialog
    function showDeleteConfirmation(reviewId, form) {
        if (confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
            // Disable button to prevent double submission
            const button = form.querySelector('.btn-delete');
            const originalHtml = button.innerHTML;
            const originalText = button.title;
            
            button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            button.disabled = true;
            button.title = 'Deleting...';
            
            // Submit the form
            form.submit();
        }
    }
    
    // Update rating
    function updateRating(reviewId, newRating) {
        if (!reviewId || !newRating) return;
        
        if (!confirm(`Change rating to ${newRating} stars?`)) {
            return;
        }
        
        const button = event.target.closest('a');
        const originalHtml = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        
        fetch(`/reviews/${reviewId}/rating`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                rating: newRating,
                _method: 'PUT'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(`Rating updated to ${newRating} stars`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to update rating', 'error');
                button.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred while updating rating', 'error');
            button.innerHTML = originalHtml;
        });
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        // Remove existing toasts
        const existingToasts = document.querySelectorAll('.custom-toast');
        existingToasts.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `custom-toast alert alert-${type} position-fixed top-0 end-0 m-3`;
        toast.style.zIndex = '9999';
        toast.style.minWidth = '300px';
        toast.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : type === 'warning' ? 'fa-exclamation-triangle' : 'fa-info-circle'} me-2"></i>
                <span>${message}</span>
            </div>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => toast.remove(), 3000);
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            document.querySelector('input[name="search"]').focus();
        }
        
        // Ctrl/Cmd + N to add new review
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = '/reviews/create';
        }
        
        // Ctrl/Cmd + E to export CSV
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            e.preventDefault();
            exportReviews('csv');
        }
    });
    
    function exportReviews(format) {
        const queryParams = new URLSearchParams(window.location.search);
        window.location.href = `/reviews/export/${format}?${queryParams.toString()}`;
    }
    
    // Auto-refresh stats (every 60 seconds if on page)
    setInterval(() => {
        // Optional: Make AJAX call to update stats without refreshing page
        // fetch('/reviews/stats')
        //     .then(response => response.json())
        //     .then(data => {
        //         // Update stats dynamically
        //     });
    }, 60000);
</script>

@endsection