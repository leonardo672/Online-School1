@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-edit"></i> Edit Payment: #{{ $payment->id }}
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

    <!-- Payment Information Summary -->
    <div class="row mb-4">
      <div class="col-md-6">
        <div class="card bg-light">
          <div class="card-body">
            <h6><i class="fas fa-info-circle"></i> Current Payment Information</h6>
            <div class="row">
              <div class="col-6">
                <p class="mb-1"><strong>User:</strong></p>
                <p class="mb-1"><strong>Course:</strong></p>
                <p class="mb-1"><strong>Amount:</strong></p>
                <p class="mb-0"><strong>Status:</strong></p>
              </div>
              <div class="col-6">
                <p class="mb-1">{{ $payment->user->name ?? 'N/A' }}</p>
                <p class="mb-1">{{ $payment->course->title ?? 'N/A' }}</p>
                <p class="mb-1">${{ number_format($payment->amount, 2) }}</p>
                <p class="mb-0">
                  @php
                    $statusColors = [
                      'completed' => 'success',
                      'pending' => 'warning',
                      'failed' => 'danger',
                      'refunded' => 'info'
                    ];
                    $statusIcons = [
                      'completed' => 'fa-check-circle',
                      'pending' => 'fa-clock',
                      'failed' => 'fa-times-circle',
                      'refunded' => 'fa-undo'
                    ];
                  @endphp
                  <span class="badge bg-{{ $statusColors[$payment->status] ?? 'secondary' }}">
                    <i class="fas {{ $statusIcons[$payment->status] ?? 'fa-question-circle' }}"></i>
                    {{ ucfirst($payment->status) }}
                  </span>
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
      
      <div class="col-md-6">
        <div class="card bg-light">
          <div class="card-body">
            <h6><i class="fas fa-history"></i> Payment Timeline</h6>
            <div class="row">
              <div class="col-6">
                <p class="mb-1"><strong>Created:</strong></p>
                <p class="mb-1"><strong>Updated:</strong></p>
                <p class="mb-0"><strong>Method:</strong></p>
              </div>
              <div class="col-6">
                <p class="mb-1">{{ $payment->created_at->format('M d, Y h:i A') }}</p>
                <p class="mb-1">{{ $payment->updated_at->format('M d, Y h:i A') }}</p>
                <p class="mb-0">
                  <span class="badge bg-info">
                    <i class="fas fa-{{ $payment->payment_method === 'stripe' ? 'credit-card' : ($payment->payment_method === 'paypal' ? 'paypal' : 'hand-holding-usd') }}"></i>
                    {{ ucfirst($payment->payment_method) }}
                  </span>
                </p>
              </div>
            </div>
            @if($payment->transaction_id)
            <div class="row mt-2">
              <div class="col-12">
                <p class="mb-0">
                  <strong>Transaction ID:</strong> 
                  <code>{{ $payment->transaction_id }}</code>
                  <button class="btn btn-sm btn-copy ms-1" onclick="copyToClipboard('{{ $payment->transaction_id }}')">
                    <i class="fas fa-copy"></i>
                  </button>
                </p>
              </div>
            </div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <form action="{{ url('payments/' . $payment->id) }}" method="post" id="paymentForm">
      @csrf
      @method('PUT')
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="user_id" class="form-label">User *</label>
          <select name="user_id" id="user_id" class="form-select" required>
            <option value="">Select User</option>
            @foreach($users ?? [] as $user)
              <option value="{{ $user->id }}" {{ old('user_id', $payment->user_id) == $user->id ? 'selected' : '' }}>
                {{ $user->name }} ({{ $user->email }})
              </option>
            @endforeach
          </select>
          @if(empty($users))
            <div class="alert alert-warning mt-2">
              <i class="fas fa-exclamation-triangle"></i> No users found.
            </div>
          @endif
          <div class="form-text">Select the user making the payment</div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="course_id" class="form-label">Course *</label>
          <select name="course_id" id="course_id" class="form-select" required>
            <option value="">Select Course</option>
            @foreach($courses ?? [] as $course)
              <option value="{{ $course->id }}" 
                      data-course-price="{{ $course->price }}"
                      {{ old('course_id', $payment->course_id) == $course->id ? 'selected' : '' }}>
                {{ $course->title }} (${{ number_format($course->price ?? 0, 2) }})
              </option>
            @endforeach
          </select>
          @if(empty($courses))
            <div class="alert alert-warning mt-2">
              <i class="fas fa-exclamation-triangle"></i> No courses found.
            </div>
          @endif
          <div class="form-text">Select the course being purchased</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="amount" class="form-label">Amount *</label>
          <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="amount" id="amount" class="form-control" 
                   value="{{ old('amount', $payment->amount) }}" step="0.01" min="0" required 
                   placeholder="0.00">
          </div>
          <div class="form-text">
            Payment amount in USD
            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="useCoursePrice()">
              <i class="fas fa-sync-alt"></i> Use Course Price
            </button>
          </div>
          <div id="amountWarning" class="text-warning small mt-1 d-none"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="status" class="form-label">Payment Status *</label>
          <select name="status" id="status" class="form-select" required onchange="showStatusChangeWarning()">
            @foreach(App\Models\Payment::STATUSES as $status)
              <option value="{{ $status }}" {{ old('status', $payment->status) == $status ? 'selected' : '' }}>
                {{ ucfirst($status) }}
              </option>
            @endforeach
          </select>
          <div class="form-text">Current payment status</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="payment_method" class="form-label">Payment Method *</label>
          <select name="payment_method" id="payment_method" class="form-select" required>
            @foreach(App\Models\Payment::METHODS as $method)
              <option value="{{ $method }}" {{ old('payment_method', $payment->payment_method) == $method ? 'selected' : '' }}>
                {{ ucfirst($method) }}
              </option>
            @endforeach
          </select>
          <div class="form-text">How the payment was made</div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="transaction_id" class="form-label">Transaction ID</label>
          <input type="text" name="transaction_id" id="transaction_id" class="form-control" 
                 value="{{ old('transaction_id', $payment->transaction_id) }}" 
                 placeholder="e.g., ch_1ABCDEFGHIJKLMNOPQRSTUVWX">
          <div class="form-text">
            <span id="transactionIdHelp">External transaction reference</span>
            <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="generateTransactionId()">
              <i class="fas fa-bolt"></i> Generate
            </button>
          </div>
        </div>
      </div>

      <!-- Payment Summary -->
      <div class="mb-3">
        <div class="card bg-light">
          <div class="card-body">
            <h6><i class="fas fa-file-invoice-dollar"></i> Updated Payment Summary</h6>
            <div id="paymentSummary" class="py-3">
              <div class="row">
                <div class="col-md-6">
                  <div class="summary-item">
                    <span class="summary-label">User:</span>
                    <span class="summary-value" id="summaryUser">{{ $payment->user->name ?? 'N/A' }}</span>
                  </div>
                  <div class="summary-item">
                    <span class="summary-label">Course:</span>
                    <span class="summary-value" id="summaryCourse">{{ $payment->course->title ?? 'N/A' }}</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="summary-item">
                    <span class="summary-label">Amount:</span>
                    <span class="summary-value" id="summaryAmount">${{ number_format($payment->amount, 2) }}</span>
                  </div>
                  <div class="summary-item">
                    <span class="summary-label">Status:</span>
                    <span class="summary-value">
                      @php
                        $statusColors = [
                          'completed' => 'success',
                          'pending' => 'warning',
                          'failed' => 'danger',
                          'refunded' => 'info'
                        ];
                      @endphp
                      <span class="badge bg-{{ $statusColors[$payment->status] ?? 'secondary' }}" id="summaryStatus">
                        {{ ucfirst($payment->status) }}
                      </span>
                    </span>
                  </div>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-12">
                  <div class="summary-total">
                    <span class="total-label">Total Payment:</span>
                    <span class="total-value" id="summaryTotal">${{ number_format($payment->amount, 2) }}</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Payment Notes -->
      <div class="mb-3">
        <label for="notes" class="form-label">Payment Notes (Optional)</label>
        <textarea name="notes" id="notes" class="form-control" rows="3" 
                  placeholder="Additional notes about this payment">{{ old('notes', $payment->notes) }}</textarea>
        <div class="form-text">Optional notes for internal reference</div>
        <div class="char-counter">
          <span id="notesCharCount">0</span>/1000 characters
        </div>
      </div>

      <!-- Payment History -->
      <div class="mb-3">
        <div class="card">
          <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-history"></i> Payment History</h5>
          </div>
          <div class="card-body">
            <div class="history-timeline">
              <div class="history-item">
                <div class="history-date">{{ $payment->created_at->format('M d, Y h:i A') }}</div>
                <div class="history-content">
                  <strong>Payment Created</strong>
                  <p class="mb-1">Status: <span class="badge bg-warning">Pending</span></p>
                  <small class="text-muted">Initial payment record created</small>
                </div>
              </div>
              
              @if($payment->status === 'completed' && $payment->updated_at != $payment->created_at)
              <div class="history-item">
                <div class="history-date">{{ $payment->updated_at->format('M d, Y h:i A') }}</div>
                <div class="history-content">
                  <strong>Payment Completed</strong>
                  <p class="mb-1">Status: <span class="badge bg-success">Completed</span></p>
                  <small class="text-muted">Payment marked as completed</small>
                </div>
              </div>
              @endif
              
              <div class="history-item future">
                <div class="history-date">Now</div>
                <div class="history-content">
                  <strong>Updating Payment</strong>
                  <p class="mb-1">New Status: <span class="badge" id="futureStatus">{{ ucfirst($payment->status) }}</span></p>
                  <small class="text-muted">Saving changes to payment record</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Status Change Actions -->
      <div class="alert alert-info d-none" id="statusChangeWarning">
        <i class="fas fa-info-circle"></i> 
        <strong>Status Change Detected:</strong> Changing payment status may trigger additional actions.
        <div class="mt-2">
          <div class="form-check">
            <input class="form-check-input" type="checkbox" name="send_notification" id="send_notification" value="1" checked>
            <label class="form-check-label" for="send_notification">
              Send notification to user about status change
            </label>
          </div>
          <div class="form-check mt-1">
            <input class="form-check-input" type="checkbox" name="update_enrollment" id="update_enrollment" value="1" 
                   {{ $payment->status === 'completed' ? 'checked' : '' }}>
            <label class="form-check-label" for="update_enrollment">
              Update user enrollment status based on payment
            </label>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between mt-4">
        <div>
          <a href="{{ url('payments') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Payments
          </a>
          <a href="{{ url('payments/' . $payment->id) }}" class="btn btn-view ms-2">
            <i class="fas fa-eye"></i> View Payment
          </a>
        </div>
        <div>
          <button type="button" class="btn btn-outline-secondary me-2" onclick="resetToOriginal()">
            <i class="fas fa-history"></i> Reset Changes
          </button>
          <button type="submit" class="btn btn-update">
            <i class="fas fa-save"></i> Update Payment
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
    max-width: 1000px;
    margin: 0 auto;
  }
  
  .card-header {
    background: linear-gradient(145deg, #e74c3c, #c0392b);
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
    border-color: #e74c3c;
    box-shadow: 0 0 0 0.25rem rgba(231, 76, 60, 0.25);
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
  
  .form-check-input:checked {
    background-color: #e74c3c;
    border-color: #e74c3c;
  }
  
  .alert-school {
    border-left: 4px solid #e74c3c;
    background-color: #f8f9fa;
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
  
  .alert-info {
    background-color: #d1ecf1;
    border-color: #bee5eb;
    color: #0c5460;
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
  
  .bg-light {
    background-color: #f8f9fa !important;
  }
  
  /* Payment Summary */
  .summary-item {
    display: flex;
    justify-content: space-between;
    padding: 8px 0;
    border-bottom: 1px dashed #e9ecef;
  }
  
  .summary-label {
    font-weight: 600;
    color: #6c757d;
  }
  
  .summary-value {
    font-weight: 500;
    color: #333;
  }
  
  .summary-total {
    display: flex;
    justify-content: space-between;
    padding: 15px;
    background-color: #e74c3c;
    color: white;
    border-radius: 8px;
    margin-top: 10px;
  }
  
  .total-label {
    font-size: 18px;
    font-weight: 600;
  }
  
  .total-value {
    font-size: 24px;
    font-weight: 700;
  }
  
  /* Badge Styling */
  .badge {
    padding: 6px 12px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 12px;
  }
  
  /* Payment History */
  .history-timeline {
    position: relative;
    padding-left: 30px;
  }
  
  .history-timeline::before {
    content: '';
    position: absolute;
    left: 10px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: linear-gradient(to bottom, #e74c3c, #3498db);
  }
  
  .history-item {
    position: relative;
    margin-bottom: 20px;
    padding-left: 20px;
  }
  
  .history-item::before {
    content: '';
    position: absolute;
    left: -8px;
    top: 5px;
    width: 16px;
    height: 16px;
    border-radius: 50%;
    background-color: #e74c3c;
    border: 2px solid white;
  }
  
  .history-item.future::before {
    background-color: #2ecc71;
    animation: pulse 2s infinite;
  }
  
  @keyframes pulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.1); }
    100% { transform: scale(1); }
  }
  
  .history-date {
    font-size: 12px;
    color: #6c757d;
    margin-bottom: 5px;
  }
  
  .history-content {
    background-color: #f8f9fa;
    padding: 10px;
    border-radius: 6px;
    border: 1px solid #e9ecef;
  }
  
  code {
    background-color: #f8f9fa;
    padding: 2px 6px;
    border-radius: 4px;
    font-family: 'Courier New', monospace;
    color: #e74c3c;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Store original values for reset functionality
    const originalValues = {
      user_id: '{{ $payment->user_id }}',
      course_id: '{{ $payment->course_id }}',
      amount: '{{ $payment->amount }}',
      status: '{{ $payment->status }}',
      payment_method: '{{ $payment->payment_method }}',
      transaction_id: '{{ $payment->transaction_id }}',
      notes: `{!! addslashes($payment->notes ?? '') !!}`
    };
    
    // Character counter for notes
    const notesInput = document.getElementById('notes');
    const notesCharCount = document.getElementById('notesCharCount');
    
    function updateCharCount(element, counter) {
      const length = element.value.length;
      counter.textContent = length;
      
      const max = 1000;
      if (length > max * 0.9) {
        counter.classList.add('danger');
        counter.classList.remove('warning');
      } else if (length > max * 0.75) {
        counter.classList.add('warning');
        counter.classList.remove('danger');
      } else {
        counter.classList.remove('warning', 'danger');
      }
    }
    
    notesInput.addEventListener('input', () => updateCharCount(notesInput, notesCharCount));
    updateCharCount(notesInput, notesCharCount);
    
    // Copy transaction ID to clipboard
    function copyToClipboard(text) {
      navigator.clipboard.writeText(text).then(() => {
        const button = event.target.closest('button');
        const originalHtml = button.innerHTML;
        
        button.innerHTML = '<i class="fas fa-check"></i>';
        
        setTimeout(() => {
          button.innerHTML = originalHtml;
        }, 2000);
        
        showToast('Transaction ID copied to clipboard!', 'success');
      }).catch(err => {
        console.error('Failed to copy: ', err);
        showToast('Failed to copy transaction ID', 'error');
      });
    }
    
    // Use course price for amount (UPDATED - no API call)
    function useCoursePrice() {
      const courseSelect = document.getElementById('course_id');
      const courseId = courseSelect.value;
      
      if (!courseId) {
        alert('Please select a course first');
        return;
      }
      
      // Get the selected option and its data-price attribute
      const selectedOption = courseSelect.options[courseSelect.selectedIndex];
      const coursePrice = selectedOption.getAttribute('data-course-price') || '0.00';
      
      const amountInput = document.getElementById('amount');
      amountInput.value = coursePrice;
      
      // Update payment summary
      updatePaymentSummary();
    }
    
    // Generate transaction ID (UPDATED - simplified)
    function generateTransactionId() {
      const paymentMethod = document.getElementById('payment_method').value;
      const transactionIdInput = document.getElementById('transaction_id');
      
      let prefix = '';
      switch(paymentMethod) {
        case 'stripe':
          prefix = 'ch_';
          break;
        case 'paypal':
          prefix = 'PAYPAL-';
          break;
        case 'manual':
          prefix = 'MANUAL-';
          break;
        default:
          prefix = 'TRX-';
      }
      
      // Generate a unique ID with timestamp and random string
      const timestamp = Date.now().toString(36);
      const randomStr = Math.random().toString(36).substring(2, 8).toUpperCase();
      
      // Combine prefix, timestamp, and random string
      const generatedId = prefix + timestamp + randomStr;
      
      // Set the value
      transactionIdInput.value = generatedId;
      
      // Visual feedback
      transactionIdInput.style.backgroundColor = '#d4edda';
      setTimeout(() => {
        transactionIdInput.style.backgroundColor = '';
      }, 1000);
    }
    
    // Update payment summary (UPDATED - no API calls)
    function updatePaymentSummary() {
      const userSelect = document.getElementById('user_id');
      const courseSelect = document.getElementById('course_id');
      const amountInput = document.getElementById('amount');
      const statusSelect = document.getElementById('status');
      const paymentMethodSelect = document.getElementById('payment_method');
      
      // Get user info
      let userName = 'Not selected';
      if (userSelect.value) {
        const selectedOption = userSelect.options[userSelect.selectedIndex];
        userName = selectedOption.text.split(' (')[0] || 'Unknown User';
      }
      
      // Get course info
      let courseTitle = 'Not selected';
      if (courseSelect.value) {
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
        courseTitle = selectedOption.text.split(' ($')[0] || 'Unknown Course';
      }
      
      // Get amount
      const amountValue = parseFloat(amountInput.value) || 0;
      
      // Get status
      const status = statusSelect.value;
      const statusText = status.charAt(0).toUpperCase() + status.slice(1);
      
      // Get payment method
      const paymentMethod = paymentMethodSelect.value;
      
      // Update the summary display
      document.getElementById('summaryUser').textContent = userName;
      document.getElementById('summaryCourse').textContent = courseTitle;
      document.getElementById('summaryAmount').textContent = '$' + amountValue.toFixed(2);
      document.getElementById('summaryTotal').textContent = '$' + amountValue.toFixed(2);
      
      // Update status badges
      const statusColors = {
        'completed': 'success',
        'pending': 'warning',
        'failed': 'danger',
        'refunded': 'info'
      };
      
      const statusBadge = document.getElementById('summaryStatus');
      const futureStatusBadge = document.getElementById('futureStatus');
      
      statusBadge.textContent = statusText;
      statusBadge.className = 'badge bg-' + (statusColors[status] || 'secondary');
      
      futureStatusBadge.textContent = statusText;
      futureStatusBadge.className = 'badge bg-' + (statusColors[status] || 'secondary');
      
      // Update transaction ID help text based on payment method
      const transactionIdHelp = document.getElementById('transactionIdHelp');
      switch(paymentMethod) {
        case 'stripe':
          transactionIdHelp.textContent = 'Stripe charge ID (e.g., ch_...)';
          break;
        case 'paypal':
          transactionIdHelp.textContent = 'PayPal transaction ID';
          break;
        case 'manual':
          transactionIdHelp.textContent = 'Manual payment reference';
          break;
        default:
          transactionIdHelp.textContent = 'External transaction reference';
      }
    }
    
    // Show status change warning
    function showStatusChangeWarning() {
      const newStatus = document.getElementById('status').value;
      const originalStatus = '{{ $payment->status }}';
      const statusChangeWarning = document.getElementById('statusChangeWarning');
      
      if (newStatus !== originalStatus) {
        statusChangeWarning.classList.remove('d-none');
      } else {
        statusChangeWarning.classList.add('d-none');
      }
      
      updatePaymentSummary();
    }
    
    // Reset form to original values
    function resetToOriginal() {
      if (confirm('Are you sure you want to reset all changes to the original values?')) {
        document.getElementById('user_id').value = originalValues.user_id;
        document.getElementById('course_id').value = originalValues.course_id;
        document.getElementById('amount').value = originalValues.amount;
        document.getElementById('status').value = originalValues.status;
        document.getElementById('payment_method').value = originalValues.payment_method;
        document.getElementById('transaction_id').value = originalValues.transaction_id;
        notesInput.value = originalValues.notes.replace(/\\'/g, "'");
        
        // Reset character count
        updateCharCount(notesInput, notesCharCount);
        
        // Hide warnings
        document.getElementById('statusChangeWarning').classList.add('d-none');
        
        // Update summary
        updatePaymentSummary();
        
        // Show success message
        const successAlert = document.createElement('div');
        successAlert.className = 'alert alert-success mt-3';
        successAlert.innerHTML = '<i class="fas fa-check-circle"></i> All changes reset to original values';
        document.querySelector('.card-body').insertBefore(successAlert, document.querySelector('form'));
        
        setTimeout(() => successAlert.remove(), 3000);
      }
    }
    
    // Form validation
    const form = document.getElementById('paymentForm');
    form.addEventListener('submit', function(e) {
      const userId = document.getElementById('user_id').value;
      const courseId = document.getElementById('course_id').value;
      const amount = document.getElementById('amount').value;
      const status = document.getElementById('status').value;
      const paymentMethod = document.getElementById('payment_method').value;
      const transactionId = document.getElementById('transaction_id').value;
      
      let isValid = true;
      
      if (!userId) {
        document.getElementById('user_id').classList.add('is-invalid');
        isValid = false;
      }
      
      if (!courseId) {
        document.getElementById('course_id').classList.add('is-invalid');
        isValid = false;
      }
      
      if (!amount || parseFloat(amount) <= 0) {
        document.getElementById('amount').classList.add('is-invalid');
        isValid = false;
      }
      
      // Warn about status changes
      const originalStatus = '{{ $payment->status }}';
      const newStatus = status;
      if (originalStatus !== newStatus) {
        if (!confirm(`Changing payment status from "${originalStatus}" to "${newStatus}". Continue?`)) {
          e.preventDefault();
          return false;
        }
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
      input.addEventListener('change', () => {
        formChanged = input.value !== originalValue;
        updatePaymentSummary();
      });
    });
    
    window.addEventListener('beforeunload', function(e) {
      if (formChanged) {
        e.preventDefault();
        e.returnValue = 'You have unsaved changes. Are you sure you want to leave?';
      }
    });
    
    // Initialize
    updatePaymentSummary();
  });
  
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
</script>

@stop