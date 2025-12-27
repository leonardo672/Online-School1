@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="fas fa-graduation-cap"></i> Course Details: {{ $course->title }}
      </h4>
      <div>
        <span class="badge {{ $course->published ? 'badge-published' : 'badge-draft' }} me-2">
          {{ $course->published ? 'Published' : 'Draft' }}
        </span>
        <a href="{{ url('/courses/' . $course->id . '/edit') }}" class="btn btn-edit btn-sm">
          <i class="fas fa-edit"></i> Edit
        </a>
      </div>
    </div>
  </div>
  
  <div class="card-body">
    <div class="row">
      <!-- Left Column: Course Info -->
      <div class="col-md-8">
        <div class="course-details mb-4">
          <h5 class="text-primary mb-3">Course Information</h5>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <strong><i class="fas fa-book text-muted"></i> Title:</strong>
              <p class="mb-2">{{ $course->title }}</p>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-tag text-muted"></i> Slug:</strong>
              <p class="mb-2"><code>{{ $course->slug }}</code></p>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <strong><i class="fas fa-layer-group text-muted"></i> Category:</strong>
              <p class="mb-2">
                @if($course->category)
                  <span class="badge bg-secondary">{{ $course->category->name }}</span>
                @else
                  <span class="badge bg-danger">No Category</span>
                @endif
              </p>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-user-tie text-muted"></i> Instructor:</strong>
              <p class="mb-2">
                @if($course->instructor)
                  {{ $course->instructor->name }} ({{ $course->instructor->email }})
                @else
                  <span class="text-danger">No Instructor Assigned</span>
                @endif
              </p>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <strong><i class="fas fa-money-bill-wave text-muted"></i> Price:</strong>
              <p class="mb-2">
                @if($course->price == 0)
                  <span class="badge bg-success">Free</span>
                @else
                  <span class="h5 text-success">${{ number_format($course->price, 2) }}</span>
                @endif
              </p>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-chart-line text-muted"></i> Level:</strong>
              <p class="mb-2">
                @if($course->level == 'beginner')
                  <span class="badge bg-info">Beginner</span>
                @elseif($course->level == 'intermediate')
                  <span class="badge bg-warning">Intermediate</span>
                @elseif($course->level == 'advanced')
                  <span class="badge bg-danger">Advanced</span>
                @endif
              </p>
            </div>
          </div>

          <div class="mb-4">
            <strong><i class="fas fa-align-left text-muted"></i> Description:</strong>
            <div class="p-3 bg-light rounded mt-2">
              {!! nl2br(e($course->description)) !!}
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Statistics & Actions -->
      <div class="col-md-4">
        <div class="course-stats">
          <h5 class="text-primary mb-3">Course Statistics</h5>
          
          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-primary text-white rounded">
              <div>
                <i class="fas fa-users fa-2x"></i>
              </div>
              <div class="text-end">
                <h3 class="mb-0">0</h3>
                <small>Total Students</small>
              </div>
            </div>
          </div>

          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-success text-white rounded">
              <div>
                <i class="fas fa-book-open fa-2x"></i>
              </div>
              <div class="text-end">
                <h3 class="mb-0">0</h3>
                <small>Total Lessons</small>
              </div>
            </div>
          </div>

          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-warning text-white rounded">
              <div>
                <i class="fas fa-star fa-2x"></i>
              </div>
              <div class="text-end">
                <h3 class="mb-0">0.0</h3>
                <small>Average Rating</small>
              </div>
            </div>
          </div>

          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-info text-white rounded">
              <div>
                <i class="fas fa-calendar-alt fa-2x"></i>
              </div>
              <div class="text-end">
                <h5 class="mb-0">{{ $course->created_at->format('M d, Y') }}</h5>
                <small>Created Date</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions mt-4">
          <h5 class="text-primary mb-3">Quick Actions</h5>
          
          <div class="d-grid gap-2">
            <a href="{{ url('/lessons?course_id=' . $course->id) }}" class="btn btn-outline-primary">
              <i class="fas fa-plus-circle"></i> Add Lessons
            </a>
            <a href="{{ url('/enrollments/create?course_id=' . $course->id) }}" class="btn btn-outline-success">
              <i class="fas fa-user-plus"></i> Enroll Students
            </a>
            <a href="{{ url('/certificates/create?course_id=' . $course->id) }}" class="btn btn-outline-info">
              <i class="fas fa-certificate"></i> Issue Certificate
            </a>
            <button class="btn btn-print mt-3" onclick="printCourseDetails()">
              <i class="fas fa-print"></i> Print Course Details
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Additional Information -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="additional-info p-4 bg-light rounded">
          <h5 class="text-primary mb-3">Additional Information</h5>
          <div class="row">
            <div class="col-md-6">
              <strong>Created At:</strong>
              <p>{{ $course->created_at->format('F d, Y h:i A') }}</p>
            </div>
            <div class="col-md-6">
              <strong>Last Updated:</strong>
              <p>{{ $course->updated_at->format('F d, Y h:i A') }}</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript Function for Print -->
