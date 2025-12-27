@extends('layout')
@section('content')

<!-- Main Card with Updated Styling -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-folder"></i> Categories Management</h2>
            <div>
                <!-- Search Form -->
                <form action="{{ url('/categories') }}" method="GET" class="d-inline">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search categories..." 
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
        <!-- Button to Add New Category -->
        <a href="{{ url('/categories/create') }}" class="btn btn-add-category mb-4" title="Add New Category">
            <i class="fas fa-plus-circle" aria-hidden="true" style="margin-right: 8px;"></i> Add New Category
        </a>
        
        <!-- Category Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white p-3 rounded">
                    <h6 class="mb-1">Total Categories</h6>
                    <h3 class="mb-0">
                        @if(method_exists($categories, 'total'))
                            {{ $categories->total() }}
                        @else
                            {{ $categories->count() }}
                        @endif
                    </h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h6 class="mb-1">Categories with Courses</h6>
                    <h3 class="mb-0">{{ $categoriesWithCourses ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h6 class="mb-1">Empty Categories</h6>
                    <h3 class="mb-0">{{ $emptyCategories ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h6 class="mb-1">Latest Added</h6>
                    <h5 class="mb-0">
                        @if($categories->isNotEmpty() && $categories->first())
                            {{ $categories->first()->created_at->diffForHumans() }}
                        @else
                            N/A
                        @endif
                    </h5>
                </div>
            </div>
        </div>
        
        <!-- Categories Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Category</th>
                        <th>Icon & Color</th>
                        <th>Courses</th>
                        <th>Description</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categories as $item)
                        <tr>
                            <td>
                                @if(method_exists($categories, 'currentPage'))
                                    {{ ($categories->currentPage() - 1) * $categories->perPage() + $loop->iteration }}
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="category-icon me-2">
                                        @if($item->icon)
                                            @php
                                                $iconColor = $item->color ?? '#2ecc71';
                                            @endphp
                                            <i class="{{ $item->icon }}" style="color: {{ $iconColor }};"></i>
                                        @else
                                            <i class="fas fa-folder text-muted"></i>
                                        @endif
                                    </div>
                                    <div>
                                        <strong>{{ $item->name }}</strong>
                                        <br>
                                        <small class="text-muted"><code>{{ $item->slug }}</code></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="me-2">
                                        @if($item->icon)
                                            @php
                                                $iconColor = $item->color ?? '#2ecc71';
                                            @endphp
                                            <i class="{{ $item->icon }} fa-lg" style="color: {{ $iconColor }};"></i>
                                        @endif
                                    </div>
                                    <div>
                                        @if($item->color)
                                            <div class="color-preview" style="background-color: {{ $item->color }}; width: 20px; height: 20px; border-radius: 3px;"></div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td>
                                @php
                                    $courseCount = $item->courses_count ?? 0;
                                @endphp
                                @if($courseCount > 0)
                                    <a href="{{ url('/courses?category=' . $item->slug) }}" class="badge bg-success text-decoration-none">
                                        {{ $courseCount }} course{{ $courseCount !== 1 ? 's' : '' }}
                                    </a>
                                @else
                                    <span class="badge bg-secondary">No courses</span>
                                @endif
                            </td>
                            <td>
                                @if($item->description)
                                    <small class="text-muted">{{ Str::limit($item->description, 40) }}</small>
                                @else
                                    <span class="text-muted">No description</span>
                                @endif
                            </td>
                            <td>
                                <small class="text-muted">{{ $item->created_at->format('M d, Y') }}</small>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- View Button -->
                                    <a href="{{ url('/categories/' . $item->id) }}" title="View Category" class="btn btn-view btn-sm">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ url('/categories/' . $item->id . '/edit') }}" title="Edit Category" class="btn btn-edit btn-sm">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ url('/categories/' . $item->id) }}" accept-charset="UTF-8" style="display:inline">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-delete btn-sm" title="Delete Category" 
                                                onclick="return confirm('Are you sure you want to delete this category? All courses in this category will become uncategorized.')">
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
        @if(method_exists($categories, 'hasPages') && $categories->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $categories->withQueryString()->links() }}
            </div>
        @endif
        
        <!-- No Categories Message -->
        @if($categories->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-folder fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No categories found</h4>
                <p class="text-muted">Categories help organize your courses into logical groups</p>
                <a href="{{ url('/categories/create') }}" class="btn btn-add-category">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Create Your First Category
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
        padding: 6px 12px;
        font-size: 12px;
    }

    .btn-group .btn {
        margin: 0 2px;
        border-radius: 6px;
    }

    /* Add New Category Button */
    .btn-add-category {
        background: linear-gradient(145deg, #2ecc71, #27ae60);
        color: white;
        font-weight: 600;
        padding: 12px 25px;
    }

    .btn-add-category:hover {
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

    /* Category Icon */
    .category-icon {
        font-size: 20px;
        width: 40px;
        text-align: center;
    }

    .color-preview {
        border: 1px solid #dee2e6;
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

    /* Code styling for slugs */
    code {
        background-color: #f8f9fa;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 12px;
        color: #6c757d;
    }
</style>

<!-- JavaScript for Enhanced Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Confirm before deleting
        const deleteForms = document.querySelectorAll('form[action*="/categories/"]');
        
        deleteForms.forEach(form => {
            if (form.method === 'post') {
                form.addEventListener('submit', function(e) {
                    const confirmDelete = confirm('Are you sure you want to delete this category? All courses in this category will become uncategorized.');
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
        
        // Color preview enhancement
        const colorPreviews = document.querySelectorAll('.color-preview');
        colorPreviews.forEach(preview => {
            preview.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.2)';
                this.style.transition = 'transform 0.2s ease';
            });
            
            preview.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    });
</script>

@endsection