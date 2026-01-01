@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-book-open"></i> Lesson: {{ $lesson->title }}
    </h4>
  </div>
  <div class="card-body">
    
    <!-- Action Buttons -->
    <div class="d-flex justify-content-end mb-4">
      <a href="{{ url('lessons') }}" class="btn btn-secondary me-2">
        <i class="fas fa-arrow-left"></i> Back to Lessons
      </a>
      <a href="{{ url('lessons/' . $lesson->id . '/edit') }}" class="btn btn-edit me-2">
        <i class="fas fa-edit"></i> Edit Lesson
      </a>
      <form method="POST" action="{{ url('lessons/' . $lesson->id) }}" accept-charset="UTF-8" style="display:inline">
        @method('DELETE')
        @csrf
        <button type="submit" class="btn btn-delete" title="Delete Lesson" 
                onclick="return confirm('Are you sure you want to delete this lesson?')">
          <i class="fas fa-trash"></i> Delete Lesson
        </button>
      </form>
    </div>

    <!-- Lesson Header Info -->
    <div class="row mb-4">
      <div class="col-md-8">
        <div class="lesson-header">
          <div class="position-badge mb-3">
            <span class="badge bg-primary position-number">
              Lesson {{ $lesson->position }}
            </span>
            @if($lesson->is_published ?? true)
              <span class="badge bg-success ms-2">
                <i class="fas fa-check-circle"></i> Published
              </span>
            @else
              <span class="badge bg-warning ms-2">
                <i class="fas fa-clock"></i> Draft
              </span>
            @endif
          </div>
          <h1 class="lesson-title">{{ $lesson->title }}</h1>
          <div class="lesson-meta text-muted">
            <i class="fas fa-clock"></i> Created: {{ $lesson->created_at->format('F d, Y') }}
            <span class="mx-2">•</span>
            <i class="fas fa-history"></i> Updated: {{ $lesson->updated_at->format('F d, Y') }}
            <span class="mx-2">•</span>
            <i class="fas fa-hashtag"></i> ID: <code>{{ $lesson->id }}</code>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="lesson-stats card bg-light">
          <div class="card-body">
            <h6><i class="fas fa-chart-bar"></i> Lesson Statistics</h6>
            <div class="row text-center">
              <div class="col-4">
                <div class="stat-number">{{ strlen($lesson->content) }}</div>
                <div class="stat-label">Characters</div>
              </div>
              <div class="col-4">
                <div class="stat-number">{{ str_word_count(strip_tags($lesson->content)) }}</div>
                <div class="stat-label">Words</div>
              </div>
              <div class="col-4">
                <div class="stat-number">{{ substr_count(strip_tags($lesson->content), '.') }}</div>
                <div class="stat-label">Sentences</div>
              </div>
            </div>
            <div class="mt-3">
              <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Estimated reading time: {{ ceil(str_word_count(strip_tags($lesson->content)) / 200) }} min
              </small>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Course Information -->
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card course-card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-graduation-cap"></i> Course Information</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-2">
                <div class="course-icon-large">
                  <i class="fas fa-book fa-3x text-primary"></i>
                </div>
              </div>
              <div class="col-md-6">
                <h3 class="mb-2">{{ $lesson->course->title ?? 'No Course' }}</h3>
                <p class="text-muted mb-1">
                  <i class="fas fa-hashtag"></i> Code: {{ $lesson->course->code ?? 'N/A' }}
                  <span class="mx-2">•</span>
                  <i class="fas fa-list-ol"></i> {{ $lesson->course->lessons_count ?? 0 }} lessons
                </p>
                @if($lesson->course->description ?? false)
                  <p class="course-description">{{ Str::limit($lesson->course->description, 150) }}</p>
                @endif
              </div>
              <div class="col-md-4 text-end">
                @if($lesson->course)
                  <a href="{{ url('/courses/' . $lesson->course_id) }}" class="btn btn-view">
                    <i class="fas fa-external-link-alt"></i> View Course
                  </a>
                  <a href="{{ url('/lessons?course_id=' . $lesson->course_id) }}" class="btn btn-outline-primary mt-2">
                    <i class="fas fa-list"></i> View All Lessons
                  </a>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Video Section -->
    @if($lesson->video_url)
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card video-card">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-video"></i> Video Content</h5>
          </div>
          <div class="card-body">
            <div class="video-container">
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
                  <div class="ratio ratio-16x9">
                    <iframe src="https://www.youtube.com/embed/{{ $videoId }}" 
                            allowfullscreen frameborder="0"></iframe>
                  </div>
                  <div class="video-info mt-3">
                    <p class="mb-1">
                      <strong><i class="fab fa-youtube text-danger"></i> YouTube Video</strong>
                    </p>
                    <small class="text-muted">
                      <a href="{{ $lesson->video_url }}" target="_blank" class="text-decoration-none">
                        <i class="fas fa-external-link-alt"></i> Open in YouTube
                      </a>
                    </small>
                  </div>
                @endif
              @elseif(str_contains($lesson->video_url, 'vimeo.com'))
                @php
                  $videoId = explode('vimeo.com/', $lesson->video_url)[1] ?? '';
                @endphp
                @if($videoId)
                  <div class="ratio ratio-16x9">
                    <iframe src="https://player.vimeo.com/video/{{ $videoId }}" 
                            allowfullscreen frameborder="0"></iframe>
                  </div>
                  <div class="video-info mt-3">
                    <p class="mb-1">
                      <strong><i class="fab fa-vimeo text-primary"></i> Vimeo Video</strong>
                    </p>
                    <small class="text-muted">
                      <a href="{{ $lesson->video_url }}" target="_blank" class="text-decoration-none">
                        <i class="fas fa-external-link-alt"></i> Open in Vimeo
                      </a>
                    </small>
                  </div>
                @endif
              @else
                <div class="alert alert-info">
                  <div class="d-flex align-items-center">
                    <i class="fas fa-video fa-2x me-3"></i>
                    <div>
                      <h6>Video Content Available</h6>
                      <p class="mb-1">This lesson includes video content.</p>
                      <a href="{{ $lesson->video_url }}" target="_blank" class="btn btn-sm btn-primary">
                        <i class="fas fa-play"></i> Watch Video
                      </a>
                    </div>
                  </div>
                </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>
    @endif

    <!-- Lesson Content -->
    <div class="row">
      <div class="col-md-12">
        <div class="card content-card">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-file-alt"></i> Lesson Content</h5>
          </div>
          <div class="card-body">
            @if($lesson->content)
              <div class="lesson-content">
                {!! $lesson->content !!}
              </div>
            @else
              <div class="text-center py-5">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No Content Available</h4>
                <p class="text-muted">This lesson doesn't have any written content yet.</p>
              </div>
            @endif
          </div>
          <div class="card-footer bg-light">
            <div class="d-flex justify-content-between align-items-center">
              <small class="text-muted">
                <i class="fas fa-info-circle"></i> 
                Content last updated: {{ $lesson->updated_at->format('M d, Y \a\t h:i A') }}
              </small>
              <div>
                <button class="btn btn-sm btn-outline-secondary" onclick="copyContent()">
                  <i class="fas fa-copy"></i> Copy Content
                </button>
                <button class="btn btn-sm btn-outline-secondary ms-2" onclick="printContent()">
                  <i class="fas fa-print"></i> Print
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Navigation & Related Lessons -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-light">
            <h5 class="mb-0"><i class="fas fa-exchange-alt"></i> Lesson Navigation</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                @if($previousLesson)
                <div class="nav-card nav-previous">
                  <div class="nav-label">
                    <i class="fas fa-arrow-left"></i> Previous Lesson
                  </div>
                  <div class="nav-content">
                    <h6 class="mb-1">{{ $previousLesson->title }}</h6>
                    <p class="text-muted small mb-2">
                      Position: {{ $previousLesson->position }}
                      <span class="mx-1">•</span>
                      {{ Str::limit(strip_tags($previousLesson->content), 80) }}
                    </p>
                    <a href="{{ url('/lessons/' . $previousLesson->id) }}" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye"></i> View
                    </a>
                  </div>
                </div>
                @else
                <div class="nav-card nav-disabled">
                  <div class="nav-label">
                    <i class="fas fa-arrow-left"></i> Previous Lesson
                  </div>
                  <div class="nav-content text-center py-3">
                    <p class="text-muted mb-0">This is the first lesson</p>
                  </div>
                </div>
                @endif
              </div>
              
              <div class="col-md-6">
                @if($nextLesson)
                <div class="nav-card nav-next">
                  <div class="nav-label">
                    Next Lesson <i class="fas fa-arrow-right"></i>
                  </div>
                  <div class="nav-content">
                    <h6 class="mb-1">{{ $nextLesson->title }}</h6>
                    <p class="text-muted small mb-2">
                      Position: {{ $nextLesson->position }}
                      <span class="mx-1">•</span>
                      {{ Str::limit(strip_tags($nextLesson->content), 80) }}
                    </p>
                    <a href="{{ url('/lessons/' . $nextLesson->id) }}" class="btn btn-sm btn-outline-primary">
                      <i class="fas fa-eye"></i> View
                    </a>
                  </div>
                </div>
                @else
                <div class="nav-card nav-disabled">
                  <div class="nav-label">
                    Next Lesson <i class="fas fa-arrow-right"></i>
                  </div>
                  <div class="nav-content text-center py-3">
                    <p class="text-muted mb-0">This is the last lesson</p>
                  </div>
                </div>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-cogs"></i> Quick Actions</h5>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-3 text-center">
                <a href="{{ url('lessons/' . $lesson->id . '/edit') }}" class="action-btn">
                  <div class="action-icon bg-warning">
                    <i class="fas fa-edit fa-2x"></i>
                  </div>
                  <h6 class="mt-2">Edit Lesson</h6>
                  <small class="text-muted">Modify content</small>
                </a>
              </div>
              <div class="col-md-3 text-center">
                <a href="{{ url('lessons/create?course_id=' . $lesson->course_id . '&position=' . ($lesson->position + 1)) }}" class="action-btn">
                  <div class="action-icon bg-success">
                    <i class="fas fa-plus-circle fa-2x"></i>
                  </div>
                  <h6 class="mt-2">Add After</h6>
                  <small class="text-muted">New lesson after this</small>
                </a>
              </div>
              <div class="col-md-3 text-center">
                <a href="{{ url('lessons/create?course_id=' . $lesson->course_id . '&position=' . $lesson->position) }}" class="action-btn">
                  <div class="action-icon bg-primary">
                    <i class="fas fa-copy fa-2x"></i>
                  </div>
                  <h6 class="mt-2">Duplicate</h6>
                  <small class="text-muted">Copy this lesson</small>
                </a>
              </div>
              <div class="col-md-3 text-center">
                <a href="{{ url('lessons?course_id=' . $lesson->course_id) }}" class="action-btn">
                  <div class="action-icon bg-info">
                    <i class="fas fa-list fa-2x"></i>
                  </div>
                  <h6 class="mt-2">All Lessons</h6>
                  <small class="text-muted">View course lessons</small>
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    
  </div>
