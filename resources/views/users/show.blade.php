@extends('layout')
@section('content')

@php
    // Calculate user statistics
    $coursesCreated = 0;
    $coursesEnrolled = 0;
    $totalPayments = 0;
    
    // Check if user is instructor and count their courses
    if ($user->isInstructor()) {
        $coursesCreated = method_exists($user, 'instructorCourses') ? $user->instructorCourses()->count() : 0;
    }
    
    // Check if user is student and count enrollments
    if ($user->isStudent()) {
        $coursesEnrolled = method_exists($user, 'enrollments') ? $user->enrollments()->count() : 0;
        // You could also calculate total payments here if you have that relationship
    }
@endphp

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="mb-0">
        <i class="fas fa-user-circle"></i> User Profile: {{ $user->name }}
      </h4>
      <div>
        <a href="{{ url('/users/' . $user->id . '/edit') }}" class="btn btn-edit">
          <i class="fas fa-edit"></i> Edit
        </a>
      </div>
    </div>
  </div>
  
  <div class="card-body">
    <div class="row">
      <!-- Left Column: User Info -->
      <div class="col-md-8">
        <div class="user-details mb-4">
          <h5 class="text-primary mb-3">
            <i class="fas fa-id-card"></i> Personal Information
          </h5>
          
          <div class="row mb-3">
            <div class="col-md-6">
              <strong><i class="fas fa-user text-muted"></i> Full Name:</strong>
              <p class="mb-2">
                <span class="h5">{{ $user->name }}</span>
              </p>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-envelope text-muted"></i> Email Address:</strong>
              <p class="mb-2">
                {{ $user->email }}
                @if($user->email_verified_at)
                  <span class="badge bg-success ms-2">Verified</span>
                @else
                  <span class="badge bg-warning ms-2">Unverified</span>
                @endif
              </p>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-6">
              <strong><i class="fas fa-user-tag text-muted"></i> Account Role:</strong>
              <p class="mb-2">
                @if($user->isAdmin())
                  <span class="badge bg-danger">Administrator</span>
                @elseif($user->isInstructor())
                  <span class="badge bg-warning">Instructor</span>
                @else
                  <span class="badge bg-secondary">Student</span>
                @endif
              </p>
            </div>
            <div class="col-md-6">
              <strong><i class="fas fa-calendar-alt text-muted"></i> Member Since:</strong>
              <p class="mb-2">{{ $user->created_at->format('F d, Y h:i A') }}</p>
            </div>
          </div>

          <div class="mb-4">
            <strong><i class="fas fa-user-shield text-muted"></i> Account Permissions:</strong>
            <div class="p-3 bg-light rounded mt-2">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" disabled 
                           {{ $user->isAdmin() || $user->isInstructor() ? 'checked' : '' }}>
                    <label class="form-check-label">
                      Can Create Courses
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" disabled 
                           {{ $user->isAdmin() ? 'checked' : '' }}>
                    <label class="form-check-label">
                      Full System Access
                    </label>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" disabled 
                           {{ $user->isAdmin() || $user->isInstructor() || $user->isStudent() ? 'checked' : '' }}>
                    <label class="form-check-label">
                      Can View Courses
                    </label>
                  </div>
                  <div class="form-check">
                    <input class="form-check-input" type="checkbox" disabled 
                           {{ $user->isAdmin() || $user->isInstructor() ? 'checked' : '' }}>
                    <label class="form-check-label">
                      Manage Content
                    </label>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Right Column: Statistics & Actions -->
      <div class="col-md-4">
        <div class="user-stats">
          <h5 class="text-primary mb-3">
            <i class="fas fa-chart-bar"></i> User Statistics
          </h5>
          
          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-primary text-white rounded">
              <div>
                <i class="fas fa-user-clock fa-2x"></i>
              </div>
              <div class="text-end">
                <h5 class="mb-0">{{ $user->created_at->diffForHumans() }}</h5>
                <small>Member Duration</small>
              </div>
            </div>
          </div>

          @if($user->isInstructor())
          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-success text-white rounded">
              <div>
                <i class="fas fa-graduation-cap fa-2x"></i>
              </div>
              <div class="text-end">
                <h3 class="mb-0">{{ $coursesCreated }}</h3>
                <small>Courses Created</small>
              </div>
            </div>
          </div>
          @endif

          @if($user->isStudent())
          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-info text-white rounded">
              <div>
                <i class="fas fa-book-open fa-2x"></i>
              </div>
              <div class="text-end">
                <h3 class="mb-0">{{ $coursesEnrolled }}</h3>
                <small>Courses Enrolled</small>
              </div>
            </div>
          </div>
          @endif

          <div class="stats-card mb-3">
            <div class="d-flex justify-content-between align-items-center p-3 bg-warning text-white rounded">
              <div>
                <i class="fas fa-calendar-check fa-2x"></i>
              </div>
              <div class="text-end">
                <h5 class="mb-0">{{ $user->updated_at->format('M d, Y') }}</h5>
                <small>Last Updated</small>
              </div>
            </div>
          </div>
        </div>

        <!-- Quick Actions -->
        <div class="quick-actions mt-4">
          <h5 class="text-primary mb-3">
            <i class="fas fa-bolt"></i> Quick Actions
          </h5>
          
          <div class="d-grid gap-2">
            @if($user->isInstructor())
              <a href="{{ url('/courses?instructor=' . $user->id) }}" class="btn btn-outline-primary">
                <i class="fas fa-graduation-cap"></i> View Instructor Courses
              </a>
            @endif
            
            @if($user->isStudent())
              <a href="{{ url('/enrollments?user=' . $user->id) }}" class="btn btn-outline-info">
                <i class="fas fa-book-open"></i> View Enrollments
              </a>
            @endif
            
            <button class="btn btn-print mt-3" onclick="printUserDetails()">
              <i class="fas fa-print"></i> Print User Profile
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- User Role Specific Information -->
    @if($user->isInstructor())
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="role-section p-4 bg-light rounded">
          <h5 class="text-warning mb-3">
            <i class="fas fa-chalkboard-teacher"></i> Instructor Information
          </h5>
          <div class="row">
            <div class="col-md-6">
              <strong>Total Courses Created:</strong>
              <p>{{ $coursesCreated }} courses</p>
            </div>
            <div class="col-md-6">
              <strong>Instructor Since:</strong>
              <p>{{ $user->created_at->format('F d, Y') }}</p>
            </div>
          </div>
          <div class="text-center">
            <a href="{{ url('/courses/create?instructor_id=' . $user->id) }}" class="btn btn-warning me-2">
              <i class="fas fa-plus-circle"></i> Add New Course
            </a>
            <a href="{{ url('/courses?instructor=' . $user->id) }}" class="btn btn-outline-warning">
              <i class="fas fa-list"></i> View All Courses
            </a>
          </div>
        </div>
      </div>
    </div>
    @endif

    @if($user->isStudent())
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="role-section p-4 bg-light rounded">
          <h5 class="text-info mb-3">
            <i class="fas fa-user-graduate"></i> Student Information
          </h5>
          <div class="row">
            <div class="col-md-6">
              <strong>Courses Enrolled:</strong>
              <p>{{ $coursesEnrolled }} courses</p>
            </div>
            <div class="col-md-6">
              <strong>Student Since:</strong>
              <p>{{ $user->created_at->format('F d, Y') }}</p>
            </div>
          </div>
          <div class="text-center">
            <a href="{{ url('/enrollments/create?user_id=' . $user->id) }}" class="btn btn-info me-2">
              <i class="fas fa-user-plus"></i> Enroll in Course
            </a>
            <a href="{{ url('/enrollments?user=' . $user->id) }}" class="btn btn-outline-info">
              <i class="fas fa-list"></i> View Enrollments
            </a>
          </div>
        </div>
      </div>
    </div>
    @endif

    <!-- Additional Information -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="additional-info p-4 bg-light rounded">
          <h5 class="text-primary mb-3">
            <i class="fas fa-info-circle"></i> Additional Information
          </h5>
          <div class="row">
            <div class="col-md-6">
              <strong>Account Created:</strong>
              <p>{{ $user->created_at->format('F d, Y h:i A') }}</p>
            </div>
            <div class="col-md-6">
              <strong>Last Profile Update:</strong>
              <p>{{ $user->updated_at->format('F d, Y h:i A') }}</p>
            </div>
          </div>
          <div class="row mt-3">
            <div class="col-md-6">
              <strong>User ID:</strong>
              <p><code>{{ $user->id }}</code></p>
            </div>
            <div class="col-md-6">
              <strong>Email Verification:</strong>
              <p>
                @if($user->email_verified_at)
                  Verified on {{ $user->email_verified_at->format('F d, Y h:i A') }}
                @else
                  <span class="text-warning">Email not verified</span>
                @endif
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript Function for Print -->
<script>
function printUserDetails() {
    // Store original content
    const originalContent = document.body.innerHTML;
    
    // Create print-friendly content
    const printContent = `
        <!DOCTYPE html>
        <html>
        <head>
            <title>User Profile - {{ $user->name }}</title>
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
                    color: #2980b9;
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
                .badge-admin { background-color: #dc3545; }
                .badge-instructor { background-color: #fd7e14; }
                .badge-student { background-color: #6c757d; }
                .badge-verified { background-color: #28a745; }
                .badge-unverified { background-color: #ffc107; color: #000; }
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
                <h1>User Profile Report</h1>
                <h2>{{ $user->name }}</h2>
                <p>Generated on: ${new Date().toLocaleDateString()}</p>
            </div>
            
            <div class="print-section">
                <h3>Basic Information</h3>
                <table>
                    <tr>
                        <td><strong>Full Name:</strong></td>
                        <td>{{ $user->name }}</td>
                    </tr>
                    <tr>
                        <td><strong>Email Address:</strong></td>
                        <td>{{ $user->email }}</td>
                    </tr>
                    <tr>
                        <td><strong>Account Role:</strong></td>
                        <td>
                            @if($user->isAdmin())
                                <span class="badge badge-admin">Administrator</span>
                            @elseif($user->isInstructor())
                                <span class="badge badge-instructor">Instructor</span>
                            @else
                                <span class="badge badge-student">Student</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td><strong>Email Status:</strong></td>
                        <td>
                            @if($user->email_verified_at)
                                <span class="badge badge-verified">Verified</span>
                            @else
                                <span class="badge badge-unverified">Unverified</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>
            
            <div class="print-section">
                <h3>Account Details</h3>
                <table>
                    <tr>
                        <td><strong>User ID:</strong></td>
                        <td>{{ $user->id }}</td>
                    </tr>
                    <tr>
                        <td><strong>Member Since:</strong></td>
                        <td>{{ $user->created_at->format('F d, Y h:i A') }}</td>
                    </tr>
                    <tr>
                        <td><strong>Last Updated:</strong></td>
                        <td>{{ $user->updated_at->format('F d, Y h:i A') }}</td>
                    </tr>
                </table>
            </div>
            
            <div class="print-section">
                <h3>User Statistics</h3>
                <table>
                    <tr>
                        <td><strong>Member Duration:</strong></td>
                        <td>{{ $user->created_at->diffForHumans() }}</td>
                    </tr>
                    @if($user->isInstructor())
                    <tr>
                        <td><strong>Courses Created:</strong></td>
                        <td>{{ $coursesCreated }}</td>
                    </tr>
                    @endif
                    @if($user->isStudent())
                    <tr>
                        <td><strong>Courses Enrolled:</strong></td>
                        <td>{{ $coursesEnrolled }}</td>
                    </tr>
                    @endif
                </table>
            </div>
            
            <div class="print-section">
                <h3>Account Permissions</h3>
                <table>
                    <tr>
                        <td><strong>Can Create Courses:</strong></td>
                        <td>{{ $user->isAdmin() || $user->isInstructor() ? 'Yes' : 'No' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Full System Access:</strong></td>
                        <td>{{ $user->isAdmin() ? 'Yes' : 'No' }}</td>
                    </tr>
                    <tr>
                        <td><strong>Can View Courses:</strong></td>
                        <td>Yes</td>
                    </tr>
                    <tr>
                        <td><strong>Can Manage Content:</strong></td>
                        <td>{{ $user->isAdmin() || $user->isInstructor() ? 'Yes' : 'No' }}</td>
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
    background: linear-gradient(145deg, #3498db, #2980b9);
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

/* Button Styling */
.btn {
    border-radius: 8px;
    font-weight: 500;
    padding: 10px 20px;
    transition: all 0.3s ease;
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

/* User Details */
.user-details strong i {
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

.quick-actions .btn-outline-info:hover {
    background: linear-gradient(145deg, #17a2b8, #138496);
    color: white;
}

/* Text Colors */
.text-primary {
    color: #3498db !important;
}

.text-warning {
    color: #fd7e14 !important;
}

.text-info {
    color: #17a2b8 !important;
}

.text-muted {
    color: #6c757d !important;
}

/* Additional Info */
.additional-info {
    border-left: 4px solid #3498db;
}

/* Role Sections */
.role-section {
    border-left: 4px solid;
}

.role-section.bg-light[style*="instructor"] {
    border-left-color: #fd7e14;
}

.role-section.bg-light[style*="student"] {
    border-left-color: #17a2b8;
}

/* Form Check */
.form-check-input:disabled {
    background-color: #e9ecef;
}

.form-check-input:checked:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
}
</style>

@endsection