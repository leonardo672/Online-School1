@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="card-title mb-0">
        <i class="fas fa-edit"></i> Edit Category: {{ $category->name }}
      </h4>
      <a href="{{ url('categories/' . $category->slug) }}" class="btn btn-view">
        <i class="fas fa-eye"></i> View
      </a>
    </div>
  </div>
  
  <div class="card-body">
    
    @if ($errors->any())
      <div class="alert alert-danger alert-school">
          <strong>Whoops!</strong> There were some problems with your input.
          <ul class="mb-0 mt-2">
              @foreach ($errors->all() as $error)
                  <li>{{ $error }}</li>
              @endforeach
          </ul>
      </div>
    @endif

    <form action="{{ url('categories/' . $category->slug) }}" method="POST">
      @csrf
      @method("PATCH")

      <div class="row">
        <div class="col-md-8 mb-3">
          <label class="form-label">Category Name *</label>
          <input type="text" name="name" class="form-control"
                 value="{{ old('name', $category->name) }}" required>
        </div>

        <div class="col-md-4 mb-3">
          <label class="form-label">Slug *</label>
          <input type="text" name="slug" class="form-control"
                 value="{{ old('slug', $category->slug) }}" required>
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Description</label>
        <textarea name="description" class="form-control" rows="4">{{ old('description', $category->description) }}</textarea>
      </div>

      <div class="mb-3">
        <label class="form-label">Icon</label>
        <select name="icon" class="form-select">
          <option value="">None</option>
          <option value="fas fa-code" {{ old('icon',$category->icon)=='fas fa-code'?'selected':'' }}>Programming</option>
          <option value="fas fa-chart-bar" {{ old('icon',$category->icon)=='fas fa-chart-bar'?'selected':'' }}>Data</option>
          <option value="fas fa-paint-brush" {{ old('icon',$category->icon)=='fas fa-paint-brush'?'selected':'' }}>Design</option>
          <option value="fas fa-language" {{ old('icon',$category->icon)=='fas fa-language'?'selected':'' }}>Languages</option>
        </select>
      </div>

      <div class="mb-3">
        <label class="form-label">Color</label>
        <input type="color" name="color" class="form-control form-control-color"
               value="{{ old('color', $category->color ?? '#3498db') }}">
      </div>

      <div class="d-flex justify-content-between">
        <a href="{{ url('categories') }}" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back
        </a>
        <button type="submit" class="btn btn-custom">
          <i class="fas fa-save"></i> Update Category
        </button>
      </div>
    </form>
  </div>
</div>

{{-- Courses --}}
@if($category->courses()->count())
<div class="card mt-4">
  <div class="card-header">
    <h5><i class="fas fa-graduation-cap"></i> Courses in this category</h5>
  </div>
  <div class="card-body">
    <table class="table table-sm">
      @foreach($category->courses()->latest()->take(5)->get() as $course)
      <tr>
        <td>{{ $course->title }}</td>
        <td>
          <a href="{{ url('courses/' . $course->id) }}" class="btn btn-view btn-sm">
            <i class="fas fa-eye"></i>
          </a>
        </td>
      </tr>
      @endforeach
    </table>

    <a href="{{ url('courses?category=' . $category->slug) }}" class="btn btn-outline-primary btn-sm">
      View All Courses
    </a>
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