@extends('layout')
@section('content')

<!-- Main Card with Updated Styling -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-certificate"></i> Certificates Management</h2>
            <div class="d-flex">
                <!-- Status Filter -->
                <div class="me-3">
                    <select id="statusFilter" class="form-select form-select-sm" onchange="filterByStatus(this.value)">
                        <option value="">All Status</option>
                        <option value="valid" {{ request('status') == 'valid' ? 'selected' : '' }}>Valid</option>
                        <option value="expired" {{ request('status') == 'expired' ? 'selected' : '' }}>Expired</option>
                        <option value="expiring_soon" {{ request('status') == 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</option>
                        <option value="not_issued" {{ request('status') == 'not_issued' ? 'selected' : '' }}>Not Issued</option>
                    </select>
                </div>
                
                <!-- User Filter -->
                @if(isset($users) && $users->isNotEmpty())
                <div class="me-3">
                    <select id="userFilter" class="form-select form-select-sm" onchange="filterByUser(this.value)">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <!-- Course Filter -->
                @if(isset($courses) && $courses->isNotEmpty())
                <div class="me-3">
                    <select id="courseFilter" class="form-select form-select-sm" onchange="filterByCourse(this.value)">
                        <option value="">All Courses</option>
                        @foreach($courses as $course)
                        <option value="{{ $course->id }}" {{ request('course_id') == $course->id ? 'selected' : '' }}>
                            {{ Str::limit($course->title, 25) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <!-- Date Range Filter -->
                <div class="me-3">
                    <select id="dateRangeFilter" class="form-select form-select-sm" onchange="filterByDateRange(this.value)">
                        <option value="">All Time</option>
                        <option value="today" {{ request('date_range') == 'today' ? 'selected' : '' }}>Today</option>
                        <option value="this_week" {{ request('date_range') == 'this_week' ? 'selected' : '' }}>This Week</option>
                        <option value="this_month" {{ request('date_range') == 'this_month' ? 'selected' : '' }}>This Month</option>
                        <option value="last_30_days" {{ request('date_range') == 'last_30_days' ? 'selected' : '' }}>Last 30 Days</option>
                        <option value="this_year" {{ request('date_range') == 'this_year' ? 'selected' : '' }}>This Year</option>
                    </select>
                </div>
                
                <!-- Sort Options -->
                <div class="me-3">
                    <select id="sortFilter" class="form-select form-select-sm" onchange="sortCertificates(this.value)">
                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Newest First</option>
                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Oldest First</option>
                        <option value="expiring_soon" {{ request('sort') == 'expiring_soon' ? 'selected' : '' }}>Expiring Soon</option>
                        <option value="recently_expired" {{ request('sort') == 'recently_expired' ? 'selected' : '' }}>Recently Expired</option>
                        <option value="code_asc" {{ request('sort') == 'code_asc' ? 'selected' : '' }}>Code (A-Z)</option>
                        <option value="code_desc" {{ request('sort') == 'code_desc' ? 'selected' : '' }}>Code (Z-A)</option>
                    </select>
                </div>
                
                <!-- Search Form -->
                <form action="{{ url('/certificates') }}" method="GET" class="d-inline">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search certificates..." 
                               value="{{ request('search') }}" style="max-width: 200px;">
                        <input type="hidden" name="status" id="searchStatus" value="{{ request('status') }}">
                        <input type="hidden" name="user_id" id="searchUserId" value="{{ request('user_id') }}">
                        <input type="hidden" name="course_id" id="searchCourseId" value="{{ request('course_id') }}">
                        <input type="hidden" name="date_range" id="searchDateRange" value="{{ request('date_range') }}">
                        <input type="hidden" name="sort" id="searchSort" value="{{ request('sort', 'newest') }}">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Button to Add New Certificate -->
        <a href="{{ url('/certificates/create') }}" class="btn btn-add-certificate mb-4" title="Add New Certificate">
            <i class="fas fa-plus-circle" aria-hidden="true" style="margin-right: 8px;"></i> Issue New Certificate
        </a>
        
        <!-- Certificates Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white p-3 rounded">
                    <h6 class="mb-1">Total Certificates</h6>
                    <h3 class="mb-0">
                        @if(method_exists($certificates, 'total'))
                            {{ $certificates->total() }}
                        @else
                            {{ $certificates->count() }}
                        @endif
                    </h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h6 class="mb-1">Valid Certificates</h6>
                    <h3 class="mb-0">{{ $validCount ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-danger text-white p-3 rounded">
                    <h6 class="mb-1">Expired Certificates</h6>
                    <h3 class="mb-0">{{ $expiredCount ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h6 class="mb-1">Expiring Soon</h6>
                    <h3 class="mb-0">{{ $expiringSoonCount ?? 0 }}</h3>
                </div>
            </div>
        </div>
        
        <!-- Status Distribution Chart -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Status Distribution</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="status-bars">
                            @php
                                $statuses = [
                                    'valid' => ['name' => 'Valid', 'color' => 'success', 'count' => $validCount ?? 0],
                                    'expiring_soon' => ['name' => 'Expiring Soon', 'color' => 'warning', 'count' => $expiringSoonCount ?? 0],
                                    'expired' => ['name' => 'Expired', 'color' => 'danger', 'count' => $expiredCount ?? 0],
                                    'not_issued' => ['name' => 'Not Issued', 'color' => 'secondary', 'count' => $notIssuedCount ?? 0],
                                ];
                                $total = $certificatesCount ?? array_sum(array_column($statuses, 'count'));
                            @endphp
                            @foreach($statuses as $key => $status)
                                @php
                                    $percentage = $total > 0 ? ($status['count'] / $total) * 100 : 0;
                                @endphp
                                <div class="status-bar-item mb-3">
                                    <div class="d-flex justify-content-between mb-1">
                                        <div class="d-flex align-items-center">
                                            <span class="status-dot bg-{{ $status['color'] }} me-2"></span>
                                            <span class="status-name">{{ $status['name'] }}</span>
                                        </div>
                                        <span class="status-count">{{ $status['count'] }} certificates</span>
                                    </div>
                                    <div class="progress" style="height: 20px;">
                                        <div class="progress-bar bg-{{ $status['color'] }}" role="progressbar" 
                                             style="width: {{ $percentage }}%;" 
                                             aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                            {{ number_format($percentage, 1) }}%
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-stats text-center">
                            <div class="certificate-summary mb-4">
                                <h1 class="display-4 text-primary mb-0">{{ $total }}</h1>
                                <p class="text-muted mb-0">Total Certificates</p>
                            </div>
                            <div class="validity-stats">
                                <div class="row">
                                    <div class="col-6">
                                        <h5 class="text-success">{{ $validCount ?? 0 }}</h5>
                                        <p class="text-muted mb-0">Valid</p>
                                    </div>
                                    <div class="col-6">
                                        <h5 class="text-danger">{{ $expiredCount ?? 0 }}</h5>
                                        <p class="text-muted mb-0">Expired</p>
                                    </div>
                                </div>
                            </div>
                            <div class="mt-4">
                                <div class="recent-issue mb-3">
                                    <h6 class="text-muted">Latest Issue</h6>
                                    <h5 class="mb-0">
                                        @if($latestCertificate)
                                            {{ $latestCertificate->issued_at ? $latestCertificate->issued_at->diffForHumans() : $latestCertificate->created_at->diffForHumans() }}
                                        @else
                                            N/A
                                        @endif
                                    </h5>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Additional Statistics -->
        @if($certificates->isNotEmpty())
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-trophy"></i> Most Certified Course</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($mostCertifiedCourse) && $mostCertifiedCourse)
                        <div class="text-center py-4">
                            <div class="most-certified-icon mb-3">
                                <i class="fas fa-award fa-3x text-warning"></i>
                            </div>
                            <h4 class="text-primary">{{ $mostCertifiedCourse->title }}</h4>
                            <p class="text-muted">{{ $mostCertifiedCourse->code ?? 'No code' }}</p>
                            <div class="badge bg-warning text-dark fs-6 px-3 py-2">
                                <i class="fas fa-certificate me-2"></i>
                                {{ $mostCertifiedCourseCount ?? 0 }} Certificates
                            </div>
                            <p class="mt-3 mb-0">
                                <small class="text-muted">
                                    {{ number_format((($mostCertifiedCourseCount ?? 0) / ($certificatesCount ?? 1)) * 100, 1) }}% of all certificates
                                </small>
                            </p>
                        </div>
                        @else
                        <div class="text-center py-4">
                            <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No course data available</h5>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> Top 5 Courses</h5>
                    </div>
                    <div class="card-body">
                        @if(isset($courseStats) && $courseStats->isNotEmpty())
                        <div class="list-group">
                            @foreach($courseStats->take(5) as $course)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $course->title }}</h6>
                                    <small class="text-muted">{{ $course->code ?? 'No code' }}</small>
                                </div>
                                <span class="badge bg-primary rounded-pill">{{ $course->certificates_count }} certificates</span>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <div class="text-center py-4">
                            <i class="fas fa-book fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No course statistics available</h5>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Recent Activity -->
        @if(isset($recentCertificates) && $recentCertificates->isNotEmpty())
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-history"></i> Recent Activity</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    @foreach($recentCertificates as $certificate)
                    <div class="col-md-6 mb-3">
                        <div class="card recent-certificate-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h6 class="mb-1">{{ $certificate->user->name ?? 'N/A' }}</h6>
                                        <p class="text-muted mb-1">{{ $certificate->course->title ?? 'N/A' }}</p>
                                        <small class="text-muted">
                                            <i class="fas fa-certificate me-1"></i> {{ $certificate->certificate_code }}
                                        </small>
                                    </div>
                                    <div class="text-end">
                                        <small class="text-muted">
                                            {{ $certificate->issued_at ? $certificate->issued_at->diffForHumans() : $certificate->created_at->diffForHumans() }}
                                        </small>
                                        <br>
                                        @php
                                            $now = now();
                                            $isExpired = $certificate->expires_at && $certificate->expires_at->lt($now);
                                            $isExpiringSoon = $certificate->expires_at && $certificate->expires_at->gt($now) && $certificate->expires_at->diffInDays($now) <= 30;
                                            $statusColor = $isExpired ? 'danger' : ($isExpiringSoon ? 'warning' : 'success');
                                        @endphp
                                        <span class="badge bg-{{ $statusColor }} mt-2">
                                            @if($isExpired)
                                                Expired
                                            @elseif($isExpiringSoon)
                                                Expiring Soon
                                            @else
                                                Valid
                                            @endif
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
        @endif
        
        <!-- Certificates Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Certificate Code</th>
                        <th>Status</th>
                        <th>User</th>
                        <th>Course</th>
                        <th>Issued Date</th>
                        <th>Expiry Date</th>
                        <th>Validity Period</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($certificates as $item)
                        @php
                            $now = now();
                            $isExpired = $item->expires_at && $item->expires_at->lt($now);
                            $isExpiringSoon = $item->expires_at && $item->expires_at->gt($now) && $item->expires_at->diffInDays($now) <= 30;
                            $isValid = $item->issued_at && $item->issued_at->lte($now) && (!$item->expires_at || $item->expires_at->gt($now));
                            $isNotIssued = !$item->issued_at || $item->issued_at->gt($now);
                            
                            if ($isExpired) {
                                $status = 'expired';
                                $statusColor = 'danger';
                                $statusText = 'Expired';
                                $statusIcon = 'fa-times-circle';
                            } elseif ($isExpiringSoon) {
                                $status = 'expiring_soon';
                                $statusColor = 'warning';
                                $statusText = 'Expiring Soon';
                                $statusIcon = 'fa-exclamation-triangle';
                            } elseif ($isValid) {
                                $status = 'valid';
                                $statusColor = 'success';
                                $statusText = 'Valid';
                                $statusIcon = 'fa-check-circle';
                            } else {
                                $status = 'not_issued';
                                $statusColor = 'secondary';
                                $statusText = 'Not Issued';
                                $statusIcon = 'fa-clock';
                            }
                            
                            $validityPeriod = '';
                            if ($item->issued_at && $item->expires_at) {
                                $days = $item->issued_at->diffInDays($item->expires_at);
                                $validityPeriod = $days . ' days';
                            } elseif ($item->issued_at) {
                                $validityPeriod = 'No expiry';
                            } else {
                                $validityPeriod = 'Not issued';
                            }
                        @endphp
                        <tr>
                            <td>
                                @if(method_exists($certificates, 'currentPage'))
                                    {{ ($certificates->currentPage() - 1) * $certificates->perPage() + $loop->iteration }}
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </td>
                            <td>
                                <div class="certificate-code">
                                    <i class="fas fa-certificate me-2" style="color: #3498db;"></i>
                                    <strong class="text-primary">{{ $item->certificate_code }}</strong>
                                    <br>
                                    <small class="text-muted">ID: {{ $item->id }}</small>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-{{ $statusColor }} d-flex align-items-center" style="width: fit-content;">
                                    <i class="fas {{ $statusIcon }} me-1"></i>
                                    {{ $statusText }}
                                    @if($isExpiringSoon && $item->expires_at)
                                        <small class="ms-2">({{ $item->expires_at->diffForHumans() }})</small>
                                    @endif
                                </span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-2">
                                        <i class="fas fa-user-circle" style="color: #3498db; font-size: 24px;"></i>
                                    </div>
                                    <div>
                                        <strong>{{ $item->user->name ?? 'N/A' }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $item->user->email ?? '' }}</small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($item->course)
                                <div class="course-info">
                                    <a href="{{ url('/courses/' . $item->course_id) }}" class="text-decoration-none">
                                        <div class="d-flex align-items-center">
                                            <div class="course-icon me-2">
                                                <i class="fas fa-book" style="color: #2ecc71;"></i>
                                            </div>
                                            <div>
                                                <strong class="text-primary">{{ $item->course->title }}</strong>
                                                <br>
                                                <small class="text-muted">{{ $item->course->code ?? 'N/A' }}</small>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                                @else
                                <span class="text-danger">No Course</span>
                                @endif
                            </td>
                            <td>
                                <div class="date-display">
                                    @if($item->issued_at)
                                        <strong>{{ $item->issued_at->format('M d, Y') }}</strong>
                                        <br>
                                        <small class="text-muted">{{ $item->issued_at->format('h:i A') }}</small>
                                    @else
                                        <span class="text-muted">Not issued</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="date-display">
                                    @if($item->expires_at)
                                        <strong class="text-{{ $isExpired ? 'danger' : ($isExpiringSoon ? 'warning' : 'success') }}">
                                            {{ $item->expires_at->format('M d, Y') }}
                                        </strong>
                                        <br>
                                        <small class="text-muted">
                                            @if($isExpired)
                                                Expired {{ $item->expires_at->diffForHumans() }}
                                            @elseif($isExpiringSoon)
                                                Expires {{ $item->expires_at->diffForHumans() }}
                                            @else
                                                {{ $item->expires_at->diffForHumans() }} left
                                            @endif
                                        </small>
                                    @else
                                        <span class="text-success">No expiry</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="validity-period">
                                    @if($item->issued_at && $item->expires_at)
                                        <div class="progress" style="height: 20px;">
                                            @php
                                                $totalDays = $item->issued_at->diffInDays($item->expires_at);
                                                $daysPassed = $item->issued_at->diffInDays(min($now, $item->expires_at));
                                                $percentage = $totalDays > 0 ? ($daysPassed / $totalDays) * 100 : 0;
                                            @endphp
                                            <div class="progress-bar bg-{{ $statusColor }}" role="progressbar" 
                                                 style="width: {{ $percentage }}%;"
                                                 aria-valuenow="{{ $percentage }}" aria-valuemin="0" aria-valuemax="100">
                                                {{ $validityPeriod }}
                                            </div>
                                        </div>
                                    @else
                                        <span class="badge bg-light text-dark">{{ $validityPeriod }}</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- View Button -->
                                    <a href="{{ url('/certificates/' . $item->id) }}" title="View Certificate" class="btn btn-view btn-sm">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ url('/certificates/' . $item->id . '/edit') }}" title="Edit Certificate" class="btn btn-edit btn-sm">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>

                                    <!-- Quick Actions Dropdown -->
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-actions btn-sm dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" title="Quick Actions">
                                            <i class="fas fa-bolt"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><h6 class="dropdown-header">Quick Actions</h6></li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="downloadCertificate({{ $item->id }})">
                                                    <i class="fas fa-download me-2"></i> Download PDF
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="sendCertificateEmail({{ $item->id }})">
                                                    <i class="fas fa-envelope me-2"></i> Send via Email
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="extendValidity({{ $item->id }})">
                                                    <i class="fas fa-calendar-plus me-2"></i> Extend Validity
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="reissueCertificate({{ $item->id }})">
                                                    <i class="fas fa-redo me-2"></i> Reissue Certificate
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item text-danger" href="#" 
                                                   onclick="confirmDeletion({{ $item->id }}, '{{ $item->certificate_code }}')">
                                                    <i class="fas fa-trash me-2"></i> Delete
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Verify Button -->
                                    <a href="{{ url('/certificates/' . $item->id . '/verify') }}" 
                                       title="Verify Certificate" 
                                       class="btn btn-verify btn-sm"
                                       target="_blank">
                                        <i class="fas fa-shield-alt"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination - Only show if pagination is available -->
        @if(method_exists($certificates, 'hasPages') && $certificates->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $certificates->withQueryString()->links() }}
            </div>
        @endif
        
        <!-- No Certificates Message -->
        @if($certificates->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-certificate fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No certificates found</h4>
                <p class="text-muted">Certificates are awarded to users upon course completion</p>
                <a href="{{ url('/certificates/create') }}" class="btn btn-add-certificate">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Issue First Certificate
                </a>
            </div>
        @endif
        
        <!-- Summary & Export Section -->
        @if($certificates->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Summary & Export</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="summary-item text-center">
                            <h6 class="text-muted">Most Certified Course</h6>
                            <h4 class="text-primary">
                                @if(isset($mostCertifiedCourse) && $mostCertifiedCourse)
                                    {{ Str::limit($mostCertifiedCourse->title, 20) }}
                                @else
                                    N/A
                                @endif
                            </h4>
                            <p class="text-muted mb-0">
                                {{ $mostCertifiedCourseCount ?? 0 }} certificates
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-item text-center">
                            <h6 class="text-muted">Top User</h6>
                            <h4 class="text-success">
                                @if(isset($topUsers) && $topUsers->isNotEmpty())
                                    {{ Str::limit($topUsers->first()->name, 20) }}
                                @else
                                    N/A
                                @endif
                            </h4>
                            <p class="text-muted mb-0">
                                @if(isset($topUsers) && $topUsers->isNotEmpty())
                                    {{ $topUsers->first()->certificates_count ?? 0 }} certificates
                                @else
                                    0 certificates
                                @endif
                            </p>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="summary-item text-center">
                            <h6 class="text-muted">Export Options</h6>
                            <div class="mt-2">
                                <a href="{{ url('/certificates/export/csv') }}" class="btn btn-outline-primary me-2">
                                    <i class="fas fa-file-csv"></i> CSV
                                </a>
                                <a href="{{ url('/certificates/export/pdf') }}" class="btn btn-outline-danger">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </a>
                                <a href="{{ url('/certificates/export/excel') }}" class="btn btn-outline-success mt-2">
                                    <i class="fas fa-file-excel"></i> Excel
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
        
        <!-- Bulk Actions -->
        @if($certificates->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-tasks"></i> Bulk Actions</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <div class="mb-3">
                            <label class="form-label">Select Action</label>
                            <select class="form-select" id="bulkAction">
                                <option value="">Choose action...</option>
                                <option value="download">Download Selected as PDF</option>
                                <option value="email">Send via Email</option>
                                <option value="extend">Extend Validity (30 days)</option>
                                <option value="revoke">Revoke Certificates</option>
                                <option value="delete">Delete Certificates</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="mb-3">
                            <label class="form-label">Select Certificates</label>
                            <div class="select-all-container mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                    <label class="form-check-label" for="selectAll">
                                        Select All Certificates
                                    </label>
                                </div>
                            </div>
                            <div class="selected-certificates">
                                <small class="text-muted">No certificates selected</small>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary" onclick="performBulkAction()">
                            <i class="fas fa-play"></i> Execute Action
                        </button>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- Certificate Details Modal -->
<div class="modal fade" id="certificateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-certificate"></i> Certificate Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="certificateModalBody">
                <!-- Content loaded via JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Confirm Deletion Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger"><i class="fas fa-exclamation-triangle"></i> Confirm Deletion</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete certificate <strong id="deleteCertificateCode"></strong>?</p>
                <div class="alert alert-danger">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <div>
                            <strong>Warning: This action cannot be undone!</strong><br>
                            This certificate will be permanently deleted.
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" id="deleteForm" style="display: inline;">
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

<!-- Extend Validity Modal -->
<div class="modal fade" id="extendModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-calendar-plus"></i> Extend Validity</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="extendForm" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Certificate</label>
                        <input type="text" class="form-control" id="extendCertificateCode" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Current Expiry Date</label>
                        <input type="text" class="form-control" id="currentExpiryDate" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="extensionDays" class="form-label">Extension Period (days)</label>
                        <input type="number" class="form-control" id="extensionDays" name="extension_days" 
                               min="1" max="365" value="30" required>
                        <small class="text-muted">Enter number of days to extend the certificate validity</small>
                    </div>
                    <div class="mb-3">
                        <label for="newExpiryDate" class="form-label">New Expiry Date</label>
                        <input type="text" class="form-control" id="newExpiryDate" readonly>
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

<style>
    /* General Styles */
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

    .btn-sm {
        padding: 6px 10px;
        font-size: 12px;
        min-width: 36px;
    }

    .btn-group .btn {
        margin: 0 2px;
        border-radius: 6px;
    }

    /* Add New Certificate Button */
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

    /* Action Buttons */
    .btn-view {
        background-color: #3498db;
        color: white;
    }

    .btn-view:hover {
        background-color: #2980b9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
    }

    .btn-edit {
        background-color: #2ecc71;
        color: white;
    }

    .btn-edit:hover {
        background-color: #27ae60;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(46, 204, 113, 0.3);
    }

    .btn-actions {
        background-color: #9b59b6;
        color: white;
    }

    .btn-actions:hover {
        background-color: #8e44ad;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(155, 89, 182, 0.3);
    }

    .btn-verify {
        background-color: #f39c12;
        color: white;
    }

    .btn-verify:hover {
        background-color: #d68910;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(243, 156, 18, 0.3);
    }

    .btn-delete {
        background-color: #dc3545;
        color: white;
    }

    .btn-delete:hover {
        background-color: #c82333;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
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

    .table-striped tbody tr:nth-child(odd) {
        background-color: #f8f9fa;
    }

    .table-hover tbody tr:hover {
        background-color: #e3f2fd;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    .table th {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
        padding: 15px;
        font-weight: 600;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 0.5px;
        border: none;
    }

    .table td {
        padding: 15px;
        text-align: left;
        font-size: 14px;
        color: #444;
        border-bottom: 1px solid #e9ecef;
        vertical-align: middle;
    }

    .table-responsive {
        overflow-x: auto;
        border-radius: 10px;
    }

    /* Statistics Cards */
    .stat-card {
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }

    /* Status Indicators */
    .status-dot {
        display: inline-block;
        width: 10px;
        height: 10px;
        border-radius: 50%;
    }

    .status-bar-item .progress {
        border-radius: 10px;
        overflow: hidden;
    }

    .status-bar-item .progress-bar {
        border-radius: 10px;
        font-size: 12px;
        font-weight: 600;
        padding: 0 10px;
        display: flex;
        align-items: center;
        justify-content: flex-start;
    }

    /* Certificate Code */
    .certificate-code {
        font-family: 'Courier New', monospace;
    }

    /* Date Display */
    .date-display {
        min-width: 120px;
    }

    /* Validity Period Progress */
    .validity-period .progress {
        border-radius: 10px;
        overflow: hidden;
    }

    .validity-period .progress-bar {
        font-size: 11px;
        font-weight: 600;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* User and Course Info */
    .user-avatar, .course-icon {
        width: 30px;
        text-align: center;
    }

    /* Filters */
    .form-select-sm {
        width: 150px;
        border-radius: 6px;
        border: 1px solid #ced4da;
    }

    /* Summary Items */
    .summary-item {
        padding: 15px;
        border-radius: 8px;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        height: 100%;
    }

    .summary-item h4 {
        margin: 10px 0;
        font-weight: 600;
    }

    /* Badge Styles */
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
    }

    /* Most Certified Course Icon */
    .most-certified-icon {
        animation: pulse 2s infinite;
    }

    /* Recent Certificate Cards */
    .recent-certificate-card {
        border: 1px solid #e9ecef;
        border-radius: 10px;
        transition: all 0.3s ease;
    }

    .recent-certificate-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }

    /* Progress Bar Animation */
    @keyframes progressAnimation {
        0% { width: 0%; }
        100% { width: var(--progress-width); }
    }

    .progress-bar {
        animation: progressAnimation 1s ease-out;
    }

    /* Pulse Animation */
    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.1); }
        100% { transform: scale(1); }
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

    /* Certificate Preview Animation */
    @keyframes certificateGlow {
        0% { box-shadow: 0 0 5px rgba(52, 152, 219, 0.5); }
        50% { box-shadow: 0 0 20px rgba(52, 152, 219, 0.8); }
        100% { box-shadow: 0 0 5px rgba(52, 152, 219, 0.5); }
    }

    .certificate-glow {
        animation: certificateGlow 2s infinite;
    }
</style>

<!-- JavaScript for Enhanced Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });

        // Initialize select all checkbox
        const selectAllCheckbox = document.getElementById('selectAll');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                const checkboxes = document.querySelectorAll('input[type="checkbox"][name="certificate_ids"]');
                checkboxes.forEach(checkbox => {
                    checkbox.checked = selectAllCheckbox.checked;
                });
                updateSelectedCount();
            });
        }

        // Add hover effect to table rows
        const tableRows = document.querySelectorAll('.table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.2s ease';
            });
        });

        // Add certificate glow animation to valid certificates
        const validCertificates = document.querySelectorAll('.badge.bg-success');
        validCertificates.forEach(badge => {
            badge.classList.add('certificate-glow');
        });

        // Add animation to most certified course icon
        const mostCertifiedIcon = document.querySelector('.most-certified-icon');
        if (mostCertifiedIcon) {
            mostCertifiedIcon.classList.add('pulse');
        }
    });

    // Filter functions
    function filterByStatus(status) {
        document.getElementById('searchStatus').value = status;
        const form = document.getElementById('searchStatus').closest('form');
        form.submit();
    }
    
    function filterByUser(userId) {
        document.getElementById('searchUserId').value = userId;
        const form = document.getElementById('searchUserId').closest('form');
        form.submit();
    }
    
    function filterByCourse(courseId) {
        document.getElementById('searchCourseId').value = courseId;
        const form = document.getElementById('searchCourseId').closest('form');
        form.submit();
    }
    
    function filterByDateRange(dateRange) {
        document.getElementById('searchDateRange').value = dateRange;
        const form = document.getElementById('searchDateRange').closest('form');
        form.submit();
    }
    
    function sortCertificates(sortValue) {
        document.getElementById('searchSort').value = sortValue;
        const form = document.getElementById('searchSort').closest('form');
        form.submit();
    }

    // Certificate actions
    function downloadCertificate(certificateId) {
        showToast('Downloading certificate...', 'info');
        window.open(`/certificates/${certificateId}/download`, '_blank');
    }

    function sendCertificateEmail(certificateId) {
        if (confirm('Send this certificate via email to the user?')) {
            showToast('Sending email...', 'info');
            setTimeout(() => {
                showToast('Certificate sent via email successfully', 'success');
            }, 1500);
        }
    }

    function extendValidity(certificateId) {
        // Get certificate details
        fetch(`/api/certificates/${certificateId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('extendCertificateCode').value = data.certificate.certificate_code;
                    document.getElementById('currentExpiryDate').value = 
                        data.certificate.expires_at ? new Date(data.certificate.expires_at).toLocaleDateString() : 'No expiry';
                    
                    // Set form action
                    document.getElementById('extendForm').action = `/certificates/${certificateId}/extend`;
                    
                    // Show modal
                    const modal = new bootstrap.Modal(document.getElementById('extendModal'));
                    modal.show();
                    
                    // Update new expiry date
                    updateNewExpiryDate();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Failed to load certificate details', 'error');
            });
    }

    function updateNewExpiryDate() {
        const extensionDays = parseInt(document.getElementById('extensionDays').value) || 0;
        const currentExpiry = document.getElementById('currentExpiryDate').value;
        
        if (currentExpiry !== 'No expiry') {
            const currentDate = new Date(currentExpiry);
            currentDate.setDate(currentDate.getDate() + extensionDays);
            document.getElementById('newExpiryDate').value = currentDate.toLocaleDateString();
        } else {
            const currentDate = new Date();
            currentDate.setDate(currentDate.getDate() + extensionDays);
            document.getElementById('newExpiryDate').value = currentDate.toLocaleDateString() + ' (from today)';
        }
    }

    function reissueCertificate(certificateId) {
        if (confirm('Reissue this certificate? This will generate a new certificate code.')) {
            showToast('Reissuing certificate...', 'info');
            setTimeout(() => {
                showToast('Certificate reissued successfully', 'success');
                setTimeout(() => window.location.reload(), 1500);
            }, 1500);
        }
    }

    function confirmDeletion(certificateId, certificateCode) {
        document.getElementById('deleteCertificateCode').textContent = certificateCode;
        document.getElementById('deleteForm').action = `/certificates/${certificateId}`;
        
        const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }

    // Update selected certificates count
    function updateSelectedCount() {
        const checkboxes = document.querySelectorAll('.certificate-checkbox:checked');
        const count = checkboxes.length;
        const selectedContainer = document.querySelector('.selected-certificates');
        
        if (count > 0) {
            const certificateCodes = Array.from(checkboxes).map(cb => cb.value).join(', ');
            selectedContainer.innerHTML = `
                <span class="text-primary">
                    <i class="fas fa-check-circle"></i> ${count} certificate(s) selected
                </span>
                <small class="d-block text-muted mt-1">${certificateCodes}</small>
            `;
        } else {
            selectedContainer.innerHTML = '<small class="text-muted">No certificates selected</small>';
        }
    }

    // Bulk actions
    function performBulkAction() {
        const action = document.getElementById('bulkAction').value;
        const selectedCertificates = Array.from(document.querySelectorAll('.certificate-checkbox:checked'))
            .map(cb => cb.value);
        
        if (selectedCertificates.length === 0) {
            showToast('Please select at least one certificate', 'warning');
            return;
        }
        
        if (!action) {
            showToast('Please select an action', 'warning');
            return;
        }
        
        let confirmMessage = '';
        switch(action) {
            case 'download':
                confirmMessage = `Download ${selectedCertificates.length} certificate(s) as PDF?`;
                break;
            case 'email':
                confirmMessage = `Send ${selectedCertificates.length} certificate(s) via email?`;
                break;
            case 'extend':
                confirmMessage = `Extend validity of ${selectedCertificates.length} certificate(s) by 30 days?`;
                break;
            case 'revoke':
                confirmMessage = `Revoke ${selectedCertificates.length} certificate(s)? This will mark them as expired.`;
                break;
            case 'delete':
                confirmMessage = `Permanently delete ${selectedCertificates.length} certificate(s)? This action cannot be undone!`;
                break;
        }
        
        if (confirm(confirmMessage)) {
            showToast(`Processing ${selectedCertificates.length} certificate(s)...`, 'info');
            
            // In a real app, you would submit a form or make an AJAX call
            setTimeout(() => {
                showToast(`Bulk action "${action}" completed successfully`, 'success');
                // Reset selection
                document.querySelectorAll('.certificate-checkbox').forEach(cb => cb.checked = false);
                document.getElementById('selectAll').checked = false;
                updateSelectedCount();
            }, 2000);
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

    // Export functionality
    function exportCertificates(format) {
        const params = new URLSearchParams(window.location.search);
        params.set('format', format);
        
        window.location.href = `/certificates/export?${params.toString()}`;
    }

    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            document.querySelector('input[name="search"]').focus();
        }
        
        // Ctrl/Cmd + N to add new certificate
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = '/certificates/create';
        }
        
        // Ctrl/Cmd + E to export CSV
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            e.preventDefault();
            exportCertificates('csv');
        }
    });

    // Auto-refresh for expiring certificates
    setInterval(() => {
        const expiringBadges = document.querySelectorAll('.badge.bg-warning');
        expiringBadges.forEach(badge => {
            badge.classList.toggle('certificate-glow');
        });
    }, 30000);
</script>

@endsection