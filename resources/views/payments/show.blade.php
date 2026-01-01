@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-credit-card"></i> Payment Details: #{{ $payment->id }}
    </h4>
  </div>
  <div class="card-body">
    
    <!-- Action Buttons -->
    <div class="d-flex justify-content-end mb-4">
      <a href="{{ url('payments') }}" class="btn btn-secondary me-2">
        <i class="fas fa-arrow-left"></i> Back to Payments
      </a>
      <a href="{{ url('payments/' . $payment->id . '/edit') }}" class="btn btn-edit me-2">
        <i class="fas fa-edit"></i> Edit Payment
      </a>
      <form method="POST" action="{{ url('payments/' . $payment->id) }}" accept-charset="UTF-8" style="display:inline">
        @method('DELETE')
        @csrf
        <button type="submit" class="btn btn-delete" title="Delete Payment" 
                onclick="return confirm('Are you sure you want to delete this payment?')">
          <i class="fas fa-trash"></i> Delete Payment
        </button>
      </form>
    </div>

    <!-- Payment Overview -->
    <div class="row mb-4">
      <div class="col-md-4">
        <div class="card payment-status-card">
          <div class="card-body text-center">
            <div class="payment-icon mb-3">
              @php
                $statusIcons = [
                  'completed' => 'fa-check-circle',
                  'pending' => 'fa-clock',
                  'failed' => 'fa-times-circle',
                  'refunded' => 'fa-undo'
                ];
                $methodIcons = [
                  'stripe' => 'fa-credit-card',
                  'paypal' => 'fa-paypal',
                  'manual' => 'fa-hand-holding-usd'
                ];
              @endphp
              <i class="fas {{ $statusIcons[$payment->status] ?? 'fa-question-circle' }} fa-4x 
                text-{{ $payment->status === 'completed' ? 'success' : 
                       ($payment->status === 'pending' ? 'warning' : 
                       ($payment->status === 'failed' ? 'danger' : 'info')) }}"></i>
            </div>
            <h4 class="mb-2">
              <span class="badge bg-{{ $payment->status === 'completed' ? 'success' : 
                                     ($payment->status === 'pending' ? 'warning' : 
                                     ($payment->status === 'failed' ? 'danger' : 'info')) }}">
                {{ ucfirst($payment->status) }}
              </span>
            </h4>
            <p class="text-muted mb-0">
              <i class="fas {{ $methodIcons[$payment->payment_method] ?? 'fa-money-bill-wave' }}"></i>
              {{ ucfirst($payment->payment_method) }} Payment
            </p>
          </div>
        </div>
      </div>
      
      <div class="col-md-8">
        <div class="card payment-details-card">
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="payment-amount-section text-center">
                  <h6 class="text-muted mb-2">Payment Amount</h6>
                  <h1 class="payment-amount text-success">${{ number_format($payment->amount, 2) }}</h1>
                  <p class="text-muted mb-0">
                    @if($payment->course)
                      For: {{ $payment->course->title }}
                    @endif
                  </p>
                </div>
              </div>
              
              <div class="col-md-6">
                <div class="payment-info-section">
                  <h6><i class="fas fa-info-circle"></i> Payment Information</h6>
                  <div class="payment-info-grid mt-3">
                    <div class="info-item">
                      <span class="info-label">Transaction ID:</span>
                      <span class="info-value">
                        <code>{{ $payment->transaction_id ?? 'N/A' }}</code>
                        @if($payment->transaction_id)
                          <button class="btn btn-sm btn-copy ms-1" onclick="copyToClipboard('{{ $payment->transaction_id }}')">
                            <i class="fas fa-copy"></i>
                          </button>
                        @endif
                      </span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Created:</span>
                      <span class="info-value">{{ $payment->created_at->format('M d, Y \a\t h:i A') }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Updated:</span>
                      <span class="info-value">{{ $payment->updated_at->format('M d, Y \a\t h:i A') }}</span>
                    </div>
                    <div class="info-item">
                      <span class="info-label">Payment ID:</span>
                      <span class="info-value">
                        <code>{{ $payment->id }}</code>
                        <button class="btn btn-sm btn-copy ms-1" onclick="copyToClipboard('{{ $payment->id }}')">
                          <i class="fas fa-copy"></i>
                        </button>
                      </span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- User and Course Information -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card user-card">
          <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-user"></i> User Information</h5>
          </div>
          <div class="card-body">
            <div class="user-details">
              <div class="d-flex align-items-center mb-3">
                <div class="user-avatar me-3">
                  <i class="fas fa-user-circle fa-3x text-primary"></i>
                </div>
                <div>
                  <h4 class="mb-1">{{ $payment->user->name ?? 'N/A' }}</h4>
                  <p class="text-muted mb-1">{{ $payment->user->email ?? 'N/A' }}</p>
                  <p class="mb-0">
                    <small class="text-muted">User ID: {{ $payment->user_id }}</small>
                  </p>
                </div>
              </div>
              
              <div class="user-stats" id="userStats">
                <div class="spinner-border spinner-border-sm text-primary"></div> Loading user statistics...
              </div>
              
              <div class="mt-3">
                <a href="{{ url('users/' . $payment->user_id) }}" class="btn btn-sm btn-view">
                  <i class="fas fa-external-link-alt"></i> View User Profile
                </a>
                <a href="{{ url('payments?user_id=' . $payment->user_id) }}" class="btn btn-sm btn-outline-primary ms-2">
                  <i class="fas fa-list"></i> View All Payments
                </a>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card course-card">
          <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-book"></i> Course Information</h5>
          </div>
          <div class="card-body">
            @if($payment->course)
            <div class="course-details">
              <div class="d-flex align-items-center mb-3">
                <div class="course-icon me-3">
                  <i class="fas fa-book fa-3x text-success"></i>
                </div>
                <div>
                  <h4 class="mb-1">{{ $payment->course->title }}</h4>
                  <p class="text-muted mb-1">
                    <i class="fas fa-hashtag"></i> {{ $payment->course->code ?? 'N/A' }}
                    <span class="mx-2">•</span>
                    <i class="fas fa-dollar-sign"></i> ${{ number_format($payment->course->price ?? 0, 2) }}
                  </p>
                  <p class="mb-0">
                    <small class="text-muted">Course ID: {{ $payment->course_id }}</small>
                  </p>
                </div>
              </div>
              
              @if($payment->course->description)
              <div class="course-description">
                <h6>Description</h6>
                <p class="text-muted">{{ Str::limit($payment->course->description, 150) }}</p>
              </div>
              @endif
              
              <div class="course-stats mt-3" id="courseStats">
                <div class="spinner-border spinner-border-sm text-primary"></div> Loading course statistics...
              </div>
              
              <div class="mt-3">
                <a href="{{ url('courses/' . $payment->course_id) }}" class="btn btn-sm btn-view">
                  <i class="fas fa-external-link-alt"></i> View Course
                </a>
                @if($payment->user)
                  <a href="{{ url('enrollments/create?user_id=' . $payment->user_id . '&course_id=' . $payment->course_id) }}" 
                     class="btn btn-sm btn-outline-success ms-2">
                    <i class="fas fa-user-plus"></i> Enroll User
                  </a>
                @endif
              </div>
            </div>
            @else
            <div class="text-center py-4">
              <i class="fas fa-book fa-3x text-muted mb-3"></i>
              <h5 class="text-muted">No Course Information</h5>
              <p class="text-muted">This payment is not associated with a course</p>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Timeline -->
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card timeline-card">
          <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> Payment Timeline</h5>
          </div>
          <div class="card-body">
            <div class="timeline">
              <div class="timeline-item completed">
                <div class="timeline-icon">
                  <i class="fas fa-plus-circle"></i>
                </div>
                <div class="timeline-content">
                  <h6>Payment Created</h6>
                  <p class="mb-1">{{ $payment->created_at->format('F d, Y \a\t h:i A') }}</p>
                  <small class="text-muted">{{ $payment->created_at->diffForHumans() }}</small>
                  <div class="timeline-meta">
                    <span class="badge bg-light text-dark">
                      <i class="fas fa-user"></i> {{ $payment->user->name ?? 'User' }}
                    </span>
                    <span class="badge bg-light text-dark ms-2">
                      <i class="fas fa-{{ $methodIcons[$payment->payment_method] ?? 'fa-money-bill-wave' }}"></i>
                      {{ ucfirst($payment->payment_method) }}
                    </span>
                  </div>
                </div>
              </div>
              
              @if($payment->status === 'completed' && $payment->updated_at != $payment->created_at)
              <div class="timeline-item completed">
                <div class="timeline-icon">
                  <i class="fas fa-check-circle"></i>
                </div>
                <div class="timeline-content">
                  <h6>Payment Completed</h6>
                  <p class="mb-1">{{ $payment->updated_at->format('F d, Y \a\t h:i A') }}</p>
                  <small class="text-muted">{{ $payment->updated_at->diffForHumans() }}</small>
                  <div class="timeline-meta">
                    <span class="badge bg-success">
                      <i class="fas fa-check"></i> Amount: ${{ number_format($payment->amount, 2) }}
                    </span>
                    @if($payment->transaction_id)
                    <span class="badge bg-light text-dark ms-2">
                      <i class="fas fa-hashtag"></i> {{ $payment->transaction_id }}
                    </span>
                    @endif
                  </div>
                </div>
              </div>
              @endif
              
              @if($payment->status === 'failed')
              <div class="timeline-item failed">
                <div class="timeline-icon">
                  <i class="fas fa-times-circle"></i>
                </div>
                <div class="timeline-content">
                  <h6>Payment Failed</h6>
                  <p class="mb-1">{{ $payment->updated_at->format('F d, Y \a\t h:i A') }}</p>
                  <small class="text-muted">{{ $payment->updated_at->diffForHumans() }}</small>
                  <div class="timeline-meta">
                    <span class="badge bg-danger">
                      <i class="fas fa-times"></i> Payment was not successful
                    </span>
                  </div>
                </div>
              </div>
              @endif
              
              @if($payment->status === 'refunded')
              <div class="timeline-item refunded">
                <div class="timeline-icon">
                  <i class="fas fa-undo"></i>
                </div>
                <div class="timeline-content">
                  <h6>Payment Refunded</h6>
                  <p class="mb-1">{{ $payment->updated_at->format('F d, Y \a\t h:i A') }}</p>
                  <small class="text-muted">{{ $payment->updated_at->diffForHumans() }}</small>
                  <div class="timeline-meta">
                    <span class="badge bg-info">
                      <i class="fas fa-undo"></i> Amount refunded to user
                    </span>
                  </div>
                </div>
              </div>
              @endif
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Payment Actions & Notes -->
    <div class="row">
      <div class="col-md-6">
        <div class="card actions-card">
          <div class="card-header bg-warning text-white">
            <h5 class="mb-0"><i class="fas fa-cogs"></i> Payment Actions</h5>
          </div>
          <div class="card-body">
            <div class="action-buttons">
              @if($payment->status === 'pending')
              <button class="btn btn-success w-100 mb-2" onclick="updateStatus('completed')">
                <i class="fas fa-check-circle"></i> Mark as Completed
              </button>
              <button class="btn btn-danger w-100 mb-2" onclick="updateStatus('failed')">
                <i class="fas fa-times-circle"></i> Mark as Failed
              </button>
              @endif
              
              @if($payment->status === 'completed')
              <button class="btn btn-info w-100 mb-2" onclick="updateStatus('refunded')">
                <i class="fas fa-undo"></i> Mark as Refunded
              </button>
              @endif
              
              @if(in_array($payment->status, ['failed', 'refunded']))
              <button class="btn btn-warning w-100 mb-2" onclick="updateStatus('pending')">
                <i class="fas fa-clock"></i> Mark as Pending
              </button>
              @endif
              
              <button class="btn btn-primary w-100 mb-2" onclick="sendReceipt()">
                <i class="fas fa-receipt"></i> Send Receipt
              </button>
              
              <a href="{{ url('payments/create?user_id=' . $payment->user_id . '&course_id=' . $payment->course_id) }}" 
                 class="btn btn-outline-secondary w-100">
                <i class="fas fa-copy"></i> Duplicate Payment
              </a>
            </div>
            
            <div class="mt-4">
              <h6><i class="fas fa-chart-line"></i> Payment Statistics</h6>
              <div class="stats-grid mt-3">
                <div class="stat-item">
                  <div class="stat-number" id="totalUserPayments">0</div>
                  <div class="stat-label">User Payments</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number" id="totalCoursePayments">0</div>
                  <div class="stat-label">Course Payments</div>
                </div>
                <div class="stat-item">
                  <div class="stat-number" id="averageAmount">$0.00</div>
                  <div class="stat-label">Average Payment</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card notes-card">
          <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Payment Notes</h5>
          </div>
          <div class="card-body">
            @if($payment->notes)
              <div class="notes-content">
                {{ $payment->notes }}
              </div>
              <div class="notes-meta mt-3">
                <small class="text-muted">
                  <i class="fas fa-info-circle"></i> 
                  These notes are for internal administrative purposes only.
                </small>
              </div>
            @else
              <div class="text-center py-4">
                <i class="fas fa-sticky-note fa-3x text-muted mb-3"></i>
                <h5 class="text-muted">No Notes Available</h5>
                <p class="text-muted">No additional notes were added to this payment</p>
                <a href="{{ url('payments/' . $payment->id . '/edit') }}" class="btn btn-sm btn-outline-secondary">
                  <i class="fas fa-edit"></i> Add Notes
                </a>
              </div>
            @endif
            
            <!-- Related Payments -->
            <div class="related-payments mt-4">
              <h6><i class="fas fa-link"></i> Related Information</h6>
              <div class="row mt-3">
                <div class="col-6 text-center">
                  <a href="{{ url('users/' . $payment->user_id) }}" class="related-link">
                    <div class="related-icon bg-primary">
                      <i class="fas fa-user fa-2x"></i>
                    </div>
                    <h6 class="mt-2">User Profile</h6>
                  </a>
                </div>
                
                <div class="col-6 text-center">
                  @if($payment->course_id)
                  <a href="{{ url('courses/' . $payment->course_id) }}" class="related-link">
                    <div class="related-icon bg-success">
                      <i class="fas fa-book fa-2x"></i>
                    </div>
                    <h6 class="mt-2">Course Details</h6>
                  </a>
                  @else
                  <div class="related-link disabled">
                    <div class="related-icon bg-secondary">
                      <i class="fas fa-book fa-2x"></i>
                    </div>
                    <h6 class="mt-2">No Course</h6>
                  </div>
                  @endif
                </div>
              </div>
            </div>
          </div>
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
  
  .card-header.bg-warning {
    background: linear-gradient(145deg, #f39c12, #e67e22) !important;
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
  
  .btn-copy {
    background-color: #6c757d;
    color: white;
    padding: 2px 6px;
    font-size: 10px;
    border: none;
    border-radius: 4px;
  }
  
  .btn-copy:hover {
    background-color: #5a6268;
  }
  
  /* Payment Status Card */
  .payment-status-card {
    border: 2px solid #e9ecef;
    border-radius: 10px;
    height: 100%;
    transition: transform 0.3s ease;
  }
  
  .payment-status-card:hover {
    transform: translateY(-5px);
  }
  
  .payment-icon {
    animation: pulse 2s infinite;
  }
  
  @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); }
    100% { transform: scale(1); }
  }
  
  /* Payment Details Card */
  .payment-details-card {
    border: none;
    border-radius: 10px;
    height: 100%;
  }
  
  .payment-amount-section {
    padding: 20px 0;
  }
  
  .payment-amount {
    font-size: 48px;
    font-weight: 700;
    margin: 10px 0;
  }
  
  .payment-info-grid {
    display: grid;
    gap: 10px;
  }
  
  .info-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px dashed #e9ecef;
  }
  
  .info-label {
    font-weight: 600;
    color: #6c757d;
    font-size: 14px;
  }
  
  .info-value {
    font-weight: 500;
    color: #333;
    font-size: 14px;
  }
  
  code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    color: #e74c3c;
  }
  
  /* User & Course Cards */
  .user-avatar, .course-icon {
    width: 60px;
    text-align: center;
  }
  
  .user-stats, .course-stats {
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
  }
  
  /* Timeline */
  .timeline-card {
    border: none;
    border-radius: 10px;
    overflow: hidden;
  }
  
  .timeline {
    position: relative;
    padding-left: 30px;
  }
  
  .timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #e74c3c, #3498db);
  }
  
  .timeline-item {
    position: relative;
    margin-bottom: 30px;
    padding-left: 20px;
  }
  
  .timeline-item::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    border: 2px solid white;
  }
  
  .timeline-item.completed::before {
    background-color: #2ecc71;
  }
  
  .timeline-item.failed::before {
    background-color: #e74c3c;
  }
  
  .timeline-item.refunded::before {
    background-color: #17a2b8;
  }
  
  .timeline-icon {
    position: absolute;
    left: -30px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: white;
    display: flex;
    align-items: center;
    justify-content: center;
    border: 2px solid #e74c3c;
    z-index: 1;
  }
  
  .timeline-item.completed .timeline-icon {
    border-color: #2ecc71;
    color: #2ecc71;
  }
  
  .timeline-item.failed .timeline-icon {
    border-color: #e74c3c;
    color: #e74c3c;
  }
  
  .timeline-item.refunded .timeline-icon {
    border-color: #17a2b8;
    color: #17a2b8;
  }
  
  .timeline-content {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border: 1px solid #e9ecef;
  }
  
  .timeline-meta {
    margin-top: 10px;
  }
  
  /* Action Buttons */
  .action-buttons .btn {
    margin-bottom: 10px;
    justify-content: center;
  }
  
  .action-buttons .btn i {
    margin-right: 8px;
  }
  
  /* Stats Grid */
  .stats-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
  }
  
  .stat-item {
    text-align: center;
    padding: 15px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
  }
  
  .stat-number {
    font-size: 24px;
    font-weight: 700;
    color: #e74c3c;
  }
  
  .stat-label {
    font-size: 12px;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  
  /* Notes */
  .notes-content {
    padding: 20px;
    background-color: #f8f9fa;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    font-size: 15px;
    line-height: 1.6;
    color: #333;
    white-space: pre-wrap;
  }
  
  /* Related Links */
  .related-link {
    text-decoration: none;
    color: inherit;
    display: block;
    transition: transform 0.3s ease;
  }
  
  .related-link:hover {
    transform: translateY(-5px);
    color: inherit;
  }
  
  .related-link.disabled {
    opacity: 0.6;
    cursor: not-allowed;
  }
  
  .related-icon {
    width: 70px;
    height: 70px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    color: white;
  }
  
  .related-icon.bg-primary {
    background: linear-gradient(145deg, #3498db, #2980b9);
  }
  
  .related-icon.bg-success {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
  }
  
  .related-icon.bg-secondary {
    background: linear-gradient(145deg, #6c757d, #495057);
  }
  
  /* Badge Styling */
  .badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 12px;
  }
  
  /* Responsive */
  @media (max-width: 768px) {
    .payment-amount {
      font-size: 36px;
    }
    
    .timeline {
      padding-left: 20px;
    }
    
    .timeline-icon {
      left: -20px;
      width: 20px;
      height: 20px;
    }
    
    .stats-grid {
      grid-template-columns: 1fr;
      gap: 10px;
    }
    
    .related-icon {
      width: 60px;
      height: 60px;
    }
    
    .related-icon i {
      font-size: 1.5rem;
    }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Confirm delete
    const deleteForm = document.querySelector('form[action*="payments/' + {{ $payment->id }} + '"]');
    if (deleteForm) {
      deleteForm.addEventListener('submit', function(e) {
        if (!confirm('Are you sure you want to delete this payment? This action cannot be undone.')) {
          e.preventDefault();
        }
      });
    }
    
    // Load user statistics
    function loadUserStats() {
      const userId = {{ $payment->user_id }};
      const userStatsDiv = document.getElementById('userStats');
      
      fetch(`/api/users/${userId}/payment-stats`)
        .then(response => response.json())
        .then(data => {
          userStatsDiv.innerHTML = `
            <div class="row">
              <div class="col-4">
                <div class="text-center">
                  <div class="stat-number-small">${data.total_payments}</div>
                  <div class="stat-label-small">Total</div>
                </div>
              </div>
              <div class="col-4">
                <div class="text-center">
                  <div class="stat-number-small">${data.completed_payments}</div>
                  <div class="stat-label-small">Completed</div>
                </div>
              </div>
              <div class="col-4">
                <div class="text-center">
                  <div class="stat-number-small">$${data.total_spent || '0.00'}</div>
                  <div class="stat-label-small">Spent</div>
                </div>
              </div>
            </div>
            <div class="mt-2">
              <small class="text-muted">
                <i class="fas fa-calendar-alt"></i> 
                Last payment: ${data.last_payment ? new Date(data.last_payment).toLocaleDateString() : 'N/A'}
              </small>
            </div>
          `;
        })
        .catch(error => {
          console.error('Error:', error);
          userStatsDiv.innerHTML = '<p class="text-danger">Failed to load user stats</p>';
        });
    }
    
    // Load course statistics
    function loadCourseStats() {
      const courseId = {{ $payment->course_id }};
      const courseStatsDiv = document.getElementById('courseStats');
      
      if (!courseId) {
        courseStatsDiv.innerHTML = '<p class="text-muted">No course statistics available</p>';
        return;
      }
      
      fetch(`/api/courses/${courseId}/payment-stats`)
        .then(response => response.json())
        .then(data => {
          courseStatsDiv.innerHTML = `
            <div class="row">
              <div class="col-4">
                <div class="text-center">
                  <div class="stat-number-small">${data.total_payments}</div>
                  <div class="stat-label-small">Total</div>
                </div>
              </div>
              <div class="col-4">
                <div class="text-center">
                  <div class="stat-number-small">${data.completed_payments}</div>
                  <div class="stat-label-small">Completed</div>
                </div>
              </div>
              <div class="col-4">
                <div class="text-center">
                  <div class="stat-number-small">$${data.total_revenue || '0.00'}</div>
                  <div class="stat-label-small">Revenue</div>
                </div>
              </div>
            </div>
            <div class="mt-2">
              <small class="text-muted">
                <i class="fas fa-chart-line"></i> 
                Course price: $${data.course_price || '0.00'}
              </small>
            </div>
          `;
        })
        .catch(error => {
          console.error('Error:', error);
          courseStatsDiv.innerHTML = '<p class="text-danger">Failed to load course stats</p>';
        });
    }
    
    // Load payment statistics
    function loadPaymentStats() {
      const userId = {{ $payment->user_id }};
      const courseId = {{ $payment->course_id }};
      
      // User payment stats
      fetch(`/api/users/${userId}/payment-stats`)
        .then(response => response.json())
        .then(data => {
          document.getElementById('totalUserPayments').textContent = data.total_payments || 0;
          document.getElementById('averageAmount').textContent = data.average_amount ? 
            '$' + parseFloat(data.average_amount).toFixed(2) : '$0.00';
        })
        .catch(error => {
          console.error('Error loading user stats:', error);
        });
      
      // Course payment stats
      if (courseId) {
        fetch(`/api/courses/${courseId}/payment-stats`)
          .then(response => response.json())
          .then(data => {
            document.getElementById('totalCoursePayments').textContent = data.total_payments || 0;
          })
          .catch(error => {
            console.error('Error loading course stats:', error);
          });
      }
    }
    
    // Update payment status
    function updateStatus(newStatus) {
      if (!confirm(`Are you sure you want to mark this payment as ${newStatus}?`)) {
        return;
      }
      
      const button = event.target.closest('button');
      const originalHtml = button.innerHTML;
      
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
      button.disabled = true;
      
      fetch(`/payments/{{ $payment->id }}/status`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        },
        body: JSON.stringify({
          status: newStatus
        })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast(`Payment marked as ${newStatus}`, 'success');
          setTimeout(() => window.location.reload(), 1000);
        } else {
          showToast(data.message || 'Failed to update status', 'error');
          button.innerHTML = originalHtml;
          button.disabled = false;
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while updating status', 'error');
        button.innerHTML = originalHtml;
        button.disabled = false;
      });
    }
    
    // Send receipt
    function sendReceipt() {
      if (!confirm('Send payment receipt to user?')) {
        return;
      }
      
      const button = event.target.closest('button');
      const originalHtml = button.innerHTML;
      
      button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
      button.disabled = true;
      
      fetch(`/payments/{{ $payment->id }}/send-receipt`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Accept': 'application/json'
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          showToast('Receipt sent successfully!', 'success');
        } else {
          showToast(data.message || 'Failed to send receipt', 'error');
        }
        button.innerHTML = originalHtml;
        button.disabled = false;
      })
      .catch(error => {
        console.error('Error:', error);
        showToast('An error occurred while sending receipt', 'error');
        button.innerHTML = originalHtml;
        button.disabled = false;
      });
    }
    
    // Copy to clipboard
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        const button = event.target.closest('button');
        const originalHtml = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-check"></i>';
        
        setTimeout(() => {
          button.innerHTML = originalHtml;
        }, 2000);
        
        showToast('Copied to clipboard!', 'success');
      }).catch(err => {
        console.error('Failed to copy: ', err);
        showToast('Failed to copy', 'error');
      });
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
      const toast = document.createElement('div');
      toast.className = `alert alert-${type} position-fixed top-0 end-0 m-3`;
      toast.style.zIndex = '9999';
      toast.innerHTML = `
        <div class="d-flex align-items-center">
          <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-times-circle' : 'fa-info-circle'} me-2"></i>
          <span>${message}</span>
        </div>
      `;
      document.body.appendChild(toast);
      
      setTimeout(() => toast.remove(), 3000);
    }
    
    // Initialize stats loading
    setTimeout(() => {
      loadUserStats();
      loadCourseStats();
      loadPaymentStats();
    }, 500);
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
      return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Animate payment amount on load
    const paymentAmount = document.querySelector('.payment-amount');
    if (paymentAmount) {
      paymentAmount.style.opacity = '0';
      paymentAmount.style.transform = 'translateY(20px)';
      
      setTimeout(() => {
        paymentAmount.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        paymentAmount.style.opacity = '1';
        paymentAmount.style.transform = 'translateY(0)';
      }, 300);
    }
    
    // Timeline animation
    const timelineItems = document.querySelectorAll('.timeline-item');
    timelineItems.forEach((item, index) => {
      item.style.opacity = '0';
      item.style.transform = 'translateX(-20px)';
      
      setTimeout(() => {
        item.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        item.style.opacity = '1';
        item.style.transform = 'translateX(0)';
      }, 300 + (index * 200));
    });
  });
</script>

@stop