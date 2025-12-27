@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-edit"></i> Edit Course: {{ $course->title }}
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

    <form action="{{ url('courses/' . $course->id) }}" method="post">
      @csrf
      @method("PATCH")
      <input type="hidden" name="id" id="id" value="{{ $course->id }}" />
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="title" class="form-label">Course Title *</label>
          <input type="text" name="title" id="title" class="form-control" 
                 value="{{ old('title', $course->title) }}" required placeholder="Enter course title">
          <div class="form-text">Make it descriptive and engaging</div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="category_id" class="form-label">Category *</label>
          <select name="category_id" id="category_id" class="form-select" required>
            <option value="">Select Category</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}" {{ (old('category_id', $course->category_id) == $category->id) ? 'selected' : '' }}>
                {{ $category->name }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="instructor_id" class="form-label">Instructor *</label>
          <select name="instructor_id" id="instructor_id" class="form-select" required>
            <option value="">Select Instructor</option>
            @foreach($instructors as $instructor)
              <option value="{{ $instructor->id }}" {{ (old('instructor_id', $course->instructor_id) == $instructor->id) ? 'selected' : '' }}>
                {{ $instructor->name }} ({{ $instructor->email }})
              </option>
            @endforeach
          </select>
        </div>

        <div class="col-md-6 mb-3">
          <label for="level" class="form-label">Difficulty Level *</label>
          <select name="level" id="level" class="form-select" required>
            <option value="">Select Level</option>
            @foreach($levels as $level)
              <option value="{{ $level }}" {{ (old('level', $course->level) == $level) ? 'selected' : '' }}>
                {{ ucfirst($level) }}
              </option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="price" class="form-label">Price ($) *</label>
          <input type="number" name="price" id="price" class="form-control" 
                 value="{{ old('price', $course->price) }}" required min="0" step="0.01" placeholder="0.00">
          <div class="form-text">Enter 0 for free course</div>
        </div>

        <div class="col-md-6 mb-3">
          <label class="form-label">Course Status</label>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" name="published" id="published" 
                   value="1" {{ old('published', $course->published) ? 'checked' : '' }}>
            <label class="form-check-label" for="published">
              Publish this course
            </label>
            <div class="form-text">Leave unchecked to save as draft</div>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label for="description" class="form-label">Course Description *</label>
        <textarea name="description" id="description" class="form-control" 
                  rows="5" required placeholder="Describe what students will learn in this course">{{ old('description', $course->description) }}</textarea>
        <div class="form-text">Be detailed about course content and learning outcomes</div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="{{ url('courses') }}" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back to Courses
        </a>
        <div>
          <a href="{{ url('courses/' . $course->id) }}" class="btn btn-info">
            <i class="fas fa-eye"></i> View
          </a>
          <button type="submit" class="btn btn-custom">
            <i class="fas fa-save"></i> Update Course
          </button>
        </div>
      </div>
    </form>
   
  </div>
</div>

<!-- Course Statistics Card -->
<div class="card mt-4">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-chart-bar"></i> Course Statistics
    </h5>
  </div>
  <div class="card-body">
    <div class="row text-center">
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Enrollments</h6>
          <h3 class="text-primary">0</h3>
          <small>Total Students</small>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Lessons</h6>
          <h3 class="text-primary">0</h3>
          <small>Total Lessons</small>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Average Rating</h6>
          <h3 class="text-warning">0.0</h3>
          <small>Out of 5</small>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Status</h6>
          <h4>
            @if($course->published)
              <span class="badge badge-published">Published</span>
            @else
              <span class="badge badge-draft">Draft</span>
            @endif
          </h4>
          <small>Current Status</small>
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
    margin-bottom: 20px;
  }
  
  .card-header {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
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
    border-color: #2ecc71;
    box-shadow: 0 0 0 0.25rem rgba(46, 204, 113, 0.25);
  }
  
  .form-check-input:checked {
    background-color: #2ecc71;
    border-color: #2ecc71;
  }
  
  .stat-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
  }
  
  .stat-card:hover {
    background: #e9ecef;
    transform: translateY(-3px);
  }
  
  .badge-published, .badge-draft {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 14px;
  }
</style>

<script>
  // Auto-format price input
  document.getElementById('price').addEventListener('blur', function() {
    let value = parseFloat(this.value);
    if (!isNaN(value)) {
      this.value = value.toFixed(2);
    }
  });
  
  // Show confirmation before leaving unsaved changes
  window.addEventListener('beforeunload', function(e) {
    const form = document.querySelector('form');
    if (form && form.checkValidity()) {
      return undefined;
    }
    e.preventDefault();
    e.returnValue = '';
  });
</script>

@stop