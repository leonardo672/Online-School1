@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-user-plus"></i> Create New Enrollment
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

    <form action="{{ url('enrollments') }}" method="post">
      @csrf
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="user_id" class="form-label">User *</label>
          <select name="user_id" id="user_id" class="form-select" required>
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
          <div class="form-text">Select the user to enroll in a course</div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="course_id" class="form-label">Course *</label>
          <select name="course_id" id="course_id" class="form-select" required>
            <option value="">Select Course</option>
            @foreach($courses ?? [] as $course)
              <option value="{{ $course->id }}" {{ old('course_id') == $course->id ? 'selected' : '' }}>
                {{ $course->title }} ({{ $course->code ?? 'N/A' }})
              </option>
            @endforeach
          </select>
          @if(empty($courses))
            <div class="alert alert-warning mt-2">
              <i class="fas fa-exclamation-triangle"></i> No courses found. 
              <a href="{{ url('courses/create') }}" class="alert-link">Create a course first</a>.
            </div>
          @endif
          <div class="form-text">Select the course for enrollment</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="enrolled_at" class="form-label">Enrollment Date *</label>
          <input type="datetime-local" name="enrolled_at" id="enrolled_at" class="form-control" 
                 value="{{ old('enrolled_at', now()->format('Y-m-d\TH:i')) }}" required>
          <div class="form-text">Date and time when the user enrolled</div>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Enrollment Status</label>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="is_active" id="is_active" 
                   value="1" {{ old('is_active', true) ? 'checked' : '' }}>
            <label class="form-check-label" for="is_active">
              Mark as active enrollment
            </label>
            <div class="form-text">Uncheck to mark as inactive/pending</div>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label for="notes" class="form-label">Notes (Optional)</label>
        <textarea name="notes" id="notes" class="form-control" rows="3" 
                  placeholder="Any additional notes about this enrollment">{{ old('notes') }}</textarea>
        <div class="form-text">Optional notes about the enrollment</div>
      </div>

      <div class="alert alert-info">
        <h6><i class="fas fa-info-circle"></i> Enrollment Information</h6>
        <ul class="mb-0">
          <li>Enrollments link users to courses they're registered for</li>
          <li>Each user can be enrolled in multiple courses</li>
          <li>Each course can have multiple enrolled users</li>
          <li>The enrollment date determines when the user started the course</li>
        </ul>
      </div>

      <div class="d-flex justify-content-between">
        <a href="{{ url('enrollments') }}" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back to Enrollments
        </a>
        <div>
          <button type="reset" class="btn btn-outline-secondary me-2">
            <i class="fas fa-redo"></i> Reset
          </button>
          <button type="submit" class="btn btn-custom">
            <i class="fas fa-user-plus"></i> Create Enrollment
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
    max-width: 800px;
    margin: 0 auto;
  }
  
  .card-header {
    background: linear-gradient(145deg, #3498db, #2980b9);
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
    border-color: #3498db;
    box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
  }
  
  .btn-custom {
    background: linear-gradient(145deg, #3498db, #2980b9);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
  }
  
  .btn-custom:hover {
    background: linear-gradient(145deg, #2980b9, #1c5a7a);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
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
    background-color: #3498db;
    border-color: #3498db;
  }
  
  .alert-school {
    border-left: 4px solid #3498db;
    background-color: #f0f9ff;
  }
  
  .alert-info {
    background-color: #e7f3ff;
    border-color: #b6d4fe;
    color: #084298;
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
  
  .form-text {
    font-size: 0.875rem;
    color: #6c757d;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Set default enrollment date to current date/time
    const enrolledAtInput = document.getElementById('enrolled_at');
    if (!enrolledAtInput.value) {
      const now = new Date();
      const year = now.getFullYear();
      const month = String(now.getMonth() + 1).padStart(2, '0');
      const day = String(now.getDate()).padStart(2, '0');
      const hours = String(now.getHours()).padStart(2, '0');
      const minutes = String(now.getMinutes()).padStart(2, '0');
      enrolledAtInput.value = `${year}-${month}-${day}T${hours}:${minutes}`;
    }
    
    // Form submission validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
      const userIdSelect = document.getElementById('user_id');
      const courseIdSelect = document.getElementById('course_id');
      const enrolledAtInput = document.getElementById('enrolled_at');
      
      let isValid = true;
      
      if (!userIdSelect.value) {
        userIdSelect.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!courseIdSelect.value) {
        courseIdSelect.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!enrolledAtInput.value) {
        enrolledAtInput.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!isValid) {
        e.preventDefault();
        // Show error message
        const errorDiv = document.createElement('div');
        errorDiv.className = 'alert alert-danger mt-3';
        errorDiv.innerHTML = '<strong>Error!</strong> Please fill in all required fields marked with *';
        form.prepend(errorDiv);
        
        // Scroll to top of form
        window.scrollTo({ top: 0, behavior: 'smooth' });
        
        return false;
      }
      
      return true;
    });
    
    // Check for duplicate enrollment
    const userIdSelect = document.getElementById('user_id');
    const courseIdSelect = document.getElementById('course_id');
    
    function checkDuplicateEnrollment() {
      const userId = userIdSelect.value;
      const courseId = courseIdSelect.value;
      
      if (userId && courseId) {
        // This would typically be an AJAX call to check for existing enrollment
        // For now, we'll just show a warning if both are selected
        console.log(`Checking enrollment for user ${userId} in course ${courseId}`);
        
        // You would implement actual duplicate checking here
        // Example AJAX call:
        /*
        fetch(`/api/check-enrollment/${userId}/${courseId}`)
          .then(response => response.json())
          .then(data => {
            if (data.exists) {
              showDuplicateWarning();
            }
          });
        */
      }
    }
    
    userIdSelect.addEventListener('change', checkDuplicateEnrollment);
    courseIdSelect.addEventListener('change', checkDuplicateEnrollment);
    
    // Function to show duplicate warning
    function showDuplicateWarning() {
      const existingWarning = document.querySelector('.duplicate-warning');
      if (existingWarning) {
        existingWarning.remove();
      }
      
      const warningDiv = document.createElement('div');
      warningDiv.className = 'alert alert-warning duplicate-warning mt-2';
      warningDiv.innerHTML = `
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Warning:</strong> This user is already enrolled in this course.
      `;
      
      const formGroup = userIdSelect.closest('.mb-3');
      formGroup.appendChild(warningDiv);
    }
    
    // Remove invalid class on input/change
    const inputs = document.querySelectorAll('input, select, textarea');
    inputs.forEach(input => {
      input.addEventListener('input', function() {
        this.classList.remove('is-invalid');
      });
      input.addEventListener('change', function() {
        this.classList.remove('is-invalid');
      });
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
</script>

@stop