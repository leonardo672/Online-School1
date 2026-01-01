@extends('layout')
@section('content')

<!-- Main Card with Updated Styling -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-chart-line"></i> Lesson Progress Tracking</h2>
            <div class="d-flex">
                <!-- Filters -->
                <div class="me-3">
                    <select id="statusFilter" class="form-select form-select-sm" onchange="filterByStatus(this.value)">
                        <option value="">All Status</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="incomplete" {{ request('status') == 'incomplete' ? 'selected' : '' }}>Incomplete</option>
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
                
                <!-- Lesson Filter -->
                @if(isset($lessons) && $lessons->isNotEmpty())
                <div class="me-3">
                    <select id="lessonFilter" class="form-select form-select-sm" onchange="filterByLesson(this.value)">
                        <option value="">All Lessons</option>
                        @foreach($lessons as $lesson)
                        <option value="{{ $lesson->id }}" {{ request('lesson_id') == $lesson->id ? 'selected' : '' }}>
                            {{ Str::limit($lesson->title, 30) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <!-- Search Form -->
                <form action="{{ url('/lesson-progress') }}" method="GET" class="d-inline">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search..." 
                               value="{{ request('search') }}" style="max-width: 200px;">
                        <input type="hidden" name="status" id="searchStatus" value="{{ request('status') }}">
                        <input type="hidden" name="user_id" id="searchUserId" value="{{ request('user_id') }}">
                        <input type="hidden" name="lesson_id" id="searchLessonId" value="{{ request('lesson_id') }}">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Statistics Overview -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white p-3 rounded">
                    <h6 class="mb-1">Total Progress Records</h6>
                    <h3 class="mb-0">
                        @if(method_exists($lessonProgresses, 'total'))
                            {{ $lessonProgresses->total() }}
                        @else
                            {{ $lessonProgresses->count() }}
                        @endif
                    </h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h6 class="mb-1">Completed Lessons</h6>
                    <h3 class="mb-0">{{ $completedCount ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h6 class="mb-1">Completion Rate</h6>
                    <h3 class="mb-0">
                        @if($lessonProgressesCount > 0)
                            {{ number_format(($completedCount / $lessonProgressesCount) * 100, 1) }}%
                        @else
                            0%
                        @endif
                    </h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h6 class="mb-1">Latest Completion</h6>
                    <h5 class="mb-0">
                        @if($latestCompletion)
                            {{ $latestCompletion->diffForHumans() }}
                        @else
                            N/A
                        @endif
                    </h5>
                </div>
            </div>
        </div>
        
        <!-- Progress Tracking Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Lesson</th>
                        <th>Course</th>
                        <th>Status</th>
                        <th>Completion Date</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lessonProgresses as $item)
                        <tr>
                            <td>
                                @if(method_exists($lessonProgresses, 'currentPage'))
                                    {{ ($lessonProgresses->currentPage() - 1) * $lessonProgresses->perPage() + $loop->iteration }}
                                @else
                                    {{ $loop->iteration }}
                                @endif
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
                                <div class="d-flex align-items-center">
                                    <div class="lesson-icon me-2">
                                        <i class="fas fa-book" style="color: #2ecc71;"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $item->lesson->title ?? 'N/A' }}</strong>
                                        <br>
                                        <small class="text-muted">
                                            Position: {{ $item->lesson->position ?? 'N/A' }}
                                            @if($item->lesson->course)
                                                | {{ $item->lesson->course->code ?? '' }}
                                            @endif
                                        </small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($item->lesson->course)
                                <div class="course-info">
                                    <div class="d-flex align-items-center">
                                        <div class="course-icon me-2">
                                            <i class="fas fa-graduation-cap" style="color: #9b59b6;"></i>
                                        </div>
                                        <div>
                                            <strong>{{ $item->lesson->course->title ?? 'N/A' }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $item->lesson->course->code ?? '' }}</small>
                                        </div>
                                    </div>
                                </div>
                                @else
                                <span class="text-danger">No Course</span>
                                @endif
                            </td>
                            <td>
                                @if($item->completed)
                                <div class="status-badge">
                                    <span class="badge bg-success">
                                        <i class="fas fa-check-circle"></i> Completed
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        {{ $item->completed_at->format('M d, Y') }}
                                    </small>
                                </div>
                                @else
                                <span class="badge bg-secondary">
                                    <i class="fas fa-clock"></i> In Progress
                                </span>
                                @endif
                            </td>
                            <td>
                                @if($item->completed_at)
                                <div class="completion-date">
                                    <strong>{{ $item->completed_at->format('M d, Y') }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $item->completed_at->format('h:i A') }}</small>
                                </div>
                                @else
                                <span class="text-muted">Not completed</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $item->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- View Button -->
                                    <a href="{{ url('/lesson-progress/' . $item->id) }}" title="View Progress" class="btn btn-view btn-sm">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ url('/lesson-progress/' . $item->id . '/edit') }}" title="Edit Progress" class="btn btn-edit btn-sm">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>

                                    <!-- Toggle Completion Button -->
                                    <button type="button" class="btn btn-toggle btn-sm" 
                                            title="{{ $item->completed ? 'Mark as Incomplete' : 'Mark as Complete' }}"
                                            onclick="toggleCompletion({{ $item->id }}, {{ $item->completed ? 'false' : 'true' }})">
                                        <i class="fas {{ $item->completed ? 'fa-undo' : 'fa-check' }}" aria-hidden="true"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ url('/lesson-progress/' . $item->id) }}" accept-charset="UTF-8" style="display:inline">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-delete btn-sm" title="Delete Progress Record" 
                                                onclick="return confirm('Are you sure you want to delete this progress record?')">
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination - Only show if pagination is available -->
        @if(method_exists($lessonProgresses, 'hasPages') && $lessonProgresses->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $lessonProgresses->withQueryString()->links() }}
            </div>
        @endif
        
        <!-- No Progress Records Message -->
        @if($lessonProgresses->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-chart-line fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No progress records found</h4>
                <p class="text-muted">Track user progress through lessons here</p>
                <a href="{{ url('/lesson-progress/create') }}" class="btn btn-add-progress">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Create Progress Record
                </a>
            </div>
        @endif
        
        <!-- Bulk Actions -->
        @if($lessonProgresses->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-tasks"></i> Bulk Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Mark Selected as Complete</label>
                            <div class="input-group">
                                <input type="text" id="bulkCompleteIds" class="form-control" placeholder="Enter IDs (comma-separated)">
                                <button class="btn btn-success" type="button" onclick="bulkComplete()">
                                    <i class="fas fa-check"></i> Complete
                                </button>
                            </div>
                            <small class="form-text text-muted">Enter progress record IDs separated by commas</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Export Progress Data</label>
                            <div>
                                <a href="{{ url('/lesson-progress/export/csv') }}" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-file-csv"></i> Export CSV
                                </a>
                                <a href="{{ url('/lesson-progress/export/pdf') }}" class="btn btn-outline-danger">
                                    <i class="fas fa-file-pdf"></i> Export PDF
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Quick Stats</label>
                            <div class="small">
                                <p class="mb-1"><strong>Total Records:</strong> {{ $lessonProgressesCount ?? 0 }}</p>
                                <p class="mb-1"><strong>Completed:</strong> {{ $completedCount ?? 0 }}</p>
                                <p class="mb-1"><strong>In Progress:</strong> {{ ($lessonProgressesCount ?? 0) - ($completedCount ?? 0) }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- CSS Styling -->
<style>
    /* General Styles */
    .card {
        border: none;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        border-radius: 15px;
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(145deg, #9b59b6, #8e44ad);
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

    /* Add New Progress Button */
    .btn-add-progress {
        background: linear-gradient(145deg, #9b59b6, #8e44ad);
        color: white;
        font-weight: 600;
        padding: 12px 25px;
    }

    .btn-add-progress:hover {
        background: linear-gradient(145deg, #8e44ad, #7d3c98);
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(155, 89, 182, 0.4);
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
        background-color: #f39c12;
        color: white;
    }

    .btn-edit:hover {
        background-color: #e67e22;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(243, 156, 18, 0.3);
    }

    .btn-toggle {
        background-color: #2ecc71;
        color: white;
    }

    .btn-toggle:hover {
        background-color: #27ae60;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
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
        background-color: #f5e6ff;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    .table th {
        background: linear-gradient(145deg, #9b59b6, #8e44ad);
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

    /* Badge Styling */
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 12px;
    }

    /* User, Lesson, Course Icons */
    .user-avatar, .lesson-icon, .course-icon {
        width: 40px;
        text-align: center;
    }

    .completion-date {
        min-width: 100px;
    }

    .status-badge {
        display: flex;
        flex-direction: column;
        align-items: flex-start;
    }

    /* Filters */
    .form-select-sm {
        width: 150px;
        border-radius: 6px;
        border: 1px solid #ced4da;
    }

    /* Bulk Actions */
    #bulkCompleteIds {
        border-radius: 6px 0 0 6px;
    }

    /* Pagination */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        color: #9b59b6;
        border: 1px solid #dee2e6;
        margin: 0 3px;
        border-radius: 6px;
    }

    .page-item.active .page-link {
        background: linear-gradient(145deg, #9b59b6, #8e44ad);
        border-color: #9b59b6;
        color: white;
    }

    .page-link:hover {
        color: #8e44ad;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    /* Empty State */
    .fa-3x {
        font-size: 4rem;
    }

    /* Progress Animation */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.05); }
        100% { transform: scale(1); }
    }

    .pulse {
        animation: pulse 2s infinite;
    }
</style>

<!-- JavaScript for Enhanced Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Confirm before deleting
        const deleteForms = document.querySelectorAll('form[action*="/lesson-progress/"]');
        
        deleteForms.forEach(form => {
            if (form.method === 'post') {
                form.addEventListener('submit', function(e) {
                    const confirmDelete = confirm('Are you sure you want to delete this progress record?');
                    if (!confirmDelete) {
                        e.preventDefault();
                    }
                });
            }
        });
        
        // Add hover effect to table rows
        const tableRows = document.querySelectorAll('.table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.2s ease';
            });
        });
        
        // Status badge animation
        const statusBadges = document.querySelectorAll('.status-badge');
        statusBadges.forEach(badge => {
            if (badge.querySelector('.bg-success')) {
                badge.addEventListener('mouseenter', function() {
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.add('pulse');
                    }
                });
                
                badge.addEventListener('mouseleave', function() {
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.classList.remove('pulse');
                    }
                });
            }
        });
        
        // Completion date hover effect
        const completionDates = document.querySelectorAll('.completion-date');
        completionDates.forEach(date => {
            date.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            date.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0)';
            });
        });
    });
    
    // Filter functions
    function filterByStatus(status) {
        document.getElementById('searchStatus').value = status;
        const form = document.getElementById('searchStatus').closest('form');
        form.submit();
    }
    
    function filterByUser(userId) {
        document.getElementById('searchUserId').value = userId;
        const form = document.getElementById('searchUserId').closest('form');
        form.submit();
    }
    
    function filterByLesson(lessonId) {
        document.getElementById('searchLessonId').value = lessonId;
        const form = document.getElementById('searchLessonId').closest('form');
        form.submit();
    }
    
    // Toggle completion status
    function toggleCompletion(progressId, markAsComplete) {
        if (!progressId) return;
        
        const button = event.target.closest('button');
        const originalHtml = button.innerHTML;
        const originalTitle = button.title;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        button.title = 'Updating...';
        
        fetch(`/lesson-progress/${progressId}/toggle`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                completed: markAsComplete
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to show updated status
                window.location.reload();
            } else {
                alert(data.message || 'Failed to update progress');
                button.innerHTML = originalHtml;
                button.disabled = false;
                button.title = originalTitle;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating progress');
            button.innerHTML = originalHtml;
            button.disabled = false;
            button.title = originalTitle;
        });
    }
    
    // Bulk complete progress records
    function bulkComplete() {
        const idsInput = document.getElementById('bulkCompleteIds');
        const ids = idsInput.value.trim();
        
        if (!ids) {
            alert('Please enter progress record IDs');
            return;
        }
        
        // Validate IDs format
        const idArray = ids.split(',').map(id => id.trim()).filter(id => id);
        if (idArray.length === 0) {
            alert('Please enter valid IDs');
            return;
        }
        
        if (!confirm(`Mark ${idArray.length} record(s) as complete?`)) {
            return;
        }
        
        const button = event.target.closest('button');
        const originalHtml = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Processing...';
        button.disabled = true;
        
        fetch('/lesson-progress/bulk-complete', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                ids: idArray
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Successfully marked ${data.updated} record(s) as complete`);
                window.location.reload();
            } else {
                alert(data.message || 'Failed to update records');
                button.innerHTML = originalHtml;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while processing bulk update');
            button.innerHTML = originalHtml;
            button.disabled = false;
        });
    }
    
    // Export functionality
    function exportData(format) {
        const params = new URLSearchParams(window.location.search);
        params.set('format', format);
        
        window.location.href = `/lesson-progress/export?${params.toString()}`;
    }
    
    // Quick stats update
    function updateQuickStats() {
        // This would typically make an AJAX call to get updated stats
        // For now, we'll just show a loading indicator
        const statsContainer = document.querySelector('.quick-stats');
        if (statsContainer) {
            const originalHtml = statsContainer.innerHTML;
            statsContainer.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...';
            
            fetch('/lesson-progress/stats')
                .then(response => response.json())
                .then(data => {
                    statsContainer.innerHTML = `
                        <p class="mb-1"><strong>Total Records:</strong> ${data.total}</p>
                        <p class="mb-1"><strong>Completed:</strong> ${data.completed}</p>
                        <p class="mb-1"><strong>In Progress:</strong> ${data.in_progress}</p>
                    `;
                })
                .catch(error => {
                    console.error('Error:', error);
                    statsContainer.innerHTML = originalHtml;
                });
        }
    }
    
    // Auto-refresh stats every 30 seconds
    setInterval(updateQuickStats, 30000);
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            document.querySelector('input[name="search"]').focus();
        }
        
        // Ctrl/Cmd + N to add new progress
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = '/lesson-progress/create';
        }
    });
</script>

@endsection