<script>
function printCourseDetails() {
    // Store original content
    const originalContent = document.body.innerHTML;
    
    // Get the card content
    const cardContent = document.querySelector('.card').outerHTML;
    
    // Create print-friendly content
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>Course Details - {{ $course->title }}</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    padding: 20px;
                    color: #333;
                }
                .print-header {
                    text-align: center;
                    border-bottom: 2px solid #3498db;
                    padding-bottom: 20px;
                    margin-bottom: 30px;
                }
                .print-header h1 {
                    color: #2c3e50;
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
                .badge-published { background-color: #2ecc71; }
                .badge-draft { background-color: #f39c12; }
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
            </style>
        </head>
        <body>
            <div class="print-header">
                <h1>Course Details Report</h1>
                <h2>{{ $course->title }}</h2>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
            </div>
            
            <div class="print-section">
                <h3>Basic Information</h3>
                <table>
                    <tr>
                        <td><strong>Course Title:</strong></td>
                        <td>{{ $course->title }}</td>
                    </tr>
                    <tr>
                        <td><strong>Status:</strong></td>
                        <td><span class="badge {{ $course->published ? 'badge-published' : 'badge-draft' }}">{{ $course->published ? 'Published' : 'Draft' }}</span></td>
                    </tr>
                    <tr>
                        <td><strong>Category:</strong></td>
                        <td>{{ $course->category ? $course->category->name : 'No Category' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Instructor:</strong></td>
                        <td>{{ $course->instructor ? $course->instructor->name : 'No Instructor' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Price:</strong></td>
                        <td>{{ $course->price == 0 ? 'Free' : '$' + {{ $course->price }} }}</td>
                    </tr>
                    <tr>
                        <td><strong>Level:</strong></td>
                        <td>{{ ucfirst($course->level) }}</td>
                    </tr>
                </table>
            </div>
            
            <div class="print-section">
                <h3>Description</h3>
                <p>{{ $course->description }}</p>
            </div>
            
            <div class="print-section">
                <h3>Course Details</h3>
                <table>
                    <tr>
                        <td><strong>Created On:</strong></td>
                        <td>{{ $course->created_at->format('F d, Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Last Updated:</strong></td>
                        <td>{{ $course->updated_at->format('F d, Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Course Slug:</strong></td>
                        <td>{{ $course->slug }}</td>
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
    background: linear-gradient(145deg, #2c3e50, #3498db);
    color: white;
    padding: 20px 30px;
    border-bottom: none;
}

.card-body {
    padding: 30px;
}

/* Badge Styling */
.badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 14px;
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
    background: linear-gradient(145deg, #3498db, #2980b9);
    color: white;
    border: none;
}

.btn-print:hover {
    background: linear-gradient(145deg, #2980b9, #1c5a7a);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(52, 152, 219, 0.3);
}

/* Stats Cards */
.stats-card {
    transition: all 0.3s ease;
}

.stats-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
}

/* Course Details */
.course-details strong i {
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
.quick-actions .btn-outline-primary:hover {
    background: linear-gradient(145deg, #3498db, #2980b9);
    color: white;
}

.quick-actions .btn-outline-success:hover {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
    color: white;
}

.quick-actions .btn-outline-info:hover {
    background: linear-gradient(145deg, #17a2b8, #138496);
    color: white;
}

/* Text Colors */
.text-primary {
    color: #3498db !important;
}

.text-success {
    color: #2ecc71 !important;
}

.text-muted {
    color: #6c757d !important;
}

/* Additional Info */
.additional-info {
    border-left: 4px solid #3498db;
}
</style>

@endsection