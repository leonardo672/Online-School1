@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-folder-plus"></i> Create New Category
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

    <form action="{{ url('categories') }}" method="post">
        @csrf
        
        <div class="row">
          <div class="col-md-8 mb-3">
            <label for="name" class="form-label">Category Name *</label>
            <input type="text" name="name" id="name" class="form-control" 
                   value="{{ old('name') }}" required placeholder="Enter category name (e.g., Web Development, Data Science)">
            <div class="form-text">Choose a clear and descriptive name for the category</div>
          </div>

          <div class="col-md-4 mb-3">
            <label for="slug" class="form-label">Slug</label>
            <input type="text" name="slug" id="slug" class="form-control" 
                   value="{{ old('slug') }}" placeholder="Auto-generated slug">
            <div class="form-text">URL-friendly version of the name (auto-generated)</div>
          </div>
        </div>

        <div class="mb-3">
          <label for="description" class="form-label">Category Description</label>
          <textarea name="description" id="description" class="form-control" 
                    rows="4" placeholder="Briefly describe what this category includes">{{ old('description') }}</textarea>
          <div class="form-text">Optional: Add a description to help organize courses</div>
        </div>

        <div class="mb-3">
          <label class="form-label">Category Icon</label>
          <div class="row">
            <div class="col-md-6">
              <select name="icon" id="icon" class="form-select">
                <option value="">Select an icon</option>
                <option value="fas fa-code" {{ old('icon') == 'fas fa-code' ? 'selected' : '' }}>💻 Programming</option>
                <option value="fas fa-chart-bar" {{ old('icon') == 'fas fa-chart-bar' ? 'selected' : '' }}>📊 Data Science</option>
                <option value="fas fa-paint-brush" {{ old('icon') == 'fas fa-paint-brush' ? 'selected' : '' }}>🎨 Design</option>
                <option value="fas fa-briefcase" {{ old('icon') == 'fas fa-briefcase' ? 'selected' : '' }}>💼 Business</option>
                <option value="fas fa-music" {{ old('icon') == 'fas fa-music' ? 'selected' : '' }}>🎵 Music</option>
                <option value="fas fa-language" {{ old('icon') == 'fas fa-language' ? 'selected' : '' }}>🌐 Languages</option>
                <option value="fas fa-heartbeat" {{ old('icon') == 'fas fa-heartbeat' ? 'selected' : '' }}>❤️ Health</option>
                <option value="fas fa-camera" {{ old('icon') == 'fas fa-camera' ? 'selected' : '' }}>📸 Photography</option>
              </select>
            </div>
            <div class="col-md-6">
              <div class="icon-preview p-3 text-center" id="iconPreview">
                <i class="fas fa-folder fa-2x text-muted"></i>
                <p class="small mt-2">Icon Preview</p>
              </div>
            </div>
          </div>
          <div class="form-text">Choose an icon to represent this category visually</div>
        </div>

        <div class="mb-3">
          <label for="color" class="form-label">Category Color</label>
          <input type="color" name="color" id="color" class="form-control form-control-color" 
                 value="{{ old('color', '#3498db') }}" title="Choose a color for this category">
          <div class="form-text">Select a color to distinguish this category (default: blue)</div>
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
              <i class="fas fa-plus-circle"></i> Create Category
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
  }
  
  .form-control-color {
    height: 45px;
    width: 100%;
    padding: 5px;
    border-radius: 8px;
  }
  
  .alert-school {
    border-left: 4px solid #2ecc71;
    background-color: #f0f9f4;
  }
</style>

<script>
  // Auto-generate slug based on name
  document.getElementById('name').addEventListener('input', function() {
    const name = this.value;
    const slugInput = document.getElementById('slug');
    
    if (name.length > 0) {
      // Generate slug from name
      let slug = name.toLowerCase()
        .replace(/[^\w\s-]/g, '') // Remove special characters
        .replace(/\s+/g, '-')     // Replace spaces with hyphens
        .replace(/--+/g, '-')     // Replace multiple hyphens with single
        .trim();
      
      // Update slug field if empty or matches auto-generation
      if (!slugInput.value || slugInput.value === slug) {
        slugInput.value = slug;
      }
    }
  });

  // Update slug field independently if user types
  document.getElementById('slug').addEventListener('input', function() {
    // Keep the user's custom slug
  });

  // Icon preview functionality
  document.getElementById('icon').addEventListener('change', function() {
    const iconClass = this.value;
    const preview = document.getElementById('iconPreview');
    
    if (iconClass) {
      preview.innerHTML = `<i class="${iconClass} fa-2x" style="color: #2ecc71;"></i>
                           <p class="small mt-2">Icon Preview</p>`;
    } else {
      preview.innerHTML = `<i class="fas fa-folder fa-2x text-muted"></i>
                           <p class="small mt-2">Icon Preview</p>`;
    }
  });

  // Color preview functionality
  document.getElementById('color').addEventListener('input', function() {
    const color = this.value;
    const iconPreview = document.querySelector('#iconPreview i');
    
    if (iconPreview) {
      iconPreview.style.color = color;
    }
  });

  // Form validation
  document.querySelector('form').addEventListener('submit', function(e) {
    const nameInput = document.getElementById('name');
    
    if (!nameInput.value.trim()) {
      e.preventDefault();
      nameInput.focus();
      nameInput.classList.add('is-invalid');
      return false;
    }
    
    return true;
  });

  // Remove invalid class on input
  document.getElementById('name').addEventListener('input', function() {
    this.classList.remove('is-invalid');
  });
</script>

@stop