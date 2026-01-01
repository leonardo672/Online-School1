@extends('layout')
@section('content')

<!-- Main Card with Updated Styling -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-user-graduate"></i> Enrollments Management</h2>
            <div>
                <!-- Search Form -->
                <form action="{{ url('/enrollments') }}" method="GET" class="d-inline">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search enrollments..." 
                               value="{{ request('search') }}" style="max-width: 250px;">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Button to Add New Enrollment -->
        <a href="{{ url('/enrollments/create') }}" class="btn btn-add-enrollment mb-4" title="Add New Enrollment">
            <i class="fas fa-plus-circle" aria-hidden="true" style="margin-right: 8px;"></i> Add New Enrollment
        </a>
        
        <!-- Enrollment Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white p-3 rounded">
                    <h6 class="mb-1">Total Enrollments</h6>
                    <h3 class="mb-0">
                        @if(method_exists($enrollments, 'total'))
                            {{ $enrollments->total() }}
                        @else
                            {{ $enrollments->count() }}
                        @endif
                    </h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h6 class="mb-1">Active Users</h6>
                    <h3 class="mb-0">{{ $activeUsersCount ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h6 class="mb-1">Popular Courses</h6>
                    <h3 class="mb-0">{{ $popularCoursesCount ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h6 class="mb-1">Latest Enrollment</h6>
                    <h5 class="mb-0">
                        @if($enrollments->isNotEmpty() && $enrollments->first())
                            {{ $enrollments->first()->enrolled_at->diffForHumans() }}
                        @else
                            N/A
                        @endif
                    </h5>
                </div>
            </div>
        </div>
        
        <!-- Enrollments Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>User</th>
                        <th>Course</th>
                        <th>Enrollment Date</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($enrollments as $item)
                        <tr>
                            <td>
                                @if(method_exists($enrollments, 'currentPage'))
                                    {{ ($enrollments->currentPage() - 1) * $enrollments->perPage() + $loop->iteration }}
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-2">
                                        <i class="fas fa-user-circle fa-lg" style="color: #3498db;"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $item->user->name ?? 'N/A' }}</strong>
                                        <br>
                                        <small class="text-muted">ID: {{ $item->user_id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="course-icon me-2">
                                        <i class="fas fa-book fa-lg" style="color: #2ecc71;"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $item->course->title ?? 'N/A' }}</strong>
                                        <br>
                                        <small class="text-muted">ID: {{ $item->course_id }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="enrollment-date">
                                    <strong>{{ $item->enrolled_at->format('M d, Y') }}</strong>
                                    <br>
                                    <small class="text-muted">{{ $item->enrolled_at->format('h:i A') }}</small>
                                </div>
                            </td>
                            <td>
                                @php
                                    $isRecent = $item->enrolled_at->diffInDays(now()) <= 7;
                                @endphp
                                @if($isRecent)
                                    <span class="badge bg-success">
                                        <i class="fas fa-circle"></i> Active
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="fas fa-clock"></i> Older
                                    </span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $item->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- View Button -->
                                    <a href="{{ url('/enrollments/' . $item->id) }}" title="View Enrollment" class="btn btn-view btn-sm">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ url('/enrollments/' . $item->id . '/edit') }}" title="Edit Enrollment" class="btn btn-edit btn-sm">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ url('/enrollments/' . $item->id) }}" accept-charset="UTF-8" style="display:inline">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-delete btn-sm" title="Delete Enrollment" 
                                                onclick="return confirm('Are you sure you want to delete this enrollment?')">
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
        @if(method_exists($enrollments, 'hasPages') && $enrollments->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $enrollments->withQueryString()->links() }}
            </div>
        @endif
        
        <!-- No Enrollments Message -->
        @if($enrollments->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-user-graduate fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No enrollments found</h4>
                <p class="text-muted">Enrollments represent users registered for courses</p>
                <a href="{{ url('/enrollments/create') }}" class="btn btn-add-enrollment">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Create Your First Enrollment
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
        background: linear-gradient(145deg, #3498db, #2980b9);
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
        padding: 6px 12px;
        font-size: 12px;
    }

    .btn-group .btn {
        margin: 0 2px;
        border-radius: 6px;
    }

    /* Add New Enrollment Button */
    .btn-add-enrollment {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
        font-weight: 600;
        padding: 12px 25px;
    }

    .btn-add-enrollment:hover {
        background: linear-gradient(145deg, #2980b9, #2573a7);
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        color: white;
    }

    /* Action Buttons */
    .btn-view {
        background-color: #2ecc71;
        color: white;
    }

    .btn-view:hover {
        background-color: #27ae60;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
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
        background-color: #e9f4ff;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    .table th {
        background: linear-gradient(145deg, #3498db, #2980b9);
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

    /* User and Course Icons */
    .user-avatar, .course-icon {
        width: 40px;
        text-align: center;
    }

    .enrollment-date {
        min-width: 100px;
    }

    /* Pagination */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        color: #3498db;
        border: 1px solid #dee2e6;
        margin: 0 3px;
        border-radius: 6px;
    }

    .page-item.active .page-link {
        background: linear-gradient(145deg, #3498db, #2980b9);
        border-color: #3498db;
        color: white;
    }

    .page-link:hover {
        color: #2980b9;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    /* Empty State */
    .fa-3x {
        font-size: 4rem;
    }
</style>

<!-- JavaScript for Enhanced Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Confirm before deleting
        const deleteForms = document.querySelectorAll('form[action*="/enrollments/"]');
        
        deleteForms.forEach(form => {
            if (form.method === 'post') {
                form.addEventListener('submit', function(e) {
                    const confirmDelete = confirm('Are you sure you want to delete this enrollment?');
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
        const badges = document.querySelectorAll('.badge');
        badges.forEach(badge => {
            badge.addEventListener('mouseenter', function() {
                if (this.classList.contains('bg-success')) {
                    this.style.transform = 'scale(1.1)';
                    this.style.transition = 'transform 0.2s ease';
                }
            });
            
            badge.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    });
</script>

@endsection