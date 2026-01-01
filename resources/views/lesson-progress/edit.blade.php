@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-edit"></i> Edit Progress Record
    </h4>
  </div>
  <div class="card-body">
    
    <!-- Display validation errors -->
    @if ($errors->any())
      <div class="alert alert-danger alert-school">
          <strong>Whoops!</strong> There were some problems with your input.<br><br>
          <ul>
              @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
              @endforeach
          </ul>
      </div>
    @endif

    <!-- Success message -->
    @if(session('success'))
      <div class="alert alert-success alert-school">
        <i class="fas fa-check-circle"></i> {{ session('success') }}
      </div>
    @endif

    <form action="{{ url('lesson-progress/' . $progress->id) }}" method="post" id="progressForm">
      @csrf
      @method('PUT')
      
      <!-- Current Information -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6><i class="fas fa-user-graduate"></i> Current Information</h6>
              <div class="row">
                <div class="col-6">
                  <p class="mb-1"><strong>User:</strong></p>
                  <p class="mb-1"><strong>Lesson:</strong></p>
                  <p class="mb-1"><strong>Course:</strong></p>
                  <p class="mb-0"><strong>Status:</strong></p>
                </div>
                <div class="col-6">
                  <p class="mb-1">{{ $progress->user->name ?? 'N/A' }}</p>
                  <p class="mb-1">{{ $progress->lesson->title ?? 'N/A' }}</p>
                  <p class="mb-1">{{ $progress->lesson->course->title ?? 'N/A' }}</p>
                  <p class="mb-0">
                    @if($progress->completed)
                      <span class="badge bg-success"><i class="fas fa-check-circle"></i> Completed</span>
                    @else
                      <span class="badge bg-secondary"><i class="fas fa-clock"></i> In Progress</span>
                    @endif
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6><i class="fas fa-history"></i> Timeline</h6>
              <div class="row">
                <div class="col-6">
                  <p class="mb-1"><strong>Created:</strong></p>
                  <p class="mb-1"><strong>Updated:</strong></p>
                  @if($progress->completed_at)
                  <p class="mb-0"><strong>Completed:</strong></p>
                  @endif
                </div>
                <div class="col-6">
                  <p class="mb-1">{{ $progress->created_at->format('M d, Y h:i A') }}</p>
                  <p class="mb-1">{{ $progress->updated_at->format('M d, Y h:i A') }}</p>
                  @if($progress->completed_at)
                  <p class="mb-0">{{ $progress->completed_at->format('M d, Y h:i A') }}</p>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Editable Fields -->
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="user_id" class="form-label">User *</label>
          <select name="user_id" id="user_id" class="form-select" required onchange="checkForDuplicate()">
            <option value="">Select User</option>
            @foreach($users ?? [] as $user)
              <option value="{{ $user->id }}" {{ old('user_id', $progress->user_id) == $user->id ? 'selected' : '' }}>
                {{ $user->name }} ({{ $user->email }})
              </option>
            @endforeach
          </select>
          @if(empty($users))
            <div class="alert alert-warning mt-2">
              <i class="fas fa-exclamation-triangle"></i> No users found.
            </div>
          @endif
          <div class="form-text">Select the user for this progress record</div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="lesson_id" class="form-label">Lesson *</label>
          <select name="lesson_id" id="lesson_id" class="form-select" required onchange="checkForDuplicate()">
            <option value="">Select Lesson</option>
            @foreach($lessons ?? [] as $lesson)
              <option value="{{ $lesson->id }}" {{ old('lesson_id', $progress->lesson_id) == $lesson->id ? 'selected' : '' }}>
                {{ $lesson->title }} ({{ $lesson->course->title ?? 'No Course' }})
              </option>
            @endforeach
          </select>
          @if(empty($lessons))
            <div class="alert alert-warning mt-2">
              <i class="fas fa-exclamation-triangle"></i> No lessons found.
            </div>
          @endif
          <div class="form-text">Select the lesson for this progress record</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="completed" class="form-label">Progress Status</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="completed" id="completed" 
                   value="1" {{ old('completed', $progress->completed) ? 'checked' : '' }} 
                   onchange="toggleCompletionDate()">
            <label class="form-check-label" for="completed">
              Mark as completed
            </label>
          </div>
          <div class="form-text">
            Toggle to mark this lesson as completed for the selected user
          </div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="completed_at" class="form-label">Completion Date & Time</label>
          <input type="datetime-local" name="completed_at" id="completed_at" class="form-control" 
                 value="{{ old('completed_at', $progress->completed_at ? $progress->completed_at->format('Y-m-d\TH:i') : '') }}">
          <div class="form-text">
            Date and time when the lesson was completed.
            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="setNow()">
              <i class="fas fa-clock"></i> Set to Now
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-1 ms-1" onclick="clearCompletionDate()">
              <i class="fas fa-times"></i> Clear
            </button>
          </div>
        </div>
      </div>

      <!-- Progress Statistics -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6><i class="fas fa-chart-line"></i> User Progress Statistics</h6>
              <div id="userStats">
                <div class="spinner-border spinner-border-sm text-primary"></div> Loading user stats...
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6><i class="fas fa-book"></i> Lesson Progress Statistics</h6>
              <div id="lessonStats">
                <div class="spinner-border spinner-border-sm text-primary"></div> Loading lesson stats...
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Progress Visualization -->
      <div class="mb-3">
        <div class="card bg-light">
          <div class="card-body">
            <h6><i class="fas fa-chart-bar"></i> Progress Visualization</h6>
            <div id="progressVisualization" class="py-3">
              <div class="row">
                <div class="col-md-6">
                  <div class="progress-container mb-3">
                    <div class="progress-label">
                      <span>User Overall Progress</span>
                      <span id="userProgressPercent">0%</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar" id="userProgressBar" role="progressbar" style="width: 0%"></div>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="progress-container mb-3">
                    <div class="progress-label">
                      <span>Lesson Completion Rate</span>
                      <span id="lessonProgressPercent">0%</span>
                    </div>
                    <div class="progress">
                      <div class="progress-bar" id="lessonProgressBar" role="progressbar" style="width: 0%"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Notes Section -->
      <div class="mb-3">
        <label for="notes" class="form-label">Notes (Optional)</label>
        <textarea name="notes" id="notes" class="form-control" rows="3" 
                  placeholder="Any additional notes about this progress record">{{ old('notes', $progress->notes) }}</textarea>
        <div class="form-text">Optional notes about the progress record</div>
        <div class="char-counter">
          <span id="notesCharCount">0</span>/500 characters
        </div>
      </div>

      <!-- Warning for duplicate progress -->
      <div class="alert alert-warning d-none" id="duplicateWarning">
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Warning:</strong> Another progress record exists for this user and lesson combination.
        <div class="mt-2">
          <a href="#" id="duplicateLink" class="btn btn-sm btn-outline-warning">
            <i class="fas fa-external-link-alt"></i> View Existing Record
          </a>
        </div>
      </div>

      <!-- Confirmation for status change -->
      <div class="alert alert-info d-none" id="statusChangeWarning">
        <i class="fas fa-info-circle"></i> 
        <strong>Note:</strong> Changing the completion status will update the progress statistics.
      </div>

      <div class="d-flex justify-content-between mt-4">
        <div>
          <a href="{{ url('lesson-progress') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Progress
          </a>
          <a href="{{ url('lesson-progress/' . $progress->id) }}" class="btn btn-view ms-2">
            <i class="fas fa-eye"></i> View Record
          </a>
        </div>
        <div>
          <button type="button" class="btn btn-outline-secondary me-2" onclick="resetToOriginal()">
            <i class="fas fa-history"></i> Reset Changes
          </button>
          <button type="submit" class="btn btn-update">
            <i class="fas fa-save"></i> Update Progress
          </button>
        </div>
      </div>
    </form>
   
  </div>
