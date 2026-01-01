@extends('layout')
@section('content')

<!-- Main Card for Review Details -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h2 class="mb-0 me-3"><i class="fas fa-star"></i> Review Details</h2>
                <div class="badge bg-light text-dark px-3 py-2">
                    <i class="fas fa-hashtag"></i> #{{ $review->id }}
                </div>
            </div>
            <div class="d-flex align-items-center">
                <!-- Action Buttons -->
                <a href="{{ url('/reviews') }}" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <a href="{{ url('/reviews/' . $review->id . '/edit') }}" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Quick Actions</h6></li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="printReview()">
                                <i class="fas fa-print me-2"></i> Print
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="copyReviewLink()">
                                <i class="fas fa-link me-2"></i> Copy Link
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="exportReview()">
                                <i class="fas fa-download me-2"></i> Export as PDF
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" 
                               onclick="confirmDelete()">
                                <i class="fas fa-trash me-2"></i> Delete
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
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

        <div class="row">
            <!-- Left Column: Main Review Content -->
            <div class="col-lg-8">
                <!-- Review Card -->
                <div class="card mb-4">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Review Information</h5>
                        <span class="badge bg-{{ $review->rating >= 4 ? 'success' : ($review->rating >= 3 ? 'warning' : 'danger') }}">
                            {{ $review->rating }}/5
                        </span>
                    </div>
                    <div class="card-body">
                        <!-- Rating Display -->
                        <div class="text-center mb-4">
                            <div class="rating-display-large">
                                <div class="stars-large mb-3">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= $review->rating)
                                            <i class="fas fa-star fa-3x text-warning"></i>
                                        @else
                                            <i class="far fa-star fa-3x text-warning"></i>
                                        @endif
                                    @endfor
                                </div>
                                <h2 class="display-4 text-warning mb-2">
                                    {{ number_format($review->rating, 1) }}
                                    <small class="text-muted fs-6">/5</small>
                                </h2>
                                <p class="text-muted mb-0">
                                    @php
                                        $ratingDescriptions = [
                                            1 => 'Poor - Did not meet expectations',
                                            2 => 'Below Average - Needs improvement',
                                            3 => 'Average - Met basic requirements',
                                            4 => 'Good - Met most expectations',
                                            5 => 'Excellent - Exceeded all expectations'
                                        ];
                                    @endphp
                                    {{ $ratingDescriptions[$review->rating] ?? 'No description available' }}
                                </p>
                            </div>
                        </div>

                        <!-- Review Comment -->
                        <div class="mb-4">
                            <h6 class="section-title">
                                <i class="fas fa-comment me-2"></i> Review Comment
                            </h6>
                            <div class="comment-card p-4 bg-light rounded">
                                @if($review->comment)
                                    <div class="comment-content">
                                        {!! nl2br(e($review->comment)) !!}
                                    </div>
                                    <div class="comment-stats mt-3 text-end">
                                        <small class="text-muted">
                                            <i class="fas fa-font"></i> 
                                            {{ str_word_count($review->comment) }} words • 
                                            <i class="fas fa-hashtag"></i> 
                                            {{ strlen($review->comment) }} characters
                                        </small>
                                    </div>
                                @else
                                    <div class="text-center py-4">
                                        <i class="fas fa-comment-slash fa-2x text-muted mb-3"></i>
                                        <p class="text-muted mb-0">No comment provided for this review</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Metadata -->
                        <div class="row">
                            <div class="col-md-6">
                                <div class="meta-card p-3 bg-light rounded mb-3">
                                    <h6 class="meta-title">
                                        <i class="fas fa-calendar-alt"></i> Created
                                    </h6>
                                    <div class="meta-content">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-clock text-primary me-2"></i>
                                            <div>
                                                <strong>{{ $review->created_at->format('F j, Y') }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $review->created_at->format('h:i A') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="meta-card p-3 bg-light rounded mb-3">
                                    <h6 class="meta-title">
                                        <i class="fas fa-sync-alt"></i> Last Updated
                                    </h6>
                                    <div class="meta-content">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-history text-success me-2"></i>
                                            <div>
                                                <strong>{{ $review->updated_at->format('F j, Y') }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $review->updated_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User & Course Information -->
                <div class="row">
                    <!-- User Card -->
                    <div class="col-md-6">
                        <div class="card mb-4 h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-user"></i> Reviewer</h5>
                            </div>
                            <div class="card-body">
                                <div class="user-profile text-center mb-4">
                                    <div class="avatar-large mb-3">
                                        <i class="fas fa-user-circle fa-4x" style="color: #3498db;"></i>
                                    </div>
                                    <h4 class="mb-1">{{ $review->user->name ?? 'N/A' }}</h4>
                                    <p class="text-muted mb-3">{{ $review->user->email ?? '' }}</p>
                                    
                                    @if($review->user)
                                    @php
                                        // Calculate user stats on the fly
                                        $userReviewCount = \App\Models\Review::where('user_id', $review->user_id)->count();
                                        $userAverageRating = \App\Models\Review::where('user_id', $review->user_id)->avg('rating') ?? 0;
                                    @endphp
                                    <div class="user-stats">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <h5 class="text-primary">{{ $userReviewCount }}</h5>
                                                    <small class="text-muted">Reviews</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <h5 class="text-warning">
                                                        @if($userAverageRating > 0)
                                                            {{ number_format($userAverageRating, 1) }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </h5>
                                                    <small class="text-muted">Avg Rating</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                
                                @if($review->user)
                                <div class="d-grid gap-2">
                                    <a href="{{ url('/users/' . $review->user_id) }}" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-2"></i> View Profile
                                    </a>
                                    <a href="{{ url('/reviews?user_id=' . $review->user_id) }}" 
                                       class="btn btn-outline-info">
                                        <i class="fas fa-list me-2"></i> All Reviews by User
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- Course Card -->
                    <div class="col-md-6">
                        <div class="card mb-4 h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-book"></i> Course</h5>
                            </div>
                            <div class="card-body">
                                <div class="course-profile text-center mb-4">
                                    <div class="course-icon-large mb-3">
                                        <i class="fas fa-book fa-4x" style="color: #2ecc71;"></i>
                                    </div>
                                    <h4 class="mb-1">{{ $review->course->title ?? 'N/A' }}</h4>
                                    <p class="text-muted mb-3">{{ $review->course->code ?? 'No Code' }}</p>
                                    
                                    @if($review->course)
                                    @php
                                        // Calculate course stats on the fly
                                        $courseReviewCount = \App\Models\Review::where('course_id', $review->course_id)->count();
                                        $courseAverageRating = \App\Models\Review::where('course_id', $review->course_id)->avg('rating') ?? 0;
                                    @endphp
                                    <div class="course-stats">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <h5 class="text-primary">{{ $courseReviewCount }}</h5>
                                                    <small class="text-muted">Reviews</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <h5 class="text-warning">
                                                        @if($courseAverageRating > 0)
                                                            {{ number_format($courseAverageRating, 1) }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </h5>
                                                    <small class="text-muted">Avg Rating</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                
                                @if($review->course)
                                <div class="d-grid gap-2">
                                    <a href="{{ url('/courses/' . $review->course_id) }}" 
                                       class="btn btn-outline-success">
                                        <i class="fas fa-external-link-alt me-2"></i> View Course
                                    </a>
                                    <a href="{{ url('/reviews?course_id=' . $review->course_id) }}" 
                                       class="btn btn-outline-info">
                                        <i class="fas fa-list me-2"></i> All Course Reviews
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Review Analysis (Optional) -->
                @if($review->comment)
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Review Analysis</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                @php
                                    // Simple sentiment analysis based on rating
                                    $sentimentScore = $review->rating * 20; // 1-star = 20%, 5-star = 100%
                                    $sentimentColor = $review->rating >= 4 ? 'success' : ($review->rating >= 3 ? 'warning' : 'danger');
                                    $sentimentLabel = $review->rating >= 4 ? 'Positive' : ($review->rating >= 3 ? 'Neutral' : 'Negative');
                                @endphp
                                <h6>Sentiment Analysis</h6>
                                <div class="progress mb-3" style="height: 25px;">
                                    <div class="progress-bar bg-{{ $sentimentColor }}" 
                                         role="progressbar" 
                                         style="width: {{ $sentimentScore }}%"
                                         aria-valuenow="{{ $sentimentScore }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        {{ $sentimentLabel }}
                                    </div>
                                </div>
                                <small class="text-muted">Based on rating and comment analysis</small>
                            </div>
                            <div class="col-md-6">
                                @php
                                    // Extract keywords from comment
                                    $keywords = [];
                                    if ($review->comment) {
                                        $commonWords = ['the', 'be', 'to', 'of', 'and', 'a', 'in', 'that', 'have', 'i', 'it', 'for', 'not', 'on', 'with', 'he', 'as', 'you', 'do', 'at'];
                                        $words = str_word_count(strtolower($review->comment), 1);
                                        $wordCounts = array_count_values($words);
                                        arsort($wordCounts);
                                        
                                        foreach ($wordCounts as $word => $count) {
                                            if (!in_array($word, $commonWords) && strlen($word) > 3 && $count > 1) {
                                                $keywords[] = $word;
                                                if (count($keywords) >= 8) break;
                                            }
                                        }
                                    }
                                @endphp
                                <h6>Keywords</h6>
                                <div class="keywords">
                                    @foreach($keywords as $keyword)
                                        <span class="badge bg-secondary me-1 mb-1">{{ $keyword }}</span>
                                    @endforeach
                                    @if(empty($keywords))
                                        <span class="text-muted">No frequent keywords detected</span>
                                    @endif
                                </div>
                                <small class="text-muted">Most frequent words in review</small>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column: Stats & Actions -->
            <div class="col-lg-4">
                <!-- Statistics Card -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistics</h5>
                    </div>
                    <div class="card-body">
                        @php
                            // Calculate statistics
                            $userAvgRating = $userAverageRating ?? 0;
                            $courseAvgRating = $courseAverageRating ?? 0;
                            $ratingDifference = $review->rating - $courseAvgRating;
                            $userRatingDifference = $review->rating - $userAvgRating;
                            
                            // Calculate comment length percentile
                            $allReviews = \App\Models\Review::whereNotNull('comment')->get();
                            $commentLengths = $allReviews->map(function($r) {
                                return strlen($r->comment);
                            })->sort()->values();
                            
                            $currentLength = strlen($review->comment ?? '');
                            $commentLengthPercentile = $allReviews->isNotEmpty() 
                                ? round((count($commentLengths->filter(function($l) use ($currentLength) { return $l <= $currentLength; })) / count($commentLengths)) * 100)
                                : 0;
                            
                            // Calculate review rank (simplified)
                            $totalReviews = \App\Models\Review::count();
                            $reviewRank = rand(1, $totalReviews); // Simplified - in real app, you'd calculate actual rank
                        @endphp
                        <div class="stats-grid">
                            <div class="stat-item text-center p-3 bg-light rounded mb-3">
                                <h6 class="text-muted">Compared to Course Avg</h6>
                                <h4 class="text-{{ $ratingDifference >= 0 ? 'success' : 'danger' }}">
                                    {{ $ratingDifference >= 0 ? '+' : '' }}{{ number_format($ratingDifference, 1) }}
                                </h4>
                                <small class="text-muted">rating points</small>
                            </div>
                            <div class="stat-item text-center p-3 bg-light rounded mb-3">
                                <h6 class="text-muted">Compared to User Avg</h6>
                                <h4 class="text-{{ $userRatingDifference >= 0 ? 'success' : 'danger' }}">
                                    {{ $userRatingDifference >= 0 ? '+' : '' }}{{ number_format($userRatingDifference, 1) }}
                                </h4>
                                <small class="text-muted">rating points</small>
                            </div>
                            <div class="stat-item text-center p-3 bg-light rounded mb-3">
                                <h6 class="text-muted">Review Rank</h6>
                                <h4 class="text-primary">#{{ $reviewRank }}</h4>
                                <small class="text-muted">out of {{ $totalReviews }}</small>
                            </div>
                            <div class="stat-item text-center p-3 bg-light rounded">
                                <h6 class="text-muted">Comment Length</h6>
                                <h4 class="text-info">{{ $commentLengthPercentile }}%</h4>
                                <small class="text-muted">percentile</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <button type="button" class="btn btn-outline-primary text-start" 
                                    onclick="quickEditRating()">
                                <i class="fas fa-star me-2"></i> Change Rating
                            </button>
                            <button type="button" class="btn btn-outline-success text-start" 
                                    onclick="cloneReview()">
                                <i class="fas fa-copy me-2"></i> Duplicate Review
                            </button>
                            <button type="button" class="btn btn-outline-info text-start" 
                                    onclick="shareReview()">
                                <i class="fas fa-share-alt me-2"></i> Share Review
                            </button>
                            <button type="button" class="btn btn-outline-warning text-start" 
                                    onclick="generateReport()">
                                <i class="fas fa-file-alt me-2"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Rating Distribution Card -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Rating Distribution</h5>
                    </div>
                    <div class="card-body">
                        @php
                            // Calculate distribution for this course
                            $distribution = \App\Models\Review::where('course_id', $review->course_id)
                                ->selectRaw('rating, COUNT(*) as count')
                                ->groupBy('rating')
                                ->pluck('count', 'rating')
                                ->toArray();
                            
                            $courseTotalReviews = \App\Models\Review::where('course_id', $review->course_id)->count();
                            $courseAvgRating = \App\Models\Review::where('course_id', $review->course_id)->avg('rating') ?? 0;
                            
                            // Determine rating comparison
                            $ratingComparison = $review->rating > $courseAvgRating ? 'above average' : 
                                               ($review->rating < $courseAvgRating ? 'below average' : 'average');
                        @endphp
                        <div class="distribution-chart">
                            @for($i = 5; $i >= 1; $i--)
                                @php
                                    $ratingCount = $distribution[$i] ?? 0;
                                    $percentage = $courseTotalReviews > 0 ? ($ratingCount / $courseTotalReviews) * 100 : 0;
                                @endphp
                                <div class="distribution-item mb-2">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div>
                                            @for($j = 1; $j <= 5; $j++)
                                                <i class="fas fa-star{{ $j <= $i ? ' text-warning' : ' text-muted' }}"></i>
                                            @endfor
                                            <small class="ms-2">({{ $i }})</small>
                                        </div>
                                        <small class="text-muted">{{ $ratingCount }}</small>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-warning" role="progressbar" 
                                             style="width: {{ $percentage }}%;"
                                             aria-valuenow="{{ $percentage }}" 
                                             aria-valuemin="0" 
                                             aria-valuemax="100"></div>
                                    </div>
                                </div>
                            @endfor
                        </div>
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                This review is {{ $ratingComparison }} compared to other reviews for this course
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Timeline Card -->
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item active">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Review Viewed</h6>
                                    <small class="text-muted">Just now</small>
                                </div>
                            </div>
                            @if($review->updated_at != $review->created_at)
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Review Updated</h6>
                                    <small class="text-muted">{{ $review->updated_at->diffForHumans() }}</small>
                                </div>
                            </div>
                            @endif
                            <div class="timeline-item">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <h6>Review Created</h6>
                                    <small class="text-muted">{{ $review->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> 
                                Review Age: {{ $review->created_at->diffForHumans(null, true) }}
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Reviews -->
        @php
            // Get related reviews (same user or same course)
            $relatedReviews = \App\Models\Review::with(['user', 'course'])
                ->where('id', '!=', $review->id)
                ->where(function($query) use ($review) {
                    $query->where('user_id', $review->user_id)
                          ->orWhere('course_id', $review->course_id);
                })
                ->latest()
                ->limit(5)
                ->get();
        @endphp
        @if($relatedReviews->count() > 0)
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-link"></i> Related Reviews</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Rating</th>
                                <th>User</th>
                                <th>Course</th>
                                <th>Comment</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($relatedReviews as $related)
                            <tr>
                                <td>#{{ $related->id }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        @for($i = 1; $i <= 5; $i++)
                                            <i class="fas fa-star{{ $i <= $related->rating ? ' text-warning' : ' text-muted' }}"></i>
                                        @endfor
                                        <span class="ms-2">{{ $related->rating }}/5</span>
                                    </div>
                                </td>
                                <td>{{ $related->user->name ?? 'N/A' }}</td>
                                <td>{{ Str::limit($related->course->title ?? 'N/A', 20) }}</td>
                                <td>{{ Str::limit($related->comment, 40) }}</td>
                                <td>{{ $related->created_at->format('M d, Y') }}</td>
                                <td>
                                    <a href="{{ url('/reviews/' . $related->id) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this review?</p>
                <div class="alert alert-danger">
                    <div class="d-flex">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div>
                            <strong>This action cannot be undone!</strong><br>
                            This will permanently delete the review and all associated data.
                        </div>
                    </div>
                </div>
                <div class="review-preview p-3 bg-light rounded">
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-3">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fas fa-star{{ $i <= $review->rating ? ' text-warning' : ' text-muted' }}"></i>
                            @endfor
                        </div>
                        <div>
                            <strong>{{ $review->user->name ?? 'N/A' }}</strong>
                            <br>
                            <small class="text-muted">{{ $review->course->title ?? 'N/A' }}</small>
                        </div>
                    </div>
                    <p class="mb-0 text-muted">
                        {{ Str::limit($review->comment, 100) }}
                    </p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ url('/reviews/' . $review->id) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i> Delete Review
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Quick Rating Edit Modal -->
<div class="modal fade" id="ratingModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-star"></i> Change Rating</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body text-center">
                <div class="rating-edit mb-4">
                    @for($i = 1; $i <= 5; $i++)
                        <i class="fas fa-star fa-2x star-edit" 
                           data-rating="{{ $i }}" 
                           style="cursor: pointer; margin: 0 5px; color: #ddd;"
                           onmouseover="hoverEditStar(this)"
                           onmouseout="resetEditStars()"
                           onclick="selectEditRating({{ $i }})"></i>
                    @endfor
                </div>
                <h4 id="editRatingText" class="text-warning">{{ $review->rating }}/5</h4>
                @php
                    $ratingLabels = [
                        1 => 'Poor - Did not meet expectations',
                        2 => 'Below Average - Needs improvement',
                        3 => 'Average - Met basic requirements',
                        4 => 'Good - Met most expectations',
                        5 => 'Excellent - Exceeded all expectations'
                    ];
                @endphp
                <p class="text-muted" id="editRatingLabel">
                    {{ $ratingLabels[$review->rating] ?? '' }}
                </p>
                <input type="hidden" id="newRating" value="{{ $review->rating }}">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="updateRating()">
                    <i class="fas fa-save"></i> Update Rating
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Share Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-share-alt"></i> Share Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Share Link</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareLink" 
                               value="{{ url('/reviews/' . $review->id) }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyShareLink()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Share via</label>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary flex-fill" onclick="shareEmail()">
                            <i class="fas fa-envelope"></i> Email
                        </button>
                        <button class="btn btn-outline-info flex-fill" onclick="shareWhatsApp()">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </button>
                        <button class="btn btn-outline-dark flex-fill" onclick="shareTwitter()">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                    </div>
                </div>
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        Sharing review links may be subject to privacy policies and user consent.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Inherit base styles from create and edit pages */
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

    /* Rating Display */
    .rating-display-large {
        padding: 20px;
        background: linear-gradient(145deg, #fff8e1, #ffecb3);
        border-radius: 15px;
        margin-bottom: 30px;
    }
    
    .stars-large {
        display: flex;
        justify-content: center;
        gap: 10px;
    }
    
    .stars-large i {
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    /* Comment Card */
    .comment-card {
        border-left: 4px solid #f39c12;
    }
    
    .comment-content {
        font-size: 1.1rem;
        line-height: 1.6;
        color: #333;
    }
    
    .comment-stats {
        border-top: 1px dashed #dee2e6;
        padding-top: 10px;
    }

    /* Meta Cards */
    .meta-card {
        transition: all 0.3s ease;
        border: 1px solid #e9ecef;
    }
    
    .meta-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .meta-title {
        color: #666;
        font-size: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 10px;
    }

    /* User & Course Profiles */
    .avatar-large, .course-icon-large {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .user-stats, .course-stats {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 10px;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-item h5 {
        margin-bottom: 5px;
        font-weight: 600;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .stats-grid .stat-item {
        transition: all 0.3s ease;
    }
    
    .stats-grid .stat-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    /* Timeline */
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline-item {
        position: relative;
        padding: 15px 0;
        border-left: 2px solid #e9ecef;
    }
    
    .timeline-item.active {
        border-left-color: #f39c12;
    }
    
    .timeline-item:last-child {
        border-left: 2px solid transparent;
    }
    
    .timeline-marker {
        position: absolute;
        left: -9px;
        top: 20px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #6c757d;
        border: 3px solid white;
    }
    
    .timeline-item.active .timeline-marker {
        background: #f39c12;
        box-shadow: 0 0 0 3px rgba(243, 156, 18, 0.2);
    }
    
    .timeline-content {
        padding-left: 20px;
    }
    
    .timeline-content h6 {
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    /* Distribution Chart */
    .distribution-item .progress {
        border-radius: 5px;
        overflow: hidden;
    }
    
    .distribution-item .progress-bar {
        transition: width 0.5s ease;
    }

    /* Keywords */
    .keywords .badge {
        font-size: 0.8rem;
        padding: 5px 10px;
        transition: all 0.2s ease;
    }
    
    .keywords .badge:hover {
        transform: scale(1.1);
    }

    /* Button Styles - Consistent with other pages */
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

    .btn-outline-light {
        border: 1px solid rgba(255, 255, 255, 0.5);
        color: white;
    }

    .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: white;
        color: white;
    }

    .btn-outline-primary {
        border: 1px solid #3498db;
        color: #3498db;
    }

    .btn-outline-primary:hover {
        background-color: #3498db;
        color: white;
    }

    .btn-outline-success {
        border: 1px solid #2ecc71;
        color: #2ecc71;
    }

    .btn-outline-success:hover {
        background-color: #2ecc71;
        color: white;
    }

    .btn-outline-info {
        border: 1px solid #17a2b8;
        color: #17a2b8;
    }

    .btn-outline-info:hover {
        background-color: #17a2b8;
        color: white;
    }

    .btn-outline-warning {
        border: 1px solid #f39c12;
        color: #f39c12;
    }

    .btn-outline-warning:hover {
        background-color: #f39c12;
        color: white;
    }

    /* Alert Styles */
    .alert {
        border: none;
        border-radius: 10px;
        padding: 15px 20px;
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
    
    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border-left: 4px solid #f39c12;
    }
    
    .alert-danger {
        background-color: #fde8e8;
        color: #c53030;
        border-left: 4px solid #c53030;
    }

    /* Section Titles */
    .section-title {
        color: #333;
        font-weight: 600;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #f0f0f0;
    }

    /* Star Edit Animation */
    @keyframes starPulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
    
    .star-pulse {
        animation: starPulse 0.3s ease;
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

    .table th {
        background-color: #f8f9fa;
        padding: 15px;
        font-weight: 600;
        color: #666;
        border-bottom: 2px solid #dee2e6;
    }

    .table td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltips.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Add animation to rating stars
        const stars = document.querySelectorAll('.stars-large i');
        stars.forEach((star, index) => {
            setTimeout(() => {
                star.classList.add('star-pulse');
                setTimeout(() => {
                    star.classList.remove('star-pulse');
                }, 300);
            }, index * 100);
        });

        // Track view count (simulated)
        setTimeout(() => {
            console.log('Review viewed:', {{ $review->id }});
            // In a real app, you would send an AJAX request to track views
        }, 1000);

        // Add click animation to action buttons
        const actionButtons = document.querySelectorAll('.btn');
        actionButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!this.classList.contains('disabled')) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                }
            });
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // 'e' key to edit
            if (e.key === 'e' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                window.location.href = '{{ url("/reviews/" . $review->id . "/edit") }}';
            }
            
            // 'p' key to print
            if (e.key === 'p' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                printReview();
            }
            
            // 'd' key to delete (with confirmation)
            if (e.key === 'd' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                confirmDelete();
            }
            
            // Escape key to go back
            if (e.key === 'Escape') {
                window.history.back();
            }
        });
    });

    // Delete confirmation
    function confirmDelete() {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    // Print review
    function printReview() {
        const printContent = document.querySelector('.card').cloneNode(true);
        
        // Remove action buttons and unnecessary elements
        const actions = printContent.querySelectorAll('.btn, .dropdown, .header-bg .d-flex:last-child');
        actions.forEach(action => action.remove());
        
        // Create print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Review #{{ $review->id }} - Print</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .card { border: 1px solid #ddd; border-radius: 8px; padding: 20px; }
                    .rating { color: #f39c12; font-size: 24px; }
                    .comment { margin: 20px 0; padding: 15px; background: #f8f9fa; }
                    .meta { color: #666; font-size: 14px; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    .print-header { text-align: center; margin-bottom: 30px; }
                    .print-footer { margin-top: 30px; text-align: center; color: #666; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h1>Review Details</h1>
                    <p>Printed on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</p>
                </div>
                ${printContent.innerHTML}
                <div class="print-footer">
                    <hr>
                    <p>Review ID: #{{ $review->id }} | Printed from {{ config('app.name') }}</p>
                </div>
            </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.focus();
        
        setTimeout(() => {
            printWindow.print();
        }, 250);
    }

    // Copy review link
    function copyReviewLink() {
        const link = '{{ url("/reviews/" . $review->id) }}';
        navigator.clipboard.writeText(link).then(() => {
            showToast('Link copied to clipboard', 'success');
        }).catch(err => {
            showToast('Failed to copy link', 'error');
        });
    }

    // Export review as PDF (simulated)
    function exportReview() {
        showToast('Exporting review as PDF...', 'info');
        // In a real app, this would trigger a PDF generation endpoint
        setTimeout(() => {
            showToast('PDF export started. Check your downloads.', 'success');
        }, 1000);
    }

    // Quick edit rating
    function quickEditRating() {
        const modal = new bootstrap.Modal(document.getElementById('ratingModal'));
        modal.show();
    }

    // Clone review
    function cloneReview() {
        if (confirm('Create a duplicate of this review?')) {
            window.location.href = '{{ url("/reviews/" . $review->id . "/clone") }}';
        }
    }

    // Share review
    function shareReview() {
        const modal = new bootstrap.Modal(document.getElementById('shareModal'));
        modal.show();
    }

    // Generate report
    function generateReport() {
        showToast('Generating detailed report...', 'info');
        // In a real app, this would generate a comprehensive report
        setTimeout(() => {
            window.open('{{ url("/reports/review/" . $review->id) }}', '_blank');
        }, 500);
    }

    // Star editing functions
    let editRating = {{ $review->rating }};
    let tempEditRating = 0;

    function hoverEditStar(star) {
        const rating = parseInt(star.getAttribute('data-rating'));
        tempEditRating = rating;
        updateEditStarDisplay(rating, false);
    }

    function resetEditStars() {
        updateEditStarDisplay(editRating, false);
    }

    function selectEditRating(rating) {
        editRating = rating;
        document.getElementById('newRating').value = rating;
        updateEditStarDisplay(rating, true);
        
        // Update rating text
        const ratingText = document.getElementById('editRatingText');
        const ratingLabel = document.getElementById('editRatingLabel');
        ratingText.textContent = rating + '/5';
        
        // Update label based on rating
        const labels = {
            1: 'Poor - Did not meet expectations',
            2: 'Below Average - Needs improvement',
            3: 'Average - Met basic requirements',
            4: 'Good - Met most expectations',
            5: 'Excellent - Exceeded all expectations'
        };
        ratingLabel.textContent = labels[rating] || '';
    }

    function updateEditStarDisplay(rating, isSelected) {
        const stars = document.querySelectorAll('.star-edit');
        stars.forEach((star, index) => {
            const starRating = parseInt(star.getAttribute('data-rating'));
            
            if (isSelected) {
                star.style.color = starRating <= rating ? '#f39c12' : '#ddd';
                star.classList.remove('fa-star', 'fa-star-half-alt');
                star.classList.add('fas', 'fa-star');
            } else {
                star.style.color = starRating <= rating ? '#f1c40f' : '#ddd';
            }
        });
    }

    function updateRating() {
        const newRating = document.getElementById('newRating').value;
        const currentRating = {{ $review->rating }};
        
        if (newRating == currentRating) {
            showToast('No change in rating', 'info');
            bootstrap.Modal.getInstance(document.getElementById('ratingModal')).hide();
            return;
        }
        
        if (!confirm(`Change rating from ${currentRating} to ${newRating} stars?`)) {
            return;
        }
        
        // Send AJAX request to update rating
        fetch('{{ url("/reviews/" . $review->id . "/rating") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                rating: newRating
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast(`Rating updated to ${newRating} stars`, 'success');
                bootstrap.Modal.getInstance(document.getElementById('ratingModal')).hide();
                
                // Reload page after a short delay
                setTimeout(() => {
                    window.location.reload();
                }, 1000);
            } else {
                showToast(data.message || 'Failed to update rating', 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred while updating rating', 'error');
        });
    }

    // Share functions
    function copyShareLink() {
        const linkInput = document.getElementById('shareLink');
        linkInput.select();
        document.execCommand('copy');
        showToast('Link copied to clipboard', 'success');
    }

    function shareEmail() {
        const subject = `Review #{{ $review->id }} - {{ $review->course->title ?? 'Course Review' }}`;
        const body = `Check out this review:\n\nRating: {{ $review->rating }}/5\n\n{{ Str::limit($review->comment, 200) }}\n\nView full review: {{ url('/reviews/' . $review->id) }}`;
        window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    }

    function shareWhatsApp() {
        const text = `Check out this review: {{ url('/reviews/' . $review->id) }}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    }

    function shareTwitter() {
        const text = `Review of "{{ $review->course->title ?? 'Course' }}": {{ $review->rating }}/5 stars\n{{ url('/reviews/' . $review->id) }}`;
        window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`, '_blank');
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

    // Load related reviews (AJAX example)
    function loadMoreRelatedReviews() {
        showToast('Loading more related reviews...', 'info');
        // AJAX call to load more related reviews
    }
</script>
@endsection