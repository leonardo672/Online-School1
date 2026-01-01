@extends('layout')

@section('content')

<!-- Main Card for Editing Certificate -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h2 class="mb-0 me-3"><i class="fas fa-certificate"></i> Edit Certificate</h2>
                <div class="badge bg-light text-dark px-3 py-2">
                    <i class="fas fa-hashtag"></i> #{{ $certificate->id }}
                </div>
            </div>
            <div>
                <a href="{{ url('/certificates') }}" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <a href="{{ url('/certificates/' . $certificate->id) }}" class="btn btn-outline-light btn-sm">
                    <i class="fas fa-eye"></i> View
                </a>
            </div>
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

        @if (session('success'))
            <div class="alert alert-success mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('warning'))
            <div class="alert alert-warning mb-4">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <span>{{ session('warning') }}</span>
                </div>
            </div>
        @endif

        <div class="row">
            <div class="col-md-8">
                <!-- Certificate Form -->
                <div class="card mb-4">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-edit"></i> Edit Certificate Details</h5>
                            <div class="d-flex align-items-center">
                                <span class="badge bg-{{ $certificateStatusColor }} text-dark me-2">
                                    <i class="fas {{ $certificateStatusIcon }}"></i> 
                                    {{ $certificateStatusText }}
                                </span>
                                <span class="badge bg-info text-dark">
                                    <i class="fas fa-clock"></i> 
                                    Created: {{ $certificate->created_at->diffForHumans() }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ url('/certificates/' . $certificate->id) }}" id="certificateForm">
                            @csrf
                            @method('PUT')
                            
                            <!-- Certificate Code (Read-only) -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-hashtag me-1"></i> Certificate Code
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-certificate"></i>
                                    </span>
                                    <input type="text" class="form-control bg-light" 
                                           value="{{ $certificate->certificate_code }}" 
                                           readonly>
                                    <button type="button" class="btn btn-outline-primary" 
                                            onclick="copyCertificateCode()">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                                <small class="text-muted">
                                    Certificate code cannot be changed for security reasons.
                                    <span class="text-warning">
                                        <i class="fas fa-lock"></i> Permanent identifier
                                    </span>
                                </small>
                            </div>

                            <!-- User Information (Read-only) -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-user me-1"></i> Recipient
                                </label>
                                <div class="card bg-light p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="user-avatar me-3">
                                            <i class="fas fa-user-circle fa-2x" style="color: #3498db;"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $certificate->user->name ?? 'N/A' }}</h6>
                                            <small class="text-muted">{{ $certificate->user->email ?? '' }}</small>
                                            <div class="mt-2">
                                                <span class="badge bg-primary">
                                                    <i class="fas fa-certificate"></i> 
                                                    {{ $userCertificatesCount ?? 0 }} Certificates
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">User cannot be changed for existing certificates.</small>
                            </div>

                            <!-- Course Information (Read-only) -->
                            <div class="mb-4">
                                <label class="form-label">
                                    <i class="fas fa-book me-1"></i> Course
                                </label>
                                <div class="card bg-light p-3">
                                    <div class="d-flex align-items-center">
                                        <div class="course-icon me-3">
                                            <i class="fas fa-book fa-2x" style="color: #2ecc71;"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-0">{{ $certificate->course->title ?? 'N/A' }}</h6>
                                            <small class="text-muted">{{ $certificate->course->code ?? 'No Code' }}</small>
                                            <div class="mt-2">
                                                <span class="badge bg-success">
                                                    <i class="fas fa-certificate"></i> 
                                                    {{ $courseCertificatesCount ?? 0 }} Certificates Issued
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <small class="text-muted">Course cannot be changed for existing certificates.</small>
                            </div>

                            <!-- Issued Date -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label for="issued_at" class="form-label mb-0">
                                        <i class="fas fa-calendar-check me-1"></i> Issued Date *
                                    </label>
                                    <div>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" 
                                                onclick="showOriginalValue('issued_at')">
                                            <i class="fas fa-history"></i> Original: {{ $certificate->getOriginal('issued_at') ? $certificate->getOriginal('issued_at')->format('M d, Y') : 'Not set' }}
                                        </button>
                                    </div>
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-alt"></i>
                                    </span>
                                    <input type="datetime-local" name="issued_at" id="issued_at" 
                                           class="form-control @error('issued_at') is-invalid @enderror" 
                                           value="{{ old('issued_at', $certificate->issued_at ? $certificate->issued_at->format('Y-m-d\TH:i') : '') }}" 
                                           required>
                                    <button type="button" class="btn btn-outline-secondary" onclick="setIssuedDate('now')">
                                        Now
                                    </button>
                                </div>
                                @error('issued_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Date and time when the certificate was issued.</small>
                            </div>

                            <!-- Expiry Date -->
                            <div class="mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <div class="d-flex align-items-center">
                                        <label for="expires_at" class="form-label mb-0 me-3">
                                            <i class="fas fa-calendar-times me-1"></i> Expiry Date
                                        </label>
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="checkbox" id="noExpiry" 
                                                   onchange="toggleExpiryDate()"
                                                   {{ old('noExpiry', !$certificate->expires_at) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="noExpiry">No Expiry</label>
                                        </div>
                                    </div>
                                    @if($certificate->expires_at)
                                    <button type="button" class="btn btn-sm btn-outline-secondary" 
                                            onclick="showOriginalValue('expires_at')">
                                        <i class="fas fa-history"></i> Original: {{ $certificate->getOriginal('expires_at') ? $certificate->getOriginal('expires_at')->format('M d, Y') : 'No expiry' }}
                                    </button>
                                    @endif
                                </div>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-calendar-times"></i>
                                    </span>
                                    <input type="datetime-local" name="expires_at" id="expires_at" 
                                           class="form-control @error('expires_at') is-invalid @enderror" 
                                           value="{{ old('expires_at', $certificate->expires_at ? $certificate->expires_at->format('Y-m-d\TH:i') : '') }}"
                                           {{ !$certificate->expires_at && !old('expires_at') ? 'disabled' : '' }}>
                                    <button type="button" class="btn btn-outline-secondary" onclick="extendExpiry('30')">
                                        +30 Days
                                    </button>
                                    <button type="button" class="btn btn-outline-secondary" onclick="extendExpiry('90')">
                                        +90 Days
                                    </button>
                                </div>
                                @error('expires_at')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">Leave blank for certificates that don't expire.</small>
                                
                                <!-- Validity Period Display -->
                                <div id="validityPeriod" class="mt-2">
                                    @if($certificate->issued_at)
                                        @if($certificate->expires_at)
                                            @php
                                                $days = $certificate->issued_at->diffInDays($certificate->expires_at);
                                                $daysLeft = now()->diffInDays($certificate->expires_at, false);
                                                
                                                if ($daysLeft < 0) {
                                                    $validityColor = 'danger';
                                                    $validityText = 'Expired ' . abs($daysLeft) . ' days ago';
                                                } elseif ($daysLeft <= 30) {
                                                    $validityColor = 'warning';
                                                    $validityText = $daysLeft . ' days left';
                                                } else {
                                                    $validityColor = 'success';
                                                    $validityText = $daysLeft . ' days left';
                                                }
                                            @endphp
                                            <span class="badge bg-{{ $validityColor }}">
                                                <i class="fas fa-clock"></i>
                                                {{ $validityText }} ({{ $days }} days total)
                                            </span>
                                        @else
                                            <span class="badge bg-success">
                                                <i class="fas fa-infinity"></i> No expiry (permanent)
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            <!-- Certificate History -->
                            <div class="card bg-light mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-history"></i> Certificate History</h6>
                                </div>
                                <div class="card-body">
                                    <div class="timeline">
                                        <div class="timeline-item">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <h6>Certificate Created</h6>
                                                <small class="text-muted">{{ $certificate->created_at->format('M d, Y h:i A') }}</small>
                                                <br>
                                                <small class="text-muted">by {{ $certificate->creator->name ?? 'System' }}</small>
                                            </div>
                                        </div>
                                        @if($certificate->updated_at != $certificate->created_at)
                                        <div class="timeline-item">
                                            <div class="timeline-marker"></div>
                                            <div class="timeline-content">
                                                <h6>Last Updated</h6>
                                                <small class="text-muted">{{ $certificate->updated_at->format('M d, Y h:i A') }}</small>
                                                <br>
                                                <small class="text-muted">by {{ $certificate->updater->name ?? 'System' }}</small>
                                            </div>
                                        </div>
                                        @endif
                                        @if($certificate->expires_at && $certificate->expires_at->isPast())
                                        <div class="timeline-item">
                                            <div class="timeline-marker bg-danger"></div>
                                            <div class="timeline-content">
                                                <h6 class="text-danger">Certificate Expired</h6>
                                                <small class="text-muted">{{ $certificate->expires_at->format('M d, Y h:i A') }}</small>
                                                <br>
                                                <small class="text-danger">{{ $certificate->expires_at->diffForHumans() }}</small>
                                            </div>
                                        </div>
                                        @endif
                                    </div>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            This certificate has been viewed {{ $certificate->view_count ?? 0 }} times.
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Update Reason (Optional) -->
                            <div class="mb-4">
                                <label for="update_reason" class="form-label">
                                    <i class="fas fa-sticky-note me-1"></i> Update Reason (Optional)
                                </label>
                                <textarea name="update_reason" id="update_reason" 
                                          class="form-control @error('update_reason') is-invalid @enderror" 
                                          rows="3" 
                                          placeholder="Briefly explain why you're updating this certificate...">{{ old('update_reason') }}</textarea>
                                @error('update_reason')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <small class="text-muted">
                                    This helps track changes and maintain audit trails.
                                    <span id="reasonCharCount" class="float-end">0/500</span>
                                </small>
                            </div>

                            <!-- Additional Actions -->
                            <div class="card bg-light mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-cog"></i> Additional Actions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="send_update_email" id="send_update_email" value="1" checked>
                                                <label class="form-check-label" for="send_update_email">
                                                    <i class="fas fa-envelope me-1"></i> Send update notification
                                                </label>
                                                <small class="d-block text-muted">
                                                    Notify user about certificate updates
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-check mb-3">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="generate_new_pdf" id="generate_new_pdf" value="1">
                                                <label class="form-check-label" for="generate_new_pdf">
                                                    <i class="fas fa-file-pdf me-1"></i> Regenerate PDF
                                                </label>
                                                <small class="d-block text-muted">
                                                    Create updated PDF version
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       name="add_to_audit_log" id="add_to_audit_log" value="1" checked>
                                                <label class="form-check-label" for="add_to_audit_log">
                                                    <i class="fas fa-clipboard-list me-1"></i> Add to audit log
                                                </label>
                                                <small class="d-block text-muted">
                                                    Record this update in system audit log
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Form Actions -->
                            <div class="d-flex justify-content-between align-items-center mt-4 pt-4 border-top">
                                <div>
                                    <a href="{{ url('/certificates') }}" class="btn btn-secondary me-2">
                                        <i class="fas fa-times"></i> Cancel
                                    </a>
                                    <button type="button" class="btn btn-outline-danger" 
                                            onclick="showResetFormModal()">
                                        <i class="fas fa-undo"></i> Reset Changes
                                    </button>
                                </div>
                                <div>
                                    <button type="submit" class="btn btn-update-certificate me-2">
                                        <i class="fas fa-save"></i> Update Certificate
                                    </button>
                                    <button type="button" class="btn btn-success" 
                                            onclick="saveAsNewVersion()">
                                        <i class="fas fa-copy"></i> Save as New Version
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Certificate Audit Log (if available) -->
                @if(isset($auditLogs) && $auditLogs->count() > 0)
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-clipboard-list"></i> Audit Log</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Action</th>
                                        <th>User</th>
                                        <th>Changes</th>
                                        <th>Reason</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($auditLogs as $log)
                                    <tr>
                                        <td>{{ $log->created_at->format('M d, Y') }}</td>
                                        <td>
                                            <span class="badge bg-{{ $log->action == 'created' ? 'success' : ($log->action == 'updated' ? 'warning' : 'danger') }}">
                                                {{ ucfirst($log->action) }}
                                            </span>
                                        </td>
                                        <td>{{ $log->user->name ?? 'System' }}</td>
                                        <td>
                                            <small class="text-muted">
                                                @if($log->changes)
                                                    @foreach(json_decode($log->changes, true) as $field => $change)
                                                        <div>{{ $field }}: {{ $change['old'] ?? 'N/A' }} → {{ $change['new'] ?? 'N/A' }}</div>
                                                    @endforeach
                                                @else
                                                    No field changes
                                                @endif
                                            </small>
                                        </td>
                                        <td>{{ $log->reason ?? 'N/A' }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="col-md-4">
                <!-- Certificate Summary Card -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Certificate Summary</h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div class="certificate-icon-large mb-3">
                                <i class="fas fa-certificate fa-3x" style="color: #3498db;"></i>
                            </div>
                            <h4 class="text-primary mb-1">{{ $certificate->certificate_code }}</h4>
                            <p class="text-muted mb-0">Certificate ID: #{{ $certificate->id }}</p>
                        </div>
                        
                        <div class="stats-grid">
                            <div class="stat-item text-center p-3 bg-light rounded mb-3">
                                <h6 class="text-muted">Certificate Age</h6>
                                <h4 class="text-primary">{{ $certificate->created_at->diffForHumans(null, true) }}</h4>
                                <small class="text-muted">since creation</small>
                            </div>
                            <div class="stat-item text-center p-3 bg-light rounded mb-3">
                                <h6 class="text-muted">Days Valid</h6>
                                <h4 class="text-{{ $validityColor ?? 'success' }}">
                                    @if($certificate->issued_at && $certificate->expires_at)
                                        {{ $certificate->issued_at->diffInDays($certificate->expires_at) }}
                                    @else
                                        ∞
                                    @endif
                                </h4>
                                <small class="text-muted">validity period</small>
                            </div>
                            <div class="stat-item text-center p-3 bg-light rounded mb-3">
                                <h6 class="text-muted">Status</h6>
                                <h4 class="text-{{ $certificateStatusColor }}">
                                    {{ $certificateStatusText }}
                                </h4>
                                <small class="text-muted">current status</small>
                            </div>
                            <div class="stat-item text-center p-3 bg-light rounded">
                                <h6 class="text-muted">Last Updated</h6>
                                <h4 class="text-warning">{{ $certificate->updated_at->diffForHumans() }}</h4>
                                <small class="text-muted">by {{ $certificate->updater->name ?? 'System' }}</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ url('/certificates/' . $certificate->id . '/download') }}" 
                               class="btn btn-outline-primary text-start" target="_blank">
                                <i class="fas fa-download me-2"></i> Download PDF
                            </a>
                            <button type="button" class="btn btn-outline-success text-start" 
                                    onclick="sendCertificateEmail()">
                                <i class="fas fa-envelope me-2"></i> Send via Email
                            </button>
                            <button type="button" class="btn btn-outline-warning text-start" 
                                    onclick="showExtendModal()">
                                <i class="fas fa-calendar-plus me-2"></i> Extend Validity
                            </button>
                            <button type="button" class="btn btn-outline-info text-start" 
                                    onclick="showReissueModal()">
                                <i class="fas fa-redo me-2"></i> Reissue Certificate
                            </button>
                            <a href="{{ url('/certificates/' . $certificate->id . '/verify') }}" 
                               class="btn btn-outline-secondary text-start" target="_blank">
                                <i class="fas fa-shield-alt me-2"></i> Verify Certificate
                            </a>
                            <button type="button" class="btn btn-outline-danger text-start" 
                                    onclick="showRevokeModal()">
                                <i class="fas fa-ban me-2"></i> Revoke Certificate
                            </button>
                        </div>
                    </div>
                </div>

                <!-- User & Course Info Card -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-info-circle"></i> Related Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="related-info">
                            <div class="mb-3">
                                <h6><i class="fas fa-user text-primary"></i> User Details</h6>
                                <div class="ps-3">
                                    <p class="mb-1"><strong>Name:</strong> {{ $certificate->user->name ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Email:</strong> {{ $certificate->user->email ?? 'N/A' }}</p>
                                    <p class="mb-0"><strong>Certificates:</strong> {{ $userCertificatesCount ?? 0 }} total</p>
                                </div>
                            </div>
                            <div class="mb-3">
                                <h6><i class="fas fa-book text-success"></i> Course Details</h6>
                                <div class="ps-3">
                                    <p class="mb-1"><strong>Title:</strong> {{ $certificate->course->title ?? 'N/A' }}</p>
                                    <p class="mb-1"><strong>Code:</strong> {{ $certificate->course->code ?? 'N/A' }}</p>
                                    <p class="mb-0"><strong>Certificates Issued:</strong> {{ $courseCertificatesCount ?? 0 }}</p>
                                </div>
                            </div>
                            <div>
                                <h6><i class="fas fa-link text-info"></i> Quick Links</h6>
                                <div class="d-grid gap-2 mt-2">
                                    <a href="{{ url('/users/' . $certificate->user_id) }}" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-external-link-alt"></i> View User Profile
                                    </a>
                                    <a href="{{ url('/courses/' . $certificate->course_id) }}" 
                                       class="btn btn-sm btn-outline-success">
                                        <i class="fas fa-external-link-alt"></i> View Course Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Certificate Preview Card -->
                <div class="card">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-eye"></i> Certificate Preview</h5>
                    </div>
                    <div class="card-body">
                        <div class="certificate-mini-preview p-3 border rounded text-center">
                            <div class="preview-header mb-2">
                                <i class="fas fa-certificate text-warning"></i>
                                <h6 class="mb-1">Certificate Preview</h6>
                            </div>
                            <div class="preview-body">
                                <p class="mb-1 small">{{ $certificate->user->name ?? 'User' }}</p>
                                <p class="mb-1 small text-muted">{{ $certificate->course->title ?? 'Course' }}</p>
                                <p class="mb-1 small">
                                    <strong>{{ $certificate->issued_at ? $certificate->issued_at->format('M d, Y') : 'Not issued' }}</strong>
                                </p>
                                <p class="mb-0 small text-{{ $certificateStatusColor }}">
                                    <i class="fas {{ $certificateStatusIcon }}"></i> {{ $certificateStatusText }}
                                </p>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <a href="{{ url('/certificates/' . $certificate->id . '/preview') }}" 
                               class="btn btn-sm btn-outline-warning" target="_blank">
                                <i class="fas fa-expand"></i> Full Preview
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reset Changes Modal -->
<div class="modal fade" id="resetModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-undo"></i> Reset Changes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to reset all changes to their original values?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    This action cannot be undone. You will lose all unsaved changes.
                </div>
                <div class="changes-list">
                    <h6>Changes that will be lost:</h6>
                    <ul id="changesList" class="mb-0">
                        <!-- JavaScript will populate this -->
                    </ul>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="resetForm()">Reset Changes</button>
            </div>
        </div>
    </div>
</div>

<!-- Extend Validity Modal -->
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-plus"></i> Extend Validity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="extendForm" method="POST" action="{{ url('/certificates/' . $certificate->id . '/extend') }}">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Certificate</label>
                        <input type="text" class="form-control" value="{{ $certificate->certificate_code }}" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Expiry Date</label>
                        <input type="text" class="form-control" 
                               value="{{ $certificate->expires_at ? $certificate->expires_at->format('M d, Y') : 'No expiry' }}" 
                               readonly>
                    </div>
                    <div class="mb-3">
                        <label for="extension_days" class="form-label">Extension Period</label>
                        <select class="form-select" id="extension_days" name="extension_days" required>
                            <option value="30">30 days (1 month)</option>
                            <option value="60">60 days (2 months)</option>
                            <option value="90">90 days (3 months)</option>
                            <option value="180">180 days (6 months)</option>
                            <option value="365">365 days (1 year)</option>
                            <option value="730">730 days (2 years)</option>
                            <option value="custom">Custom...</option>
                        </select>
                    </div>
                    <div class="mb-3" id="customDaysContainer" style="display: none;">
                        <label for="custom_days" class="form-label">Custom Days</label>
                        <input type="number" class="form-control" id="custom_days" name="custom_days" 
                               min="1" max="3650" placeholder="Enter number of days">
                    </div>
                    <div class="mb-3">
                        <label for="extension_reason" class="form-label">Reason for Extension</label>
                        <textarea class="form-control" id="extension_reason" name="reason" rows="2" 
                                  placeholder="Why are you extending this certificate?"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Extend Validity
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reissue Certificate Modal -->
<div class="modal fade" id="reissueModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-redo"></i> Reissue Certificate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="reissueForm" method="POST" action="{{ url('/certificates/' . $certificate->id . '/reissue') }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>Warning: This will create a new certificate!</strong><br>
                                The old certificate will remain in the system but marked as replaced.
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Current Certificate</label>
                        <input type="text" class="form-control" value="{{ $certificate->certificate_code }}" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">New Certificate Code</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="newCertificateCode" 
                                   value="{{ 'CERT-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) }}" 
                                   readonly>
                            <button type="button" class="btn btn-outline-secondary" onclick="generateNewCode()">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <input type="hidden" name="new_certificate_code" id="newCertificateCodeHidden">
                    </div>
                    
                    <div class="mb-3">
                        <label for="reissue_reason" class="form-label">Reason for Reissue</label>
                        <select class="form-select" id="reissue_reason" name="reason" required>
                            <option value="">Select a reason...</option>
                            <option value="lost">Certificate lost by user</option>
                            <option value="damaged">Certificate damaged</option>
                            <option value="name_change">Name change</option>
                            <option value="course_update">Course information updated</option>
                            <option value="error_correction">Error in original certificate</option>
                            <option value="other">Other reason</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reissue_details" class="form-label">Additional Details</label>
                        <textarea class="form-control" id="reissue_details" name="details" rows="2" 
                                  placeholder="Provide additional details if needed..."></textarea>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="invalidate_old" id="invalidate_old" value="1" checked>
                        <label class="form-check-label" for="invalidate_old">
                            Invalidate old certificate
                        </label>
                        <small class="d-block text-muted">
                            Mark the old certificate as invalid/replaced
                        </small>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="send_new_certificate" id="send_new_certificate" value="1" checked>
                        <label class="form-check-label" for="send_new_certificate">
                            Send new certificate to user
                        </label>
                        <small class="d-block text-muted">
                            Email the new certificate to the user
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-redo"></i> Reissue Certificate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Revoke Certificate Modal -->
<div class="modal fade" id="revokeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-ban"></i> Revoke Certificate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="revokeForm" method="POST" action="{{ url('/certificates/' . $certificate->id . '/revoke') }}">
                @csrf
                <div class="modal-body">
                    <div class="alert alert-danger">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <div>
                                <strong>Warning: This action is serious!</strong><br>
                                Revoking a certificate marks it as invalid and cannot be easily undone.
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Certificate to Revoke</label>
                        <input type="text" class="form-control" value="{{ $certificate->certificate_code }}" readonly>
                    </div>
                    
                    <div class="mb-3">
                        <label for="revoke_reason" class="form-label">Reason for Revocation *</label>
                        <select class="form-select" id="revoke_reason" name="reason" required>
                            <option value="">Select a reason...</option>
                            <option value="fraud">Suspected fraud</option>
                            <option value="misconduct">User misconduct</option>
                            <option value="course_violation">Course policy violation</option>
                            <option value="false_information">False information provided</option>
                            <option value="administrative_error">Administrative error</option>
                            <option value="other">Other reason</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="revoke_details" class="form-label">Detailed Explanation *</label>
                        <textarea class="form-control" id="revoke_details" name="details" rows="3" 
                                  placeholder="Provide a detailed explanation for revoking this certificate..." required></textarea>
                        <small class="text-muted">This explanation will be recorded in the audit log.</small>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="notify_user" id="notify_user" value="1" checked>
                        <label class="form-check-label" for="notify_user">
                            Notify user about revocation
                        </label>
                        <small class="d-block text-muted">
                            Send email notification to the user
                        </small>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="permanent" id="permanent" value="1">
                        <label class="form-check-label" for="permanent">
                            Permanent revocation
                        </label>
                        <small class="d-block text-muted">
                            Cannot be reinstated without administrator approval
                        </small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-ban"></i> Revoke Certificate
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Inherit base styles from create page */
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
    
    .header-bg {
        background: linear-gradient(145deg, #3498db, #2980b9);
    }
    
    .card-body {
        padding: 30px;
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

    .btn-update-certificate {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
        font-weight: 600;
        padding: 12px 25px;
    }

    .btn-update-certificate:hover {
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

    .btn-outline-danger {
        border: 1px solid #dc3545;
        color: #dc3545;
    }

    .btn-outline-danger:hover {
        background-color: #dc3545;
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
    
    .form-control:disabled, .form-control[readonly] {
        background-color: #f8f9fa;
        opacity: 1;
    }

    /* Timeline */
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline-item {
        position: relative;
        padding: 10px 0;
        border-left: 2px solid #e9ecef;
    }
    
    .timeline-item:last-child {
        border-left: 2px solid transparent;
    }
    
    .timeline-marker {
        position: absolute;
        left: -9px;
        top: 15px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #6c757d;
        border: 3px solid white;
    }
    
    .timeline-item:first-child .timeline-marker {
        background: #3498db;
    }
    
    .timeline-content {
        padding-left: 20px;
    }
    
    .timeline-content h6 {
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .stat-item {
        transition: all 0.3s ease;
    }
    
    .stat-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .stat-item h4 {
        margin: 10px 0;
        font-weight: 600;
    }
    
    .stat-item h6 {
        font-size: 0.8rem;
        margin-bottom: 5px;
    }

    /* Certificate Mini Preview */
    .certificate-mini-preview {
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
        border: 1px solid #dee2e6;
        border-radius: 8px;
    }
    
    .preview-header {
        color: #3498db;
    }
    
    .preview-body {
        font-size: 0.9rem;
    }

    /* Badge Styles */
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
    }

    /* User and Course Cards */
    .user-avatar, .course-icon {
        width: 40px;
        text-align: center;
    }

    /* Certificate Icon Large */
    .certificate-icon-large i {
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    /* Related Info */
    .related-info h6 {
        font-size: 0.9rem;
        color: #666;
        border-bottom: 1px solid #eee;
        padding-bottom: 5px;
        margin-bottom: 10px;
    }
    
    .related-info p {
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    /* Input Group */
    .input-group-text {
        background-color: #f8f9fa;
        border: 2px solid #e9ecef;
    }
    
    .input-group .btn {
        margin: 0;
        border-radius: 0 6px 6px 0;
    }
    
    .input-group .btn:first-of-type {
        border-radius: 0;
    }

    /* Table Styles */
    .table-sm th, .table-sm td {
        padding: 8px;
        font-size: 0.85rem;
    }
    
    .table-sm .badge {
        font-size: 0.75rem;
        padding: 4px 8px;
    }

    /* Alert Styles */
    .alert {
        border: none;
        border-radius: 10px;
        padding: 15px 20px;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border-left: 4px solid #2ecc71;
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

    /* Modal Styles */
    .modal-content {
        border-radius: 12px;
        border: none;
    }
    
    .modal-header {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
        border-bottom: none;
    }
    
    .modal-header.text-danger {
        background: linear-gradient(145deg, #dc3545, #c82333);
    }
    
    .modal-header.bg-warning {
        background: linear-gradient(145deg, #f39c12, #e67e22);
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize character counter for update reason
        const reasonTextarea = document.getElementById('update_reason');
        const charCount = document.getElementById('reasonCharCount');
        
        reasonTextarea.addEventListener('input', function() {
            const length = this.value.length;
            charCount.textContent = `${length}/500`;
            
            if (length > 450) {
                charCount.style.color = '#f39c12';
            } else if (length > 490) {
                charCount.style.color = '#e74c3c';
            } else {
                charCount.style.color = '#666';
            }
        });
        
        // Initialize character count
        charCount.textContent = `${reasonTextarea.value.length}/500`;

        // Toggle expiry date based on checkbox
        toggleExpiryDate();
        
        // Track form changes
        trackFormChanges();
        
        // Handle custom days selection in extend modal
        const extensionDaysSelect = document.getElementById('extension_days');
        const customDaysContainer = document.getElementById('customDaysContainer');
        
        if (extensionDaysSelect) {
            extensionDaysSelect.addEventListener('change', function() {
                if (this.value === 'custom') {
                    customDaysContainer.style.display = 'block';
                } else {
                    customDaysContainer.style.display = 'none';
                }
            });
        }

        // Form validation
        const form = document.getElementById('certificateForm');
        form.addEventListener('submit', function(e) {
            const issuedAt = document.getElementById('issued_at').value;
            const expiresAt = document.getElementById('expires_at').value;
            const noExpiry = document.getElementById('noExpiry').checked;
            
            // Validate dates
            if (issuedAt && expiresAt && !noExpiry) {
                const issuedDate = new Date(issuedAt);
                const expiryDate = new Date(expiresAt);
                
                if (expiryDate <= issuedDate) {
                    e.preventDefault();
                    showToast('Expiry date must be after issue date', 'error');
                    return false;
                }
            }
            
            // Check if there are actual changes
            if (!hasFormChanges()) {
                e.preventDefault();
                const confirmNoChanges = confirm(
                    'No changes were made to this certificate. Do you want to continue anyway?'
                );
                if (!confirmNoChanges) {
                    return false;
                }
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
            submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        });

        // Initialize new certificate code for reissue modal
        if (document.getElementById('newCertificateCode')) {
            document.getElementById('newCertificateCodeHidden').value = 
                document.getElementById('newCertificateCode').value;
        }
    });

    // Track form changes
    let originalFormData = {};
    let formChanges = {};

    function trackFormChanges() {
        const form = document.getElementById('certificateForm');
        const formElements = form.querySelectorAll('input, select, textarea');
        
        // Store original values
        formElements.forEach(element => {
            if (element.name && !element.disabled) {
                originalFormData[element.name] = element.value;
            }
        });
        
        // Track changes
        formElements.forEach(element => {
            if (element.name && !element.disabled) {
                element.addEventListener('change', function() {
                    const currentValue = this.value;
                    const originalValue = originalFormData[this.name];
                    
                    if (currentValue !== originalValue) {
                        formChanges[this.name] = {
                            original: originalValue,
                            current: currentValue
                        };
                    } else {
                        delete formChanges[this.name];
                    }
                    
                    updateChangesList();
                });
            }
        });
    }

    function hasFormChanges() {
        return Object.keys(formChanges).length > 0;
    }

    function updateChangesList() {
        const changesList = document.getElementById('changesList');
        if (!changesList) return;
        
        changesList.innerHTML = '';
        
        for (const [field, change] of Object.entries(formChanges)) {
            const li = document.createElement('li');
            
            let fieldName = field.replace(/_/g, ' ');
            fieldName = fieldName.charAt(0).toUpperCase() + fieldName.slice(1);
            
            let originalText = change.original || '(empty)';
            let currentText = change.current || '(empty)';
            
            // Format dates if they look like dates
            if (field.includes('_at') && change.original) {
                try {
                    const date = new Date(change.original);
                    if (!isNaN(date.getTime())) {
                        originalText = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    }
                } catch(e) {}
            }
            
            if (field.includes('_at') && change.current) {
                try {
                    const date = new Date(change.current);
                    if (!isNaN(date.getTime())) {
                        currentText = date.toLocaleDateString() + ' ' + date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                    }
                } catch(e) {}
            }
            
            li.innerHTML = `<strong>${fieldName}:</strong> ${originalText} → ${currentText}`;
            changesList.appendChild(li);
        }
        
        if (changesList.children.length === 0) {
            const li = document.createElement('li');
            li.textContent = 'No changes detected';
            changesList.appendChild(li);
        }
    }

    // Form functions
    function copyCertificateCode() {
        const code = '{{ $certificate->certificate_code }}';
        navigator.clipboard.writeText(code).then(() => {
            showToast('Certificate code copied to clipboard', 'success');
        }).catch(err => {
            showToast('Failed to copy code', 'error');
        });
    }

    function showOriginalValue(field) {
        let originalValue = '';
        let fieldName = '';
        
        switch(field) {
            case 'issued_at':
                originalValue = '{{ $certificate->getOriginal("issued_at") ? $certificate->getOriginal("issued_at")->format("M d, Y h:i A") : "Not set" }}';
                fieldName = 'Issued Date';
                break;
            case 'expires_at':
                originalValue = '{{ $certificate->getOriginal("expires_at") ? $certificate->getOriginal("expires_at")->format("M d, Y h:i A") : "No expiry" }}';
                fieldName = 'Expiry Date';
                break;
        }
        
        showToast(`${fieldName} was: ${originalValue}`, 'info');
    }

    function setIssuedDate(type) {
        const issuedInput = document.getElementById('issued_at');
        
        if (type === 'now') {
            const now = new Date();
            const localDateTime = now.toISOString().slice(0, 16);
            issuedInput.value = localDateTime;
            issuedInput.dispatchEvent(new Event('change'));
        }
    }

    function extendExpiry(days) {
        const expiresInput = document.getElementById('expires_at');
        const issuedInput = document.getElementById('issued_at');
        const noExpiryCheckbox = document.getElementById('noExpiry');
        
        // Uncheck no expiry if checked
        if (noExpiryCheckbox.checked) {
            noExpiryCheckbox.checked = false;
            toggleExpiryDate();
        }
        
        if (!issuedInput.value) {
            showToast('Please set issued date first', 'warning');
            return;
        }
        
        let baseDate;
        if (expiresInput.value && !expiresInput.disabled) {
            baseDate = new Date(expiresInput.value);
        } else {
            baseDate = new Date(issuedInput.value);
        }
        
        const newDate = new Date(baseDate);
        newDate.setDate(newDate.getDate() + parseInt(days));
        
        const localDateTime = newDate.toISOString().slice(0, 16);
        expiresInput.value = localDateTime;
        expiresInput.dispatchEvent(new Event('change'));
        
        showToast(`Expiry extended by ${days} days`, 'success');
    }

    function toggleExpiryDate() {
        const noExpiryCheckbox = document.getElementById('noExpiry');
        const expiryInput = document.getElementById('expires_at');
        
        if (noExpiryCheckbox.checked) {
            expiryInput.disabled = true;
            expiryInput.value = '';
        } else {
            expiryInput.disabled = false;
        }
        
        expiryInput.dispatchEvent(new Event('change'));
    }

    // Modal functions
    function showResetFormModal() {
        if (!hasFormChanges()) {
            showToast('No changes to reset', 'info');
            return;
        }
        
        updateChangesList();
        const modal = new bootstrap.Modal(document.getElementById('resetModal'));
        modal.show();
    }

    function resetForm() {
        for (const [field, change] of Object.entries(formChanges)) {
            const element = document.querySelector(`[name="${field}"]`);
            if (element) {
                element.value = change.original;
                element.dispatchEvent(new Event('change'));
            }
        }
        
        formChanges = {};
        updateChangesList();
        
        const modal = bootstrap.Modal.getInstance(document.getElementById('resetModal'));
        modal.hide();
        
        showToast('Form reset to original values', 'success');
    }

    function showExtendModal() {
        const modal = new bootstrap.Modal(document.getElementById('extendModal'));
        modal.show();
    }

    function showReissueModal() {
        const modal = new bootstrap.Modal(document.getElementById('reissueModal'));
        modal.show();
    }

    function showRevokeModal() {
        const modal = new bootstrap.Modal(document.getElementById('revokeModal'));
        modal.show();
    }

    function generateNewCode() {
        const prefix = 'CERT';
        const timestamp = Date.now().toString(36).toUpperCase();
        const random = Math.random().toString(36).substring(2, 6).toUpperCase();
        const code = `${prefix}-${timestamp.substring(0, 4)}-${timestamp.substring(4, 8)}-${random}`;
        
        document.getElementById('newCertificateCode').value = code;
        document.getElementById('newCertificateCodeHidden').value = code;
    }

    function sendCertificateEmail() {
        if (confirm('Send this certificate via email to the user?')) {
            fetch(`/certificates/{{ $certificate->id }}/send-email`, {
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
                    showToast('Certificate sent via email successfully', 'success');
                } else {
                    showToast(data.message || 'Failed to send email', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('An error occurred while sending email', 'error');
            });
        }
    }

    function saveAsNewVersion() {
        if (confirm('Save as a new version? This will create a copy with your changes.')) {
            // Clone the form, change method to POST, and submit to a different endpoint
            const form = document.getElementById('certificateForm');
            const newForm = form.cloneNode(true);
            newForm.method = 'POST';
            newForm.action = '{{ url("/certificates/" . $certificate->id . "/version") }}';
            newForm.style.display = 'none';
            document.body.appendChild(newForm);
            newForm.submit();
        }
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
            document.getElementById('certificateForm').submit();
        }
        
        // Ctrl/Cmd + Z to reset (when not in textarea)
        if ((e.ctrlKey || e.metaKey) && e.key === 'z' && 
            e.target.tagName !== 'TEXTAREA' && e.target.tagName !== 'INPUT') {
            e.preventDefault();
            showResetFormModal();
        }
        
        // Ctrl/Cmd + Shift + S to save as new version
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'S') {
            e.preventDefault();
            saveAsNewVersion();
        }
        
        // Esc to close modals
        if (e.key === 'Escape') {
            const modal = bootstrap.Modal.getInstance(document.querySelector('.modal.show'));
            if (modal) {
                modal.hide();
            }
        }
    });
</script>

@endsection