@extends('layout')
@section('content')

<div class="card">
  <div class="card-header">
    <h4 class="card-title">
      <i class="fas fa-user-plus"></i> Create New User
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

    <form action="{{ url('users') }}" method="post">
      @csrf
      
      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="name" class="form-label">Full Name *</label>
          <input type="text" name="name" id="name" class="form-control" 
                 value="{{ old('name') }}" required placeholder="Enter full name">
          <div class="form-text">Enter the user's full name</div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="email" class="form-label">Email Address *</label>
          <input type="email" name="email" id="email" class="form-control" 
                 value="{{ old('email') }}" required placeholder="user@example.com">
          <div class="form-text">Must be a valid email address</div>
        </div>
      </div>

      <div class="row">
        <div class="col-md-6 mb-3">
          <label for="password" class="form-label">Password *</label>
          <input type="password" name="password" id="password" class="form-control" 
                 required placeholder="Minimum 8 characters">
          <div class="form-text">Minimum 8 characters</div>
        </div>

        <div class="col-md-6 mb-3">
          <label for="password_confirmation" class="form-label">Confirm Password *</label>
          <input type="password" name="password_confirmation" id="password_confirmation" 
                 class="form-control" required placeholder="Re-enter password">
          <div class="form-text">Re-enter the password for confirmation</div>
        </div>
      </div>

      <div class="mb-3">
        <label for="role" class="form-label">User Role *</label>
        <select name="role" id="role" class="form-select" required>
          <option value="">Select Role</option>
          @foreach(App\Models\User::ROLES as $role)
            <option value="{{ $role }}" {{ old('role') == $role ? 'selected' : '' }}>
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
        <div class="form-check">
          <input class="form-check-input" type="checkbox" name="email_verified" id="email_verified" 
                 value="1" {{ old('email_verified') ? 'checked' : '' }}>
          <label class="form-check-label" for="email_verified">
            Mark email as verified
          </label>
          <div class="form-text">Check to skip email verification</div>
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
            <i class="fas fa-user-plus"></i> Create User
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
</style>

<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Password confirmation validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('password_confirmation');
    
    function validatePassword() {
      if (password.value !== confirmPassword.value) {
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
    
    // Form submission validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
      const nameInput = document.getElementById('name');
      const emailInput = document.getElementById('email');
      const passwordInput = document.getElementById('password');
      const roleSelect = document.getElementById('role');
      
      let isValid = true;
      
      if (!nameInput.value.trim()) {
        nameInput.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!emailInput.value.trim()) {
        emailInput.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!passwordInput.value.trim()) {
        passwordInput.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!roleSelect.value) {
        roleSelect.classList.add('is-invalid');
        isValid = false;
      }
      
      if (!isValid) {
        e.preventDefault();
        return false;
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