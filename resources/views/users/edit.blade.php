@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <div class="d-flex justify-content-between align-items-center">
      <h4 class="card-title mb-0">
        <i class="fas fa-user-edit"></i> Edit User: {{ $user->name }}
      </h4>
      <a href="{{ url('users/' . $user->id) }}" class="btn btn-view">
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

    <form action="{{ url('users/' . $user->id) }}" method="post">
      @csrf
      @method("PATCH")
      <input type="hidden" name="id" id="id" value="{{ $user->id }}" />
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="name" class="form-label">Full Name *</label>
          <input type="text" name="name" id="name" class="form-control" 
                 value="{{ old('name', $user->name) }}" required placeholder="Enter full name">
          <div class="form-text">Enter the user's full name</div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="email" class="form-label">Email Address *</label>
          <input type="email" name="email" id="email" class="form-control" 
                 value="{{ old('email', $user->email) }}" required placeholder="user@example.com">
          <div class="form-text">Must be a valid email address</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="password" class="form-label">Password</label>
          <input type="password" name="password" id="password" class="form-control" 
                 placeholder="Leave blank to keep current password">
          <div class="form-text">Leave blank to keep current password</div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="password_confirmation" class="form-label">Confirm Password</label>
          <input type="password" name="password_confirmation" id="password_confirmation" 
                 class="form-control" placeholder="Confirm new password">
          <div class="form-text">Only required if changing password</div>
        </div>
      </div>

      <div class="mb-3">
        <label for="role" class="form-label">User Role *</label>
        <select name="role" id="role" class="form-select" required>
          <option value="">Select Role</option>
          @foreach(App\Models\User::ROLES as $role)
            <option value="{{ $role }}" {{ (old('role', $user->role) == $role) ? 'selected' : '' }}>
              {{ ucfirst($role) }}
            </option>
          @endforeach
        </select>
        <div class="form-text mt-1">
          <strong>Role descriptions:</strong><br>
          • <span class="text-primary">Admin</span> - Full system access<br>
          • <span class="text-warning">Instructor</span> - Can create and manage courses<br>
          • <span class="text-secondary">Student</span> - Can enroll in and take courses
        </div>
      </div>

      <div class="mb-3">
        <label class="form-label">Account Status</label>
        <div class="row">
          <div class="col-md-6">
            <div class="form-check mb-2">
              <input class="form-check-input" type="checkbox" name="email_verified" id="email_verified" 
                     value="1" {{ (old('email_verified', $user->email_verified_at) ? 'checked' : '') }}>
              <label class="form-check-label" for="email_verified">
                Email Verified
              </label>
              <div class="form-text">
                @if($user->email_verified_at)
                  Verified on: {{ $user->email_verified_at->format('M d, Y h:i A') }}
                @else
                  Email not verified
                @endif
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="account-status p-3 bg-light rounded">
              <strong>Current Status:</strong>
              <div class="mt-1">
                @if($user->email_verified_at)
                  <span class="badge bg-success">Verified</span>
                @else
                  <span class="badge bg-warning">Unverified</span>
                @endif
                
                @if($user->isAdmin())
                  <span class="badge bg-danger ms-1">Admin</span>
                @elseif($user->isInstructor())
                  <span class="badge bg-warning ms-1">Instructor</span>
                @else
                  <span class="badge bg-secondary ms-1">Student</span>
                @endif
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-between">
        <a href="{{ url('users') }}" class="btn btn-secondary">
          <i class="fas fa-arrow-left"></i> Back to Users
        </a>
        <div>
          <button type="reset" class="btn btn-outline-secondary me-2">
            <i class="fas fa-redo"></i> Reset
          </button>
          <button type="submit" class="btn btn-custom">
            <i class="fas fa-save"></i> Update User
          </button>
        </div>
      </div>
    </form>
   
  </div>
</div>

