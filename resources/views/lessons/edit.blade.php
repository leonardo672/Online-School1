@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-edit"></i> Edit Lesson: {{ $lesson->title }}
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

    <form action="{{ url('lessons/' . $lesson->id) }}" method="post" id="lessonForm">
      @csrf
      @method('PUT')
      
      <div class="row">
        <div class="col-md-8 mb-3">
          <label for="title" class="form-label">Lesson Title *</label>
          <input type="text" name="title" id="title" class="form-control" 
                 value="{{ old('title', $lesson->title) }}" required 
                 placeholder="Enter lesson title">
          <div class="form-text">A clear, descriptive title for the lesson</div>
          <div class="char-counter">
            <span id="titleCharCount">0</span>/100 characters
          </div>
        </div>

        <div class="col-md-4 mb-3">
          <label for="position" class="form-label">Position *</label>
          <input type="number" name="position" id="position" class="form-control" 
                 value="{{ old('position', $lesson->position) }}" min="1" 
                 placeholder="Position in course">
          <div class="form-text">
            <span id="positionHelp">Current position: {{ $lesson->position }}</span>
            <br>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" 
                    onclick="suggestNextPosition()">
              <i class="fas fa-lightbulb"></i> Suggest Next Position
            </button>
          </div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="course_id" class="form-label">Course *</label>
          <select name="course_id" id="course_id" class="form-select" required 
                  onchange="updatePositionSuggestions()">
            <option value="">Select Course</option>
            @foreach($courses ?? [] as $course)
              <option value="{{ $course->id }}" {{ old('course_id', $lesson->course_id) == $course->id ? 'selected' : '' }}>
                {{ $course->title }} ({{ $course->code ?? 'N/A' }})
              </option>
            @endforeach
          </select>
          @if(empty($courses))
            <div class="alert alert-warning mt-2">
              <i class="fas fa-exclamation-triangle"></i> No courses found.
            </div>
          @endif
          <div class="form-text">
            Current course: <strong>{{ $lesson->course->title ?? 'N/A' }}</strong>
          </div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="video_url" class="form-label">Video URL (Optional)</label>
          <input type="url" name="video_url" id="video_url" class="form-control" 
                 value="{{ old('video_url', $lesson->video_url) }}" 
                 placeholder="https://youtube.com/watch?v=... or https://vimeo.com/...">
          <div class="form-text">
            Supported: YouTube, Vimeo, or direct video URLs
            <span id="videoPreview" class="mt-2 {{ $lesson->video_url ? '' : 'd-none' }}">
              @if($lesson->video_url)
                <div class="video-preview-frame mt-2">
                  @if(str_contains($lesson->video_url, 'youtube.com') || str_contains($lesson->video_url, 'youtu.be'))
                    @php
                      $videoId = '';
                      if (str_contains($lesson->video_url, 'youtube.com/watch?v=')) {
                          $videoId = explode('v=', $lesson->video_url)[1];
                          $ampPos = strpos($videoId, '&');
                          if ($ampPos !== false) $videoId = substr($videoId, 0, $ampPos);
                      } elseif (str_contains($lesson->video_url, 'youtu.be/')) {
                          $videoId = explode('youtu.be/', $lesson->video_url)[1];
                          $ampPos = strpos($videoId, '&');
                          if ($ampPos !== false) $videoId = substr($videoId, 0, $ampPos);
                      }
                    @endphp
                    @if($videoId)
                      <iframe src="https://www.youtube.com/embed/{{ $videoId }}" 
                              allowfullscreen style="width:100%; height:200px; border:none; border-radius:8px;"></iframe>
                    @endif
                  @elseif(str_contains($lesson->video_url, 'vimeo.com'))
                    @php
                      $videoId = explode('vimeo.com/', $lesson->video_url)[1] ?? '';
                    @endphp
                    @if($videoId)
                      <iframe src="https://player.vimeo.com/video/{{ $videoId }}" 
                              allowfullscreen style="width:100%; height:200px; border:none; border-radius:8px;"></iframe>
                    @endif
                  @else
                    <div class="alert alert-info">
                      <i class="fas fa-video"></i> Video URL set but cannot preview
                    </div>
                  @endif
                </div>
              @endif
            </span>
          </div>
          <div class="mt-2">
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="previewVideo()">
              <i class="fas fa-play-circle"></i> Preview Video
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary ms-1" onclick="clearVideoUrl()">
              <i class="fas fa-times-circle"></i> Clear
            </button>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <label for="content" class="form-label">Lesson Content *</label>
        <textarea name="content" id="content" class="form-control" rows="10" required
                  placeholder="Enter the lesson content. You can use HTML for formatting.">{{ old('content', $lesson->content) }}</textarea>
        <div class="form-text">
          Full lesson content. Use HTML tags for formatting
          <div class="char-counter">
            <span id="contentCharCount">0</span>/5000 characters
          </div>
        </div>
        
        <!-- Rich Text Editor Toolbar -->
        <div class="editor-toolbar mt-2">
          <div class="btn-group btn-group-sm" role="group">
            <button type="button" class="btn btn-outline-secondary" onclick="formatText('bold')" title="Bold">
              <i class="fas fa-bold"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="formatText('italic')" title="Italic">
              <i class="fas fa-italic"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="formatText('underline')" title="Underline">
              <i class="fas fa-underline"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="formatText('link')" title="Insert Link">
              <i class="fas fa-link"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="formatText('list')" title="Bulleted List">
              <i class="fas fa-list-ul"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="formatText('code')" title="Code Block">
              <i class="fas fa-code"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="formatText('quote')" title="Blockquote">
              <i class="fas fa-quote-right"></i>
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="showPreview()" title="Preview Content">
              <i class="fas fa-eye"></i> Preview
            </button>
            <button type="button" class="btn btn-outline-secondary" onclick="showOriginal()" title="View Original">
              <i class="fas fa-history"></i> Original
            </button>
          </div>
        </div>
      </div>

      <div class="mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="is_published" id="is_published" 
                 value="1" {{ old('is_published', $lesson->is_published ?? true) ? 'checked' : '' }}>
          <label class="form-check-label" for="is_published">
            Publish this lesson
          </label>
          <div class="form-text">
            @if($lesson->is_published ?? true)
              <span class="text-success"><i class="fas fa-check-circle"></i> Currently published</span>
            @else
              <span class="text-warning"><i class="fas fa-clock"></i> Currently in draft</span>
            @endif
          </div>
        </div>
      </div>

      <!-- Lesson Stats & Info -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6><i class="fas fa-chart-bar"></i> Lesson Statistics</h6>
              <div class="row">
                <div class="col-6">
                  <small class="text-muted">Content Length</small>
                  <h5 class="mb-0">{{ strlen($lesson->content) }} chars</h5>
                </div>
                <div class="col-6">
                  <small class="text-muted">Created</small>
                  <h5 class="mb-0">{{ $lesson->created_at->format('M d, Y') }}</h5>
                </div>
              </div>
              <div class="row mt-2">
                <div class="col-6">
                  <small class="text-muted">Last Updated</small>
                  <h5 class="mb-0">{{ $lesson->updated_at->format('M d, Y') }}</h5>
                </div>
                <div class="col-6">
                  <small class="text-muted">Position</small>
                  <h5 class="mb-0">
                    <span class="badge bg-primary">{{ $lesson->position }}</span>
                  </h5>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6><i class="fas fa-info-circle"></i> Course Information</h6>
              <div class="d-flex align-items-center">
                <div class="course-icon me-3">
                  <i class="fas fa-book fa-2x text-primary"></i>
                </div>
                <div>
                  <h5 class="mb-1">{{ $lesson->course->title ?? 'No Course' }}</h5>
                  <p class="text-muted mb-1 small">
                    {{ $lesson->course->code ?? 'N/A' }}
                    @if($lesson->course)
                      • {{ $lesson->course->lessons_count ?? 0 }} lessons
                    @endif
                  </p>
                  @if($lesson->course)
                    <a href="{{ url('/courses/' . $lesson->course_id) }}" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-external-link-alt"></i> View Course
                    </a>
                  @endif
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <div>
          <a href="{{ url('lessons') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Lessons
          </a>
          <a href="{{ url('lessons/' . $lesson->id) }}" class="btn btn-view ms-2">
            <i class="fas fa-eye"></i> View Lesson
          </a>
        </div>
        <div>
          <button type="button" class="btn btn-outline-secondary me-2" onclick="resetToOriginal()">
            <i class="fas fa-history"></i> Reset to Original
          </button>
          <button type="submit" class="btn btn-update">
            <i class="fas fa-save"></i> Update Lesson
          </button>
        </div>
      </div>
    </form>
   
  </div>
