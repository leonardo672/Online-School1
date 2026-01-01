@extends('layout')
@section('content')

<!-- Main Card with Updated Styling -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-book-open"></i> Lessons Management</h2>
            <div class="d-flex">
                <!-- Course Filter -->
                @if(isset($courses) && $courses->isNotEmpty())
                <div class="me-3">
                    <select id="courseFilter" class="form-select form-select-sm" onchange="filterByCourse(this.value)">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                            {{ $course->title }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <!-- Search Form -->
                <form action="{{ url('/lessons') }}" method="GET" class="d-inline">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search lessons..." 
                               value="{{ request('search') }}" style="max-width: 250px;">
                        <input type="hidden" name="course_id" id="searchCourseId" value="{{ request('course_id') }}">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Button to Add New Lesson -->
        <a href="{{ url('/lessons/create') }}" class="btn btn-add-lesson mb-4" title="Add New Lesson">
            <i class="fas fa-plus-circle" aria-hidden="true" style="margin-right: 8px;"></i> Add New Lesson
        </a>
        
        <!-- Lesson Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white p-3 rounded">
                    <h6 class="mb-1">Total Lessons</h6>
                    <h3 class="mb-0">
                        @if(method_exists($lessons, 'total'))
                            {{ $lessons->total() }}
                        @else
                            {{ $lessons->count() }}
                        @endif
                    </h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h6 class="mb-1">With Video</h6>
                    <h3 class="mb-0">{{ $lessonsWithVideo ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h6 class="mb-1">Avg. Position</h6>
                    <h3 class="mb-0">{{ $averagePosition ?? 'N/A' }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h6 class="mb-1">Latest Added</h6>
                    <h5 class="mb-0">
                        @if($lessons->isNotEmpty() && $lessons->first())
                            {{ $lessons->first()->created_at->diffForHumans() }}
                        @else
                            N/A
                        @endif
                    </h5>
                </div>
            </div>
        </div>
        
        <!-- Lessons Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Position</th>
                        <th>Lesson Title</th>
                        <th>Course</th>
                        <th>Content Preview</th>
                        <th>Video</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lessons as $item)
                        <tr>
                            <td>
                                @if(method_exists($lessons, 'currentPage'))
                                    {{ ($lessons->currentPage() - 1) * $lessons->perPage() + $loop->iteration }}
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </td>
                            <td>
                                <div class="position-badge">
                                    <span class="badge bg-primary position-number">
                                        {{ $item->position }}
                                    </span>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="lesson-icon me-2">
                                        <i class="fas fa-book" style="color: #2ecc71;"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $item->title }}</strong>
                                        <br>
                                        <small class="text-muted">ID: {{ $item->id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($item->course)
                                <div class="course-info">
                                    <a href="{{ url('/courses/' . $item->course_id) }}" class="text-decoration-none">
                                        <div class="d-flex align-items-center">
                                            <div class="course-icon me-2">
                                                <i class="fas fa-graduation-cap" style="color: #3498db;"></i>
                                            </div>
                                            <div>
                                                <strong class="text-primary">{{ $item->course->title }}</strong>
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
                                @if($item->content)
                                    <div class="content-preview" data-bs-toggle="tooltip" title="{{ strip_tags($item->content) }}">
                                        {{ Str::limit(strip_tags($item->content), 40) }}
                                    </div>
                                @else
                                    <span class="text-muted">No content</span>
                                @endif
                            </td>
                            <td>
                                @if($item->video_url)
                                    <span class="badge bg-success">
                                        <i class="fas fa-video"></i> Has Video
                                    </span>
                                    <br>
                                    <small class="text-muted">
                                        {{ parse_url($item->video_url, PHP_URL_HOST) }}
                                    </small>
                                @else
                                    <span class="badge bg-secondary">No Video</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $item->created_at->format('M d, Y') }}</small>
                                <br>
                                <small class="text-muted">{{ $item->created_at->format('h:i A') }}</small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- View Button -->
                                    <a href="{{ url('/lessons/' . $item->id) }}" title="View Lesson" class="btn btn-view btn-sm">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ url('/lessons/' . $item->id . '/edit') }}" title="Edit Lesson" class="btn btn-edit btn-sm">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>

                                    <!-- Move Up Button -->
                                    <button type="button" class="btn btn-position btn-sm" title="Move Up"
                                            onclick="moveLesson({{ $item->id }}, 'up')"
                                            {{ $loop->first ? 'disabled' : '' }}>
                                        <i class="fas fa-arrow-up" aria-hidden="true"></i>
                                    </button>

                                    <!-- Move Down Button -->
                                    <button type="button" class="btn btn-position btn-sm" title="Move Down"
                                            onclick="moveLesson({{ $item->id }}, 'down')"
                                            {{ $loop->last ? 'disabled' : '' }}>
                                        <i class="fas fa-arrow-down" aria-hidden="true"></i>
                                    </button>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ url('/lessons/' . $item->id) }}" accept-charset="UTF-8" style="display:inline">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-delete btn-sm" title="Delete Lesson" 
                                                onclick="return confirm('Are you sure you want to delete this lesson?')">
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
        @if(method_exists($lessons, 'hasPages') && $lessons->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $lessons->withQueryString()->links() }}
            </div>
        @endif
        
        <!-- No Lessons Message -->
        @if($lessons->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-book-open fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No lessons found</h4>
                <p class="text-muted">Lessons are the building blocks of your courses</p>
                <a href="{{ url('/lessons/create') }}" class="btn btn-add-lesson">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Create Your First Lesson
                </a>
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
        background: linear-gradient(145deg, #2ecc71, #27ae60);
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

    /* Add New Lesson Button */
    .btn-add-lesson {
        background: linear-gradient(145deg, #2ecc71, #27ae60);
        color: white;
        font-weight: 600;
        padding: 12px 25px;
    }

    .btn-add-lesson:hover {
        background: linear-gradient(145deg, #27ae60, #219653);
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(46, 204, 113, 0.4);
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
        background-color: #fd7e14;
        color: white;
    }

    .btn-edit:hover {
        background-color: #e8650f;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(253, 126, 20, 0.3);
    }

    .btn-position {
        background-color: #6c757d;
        color: white;
    }

    .btn-position:hover:not(:disabled) {
        background-color: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
    }

    .btn-position:disabled {
        opacity: 0.5;
        cursor: not-allowed;
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
        background-color: #e9f7ef;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    .table th {
        background: linear-gradient(145deg, #2ecc71, #27ae60);
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

    .position-badge {
        display: flex;
        justify-content: center;
    }

    .position-number {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        font-weight: bold;
        font-size: 14px;
    }

    /* Lesson and Course Icons */
    .lesson-icon, .course-icon {
        width: 40px;
        text-align: center;
    }

    /* Content Preview */
    .content-preview {
        max-width: 200px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        cursor: help;
    }

    /* Course Filter */
    #courseFilter {
        width: 200px;
        border-radius: 6px;
        border: 1px solid #ced4da;
    }

    /* Pagination */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        color: #2ecc71;
        border: 1px solid #dee2e6;
        margin: 0 3px;
        border-radius: 6px;
    }

    .page-item.active .page-link {
        background: linear-gradient(145deg, #2ecc71, #27ae60);
        border-color: #2ecc71;
        color: white;
    }

    .page-link:hover {
        color: #219653;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    /* Empty State */
    .fa-3x {
        font-size: 4rem;
    }

    /* Tooltip */
    .tooltip-inner {
        max-width: 300px;
        padding: 10px;
        background-color: #333;
        color: white;
        border-radius: 8px;
    }
</style>

<!-- JavaScript for Enhanced Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Confirm before deleting
        const deleteForms = document.querySelectorAll('form[action*="/lessons/"]');
        
        deleteForms.forEach(form => {
            if (form.method === 'post') {
                form.addEventListener('submit', function(e) {
                    const confirmDelete = confirm('Are you sure you want to delete this lesson?');
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
        
        // Position badge animation
        const positionBadges = document.querySelectorAll('.position-number');
        positionBadges.forEach(badge => {
            badge.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.2) rotate(10deg)';
                this.style.transition = 'transform 0.3s ease';
            });
            
            badge.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1) rotate(0deg)';
            });
        });
        
        // Content preview click to expand
        const contentPreviews = document.querySelectorAll('.content-preview');
        contentPreviews.forEach(preview => {
            preview.addEventListener('click', function() {
                const content = this.getAttribute('data-bs-title') || this.title;
                if (content && content.length > 40) {
                    // Create modal for full content view
                    const modalHtml = `
                        <div class="modal fade" id="contentModal" tabindex="-1">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Lesson Content</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="content-display">
                                            ${content}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Remove existing modal if any
                    const existingModal = document.getElementById('contentModal');
                    if (existingModal) {
                        existingModal.remove();
                    }
                    
                    // Add new modal
                    document.body.insertAdjacentHTML('beforeend', modalHtml);
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('contentModal'));
                    modal.show();
                }
            });
        });
    });
    
    // Filter lessons by course
    function filterByCourse(courseId) {
        const searchCourseId = document.getElementById('searchCourseId');
        const searchForm = searchCourseId.closest('form');
        
        searchCourseId.value = courseId;
        searchForm.submit();
    }
    
    // Move lesson position
    function moveLesson(lessonId, direction) {
        if (!lessonId || !direction) return;
        
        // Show loading state
        const button = event.target.closest('button');
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
        button.disabled = true;
        
        // Send AJAX request
        fetch(`/lessons/${lessonId}/move-${direction}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            credentials: 'same-origin'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload page to see updated positions
                window.location.reload();
            } else {
                alert(data.message || 'Failed to move lesson');
                button.innerHTML = originalHtml;
                button.disabled = false;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while moving the lesson');
            button.innerHTML = originalHtml;
            button.disabled = false;
        });
    }
    
    // Sortable table functionality (optional enhancement)
    function makeTableSortable() {
        const table = document.querySelector('.table');
        const headers = table.querySelectorAll('th');
        
        headers.forEach((header, index) => {
            if (index !== headers.length - 1) { // Skip actions column
                header.style.cursor = 'pointer';
                header.addEventListener('click', function() {
                    const currentSort = this.getAttribute('data-sort') || 'asc';
                    const newSort = currentSort === 'asc' ? 'desc' : 'asc';
                    
                    // Update sort attribute
                    this.setAttribute('data-sort', newSort);
                    
                    // Update URL and reload
                    const url = new URL(window.location.href);
                    url.searchParams.set('sort_by', index);
                    url.searchParams.set('sort_order', newSort);
                    window.location.href = url.toString();
                });
            }
        });
    }
    
    // Initialize sortable table if enabled
    if (typeof makeTableSortable === 'function') {
        makeTableSortable();
    }
</script>

@endsection