<!-- User Statistics Card -->
<div class="card mt-4">
  <div class="card-header">
    <h5 class="card-title mb-0">
      <i class="fas fa-chart-bar"></i> User Statistics
    </h5>
  </div>
  <div class="card-body">
    <div class="row text-center">
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Member Since</h6>
          <h5 class="text-primary">{{ $user->created_at->format('M d, Y') }}</h5>
          <small>{{ $user->created_at->diffForHumans() }}</small>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Last Updated</h6>
          <h5 class="text-success">{{ $user->updated_at->format('M d, Y') }}</h5>
          <small>{{ $user->updated_at->diffForHumans() }}</small>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Account Type</h6>
          <h4>
            @if($user->isAdmin())
              <span class="badge bg-danger">Administrator</span>
            @elseif($user->isInstructor())
              <span class="badge bg-warning">Instructor</span>
            @else
              <span class="badge bg-secondary">Student</span>
            @endif
          </h4>
          <small>Current Role</small>
        </div>
      </div>
      <div class="col-md-3 mb-3">
        <div class="stat-card p-3 rounded">
          <h6 class="text-muted">Email Status</h6>
          <h4>
            @if($user->email_verified_at)
              <span class="badge bg-success">Verified</span>
            @else
              <span class="badge bg-warning">Unverified</span>
            @endif
          </h4>
          <small>Verification Status</small>
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
    margin-bottom: 20px;
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
  
  .btn-custom {
    background: linear-gradient(145deg, #3498db, #2980b9);
    color: white;
    border: none;
    padding: 10px 25px;
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.3s ease;
  }
  
  .btn-custom:hover {
    background: linear-gradient(145deg, #2980b9, #1c5a7a);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    color: white;
  }
  
  .btn-view {
    background: linear-gradient(145deg, #2ecc71, #27ae60);
    color: white;
    border: none;
  }
  
  .btn-view:hover {
    background: linear-gradient(145deg, #27ae60, #219653);
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
    background-color: #3498db;
    border-color: #3498db;
  }
  
  .alert-school {
    border-left: 4px solid #3498db;
    background-color: #f0f9ff;
  }
  
  .form-text strong {
    color: #495057;
  }
  
  .account-status {
    border-left: 4px solid #3498db;
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
  
  .badge {
    padding: 8px 16px;
    border-radius: 20px;
    font-weight: 500;
    font-size: 14px;
  }
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation (only if password is entered)
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');
    
    function validatePassword() {
      if (password.value && password.value !== confirmPassword.value) {
        confirmPassword.setCustomValidity("Passwords don't match");
        confirmPassword.classList.add('is-invalid');
      } else {
        confirmPassword.setCustomValidity('');
        confirmPassword.classList.remove('is-invalid');
      }
    }
    
    password.addEventListener('input', validatePassword);
    confirmPassword.addEventListener('input', validatePassword);
    
    // Email validation
    const emailInput = document.getElementById('email');
    emailInput.addEventListener('blur', function() {
      const email = this.value;
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      
      if (email && !emailRegex.test(email)) {
        this.classList.add('is-invalid');
        this.setCustomValidity('Please enter a valid email address');
      } else {
        this.classList.remove('is-invalid');
        this.setCustomValidity('');
      }
    });
    
    // Email verification checkbox logic
    const emailVerifiedCheckbox = document.getElementById('email_verified');
    const currentEmailStatus = "{{ $user->email_verified_at ? 'verified' : 'unverified' }}";
    
    emailVerifiedCheckbox.addEventListener('change', function() {
      if (this.checked) {
        if (!confirm('Are you sure you want to mark this email as verified?')) {
          this.checked = false;
        }
      } else {
        if (currentEmailStatus === 'verified' && !confirm('Are you sure you want to mark this email as unverified?')) {
          this.checked = true;
        }
      }
    });
    
    // Show confirmation before leaving unsaved changes
    let formChanged = false;
    const formInputs = document.querySelectorAll('form input, form select');
    
    formInputs.forEach(input => {
      const originalValue = input.value;
      input.addEventListener('input', () => {
        if (input.value !== originalValue) {
          formChanged = true;
        }
      });
    });
    
    window.addEventListener('beforeunload', function(e) {
      if (formChanged) {
        e.preventDefault();
        e.returnValue = '';
      }
    });
    
    // Form submission validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
      const nameInput = document.getElementById('name');
      const emailInput = document.getElementById('email');
      const roleSelect = document.getElementById('role');
      const passwordInput = document.getElementById('password');
      const confirmPasswordInput = document.getElementById('password_confirmation');
      
      let isValid = true;
      
      if (!nameInput.value.trim()) {
        nameInput.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!emailInput.value.trim()) {
        emailInput.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!roleSelect.value) {
        roleSelect.classList.add('is-invalid');
        isValid = false;
      }
      
      // Check password confirmation if password is entered
      if (passwordInput.value && passwordInput.value !== confirmPasswordInput.value) {
        confirmPasswordInput.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!isValid) {
        e.preventDefault();
        return false;
      }
      
      // Confirm role change if changing from/to admin
      const currentRole = "{{ $user->role }}";
      const newRole = roleSelect.value;
      
      if (currentRole !== newRole) {
        if (currentRole === 'admin' || newRole === 'admin') {
          if (!confirm(`Changing role from ${currentRole} to ${newRole}. Are you sure?`)) {
            e.preventDefault();
            return false;
          }
        }
      }
      
      return true;
    });
    
    // Remove invalid class on input
    const inputs = document.querySelectorAll('input, select');
    inputs.forEach(input => {
      input.addEventListener('input', function() {
        this.classList.remove('is-invalid');
      });
    });
  });
</script>

@stop