</div>

<!-- Content Print Modal -->
<div class="modal fade" id="printModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Print Preview</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body" id="printContent">
        <!-- Print content will be inserted here -->
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        <button type="button" class="btn btn-primary" onclick="window.print()">
          <i class="fas fa-print"></i> Print
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
    max-width: 1200px;
    margin: 0 auto;
  }
  
  .card-header {
    padding: 20px;
    border-bottom: none;
  }
  
  .card-header.bg-primary {
    background: linear-gradient(145deg, #3498db, #2980b9) !important;
  }
  
  .card-header.bg-success {
    background: linear-gradient(145deg, #2ecc71, #27ae60) !important;
  }
  
  .card-header.bg-info {
    background: linear-gradient(145deg, #17a2b8, #138496) !important;
  }
  
  .card-header.bg-secondary {
    background: linear-gradient(145deg, #6c757d, #495057) !important;
  }
  
  .card-title {
    margin: 0;
    font-weight: 600;
    font-size: 1.5rem;
  }
  
  .card-body {
    padding: 30px;
  }
  
  /* Button Styles */
  .btn {
    font-size: 14px;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
  }
  
  .btn-secondary {
    background: linear-gradient(145deg, #6c757d, #495057);
    color: white;
    border: none;
  }
  
  .btn-secondary:hover {
    background: linear-gradient(145deg, #495057, #343a40);
    color: white;
    transform: translateY(-2px);
  }
  
  .btn-edit {
    background: linear-gradient(145deg, #f39c12, #e67e22);
    color: white;
    border: none;
  }
  
  .btn-edit:hover {
    background: linear-gradient(145deg, #e67e22, #d35400);
    color: white;
    transform: translateY(-2px);
  }
  
  .btn-delete {
    background: linear-gradient(145deg, #dc3545, #c82333);
    color: white;
    border: none;
  }
  
  .btn-delete:hover {
    background: linear-gradient(145deg, #c82333, #a71e2a);
    color: white;
    transform: translateY(-2px);
  }
  
  .btn-view {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
    color: white;
    border: none;
  }
  
  .btn-view:hover {
    background: linear-gradient(145deg, #27ae60, #219653);
    color: white;
    transform: translateY(-2px);
  }
  
  .btn-sm {
    padding: 5px 10px;
    font-size: 12px;
  }
  
  /* Lesson Header */
  .lesson-header {
    margin-bottom: 20px;
  }
  
  .position-badge {
    display: flex;
    align-items: center;
  }
  
  .position-number {
    font-size: 16px;
    padding: 8px 16px;
    border-radius: 20px;
    background: linear-gradient(145deg, #3498db, #2980b9);
  }
  
  .lesson-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #2c3e50;
    margin: 10px 0;
    line-height: 1.2;
  }
  
  .lesson-meta {
    font-size: 14px;
    color: #6c757d;
  }
  
  code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: monospace;
    color: #e83e8c;
  }
  
  /* Stats Card */
  .lesson-stats {
    border: none;
    border-radius: 10px;
    height: 100%;
  }
  
  .stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #2ecc71;
  }
  
  .stat-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  /* Course Card */
  .course-card {
    border: none;
    border-radius: 10px;
    overflow: hidden;
  }
  
  .course-icon-large {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
  }
  
  .course-description {
    color: #6c757d;
    font-size: 14px;
    line-height: 1.5;
  }
  
  /* Video Card */
  .video-card {
    border: none;
    border-radius: 10px;
    overflow: hidden;
  }
  
  .video-container {
    position: relative;
  }
  
  .ratio-16x9 {
    --bs-aspect-ratio: 56.25%;
  }
  
  .video-info {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    margin-top: 20px;
  }
  
  /* Content Card */
  .content-card {
    border: none;
    border-radius: 10px;
    overflow: hidden;
  }
  
  .lesson-content {
    font-size: 16px;
    line-height: 1.7;
    color: #333;
  }
  
  .lesson-content h1, .lesson-content h2, .lesson-content h3 {
    color: #2c3e50;
    margin-top: 1.5em;
    margin-bottom: 0.5em;
  }
  
  .lesson-content p {
    margin-bottom: 1.2em;
  }
  
  .lesson-content ul, .lesson-content ol {
    margin-bottom: 1.2em;
    padding-left: 2em;
  }
  
  .lesson-content blockquote {
    border-left: 4px solid #2ecc71;
    padding-left: 20px;
    margin: 20px 0;
    color: #6c757d;
    font-style: italic;
  }
  
  .lesson-content pre {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    overflow-x: auto;
    margin: 20px 0;
  }
  
  .lesson-content code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
  }
  
  /* Navigation Cards */
  .nav-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    padding: 20px;
    height: 100%;
    transition: all 0.3s ease;
  }
  
  .nav-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
  }
  
  .nav-previous:hover {
    border-color: #3498db;
  }
  
  .nav-next:hover {
    border-color: #2ecc71;
  }
  
  .nav-disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  
  .nav-label {
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #6c757d;
    margin-bottom: 15px;
  }
  
  .nav-content h6 {
    font-size: 16px;
    font-weight: 600;
    color: #2c3e50;
  }
  
  /* Quick Actions */
  .action-btn {
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.3s ease;
  }
  
  .action-btn:hover {
    transform: translateY(-5px);
    color: inherit;
  }
  
  .action-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
  }
  
  .action-icon.bg-warning {
    background: linear-gradient(145deg, #f39c12, #e67e22);
  }
  
  .action-icon.bg-success {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
  }
  
  .action-icon.bg-primary {
    background: linear-gradient(145deg, #3498db, #2980b9);
  }
  
  .action-icon.bg-info {
    background: linear-gradient(145deg, #17a2b8, #138496);
  }
  
  /* Badge Styles */
  .badge {
    padding: 8px 12px;
    border-radius: 20px;
    font-weight: 500;
  }
  
  /* Modal */
  .modal-content {
    border-radius: 12px;
    border: none;
  }
  
  .modal-header {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
    color: white;
    border-bottom: none;
  }
  
  /* Print Styles */
  @media print {
    .no-print {
      display: none !important;
    }
    
    .lesson-content {
      font-size: 14px;
      line-height: 1.5;
    }
    
    .lesson-content h1 {
      font-size: 24px;
    }
    
    .lesson-content h2 {
      font-size: 20px;
    }
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .lesson-title {
      font-size: 1.8rem;
    }
    
    .position-number {
      font-size: 14px;
    }
    
    .action-icon {
      width: 60px;
      height: 60px;
    }
    
    .action-icon i {
      font-size: 1.5rem;
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Confirm delete
    const deleteForm = document.querySelector('form[action*="lessons/' + {{ $lesson->id }} + '"]');
    if (deleteForm) {
      deleteForm.addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to delete this lesson? This action cannot be undone.')) {
          e.preventDefault();
        }
      });
    }
    
    // Copy content to clipboard
    function copyContent() {
      const content = document.querySelector('.lesson-content').innerText;
      
      navigator.clipboard.writeText(content).then(() => {
        // Show success message
        const toast = document.createElement('div');
        toast.className = 'alert alert-success position-fixed top-0 end-0 m-3';
        toast.style.zIndex = '9999';
        toast.innerHTML = '<i class="fas fa-check-circle"></i> Content copied to clipboard!';
        document.body.appendChild(toast);
        
        setTimeout(() => toast.remove(), 3000);
      }).catch(err => {
        console.error('Failed to copy: ', err);
        alert('Failed to copy content. Please try again.');
      });
    }
    
    // Print content with preview
    function printContent() {
      const content = document.querySelector('.lesson-content').innerHTML;
      const title = '{{ $lesson->title }}';
      const course = '{{ $lesson->course->title ?? "No Course" }}';
      
      const printContent = `
        <div class="print-header">
          <h1>${title}</h1>
          <p class="text-muted">Course: ${course} | Position: {{ $lesson->position }}</p>
          <hr>
        </div>
        <div class="print-body">
          ${content}
        </div>
        <div class="print-footer">
          <hr>
          <p class="text-muted small">
            Printed on: ${new Date().toLocaleDateString()} | 
            Lesson ID: {{ $lesson->id }} | 
            Created: {{ $lesson->created_at->format('Y-m-d') }}
          </p>
        </div>
      `;
      
      document.getElementById('printContent').innerHTML = printContent;
      const modal = new bootstrap.Modal(document.getElementById('printModal'));
      modal.show();
    }
    
    // Auto-embed video on load
    const videoContainer = document.querySelector('.video-container');
    if (videoContainer) {
      // Add loading animation
      const iframe = videoContainer.querySelector('iframe');
      if (iframe) {
        iframe.onload = function() {
          iframe.style.opacity = '1';
          iframe.style.transition = 'opacity 0.3s ease';
        };
        iframe.style.opacity = '0';
      }
    }
    
    // Highlight code blocks
    const codeBlocks = document.querySelectorAll('pre code');
    codeBlocks.forEach(block => {
      block.classList.add('language-html');
      
      // Add copy button to code blocks
      const copyButton = document.createElement('button');
      copyButton.className = 'btn btn-sm btn-outline-secondary code-copy-btn';
      copyButton.innerHTML = '<i class="fas fa-copy"></i>';
      copyButton.style.position = 'absolute';
      copyButton.style.top = '5px';
      copyButton.style.right = '5px';
      copyButton.style.opacity = '0.7';
      
      copyButton.addEventListener('mouseenter', () => copyButton.style.opacity = '1');
      copyButton.addEventListener('mouseleave', () => copyButton.style.opacity = '0.7');
      
      copyButton.addEventListener('click', function() {
        const code = block.textContent;
        navigator.clipboard.writeText(code).then(() => {
          copyButton.innerHTML = '<i class="fas fa-check"></i>';
          setTimeout(() => {
            copyButton.innerHTML = '<i class="fas fa-copy"></i>';
          }, 2000);
        });
      });
      
      const pre = block.parentElement;
      pre.style.position = 'relative';
      pre.appendChild(copyButton);
    });
    
    // Add table of contents for long content
    const lessonContent = document.querySelector('.lesson-content');
    if (lessonContent) {
      const headings = lessonContent.querySelectorAll('h1, h2, h3');
      if (headings.length >= 3) {
        const toc = document.createElement('div');
        toc.className = 'toc-card mb-4 p-3 bg-light rounded';
        toc.innerHTML = `
          <h6><i class="fas fa-list"></i> Table of Contents</h6>
          <ul class="toc-list mb-0">
            ${Array.from(headings).map((heading, index) => `
              <li class="toc-item">
                <a href="#heading-${index}" class="text-decoration-none">
                  ${heading.textContent}
                </a>
              </li>
            `).join('')}
          </ul>
        `;
        
        // Add IDs to headings
        headings.forEach((heading, index) => {
          heading.id = `heading-${index}`;
        });
        
        lessonContent.insertBefore(toc, lessonContent.firstChild);
      }
    }
    
    // Lesson navigation animation
    const navCards = document.querySelectorAll('.nav-card:not(.nav-disabled)');
    navCards.forEach(card => {
      card.addEventListener('mouseenter', function() {
        const icon = this.querySelector('.nav-label i');
        if (icon) {
          if (this.classList.contains('nav-previous')) {
            icon.style.transform = 'translateX(-5px)';
          } else {
            icon.style.transform = 'translateX(5px)';
          }
          icon.style.transition = 'transform 0.3s ease';
        }
      });
      
      card.addEventListener('mouseleave', function() {
        const icon = this.querySelector('.nav-label i');
        if (icon) {
          icon.style.transform = 'translateX(0)';
        }
      });
    });
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Estimate reading time updates
    function updateReadingTime() {
      const content = document.querySelector('.lesson-content').innerText;
      const wordCount = content.split(/\s+/).length;
      const readingTime = Math.ceil(wordCount / 200);
      
      const readingTimeElement = document.querySelector('[class*="reading"]');
      if (readingTimeElement) {
        readingTimeElement.textContent = `Estimated reading time: ${readingTime} min`;
      }
    }
    
    // Update reading time on content changes (if editable)
    if (typeof MutationObserver !== 'undefined') {
      const observer = new MutationObserver(updateReadingTime);
      const contentElement = document.querySelector('.lesson-content');
      if (contentElement) {
        observer.observe(contentElement, { 
          childList: true, 
          subtree: true, 
          characterData: true 
        });
      }
    }
  });
</script>

@stop