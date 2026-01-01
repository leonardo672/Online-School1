@extends('layout')

@section('content')

<!-- Main Card for Certificate Details -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center">
                <h2 class="mb-0 me-3"><i class="fas fa-certificate"></i> Certificate Details</h2>
                <div class="badge bg-light text-dark px-3 py-2">
                    <i class="fas fa-hashtag"></i> #{{ $certificate->id }}
                </div>
            </div>
            <div class="d-flex align-items-center">
                <!-- Action Buttons -->
                <a href="{{ url('/certificates') }}" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
                <a href="{{ url('/certificates/' . $certificate->id . '/edit') }}" class="btn btn-outline-light btn-sm me-2">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <div class="dropdown">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" 
                            data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fas fa-ellipsis-h"></i>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header">Quick Actions</h6></li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="printCertificate()">
                                <i class="fas fa-print me-2"></i> Print
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="copyCertificateLink()">
                                <i class="fas fa-link me-2"></i> Copy Link
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item" href="#" onclick="downloadCertificate()">
                                <i class="fas fa-download me-2"></i> Download PDF
                            </a>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <a class="dropdown-item text-danger" href="#" 
                               onclick="confirmDelete()">
                                <i class="fas fa-trash me-2"></i> Delete
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Success Message -->
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="fas fa-check-circle me-2"></i>
                    <div>{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        <div class="row">
            <!-- Left Column: Main Certificate Content -->
            <div class="col-lg-8">
                <!-- Certificate Header -->
                <div class="text-center mb-5">
                    <div class="certificate-icon-large mb-3">
                        <i class="fas fa-certificate fa-4x" style="color: #3498db;"></i>
                    </div>
                    <h1 class="display-5 text-primary mb-2">Certificate of Completion</h1>
                    <p class="lead text-muted mb-0">
                        This certifies the successful completion of the course requirements
                    </p>
                </div>

                <!-- Certificate Body -->
                <div class="certificate-body mb-5">
                    <div class="row">
                        <!-- Certificate Details -->
                        <div class="col-md-6">
                            <div class="detail-card p-4 rounded mb-4">
                                <h4 class="detail-title mb-4">
                                    <i class="fas fa-info-circle me-2"></i> Certificate Information
                                </h4>
                                
                                <div class="detail-item mb-3">
                                    <div class="detail-label">Certificate Code</div>
                                    <div class="detail-value">
                                        <code class="text-primary fs-5">{{ $certificate->certificate_code }}</code>
                                        <button class="btn btn-sm btn-outline-secondary ms-2" 
                                                onclick="copyToClipboard('{{ $certificate->certificate_code }}')">
                                            <i class="fas fa-copy"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="detail-item mb-3">
                                    <div class="detail-label">Status</div>
                                    <div class="detail-value">
                                        <span class="badge bg-{{ $certificateStatusColor }} fs-6">
                                            <i class="fas {{ $certificateStatusIcon }} me-1"></i>
                                            {{ $certificateStatusText }}
                                            @if($isExpiringSoon && $certificate->expires_at)
                                                <small class="ms-2">({{ $certificate->expires_at->diffForHumans() }})</small>
                                            @endif
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="detail-item mb-3">
                                    <div class="detail-label">Certificate ID</div>
                                    <div class="detail-value">#{{ $certificate->id }}</div>
                                </div>
                                
                                <div class="detail-item mb-3">
                                    <div class="detail-label">Created Date</div>
                                    <div class="detail-value">
                                        {{ $certificate->created_at->format('F j, Y') }}
                                        <br>
                                        <small class="text-muted">{{ $certificate->created_at->format('h:i A') }}</small>
                                    </div>
                                </div>
                                
                                <div class="detail-item">
                                    <div class="detail-label">Last Updated</div>
                                    <div class="detail-value">
                                        {{ $certificate->updated_at->format('F j, Y') }}
                                        <br>
                                        <small class="text-muted">{{ $certificate->updated_at->diffForHumans() }}</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Issue & Expiry Details -->
                        <div class="col-md-6">
                            <div class="detail-card p-4 rounded mb-4">
                                <h4 class="detail-title mb-4">
                                    <i class="fas fa-calendar-alt me-2"></i> Validity Period
                                </h4>
                                
                                <div class="detail-item mb-3">
                                    <div class="detail-label">Issued Date</div>
                                    <div class="detail-value">
                                        @if($certificate->issued_at)
                                            <strong>{{ $certificate->issued_at->format('F j, Y') }}</strong>
                                            <br>
                                            <small class="text-muted">{{ $certificate->issued_at->format('h:i A') }}</small>
                                        @else
                                            <span class="text-warning">Not issued yet</span>
                                        @endif
                                    </div>
                                </div>
                                
                                <div class="detail-item mb-3">
                                    <div class="detail-label">Expiry Date</div>
                                    <div class="detail-value">
                                        @if($certificate->expires_at)
                                            <strong class="text-{{ $isExpired ? 'danger' : ($isExpiringSoon ? 'warning' : 'success') }}">
                                                {{ $certificate->expires_at->format('F j, Y') }}
                                            </strong>
                                            <br>
                                            <small class="text-muted">
                                                @if($isExpired)
                                                    Expired {{ $certificate->expires_at->diffForHumans() }}
                                                @elseif($isExpiringSoon)
                                                    Expires {{ $certificate->expires_at->diffForHumans() }}
                                                @else
                                                    {{ $certificate->expires_at->diffForHumans() }} remaining
                                                @endif
                                            </small>
                                        @else
                                            <span class="text-success">
                                                <i class="fas fa-infinity"></i> No expiry (permanent)
                                            </span>
                                        @endif
                                    </div>
                                </div>
                                
                                @if($certificate->issued_at && $certificate->expires_at)
                                <div class="detail-item mb-3">
                                    <div class="detail-label">Total Validity Period</div>
                                    <div class="detail-value">
                                        {{ $certificate->issued_at->diffInDays($certificate->expires_at) }} days
                                    </div>
                                </div>
                                @endif
                                
                                <div class="detail-item">
                                    <div class="detail-label">Certificate Age</div>
                                    <div class="detail-value">
                                        {{ $certificate->created_at->diffForHumans(null, true) }}
                                        <br>
                                        <small class="text-muted">since creation</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- User & Course Information -->
                <div class="row">
                    <!-- User Information -->
                    <div class="col-md-6">
                        <div class="card mb-4 h-100">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0"><i class="fas fa-user-graduate"></i> Recipient Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="user-profile text-center mb-4">
                                    <div class="avatar-large mb-3">
                                        <i class="fas fa-user-circle fa-4x" style="color: #3498db;"></i>
                                    </div>
                                    <h3 class="mb-2">{{ $certificate->user->name ?? 'N/A' }}</h3>
                                    <p class="text-muted mb-4">{{ $certificate->user->email ?? '' }}</p>
                                    
                                    @if($certificate->user)
                                    <div class="user-stats">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <h4 class="text-primary">{{ $userCertificatesCount ?? 0 }}</h4>
                                                    <small class="text-muted">Certificates</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <h4 class="text-success">
                                                        @if($userValidCertificatesCount > 0)
                                                            {{ $userValidCertificatesCount }}
                                                        @else
                                                            0
                                                        @endif
                                                    </h4>
                                                    <small class="text-muted">Valid</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="{{ url('/users/' . $certificate->user_id) }}" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-external-link-alt me-2"></i> View User Profile
                                    </a>
                                    <a href="{{ url('/certificates?user_id=' . $certificate->user_id) }}" 
                                       class="btn btn-outline-info">
                                        <i class="fas fa-list me-2"></i> All User's Certificates
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Course Information -->
                    <div class="col-md-6">
                        <div class="card mb-4 h-100">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0"><i class="fas fa-book-open"></i> Course Details</h5>
                            </div>
                            <div class="card-body">
                                <div class="course-profile text-center mb-4">
                                    <div class="course-icon-large mb-3">
                                        <i class="fas fa-book fa-4x" style="color: #2ecc71;"></i>
                                    </div>
                                    <h3 class="mb-2">{{ $certificate->course->title ?? 'N/A' }}</h3>
                                    <p class="text-muted mb-4">{{ $certificate->course->code ?? 'No Code' }}</p>
                                    
                                    @if($certificate->course)
                                    <div class="course-stats">
                                        <div class="row">
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <h4 class="text-primary">{{ $courseCertificatesCount ?? 0 }}</h4>
                                                    <small class="text-muted">Certificates Issued</small>
                                                </div>
                                            </div>
                                            <div class="col-6">
                                                <div class="stat-item">
                                                    <h4 class="text-warning">
                                                        @if($courseAverageRating > 0)
                                                            {{ number_format($courseAverageRating, 1) }}
                                                        @else
                                                            N/A
                                                        @endif
                                                    </h4>
                                                    <small class="text-muted">Avg. Rating</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    @endif
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <a href="{{ url('/courses/' . $certificate->course_id) }}" 
                                       class="btn btn-outline-success">
                                        <i class="fas fa-external-link-alt me-2"></i> View Course Details
                                    </a>
                                    <a href="{{ url('/certificates?course_id=' . $certificate->course_id) }}" 
                                       class="btn btn-outline-info">
                                        <i class="fas fa-list me-2"></i> All Course Certificates
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Validity Progress -->
                @if($certificate->issued_at && $certificate->expires_at)
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> Validity Progress</h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="progress" style="height: 30px;">
                                    @php
                                        $totalDays = $certificate->issued_at->diffInDays($certificate->expires_at);
                                        $daysPassed = $certificate->issued_at->diffInDays(min(now(), $certificate->expires_at));
                                        $percentage = $totalDays > 0 ? ($daysPassed / $totalDays) * 100 : 0;
                                        $daysLeft = $certificate->expires_at->diffInDays(now());
                                    @endphp
                                    <div class="progress-bar bg-{{ $certificateStatusColor }}" role="progressbar" 
                                         style="width: {{ $percentage }}%;"
                                         aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                        @if($isExpired)
                                            Expired
                                        @elseif($isExpiringSoon)
                                            {{ $daysLeft }} days left
                                        @else
                                            {{ number_format($percentage, 1) }}% Complete
                                        @endif
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted">
                                        Issued: {{ $certificate->issued_at->format('M d, Y') }}
                                    </small>
                                    <small class="text-muted">
                                        Expires: {{ $certificate->expires_at->format('M d, Y') }}
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-4 text-center">
                                <div class="validity-stats">
                                    <h2 class="text-{{ $certificateStatusColor }} mb-0">
                                        @if($isExpired)
                                            0
                                        @else
                                            {{ $daysLeft > 0 ? $daysLeft : 0 }}
                                        @endif
                                    </h2>
                                    <p class="text-muted mb-0">
                                        @if($isExpired)
                                            Days since expiry
                                        @else
                                            Days remaining
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Certificate Verification -->
                <div class="card mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-shield-alt"></i> Certificate Verification</h5>
                    </div>
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <p class="mb-3">Verify this certificate's authenticity using the verification code:</p>
                                <div class="input-group mb-3">
                                    <input type="text" class="form-control" 
                                           value="{{ $certificate->certificate_code }}" 
                                           id="verificationCode" readonly>
                                    <button class="btn btn-outline-secondary" type="button" 
                                            onclick="copyVerificationCode()">
                                        <i class="fas fa-copy"></i> Copy
                                    </button>
                                </div>
                                <p class="small text-muted mb-0">
                                    <i class="fas fa-info-circle"></i>
                                    This certificate can be verified at: 
                                    <a href="{{ url('/certificates/verify') }}" target="_blank">
                                        {{ url('/certificates/verify') }}
                                    </a>
                                </p>
                            </div>
                            <div class="col-md-4 text-center">
                                <a href="{{ url('/certificates/' . $certificate->id . '/verify') }}" 
                                   class="btn btn-warning btn-lg" target="_blank">
                                    <i class="fas fa-search me-2"></i> Verify Now
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Audit Log (if available) -->
                @if(isset($auditLogs) && $auditLogs->count() > 0)
                <div class="card">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-history"></i> Activity History</h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            @foreach($auditLogs as $log)
                            <div class="timeline-item {{ $loop->first ? 'active' : '' }}">
                                <div class="timeline-marker"></div>
                                <div class="timeline-content">
                                    <div class="d-flex justify-content-between">
                                        <h6 class="mb-1">
                                            {{ ucfirst($log->action) }}
                                            @if($loop->first)
                                                <span class="badge bg-info">Latest</span>
                                            @endif
                                        </h6>
                                        <small class="text-muted">{{ $log->created_at->diffForHumans() }}</small>
                                    </div>
                                    <p class="mb-1 small">
                                        <i class="fas fa-user"></i> 
                                        {{ $log->user->name ?? 'System' }}
                                    </p>
                                    @if($log->reason)
                                    <p class="mb-2 small text-muted">
                                        <i class="fas fa-comment"></i> 
                                        {{ $log->reason }}
                                    </p>
                                    @endif
                                    @if($log->changes)
                                        <div class="changes small bg-light p-2 rounded">
                                            @foreach(json_decode($log->changes, true) as $field => $change)
                                                <div>
                                                    <strong>{{ str_replace('_', ' ', $field) }}:</strong>
                                                    {{ $change['old'] ?? 'N/A' }} → 
                                                    {{ $change['new'] ?? 'N/A' }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                            @if($auditLogs->count() === 0)
                            <div class="text-center py-4">
                                <i class="fas fa-history fa-2x text-muted mb-3"></i>
                                <p class="text-muted mb-0">No activity history available</p>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Right Column: Stats & Actions -->
            <div class="col-lg-4">
                <!-- Statistics Card -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Statistics</h5>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-item text-center p-3 bg-light rounded mb-3">
                                <h6 class="text-muted">Certificate Views</h6>
                                <h2 class="text-info">{{ $certificate->view_count ?? 0 }}</h2>
                                <small class="text-muted">times viewed</small>
                            </div>
                            <div class="stat-item text-center p-3 bg-light rounded mb-3">
                                <h6 class="text-muted">Days Valid</h6>
                                <h2 class="text-success">
                                    @if($certificate->issued_at && $certificate->expires_at)
                                        {{ $certificate->issued_at->diffInDays($certificate->expires_at) }}
                                    @else
                                        ∞
                                    @endif
                                </h2>
                                <small class="text-muted">total validity</small>
                            </div>
                            <div class="stat-item text-center p-3 bg-light rounded mb-3">
                                <h6 class="text-muted">User Rank</h6>
                                <h2 class="text-warning">#{{ $userRank ?? 'N/A' }}</h2>
                                <small class="text-muted">out of {{ $totalUsers ?? 0 }} users</small>
                            </div>
                            <div class="stat-item text-center p-3 bg-light rounded">
                                <h6 class="text-muted">Course Rank</h6>
                                <h2 class="text-danger">#{{ $courseRank ?? 'N/A' }}</h2>
                                <small class="text-muted">out of {{ $totalCourses ?? 0 }} courses</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions Card -->
                <div class="card mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-grid gap-2">
                            <a href="{{ url('/certificates/' . $certificate->id . '/download') }}" 
                               class="btn btn-outline-primary text-start" onclick="downloadCertificate(event)">
                                <i class="fas fa-download me-2"></i> Download PDF
                            </a>
                            <button type="button" class="btn btn-outline-success text-start" 
                                    onclick="sendCertificateEmail()">
                                <i class="fas fa-envelope me-2"></i> Send via Email
                            </button>
                            <button type="button" class="btn btn-outline-warning text-start" 
                                    onclick="extendCertificate()">
                                <i class="fas fa-calendar-plus me-2"></i> Extend Validity
                            </button>
                            <button type="button" class="btn btn-outline-info text-start" 
                                    onclick="reissueCertificate()">
                                <i class="fas fa-redo me-2"></i> Reissue Certificate
                            </button>
                            <button type="button" class="btn btn-outline-secondary text-start" 
                                    onclick="shareCertificate()">
                                <i class="fas fa-share-alt me-2"></i> Share Certificate
                            </button>
                            <button type="button" class="btn btn-outline-danger text-start" 
                                    onclick="generateReport()">
                                <i class="fas fa-file-alt me-2"></i> Generate Report
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Status Timeline Card -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-stream"></i> Status Timeline</h5>
                    </div>
                    <div class="card-body">
                        <div class="status-timeline">
                            <div class="status-item completed">
                                <div class="status-icon">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Certificate Created</h6>
                                    <small class="text-muted">{{ $certificate->created_at->diffForHumans() }}</small>
                                </div>
                            </div>
                            
                            @if($certificate->issued_at)
                            <div class="status-item {{ $certificate->issued_at->lte(now()) ? 'completed' : 'pending' }}">
                                <div class="status-icon">
                                    <i class="fas fa-calendar-check"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Certificate Issued</h6>
                                    <small class="text-muted">{{ $certificate->issued_at->diffForHumans() }}</small>
                                </div>
                            </div>
                            @endif
                            
                            @if($certificate->expires_at)
                            <div class="status-item {{ $isExpired ? 'completed' : ($isExpiringSoon ? 'warning' : 'pending') }}">
                                <div class="status-icon">
                                    <i class="fas fa-calendar-times"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Certificate {{ $isExpired ? 'Expired' : 'Expires' }}</h6>
                                    <small class="text-muted">
                                        @if($isExpired)
                                            {{ $certificate->expires_at->diffForHumans() }}
                                        @else
                                            {{ $certificate->expires_at->diffForHumans() }}
                                        @endif
                                    </small>
                                </div>
                            </div>
                            @endif
                            
                            <div class="status-item {{ $certificate->updated_at != $certificate->created_at ? 'completed' : 'pending' }}">
                                <div class="status-icon">
                                    <i class="fas fa-sync-alt"></i>
                                </div>
                                <div class="status-content">
                                    <h6>Last Updated</h6>
                                    <small class="text-muted">{{ $certificate->updated_at->diffForHumans() }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-clock"></i> 
                                Current Status: <strong class="text-{{ $certificateStatusColor }}">{{ $certificateStatusText }}</strong>
                            </small>
                        </div>
                    </div>
                </div>

                <!-- QR Code Card -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0"><i class="fas fa-qrcode"></i> QR Code</h5>
                    </div>
                    <div class="card-body text-center">
                        <div class="qrcode-container mb-3">
                            <!-- This would be generated by a QR code library -->
                            <div id="qrCode" class="bg-light p-3 rounded d-inline-block">
                                <div class="qr-placeholder">
                                    <i class="fas fa-qrcode fa-4x text-muted"></i>
                                </div>
                            </div>
                        </div>
                        <p class="small text-muted mb-3">
                            Scan to verify this certificate instantly
                        </p>
                        <button type="button" class="btn btn-sm btn-outline-dark" onclick="downloadQRCode()">
                            <i class="fas fa-download me-2"></i> Download QR Code
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Certificates -->
        @if($relatedCertificates && $relatedCertificates->count() > 0)
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-link"></i> Related Certificates</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Certificate Code</th>
                                <th>Status</th>
                                <th>Issued Date</th>
                                <th>Expiry Date</th>
                                <th>Course</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($relatedCertificates as $related)
                                @php
                                    $now = now();
                                    $isRelatedExpired = $related->expires_at && $related->expires_at->lt($now);
                                    $isRelatedExpiringSoon = $related->expires_at && $related->expires_at->gt($now) && $related->expires_at->diffInDays($now) <= 30;
                                    $isRelatedValid = $related->issued_at && $related->issued_at->lte($now) && (!$related->expires_at || $related->expires_at->gt($now));
                                    
                                    if ($isRelatedExpired) {
                                        $relatedStatusColor = 'danger';
                                        $relatedStatusText = 'Expired';
                                    } elseif ($isRelatedExpiringSoon) {
                                        $relatedStatusColor = 'warning';
                                        $relatedStatusText = 'Expiring Soon';
                                    } elseif ($isRelatedValid) {
                                        $relatedStatusColor = 'success';
                                        $relatedStatusText = 'Valid';
                                    } else {
                                        $relatedStatusColor = 'secondary';
                                        $relatedStatusText = 'Not Issued';
                                    }
                                @endphp
                                <tr>
                                    <td>
                                        <strong>{{ $related->certificate_code }}</strong>
                                        <br>
                                        <small class="text-muted">ID: #{{ $related->id }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $relatedStatusColor }}">
                                            {{ $relatedStatusText }}
                                        </span>
                                    </td>
                                    <td>
                                        @if($related->issued_at)
                                            {{ $related->issued_at->format('M d, Y') }}
                                        @else
                                            <span class="text-muted">Not issued</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($related->expires_at)
                                            <span class="text-{{ $isRelatedExpired ? 'danger' : ($isRelatedExpiringSoon ? 'warning' : 'success') }}">
                                                {{ $related->expires_at->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="text-success">No expiry</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($related->course)
                                            {{ Str::limit($related->course->title, 25) }}
                                        @else
                                            <span class="text-danger">No Course</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ url('/certificates/' . $related->id) }}" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this certificate?</p>
                <div class="alert alert-danger">
                    <div class="d-flex">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div>
                            <strong>This action cannot be undone!</strong><br>
                            This will permanently delete the certificate and all associated data.
                        </div>
                    </div>
                </div>
                <div class="certificate-preview p-3 bg-light rounded">
                    <div class="d-flex align-items-center mb-2">
                        <div class="me-3">
                            <i class="fas fa-certificate fa-2x text-primary"></i>
                        </div>
                        <div>
                            <strong>{{ $certificate->certificate_code }}</strong>
                            <br>
                            <small class="text-muted">
                                {{ $certificate->user->name ?? 'N/A' }} - 
                                {{ $certificate->course->title ?? 'N/A' }}
                            </small>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6">
                            <small class="text-muted">Issued:</small><br>
                            <small>{{ $certificate->issued_at ? $certificate->issued_at->format('M d, Y') : 'Not issued' }}</small>
                        </div>
                        <div class="col-6">
                            <small class="text-muted">Status:</small><br>
                            <span class="badge bg-{{ $certificateStatusColor }}">{{ $certificateStatusText }}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" action="{{ url('/certificates/' . $certificate->id) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-2"></i> Delete Certificate
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Extend Certificate Modal -->
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-plus"></i> Extend Certificate Validity</h5>
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
                               value="{{ $certificate->expires_at ? $certificate->expires_at->format('F j, Y') : 'No expiry' }}" 
                               readonly>
                    </div>
                    <div class="mb-3">
                        <label for="extension_days" class="form-label">Extension Period</label>
                        <div class="row">
                            <div class="col-4 mb-2">
                                <button type="button" class="btn btn-outline-secondary w-100" 
                                        onclick="setExtensionDays(30)">30 Days</button>
                            </div>
                            <div class="col-4 mb-2">
                                <button type="button" class="btn btn-outline-secondary w-100" 
                                        onclick="setExtensionDays(90)">90 Days</button>
                            </div>
                            <div class="col-4 mb-2">
                                <button type="button" class="btn btn-outline-secondary w-100" 
                                        onclick="setExtensionDays(180)">6 Months</button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-secondary w-100" 
                                        onclick="setExtensionDays(365)">1 Year</button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-secondary w-100" 
                                        onclick="setExtensionDays(730)">2 Years</button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="btn btn-outline-secondary w-100" 
                                        onclick="showCustomExtension()">Custom</button>
                            </div>
                        </div>
                        <input type="hidden" id="extension_days" name="extension_days" value="30" required>
                    </div>
                    <div class="mb-3" id="customExtensionContainer" style="display: none;">
                        <label for="custom_days" class="form-label">Custom Days</label>
                        <input type="number" class="form-control" id="custom_days" 
                               min="1" max="3650" placeholder="Enter number of days">
                    </div>
                    <div class="mb-3">
                        <label for="new_expiry_date" class="form-label">New Expiry Date</label>
                        <input type="text" class="form-control" id="new_expiry_date" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="extension_reason" class="form-label">Reason for Extension</label>
                        <textarea class="form-control" id="extension_reason" name="reason" rows="2" 
                                  placeholder="Why are you extending this certificate?"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="notify_user" id="notify_user" value="1" checked>
                        <label class="form-check-label" for="notify_user">
                            Notify user about extension
                        </label>
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

<!-- Share Certificate Modal -->
<div class="modal fade" id="shareModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-share-alt"></i> Share Certificate</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Share Link</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="shareLink" 
                               value="{{ url('/certificates/' . $certificate->id) }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyShareLink()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Verification Link</label>
                    <div class="input-group">
                        <input type="text" class="form-control" id="verificationLink" 
                               value="{{ url('/certificates/' . $certificate->id . '/verify') }}" readonly>
                        <button class="btn btn-outline-secondary" type="button" onclick="copyVerificationLink()">
                            <i class="fas fa-copy"></i>
                        </button>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Share via</label>
                    <div class="d-flex flex-wrap gap-2">
                        <button class="btn btn-outline-primary flex-grow-1" onclick="shareEmail()">
                            <i class="fas fa-envelope"></i> Email
                        </button>
                        <button class="btn btn-outline-success flex-grow-1" onclick="shareWhatsApp()">
                            <i class="fab fa-whatsapp"></i> WhatsApp
                        </button>
                        <button class="btn btn-outline-info flex-grow-1" onclick="shareLinkedIn()">
                            <i class="fab fa-linkedin"></i> LinkedIn
                        </button>
                        <button class="btn btn-outline-dark flex-grow-1" onclick="shareTwitter()">
                            <i class="fab fa-twitter"></i> Twitter
                        </button>
                    </div>
                </div>
                <div class="alert alert-info">
                    <small>
                        <i class="fas fa-info-circle"></i>
                        Sharing certificate links may be subject to privacy policies and user consent.
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Inherit base styles from create and edit pages */
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

    /* Certificate Header */
    .certificate-icon-large i {
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));
        animation: float 3s ease-in-out infinite;
    }
    
    @keyframes float {
        0%, 100% { transform: translateY(0); }
        50% { transform: translateY(-10px); }
    }

    /* Detail Cards */
    .detail-card {
        background: linear-gradient(145deg, #f8f9fa, #e9ecef);
        border: 1px solid #dee2e6;
        height: 100%;
    }
    
    .detail-title {
        color: #2c3e50;
        font-weight: 600;
        border-bottom: 2px solid #3498db;
        padding-bottom: 10px;
    }
    
    .detail-item {
        margin-bottom: 15px;
    }
    
    .detail-label {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .detail-value {
        font-size: 1.1rem;
        color: #2c3e50;
    }

    /* User & Course Profiles */
    .avatar-large, .course-icon-large {
        display: flex;
        justify-content: center;
        align-items: center;
    }
    
    .user-stats, .course-stats {
        margin-top: 20px;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 10px;
    }
    
    .stat-item {
        text-align: center;
    }
    
    .stat-item h4 {
        margin-bottom: 5px;
        font-weight: 600;
    }

    /* Validity Progress */
    .progress {
        border-radius: 15px;
        overflow: hidden;
        background-color: #e9ecef;
    }
    
    .progress-bar {
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: width 1s ease-in-out;
    }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }
    
    .stats-grid .stat-item {
        transition: all 0.3s ease;
    }
    
    .stats-grid .stat-item:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .stats-grid .stat-item h2 {
        font-weight: 700;
        margin: 10px 0;
    }

    /* Status Timeline */
    .status-timeline {
        position: relative;
        padding-left: 40px;
    }
    
    .status-timeline::before {
        content: '';
        position: absolute;
        left: 20px;
        top: 0;
        bottom: 0;
        width: 2px;
        background-color: #e9ecef;
    }
    
    .status-item {
        position: relative;
        padding: 15px 0;
    }
    
    .status-item.completed .status-icon {
        background-color: #2ecc71;
        color: white;
    }
    
    .status-item.pending .status-icon {
        background-color: #95a5a6;
        color: white;
    }
    
    .status-item.warning .status-icon {
        background-color: #f39c12;
        color: white;
    }
    
    .status-icon {
        position: absolute;
        left: -40px;
        top: 15px;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 3px solid white;
        z-index: 1;
    }
    
    .status-content {
        padding-left: 20px;
    }
    
    .status-content h6 {
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    /* Timeline for Audit Log */
    .timeline {
        position: relative;
        padding-left: 30px;
    }
    
    .timeline-item {
        position: relative;
        padding: 20px 0;
        border-left: 2px solid #e9ecef;
    }
    
    .timeline-item.active {
        border-left-color: #3498db;
    }
    
    .timeline-item:last-child {
        border-left: 2px solid transparent;
    }
    
    .timeline-marker {
        position: absolute;
        left: -9px;
        top: 25px;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        background: #6c757d;
        border: 3px solid white;
    }
    
    .timeline-item.active .timeline-marker {
        background: #3498db;
        box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.2);
    }
    
    .timeline-content {
        padding-left: 20px;
    }
    
    .timeline-content h6 {
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    /* QR Code */
    .qrcode-container {
        padding: 10px;
        background: white;
        border-radius: 10px;
        display: inline-block;
        border: 1px solid #dee2e6;
    }
    
    .qr-placeholder {
        width: 150px;
        height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 5px;
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

    .btn-sm {
        padding: 6px 10px;
        font-size: 12px;
        min-width: 36px;
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

    .btn-outline-warning {
        border: 1px solid #f39c12;
        color: #f39c12;
    }

    .btn-outline-warning:hover {
        background-color: #f39c12;
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

    .btn-outline-secondary {
        border: 1px solid #6c757d;
        color: #6c757d;
    }

    .btn-outline-secondary:hover {
        background-color: #6c757d;
        color: white;
    }

    .btn-outline-dark {
        border: 1px solid #343a40;
        color: #343a40;
    }

    .btn-outline-dark:hover {
        background-color: #343a40;
        color: white;
    }

    .btn-lg {
        padding: 15px 30px;
        font-size: 1.1rem;
    }

    /* Badge Styles */
    .badge {
        padding: 8px 15px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 0.9rem;
    }
    
    .badge.fs-6 {
        font-size: 1rem !important;
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

    /* Table Styling */
    .table {
        width: 100%;
        margin-bottom: 1rem;
        background-color: white;
        border-radius: 10px;
        overflow: hidden;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table th {
        background-color: #f8f9fa;
        padding: 15px;
        font-weight: 600;
        color: #666;
        border-bottom: 2px solid #dee2e6;
    }

    .table td {
        padding: 15px;
        vertical-align: middle;
        border-bottom: 1px solid #e9ecef;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
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

    /* Certificate Glow Animation */
    @keyframes certificateGlow {
        0% { box-shadow: 0 0 5px rgba(52, 152, 219, 0.5); }
        50% { box-shadow: 0 0 20px rgba(52, 152, 219, 0.8); }
        100% { box-shadow: 0 0 5px rgba(52, 152, 219, 0.5); }
    }
    
    .certificate-glow {
        animation: certificateGlow 2s infinite;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltips = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltips.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Add certificate glow animation to valid certificates
        const validBadge = document.querySelector('.badge.bg-success');
        if (validBadge) {
            validBadge.classList.add('certificate-glow');
        }

        // Initialize QR Code (simulated)
        initializeQRCode();

        // Update view count (simulated)
        setTimeout(() => {
            console.log('Certificate viewed:', {{ $certificate->id }});
            // In a real app, you would send an AJAX request to increment view count
        }, 1000);

        // Add click animation to action buttons
        const actionButtons = document.querySelectorAll('.btn');
        actionButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                if (!this.classList.contains('disabled')) {
                    this.style.transform = 'scale(0.95)';
                    setTimeout(() => {
                        this.style.transform = '';
                    }, 150);
                }
            });
        });

        // Initialize extension modal
        updateNewExpiryDate();

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // 'e' key to edit
            if (e.key === 'e' && !e.ctrlKey && !e.metaKey) {
                e.preventDefault();
                window.location.href = '{{ url("/certificates/" . $certificate->id . "/edit") }}';
            }
            
            // 'p' key to print
            if (e.key === 'p' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                printCertificate();
            }
            
            // 'd' key to download
            if (e.key === 'd' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                downloadCertificate();
            }
            
            // 's' key to share
            if (e.key === 's' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                shareCertificate();
            }
            
            // Escape key to go back
            if (e.key === 'Escape') {
                window.history.back();
            }
        });
    });

    // Delete confirmation
    function confirmDelete() {
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    // Print certificate
    function printCertificate() {
        const printContent = document.querySelector('.card').cloneNode(true);
        
        // Remove action buttons and unnecessary elements
        const actions = printContent.querySelectorAll('.btn, .dropdown, .header-bg .d-flex:last-child, .modal, .share-section, .quick-actions');
        actions.forEach(action => action.remove());
        
        // Create print window
        const printWindow = window.open('', '_blank');
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Certificate {{ $certificate->certificate_code }} - Print</title>
                <style>
                    @import url('https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css');
                    @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap');
                    
                    body { 
                        font-family: 'Poppins', sans-serif; 
                        margin: 20px; 
                        background: #f8f9fa;
                    }
                    .certificate-print { 
                        max-width: 800px; 
                        margin: 0 auto; 
                        background: white; 
                        padding: 40px; 
                        border-radius: 15px; 
                        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                        border: 2px solid #3498db;
                    }
                    .print-header { 
                        text-align: center; 
                        margin-bottom: 40px; 
                        padding-bottom: 20px; 
                        border-bottom: 3px solid #3498db;
                    }
                    .print-header h1 { 
                        color: #2c3e50; 
                        margin-bottom: 10px; 
                    }
                    .print-header p { 
                        color: #7f8c8d; 
                        font-size: 1.1rem;
                    }
                    .certificate-body { 
                        margin: 30px 0; 
                    }
                    .certificate-info { 
                        background: #f8f9fa; 
                        padding: 25px; 
                        border-radius: 10px; 
                        margin-bottom: 30px;
                        border-left: 4px solid #3498db;
                    }
                    .info-row { 
                        display: flex; 
                        justify-content: space-between; 
                        margin-bottom: 15px; 
                        padding-bottom: 15px; 
                        border-bottom: 1px dashed #dee2e6;
                    }
                    .info-row:last-child { 
                        border-bottom: none; 
                        margin-bottom: 0; 
                        padding-bottom: 0;
                    }
                    .info-label { 
                        font-weight: 600; 
                        color: #2c3e50; 
                        min-width: 200px;
                    }
                    .info-value { 
                        color: #34495e; 
                        text-align: right;
                        flex: 1;
                    }
                    .user-course-info { 
                        display: grid; 
                        grid-template-columns: 1fr 1fr; 
                        gap: 30px; 
                        margin: 30px 0;
                    }
                    .user-card, .course-card { 
                        background: #f8f9fa; 
                        padding: 25px; 
                        border-radius: 10px; 
                        text-align: center;
                        border-top: 4px solid #3498db;
                    }
                    .user-card { border-top-color: #3498db; }
                    .course-card { border-top-color: #2ecc71; }
                    .card-title { 
                        color: #2c3e50; 
                        margin-bottom: 15px; 
                        font-size: 1.2rem;
                    }
                    .card-name { 
                        font-size: 1.4rem; 
                        color: #2c3e50; 
                        margin: 10px 0;
                        font-weight: 600;
                    }
                    .print-footer { 
                        margin-top: 40px; 
                        text-align: center; 
                        color: #7f8c8d; 
                        font-size: 0.9rem; 
                        padding-top: 20px; 
                        border-top: 2px solid #eee;
                    }
                    .verification-note { 
                        background: #e3f2fd; 
                        padding: 15px; 
                        border-radius: 8px; 
                        margin-top: 20px;
                        font-size: 0.9rem;
                    }
                    .status-badge { 
                        display: inline-block; 
                        padding: 8px 20px; 
                        border-radius: 20px; 
                        font-weight: 600; 
                        margin-left: 10px;
                    }
                    @media print {
                        body { background: white; margin: 0; }
                        .certificate-print { box-shadow: none; border: none; padding: 0; }
                        .no-print { display: none !important; }
                    }
                </style>
            </head>
            <body>
                <div class="certificate-print">
                    <div class="print-header">
                        <h1><i class="fas fa-certificate" style="color: #3498db;"></i> Certificate Details</h1>
                        <p>Certificate Code: <strong>{{ $certificate->certificate_code }}</strong></p>
                        <p>Printed on: ${new Date().toLocaleDateString()} ${new Date().toLocaleTimeString()}</p>
                    </div>
                    
                    <div class="certificate-body">
                        <div class="certificate-info">
                            <div class="info-row">
                                <span class="info-label">Certificate Status:</span>
                                <span class="info-value">
                                    <span class="status-badge" style="background: {{ $certificateStatusColor }}; color: white;">
                                        {{ $certificateStatusText }}
                                    </span>
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Issued Date:</span>
                                <span class="info-value">
                                    {{ $certificate->issued_at ? $certificate->issued_at->format('F j, Y') : 'Not issued' }}
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Expiry Date:</span>
                                <span class="info-value">
                                    @if($certificate->expires_at)
                                        {{ $certificate->expires_at->format('F j, Y') }}
                                    @else
                                        No expiry (permanent)
                                    @endif
                                </span>
                            </div>
                            <div class="info-row">
                                <span class="info-label">Certificate ID:</span>
                                <span class="info-value">#{{ $certificate->id }}</span>
                            </div>
                        </div>
                        
                        <div class="user-course-info">
                            <div class="user-card">
                                <div class="card-title">
                                    <i class="fas fa-user-graduate" style="color: #3498db;"></i> Recipient
                                </div>
                                <div class="card-name">{{ $certificate->user->name ?? 'N/A' }}</div>
                                <p>{{ $certificate->user->email ?? '' }}</p>
                                <p><small>User ID: {{ $certificate->user_id }}</small></p>
                            </div>
                            
                            <div class="course-card">
                                <div class="card-title">
                                    <i class="fas fa-book-open" style="color: #2ecc71;"></i> Course
                                </div>
                                <div class="card-name">{{ $certificate->course->title ?? 'N/A' }}</div>
                                <p>{{ $certificate->course->code ?? 'No Code' }}</p>
                                <p><small>Course ID: {{ $certificate->course_id }}</small></p>
                            </div>
                        </div>
                        
                        <div class="verification-note">
                            <strong><i class="fas fa-shield-alt"></i> Verification Note:</strong><br>
                            This certificate can be verified online using the code: <strong>{{ $certificate->certificate_code }}</strong><br>
                            Visit: {{ url('/certificates/verify') }}
                        </div>
                    </div>
                    
                    <div class="print-footer">
                        <p>Issued by {{ config('app.name') }} • Certificate printed on ${new Date().toLocaleDateString()}</p>
                        <p><small>This is an official document. Do not alter.</small></p>
                    </div>
                </div>
                
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(() => window.close(), 500);
                    };
                <\/script>
            </body>
            </html>
        `);
        printWindow.document.close();
    }

    // Copy certificate link
    function copyCertificateLink() {
        const link = '{{ url("/certificates/" . $certificate->id) }}';
        navigator.clipboard.writeText(link).then(() => {
            showToast('Certificate link copied to clipboard', 'success');
        }).catch(err => {
            showToast('Failed to copy link', 'error');
        });
    }

    // Download certificate
    function downloadCertificate(event) {
        if (event) event.preventDefault();
        showToast('Downloading certificate PDF...', 'info');
        window.open('{{ url("/certificates/" . $certificate->id . "/download") }}', '_blank');
    }

    // Copy to clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            showToast('Copied to clipboard', 'success');
        }).catch(err => {
            showToast('Failed to copy', 'error');
        });
    }

    // Copy verification code
    function copyVerificationCode() {
        const code = document.getElementById('verificationCode').value;
        copyToClipboard(code);
    }

    // Send certificate email
    function sendCertificateEmail() {
        if (confirm('Send this certificate via email to the user?')) {
            showToast('Sending certificate via email...', 'info');
            // In a real app, this would trigger an email sending process
            setTimeout(() => {
                showToast('Certificate sent successfully', 'success');
            }, 1500);
        }
    }

    // Extend certificate
    function extendCertificate() {
        const modal = new bootstrap.Modal(document.getElementById('extendModal'));
        modal.show();
    }

    // Set extension days
    function setExtensionDays(days) {
        document.getElementById('extension_days').value = days;
        document.getElementById('customExtensionContainer').style.display = 'none';
        updateNewExpiryDate();
    }

    // Show custom extension
    function showCustomExtension() {
        document.getElementById('customExtensionContainer').style.display = 'block';
        document.getElementById('custom_days').focus();
        
        document.getElementById('custom_days').addEventListener('input', function() {
            document.getElementById('extension_days').value = this.value;
            updateNewExpiryDate();
        });
    }

    // Update new expiry date
    function updateNewExpiryDate() {
        const extensionDays = parseInt(document.getElementById('extension_days').value) || 0;
        const currentExpiry = '{{ $certificate->expires_at ? $certificate->expires_at->format("Y-m-d") : "" }}';
        
        let newDate;
        if (currentExpiry) {
            const currentDate = new Date(currentExpiry);
            currentDate.setDate(currentDate.getDate() + extensionDays);
            newDate = currentDate;
        } else {
            const currentDate = new Date();
            currentDate.setDate(currentDate.getDate() + extensionDays);
            newDate = currentDate;
        }
        
        const formattedDate = newDate.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        document.getElementById('new_expiry_date').value = formattedDate;
    }

    // Reissue certificate
    function reissueCertificate() {
        if (confirm('Reissue this certificate? This will create a new certificate with a new code.')) {
            window.location.href = '{{ url("/certificates/" . $certificate->id . "/reissue") }}';
        }
    }

    // Share certificate
    function shareCertificate() {
        const modal = new bootstrap.Modal(document.getElementById('shareModal'));
        modal.show();
    }

    // Copy share link
    function copyShareLink() {
        const link = document.getElementById('shareLink').value;
        copyToClipboard(link);
    }

    // Copy verification link
    function copyVerificationLink() {
        const link = document.getElementById('verificationLink').value;
        copyToClipboard(link);
    }

    // Share via email
    function shareEmail() {
        const subject = `Certificate: {{ $certificate->certificate_code }} - {{ $certificate->course->title ?? 'Course Certificate' }}`;
        const body = `Check out this certificate:\n\nCertificate Code: {{ $certificate->certificate_code }}\nRecipient: {{ $certificate->user->name }}\nCourse: {{ $certificate->course->title }}\nIssued: {{ $certificate->issued_at ? $certificate->issued_at->format('M d, Y') : 'Not issued' }}\n\nView certificate: {{ url('/certificates/' . $certificate->id) }}\nVerify certificate: {{ url('/certificates/' . $certificate->id . '/verify') }}`;
        window.location.href = `mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`;
    }

    // Share via WhatsApp
    function shareWhatsApp() {
        const text = `Check out this certificate: {{ url('/certificates/' . $certificate->id) }}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    }

    // Share via LinkedIn
    function shareLinkedIn() {
        const url = encodeURIComponent('{{ url("/certificates/" . $certificate->id) }}');
        const title = encodeURIComponent('Certificate: {{ $certificate->certificate_code }}');
        const summary = encodeURIComponent('Certificate of completion for {{ $certificate->course->title }}');
        window.open(`https://www.linkedin.com/sharing/share-offsite/?url=${url}&title=${title}&summary=${summary}`, '_blank');
    }

    // Share via Twitter
    function shareTwitter() {
        const text = `Certificate: {{ $certificate->certificate_code }} for {{ $certificate->course->title }}\n{{ url('/certificates/' . $certificate->id) }}`;
        window.open(`https://twitter.com/intent/tweet?text=${encodeURIComponent(text)}`, '_blank');
    }

    // Generate report
    function generateReport() {
        showToast('Generating certificate report...', 'info');
        // In a real app, this would generate a detailed report
        setTimeout(() => {
            window.open('{{ url("/reports/certificate/" . $certificate->id) }}', '_blank');
        }, 1000);
    }

    // Initialize QR Code
    function initializeQRCode() {
        // In a real app, you would generate an actual QR code
        // For now, we'll just show a placeholder
        const qrContainer = document.getElementById('qrCode');
        const verificationUrl = '{{ url("/certificates/" . $certificate->id . "/verify") }}';
        
        // This would be replaced with actual QR code generation
        // Example: new QRCode(qrContainer, verificationUrl);
        
        // For demo purposes, we'll just show the URL
        qrContainer.innerHTML = `
            <div class="qr-placeholder">
                <i class="fas fa-qrcode fa-4x text-muted"></i>
                <div class="mt-2 small text-muted">QR Code</div>
                <div class="mt-1 very-small text-muted" style="font-size: 0.7rem; max-width: 150px; word-break: break-all;">
                    {{ $certificate->certificate_code }}
                </div>
            </div>
        `;
    }

    // Download QR Code
    function downloadQRCode() {
        showToast('Downloading QR Code...', 'info');
        // In a real app, this would download the actual QR code image
        setTimeout(() => {
            showToast('QR Code downloaded', 'success');
        }, 1000);
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

    // Load related certificates (AJAX example)
    function loadMoreRelatedCertificates() {
        showToast('Loading more related certificates...', 'info');
        // AJAX call to load more related certificates
    }
</script>

@endsection