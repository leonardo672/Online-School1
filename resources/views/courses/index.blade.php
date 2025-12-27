@extends('layout')
@section('content')

<!-- Main Card with Updated Styling -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-graduation-cap"></i> Courses Management</h2>
            <div>
                <!-- Search Form -->
                <form action="{{ url('/courses') }}" method="GET" class="d-inline">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search courses..." 
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
        <!-- Button to Add New Course -->
        <a href="{{ url('/courses/create') }}" class="btn btn-add-course mb-4" title="Add New Course">
            <i class="fas fa-plus-circle" aria-hidden="true" style="margin-right: 8px;"></i> Add New Course
        </a>
        
        <!-- Course Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white p-3 rounded">
                    <h6 class="mb-1">Total Courses</h6>
                    <h3 class="mb-0">{{ $courses->total() }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h6 class="mb-1">Published</h6>
                    <h3 class="mb-0">{{ $courses->where('published', true)->count() }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h6 class="mb-1">Draft</h6>
                    <h3 class="mb-0">{{ $courses->where('published', false)->count() }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h6 class="mb-1">Free Courses</h6>
                    <h3 class="mb-0">{{ $courses->where('price', 0)->count() }}</h3>
                </div>
            </div>
        </div>
        
        <!-- Courses Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Course</th>
                        <th>Category</th>
                        <th>Instructor</th>
                        <th>Price</th>
                        <th>Level</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($courses as $item)
                        <tr>
                            <td>{{ ($courses->currentPage() - 1) * $courses->perPage() + $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="course-icon me-2">
                                        <i class="fas fa-book text-primary"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $item->title }}</strong>
                                        <br>
                                        <small class="text-muted">{{ Str::limit($item->description, 50) }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($item->category)
                                    <span class="badge bg-secondary">{{ $item->category->name }}</span>
                                @else
                                    <span class="badge bg-danger">No Category</span>
                                @endif
                            </td>
                            <td>
                                @if($item->instructor)
                                    <span class="text-primary">{{ $item->instructor->name }}</span>
                                @endif
                            </td>
                            <td>
                                @if($item->price == 0)
                                    <span class="badge bg-success">Free</span>
                                @else
                                    ${{ number_format($item->price, 2) }}
                                @endif
                            </td>
                            <td>
                                @if($item->level == 'beginner')
                                    <span class="badge bg-info">Beginner</span>
                                @elseif($item->level == 'intermediate')
                                    <span class="badge bg-warning">Intermediate</span>
                                @elseif($item->level == 'advanced')
                                    <span class="badge bg-danger">Advanced</span>
                                @endif
                            </td>
                            <td>
                                @if($item->published)
                                    <span class="badge badge-published">Published</span>
                                @else
                                    <span class="badge badge-draft">Draft</span>
                                @endif
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- View Button -->
                                    <a href="{{ url('/courses/' . $item->id) }}" title="View Course" class="btn btn-view btn-sm">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ url('/courses/' . $item->id . '/edit') }}" title="Edit Course" class="btn btn-edit btn-sm">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ url('/courses/' . $item->id) }}" accept-charset="UTF-8" style="display:inline">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-delete btn-sm" title="Delete Course" 
                                                onclick="return confirm('Are you sure you want to delete this course?')">
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
        
        <!-- Pagination -->
        @if($courses->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $courses->withQueryString()->links() }}
            </div>
        @endif
        
        <!-- No Courses Message -->
        @if($courses->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-book fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No courses found</h4>
                <p class="text-muted">Get started by creating your first course!</p>
                <a href="{{ url('/courses/create') }}" class="btn btn-add-course">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Create Your First Course
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
        background: linear-gradient(145deg, #2c3e50, #3498db);
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

    /* Add New Course Button */
    .btn-add-course {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
        font-weight: 600;
        padding: 12px 25px;
    }

    .btn-add-course:hover {
        background: linear-gradient(145deg, #2980b9, #1c5a7a);
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        color: white;
    }

    /* Action Buttons */
    .btn-view {
        background-color: #007BFF;
        color: white;
    }

    .btn-view:hover {
        background-color: #0056b3;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 123, 255, 0.3);
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
        background-color: #e3f2fd;
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

    .badge-published {
        background: linear-gradient(145deg, #2ecc71, #27ae60);
        color: white;
    }

    .badge-draft {
        background: linear-gradient(145deg, #f39c12, #d35400);
        color: white;
    }

    /* Course Icon */
    .course-icon {
        font-size: 20px;
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
    }

    .page-link:hover {
        color: #1c5a7a;
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
    // Confirm before deleting
    document.addEventListener('DOMContentLoaded', function() {
        const deleteForms = document.querySelectorAll('form[action*="/courses/"]');
        
        deleteForms.forEach(form => {
            if (form.method === 'post') {
                form.addEventListener('submit', function(e) {
                    const confirmDelete = confirm('Are you sure you want to delete this course? This action cannot be undone.');
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
    });
</script>

@endsection