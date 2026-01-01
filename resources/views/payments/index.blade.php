@extends('layout')
@section('content')

<!-- Main Card with Updated Styling -->
<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-credit-card"></i> Payment Management</h2>
            <div class="d-flex">
                <!-- Status Filter -->
                <div class="me-3">
                    <select id="statusFilter" class="form-select form-select-sm" onchange="filterByStatus(this.value)">
                        <option value="">All Statuses</option>
                        @foreach(App\Models\Payment::STATUSES as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>
                            {{ ucfirst($status) }}
                        </option>
                        @endforeach
                    </select>
                </div>
                
                <!-- Payment Method Filter -->
                <div class="me-3">
                    <select id="methodFilter" class="form-select form-select-sm" onchange="filterByMethod(this.value)">
                        <option value="">All Methods</option>
                        @foreach(App\Models\Payment::METHODS as $method)
                        <option value="{{ $method }}" {{ request('method') == $method ? 'selected' : '' }}>
                            {{ ucfirst($method) }}
                        </option>
                        @endforeach
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
                            {{ $course->title }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                
                <!-- Search Form -->
                <form action="{{ url('/payments') }}" method="GET" class="d-inline">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search transactions..." 
                               value="{{ request('search') }}" style="max-width: 200px;">
                        <input type="hidden" name="status" id="searchStatus" value="{{ request('status') }}">
                        <input type="hidden" name="method" id="searchMethod" value="{{ request('method') }}">
                        <input type="hidden" name="user_id" id="searchUserId" value="{{ request('user_id') }}">
                        <input type="hidden" name="course_id" id="searchCourseId" value="{{ request('course_id') }}">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Button to Add New Payment -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="{{ url('/payments/create') }}" class="btn btn-add-payment" title="Add New Payment">
                <i class="fas fa-plus-circle" aria-hidden="true" style="margin-right: 8px;"></i> Add New Payment
            </a>
            
            <!-- Export & Report Buttons -->
            <div>
                <a href="{{ url('/payments/export/csv') }}" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-file-csv"></i> Export CSV
                </a>
                <a href="{{ url('/payments/export/pdf') }}" class="btn btn-outline-danger">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </a>
            </div>
        </div>
        
        <!-- Payment Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card bg-primary text-white p-3 rounded">
                    <h6 class="mb-1">Total Payments</h6>
                    <h3 class="mb-0">
                        @if(method_exists($payments, 'total'))
                            {{ $payments->total() }}
                        @else
                            {{ $payments->count() }}
                        @endif
                    </h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-success text-white p-3 rounded">
                    <h6 class="mb-1">Total Revenue</h6>
                    <h3 class="mb-0">${{ number_format($totalRevenue ?? 0, 2) }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-info text-white p-3 rounded">
                    <h6 class="mb-1">Completed Payments</h6>
                    <h3 class="mb-0">{{ $completedCount ?? 0 }}</h3>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card bg-warning text-white p-3 rounded">
                    <h6 class="mb-1">Pending Payments</h6>
                    <h3 class="mb-0">{{ $pendingCount ?? 0 }}</h3>
                </div>
            </div>
        </div>
        
        <!-- Monthly Revenue Chart (Placeholder) -->
        <div class="card mb-4">
            <div class="card-header bg-light">
                <h5 class="mb-0"><i class="fas fa-chart-line"></i> Revenue Overview</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="revenue-chart-placeholder">
                            <canvas id="revenueChart" height="200"></canvas>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="payment-methods-breakdown">
                            <h6>Payment Methods Breakdown</h6>
                            <div id="methodsBreakdown" class="mt-3">
                                @foreach($paymentMethodsBreakdown ?? [] as $method => $count)
                                <div class="method-item mb-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="text-capitalize">{{ $method }}</span>
                                        <span class="badge bg-secondary">{{ $count }}</span>
                                    </div>
                                    <div class="progress" style="height: 5px;">
                                        @php
                                            $percentage = $paymentsCount > 0 ? ($count / $paymentsCount) * 100 : 0;
                                        @endphp
                                        <div class="progress-bar" role="progressbar" 
                                             style="width: {{ $percentage }}%;"></div>
                                    </div>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Payments Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Transaction ID</th>
                        <th>User</th>
                        <th>Course</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Method</th>
                        <th>Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payments as $item)
                        <tr>
                            <td>
                                @if(method_exists($payments, 'currentPage'))
                                    {{ ($payments->currentPage() - 1) * $payments->perPage() + $loop->iteration }}
                                @else
                                    {{ $loop->iteration }}
                                @endif
                            </td>
                            <td>
                                <div class="transaction-id">
                                    <code class="transaction-code">{{ $item->transaction_id ?? 'N/A' }}</code>
                                    @if($item->transaction_id)
                                    <button class="btn btn-sm btn-copy ms-1" onclick="copyToClipboard('{{ $item->transaction_id }}')" title="Copy Transaction ID">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="user-avatar me-2">
                                        <i class="fas fa-user-circle" style="color: #3498db; font-size: 20px;"></i>
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
                                <div class="amount-display">
                                    <span class="badge bg-light text-dark amount-badge">
                                        ${{ number_format($item->amount, 2) }}
                                    </span>
                                </div>
                            </td>
                            <td>
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
                                    $color = $statusColors[$item->status] ?? 'secondary';
                                    $icon = $statusIcons[$item->status] ?? 'fa-question-circle';
                                @endphp
                                <span class="badge bg-{{ $color }} status-badge">
                                    <i class="fas {{ $icon }}"></i> {{ ucfirst($item->status) }}
                                </span>
                            </td>
                            <td>
                                @php
                                    $methodColors = [
                                        'stripe' => 'primary',
                                        'paypal' => 'info',
                                        'manual' => 'secondary'
                                    ];
                                    $methodIcons = [
                                        'stripe' => 'fa-credit-card',
                                        'paypal' => 'fa-paypal',
                                        'manual' => 'fa-hand-holding-usd'
                                    ];
                                    $methodColor = $methodColors[$item->payment_method] ?? 'dark';
                                    $methodIcon = $methodIcons[$item->payment_method] ?? 'fa-money-bill-wave';
                                @endphp
                                <span class="badge bg-{{ $methodColor }} method-badge">
                                    <i class="fab {{ $methodIcon }}"></i> {{ ucfirst($item->payment_method) }}
                                </span>
                            </td>
                            <td>
                                <div class="date-display">
                                    <small class="text-muted">{{ $item->created_at->format('M d, Y') }}</small>
                                    <br>
                                    <small class="text-muted">{{ $item->created_at->format('h:i A') }}</small>
                                </div>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- View Button -->
                                    <a href="{{ url('/payments/' . $item->id) }}" title="View Payment" class="btn btn-view btn-sm">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ url('/payments/' . $item->id . '/edit') }}" title="Edit Payment" class="btn btn-edit btn-sm">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>

                                    <!-- Quick Actions Dropdown -->
                                    <div class="dropdown d-inline">
                                        <button class="btn btn-action btn-sm dropdown-toggle" type="button" 
                                                data-bs-toggle="dropdown" title="Quick Actions">
                                            <i class="fas fa-cog"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            @if($item->status === 'pending')
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="updateStatus({{ $item->id }}, 'completed')">
                                                    <i class="fas fa-check text-success"></i> Mark as Completed
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="updateStatus({{ $item->id }}, 'failed')">
                                                    <i class="fas fa-times text-danger"></i> Mark as Failed
                                                </a>
                                            </li>
                                            @endif
                                            @if($item->status === 'completed')
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="updateStatus({{ $item->id }}, 'refunded')">
                                                    <i class="fas fa-undo text-info"></i> Mark as Refunded
                                                </a>
                                            </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <a class="dropdown-item" href="{{ url('/payments/create?user_id=' . $item->user_id . '&course_id=' . $item->course_id) }}">
                                                    <i class="fas fa-copy"></i> Duplicate Payment
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item" href="#" onclick="sendReceipt({{ $item->id }})">
                                                    <i class="fas fa-receipt"></i> Send Receipt
                                                </a>
                                            </li>
                                        </ul>
                                    </div>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ url('/payments/' . $item->id) }}" accept-charset="UTF-8" style="display:inline">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-delete btn-sm" title="Delete Payment" 
                                                onclick="return confirm('Are you sure you want to delete this payment?')">
                                            <i class="fas fa-trash" aria-hidden="true"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination - Only show if pagination is available -->
        @if(method_exists($payments, 'hasPages') && $payments->hasPages())
            <div class="d-flex justify-content-center mt-4">
                {{ $payments->withQueryString()->links() }}
            </div>
        @endif
        
        <!-- No Payments Message -->
        @if($payments->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-credit-card fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No payments found</h4>
                <p class="text-muted">Payment records will appear here when users purchase courses</p>
                <a href="{{ url('/payments/create') }}" class="btn btn-add-payment">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Create Payment Record
                </a>
            </div>
        @endif
        
        <!-- Financial Summary -->
        @if($payments->isNotEmpty())
        <div class="card mt-4">
            <div class="card-header bg-dark text-white">
                <h5 class="mb-0"><i class="fas fa-calculator"></i> Financial Summary</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <div class="summary-item text-center">
                            <h6 class="text-muted">Total Revenue</h6>
                            <h3 class="text-success">${{ number_format($totalRevenue ?? 0, 2) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-item text-center">
                            <h6 class="text-muted">Average Payment</h6>
                            <h3 class="text-primary">
                                ${{ $paymentsCount > 0 ? number_format($totalRevenue / $paymentsCount, 2) : '0.00' }}
                            </h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-item text-center">
                            <h6 class="text-muted">Completed Revenue</h6>
                            <h3 class="text-info">${{ number_format($completedRevenue ?? 0, 2) }}</h3>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="summary-item text-center">
                            <h6 class="text-muted">Refunded Amount</h6>
                            <h3 class="text-danger">${{ number_format($refundedAmount ?? 0, 2) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>
</div>

<!-- CSS Styling -->
<style>
    /* General Styles */
    .card {
        border: none;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.1);
        border-radius: 15px;
        overflow: hidden;
    }
    
    .card-header {
        background: linear-gradient(145deg, #e74c3c, #c0392b);
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

    /* Add New Payment Button */
    .btn-add-payment {
        background: linear-gradient(145deg, #e74c3c, #c0392b);
        color: white;
        font-weight: 600;
        padding: 12px 25px;
    }

    .btn-add-payment:hover {
        background: linear-gradient(145deg, #c0392b, #a93226);
        transform: translateY(-3px);
        box-shadow: 0 6px 15px rgba(231, 76, 60, 0.4);
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
        background-color: #f39c12;
        color: white;
    }

    .btn-edit:hover {
        background-color: #e67e22;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(243, 156, 18, 0.3);
    }

    .btn-action {
        background-color: #95a5a6;
        color: white;
    }

    .btn-action:hover {
        background-color: #7f8c8d;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(149, 165, 166, 0.3);
    }

    .btn-copy {
        background-color: #6c757d;
        color: white;
        padding: 2px 6px;
        font-size: 10px;
    }

    .btn-copy:hover {
        background-color: #5a6268;
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
        background-color: #ffeaea;
        transform: scale(1.01);
        transition: all 0.2s ease;
    }

    .table th {
        background: linear-gradient(145deg, #e74c3c, #c0392b);
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

    /* Badge Styling */
    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 12px;
    }

    .status-badge, .method-badge {
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }

    .amount-badge {
        font-weight: 600;
        font-size: 13px;
        padding: 8px 12px;
        border: 1px solid #dee2e6;
    }

    /* Transaction ID */
    .transaction-id {
        display: flex;
        align-items: center;
    }

    .transaction-code {
        background-color: #f8f9fa;
        padding: 4px 8px;
        border-radius: 4px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    /* User, Course Icons */
    .user-avatar, .course-icon {
        width: 30px;
        text-align: center;
    }

    /* Date Display */
    .date-display {
        min-width: 100px;
    }

    /* Filters */
    .form-select-sm {
        width: 150px;
        border-radius: 6px;
        border: 1px solid #ced4da;
    }

    /* Revenue Chart */
    .revenue-chart-placeholder {
        height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    /* Payment Methods Breakdown */
    .method-item .progress-bar {
        background: linear-gradient(145deg, #e74c3c, #c0392b);
    }

    /* Summary Items */
    .summary-item {
        padding: 15px;
        border-radius: 8px;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
    }

    .summary-item h3 {
        margin: 10px 0 0 0;
        font-weight: 700;
    }

    /* Dropdown Menu */
    .dropdown-menu {
        border: none;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        border-radius: 8px;
        padding: 8px 0;
    }

    .dropdown-item {
        padding: 8px 16px;
        font-size: 14px;
        transition: all 0.2s ease;
    }

    .dropdown-item:hover {
        background-color: #f8f9fa;
    }

    /* Pagination */
    .pagination {
        margin-bottom: 0;
    }

    .page-link {
        color: #e74c3c;
        border: 1px solid #dee2e6;
        margin: 0 3px;
        border-radius: 6px;
    }

    .page-item.active .page-link {
        background: linear-gradient(145deg, #e74c3c, #c0392b);
        border-color: #e74c3c;
        color: white;
    }

    .page-link:hover {
        color: #c0392b;
        background-color: #e9ecef;
        border-color: #dee2e6;
    }

    /* Empty State */
    .fa-3x {
        font-size: 4rem;
    }

    /* Copy Animation */
    @keyframes copyFlash {
        0% { background-color: #f8f9fa; }
        50% { background-color: #2ecc71; color: white; }
        100% { background-color: #f8f9fa; }
    }

    .copy-flash {
        animation: copyFlash 0.5s ease;
    }
</style>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- JavaScript for Enhanced Functionality -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Confirm before deleting
        const deleteForms = document.querySelectorAll('form[action*="/payments/"]');
        
        deleteForms.forEach(form => {
            if (form.method === 'post') {
                form.addEventListener('submit', function(e) {
                    const confirmDelete = confirm('Are you sure you want to delete this payment record?');
                    if (!confirmDelete) {
                        e.preventDefault();
                    }
                });
            }
        });
        
        // Add hover effect to table rows
        const tableRows = document.querySelectorAll('.table tbody tr');
        tableRows.forEach(row => {
            row.addEventListener('mouseenter', function() {
                this.style.transition = 'all 0.2s ease';
            });
        });
        
        // Status badge animation
        const statusBadges = document.querySelectorAll('.status-badge');
        statusBadges.forEach(badge => {
            if (badge.classList.contains('bg-success')) {
                badge.addEventListener('mouseenter', function() {
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.style.transform = 'rotate(20deg)';
                        icon.style.transition = 'transform 0.3s ease';
                    }
                });
                
                badge.addEventListener('mouseleave', function() {
                    const icon = this.querySelector('i');
                    if (icon) {
                        icon.style.transform = 'rotate(0deg)';
                    }
                });
            }
        });
        
        // Initialize revenue chart if data exists
        initializeRevenueChart();
    });
    
    // Filter functions
    function filterByStatus(status) {
        document.getElementById('searchStatus').value = status;
        const form = document.getElementById('searchStatus').closest('form');
        form.submit();
    }
    
    function filterByMethod(method) {
        document.getElementById('searchMethod').value = method;
        const form = document.getElementById('searchMethod').closest('form');
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
    
    // Copy transaction ID to clipboard
    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            const button = event.target.closest('button');
            const originalHtml = button.innerHTML;
            
            // Flash animation
            const code = button.parentElement.querySelector('.transaction-code');
            code.classList.add('copy-flash');
            
            // Change button icon
            button.innerHTML = '<i class="fas fa-check"></i>';
            
            setTimeout(() => {
                code.classList.remove('copy-flash');
                button.innerHTML = originalHtml;
            }, 1500);
            
            // Show toast notification
            showToast('Transaction ID copied to clipboard!', 'success');
        }).catch(err => {
            console.error('Failed to copy: ', err);
            showToast('Failed to copy transaction ID', 'error');
        });
    }
    
    // Update payment status
    function updateStatus(paymentId, newStatus) {
        if (!paymentId || !newStatus) return;
        
        const statusNames = {
            'completed': 'Completed',
            'pending': 'Pending',
            'failed': 'Failed',
            'refunded': 'Refunded'
        };
        
        if (!confirm(`Mark this payment as ${statusNames[newStatus]}?`)) {
            return;
        }
        
        // Show loading state
        const button = event.target.closest('a');
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
        
        fetch(`/payments/${paymentId}/status`, {
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
                showToast(`Payment marked as ${statusNames[newStatus]}`, 'success');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                showToast(data.message || 'Failed to update status', 'error');
                button.innerHTML = originalHtml;
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred while updating status', 'error');
            button.innerHTML = originalHtml;
        });
    }
    
    // Send receipt
    function sendReceipt(paymentId) {
        if (!paymentId) return;
        
        if (!confirm('Send payment receipt to user?')) {
            return;
        }
        
        const button = event.target.closest('a');
        const originalHtml = button.innerHTML;
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';
        
        fetch(`/payments/${paymentId}/send-receipt`, {
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
        })
        .catch(error => {
            console.error('Error:', error);
            showToast('An error occurred while sending receipt', 'error');
            button.innerHTML = originalHtml;
        });
    }
    
    // Initialize revenue chart
    function initializeRevenueChart() {
        const ctx = document.getElementById('revenueChart');
        if (!ctx) return;
        
        // Sample data - replace with actual API call
        const revenueData = {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
            datasets: [{
                label: 'Monthly Revenue ($)',
                data: [1200, 1900, 3000, 2500, 2800, 3200],
                backgroundColor: 'rgba(231, 76, 60, 0.2)',
                borderColor: 'rgba(231, 76, 60, 1)',
                borderWidth: 2,
                tension: 0.4,
                fill: true
            }]
        };
        
        const chart = new Chart(ctx, {
            type: 'line',
            data: revenueData,
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: true,
                        position: 'top',
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return '$' + value;
                            }
                        }
                    }
                }
            }
        });
        
        // Real data loading (commented out for now)
        /*
        fetch('/api/payments/monthly-revenue')
            .then(response => response.json())
            .then(data => {
                chart.data.labels = data.months;
                chart.data.datasets[0].data = data.revenues;
                chart.update();
            })
            .catch(error => {
                console.error('Error loading revenue data:', error);
            });
        */
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
    
    // Export functionality
    function exportReport(format) {
        const params = new URLSearchParams(window.location.search);
        params.set('format', format);
        
        window.location.href = `/payments/export?${params.toString()}`;
    }
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + F to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
            e.preventDefault();
            document.querySelector('input[name="search"]').focus();
        }
        
        // Ctrl/Cmd + N to add new payment
        if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
            e.preventDefault();
            window.location.href = '/payments/create';
        }
        
        // Ctrl/Cmd + E to export CSV
        if ((e.ctrlKey || e.metaKey) && e.key === 'e') {
            e.preventDefault();
            exportReport('csv');
        }
    });
</script>

@endsection