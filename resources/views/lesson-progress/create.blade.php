@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-plus-circle"></i> Create Progress Record
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

    <form action="{{ url('lesson-progress') }}" method="post" id="progressForm">
      @csrf
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="user_id" class="form-label">User *</label>
          <select name="user_id" id="user_id" class="form-select" required onchange="loadUserProgress()">
            <option value="">Select User</option>
            @foreach($users ?? [] as $user)
              <option value="{{ $user->id }}" {{ old('user_id') == $user->id ? 'selected' : '' }}>
                {{ $user->name }} ({{ $user->email }})
              </option>
            @endforeach
          </select>
          @if(empty($users))
            <div class="alert alert-warning mt-2">
              <i class="fas fa-exclamation-triangle"></i> No users found. 
              <a href="{{ url('users/create') }}" class="alert-link">Create a user first</a>.
            </div>
          @endif
          <div class="form-text">Select the user for this progress record</div>
          <div id="userProgress" class="mt-2"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="lesson_id" class="form-label">Lesson *</label>
          <select name="lesson_id" id="lesson_id" class="form-select" required onchange="loadLessonInfo()">
            <option value="">Select Lesson</option>
            @foreach($lessons ?? [] as $lesson)
              <option value="{{ $lesson->id }}" {{ old('lesson_id') == $lesson->id ? 'selected' : '' }}>
                {{ $lesson->title }} ({{ $lesson->course->title ?? 'No Course' }})
              </option>
            @endforeach
          </select>
          @if(empty($lessons))
            <div class="alert alert-warning mt-2">
              <i class="fas fa-exclamation-triangle"></i> No lessons found. 
              <a href="{{ url('lessons/create') }}" class="alert-link">Create a lesson first</a>.
            </div>
          @endif
          <div class="form-text">Select the lesson for this progress record</div>
          <div id="lessonInfo" class="mt-2"></div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="completed" class="form-label">Progress Status</label>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" name="completed" id="completed" 
                   value="1" {{ old('completed') ? 'checked' : '' }} 
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
                 value="{{ old('completed_at', now()->format('Y-m-d\TH:i')) }}">
          <div class="form-text">
            Date and time when the lesson was completed. 
            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="setNow()">
              <i class="fas fa-clock"></i> Set to Now
            </button>
          </div>
        </div>
      </div>

      <!-- Progress Visualization -->
      <div class="mb-3">
        <div class="card bg-light">
          <div class="card-body">
            <h6><i class="fas fa-chart-bar"></i> Progress Overview</h6>
            <div id="progressOverview" class="text-center py-4">
              <p class="text-muted mb-0">
                <i class="fas fa-info-circle"></i> 
                Select a user and lesson to see progress information
              </p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Stats -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6><i class="fas fa-user-graduate"></i> User Information</h6>
              <div id="userStats" class="py-3 text-center">
                <p class="text-muted mb-0">No user selected</p>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6><i class="fas fa-book"></i> Lesson Information</h6>
              <div id="lessonStats" class="py-3 text-center">
                <p class="text-muted mb-0">No lesson selected</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Notes Section -->
      <div class="mb-3">
        <label for="notes" class="form-label">Notes (Optional)</label>
        <textarea name="notes" id="notes" class="form-control" rows="3" 
                  placeholder="Any additional notes about this progress record">{{ old('notes') }}</textarea>
        <div class="form-text">Optional notes about the progress record</div>
        <div class="char-counter">
          <span id="notesCharCount">0</span>/500 characters
        </div>
      </div>

      <!-- Warning for duplicate progress -->
      <div class="alert alert-warning d-none" id="duplicateWarning">
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Warning:</strong> This user already has a progress record for this lesson. 
        Creating a new record will replace the existing one.
      </div>

      <div class="d-flex justify-content-between mt-4">
        <div>
          <a href="{{ url('lesson-progress') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Progress
          </a>
          <button type="button" class="btn btn-outline-secondary ms-2" onclick="saveAsIncomplete()">
            <i class="fas fa-save"></i> Save as Incomplete
          </button>
        </div>
        <div>
          <button type="reset" class="btn btn-outline-secondary me-2">
            <i class="fas fa-redo"></i> Reset Form
          </button>
          <button type="submit" class="btn btn-custom">
            <i class="fas fa-plus-circle"></i> Create Progress Record
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
  
  .btn-custom {
    background: linear-gradient(145deg, #9b59b6, #8e44ad);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
  }
  
  .btn-custom:hover {
    background: linear-gradient(145deg, #8e44ad, #7d3c98);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(155, 89, 182, 0.3);
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
  
  .alert-warning {
    background-color: #fff3cd;
    border-color: #ffecb5;
    color: #664d03;
  }
  
  .alert-warning a {
    color: #664d03;
    text-decoration: underline;
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
    margin: 15px 0;
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
  }
  
  .stat-card {
    padding: 10px;
    border-radius: 8px;
    text-align: center;
    background-color: white;
    border: 1px solid #e9ecef;
    margin: 5px 0;
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
  }
  
  /* Completion date field styling */
  #completed_at:disabled {
    background-color: #e9ecef;
    opacity: 0.6;
    cursor: not-allowed;
  }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for notes (keep this as is)
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
    
    if (notesInput) {
        notesInput.addEventListener('input', () => updateCharCount(notesInput, notesCharCount));
        updateCharCount(notesInput, notesCharCount);
    }
    
    // FIX: Completion date functionality
    const completedCheckbox = document.getElementById('completed');
    const completedAtInput = document.getElementById('completed_at');
    
    if (completedCheckbox && completedAtInput) {
        // Function to toggle completion date field
        function toggleCompletionDate() {
            if (completedCheckbox.checked) {
                // If marking as completed, enable the date field
                completedAtInput.disabled = false;
                completedAtInput.required = false;
                
                // If empty, auto-fill with current time
                if (!completedAtInput.value) {
                    setNow();
                }
            } else {
                // If marking as incomplete, disable and clear the date field
                completedAtInput.disabled = true;
                completedAtInput.required = false;
                completedAtInput.value = '';
            }
        }
        
        // Function to set current date/time
        function setNow() {
            const now = new Date();
            // Format: YYYY-MM-DDThh:mm
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            completedAtInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
        }
        
        // Attach event listener to the checkbox
        completedCheckbox.addEventListener('change', toggleCompletionDate);
        
        // Initialize based on current checkbox state
        toggleCompletionDate();
        
        // Make setNow function available for the "Set to Now" button
        window.setNow = setNow;
    }
    
    // Keep all your other functions EXACTLY as they were...
    // [Rest of your existing JavaScript code remains unchanged]
    
    // Load user progress information
    function loadUserProgress() {
      const userId = document.getElementById('user_id').value;
      const userProgressDiv = document.getElementById('userProgress');
      const userStatsDiv = document.getElementById('userStats');
      const lessonId = document.getElementById('lesson_id').value;
      
      if (!userId) {
        userProgressDiv.innerHTML = '';
        userStatsDiv.innerHTML = '<p class="text-muted mb-0">No user selected</p>';
        checkForDuplicate();
        return;
      }
      
      // Show loading
      userProgressDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Loading user progress...';
      userStatsDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...';
      
      fetch(`/api/users/${userId}/lesson-progress`)
        .then(response => response.json())
        .then(data => {
          // Update user progress display
          if (data.total_lessons > 0) {
            const completionRate = Math.round((data.completed_lessons / data.total_lessons) * 100);
            
            userProgressDiv.innerHTML = `
              <div class="progress-container">
                <div class="progress-label">
                  <span>Progress: ${data.completed_lessons}/${data.total_lessons} lessons</span>
                  <span>${completionRate}%</span>
                </div>
                <div class="progress">
                  <div class="progress-bar" role="progressbar" 
                       style="width: ${completionRate}%"></div>
                </div>
              </div>
            `;
          } else {
            userProgressDiv.innerHTML = '<div class="alert alert-info">No progress data available for this user</div>';
          }
          
          // Update user stats
          userStatsDiv.innerHTML = `
            <div class="row">
              <div class="col-6">
                <div class="stat-card">
                  <div class="stat-number">${data.completed_lessons}</div>
                  <div class="stat-label">Completed</div>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-card">
                  <div class="stat-number">${data.in_progress}</div>
                  <div class="stat-label">In Progress</div>
                </div>
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-12">
                <div class="stat-card">
                  <div class="stat-number">${data.total_lessons}</div>
                  <div class="stat-label">Total Lessons</div>
                </div>
              </div>
            </div>
          `;
          
          // Check for duplicate if lesson is also selected
          if (lessonId) {
            checkForDuplicate();
          }
          
          // Update overall progress overview
          updateProgressOverview();
        })
        .catch(error => {
          console.error('Error:', error);
          userProgressDiv.innerHTML = '<div class="alert alert-danger">Failed to load user progress</div>';
          userStatsDiv.innerHTML = '<p class="text-danger">Failed to load user stats</p>';
        });
    }
    
    // Load lesson information
    function loadLessonInfo() {
      const lessonId = document.getElementById('lesson_id').value;
      const lessonInfoDiv = document.getElementById('lessonInfo');
      const lessonStatsDiv = document.getElementById('lessonStats');
      const userId = document.getElementById('user_id').value;
      
      if (!lessonId) {
        lessonInfoDiv.innerHTML = '';
        lessonStatsDiv.innerHTML = '<p class="text-muted mb-0">No lesson selected</p>';
        checkForDuplicate();
        return;
      }
      
      // Show loading
      lessonInfoDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Loading lesson info...';
      lessonStatsDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Loading...';
      
      fetch(`/api/lessons/${lessonId}/progress-stats`)
        .then(response => response.json())
        .then(data => {
          // Update lesson info display
          lessonInfoDiv.innerHTML = `
            <div class="alert alert-info">
              <div class="row">
                <div class="col-8">
                  <p class="mb-1"><strong>Course:</strong> ${data.course_title || 'N/A'}</p>
                  <p class="mb-1"><strong>Position:</strong> ${data.position || 'N/A'}</p>
                  <p class="mb-0"><strong>Status:</strong> ${data.is_published ? 'Published' : 'Draft'}</p>
                </div>
                <div class="col-4 text-end">
                  ${data.has_video ? '<span class="badge bg-success"><i class="fas fa-video"></i> Has Video</span>' : ''}
                </div>
              </div>
            </div>
          `;
          
          // Update lesson stats
          lessonStatsDiv.innerHTML = `
            <div class="row">
              <div class="col-6">
                <div class="stat-card">
                  <div class="stat-number">${data.completed_count}</div>
                  <div class="stat-label">Completed</div>
                </div>
              </div>
              <div class="col-6">
                <div class="stat-card">
                  <div class="stat-number">${data.in_progress_count}</div>
                  <div class="stat-label">In Progress</div>
                </div>
              </div>
            </div>
            <div class="row mt-2">
              <div class="col-12">
                <div class="stat-card">
                  <div class="stat-number">${data.total_users}</div>
                  <div class="stat-label">Total Users</div>
                </div>
              </div>
            </div>
          `;
          
          // Check for duplicate if user is also selected
          if (userId) {
            checkForDuplicate();
          }
          
          // Update overall progress overview
          updateProgressOverview();
        })
        .catch(error => {
          console.error('Error:', error);
          lessonInfoDiv.innerHTML = '<div class="alert alert-danger">Failed to load lesson info</div>';
          lessonStatsDiv.innerHTML = '<p class="text-danger">Failed to load lesson stats</p>';
        });
    }
    
    // Check for duplicate progress record
    function checkForDuplicate() {
      const userId = document.getElementById('user_id').value;
      const lessonId = document.getElementById('lesson_id').value;
      const duplicateWarning = document.getElementById('duplicateWarning');
      
      if (!userId || !lessonId) {
        duplicateWarning.classList.add('d-none');
        return;
      }
      
      // Check if a progress record already exists for this user and lesson
      fetch(`/api/check-progress-duplicate/${userId}/${lessonId}`)
        .then(response => response.json())
        .then(data => {
          if (data.exists) {
            duplicateWarning.classList.remove('d-none');
          } else {
            duplicateWarning.classList.add('d-none');
          }
        })
        .catch(error => {
          console.error('Error checking duplicate:', error);
        });
    }
    
    // Update progress overview visualization
    function updateProgressOverview() {
      const userId = document.getElementById('user_id').value;
      const lessonId = document.getElementById('lesson_id').value;
      const progressOverviewDiv = document.getElementById('progressOverview');
      
      if (!userId || !lessonId) {
        progressOverviewDiv.innerHTML = `
          <p class="text-muted mb-0">
            <i class="fas fa-info-circle"></i> 
            Select a user and lesson to see progress information
          </p>
        `;
        return;
      }
      
      progressOverviewDiv.innerHTML = '<div class="spinner-border spinner-border-sm text-primary"></div> Loading progress overview...';
      
      // Fetch combined progress data
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
        
        progressOverviewDiv.innerHTML = `
          <div class="row">
            <div class="col-md-6">
              <h6>User Progress</h6>
              <div class="progress-container">
                <div class="progress-label">
                  <span>Overall Completion</span>
                  <span>${userCompletionRate}%</span>
                </div>
                <div class="progress">
                  <div class="progress-bar" role="progressbar" 
                       style="width: ${userCompletionRate}%; background: linear-gradient(145deg, #9b59b6, #8e44ad);"></div>
                </div>
              </div>
              <div class="mt-3">
                <p class="mb-1"><i class="fas fa-user"></i> <strong>User:</strong> ${userData.user_name || 'N/A'}</p>
                <p class="mb-0"><i class="fas fa-book"></i> <strong>Completed Lessons:</strong> ${userData.completed_lessons}/${userData.total_lessons}</p>
              </div>
            </div>
            <div class="col-md-6">
              <h6>Lesson Statistics</h6>
              <div class="progress-container">
                <div class="progress-label">
                  <span>Lesson Completion Rate</span>
                  <span>${lessonCompletionRate}%</span>
                </div>
                <div class="progress">
                  <div class="progress-bar" role="progressbar" 
                       style="width: ${lessonCompletionRate}%; background: linear-gradient(145deg, #2ecc71, #27ae60);"></div>
                </div>
              </div>
              <div class="mt-3">
                <p class="mb-1"><i class="fas fa-graduation-cap"></i> <strong>Course:</strong> ${lessonData.course_title || 'N/A'}</p>
                <p class="mb-0"><i class="fas fa-users"></i> <strong>Users Completed:</strong> ${lessonData.completed_count}/${lessonData.total_users}</p>
              </div>
            </div>
          </div>
        `;
      })
      .catch(error => {
        console.error('Error:', error);
        progressOverviewDiv.innerHTML = '<div class="alert alert-danger">Failed to load progress overview</div>';
      });
    }
    
    // Save as incomplete
    function saveAsIncomplete() {
      document.getElementById('completed').checked = false;
      toggleCompletionDate();
      document.getElementById('progressForm').submit();
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
    
    // Initialize from URL parameters
    const urlParams = new URLSearchParams(window.location.search);
    const userIdParam = urlParams.get('user_id');
    const lessonIdParam = urlParams.get('lesson_id');
    
    if (userIdParam) {
      document.getElementById('user_id').value = userIdParam;
      setTimeout(() => loadUserProgress(), 100);
    }
    
    if (lessonIdParam) {
      document.getElementById('lesson_id').value = lessonIdParam;
      setTimeout(() => loadLessonInfo(), 100);
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
</script>

@stop