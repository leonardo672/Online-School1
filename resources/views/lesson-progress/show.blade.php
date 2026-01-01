@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-chart-line"></i> Progress Record Details
    </h4>
  </div>
  <div class="card-body">
    
    <!-- Action Buttons -->
    <div class="d-flex justify-content-end mb-4">
      <a href="{{ url('lesson-progress') }}" class="btn btn-secondary me-2">
        <i class="fas fa-arrow-left"></i> Back to Progress
      </a>
      <a href="{{ url('lesson-progress/' . $progress->id . '/edit') }}" class="btn btn-edit me-2">
        <i class="fas fa-edit"></i> Edit Record
      </a>
      <form method="POST" action="{{ url('lesson-progress/' . $progress->id) }}" accept-charset="UTF-8" style="display:inline">
        @method('DELETE')
        @csrf
        <button type="submit" class="btn btn-delete" title="Delete Progress Record" 
                onclick="return confirm('Are you sure you want to delete this progress record?')">
          <i class="fas fa-trash"></i> Delete Record
        </button>
      </form>
    </div>

    <!-- Progress Overview -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card status-card">
          <div class="card-body text-center">
            <div class="status-icon mb-3">
              @if($progress->completed)
                <i class="fas fa-check-circle fa-4x text-success"></i>
              @else
                <i class="fas fa-clock fa-4x text-warning"></i>
              @endif
            </div>
            <h4 class="mb-2">
              @if($progress->completed)
                <span class="badge bg-success">Completed</span>
              @else
                <span class="badge bg-warning">In Progress</span>
              @endif
            </h4>
            <p class="text-muted mb-0">
              @if($progress->completed_at)
                Completed on: {{ $progress->completed_at->format('M d, Y') }}
              @else
                Started on: {{ $progress->created_at->format('M d, Y') }}
              @endif
            </p>
          </div>
        </div>
      </div>
      
      <div class="col-md-8">
        <div class="card info-card">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="info-item">
                  <h6><i class="fas fa-user"></i> User Information</h6>
                  <div class="user-details mt-3">
                    <div class="d-flex align-items-center">
                      <div class="user-avatar me-3">
                        <i class="fas fa-user-circle fa-3x text-primary"></i>
                      </div>
                      <div>
                        <h4 class="mb-1">{{ $progress->user->name ?? 'N/A' }}</h4>
                        <p class="text-muted mb-1">{{ $progress->user->email ?? 'N/A' }}</p>
                        <p class="mb-0">
                          <small class="text-muted">User ID: {{ $progress->user_id }}</small>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="info-item">
                  <h6><i class="fas fa-book"></i> Lesson Information</h6>
                  <div class="lesson-details mt-3">
                    <div class="d-flex align-items-center">
                      <div class="lesson-icon me-3">
                        <i class="fas fa-book fa-3x text-success"></i>
                      </div>
                      <div>
                        <h4 class="mb-1">{{ $progress->lesson->title ?? 'N/A' }}</h4>
                        <p class="text-muted mb-1">
                          <i class="fas fa-graduation-cap"></i> {{ $progress->lesson->course->title ?? 'No Course' }}
                        </p>
                        <p class="mb-0">
                          <small class="text-muted">
                            Position: {{ $progress->lesson->position ?? 'N/A' }}
                            <span class="mx-1">•</span>
                            ID: {{ $progress->lesson_id }}
                          </small>
                        </p>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Detailed Timeline -->
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card timeline-card">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> Progress Timeline</h5>
          </div>
          <div class="card-body">
            <div class="timeline">
              <div class="timeline-item completed">
                <div class="timeline-icon">
                  <i class="fas fa-plus-circle"></i>
                </div>
                <div class="timeline-content">
                  <h6>Progress Record Created</h6>
                  <p class="mb-1">{{ $progress->created_at->format('F d, Y \a\t h:i A') }}</p>
                  <small class="text-muted">{{ $progress->created_at->diffForHumans() }}</small>
                </div>
              </div>
              
              @if($progress->completed_at)
              <div class="timeline-item completed">
                <div class="timeline-icon">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="timeline-content">
                  <h6>Lesson Completed</h6>
                  <p class="mb-1">{{ $progress->completed_at->format('F d, Y \a\t h:i A') }}</p>
                  <small class="text-muted">{{ $progress->completed_at->diffForHumans() }}</small>
                  <div class="completion-duration mt-2">
                    <span class="badge bg-light text-dark">
                      <i class="fas fa-clock"></i> 
                      Completed {{ $progress->created_at->diffInDays($progress->completed_at) }} days after starting
                    </span>
                  </div>
                </div>
              </div>
              @endif
              
              <div class="timeline-item">
                <div class="timeline-icon">
                  <i class="fas fa-edit"></i>
                </div>
                <div class="timeline-content">
                  <h6>Last Updated</h6>
                  <p class="mb-1">{{ $progress->updated_at->format('F d, Y \a\t h:i A') }}</p>
                  <small class="text-muted">{{ $progress->updated_at->diffForHumans() }}</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress Statistics -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card stats-card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user-chart"></i> User Progress Statistics</h5>
          </div>
          <div class="card-body">
            <div id="userProgressStats" class="text-center py-3">
              <div class="spinner-border spinner-border-sm text-primary"></div> Loading user statistics...
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card stats-card">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-book-chart"></i> Lesson Progress Statistics</h5>
          </div>
          <div class="card-body">
            <div id="lessonProgressStats" class="text-center py-3">
              <div class="spinner-border spinner-border-sm text-primary"></div> Loading lesson statistics...
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Progress Visualization -->
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Progress Visualization</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="progress-visualization">
                  <h6>User Overall Progress</h6>
                  <div class="progress-container">
                    <div class="progress-label">
                      <span>Completion Rate</span>
                      <span id="userCompletionRate">0%</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar" id="userProgressBar" role="progressbar" style="width: 0%"></div>
                    </div>
                  </div>
                  <div class="stats-grid mt-3">
                    <div class="stat-item">
                      <div class="stat-number" id="userCompleted">0</div>
                      <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-number" id="userInProgress">0</div>
                      <div class="stat-label">In Progress</div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-number" id="userTotal">0</div>
                      <div class="stat-label">Total Lessons</div>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="progress-visualization">
                  <h6>Lesson Completion Statistics</h6>
                  <div class="progress-container">
                    <div class="progress-label">
                      <span>Completion Rate</span>
                      <span id="lessonCompletionRate">0%</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar" id="lessonProgressBar" role="progressbar" style="width: 0%"></div>
                    </div>
                  </div>
                  <div class="stats-grid mt-3">
                    <div class="stat-item">
                      <div class="stat-number" id="lessonCompleted">0</div>
                      <div class="stat-label">Completed</div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-number" id="lessonInProgress">0</div>
                      <div class="stat-label">In Progress</div>
                    </div>
                    <div class="stat-item">
                      <div class="stat-number" id="lessonTotal">0</div>
                      <div class="stat-label">Total Users</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Notes Section -->
    @if($progress->notes)
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Notes</h5>
          </div>
          <div class="card-body">
            <div class="notes-content p-3 bg-white rounded border">
              {{ $progress->notes }}
            </div>
            <div class="mt-3">
              <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Notes are visible to administrators only
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>
    @endif

    <!-- Related Information -->
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-warning text-white">
            <h5 class="mb-0"><i class="fas fa-link"></i> Related Information</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-4 text-center">
                <a href="{{ url('users/' . $progress->user_id) }}" class="related-link">
                  <div class="related-icon bg-primary">
                    <i class="fas fa-user fa-2x"></i>
                  </div>
                  <h6 class="mt-2">View User Profile</h6>
                  <small class="text-muted">See full user details</small>
                </a>
              </div>
              
              <div class="col-md-4 text-center">
                <a href="{{ url('lessons/' . $progress->lesson_id) }}" class="related-link">
                  <div class="related-icon bg-success">
                    <i class="fas fa-book fa-2x"></i>
                  </div>
                  <h6 class="mt-2">View Lesson</h6>
                  <small class="text-muted">See lesson content</small>
                </a>
              </div>
              
              <div class="col-md-4 text-center">
                @if($progress->lesson->course_id ?? false)
                <a href="{{ url('courses/' . $progress->lesson->course_id) }}" class="related-link">
                  <div class="related-icon bg-info">
                    <i class="fas fa-graduation-cap fa-2x"></i>
                  </div>
                  <h6 class="mt-2">View Course</h6>
                  <small class="text-muted">See course details</small>
                </a>
                @else
                <div class="related-link disabled">
                  <div class="related-icon bg-secondary">
                    <i class="fas fa-graduation-cap fa-2x"></i>
                  </div>
                  <h6 class="mt-2">No Course</h6>
                  <small class="text-muted">Lesson not in a course</small>
                </div>
                @endif
              </div>
            </div>
            
            <!-- Additional Actions -->
            <div class="row mt-4">
              <div class="col-md-6">
                <div class="additional-action">
                  <h6><i class="fas fa-exchange-alt"></i> Toggle Status</h6>
                  <p class="small text-muted mb-2">
                    Mark this lesson as {{ $progress->completed ? 'incomplete' : 'complete' }}
                  </p>
                  <button class="btn btn-sm btn-toggle" onclick="toggleProgressStatus()">
                    <i class="fas {{ $progress->completed ? 'fa-undo' : 'fa-check' }}"></i>
                    Mark as {{ $progress->completed ? 'Incomplete' : 'Complete' }}
                  </button>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="additional-action">
                  <h6><i class="fas fa-copy"></i> Duplicate Record</h6>
                  <p class="small text-muted mb-2">
                    Create similar progress for another user
                  </p>
                  <a href="{{ url('lesson-progress/create?user_id=' . $progress->user_id . '&lesson_id=' . $progress->lesson_id) }}" 
                     class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-copy"></i> Duplicate
                  </a>
                </div>
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
  
  .card-header.bg-warning {
    background: linear-gradient(145deg, #f39c12, #e67e22) !important;
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
  
  .btn-toggle {
    background: linear-gradient(145deg, #9b59b6, #8e44ad);
    color: white;
    border: none;
  }
  
  .btn-toggle:hover {
    background: linear-gradient(145deg, #8e44ad, #7d3c98);
    color: white;
    transform: translateY(-2px);
  }
  
  /* Status Card */
  .status-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    height: 100%;
    transition: transform 0.3s ease;
  }
  
  .status-card:hover {
    transform: translateY(-5px);
  }
  
  .status-icon {
    animation: pulse 2s infinite;
  }
  
  @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
  }
  
  /* Info Card */
  .info-card {
    border: none;
    border-radius: 10px;
    height: 100%;
  }
  
  .user-avatar, .lesson-icon {
    width: 60px;
    text-align: center;
  }
  
  /* Timeline */
  .timeline-card {
    border: none;
    border-radius: 10px;
    overflow: hidden;
  }
  
  .timeline {
    position: relative;
    padding-left: 30px;
  }
  
  .timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #9b59b6, #3498db);
  }
  
  .timeline-item {
    position: relative;
    margin-bottom: 30px;
  }
  
  .timeline-icon {
    position: absolute;
    left: -30px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #9b59b6;
    z-index: 1;
  }
  
  .timeline-item.completed .timeline-icon {
    background: #9b59b6;
    color: white;
  }
  
  .timeline-content {
    padding-left: 20px;
  }
  
  /* Progress Visualization */
  .progress-container {
    margin: 15px 0;
  }
  
  .progress {
    height: 20px;
    border-radius: 10px;
    background-color: #e9ecef;
    overflow: hidden;
  }
  
  .progress-bar {
    border-radius: 10px;
    transition: width 1s ease-in-out;
  }
  
  .progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 14px;
    font-weight: 500;
  }
  
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
  }
  
  .stat-item {
    text-align: center;
    padding: 10px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
  }
  
  .stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #9b59b6;
  }
  
  .stat-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  /* Related Links */
  .related-link {
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.3s ease;
  }
  
  .related-link:hover {
    transform: translateY(-5px);
    color: inherit;
  }
  
  .related-link.disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  
  .related-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
  }
  
  .related-icon.bg-primary {
    background: linear-gradient(145deg, #3498db, #2980b9);
  }
  
  .related-icon.bg-success {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
  }
  
  .related-icon.bg-info {
    background: linear-gradient(145deg, #17a2b8, #138496);
  }
  
  .related-icon.bg-secondary {
    background: linear-gradient(145deg, #6c757d, #495057);
  }
  
  /* Additional Actions */
  .additional-action {
    padding: 15px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
  }
  
  /* Notes */
  .notes-content {
    font-size: 15px;
    line-height: 1.6;
    color: #333;
    white-space: pre-wrap;
  }
  
  /* Badge Styles */
  .badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .timeline {
      padding-left: 20px;
    }
    
    .timeline-icon {
      left: -20px;
      width: 20px;
      height: 20px;
    }
    
    .stats-grid {
      grid-template-columns: 1fr;
      gap: 10px;
    }
    
    .related-icon {
      width: 60px;
      height: 60px;
    }
    
    .related-icon i {
      font-size: 1.5rem;
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Confirm delete
    const deleteForm = document.querySelector('form[action*="lesson-progress/' + {{ $progress->id }} + '"]');
    if (deleteForm) {
      deleteForm.addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to delete this progress record? This action cannot be undone.')) {
          e.preventDefault();
        }
      });
    }
    
    // Load user progress statistics
    function loadUserStats() {
      const userId = {{ $progress->user_id }};
      const userStatsDiv = document.getElementById('userProgressStats');
      
      fetch(`/api/users/${userId}/lesson-progress`)
        .then(response => response.json())
        .then(data => {
          const completionRate = data.total_lessons > 0 
            ? Math.round((data.completed_lessons / data.total_lessons) * 100) 
            : 0;
          
          userStatsDiv.innerHTML = `
            <div class="progress-container">
              <div class="progress-label">
                <span>Overall Progress</span>
                <span>${completionRate}%</span>
              </div>
              <div class="progress">
                <div class="progress-bar" role="progressbar" 
                     style="width: ${completionRate}%; background: linear-gradient(145deg, #9b59b6, #8e44ad);"></div>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-4">
                <div class="stat-item">
                  <div class="stat-number">${data.completed_lessons}</div>
                  <div class="stat-label">Completed</div>
                </div>
              </div>
              <div class="col-4">
                <div class="stat-item">
                  <div class="stat-number">${data.in_progress}</div>
                  <div class="stat-label">In Progress</div>
                </div>
              </div>
              <div class="col-4">
                <div class="stat-item">
                  <div class="stat-number">${data.total_lessons}</div>
                  <div class="stat-label">Total</div>
                </div>
              </div>
            </div>
            <div class="mt-3">
              <p class="mb-1 small">
                <i class="fas fa-user"></i> ${data.user_name}
              </p>
              <p class="mb-0 small text-muted">
                Last activity: ${data.last_activity ? new Date(data.last_activity).toLocaleDateString() : 'N/A'}
              </p>
            </div>
          `;
          
          // Update visualization
          updateVisualization();
        })
        .catch(error => {
          console.error('Error:', error);
          userStatsDiv.innerHTML = '<p class="text-danger">Failed to load user statistics</p>';
        });
    }
    
    // Load lesson progress statistics
    function loadLessonStats() {
      const lessonId = {{ $progress->lesson_id }};
      const lessonStatsDiv = document.getElementById('lessonProgressStats');
      
      fetch(`/api/lessons/${lessonId}/progress-stats`)
        .then(response => response.json())
        .then(data => {
          const completionRate = data.total_users > 0
            ? Math.round((data.completed_count / data.total_users) * 100)
            : 0;
          
          lessonStatsDiv.innerHTML = `
            <div class="progress-container">
              <div class="progress-label">
                <span>Lesson Completion</span>
                <span>${completionRate}%</span>
              </div>
              <div class="progress">
                <div class="progress-bar" role="progressbar" 
                     style="width: ${completionRate}%; background: linear-gradient(145deg, #2ecc71, #27ae60);"></div>
              </div>
            </div>
            <div class="row mt-3">
              <div class="col-4">
                <div class="stat-item">
                  <div class="stat-number">${data.completed_count}</div>
                  <div class="stat-label">Completed</div>
                </div>
              </div>
              <div class="col-4">
                <div class="stat-item">
                  <div class="stat-number">${data.in_progress_count}</div>
                  <div class="stat-label">In Progress</div>
                </div>
              </div>
              <div class="col-4">
                <div class="stat-item">
                  <div class="stat-number">${data.total_users}</div>
                  <div class="stat-label">Total Users</div>
                </div>
              </div>
            </div>
            <div class="mt-3">
              <p class="mb-1 small">
                <i class="fas fa-book"></i> ${data.lesson_title}
              </p>
              <p class="mb-0 small text-muted">
                <i class="fas fa-graduation-cap"></i> ${data.course_title || 'No Course'}
              </p>
            </div>
          `;
          
          // Update visualization
          updateVisualization();
        })
        .catch(error => {
          console.error('Error:', error);
          lessonStatsDiv.innerHTML = '<p class="text-danger">Failed to load lesson statistics</p>';
        });
    }
    
    // Update progress visualization
    function updateVisualization() {
      // Fetch both sets of data
      Promise.all([
        fetch(`/api/users/{{ $progress->user_id }}/lesson-progress`).then(r => r.json()),
        fetch(`/api/lessons/{{ $progress->lesson_id }}/progress-stats`).then(r => r.json())
      ])
      .then(([userData, lessonData]) => {
        // User progress
        const userCompletionRate = userData.total_lessons > 0 
          ? Math.round((userData.completed_lessons / userData.total_lessons) * 100) 
          : 0;
        
        document.getElementById('userCompletionRate').textContent = userCompletionRate + '%';
        const userProgressBar = document.getElementById('userProgressBar');
        userProgressBar.style.width = userCompletionRate + '%';
        
        // Color code user progress bar
        if (userCompletionRate >= 80) {
          userProgressBar.style.background = 'linear-gradient(145deg, #2ecc71, #27ae60)';
        } else if (userCompletionRate >= 50) {
          userProgressBar.style.background = 'linear-gradient(145deg, #f39c12, #e67e22)';
        } else {
          userProgressBar.style.background = 'linear-gradient(145deg, #e74c3c, #c0392b)';
        }
        
        // Update user stats numbers
        document.getElementById('userCompleted').textContent = userData.completed_lessons;
        document.getElementById('userInProgress').textContent = userData.in_progress;
        document.getElementById('userTotal').textContent = userData.total_lessons;
        
        // Lesson progress
        const lessonCompletionRate = lessonData.total_users > 0
          ? Math.round((lessonData.completed_count / lessonData.total_users) * 100)
          : 0;
        
        document.getElementById('lessonCompletionRate').textContent = lessonCompletionRate + '%';
        const lessonProgressBar = document.getElementById('lessonProgressBar');
        lessonProgressBar.style.width = lessonCompletionRate + '%';
        
        // Color code lesson progress bar
        if (lessonCompletionRate >= 80) {
          lessonProgressBar.style.background = 'linear-gradient(145deg, #2ecc71, #27ae60)';
        } else if (lessonCompletionRate >= 50) {
          lessonProgressBar.style.background = 'linear-gradient(145deg, #f39c12, #e67e22)';
        } else {
          lessonProgressBar.style.background = 'linear-gradient(145deg, #e74c3c, #c0392b)';
        }
        
        // Update lesson stats numbers
        document.getElementById('lessonCompleted').textContent = lessonData.completed_count;
        document.getElementById('lessonInProgress').textContent = lessonData.in_progress_count;
        document.getElementById('lessonTotal').textContent = lessonData.total_users;
      })
      .catch(error => {
        console.error('Error updating visualization:', error);
      });
    }
    
    // Toggle progress status
    function toggleProgressStatus() {
      if (!confirm('Are you sure you want to change the completion status?')) {
        return;
      }
      
      const button = event.target.closest('button');
      const originalHtml = button.innerHTML;
      const newStatus = {{ $progress->completed ? 'false' : 'true' }};
      
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
      button.disabled = true;
      
      fetch(`/lesson-progress/{{ $progress->id }}/toggle`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          completed: newStatus
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload page to show updated status
          window.location.reload();
        } else {
          alert(data.message || 'Failed to update progress status');
          button.innerHTML = originalHtml;
          button.disabled = false;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while updating progress status');
        button.innerHTML = originalHtml;
        button.disabled = false;
      });
    }
    
    // Calculate and display duration
    function calculateDuration() {
      const created = new Date('{{ $progress->created_at }}');
      const updated = new Date('{{ $progress->updated_at }}');
      const completed = {{ $progress->completed_at ? "'" . $progress->completed_at . "'" : 'null' }};
      
      const duration = Math.floor((updated - created) / (1000 * 60 * 60 * 24));
      
      // Update duration display if it exists
      const durationElement = document.querySelector('.completion-duration');
      if (durationElement) {
        durationElement.innerHTML = `
          <span class="badge bg-light text-dark">
            <i class="fas fa-clock"></i> 
            ${duration} day${duration !== 1 ? 's' : ''} total duration
          </span>
        `;
      }
    }
    
    // Initialize stats loading
    setTimeout(() => {
      loadUserStats();
      loadLessonStats();
      calculateDuration();
    }, 500);
    
    // Auto-refresh stats every 30 seconds
    setInterval(() => {
      loadUserStats();
      loadLessonStats();
    }, 30000);
    
    // Copy progress ID to clipboard
    const progressId = document.querySelector('small:contains("ID:")');
    if (progressId) {
      progressId.addEventListener('click', function() {
        const text = '{{ $progress->id }}';
        navigator.clipboard.writeText(text).then(() => {
          const toast = document.createElement('div');
          toast.className = 'alert alert-success position-fixed top-0 end-0 m-3';
          toast.style.zIndex = '9999';
          toast.innerHTML = '<i class="fas fa-check-circle"></i> Progress ID copied to clipboard!';
          document.body.appendChild(toast);
          
          setTimeout(() => toast.remove(), 3000);
        });
      });
      
      progressId.style.cursor = 'pointer';
      progressId.title = 'Click to copy Progress ID';
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Animation for progress bars
    function animateProgressBars() {
      const progressBars = document.querySelectorAll('.progress-bar');
      progressBars.forEach(bar => {
        const width = bar.style.width;
        bar.style.width = '0%';
        setTimeout(() => {
          bar.style.width = width;
        }, 300);
      });
    }
    
    // Animate progress bars on load
    setTimeout(animateProgressBars, 1000);
  });
</script>

@stop