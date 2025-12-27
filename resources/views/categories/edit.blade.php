@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="card-title mb-0">
        <i class="fas fa-edit"></i> Edit Category: {{ $category->name }}
      </h4>
      <a href="{{ url('categories/' . $category->id) }}" class="btn btn-view">
        <i class="fas fa-eye"></i> View
      </a>
    </div>
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

    <form action="{{ url('categories/' . $category->id) }}" method="post">
      @csrf
      @method("PATCH")
      <input type="hidden" name="id" id="id" value="{{ $category->id }}" />
      
      <div class="row">
        <div class="col-md-8 mb-3">
          <label for="name" class="form-label">Category Name *</label>
          <input type="text" name="name" id="name" class="form-control" 
                 value="{{ old('name', $category->name) }}" required placeholder="Enter category name">
          <div class="form-text">Choose a clear and descriptive name for the category</div>
        </div>

        <div class="col-md-4 mb-3">
          <label for="slug" class="form-label">Slug *</label>
          <input type="text" name="slug" id="slug" class="form-control" 
                 value="{{ old('slug', $category->slug) }}" required>
          <div class="form-text">URL-friendly version of the name</div>
        </div>
      </div>

      <div class="mb-3">
        <label for="description" class="form-label">Category Description</label>
        <textarea name="description" id="description" class="form-control" 
                  rows="4" placeholder="Briefly describe what this category includes">{{ old('description', $category->description) }}</textarea>
        <div class="form-text">Optional: Add a description to help organize courses</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Category Icon</label>
        <div class="row">
          <div class="col-md-6">
            <select name="icon" id="icon" class="form-select">
              <option value="">Select an icon</option>
              <option value="fas fa-code" {{ (old('icon', $category->icon) == 'fas fa-code') ? 'selected' : '' }}>💻 Programming</option>
              <option value="fas fa-chart-bar" {{ (old('icon', $category->icon) == 'fas fa-chart-bar') ? 'selected' : '' }}>📊 Data Science</option>
              <option value="fas fa-paint-brush" {{ (old('icon', $category->icon) == 'fas fa-paint-brush') ? 'selected' : '' }}>🎨 Design</option>
              <option value="fas fa-briefcase" {{ (old('icon', $category->icon) == 'fas fa-briefcase') ? 'selected' : '' }}>💼 Business</option>
              <option value="fas fa-music" {{ (old('icon', $category->icon) == 'fas fa-music') ? 'selected' : '' }}>🎵 Music</option>
              <option value="fas fa-language" {{ (old('icon', $category->icon) == 'fas fa-language') ? 'selected' : '' }}>🌐 Languages</option>
              <option value="fas fa-heartbeat" {{ (old('icon', $category->icon) == 'fas fa-heartbeat') ? 'selected' : '' }}>❤️ Health</option>
              <option value="fas fa-camera" {{ (old('icon', $category->icon) == 'fas fa-camera') ? 'selected' : '' }}>📸 Photography</option>
            </select>
          </div>
            <div class="col-md-6">
                <div class="icon-preview p-3 text-center" id="iconPreview">
                    @if($category->icon)
                        <i class="{{ $category->icon }} fa-2x" @if($category->color) style="color: {{ $category->color }};" @endif></i>
                    @else
                    <i class="fas fa-folder fa-2x text-muted"></i>
                    @endif
                    <p class="small mt-2">Icon Preview</p>
                </div>
            </div>
        </div>
        <div class="form-text">Choose an icon to represent this category visually</div>
      </div>

      <div class="mb-3">
        <label for="color" class="form-label">Category Color</label>
        <div class="input-group">
          <input type="color" name="color" id="color" class="form-control form-control-color" 
                 value="{{ old('color', $category->color ?: '#3498db') }}" title="Choose a color for this category">
          <input type="text" id="colorText" class="form-control" 
                 value="{{ old('color', $category->color ?: '#3498db') }}" placeholder="#3498db">
        </div>
        <div class="form-text">Select a color to distinguish this category</div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="{{ url('categories') }}" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back to Categories
        </a>
        <div>
          <button type="reset" class="btn btn-outline-secondary me-2">
            <i class="fas fa-redo"></i> Reset
          </button>
          <button type="submit" class="btn btn-custom">
            <i class="fas fa-save"></i> Update Category
          </button>
        </div>
      </div>
    </form>
   
  </div>
</div>

<!-- Category Statistics Card -->
<div class="card mt-4">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-chart-pie"></i> Category Statistics
    </h5>
  </div>
  <div class="card-body">
    <div class="row text-center">
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Total Courses</h6>
          <h3 class="text-primary">
            @php
                // Safely get course count
                $totalCourses = 0;
                if (method_exists($category, 'courses')) {
                    $totalCourses = $category->courses()->count();
                }
            @endphp
            {{ $totalCourses }}
          </h3>
          <small>Courses in this category</small>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Published</h6>
          <h3 class="text-success">
            @php
                // Safely get published courses count
                $publishedCourses = 0;
                if (method_exists($category, 'courses')) {
                    $publishedCourses = $category->courses()->where('published', true)->count();
                }
            @endphp
            {{ $publishedCourses }}
          </h3>
          <small>Active courses</small>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Total Students</h6>
          <h3 class="text-info">0</h3>
          <small>Enrolled students</small>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Created</h6>
          <h5 class="text-secondary">
            {{ $category->created_at->format('M d, Y') }}
          </h5>
          <small>Creation date</small>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Recent Courses in This Category -->