</div>

<!-- Preview Modal -->
<div class="modal fade" id="previewModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Content Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="previewContent">
        <!-- Preview content will be inserted here -->
      </div>
    </div>
  </div>
</div>

<!-- Original Content Modal -->
<div class="modal fade" id="originalModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Original Content</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="original-content-display">
          {!! $lesson->content !!}
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="restoreOriginalContent()">
          <i class="fas fa-undo"></i> Restore This Content
        </button>
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
    max-width: 1000px;
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
    background-color: #2ecc71;
    border-color: #2ecc71;
  }
  
  .alert-school {
    border-left: 4px solid #2ecc71;
    background-color: #f0f9fa;
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
  
  .editor-toolbar {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
  }
  
  .editor-toolbar .btn {
    padding: 5px 10px;
    margin: 0 2px;
  }
  
  .bg-light {
    background-color: #f8f9fa !important;
  }
  
  .video-preview-frame {
    width: 100%;
    height: 200px;
    background-color: #000;
    border-radius: 8px;
    overflow: hidden;
    margin-top: 10px;
  }
  
  .course-icon {
    width: 50px;
    text-align: center;
  }
  
  .modal-content {
    border-radius: 12px;
    border: none;
  }
  
  .modal-header {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
    color: white;
    border-bottom: none;
  }
  
  .modal-body {
    padding: 20px;
    max-height: 70vh;
    overflow-y: auto;
  }
  
  .original-content-display {
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
  }
  
  .badge {
    padding: 8px 12px;
    border-radius: 20px;
    font-weight: 500;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Store original values for reset functionality
    const originalValues = {
      title: '{{ addslashes($lesson->title) }}',
      position: '{{ $lesson->position }}',
      course_id: '{{ $lesson->course_id }}',
      video_url: '{{ addslashes($lesson->video_url) }}',
      content: `{!! addslashes($lesson->content) !!}`,
      is_published: {{ $lesson->is_published ?? true ? 'true' : 'false' }}
    };
    
    // Character counters
    const titleInput = document.getElementById('title');
    const contentInput = document.getElementById('content');
    const titleCharCount = document.getElementById('titleCharCount');
    const contentCharCount = document.getElementById('contentCharCount');
    
    function updateCharCount(element, counter) {
      const length = element.value.length;
      counter.textContent = length;
      
      // Update color based on length
      if (element === titleInput) {
        const max = 100;
        if (length > max * 0.9) {
          counter.classList.add('danger');
          counter.classList.remove('warning');
        } else if (length > max * 0.75) {
          counter.classList.add('warning');
          counter.classList.remove('danger');
        } else {
          counter.classList.remove('warning', 'danger');
        }
      } else if (element === contentInput) {
        const max = 5000;
        if (length > max * 0.95) {
          counter.classList.add('danger');
          counter.classList.remove('warning');
        } else if (length > max * 0.85) {
          counter.classList.add('warning');
          counter.classList.remove('danger');
        } else {
          counter.classList.remove('warning', 'danger');
        }
      }
    }
    
    titleInput.addEventListener('input', () => updateCharCount(titleInput, titleCharCount));
    contentInput.addEventListener('input', () => updateCharCount(contentInput, contentCharCount));
    
    // Initialize counts
    updateCharCount(titleInput, titleCharCount);
    updateCharCount(contentInput, contentCharCount);
    
    // Position suggestions
    const positionInput = document.getElementById('position');
    const positionHelp = document.getElementById('positionHelp');
    const currentCourseId = '{{ $lesson->course_id }}';
    
    function updatePositionSuggestions() {
      const courseId = document.getElementById('course_id').value;
      
      if (!courseId) {
        positionHelp.textContent = 'Position in the course sequence';
        return;
      }
      
      // Fetch lesson positions for this course
      fetch(`/api/courses/${courseId}/lessons/positions`)
        .then(response => response.json())
        .then(data => {
          const positions = data.positions || [];
          const currentPos = parseInt(positionInput.value) || 1;
          
          let suggestion = `Available positions: `;
          if (positions.length === 0) {
            suggestion += '1 (first lesson)';
          } else {
            suggestion += positions.join(', ');
            
            // Check if current position is already taken (excluding current lesson)
            if (currentPos > 0 && positions.includes(currentPos) && courseId == currentCourseId) {
              suggestion += ` <span class="text-warning">(Position ${currentPos} is taken)</span>`;
            }
          }
          
          positionHelp.innerHTML = suggestion;
        })
        .catch(error => {
          console.error('Error:', error);
          positionHelp.textContent = 'Position in the course sequence';
        });
    }
    
    // Manual suggestion button
    function suggestNextPosition() {
      const courseId = document.getElementById('course_id').value;
      
      if (!courseId) {
        alert('Please select a course first');
        return;
      }
      
      fetch(`/api/courses/${courseId}/lessons/positions`)
        .then(response => response.json())
        .then(data => {
          const positions = data.positions || [];
          let nextPos = 1;
          
          // Find the next available position
          while (positions.includes(nextPos)) {
            nextPos++;
          }
          
          positionInput.value = nextPos;
          positionHelp.innerHTML = `Next available position: <strong>${nextPos}</strong>`;
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Could not determine next position. Please enter manually.');
        });
    }
    
    // Initialize position suggestions
    setTimeout(updatePositionSuggestions, 100);
    
    // Video URL handling
    const videoUrlInput = document.getElementById('video_url');
    const videoPreview = document.getElementById('videoPreview');
    
    function previewVideo() {
      const url = videoUrlInput.value.trim();
      
      if (!url) {
        alert('Please enter a video URL first');
        return;
      }
      
      videoPreview.classList.remove('d-none');
      
      // Try to embed based on URL
      if (url.includes('youtube.com') || url.includes('youtu.be')) {
        let videoId = '';
        
        if (url.includes('youtube.com/watch?v=')) {
          videoId = url.split('v=')[1];
          const ampersandPosition = videoId.indexOf('&');
          if (ampersandPosition !== -1) {
            videoId = videoId.substring(0, ampersandPosition);
          }
        } else if (url.includes('youtu.be/')) {
          videoId = url.split('youtu.be/')[1];
          const ampersandPosition = videoId.indexOf('&');
          if (ampersandPosition !== -1) {
            videoId = videoId.substring(0, ampersandPosition);
          }
        }
        
        if (videoId) {
          videoPreview.innerHTML = `
            <div class="video-preview-frame">
              <iframe src="https://www.youtube.com/embed/${videoId}" 
                      allowfullscreen style="width:100%; height:200px; border:none;"></iframe>
            </div>
            <small class="text-muted mt-2 d-block">YouTube preview</small>
          `;
        } else {
          videoPreview.innerHTML = '<div class="alert alert-warning">Invalid YouTube URL</div>';
        }
      } else if (url.includes('vimeo.com')) {
        const videoId = url.split('vimeo.com/')[1];
        if (videoId) {
          videoPreview.innerHTML = `
            <div class="video-preview-frame">
              <iframe src="https://player.vimeo.com/video/${videoId}" 
                      allowfullscreen style="width:100%; height:200px; border:none;"></iframe>
            </div>
            <small class="text-muted mt-2 d-block">Vimeo preview</small>
          `;
        } else {
          videoPreview.innerHTML = '<div class="alert alert-warning">Invalid Vimeo URL</div>';
        }
      } else if (url.match(/\.(mp4|webm|ogg)$/i)) {
        videoPreview.innerHTML = `
          <div class="video-preview-frame">
            <video controls style="width: 100%; height: 100%;">
              <source src="${url}" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          </div>
          <small class="text-muted mt-2 d-block">Direct video preview</small>
        `;
      } else {
        videoPreview.innerHTML = `
          <div class="alert alert-info">
            <i class="fas fa-info-circle"></i> URL recognized but cannot preview. 
            The link will work when the lesson is published.
          </div>
        `;
      }
    }
    
    function clearVideoUrl() {
      videoUrlInput.value = '';
      videoPreview.classList.add('d-none');
      videoPreview.innerHTML = '';
    }
    
    // Content formatting functions
    function formatText(command) {
      const textarea = contentInput;
      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      const selectedText = textarea.value.substring(start, end);
      
      let formattedText = selectedText;
      
      switch(command) {
        case 'bold':
          formattedText = `<strong>${selectedText}</strong>`;
          break;
        case 'italic':
          formattedText = `<em>${selectedText}</em>`;
          break;
        case 'underline':
          formattedText = `<u>${selectedText}</u>`;
          break;
        case 'link':
          const url = prompt('Enter URL:', 'https://');
          if (url) {
            formattedText = `<a href="${url}" target="_blank">${selectedText || 'Link'}</a>`;
          } else {
            return; // User cancelled
          }
          break;
        case 'list':
          formattedText = `<ul><li>${selectedText || 'List item'}</li></ul>`;
          break;
        case 'code':
          formattedText = `<pre><code>${selectedText}</code></pre>`;
          break;
        case 'quote':
          formattedText = `<blockquote>${selectedText}</blockquote>`;
          break;
      }
      
      // Replace selected text with formatted text
      textarea.value = textarea.value.substring(0, start) + 
                       formattedText + 
                       textarea.value.substring(end);
      
      // Update cursor position
      textarea.focus();
      textarea.setSelectionRange(start + formattedText.length, start + formattedText.length);
      
      // Update character count
      updateCharCount(contentInput, contentCharCount);
    }
    
    // Content preview
    function showPreview() {
      const content = contentInput.value;
      const previewContent = document.getElementById('previewContent');
      
      if (!content.trim()) {
        previewContent.innerHTML = '<p class="text-muted"><em>No content to preview</em></p>';
      } else {
        previewContent.innerHTML = content;
      }
      
      const modal = new bootstrap.Modal(document.getElementById('previewModal'));
      modal.show();
    }
    
    // Show original content
    function showOriginal() {
      const modal = new bootstrap.Modal(document.getElementById('originalModal'));
      modal.show();
    }
    
    // Restore original content
    function restoreOriginalContent() {
      if (confirm('Are you sure you want to restore the original content? Current changes will be lost.')) {
        contentInput.value = originalValues.content.replace(/\\'/g, "'");
        updateCharCount(contentInput, contentCharCount);
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('originalModal'));
        modal.hide();
        
        // Show success message
        const successAlert = document.createElement('div');
        successAlert.className = 'alert alert-success mt-3';
        successAlert.innerHTML = '<i class="fas fa-check-circle"></i> Original content restored';
        document.querySelector('.card-body').insertBefore(successAlert, document.querySelector('form'));
        
        setTimeout(() => successAlert.remove(), 3000);
      }
    }
    
    // Reset to original values
    function resetToOriginal() {
      if (confirm('Are you sure you want to reset all fields to their original values?')) {
        titleInput.value = originalValues.title.replace(/\\'/g, "'");
        positionInput.value = originalValues.position;
        document.getElementById('course_id').value = originalValues.course_id;
        videoUrlInput.value = originalValues.video_url.replace(/\\'/g, "'");
        contentInput.value = originalValues.content.replace(/\\'/g, "'");
        document.getElementById('is_published').checked = originalValues.is_published;
        
        // Update character counts
        updateCharCount(titleInput, titleCharCount);
        updateCharCount(contentInput, contentCharCount);
        
        // Update position suggestions
        updatePositionSuggestions();
        
        // Update video preview if needed
        if (originalValues.video_url) {
          videoPreview.classList.remove('d-none');
          previewVideo();
        } else {
          videoPreview.classList.add('d-none');
        }
        
        // Show success message
        const successAlert = document.createElement('div');
        successAlert.className = 'alert alert-success mt-3';
        successAlert.innerHTML = '<i class="fas fa-check-circle"></i> All fields reset to original values';
        document.querySelector('.card-body').insertBefore(successAlert, document.querySelector('form'));
        
        setTimeout(() => successAlert.remove(), 3000);
      }
    }
    
    // Form validation
    const form = document.getElementById('lessonForm');
    form.addEventListener('submit', function(e) {
      const title = titleInput.value.trim();
      const content = contentInput.value.trim();
      const courseId = document.getElementById('course_id').value;
      const position = positionInput.value;
      
      let isValid = true;
      
      if (!title) {
        titleInput.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!content) {
        contentInput.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!courseId) {
        document.getElementById('course_id').classList.add('is-invalid');
        isValid = false;
      }
      
      if (!position || position < 1) {
        positionInput.classList.add('is-invalid');
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
    
    // Warn about unsaved changes
    let formChanged = false;
    const formInputs = form.querySelectorAll('input, select, textarea');
    
    formInputs.forEach(input => {
      const originalValue = input.value;
      input.addEventListener('input', () => {
        formChanged = input.value !== originalValue;
      });
    });
    
    window.addEventListener('beforeunload', function(e) {
      if (formChanged) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
      }
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
  });
</script>

@stop