</div>

<style>
  .card {
    border: none;
    box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    border-radius: 12px;
    overflow: hidden;
    max-width: 1000px;
    margin: 0 auto;
  }
  
  .card-header {
    background: linear-gradient(145deg, #9b59b6, #8e44ad);
    color: white;
    padding: 20px;
    border-bottom: none;
  }
  
  .card-title {
    margin: 0;
    font-weight: 600;
    font-size: 1.5rem;
  }
  
  .card-body {
    padding: 30px;
  }
  
  .form-control:focus, .form-select:focus {
    border-color: #9b59b6;
    box-shadow: 0 0 0 0.25rem rgba(155, 89, 182, 0.25);
  }
  
  .btn-update {
    background: linear-gradient(145deg, #f39c12, #e67e22);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
  }
  
  .btn-update:hover {
    background: linear-gradient(145deg, #e67e22, #d35400);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(243, 156, 18, 0.3);
    color: white;
  }
  
  .btn-secondary {
    background: linear-gradient(145deg, #6c757d, #495057);
    color: white;
    border: none;
  }
  
  .btn-secondary:hover {
    background: linear-gradient(145deg, #495057, #343a40);
    color: white;
  }
  
  .btn-view {
    background: linear-gradient(145deg, #3498db, #2980b9);
    color: white;
    border: none;
  }
  
  .btn-view:hover {
    background: linear-gradient(145deg, #2980b9, #1c5a7a);
    color: white;
  }
  
  .form-check-input:checked {
    background-color: #9b59b6;
    border-color: #9b59b6;
  }
  
  .form-switch .form-check-input {
    width: 3em;
    height: 1.5em;
  }
  
  .alert-school {
    border-left: 4px solid #9b59b6;
    background-color: #f8f9fa;
  }
  
  .alert-success {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
  }
  
  .alert-warning {
    background-color: #fff3cd;
    border-color: #ffecb5;
    color: #664d03;
  }
  
  .alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
  }
  
  .char-counter {
    font-size: 0.875rem;
    color: #6c757d;
    text-align: right;
    margin-top: 5px;
  }
  
  .char-counter.warning {
    color: #ffc107;
  }
  
  .char-counter.danger {
    color: #dc3545;
  }
  
  .bg-light {
    background-color: #f8f9fa !important;
  }
  
  /* Progress bar styling */
  .progress-container {
    margin: 10px 0;
  }
  
  .progress {
    height: 20px;
    border-radius: 10px;
    background-color: #e9ecef;
  }
  
  .progress-bar {
    background: linear-gradient(145deg, #9b59b6, #8e44ad);
    border-radius: 10px;
    transition: width 1s ease-in-out;
  }
  
  .progress-label {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
    font-size: 14px;
  }
  
  .stat-card {
    padding: 8px;
    border-radius: 6px;
    text-align: center;
    background-color: white;
    border: 1px solid #e9ecef;
    margin: 5px 0;
  }
  
  .stat-number {
    font-size: 20px;
    font-weight: 700;
    color: #9b59b6;
  }
  
  .stat-label {
    font-size: 11px;
    color: #6c757d;
    text-transform: uppercase;
  }
  
  /* Badge styling */
  .badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
  }
  
  /* Timeline styling */
  .timeline-item {
    margin-bottom: 10px;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Store original values for reset functionality
    const originalValues = {
      user_id: '{{ $progress->user_id }}',
      lesson_id: '{{ $progress->lesson_id }}',
      completed: {{ $progress->completed ? 'true' : 'false' }},
      completed_at: '{{ $progress->completed_at ? $progress->completed_at->format("Y-m-d\\TH:i") : "" }}',
      notes: `{!! addslashes($progress->notes ?? '') !!}`
    };
    
    // Character counter for notes
    const notesInput = document.getElementById('notes');
    const notesCharCount = document.getElementById('notesCharCount');
    
    function updateCharCount(element, counter) {
      const length = element.value.length;
      counter.textContent = length;
      
      const max = 500;
      if (length > max * 0.9) {
        counter.classList.add('danger');
        counter.classList.remove('warning');
      } else if (length > max * 0.75) {
        counter.classList.add('warning');
        counter.classList.remove('danger');
      } else {
        counter.classList.remove('warning', 'danger');
      }
    }
    
    notesInput.addEventListener('input', () => updateCharCount(notesInput, notesCharCount));
    updateCharCount(notesInput, notesCharCount);
    
    // Toggle completion date field
    const completedCheckbox = document.getElementById('completed');
    const completedAtInput = document.getElementById('completed_at');
    const statusChangeWarning = document.getElementById('statusChangeWarning');
    
    function toggleCompletionDate() {
      if (completedCheckbox.checked) {
        completedAtInput.disabled = false;
        completedAtInput.required = false;
        if (!completedAtInput.value) {
          setNow();
        }
        
        // Show status change warning if changing from incomplete to complete
        if (!originalValues.completed) {
          statusChangeWarning.classList.remove('d-none');
        }
      } else {
        completedAtInput.disabled = true;
        completedAtInput.required = false;
        completedAtInput.value = '';
        
        // Show status change warning if changing from complete to incomplete
        if (originalValues.completed) {
          statusChangeWarning.classList.remove('d-none');
        }
      }
    }
    
    // Initialize completion date field
    toggleCompletionDate();
    
    // Set completion date to current time
    function setNow() {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      completedAtInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    
    // Clear completion date
    function clearCompletionDate() {
      completedAtInput.value = '';
    }
    
    // Check for duplicate progress record
    function checkForDuplicate() {
      const userId = document.getElementById('user_id').value;
      const lessonId = document.getElementById('lesson_id').value;
      const duplicateWarning = document.getElementById('duplicateWarning');
      const duplicateLink = document.getElementById('duplicateLink');
      
      // Don't check if values haven't changed
      if (userId === originalValues.user_id && lessonId === originalValues.lesson_id) {
        duplicateWarning.classList.add('d-none');
        return;
      }
      
      if (!userId || !lessonId) {
        duplicateWarning.classList.add('d-none');
        return;
      }
      
      // Check if another progress record exists for this user and lesson
      fetch(`/api/check-progress-duplicate/${userId}/${lessonId}?exclude={{ $progress->id }}`)
        .then(response => response.json())
        .then(data => {
          if (data.exists && data.progress_id) {
            duplicateWarning.classList.remove('d-none');
            duplicateLink.href = `/lesson-progress/${data.progress_id}`;
          } else {
            duplicateWarning.classList.add('d-none');
          }
        })
        .catch(error => {
          console.error('Error checking duplicate:', error);
        });
    }
    
    // Load user statistics
    function loadUserStats() {
      const userId = document.getElementById('user_id').value;
      const userStatsDiv = document.getElementById('userStats');
      
      if (!userId) {
        userStatsDiv.innerHTML = '<p class="text-muted mb-0 text-center">No user selected</p>';
        return;
      }
      
      fetch(`/api/users/${userId}/lesson-progress`)
        .then(response => response.json())
        .then(data => {
          userStatsDiv.innerHTML = `
            <div class="row">
              <div class="col-4">
                <div class="stat-card">
                  <div class="stat-number">${data.completed_lessons}</div>
                  <div class="stat-label">Completed</div>
                </div>
              </div>
              <div class="col-4">
                <div class="stat-card">
                  <div class="stat-number">${data.in_progress}</div>
                  <div class="stat-label">In Progress</div>
                </div>
              </div>
              <div class="col-4">
                <div class="stat-card">
                  <div class="stat-number">${data.total_lessons}</div>
                  <div class="stat-label">Total</div>
                </div>
              </div>
            </div>
            <div class="mt-2">
              <p class="mb-1 small text-muted">
                <i class="fas fa-user"></i> ${data.user_name || 'User'}
              </p>
              <p class="mb-0 small text-muted">
                <i class="fas fa-percentage"></i> Completion Rate: 
                ${data.total_lessons > 0 ? Math.round((data.completed_lessons / data.total_lessons) * 100) : 0}%
              </p>
            </div>
          `;
          
          // Update progress visualization
          updateProgressVisualization();
        })
        .catch(error => {
          console.error('Error:', error);
          userStatsDiv.innerHTML = '<p class="text-danger text-center">Failed to load user stats</p>';
        });
    }
    
    // Load lesson statistics
    function loadLessonStats() {
      const lessonId = document.getElementById('lesson_id').value;
      const lessonStatsDiv = document.getElementById('lessonStats');
      
      if (!lessonId) {
        lessonStatsDiv.innerHTML = '<p class="text-muted mb-0 text-center">No lesson selected</p>';
        return;
      }
      
      fetch(`/api/lessons/${lessonId}/progress-stats`)
        .then(response => response.json())
        .then(data => {
          lessonStatsDiv.innerHTML = `
            <div class="row">
              <div class="col-4">
                <div class="stat-card">
                  <div class="stat-number">${data.completed_count}</div>
                  <div class="stat-label">Completed</div>
                </div>
              </div>
              <div class="col-4">
                <div class="stat-card">
                  <div class="stat-number">${data.in_progress_count}</div>
                  <div class="stat-label">In Progress</div>
                </div>
              </div>
              <div class="col-4">
                <div class="stat-card">
                  <div class="stat-number">${data.total_users}</div>
                  <div class="stat-label">Total Users</div>
                </div>
              </div>
            </div>
            <div class="mt-2">
              <p class="mb-1 small text-muted">
                <i class="fas fa-book"></i> ${data.lesson_title || 'Lesson'}
              </p>
              <p class="mb-0 small text-muted">
                <i class="fas fa-graduation-cap"></i> ${data.course_title || 'No Course'}
              </p>
            </div>
          `;
          
          // Update progress visualization
          updateProgressVisualization();
        })
        .catch(error => {
          console.error('Error:', error);
          lessonStatsDiv.innerHTML = '<p class="text-danger text-center">Failed to load lesson stats</p>';
        });
    }
    
    // Update progress visualization
    function updateProgressVisualization() {
      const userId = document.getElementById('user_id').value;
      const lessonId = document.getElementById('lesson_id').value;
      
      if (!userId || !lessonId) {
        return;
      }
      
      // Fetch combined data
      Promise.all([
        fetch(`/api/users/${userId}/lesson-progress`).then(r => r.json()),
        fetch(`/api/lessons/${lessonId}/progress-stats`).then(r => r.json())
      ])
      .then(([userData, lessonData]) => {
        const userCompletionRate = userData.total_lessons > 0 
          ? Math.round((userData.completed_lessons / userData.total_lessons) * 100) 
          : 0;
        
        const lessonCompletionRate = lessonData.total_users > 0
          ? Math.round((lessonData.completed_count / lessonData.total_users) * 100)
          : 0;
        
        // Update user progress
        document.getElementById('userProgressPercent').textContent = userCompletionRate + '%';
        const userProgressBar = document.getElementById('userProgressBar');
        userProgressBar.style.width = userCompletionRate + '%';
        
        // Update lesson progress
        document.getElementById('lessonProgressPercent').textContent = lessonCompletionRate + '%';
        const lessonProgressBar = document.getElementById('lessonProgressBar');
        lessonProgressBar.style.width = lessonCompletionRate + '%';
        
        // Color code based on completion rate
        if (userCompletionRate >= 80) {
          userProgressBar.style.background = 'linear-gradient(145deg, #2ecc71, #27ae60)';
        } else if (userCompletionRate >= 50) {
          userProgressBar.style.background = 'linear-gradient(145deg, #f39c12, #e67e22)';
        } else {
          userProgressBar.style.background = 'linear-gradient(145deg, #e74c3c, #c0392b)';
        }
        
        if (lessonCompletionRate >= 80) {
          lessonProgressBar.style.background = 'linear-gradient(145deg, #2ecc71, #27ae60)';
        } else if (lessonCompletionRate >= 50) {
          lessonProgressBar.style.background = 'linear-gradient(145deg, #f39c12, #e67e22)';
        } else {
          lessonProgressBar.style.background = 'linear-gradient(145deg, #e74c3c, #c0392b)';
        }
      })
      .catch(error => {
        console.error('Error updating visualization:', error);
      });
    }
    
    // Reset form to original values
    function resetToOriginal() {
      if (confirm('Are you sure you want to reset all changes to the original values?')) {
        document.getElementById('user_id').value = originalValues.user_id;
        document.getElementById('lesson_id').value = originalValues.lesson_id;
        document.getElementById('completed').checked = originalValues.completed;
        completedAtInput.value = originalValues.completed_at;
        notesInput.value = originalValues.notes.replace(/\\'/g, "'");
        
        // Reset character count
        updateCharCount(notesInput, notesCharCount);
        
        // Toggle completion date
        toggleCompletionDate();
        
        // Hide warnings
        document.getElementById('duplicateWarning').classList.add('d-none');
        document.getElementById('statusChangeWarning').classList.add('d-none');
        
        // Reload stats
        loadUserStats();
        loadLessonStats();
        
        // Show success message
        const successAlert = document.createElement('div');
        successAlert.className = 'alert alert-success mt-3';
        successAlert.innerHTML = '<i class="fas fa-check-circle"></i> All changes reset to original values';
        document.querySelector('.card-body').insertBefore(successAlert, document.querySelector('form'));
        
        setTimeout(() => successAlert.remove(), 3000);
      }
    }
    
    // Form validation
    const form = document.getElementById('progressForm');
    form.addEventListener('submit', function(e) {
      const userId = document.getElementById('user_id').value;
      const lessonId = document.getElementById('lesson_id').value;
      const completed = document.getElementById('completed').checked;
      const completedAt = document.getElementById('completed_at').value;
      
      let isValid = true;
      
      if (!userId) {
        document.getElementById('user_id').classList.add('is-invalid');
        isValid = false;
      }
      
      if (!lessonId) {
        document.getElementById('lesson_id').classList.add('is-invalid');
        isValid = false;
      }
      
      if (completed && !completedAt) {
        document.getElementById('completed_at').classList.add('is-invalid');
        isValid = false;
      }
      
      // Check for duplicate (prevent submission if duplicate exists)
      const duplicateWarning = document.getElementById('duplicateWarning');
      if (!duplicateWarning.classList.contains('d-none')) {
        if (!confirm('Another progress record exists for this user and lesson. Continue anyway?')) {
          e.preventDefault();
          return false;
        }
      }
      
      if (!isValid) {
        e.preventDefault();
        
        // Show error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger mt-3';
        errorDiv.innerHTML = '<strong>Error!</strong> Please fill in all required fields marked with *';
        form.prepend(errorDiv);
        
        // Scroll to top
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        return false;
      }
      
      return true;
    });
    
    // Remove invalid class on input
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
      input.addEventListener('input', function() {
        this.classList.remove('is-invalid');
      });
      input.addEventListener('change', function() {
        this.classList.remove('is-invalid');
      });
    });
    
    // Warn about unsaved changes
    let formChanged = false;
    const formInputs = form.querySelectorAll('input, select, textarea');
    
    formInputs.forEach(input => {
      const originalValue = input.value;
      input.addEventListener('input', () => {
        formChanged = input.value !== originalValue;
      });
      input.addEventListener('change', () => {
        formChanged = input.value !== originalValue;
      });
    });
    
    window.addEventListener('beforeunload', function(e) {
      if (formChanged) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
      }
    });
    
    // Initialize stats on load
    setTimeout(() => {
      loadUserStats();
      loadLessonStats();
    }, 500);
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
</script>

@stop