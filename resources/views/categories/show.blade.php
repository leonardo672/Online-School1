@extends('layout')
@section('content')

@php
    // Safely get course data
    $coursesCount = 0;
    $publishedCoursesCount = 0;
    $draftCoursesCount = 0;
    $categoryCourses = collect([]);
    
    if (method_exists($category, 'courses')) {
        $coursesCount = $category->courses()->count();
        $publishedCoursesCount = $category->courses()->where('published', true)->count();
        $draftCoursesCount = $category->courses()->where('published', false)->count();
        $categoryCourses = $category->courses()->with('instructor')->get();
    }
@endphp

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="fas fa-folder"></i> Category Details: {{ $category->name }}
      </h4>
      <div>
        <a href="{{ url('/categories/' . $category->id . '/edit') }}" class="btn btn-edit">
          <i class="fas fa-edit"></i> Edit
        </a>
      </div>
    </div>
  </div>
  
  <div class="card-body">
    <div class="row">
      <!-- Left Column: Category Info -->
      <div class="col-md-8">
        <div class="category-details mb-4">
          <h5 class="text-success mb-3">
            <i class="fas fa-info-circle"></i> Category Information
          </h5>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <strong><i class="fas fa-tag text-muted"></i> Name:</strong>
              <p class="mb-2">
                <span class="h5">{{ $category->name }}</span>
              </p>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-link text-muted"></i> Slug:</strong>
              <p class="mb-2"><code>{{ $category->slug }}</code></p>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <strong><i class="fas fa-palette text-muted"></i> Visual Identity:</strong>
              <div class="d-flex align-items-center mt-2">
                <div class="me-3">
                  @if($category->icon)
                    <i class="{{ $category->icon }} fa-2x" 
                       style="color: {{ $category->color ?? '#2ecc71' }}"></i>
                  @else
                    <i class="fas fa-folder fa-2x text-muted"></i>
                  @endif
                </div>
                <div>
                  @if($category->color)
                    <div class="d-flex align-items-center">
                      <div class="color-preview me-2" 
                           style="background-color: {{ $category->color }}; width: 25px; height: 25px; border-radius: 4px; border: 1px solid #dee2e6;"></div>
                      <span class="text-muted">{{ $category->color }}</span>
                    </div>
                  @endif
                </div>
              </div>
            </div>

            <div class="col-md-6">
              <strong><i class="fas fa-graduation-cap text-muted"></i> Courses Count:</strong>
              <p class="mb-2">
                <span class="h4">{{ $coursesCount }}</span>
                <span class="text-muted">courses in this category</span>
              </p>
            </div>
          </div>

          <div class="mb-4">
            <strong><i class="fas fa-align-left text-muted"></i> Description:</strong>
            <div class="p-3 bg-light rounded mt-2">
              @if($category->description)
                {!! nl2br(e($category->description)) !!}
              @else
                <span class="text-muted">No description provided</span>
              @endif
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Statistics & Actions -->
      <div class="col-md-4">
        <div class="category-stats">
          <h5 class="text-success mb-3">
            <i class="fas fa-chart-bar"></i> Category Statistics
          </h5>
          
          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-success text-white rounded">
              <div>
                <i class="fas fa-book fa-2x"></i>
              </div>
              <div class="text-end">
                <h3 class="mb-0">{{ $coursesCount }}</h3>
                <small>Total Courses</small>
              </div>
            </div>
          </div>

          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-primary text-white rounded">
              <div>
                <i class="fas fa-check-circle fa-2x"></i>
              </div>
              <div class="text-end">
                <h3 class="mb-0">{{ $publishedCoursesCount }}</h3>
                <small>Published Courses</small>
              </div>
            </div>
          </div>

          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-warning text-white rounded">
              <div>
                <i class="fas fa-clock fa-2x"></i>
              </div>
              <div class="text-end">
                <h3 class="mb-0">{{ $draftCoursesCount }}</h3>
                <small>Draft Courses</small>
              </div>
            </div>
          </div>

          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-info text-white rounded">
              <div>
                <i class="fas fa-calendar-alt fa-2x"></i>
              </div>
              <div class="text-end">
                <h5 class="mb-0">{{ $category->created_at->format('M d, Y') }}</h5>
                <small>Created Date</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions mt-4">
          <h5 class="text-success mb-3">
            <i class="fas fa-bolt"></i> Quick Actions
          </h5>
          
          <div class="d-grid gap-2">
            <a href="{{ url('/courses/create?category_id=' . $category->id) }}" class="btn btn-outline-success">
              <i class="fas fa-plus-circle"></i> Add Course to Category
            </a>
            <a href="{{ url('/courses?category=' . $category->slug) }}" class="btn btn-outline-primary">
              <i class="fas fa-list"></i> View All Courses
            </a>
            <button class="btn btn-print mt-3" onclick="printCategoryDetails()">
              <i class="fas fa-print"></i> Print Category Details
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Courses in This Category -->
    @if($coursesCount > 0)
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="courses-section">
          <h5 class="text-success mb-3">
            <i class="fas fa-graduation-cap"></i> Courses in This Category
          </h5>
          
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Course</th>
                  <th>Instructor</th>
                  <th>Price</th>
                  <th>Level</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                @foreach($categoryCourses as $course)
                  <tr>
                    <td>
                      <strong>{{ $course->title }}</strong>
                      <br>
                      <small class="text-muted">{{ Str::limit($course->description, 40) }}</small>
                    </td>
                    <td>
                      @if($course->instructor)
                        {{ $course->instructor->name }}
                      @else
                        <span class="text-muted">No instructor</span>
                      @endif
                    </td>
                    <td>
                      @if($course->price == 0)
                        <span class="badge bg-success">Free</span>
                      @else
                        ${{ number_format($course->price, 2) }}
                      @endif
                    </td>
                    <td>
                      @if($course->level == 'beginner')
                        <span class="badge bg-info">Beginner</span>
                      @elseif($course->level == 'intermediate')
                        <span class="badge bg-warning">Intermediate</span>
                      @elseif($course->level == 'advanced')
                        <span class="badge bg-danger">Advanced</span>
                      @endif
                    </td>
                    <td>
                      @if($course->published)
                        <span class="badge badge-published">Published</span>
                      @else
                        <span class="badge badge-draft">Draft</span>
                      @endif
                    </td>
                    <td>
                      <a href="{{ url('/courses/' . $course->id) }}" class="btn btn-view btn-sm">
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
    </div>
    @else
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="empty-state text-center py-5 bg-light rounded">
          <i class="fas fa-graduation-cap fa-3x text-muted mb-3"></i>
          <h5 class="text-muted">No Courses in This Category</h5>
          <p class="text-muted mb-4">This category doesn't have any courses yet.</p>
          <a href="{{ url('/courses/create?category_id=' . $category->id) }}" class="btn btn-success">
            <i class="fas fa-plus-circle"></i> Create First Course
          </a>
        </div>
      </div>
    </div>
    @endif

    <!-- Additional Information -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="additional-info p-4 bg-light rounded">
          <h5 class="text-success mb-3">
            <i class="fas fa-info-circle"></i> Additional Information
          </h5>
          <div class="row">
            <div class="col-md-6">
              <strong>Created At:</strong>
              <p>{{ $category->created_at->format('F d, Y h:i A') }}</p>
            </div>
            <div class="col-md-6">
              <strong>Last Updated:</strong>
              <p>{{ $category->updated_at->format('F d, Y h:i A') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript Function for Print -->
<script>
function printCategoryDetails() {
    // Store original content
    const originalContent = document.body.innerHTML;
    
    // Create print-friendly content
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Category Details - {{ $category->name }}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    color: #333;
                }
                .print-header {
                    text-align: center;
                    border-bottom: 2px solid #2ecc71;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .print-header h1 {
                    color: #27ae60;
                    margin: 0;
                }
                .print-section {
                    margin-bottom: 20px;
                    padding-bottom: 20px;
                    border-bottom: 1px solid #eee;
                }
                .badge {
                    padding: 5px 10px;
                    border-radius: 15px;
                    font-size: 12px;
                    color: white;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 10px;
                }
                th, td {
                    padding: 10px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }
                th {
                    background-color: #f8f9fa;
                }
                .color-box {
                    width: 20px;
                    height: 20px;
                    display: inline-block;
                    border: 1px solid #ddd;
                    margin-right: 10px;
                    vertical-align: middle;
                }
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>Category Details Report</h1>
                <h2>{{ $category->name }}</h2>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
            </div>
            
            <div class="print-section">
                <h3>Basic Information</h3>
                <table>
                    <tr>
                        <td><strong>Category Name:</strong></td>
                        <td>{{ $category->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Slug:</strong></td>
                        <td>{{ $category->slug }}</td>
                    </tr>
                    <tr>
                        <td><strong>Icon:</strong></td>
                        <td>{{ $category->icon ?? 'Default icon' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Color:</strong></td>
                        <td>
                            @if($category->color)
                                <div class="color-box" style="background-color: {{ $category->color }};"></div>
                                {{ $category->color }}
                            @else
                                Default color
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Total Courses:</strong></td>
                        <td>{{ $coursesCount }}</td>
                    </tr>
                </table>
            </div>
            
            <div class="print-section">
                <h3>Description</h3>
                <p>{{ $category->description ?? 'No description provided' }}</p>
            </div>
            
            <div class="print-section">
                <h3>Courses in This Category ({{ $coursesCount }})</h3>
                @if($coursesCount > 0)
                <table>
                    <thead>
                        <tr>
                            <th>Course Title</th>
                            <th>Instructor</th>
                            <th>Price</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categoryCourses as $course)
                        <tr>
                            <td>{{ $course->title }}</td>
                            <td>{{ $course->instructor ? $course->instructor->name : 'No instructor' }}</td>
                            <td>{{ $course->price == 0 ? 'Free' : '$' . number_format($course->price, 2) }}</td>
                            <td>{{ $course->published ? 'Published' : 'Draft' }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                @else
                <p>No courses in this category.</p>
                @endif
            </div>
            
            <div class="print-section">
                <h3>Category Details</h3>
                <table>
                    <tr>
                        <td><strong>Created On:</strong></td>
                        <td>{{ $category->created_at->format('F d, Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Last Updated:</strong></td>
                        <td>{{ $category->updated_at->format('F d, Y h:i A') }}</td>
                    </tr>
                </table>
            </div>
            
            <div class="print-footer" style="margin-top: 50px; text-align: center; color: #666; font-size: 12px;">
                <p>--- End of Report ---</p>
            </div>
        </body>
        </html>
    `;
    
    // Open print window
    const printWindow = window.open('', '_blank');
    printWindow.document.write(printContent);
    printWindow.document.close();
    
    // Wait for content to load then print
    printWindow.onload = function() {
        printWindow.print();
        printWindow.close();
    };
    
    // Restore original content (fallback)
    setTimeout(() => {
        document.body.innerHTML = originalContent;
        window.location.reload();
    }, 1000);
}
</script>

<!-- CSS Styling -->
<style>
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

.card-body {
    padding: 30px;
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

/* Button Styling */
.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 10px 20px;
    transition: all 0.3s ease;
}

.btn-sm {
    padding: 6px 12px;
    font-size: 14px;
}

.btn-edit {
    background: linear-gradient(145deg, #fd7e14, #e8650f);
    color: white;
    border: none;
}

.btn-edit:hover {
    background: linear-gradient(145deg, #e8650f, #c0550d);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(253, 126, 20, 0.3);
}

.btn-print {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
    color: white;
    border: none;
}

.btn-print:hover {
    background: linear-gradient(145deg, #27ae60, #219653);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(46, 204, 113, 0.3);
}

.btn-view {
    background-color: #3498db;
    color: white;
    border: none;
}

.btn-view:hover {
    background-color: #2980b9;
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
}

/* Stats Cards */
.stats-card {
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
}

/* Category Details */
.category-details strong i {
    width: 20px;
    margin-right: 10px;
}

.bg-light {
    background-color: #f8f9fa !important;
}

.rounded {
    border-radius: 10px !important;
}

/* Quick Actions */
.quick-actions .btn-outline-success:hover {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
    color: white;
}

.quick-actions .btn-outline-primary:hover {
    background: linear-gradient(145deg, #3498db, #2980b9);
    color: white;
}

/* Text Colors */
.text-success {
    color: #2ecc71 !important;
}

.text-primary {
    color: #3498db !important;
}

.text-muted {
    color: #6c757d !important;
}

/* Additional Info */
.additional-info {
    border-left: 4px solid #2ecc71;
}

/* Courses Table */
.courses-section .table {
    background-color: white;
    border-radius: 10px;
    overflow: hidden;
}

.courses-section .table th {
    background-color: #f8f9fa;
    color: #495057;
    font-weight: 600;
    border-bottom: 2px solid #2ecc71;
}

.courses-section .table-hover tbody tr:hover {
    background-color: #e9f7ef;
}

/* Empty State */
.empty-state {
    border: 2px dashed #dee2e6;
}

/* Color Preview */
.color-preview {
    transition: all 0.3s ease;
}

.color-preview:hover {
    transform: scale(1.1);
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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

@endsection