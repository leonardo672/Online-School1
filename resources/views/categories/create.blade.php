@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-folder-plus"></i> Create New Category
    </h4>
  </div>

  <div class="card-body">

    {{-- Validation Errors --}}
    @if ($errors->any())
      <div class="alert alert-danger alert-school">
        <strong>Whoops!</strong> There were some problems with your input.
        <ul class="mt-2 mb-0">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form action="{{ url('categories') }}" method="POST">
      @csrf

      <div class="row">
        <div class="col-md-8 mb-3">
          <label for="name" class="form-label">Category Name *</label>
          <input type="text" name="name" id="name" class="form-control"
                 value="{{ old('name') }}" required
                 placeholder="Enter category name (e.g., Web Development, Data Science)">
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
                  rows="4"
                  placeholder="Briefly describe what this category includes">{{ old('description') }}</textarea>
        <div class="form-text">Optional: Add a description to help organize courses</div>
      </div>

      <div class="mb-3">
        <label class="form-label">Category Icon</label>
        <div class="row">
          <div class="col-md-6">
            <select name="icon" id="icon" class="form-select">
              <option value="">Select an icon</option>
              <option value="fas fa-code">💻 Programming</option>
              <option value="fas fa-chart-bar">📊 Data Science</option>
              <option value="fas fa-paint-brush">🎨 Design</option>
              <option value="fas fa-briefcase">💼 Business</option>
              <option value="fas fa-music">🎵 Music</option>
              <option value="fas fa-language">🌐 Languages</option>
              <option value="fas fa-heartbeat">❤️ Health</option>
              <option value="fas fa-camera">📸 Photography</option>
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
        <input type="color" name="color" id="color"
               class="form-control form-control-color"
               value="{{ old('color', '#3498db') }}">
        <div class="form-text">Select a color to distinguish this category</div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="{{ url('categories') }}" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back
        </a>
        <button type="submit" class="btn btn-custom">
          <i class="fas fa-plus-circle"></i> Create Category
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
  max-width: 800px;
  margin: 0 auto;
}
.card-header {
  background: linear-gradient(145deg, #2ecc71, #27ae60);
  color: white;
  padding: 20px;
}
.btn-custom {
  background: linear-gradient(145deg, #2ecc71, #27ae60);
  color: white;
  border: none;
}
.icon-preview {
  border: 2px dashed #dee2e6;
  border-radius: 8px;
  background: #f8f9fa;
}
.alert-school {
  background: #f0f9f4;
  border-left: 4px solid #2ecc71;
}
</style>

<script>
const nameInput = document.getElementById('name');
const slugInput = document.getElementById('slug');
const iconSelect = document.getElementById('icon');
const iconPreview = document.getElementById('iconPreview');
const colorInput = document.getElementById('color');

let userEditedSlug = false;

// Track manual slug edits
slugInput.addEventListener('input', () => userEditedSlug = true);

// Laravel-compatible slug generation
nameInput.addEventListener('input', function () {
  if (userEditedSlug) return;

  const slug = this.value
    .toLowerCase()
    .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
    .replace(/[^a-z0-9]+/g, "-")
    .replace(/(^-|-$)/g, "");

  slugInput.value = slug;
});

// Icon preview
iconSelect.addEventListener('change', function () {
  if (this.value) {
    iconPreview.innerHTML = `<i class="${this.value} fa-2x"></i><p class="small mt-2">Icon Preview</p>`;
  } else {
    iconPreview.innerHTML = `<i class="fas fa-folder fa-2x text-muted"></i><p class="small mt-2">Icon Preview</p>`;
  }
});

// Color preview
colorInput.addEventListener('input', function () {
  const icon = iconPreview.querySelector('i');
  if (icon) icon.style.color = this.value;
});
</script>

@stop
