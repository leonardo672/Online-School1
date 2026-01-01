@extends('layout')

@section('content')

<!-- Main Card for Certificate Creation -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-certificate"></i> Issue New Certificate</h2>
            <a href="{{ url('/certificates') }}" class="btn btn-outline-light btn-sm">
                <i class="fas fa-arrow-left"></i> Back to Certificates
            </a>
        </div>
    </div>

    <div class="card-body">
        @if ($errors->any())
            <div class="alert alert-danger mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <div>
                        <strong>Oops! Something went wrong.</strong>
                        <ul class="mb-0 mt-2">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <!-- Certificate Form -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-edit"></i> Certificate Details</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ url('/certificates') }}" id="certificateForm">
                            @csrf
                            
                            <!-- User Selection -->
                            <div class="mb-4">
                                <label for="user_id" class="form-label">
                                    <i class="fas fa-user me-1"></i> Recipient *
                                </label>
                                <select name="user_id" id="user_id" 
                                        class="form-select @error('user_id') is-invalid @enderror" 
                                        required
                                        onchange="loadUserCertificates(this.value)">
                                    <option value="">Select a User</option>
                                    @foreach($users as $user)
                                        <option value="{{ $user->id }}" 
                                            {{ old('user_id') == $user->id ? 'selected' : '' }}
                                            data-email="{{ $user->email }}"
                                            data-name="{{ $user->name }}">
                                            {{ $user->name }} ({{ $user->email }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('user_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select the user who will receive this certificate.</small>
                                
                                <!-- User Certificate Stats -->
                                <div id="userCertificateStats" class="mt-3" style="display: none;">
                                    <div class="card bg-light">
                                        <div class="card-body p-3">
                                            <h6 class="mb-2">User's Certificate Summary</h6>
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <h5 id="userTotalCertificates" class="text-primary">0</h5>
                                                        <small class="text-muted">Total</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <h5 id="userValidCertificates" class="text-success">0</h5>
                                                        <small class="text-muted">Valid</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <h5 id="userExpiredCertificates" class="text-danger">0</h5>
                                                        <small class="text-muted">Expired</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Course Selection -->
                            <div class="mb-4">
                                <label for="course_id" class="form-label">
                                    <i class="fas fa-book me-1"></i> Course *
                                </label>
                                <select name="course_id" id="course_id" 
                                        class="form-select @error('course_id') is-invalid @enderror" 
                                        required
                                        onchange="loadCourseCertificates(this.value)">
                                    <option value="">Select a Course</option>
                                    @foreach($courses as $course)
                                        <option value="{{ $course->id }}" 
                                            {{ old('course_id') == $course->id ? 'selected' : '' }}
                                            data-title="{{ $course->title }}"
                                            data-code="{{ $course->code ?? '' }}">
                                            {{ $course->title }} ({{ $course->code ?? 'No Code' }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('course_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Select the course for which this certificate is issued.</small>
                                
                                <!-- Course Certificate Stats -->
                                <div id="courseCertificateStats" class="mt-3" style="display: none;">
                                    <div class="card bg-light">
                                        <div class="card-body p-3">
                                            <h6 class="mb-2">Course Certificate Summary</h6>
                                            <div class="row text-center">
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <h5 id="courseTotalCertificates" class="text-primary">0</h5>
                                                        <small class="text-muted">Total</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <h5 id="courseValidCertificates" class="text-success">0</h5>
                                                        <small class="text-muted">Valid</small>
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <div class="stat-item">
                                                        <h5 id="courseExpiredCertificates" class="text-danger">0</h5>
                                                        <small class="text-muted">Expired</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Certificate Code -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label for="certificate_code" class="form-label mb-0">
                                        <i class="fas fa-hashtag me-1"></i> Certificate Code *
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="generateCertificateCode()">
                                        <i class="fas fa-sync-alt"></i> Generate Code
                                    </button>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-certificate"></i>
                                    </span>
                                    <input type="text" name="certificate_code" id="certificate_code" 
                                           class="form-control @error('certificate_code') is-invalid @enderror" 
                                           value="{{ old('certificate_code', $suggestedCode ?? '') }}" 
                                           placeholder="CERT-XXXX-XXXX-XXXX" 
                                           required>
                                </div>
                                @error('certificate_code')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    Unique identifier for this certificate. Auto-generated if left blank.
                                </small>
                                
                                <!-- Code Availability Check -->
                                <div id="codeAvailability" class="mt-2" style="display: none;">
                                    <span class="badge" id="availabilityBadge">
                                        <i class="fas fa-spinner fa-spin"></i> Checking...
                                    </span>
                                </div>
                            </div>

                            <!-- Issued Date -->
                            <div class="mb-4">
                                <label for="issued_at" class="form-label">
                                    <i class="fas fa-calendar-check me-1"></i> Issued Date *
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                    <input type="datetime-local" name="issued_at" id="issued_at" 
                                           class="form-control @error('issued_at') is-invalid @enderror" 
                                           value="{{ old('issued_at', now()->format('Y-m-d\TH:i')) }}" 
                                           required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setIssuedDate('now')">
                                        Now
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setIssuedDate('custom')">
                                        Custom
                                    </button>
                                </div>
                                @error('issued_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Date and time when the certificate is issued.</small>
                            </div>

                            <!-- Expiry Date -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label for="expires_at" class="form-label mb-0">
                                        <i class="fas fa-calendar-times me-1"></i> Expiry Date
                                    </label>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="checkbox" id="noExpiry" 
                                               onchange="toggleExpiryDate()">
                                        <label class="form-check-label" for="noExpiry">No Expiry</label>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-times"></i>
                                    </span>
                                    <input type="datetime-local" name="expires_at" id="expires_at" 
                                           class="form-control @error('expires_at') is-invalid @enderror" 
                                           value="{{ old('expires_at') }}">
                                    <button type="button" class="btn btn-outline-secondary" onclick="setExpiryDate('30')">
                                        30 Days
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setExpiryDate('90')">
                                        90 Days
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setExpiryDate('365')">
                                        1 Year
                                    </button>
                                </div>
                                @error('expires_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Optional. Leave blank for certificates that don't expire.</small>
                                
                                <!-- Validity Period Display -->
                                <div id="validityPeriod" class="mt-2" style="display: none;">
                                    <span class="badge bg-info">
                                        <i class="fas fa-clock"></i>
                                        <span id="validityText"></span>
                                    </span>
                                </div>
                            </div>

                            <!-- Additional Options -->
                            <div class="card bg-light mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-cog"></i> Additional Options</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="send_email" id="send_email" value="1" checked>
                                                <label class="form-check-label" for="send_email">
                                                    <i class="fas fa-envelope me-1"></i> Send email notification
                                                </label>
                                                <small class="d-block text-muted">
                                                    Send certificate to user's email address
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="generate_pdf" id="generate_pdf" value="1" checked>
                                                <label class="form-check-label" for="generate_pdf">
                                                    <i class="fas fa-file-pdf me-1"></i> Generate PDF
                                                </label>
                                                <small class="d-block text-muted">
                                                    Create downloadable PDF version
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="notify_instructor" id="notify_instructor" value="1">
                                                <label class="form-check-label" for="notify_instructor">
                                                    <i class="fas fa-bell me-1"></i> Notify course instructor
                                                </label>
                                                <small class="d-block text-muted">
                                                    Send notification to course instructor
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Certificate Preview -->
                            <div class="card bg-light mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-eye"></i> Certificate Preview</h6>
                                </div>
                                <div class="card-body">
                                    <div class="certificate-preview p-4 border rounded text-center">
                                        <div class="preview-header mb-4">
                                            <i class="fas fa-certificate fa-3x text-warning mb-3"></i>
                                            <h4 class="text-primary">Certificate of Completion</h4>
                                        </div>
                                        <div class="preview-body mb-4">
                                            <p class="mb-2">This certifies that</p>
                                            <h3 id="previewUserName" class="text-dark mb-4">[User Name]</h3>
                                            <p class="mb-2">has successfully completed the course</p>
                                            <h4 id="previewCourseName" class="text-success mb-4">[Course Title]</h4>
                                            <div class="row justify-content-center mb-4">
                                                <div class="col-md-6">
                                                    <div class="preview-detail">
                                                        <small class="text-muted">Certificate Code</small>
                                                        <p id="previewCertCode" class="mb-0">[CERT-XXXX-XXXX-XXXX]</p>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="preview-detail">
                                                        <small class="text-muted">Issued Date</small>
                                                        <p id="previewIssuedDate" class="mb-0">{{ now()->format('M d, Y') }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="preview-footer">
                                            <small class="text-muted">Issued by {{ config('app.name') }}</small>
                                        </div>
                                    </div>
                                    <button type="button" class="btn btn-outline-info mt-3" onclick="refreshPreview()">
                                        <i class="fas fa-sync-alt"></i> Refresh Preview
                                    </button>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                                <div>
                                    <a href="{{ url('/certificates') }}" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="button" class="btn btn-outline-warning ms-2" onclick="resetForm()">
                                        <i class="fas fa-undo"></i> Reset Form
                                    </button>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-outline-success me-2" onclick="saveAsDraft()">
                                        <i class="fas fa-save"></i> Save as Draft
                                    </button>
                                    <button type="submit" class="btn btn-add-certificate">
                                        <i class="fas fa-paper-plane"></i> Issue Certificate
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Guidelines Card -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-lightbulb"></i> Certificate Guidelines</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <h6><i class="fas fa-certificate text-warning"></i> Certificate Rules:</h6>
                            <ul class="list-unstyled">
                                <li><small><i class="fas fa-check-circle text-success me-1"></i> Each certificate must have a unique code</small></li>
                                <li><small><i class="fas fa-check-circle text-success me-1"></i> Certificate codes should follow the pattern: CERT-XXXX-XXXX-XXXX</small></li>
                                <li><small><i class="fas fa-check-circle text-success me-1"></i> Issued date cannot be in the future</small></li>
                                <li><small><i class="fas fa-check-circle text-success me-1"></i> Expiry date must be after issued date</small></li>
                                <li><small><i class="fas fa-check-circle text-success me-1"></i> A user can have multiple certificates for different courses</small></li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-user-check text-primary"></i> User Validation:</h6>
                            <ul class="list-unstyled">
                                <li><small>• Check if user already has a certificate for this course</small></li>
                                <li><small>• Verify user's email is valid for notifications</small></li>
                                <li><small>• Ensure user has completed course requirements</small></li>
                            </ul>
                        </div>
                        
                        <div class="mb-3">
                            <h6><i class="fas fa-shield-alt text-success"></i> Security Notes:</h6>
                            <ul class="list-unstyled">
                                <li><small>• Certificate codes are permanently recorded</small></li>
                                <li><small>• Issued certificates cannot be easily modified</small></li>
                                <li><small>• Expired certificates remain in the system for records</small></li>
                            </ul>
                        </div>
                        
                        <div>
                            <h6><i class="fas fa-question-circle text-warning"></i> Quick Tips:</h6>
                            <p class="small mb-0">
                                Use the "Generate Code" button for automatic unique code generation.
                                Set expiry dates only for time-limited certifications.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- User Information Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-user-circle"></i> Selected User</h5>
                    </div>
                    <div class="card-body">
                        <div id="userInfo" class="text-center py-4">
                            <i class="fas fa-user fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Select a user to view details</p>
                        </div>
                    </div>
                </div>

                <!-- Course Information Card -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-book"></i> Selected Course</h5>
                    </div>
                    <div class="card-body">
                        <div id="courseInfo" class="text-center py-4">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <p class="text-muted">Select a course to view details</p>
                        </div>
                    </div>
                </div>

                <!-- Duplicate Check Card -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-exclamation-triangle"></i> Duplicate Check</h5>
                    </div>
                    <div class="card-body">
                        <div id="duplicateCheck">
                            <div class="text-center py-3">
                                <i class="fas fa-search fa-2x text-muted mb-3"></i>
                                <p class="text-muted">Select user and course to check for duplicates</p>
                            </div>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-warning w-100 mt-2" 
                                onclick="checkForDuplicates()">
                            <i class="fas fa-search"></i> Check for Duplicates
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Duplicate Certificate Modal -->
<div class="modal fade" id="duplicateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning"><i class="fas fa-exclamation-triangle"></i> Duplicate Certificate Found</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>A certificate already exists for this user and course combination.</p>
                <div class="alert alert-warning">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-info-circle me-2"></i>
                        <div>
                            <strong>Existing Certificate Details:</strong><br>
                            <span id="duplicateCertCode"></span><br>
                            <span id="duplicateIssuedDate"></span><br>
                            <span id="duplicateStatus"></span>
                        </div>
                    </div>
                </div>
                <p>Do you want to proceed with issuing a new certificate anyway?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" onclick="proceedWithDuplicate()">
                    <i class="fas fa-forward"></i> Proceed Anyway
                </button>
            </div>
        </div>
    </div>
</div>

<style>
    /* Inherit base styles from index page */
    .card {
        border: none;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        border-radius: 15px;
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
        padding: 20px 30px;
        border-bottom: none;
    }
    
    .card-header h2 {
        margin: 0;
        font-weight: 600;
        font-size: 1.8rem;
    }
    
    .header-bg {
        background: linear-gradient(145deg, #3498db, #2980b9);
    }
    
    .card-body {
        padding: 30px;
    }

    /* Form Styles */
    .form-label {
        font-weight: 600;
        color: #333;
        margin-bottom: 8px;
    }
    
    .form-control, .form-select {
        border: 2px solid #e9ecef;
        border-radius: 8px;
        padding: 12px 15px;
        transition: all 0.3s ease;
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #3498db;
        box-shadow: 0 0 0 0.25rem rgba(52, 152, 219, 0.25);
    }
    
    .form-control.is-invalid, .form-select.is-invalid {
        border-color: #dc3545;
    }
    
    .input-group-text {
        background-color: #f8f9fa;
        border: 2px solid #e9ecef;
    }

    /* Button Styles */
    .btn {
        font-size: 14px;
        padding: 10px 20px;
        margin: 5px;
        border-radius: 8px;
        font-weight: 500;
        cursor: pointer;
        border: none;
        outline: none;
        transition: all 0.3s ease-in-out;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-transform: capitalize;
    }

    .btn-add-certificate {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
        font-weight: 600;
        padding: 12px 25px;
    }

    .btn-add-certificate:hover {
        background: linear-gradient(145deg, #2980b9, #1c5d87);
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(52, 152, 219, 0.4);
        color: white;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(108, 117, 125, 0.3);
        color: white;
    }

    .btn-outline-light {
        border: 1px solid rgba(255, 255, 255, 0.5);
        color: white;
    }

    .btn-outline-light:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: white;
        color: white;
    }

    .btn-outline-primary {
        border: 1px solid #3498db;
        color: #3498db;
    }

    .btn-outline-primary:hover {
        background-color: #3498db;
        color: white;
    }

    .btn-outline-secondary {
        border: 1px solid #6c757d;
        color: #6c757d;
    }

    .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: white;
    }

    .btn-outline-warning {
        border: 1px solid #f39c12;
        color: #f39c12;
    }

    .btn-outline-warning:hover {
        background-color: #f39c12;
        color: white;
    }

    .btn-outline-success {
        border: 1px solid #2ecc71;
        color: #2ecc71;
    }

    .btn-outline-success:hover {
        background-color: #2ecc71;
        color: white;
    }

    .btn-outline-info {
        border: 1px solid #17a2b8;
        color: #17a2b8;
    }

    .btn-outline-info:hover {
        background-color: #17a2b8;
        color: white;
    }

    .btn-sm {
        padding: 6px 10px;
        font-size: 12px;
        min-width: 36px;
    }

    /* Certificate Preview */
    .certificate-preview {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 2px solid #dee2e6;
        border-radius: 10px;
        position: relative;
        overflow: hidden;
    }
    
    .certificate-preview::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%233498db' fill-opacity='0.05' fill-rule='evenodd'/%3E%3C/svg%3E");
        opacity: 0.3;
    }
    
    .preview-header {
        position: relative;
        z-index: 1;
    }
    
    .preview-body {
        position: relative;
        z-index: 1;
    }
    
    .preview-detail {
        background: white;
        padding: 10px;
        border-radius: 5px;
        border: 1px solid #dee2e6;
    }

    /* Stats Items */
    .stat-item h5 {
        font-weight: 600;
        margin-bottom: 5px;
    }
    
    .stat-item small {
        font-size: 0.8rem;
    }

    /* Alert Styles */
    .alert {
        border: none;
        border-radius: 10px;
        padding: 15px 20px;
    }
    
    .alert-danger {
        background-color: #fde8e8;
        color: #c53030;
        border-left: 4px solid #c53030;
    }
    
    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border-left: 4px solid #f39c12;
    }
    
    .alert-info {
        background-color: #e8f4fd;
        color: #31708f;
        border-left: 4px solid #3498db;
    }

    /* Badge Styles */
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
    }

    /* Guidelines */
    .list-unstyled li {
        padding: 3px 0;
        border-bottom: 1px dashed #eee;
    }
    
    .list-unstyled li:last-child {
        border-bottom: none;
    }

    /* Form Check */
    .form-check-input:checked {
        background-color: #3498db;
        border-color: #3498db;
    }
    
    .form-check-label {
        font-weight: 500;
    }

    /* Input Group Buttons */
    .input-group .btn {
        margin: 0;
        border-radius: 0 6px 6px 0;
    }
    
    .input-group .btn:first-of-type {
        border-radius: 0;
    }
    
    .input-group .btn:last-of-type {
        border-radius: 0 6px 6px 0;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize form with default values
        generateCertificateCode();
        updatePreview();
        toggleExpiryDate();
        updateValidityPeriod();
        
        // Check code availability on input
        const codeInput = document.getElementById('certificate_code');
        codeInput.addEventListener('input', debounce(checkCodeAvailability, 500));
        
        // Update validity period when dates change
        document.getElementById('issued_at').addEventListener('change', updateValidityPeriod);
        document.getElementById('expires_at').addEventListener('change', updateValidityPeriod);
        
        // Update preview when form fields change
        document.getElementById('user_id').addEventListener('change', updatePreview);
        document.getElementById('course_id').addEventListener('change', updatePreview);
        document.getElementById('certificate_code').addEventListener('input', updatePreview);
        document.getElementById('issued_at').addEventListener('change', updatePreview);
        
        // Form validation
        const form = document.getElementById('certificateForm');
        form.addEventListener('submit', function(e) {
            const userId = document.getElementById('user_id').value;
            const courseId = document.getElementById('course_id').value;
            const certCode = document.getElementById('certificate_code').value;
            
            let isValid = true;
            let errorMessage = '';
            
            if (!userId) {
                isValid = false;
                errorMessage = 'Please select a user';
                document.getElementById('user_id').focus();
            } else if (!courseId) {
                isValid = false;
                errorMessage = 'Please select a course';
                document.getElementById('course_id').focus();
            } else if (!certCode) {
                isValid = false;
                errorMessage = 'Please enter a certificate code';
                document.getElementById('certificate_code').focus();
            }
            
            if (!isValid) {
                e.preventDefault();
                showToast(errorMessage, 'error');
                return false;
            }
            
            // Check for duplicate certificate (if not already confirmed)
            if (!window.duplicateConfirmed) {
                checkForDuplicates().then(isDuplicate => {
                    if (isDuplicate) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
            
            // Check code availability
            if (!window.codeAvailable) {
                e.preventDefault();
                showToast('Please wait for code availability check to complete', 'warning');
                return false;
            }
            
            // Prevent double form submission
            let isSubmitting = false;
            if (isSubmitting) {
                e.preventDefault();
                return false;
            }
            isSubmitting = true;
            
            // Disable submit button
            const submitButton = form.querySelector('button[type="submit"]');
            submitButton.disabled = true;
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Issuing Certificate...';
        });

        // Load user and course info if already selected
        const userId = document.getElementById('user_id').value;
        const courseId = document.getElementById('course_id').value;
        
        if (userId) {
            loadUserCertificates(userId);
            updateUserInfo(userId);
        }
        
        if (courseId) {
            loadCourseCertificates(courseId);
            updateCourseInfo(courseId);
        }
    });

    // Debounce function for code availability check
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Generate certificate code
    function generateCertificateCode() {
        const prefix = 'CERT';
        const timestamp = Date.now().toString(36).toUpperCase();
        const random = Math.random().toString(36).substring(2, 6).toUpperCase();
        const code = `${prefix}-${timestamp.substring(0, 4)}-${timestamp.substring(4, 8)}-${random}`;
        
        document.getElementById('certificate_code').value = code;
        updatePreview();
        checkCodeAvailability();
    }

    // Check code availability
    function checkCodeAvailability() {
        const code = document.getElementById('certificate_code').value;
        const availabilityDiv = document.getElementById('codeAvailability');
        const badge = document.getElementById('availabilityBadge');
        
        if (!code) {
            availabilityDiv.style.display = 'none';
            window.codeAvailable = false;
            return;
        }
        
        availabilityDiv.style.display = 'block';
        badge.className = 'badge bg-secondary';
        badge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Checking...';
        
        // Simulate API call
        setTimeout(() => {
            // In a real app, this would be an AJAX call to check database
            const isAvailable = Math.random() > 0.3; // 70% chance of being available for demo
            
            if (isAvailable) {
                badge.className = 'badge bg-success';
                badge.innerHTML = '<i class="fas fa-check-circle"></i> Code available';
                window.codeAvailable = true;
            } else {
                badge.className = 'badge bg-danger';
                badge.innerHTML = '<i class="fas fa-times-circle"></i> Code already exists';
                window.codeAvailable = false;
            }
        }, 1000);
    }

    // Load user certificates
    async function loadUserCertificates(userId) {
        if (!userId) {
            document.getElementById('userCertificateStats').style.display = 'none';
            return;
        }
        
        try {
            // Simulated API call - replace with actual API endpoint
            const response = await fetch(`/api/users/${userId}/certificates`);
            const data = await response.json();
            
            document.getElementById('userTotalCertificates').textContent = data.total || 0;
            document.getElementById('userValidCertificates').textContent = data.valid || 0;
            document.getElementById('userExpiredCertificates').textContent = data.expired || 0;
            
            document.getElementById('userCertificateStats').style.display = 'block';
            updateUserInfo(userId);
        } catch (error) {
            console.error('Error loading user certificates:', error);
        }
    }

    // Load course certificates
    async function loadCourseCertificates(courseId) {
        if (!courseId) {
            document.getElementById('courseCertificateStats').style.display = 'none';
            return;
        }
        
        try {
            // Simulated API call - replace with actual API endpoint
            const response = await fetch(`/api/courses/${courseId}/certificates`);
            const data = await response.json();
            
            document.getElementById('courseTotalCertificates').textContent = data.total || 0;
            document.getElementById('courseValidCertificates').textContent = data.valid || 0;
            document.getElementById('courseExpiredCertificates').textContent = data.expired || 0;
            
            document.getElementById('courseCertificateStats').style.display = 'block';
            updateCourseInfo(courseId);
        } catch (error) {
            console.error('Error loading course certificates:', error);
        }
    }

    // Update user info card
    function updateUserInfo(userId) {
        const userSelect = document.getElementById('user_id');
        const selectedOption = userSelect.options[userSelect.selectedIndex];
        const userInfoDiv = document.getElementById('userInfo');
        
        if (selectedOption && selectedOption.value) {
            userInfoDiv.innerHTML = `
                <div class="user-details">
                    <div class="avatar mb-3">
                        <i class="fas fa-user-circle fa-3x" style="color: #3498db;"></i>
                    </div>
                    <h5 class="mb-1">${selectedOption.getAttribute('data-name')}</h5>
                    <p class="text-muted mb-3">${selectedOption.getAttribute('data-email')}</p>
                    <div class="user-meta">
                        <small class="d-block text-muted">
                            <i class="fas fa-id-card"></i> User ID: ${selectedOption.value}
                        </small>
                    </div>
                </div>
            `;
        }
    }

    // Update course info card
    function updateCourseInfo(courseId) {
        const courseSelect = document.getElementById('course_id');
        const selectedOption = courseSelect.options[courseSelect.selectedIndex];
        const courseInfoDiv = document.getElementById('courseInfo');
        
        if (selectedOption && selectedOption.value) {
            courseInfoDiv.innerHTML = `
                <div class="course-details">
                    <div class="icon mb-3">
                        <i class="fas fa-book fa-3x" style="color: #2ecc71;"></i>
                    </div>
                    <h5 class="mb-1">${selectedOption.getAttribute('data-title')}</h5>
                    <p class="text-muted mb-3">${selectedOption.getAttribute('data-code') || 'No Code'}</p>
                    <div class="course-meta">
                        <small class="d-block text-muted">
                            <i class="fas fa-hashtag"></i> Course ID: ${selectedOption.value}
                        </small>
                    </div>
                </div>
            `;
        }
    }

    // Set issued date
    function setIssuedDate(type) {
        const issuedInput = document.getElementById('issued_at');
        
        if (type === 'now') {
            const now = new Date();
            const localDateTime = now.toISOString().slice(0, 16);
            issuedInput.value = localDateTime;
        } else {
            // For custom, you might open a date picker or modal
            // For now, just focus the input
            issuedInput.focus();
        }
        
        updateValidityPeriod();
        updatePreview();
    }

    // Set expiry date
    function setExpiryDate(days) {
        const issuedInput = document.getElementById('issued_at');
        const expiryInput = document.getElementById('expires_at');
        
        if (!issuedInput.value) {
            showToast('Please set issued date first', 'warning');
            return;
        }
        
        const issuedDate = new Date(issuedInput.value);
        const expiryDate = new Date(issuedDate);
        expiryDate.setDate(expiryDate.getDate() + parseInt(days));
        
        const localDateTime = expiryDate.toISOString().slice(0, 16);
        expiryInput.value = localDateTime;
        
        document.getElementById('noExpiry').checked = false;
        expiryInput.disabled = false;
        updateValidityPeriod();
    }

    // Toggle expiry date field
    function toggleExpiryDate() {
        const noExpiryCheckbox = document.getElementById('noExpiry');
        const expiryInput = document.getElementById('expires_at');
        
        expiryInput.disabled = noExpiryCheckbox.checked;
        
        if (noExpiryCheckbox.checked) {
            expiryInput.value = '';
        }
        
        updateValidityPeriod();
    }

    // Update validity period display
    function updateValidityPeriod() {
        const issuedInput = document.getElementById('issued_at');
        const expiryInput = document.getElementById('expires_at');
        const validityDiv = document.getElementById('validityPeriod');
        const validityText = document.getElementById('validityText');
        
        if (!issuedInput.value) {
            validityDiv.style.display = 'none';
            return;
        }
        
        const issuedDate = new Date(issuedInput.value);
        
        if (!expiryInput.value || expiryInput.disabled) {
            validityDiv.style.display = 'block';
            validityText.textContent = 'No expiry (permanent)';
            validityDiv.querySelector('.badge').className = 'badge bg-success';
            return;
        }
        
        const expiryDate = new Date(expiryInput.value);
        
        if (expiryDate <= issuedDate) {
            validityDiv.style.display = 'block';
            validityText.textContent = 'Invalid: Expiry must be after issue date';
            validityDiv.querySelector('.badge').className = 'badge bg-danger';
            return;
        }
        
        const diffTime = Math.abs(expiryDate - issuedDate);
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
        
        validityDiv.style.display = 'block';
        validityText.textContent = `${diffDays} days validity`;
        
        // Color code based on validity period
        if (diffDays <= 30) {
            validityDiv.querySelector('.badge').className = 'badge bg-warning';
        } else if (diffDays <= 365) {
            validityDiv.querySelector('.badge').className = 'badge bg-info';
        } else {
            validityDiv.querySelector('.badge').className = 'badge bg-success';
        }
    }

    // Update certificate preview
    function updatePreview() {
        const userSelect = document.getElementById('user_id');
        const courseSelect = document.getElementById('course_id');
        const certCode = document.getElementById('certificate_code').value;
        const issuedDate = document.getElementById('issued_at').value;
        
        const userName = userSelect.selectedIndex > 0 ? 
            userSelect.options[userSelect.selectedIndex].getAttribute('data-name') : '[User Name]';
        const courseName = courseSelect.selectedIndex > 0 ? 
            courseSelect.options[courseSelect.selectedIndex].getAttribute('data-title') : '[Course Title]';
        
        document.getElementById('previewUserName').textContent = userName;
        document.getElementById('previewCourseName').textContent = courseName;
        document.getElementById('previewCertCode').textContent = certCode || '[CERT-XXXX-XXXX-XXXX]';
        
        if (issuedDate) {
            const date = new Date(issuedDate);
            document.getElementById('previewIssuedDate').textContent = 
                date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }
    }

    function refreshPreview() {
        updatePreview();
        showToast('Preview refreshed', 'info');
    }

    // Check for duplicate certificates
    async function checkForDuplicates() {
        const userId = document.getElementById('user_id').value;
        const courseId = document.getElementById('course_id').value;
        
        if (!userId || !courseId) {
            showToast('Please select both user and course', 'warning');
            return false;
        }
        
        showToast('Checking for duplicate certificates...', 'info');
        
        try {
            // Simulated API call
            const response = await fetch(`/api/certificates/check-duplicate?user_id=${userId}&course_id=${courseId}`);
            const data = await response.json();
            
            if (data.duplicate) {
                document.getElementById('duplicateCertCode').textContent = `Code: ${data.certificate.certificate_code}`;
                document.getElementById('duplicateIssuedDate').textContent = `Issued: ${new Date(data.certificate.issued_at).toLocaleDateString()}`;
                document.getElementById('duplicateStatus').textContent = `Status: ${data.certificate.status}`;
                
                const modal = new bootstrap.Modal(document.getElementById('duplicateModal'));
                modal.show();
                return true;
            } else {
                document.getElementById('duplicateCheck').innerHTML = `
                    <div class="text-center py-3">
                        <i class="fas fa-check-circle fa-2x text-success mb-3"></i>
                        <p class="text-success mb-0">No duplicate certificate found</p>
                    </div>
                `;
                window.duplicateConfirmed = false;
                return false;
            }
        } catch (error) {
            console.error('Error checking duplicates:', error);
            showToast('Error checking for duplicates', 'error');
            return false;
        }
    }

    // Proceed with duplicate
    function proceedWithDuplicate() {
        window.duplicateConfirmed = true;
        bootstrap.Modal.getInstance(document.getElementById('duplicateModal')).hide();
        showToast('Proceeding with duplicate certificate', 'warning');
    }

    // Reset form
    function resetForm() {
        if (confirm('Are you sure you want to reset the form? All entered data will be lost.')) {
            document.getElementById('certificateForm').reset();
            generateCertificateCode();
            toggleExpiryDate();
            updatePreview();
            updateValidityPeriod();
            
            document.getElementById('userCertificateStats').style.display = 'none';
            document.getElementById('courseCertificateStats').style.display = 'none';
            document.getElementById('userInfo').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-user fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Select a user to view details</p>
                </div>
            `;
            document.getElementById('courseInfo').innerHTML = `
                <div class="text-center py-4">
                    <i class="fas fa-book fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Select a course to view details</p>
                </div>
            `;
            document.getElementById('duplicateCheck').innerHTML = `
                <div class="text-center py-3">
                    <i class="fas fa-search fa-2x text-muted mb-3"></i>
                    <p class="text-muted">Select user and course to check for duplicates</p>
                </div>
            `;
            
            showToast('Form reset successfully', 'success');
        }
    }

    // Save as draft
    function saveAsDraft() {
        showToast('Draft saved successfully', 'success');
        // In a real app, this would save the form data as a draft
    }

    // Toast notification
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

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + S to save
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveAsDraft();
        }
        
        // Ctrl/Cmd + Enter to submit
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
            e.preventDefault();
            document.getElementById('certificateForm').submit();
        }
        
        // Ctrl/Cmd + G to generate code
        if ((e.ctrlKey || e.metaKey) && e.key === 'g') {
            e.preventDefault();
            generateCertificateCode();
        }
        
        // Esc to cancel
        if (e.key === 'Escape') {
            if (confirm('Are you sure you want to cancel? Unsaved changes will be lost.')) {
                window.location.href = '/certificates';
            }
        }
    });
</script>

@endsection