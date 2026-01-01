@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-credit-card"></i> Create New Payment
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

    <form action="{{ url('payments') }}" method="post" id="paymentForm">
      @csrf
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="user_id" class="form-label">User *</label>
          <select name="user_id" id="user_id" class="form-select" required>
            <option value="">Select User</option>
            @foreach($users ?? [] as $user)
              <option value="{{ $user->id }}" {{ old('user_id', request('user_id')) == $user->id ? 'selected' : '' }}>
                {{ $user->name }} ({{ $user->email }})
              </option>
            @endforeach
          </select>
          @if(empty($users))
            <div class="alert alert-warning mt-2">
              <i class="fas fa-exclamation-triangle"></i> No users found. 
              <a href="{{ url('users/create') }}" class="alert-link">Create a user first</a>.
            </div>
          @endif
          <div class="form-text">Select the user making the payment</div>
          <div id="userInfo" class="mt-2"></div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="course_id" class="form-label">Course *</label>
          <select name="course_id" id="course_id" class="form-select" required>
            <option value="">Select Course</option>
            @foreach($courses ?? [] as $course)
              <option value="{{ $course->id }}" {{ old('course_id', request('course_id')) == $course->id ? 'selected' : '' }}>
                {{ $course->title }} (${{ number_format($course->price ?? 0, 2) }})
              </option>
            @endforeach
          </select>
          @if(empty($courses))
            <div class="alert alert-warning mt-2">
              <i class="fas fa-exclamation-triangle"></i> No courses found. 
              <a href="{{ url('courses/create') }}" class="alert-link">Create a course first</a>.
            </div>
          @endif
          <div class="form-text">Select the course being purchased</div>
          <div id="courseInfo" class="mt-2"></div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="amount" class="form-label">Amount *</label>
          <div class="input-group">
            <span class="input-group-text">$</span>
            <input type="number" name="amount" id="amount" class="form-control" 
                   value="{{ old('amount') }}" step="0.01" min="0" required 
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
          <select name="status" id="status" class="form-select" required>
            @foreach(App\Models\Payment::STATUSES as $status)
              <option value="{{ $status }}" {{ old('status', 'pending') == $status ? 'selected' : '' }}>
                {{ ucfirst($status) }}
              </option>
            @endforeach
          </select>
          <div class="form-text">Initial payment status</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="payment_method" class="form-label">Payment Method *</label>
          <select name="payment_method" id="payment_method" class="form-select" required>
            @foreach(App\Models\Payment::METHODS as $method)
              <option value="{{ $method }}" {{ old('payment_method', 'manual') == $method ? 'selected' : '' }}>
                {{ ucfirst($method) }}
              </option>
            @endforeach
          </select>
          <div class="form-text">How the payment was made</div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="transaction_id" class="form-label">Transaction ID</label>
          <input type="text" name="transaction_id" id="transaction_id" class="form-control" 
                 value="{{ old('transaction_id') }}" placeholder="e.g., ch_1ABCDEFGHIJKLMNOPQRSTUVWX">
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
            <h6><i class="fas fa-file-invoice-dollar"></i> Payment Summary</h6>
            <div id="paymentSummary" class="py-3">
              <div class="row">
                <div class="col-md-6">
                  <div class="summary-item">
                    <span class="summary-label">User:</span>
                    <span class="summary-value" id="summaryUser">Not selected</span>
                  </div>
                  <div class="summary-item">
                    <span class="summary-label">Course:</span>
                    <span class="summary-value" id="summaryCourse">Not selected</span>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="summary-item">
                    <span class="summary-label">Amount:</span>
                    <span class="summary-value" id="summaryAmount">$0.00</span>
                  </div>
                  <div class="summary-item">
                    <span class="summary-label">Status:</span>
                    <span class="summary-value">
                      <span class="badge bg-warning" id="summaryStatus">Pending</span>
                    </span>
                  </div>
                </div>
              </div>
              <div class="row mt-3">
                <div class="col-md-12">
                  <div class="summary-total">
                    <span class="total-label">Total Payment:</span>
                    <span class="total-value" id="summaryTotal">$0.00</span>
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
                  placeholder="Additional notes about this payment (e.g., reason for manual payment, special discount, etc.)">{{ old('notes') }}</textarea>
        <div class="form-text">Optional notes for internal reference</div>
        <div class="char-counter">
          <span id="notesCharCount">0</span>/1000 characters
        </div>
      </div>

      <!-- Payment Preview -->
      <div class="mb-3">
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="send_receipt" id="send_receipt" value="1" 
                 {{ old('send_receipt') ? 'checked' : '' }}>
          <label class="form-check-label" for="send_receipt">
            Send payment receipt to user
          </label>
          <div class="form-text">Email receipt will be sent to user's email address</div>
        </div>
        
        <div class="form-check mt-2">
          <input class="form-check-input" type="checkbox" name="enroll_user" id="enroll_user" value="1" 
                 {{ old('enroll_user', true) ? 'checked' : '' }}>
          <label class="form-check-label" for="enroll_user">
            Enroll user in course upon payment completion
          </label>
          <div class="form-text">Automatically create enrollment record for this course</div>
        </div>
      </div>

      <!-- Quick Actions -->
      <div class="row mb-4">
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6><i class="fas fa-lightbulb"></i> Quick Amounts</h6>
              <div class="d-flex flex-wrap gap-2 mt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAmount(19.99)">$19.99</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAmount(29.99)">$29.99</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAmount(49.99)">$49.99</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAmount(99.99)">$99.99</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAmount(199.99)">$199.99</button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="setAmount(299.99)">$299.99</button>
              </div>
              <small class="text-muted mt-2 d-block">Click to set common payment amounts</small>
            </div>
          </div>
        </div>
        
        <div class="col-md-6">
          <div class="card bg-light">
            <div class="card-body">
              <h6><i class="fas fa-history"></i> Recent Transactions</h6>
              <div id="recentTransactions" class="mt-2">
                <p class="text-muted mb-0">No recent payments</p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Duplicate Warning -->
      <div class="alert alert-warning d-none" id="duplicateWarning">
        <i class="fas fa-exclamation-triangle"></i> 
        <strong>Warning:</strong> This user already has a payment for this course. 
        Creating a new payment may create duplicate records.
      </div>

      <div class="d-flex justify-content-between mt-4">
        <div>
          <a href="{{ url('payments') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back to Payments
          </a>
          <button type="button" class="btn btn-outline-secondary ms-2" onclick="saveAsDraft()">
            <i class="fas fa-save"></i> Save as Draft
          </button>
        </div>
        <div>
          <button type="reset" class="btn btn-outline-secondary me-2">
            <i class="fas fa-redo"></i> Reset Form
          </button>
          <button type="submit" class="btn btn-custom">
            <i class="fas fa-credit-card"></i> Create Payment
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
  
  .btn-custom {
    background: linear-gradient(145deg, #e74c3c, #c0392b);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
  }
  
  .btn-custom:hover {
    background: linear-gradient(145deg, #c0392b, #a93226);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(231, 76, 60, 0.3);
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
  
  .form-check-input:checked {
    background-color: #e74c3c;
    border-color: #e74c3c;
  }
  
  .alert-school {
    border-left: 4px solid #e74c3c;
    background-color: #f8f9fa;
  }
  
  .alert-warning {
    background-color: #fff3cd;
    border-color: #ffecb5;
    color: #664d03;
  }
  
  .alert-warning a {
    color: #664d03;
    text-decoration: underline;
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
  
  /* User/Course Info Cards */
  .info-card {
    padding: 10px;
    background-color: white;
    border-radius: 6px;
    border: 1px solid #e9ecef;
    margin-top: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
  }
  
  .info-card h6 {
    margin: 0 0 5px 0;
    font-size: 14px;
    color: #333;
  }
  
  .info-card p {
    margin: 0;
    font-size: 13px;
    color: #666;
  }
  
  /* Quick Amounts */
  .gap-2 {
    gap: 8px;
  }
  
  /* Recent Transactions */
  .transaction-item {
    padding: 8px;
    border-bottom: 1px solid #e9ecef;
    font-size: 13px;
  }
  
  .transaction-item:last-child {
    border-bottom: none;
  }
  
  .transaction-amount {
    font-weight: 600;
    color: #e74c3c;
  }
  
  .transaction-status {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 10px;
  }
  
  .status-completed {
    background-color: #d1e7dd;
    color: #0f5132;
  }
  
  .status-pending {
    background-color: #fff3cd;
    color: #664d03;
  }
  
  /* Transaction ID Generator */
  .transaction-highlight {
    animation: highlight 2s ease;
  }
  
  @keyframes highlight {
    0% { background-color: #d4edda; }
    100% { background-color: white; }
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
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
    
    // Add event listeners for real-time updates
    document.getElementById('user_id').addEventListener('change', loadUserInfo);
    document.getElementById('course_id').addEventListener('change', loadCourseInfo);
    document.getElementById('amount').addEventListener('input', updatePaymentSummary);
    document.getElementById('status').addEventListener('change', updatePaymentSummary);
    document.getElementById('payment_method').addEventListener('change', updatePaymentSummary);
    
    // Initial update
    updatePaymentSummary();
    
    // Check for pre-selected values
    if (document.getElementById('user_id').value) {
      loadUserInfo();
    }
    if (document.getElementById('course_id').value) {
      loadCourseInfo();
    }
  });
  
  // Load user information
  function loadUserInfo() {
    const userId = document.getElementById('user_id').value;
    const userInfoDiv = document.getElementById('userInfo');
    
    if (!userId) {
      userInfoDiv.innerHTML = '';
      updatePaymentSummary();
      return;
    }
    
    // Get the selected option
    const userSelect = document.getElementById('user_id');
    const selectedOption = userSelect.options[userSelect.selectedIndex];
    const userText = selectedOption.text;
    const userName = userText.split(' (')[0] || 'Unknown User';
    const userEmail = userText.match(/\((.*)\)/)?.[1] || 'N/A';
    
    userInfoDiv.innerHTML = `
      <div class="info-card">
        <h6><i class="fas fa-user"></i> ${userName}</h6>
        <p class="mb-1"><strong>Email:</strong> ${userEmail}</p>
        <p class="mb-0">User selected</p>
      </div>
    `;
    
    updatePaymentSummary();
  }
  
  // Load course information
  function loadCourseInfo() {
    const courseId = document.getElementById('course_id').value;
    const courseInfoDiv = document.getElementById('courseInfo');
    
    if (!courseId) {
      courseInfoDiv.innerHTML = '';
      updatePaymentSummary();
      return;
    }
    
    // Get the selected option
    const courseSelect = document.getElementById('course_id');
    const selectedOption = courseSelect.options[courseSelect.selectedIndex];
    const courseText = selectedOption.text;
    
    // Extract course title and price
    const titleMatch = courseText.split(' ($');
    const courseTitle = titleMatch[0] || 'Unknown Course';
    const coursePrice = titleMatch[1] ? titleMatch[1].replace(')', '') : '0.00';
    
    courseInfoDiv.innerHTML = `
      <div class="info-card">
        <h6><i class="fas fa-book"></i> ${courseTitle}</h6>
        <p class="mb-1"><strong>Price:</strong> $${coursePrice}</p>
        <p class="mb-0">Course selected</p>
      </div>
    `;
    
    // Auto-fill amount with course price if empty
    const amountInput = document.getElementById('amount');
    if (!amountInput.value || amountInput.value === '0' || amountInput.value === '0.00') {
      amountInput.value = coursePrice;
    }
    
    updatePaymentSummary();
  }
  
  // Use course price for amount
  function useCoursePrice() {
    const courseId = document.getElementById('course_id').value;
    
    if (!courseId) {
      alert('Please select a course first');
      return;
    }
    
    const courseSelect = document.getElementById('course_id');
    const selectedOption = courseSelect.options[courseSelect.selectedIndex];
    const courseText = selectedOption.text;
    const priceMatch = courseText.match(/\$([\d,.]+)/);
    const price = priceMatch ? priceMatch[1] : '0.00';
    
    const amountInput = document.getElementById('amount');
    amountInput.value = price;
    updatePaymentSummary();
  }
  
  // Update payment summary
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
    
    // Update status badge
    const statusBadge = document.getElementById('summaryStatus');
    statusBadge.textContent = statusText;
    
    // Update badge color
    const statusColors = {
      'completed': 'success',
      'pending': 'warning',
      'failed': 'danger',
      'refunded': 'info'
    };
    statusBadge.className = 'badge bg-' + (statusColors[status] || 'secondary');
    
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
  
  // Generate transaction ID
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
    const timestamp = Date.now().toString(36); // Current time in base36
    const randomStr = Math.random().toString(36).substring(2, 8).toUpperCase(); // Random string
    
    // Combine prefix, timestamp, and random string
    const generatedId = prefix + timestamp + randomStr;
    
    // Set the value
    transactionIdInput.value = generatedId;
    
    // Visual feedback
    transactionIdInput.classList.add('transaction-highlight');
    setTimeout(() => {
      transactionIdInput.classList.remove('transaction-highlight');
    }, 2000);
  }
  
  // Set quick amount
  function setAmount(amount) {
    document.getElementById('amount').value = amount.toFixed(2);
    updatePaymentSummary();
  }
  
  // Save as draft (sets status to pending)
  function saveAsDraft() {
    document.getElementById('status').value = 'pending';
    document.getElementById('paymentForm').submit();
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
    
    // Check transaction ID requirement
    if ((paymentMethod === 'stripe' || paymentMethod === 'paypal') && 
        (status === 'completed' || status === 'failed') && 
        !transactionId) {
      document.getElementById('transaction_id').classList.add('is-invalid');
      isValid = false;
    }
    
    // Check for duplicate warning
    const duplicateWarning = document.getElementById('duplicateWarning');
    if (!duplicateWarning.classList.contains('d-none')) {
      if (!confirm('A payment already exists for this user and course. Continue anyway?')) {
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
</script>

@stop