@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-user-graduate"></i> Enrollment Details
    </h4>
  </div>
  <div class="card-body">
    
    <!-- Action Buttons -->
    <div class="d-flex justify-content-end mb-4">
      <a href="{{ url('enrollments') }}" class="btn btn-secondary me-2">
        <i class="fas fa-arrow-left"></i> Back to Enrollments
      </a>
      <a href="{{ url('enrollments/' . $enrollment->id . '/edit') }}" class="btn btn-edit me-2">
        <i class="fas fa-edit"></i> Edit Enrollment
      </a>
      <form method="POST" action="{{ url('enrollments/' . $enrollment->id) }}" accept-charset="UTF-8" style="display:inline">
        @method('DELETE')
        @csrf
        <button type="submit" class="btn btn-delete" title="Delete Enrollment" 
                onclick="return confirm('Are you sure you want to delete this enrollment?')">
          <i class="fas fa-trash"></i> Delete Enrollment
        </button>
      </form>
    </div>

    <!-- Enrollment Information -->
    <div class="row">
      <div class="col-md-6">
        <div class="card info-card mb-4">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user"></i> User Information</h5>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-3">
                <div class="user-avatar-circle">
                  <i class="fas fa-user-circle fa-3x" style="color: #3498db;"></i>
                </div>
              </div>
              <div class="col-9">
                <h4 class="mb-1">{{ $enrollment->user->name ?? 'N/A' }}</h4>
                <p class="text-muted mb-1">{{ $enrollment->user->email ?? 'N/A' }}</p>
                <span class="badge bg-info">User ID: {{ $enrollment->user_id }}</span>
              </div>
            </div>
            <div class="user-details">
              <p><strong><i class="fas fa-id-card"></i> User ID:</strong> {{ $enrollment->user_id }}</p>
              <p><strong><i class="fas fa-calendar-alt"></i> Registered:</strong> 
                {{ $enrollment->user->created_at->format('M d, Y') ?? 'N/A' }}</p>
              <a href="{{ url('users/' . $enrollment->user_id) }}" class="btn btn-sm btn-view">
                <i class="fas fa-external-link-alt"></i> View User Profile
              </a>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card info-card mb-4">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-book"></i> Course Information</h5>
          </div>
          <div class="card-body">
            <div class="row mb-3">
              <div class="col-3">
                <div class="course-icon-circle">
                  <i class="fas fa-book fa-3x" style="color: #2ecc71;"></i>
                </div>
              </div>
              <div class="col-9">
                <h4 class="mb-1">{{ $enrollment->course->title ?? 'N/A' }}</h4>
                <p class="text-muted mb-1">{{ $enrollment->course->description ? Str::limit($enrollment->course->description, 60) : 'No description' }}</p>
                <span class="badge bg-secondary">{{ $enrollment->course->code ?? 'N/A' }}</span>
              </div>
            </div>
            <div class="course-details">
              <p><strong><i class="fas fa-id-card"></i> Course ID:</strong> {{ $enrollment->course_id }}</p>
              <p><strong><i class="fas fa-chart-line"></i> Status:</strong> 
                <span class="badge {{ $enrollment->course->is_active ?? true ? 'bg-success' : 'bg-secondary' }}">
                  {{ $enrollment->course->is_active ?? true ? 'Active' : 'Inactive' }}
                </span>
              </p>
              <a href="{{ url('courses/' . $enrollment->course_id) }}" class="btn btn-sm btn-view">
                <i class="fas fa-external-link-alt"></i> View Course Details
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Enrollment Details -->
    <div class="row">
      <div class="col-md-12">
        <div class="card info-card">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-info-circle"></i> Enrollment Details</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="detail-item">
                  <h6><i class="fas fa-calendar-check"></i> Enrollment Date & Time</h6>
                  <div class="enrollment-date-display">
                    <div class="date-box bg-primary text-white">
                      <div class="date-day">{{ $enrollment->enrolled_at->format('d') }}</div>
                      <div class="date-month">{{ $enrollment->enrolled_at->format('M') }}</div>
                      <div class="date-year">{{ $enrollment->enrolled_at->format('Y') }}</div>
                    </div>
                    <div class="enrollment-time ms-3">
                      <h4 class="mb-0">{{ $enrollment->enrolled_at->format('h:i A') }}</h4>
                      <small class="text-muted">{{ $enrollment->enrolled_at->diffForHumans() }}</small>
                    </div>
                  </div>
                </div>
                
                <div class="detail-item mt-4">
                  <h6><i class="fas fa-history"></i> Duration</h6>
                  <p class="mb-2">
                    <strong>Enrolled for:</strong> 
                    {{ $enrollment->enrolled_at->diffInDays(now()) }} day{{ $enrollment->enrolled_at->diffInDays(now()) !== 1 ? 's' : '' }}
                  </p>
                  <p class="mb-0">
                    @php
                      $isRecent = $enrollment->enrolled_at->diffInDays(now()) <= 7;
                    @endphp
                    <strong>Status:</strong> 
                    @if($isRecent)
                      <span class="badge bg-success"><i class="fas fa-circle"></i> Recent Enrollment</span>
                    @else
                      <span class="badge bg-secondary"><i class="fas fa-clock"></i> Established Enrollment</span>
                    @endif
                  </p>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="detail-item">
                  <h6><i class="fas fa-clipboard-list"></i> Enrollment Status</h6>
                  <div class="status-indicator">
                    <div class="status-card {{ $enrollment->is_active ?? true ? 'status-active' : 'status-inactive' }}">
                      <i class="fas {{ $enrollment->is_active ?? true ? 'fa-check-circle' : 'fa-pause-circle' }} fa-2x"></i>
                      <div class="status-text">
                        <h4 class="mb-0">{{ $enrollment->is_active ?? true ? 'Active' : 'Inactive' }}</h4>
                        <small>{{ $enrollment->is_active ?? true ? 'User can access course content' : 'User access is restricted' }}</small>
                      </div>
                    </div>
                  </div>
                </div>
                
                <div class="detail-item mt-4">
                  <h6><i class="fas fa-sticky-note"></i> Notes</h6>
                  <div class="notes-box p-3 bg-light rounded">
                    @if($enrollment->notes)
                      {{ $enrollment->notes }}
                    @else
                      <span class="text-muted"><i>No notes provided</i></span>
                    @endif
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Timeline & Metadata -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card info-card">
          <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> Timeline & Metadata</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="timeline">
                  <div class="timeline-item">
                    <div class="timeline-icon">
                      <i class="fas fa-plus-circle text-success"></i>
                    </div>
                    <div class="timeline-content">
                      <h6>Enrollment Created</h6>
                      <p class="mb-1">{{ $enrollment->created_at->format('F d, Y \a\t h:i A') }}</p>
                      <small class="text-muted">{{ $enrollment->created_at->diffForHumans() }}</small>
                    </div>
                  </div>
                  <div class="timeline-item">
                    <div class="timeline-icon">
                      <i class="fas fa-edit text-warning"></i>
                    </div>
                    <div class="timeline-content">
                      <h6>Last Updated</h6>
                      <p class="mb-1">{{ $enrollment->updated_at->format('F d, Y \a\t h:i A') }}</p>
                      <small class="text-muted">{{ $enrollment->updated_at->diffForHumans() }}</small>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="metadata">
                  <h6><i class="fas fa-database"></i> System Information</h6>
                  <table class="table table-sm">
                    <tr>
                      <td><strong>Enrollment ID:</strong></td>
                      <td><code>{{ $enrollment->id }}</code></td>
                    </tr>
                    <tr>
                      <td><strong>Record Created:</strong></td>
                      <td>{{ $enrollment->created_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    <tr>
                      <td><strong>Last Updated:</strong></td>
                      <td>{{ $enrollment->updated_at->format('Y-m-d H:i:s') }}</td>
                    </tr>
                    @if($enrollment->deleted_at)
                    <tr>
                      <td><strong>Deleted At:</strong></td>
                      <td><span class="badge bg-danger">{{ $enrollment->deleted_at->format('Y-m-d H:i:s') }}</span></td>
                    </tr>
                    @endif
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Related Actions -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-cogs"></i> Quick Actions</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3 text-center">
                <a href="{{ url('enrollments/' . $enrollment->id . '/edit') }}" class="action-btn">
                  <div class="action-icon bg-warning">
                    <i class="fas fa-edit fa-2x"></i>
                  </div>
                  <h6 class="mt-2">Edit Enrollment</h6>
                  <small class="text-muted">Modify details</small>
                </a>
              </div>
              <div class="col-md-3 text-center">
                <a href="{{ url('users/' . $enrollment->user_id) }}" class="action-btn">
                  <div class="action-icon bg-primary">
                    <i class="fas fa-user fa-2x"></i>
                  </div>
                  <h6 class="mt-2">View User</h6>
                  <small class="text-muted">See user profile</small>
                </a>
              </div>
              <div class="col-md-3 text-center">
                <a href="{{ url('courses/' . $enrollment->course_id) }}" class="action-btn">
                  <div class="action-icon bg-success">
                    <i class="fas fa-book fa-2x"></i>
                  </div>
                  <h6 class="mt-2">View Course</h6>
                  <small class="text-muted">See course details</small>
                </a>
              </div>
              <div class="col-md-3 text-center">
                <a href="{{ url('enrollments/create?user_id=' . $enrollment->user_id . '&course_id=' . $enrollment->course_id) }}" class="action-btn">
                  <div class="action-icon bg-info">
                    <i class="fas fa-copy fa-2x"></i>
                  </div>
                  <h6 class="mt-2">Duplicate</h6>
                  <small class="text-muted">Create similar</small>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>

<style>
  .card {
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border-radius: 12px;
    overflow: hidden;
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .card-header {
    padding: 20px;
    border-bottom: none;
  }
  
  .card-header.bg-primary {
    background: linear-gradient(145deg, #3498db, #2980b9) !important;
  }
  
  .card-header.bg-success {
    background: linear-gradient(145deg, #2ecc71, #27ae60) !important;
  }
  
  .card-header.bg-info {
    background: linear-gradient(145deg, #17a2b8, #138496) !important;
  }
  
  .card-header.bg-secondary {
    background: linear-gradient(145deg, #6c757d, #495057) !important;
  }
  
  .card-title {
    margin: 0;
    font-weight: 600;
    font-size: 1.5rem;
  }
  
  .card-body {
    padding: 30px;
  }
  
  /* Button Styles */
  .btn {
    font-size: 14px;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  
  .btn-secondary {
    background: linear-gradient(145deg, #6c757d, #495057);
    color: white;
    border: none;
  }
  
  .btn-secondary:hover {
    background: linear-gradient(145deg, #495057, #343a40);
    color: white;
    transform: translateY(-2px);
  }
  
  .btn-edit {
    background: linear-gradient(145deg, #f39c12, #e67e22);
    color: white;
    border: none;
  }
  
  .btn-edit:hover {
    background: linear-gradient(145deg, #e67e22, #d35400);
    color: white;
    transform: translateY(-2px);
  }
  
  .btn-delete {
    background: linear-gradient(145deg, #dc3545, #c82333);
    color: white;
    border: none;
  }
  
  .btn-delete:hover {
    background: linear-gradient(145deg, #c82333, #a71e2a);
    color: white;
    transform: translateY(-2px);
  }
  
  .btn-view {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
    color: white;
    border: none;
  }
  
  .btn-view:hover {
    background: linear-gradient(145deg, #27ae60, #219653);
    color: white;
    transform: translateY(-2px);
  }
  
  .btn-sm {
    padding: 5px 10px;
    font-size: 12px;
  }
  
  /* Info Cards */
  .info-card {
    border: none;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    height: 100%;
    transition: transform 0.3s ease;
  }
  
  .info-card:hover {
    transform: translateY(-5px);
  }
  
  /* Avatar & Icon Circles */
  .user-avatar-circle, .course-icon-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: #f8f9fa;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 3px solid #e9ecef;
  }
  
  /* Enrollment Date Display */
  .enrollment-date-display {
    display: flex;
    align-items: center;
  }
  
  .date-box {
    width: 80px;
    height: 80px;
    border-radius: 10px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    background: linear-gradient(145deg, #3498db, #2980b9);
  }
  
  .date-day {
    font-size: 24px;
    font-weight: bold;
    line-height: 1;
  }
  
  .date-month {
    font-size: 14px;
    text-transform: uppercase;
    margin: 2px 0;
  }
  
  .date-year {
    font-size: 12px;
    opacity: 0.9;
  }
  
  /* Status Indicators */
  .status-indicator {
    margin-top: 10px;
  }
  
  .status-card {
    display: flex;
    align-items: center;
    padding: 15px;
    border-radius: 10px;
    background: #f8f9fa;
    border-left: 5px solid #ddd;
  }
  
  .status-active {
    border-left-color: #2ecc71;
  }
  
  .status-inactive {
    border-left-color: #6c757d;
  }
  
  .status-card i {
    margin-right: 15px;
  }
  
  .status-active i {
    color: #2ecc71;
  }
  
  .status-inactive i {
    color: #6c757d;
  }
  
  /* Notes Box */
  .notes-box {
    min-height: 80px;
    background: #f8f9fa;
    border-left: 4px solid #3498db;
  }
  
  /* Timeline */
  .timeline {
    position: relative;
    padding-left: 30px;
  }
  
  .timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #e9ecef;
  }
  
  .timeline-item {
    position: relative;
    margin-bottom: 20px;
  }
  
  .timeline-icon {
    position: absolute;
    left: -30px;
    top: 0;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
  }
  
  /* Metadata Table */
  .metadata table {
    margin-bottom: 0;
  }
  
  .metadata td {
    padding: 8px 0;
    border-bottom: 1px solid #e9ecef;
  }
  
  .metadata td:first-child {
    width: 40%;
  }
  
  /* Quick Actions */
  .action-btn {
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.3s ease;
  }
  
  .action-btn:hover {
    transform: translateY(-5px);
    color: inherit;
  }
  
  .action-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
  }
  
  .action-icon.bg-warning {
    background: linear-gradient(145deg, #f39c12, #e67e22);
  }
  
  .action-icon.bg-primary {
    background: linear-gradient(145deg, #3498db, #2980b9);
  }
  
  .action-icon.bg-success {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
  }
  
  .action-icon.bg-info {
    background: linear-gradient(145deg, #17a2b8, #138496);
  }
  
  /* Badge Styles */
  .badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
  }
  
  /* Detail Items */
  .detail-item h6 {
    color: #495057;
    margin-bottom: 15px;
    font-weight: 600;
  }
  
  .detail-item h6 i {
    margin-right: 8px;
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .enrollment-date-display {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .enrollment-time {
      margin-left: 0 !important;
      margin-top: 15px;
    }
    
    .user-avatar-circle, .course-icon-circle {
      width: 60px;
      height: 60px;
    }
    
    .user-avatar-circle i, .course-icon-circle i {
      font-size: 2rem !important;
    }
    
    .date-box {
      width: 70px;
      height: 70px;
    }
    
    .date-day {
      font-size: 20px;
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Confirm delete
    const deleteForm = document.querySelector('form[action*="enrollments/' + {{ $enrollment->id }} + '"]');
    if (deleteForm) {
      deleteForm.addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to delete this enrollment? This action cannot be undone.')) {
          e.preventDefault();
        }
      });
    }
    
    // Status card animation
    const statusCard = document.querySelector('.status-card');
    if (statusCard) {
      statusCard.addEventListener('mouseenter', function() {
        this.style.transform = 'scale(1.02)';
        this.style.transition = 'transform 0.3s ease';
      });
      
      statusCard.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
      });
    }
    
    // Date box hover effect
    const dateBox = document.querySelector('.date-box');
    if (dateBox) {
      dateBox.addEventListener('mouseenter', function() {
        this.style.transform = 'rotate(5deg)';
        this.style.transition = 'transform 0.3s ease';
      });
      
      dateBox.addEventListener('mouseleave', function() {
        this.style.transform = 'rotate(0deg)';
      });
    }
    
    // Copy enrollment ID to clipboard
    const enrollmentId = document.querySelector('code');
    if (enrollmentId) {
      enrollmentId.addEventListener('click', function() {
        const text = this.textContent;
        navigator.clipboard.writeText(text).then(() => {
          const originalText = this.textContent;
          this.textContent = 'Copied!';
          this.style.backgroundColor = '#2ecc71';
          this.style.color = 'white';
          this.style.padding = '5px 10px';
          this.style.borderRadius = '4px';
          
          setTimeout(() => {
            this.textContent = originalText;
            this.style.backgroundColor = '';
            this.style.color = '';
            this.style.padding = '';
            this.style.borderRadius = '';
          }, 2000);
        });
      });
      
      // Add tooltip
      enrollmentId.title = 'Click to copy Enrollment ID';
      enrollmentId.style.cursor = 'pointer';
    }
    
    // Quick actions animation
    const actionButtons = document.querySelectorAll('.action-btn');
    actionButtons.forEach(btn => {
      btn.addEventListener('mouseenter', function() {
        const icon = this.querySelector('.action-icon');
        if (icon) {
          icon.style.transform = 'rotate(15deg) scale(1.1)';
          icon.style.transition = 'transform 0.3s ease';
        }
      });
      
      btn.addEventListener('mouseleave', function() {
        const icon = this.querySelector('.action-icon');
        if (icon) {
          icon.style.transform = 'rotate(0deg) scale(1)';
        }
      });
    });
    
    // Calculate and display exact duration
    const enrolledAt = new Date('{{ $enrollment->enrolled_at }}');
    const now = new Date();
    const diffTime = Math.abs(now - enrolledAt);
    const diffDays = Math.floor(diffTime / (1000 * 60 * 60 * 24));
    const diffHours = Math.floor((diffTime % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const diffMinutes = Math.floor((diffTime % (1000 * 60 * 60)) / (1000 * 60));
    
    // Update duration display if it exists
    const durationElement = document.querySelector('.detail-item .mb-2 p');
    if (durationElement) {
      let durationText = `<strong>Enrolled for:</strong> ${diffDays} day${diffDays !== 1 ? 's' : ''}`;
      if (diffHours > 0) {
        durationText += `, ${diffHours} hour${diffHours !== 1 ? 's' : ''}`;
      }
      if (diffMinutes > 0 && diffDays === 0) {
        durationText += `, ${diffMinutes} minute${diffMinutes !== 1 ? 's' : ''}`;
      }
      durationElement.innerHTML = durationText;
    }
    
    // Auto-refresh status if enrollment is recent
    const isRecent = {{ $enrollment->enrolled_at->diffInDays(now()) <= 7 ? 'true' : 'false' }};
    if (isRecent) {
      // Update the "X minutes ago" text every minute
      setInterval(() => {
        const timeElements = document.querySelectorAll('.text-muted');
        timeElements.forEach(element => {
          if (element.textContent.includes('ago') || element.textContent.includes('minute') || element.textContent.includes('hour')) {
            // This would ideally update with actual time calculations
            // For simplicity, we'll just update the duration
            if (element.textContent.includes('Enrolled for:')) {
              // Recalculate and update
            }
          }
        });
      }, 60000); // Update every minute
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
</script>

@stop