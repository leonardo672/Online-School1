@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-graduation-cap"></i> Create New Course
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

    <form action="{{ url('courses') }}" method="post">
        @csrf
        
        <div class="row">
          <div class="col-md-6 mb-3">
            <label for="title" class="form-label">Course Title *</label>
            <input type="text" name="title" id="title" class="form-control" 
                   value="{{ old('title') }}" required placeholder="Enter course title">
            <div class="form-text">Make it descriptive and engaging</div>
          </div>

          <div class="col-md-6 mb-3">
            <label for="category_id" class="form-label">Category *</label>
            <select name="category_id" id="category_id" class="form-select" required>
              <option value="">Select Category</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
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
                <option value="{{ $instructor->id }}" {{ old('instructor_id') == $instructor->id ? 'selected' : '' }}>
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
                <option value="{{ $level }}" {{ old('level') == $level ? 'selected' : '' }}>
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
                   value="{{ old('price') }}" required min="0" step="0.01" placeholder="0.00">
            <div class="form-text">Enter 0 for free course</div>
          </div>

          <div class="col-md-6 mb-3">
            <label class="form-label">Course Status</label>
            <div class="form-check mt-2">
              <input class="form-check-input" type="checkbox" name="published" id="published" 
                     value="1" {{ old('published') ? 'checked' : '' }}>
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
                    rows="5" required placeholder="Describe what students will learn in this course">{{ old('description') }}</textarea>
          <div class="form-text">Be detailed about course content and learning outcomes</div>
        </div>

        <div class="d-flex justify-content-between">
          <a href="{{ url('courses') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Courses
          </a>
          <button type="submit" class="btn btn-custom">
            <i class="fas fa-save"></i> Create Course
          </button>
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
  
  .form-check-input:checked {
    background-color: #3498db;
    border-color: #3498db;
  }
</style>

<script>
  // Auto-generate slug based on title
  document.getElementById('title').addEventListener('input', function() {
    const title = this.value;
    if (title.length > 0) {
      // Show slug preview (you could implement actual slug generation here)
      console.log('Title changed:', title);
    }
  });
  
  // Format price input
  document.getElementById('price').addEventListener('blur', function() {
    let value = parseFloat(this.value);
    if (!isNaN(value)) {
      this.value = value.toFixed(2);
    }
  });
</script>

@stop