@php
    // Safely check if category has courses
    $hasCourses = false;
    $recentCourses = collect([]);
    $courseCount = 0;
    
    if (method_exists($category, 'courses')) {
        $courseCount = $category->courses()->count();
        $hasCourses = $courseCount > 0;
        if ($hasCourses) {
            $recentCourses = $category->courses()->latest()->take(5)->get();
        }
    }
@endphp

@if($hasCourses)
<div class="card mt-4">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-graduation-cap"></i> Recent Courses in This Category
    </h5>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table class="table table-sm table-hover">
        <thead>
          <tr>
            <th>Course</th>
            <th>Instructor</th>
            <th>Price</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          @foreach($recentCourses as $course)
            <tr>
              <td>
                <strong>{{ $course->title }}</strong>
                <br>
                <small class="text-muted">{{ Str::limit($course->description, 30) }}</small>
              </td>
              <td>
                @if($course->instructor)
                  {{ $course->instructor->name }}
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
                @if($course->published)
                  <span class="badge badge-published">Published</span>
                @else
                  <span class="badge badge-draft">Draft</span>
                @endif
              </td>
              <td>
                <a href="{{ url('courses/' . $course->id) }}" class="btn btn-view btn-sm">
                  <i class="fas fa-eye"></i>
                </a>
              </td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
    @if($courseCount > 5)
      <div class="text-center mt-3">
        <a href="{{ url('courses?category=' . $category->slug) }}" class="btn btn-outline-primary btn-sm">
          View All {{ $courseCount }} Courses
        </a>
      </div>
    @endif
  </div>
</div>
@endif

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
  
  .btn-custom {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
  }
  
  .btn-custom:hover {
    background: linear-gradient(145deg, #27ae60, #219653);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(46, 204, 113, 0.3);
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
  
  .btn-secondary {
    background: linear-gradient(145deg, #6c757d, #495057);
    color: white;
    border: none;
  }
  
  .btn-secondary:hover {
    background: linear-gradient(145deg, #495057, #343a40);
    color: white;
  }
  
  .icon-preview {
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 2px dashed #dee2e6;
    min-height: 100px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  }
  
  .form-control-color {
    height: 45px;
    width: 70px;
    padding: 5px;
    border-radius: 8px;
  }
  
  .stat-card {
    background: #f8f9fa;
    border: 1px solid #e9ecef;
    transition: all 0.3s ease;
  }
  
  .stat-card:hover {
    background: #e9ecef;
    transform: translateY(-3px);
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  }
  
  .badge-published, .badge-draft {
    padding: 5px 10px;
    border-radius: 15px;
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
  
  .alert-school {
    border-left: 4px solid #2ecc71;
    background-color: #f0f9f4;
  }
  
  .table-sm th, .table-sm td {
    padding: 10px;
  }
</style>

<script>
  // Auto-generate slug based on name
  document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slugInput = document.getElementById('slug');
    const currentSlug = '{{ $category->slug }}';
    
    if (name.length > 0) {
      // Generate slug from name
      let slug = name.toLowerCase()
        .replace(/[^\w\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/--+/g, '-')
        .trim();
      
      // Only update if slug hasn't been manually modified or matches old slug
      if (slugInput.value === currentSlug || !slugInput.value) {
        slugInput.value = slug;
      }
    }
  });

  // Icon preview functionality
  const updateIconPreview = function() {
    const iconClass = document.getElementById('icon').value;
    const color = document.getElementById('color').value;
    const preview = document.getElementById('iconPreview');
    
    if (iconClass) {
      preview.innerHTML = `<i class="${iconClass} fa-2x" style="color: ${color};"></i>
                           <p class="small mt-2">Icon Preview</p>`;
    } else {
      preview.innerHTML = `<i class="fas fa-folder fa-2x text-muted"></i>
                           <p class="small mt-2">Icon Preview</p>`;
    }
  };

  document.getElementById('icon').addEventListener('change', updateIconPreview);
  document.getElementById('color').addEventListener('input', updateIconPreview);

  // Color picker with text input sync
  document.getElementById('color').addEventListener('input', function() {
    document.getElementById('colorText').value = this.value;
    updateIconPreview();
  });

  document.getElementById('colorText').addEventListener('input', function() {
    const colorValue = this.value;
    if (/^#[0-9A-F]{6}$/i.test(colorValue)) {
      document.getElementById('color').value = colorValue;
      updateIconPreview();
    }
  });

  // Show confirmation before leaving unsaved changes
  let formChanged = false;
  const formInputs = document.querySelectorAll('form input, form select, form textarea');
  
  formInputs.forEach(input => {
    input.addEventListener('input', () => {
      formChanged = true;
    });
  });
  
  window.addEventListener('beforeunload', function(e) {
    if (formChanged) {
      e.preventDefault();
      e.returnValue = '';
    }
  });

  // Form submit validation
  document.querySelector('form').addEventListener('submit', function(e) {
    const nameInput = document.getElementById('name');
    const slugInput = document.getElementById('slug');
    
    let isValid = true;
    
    if (!nameInput.value.trim()) {
      nameInput.classList.add('is-invalid');
      isValid = false;
    }
    
    if (!slugInput.value.trim()) {
      slugInput.classList.add('is-invalid');
      isValid = false;
    }
    
    if (!isValid) {
      e.preventDefault();
      return false;
    }
    
    return true;
  });

  // Remove invalid class on input
  formInputs.forEach(input => {
    input.addEventListener('input', function() {
      this.classList.remove('is-invalid');
    });
  });
</script>

@stop