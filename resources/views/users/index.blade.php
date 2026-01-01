@extends('layout')
@section('content')

<div class="card shadow-lg rounded card-bg">
    <div class="card-header header-bg">
        <div class="d-flex justify-content-between align-items-center">
            <h2><i class="fas fa-users"></i> Users Management</h2>
            <div>
                <!-- Search Form -->
                <form action="{{ url('/users') }}" method="GET" class="d-inline">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="Search users..." 
                               value="{{ request('search') }}" style="max-width: 250px;">
                        <button class="btn btn-outline-light" type="submit">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="card-body">
        <!-- Button to Add New User -->
        <a href="{{ url('/users/create') }}" class="btn btn-add-user mb-4" title="Add New User">
            <i class="fas fa-plus-circle" aria-hidden="true" style="margin-right: 8px;"></i> Add New User
        </a>
        
        <!-- Users Table -->
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr>
                            <td>{{ $loop->iteration }}</td>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                @if($user->role == 'admin')
                                    <span class="badge bg-danger">Admin</span>
                                @elseif($user->role == 'instructor')
                                    <span class="badge bg-warning">Instructor</span>
                                @else
                                    <span class="badge bg-secondary">Student</span>
                                @endif
                            </td>
                            <td>{{ $user->created_at->format('M d, Y') }}</td>
                            <td>
                                <div class="btn-group" role="group">
                                    <!-- View Button -->
                                    <a href="{{ url('/users/' . $user->id) }}" title="View User" class="btn btn-view btn-sm">
                                        <i class="fas fa-eye" aria-hidden="true"></i>
                                    </a>

                                    <!-- Edit Button -->
                                    <a href="{{ url('/users/' . $user->id . '/edit') }}" title="Edit User" class="btn btn-edit btn-sm">
                                        <i class="fas fa-edit" aria-hidden="true"></i>
                                    </a>

                                    <!-- Delete Button -->
                                    <form method="POST" action="{{ url('/users/' . $user->id) }}" accept-charset="UTF-8" style="display:inline">
                                        @method('DELETE')
                                        @csrf
                                        <button type="submit" class="btn btn-delete btn-sm" title="Delete User" 
                                                onclick="return confirm('Are you sure you want to delete this user?')">
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
        
        <!-- No Users Message -->
        @if($users->isEmpty())
            <div class="text-center py-5">
                <i class="fas fa-users fa-3x text-muted mb-3"></i>
                <h4 class="text-muted">No users found</h4>
                <p class="text-muted">Get started by creating your first user!</p>
                <a href="{{ url('/users/create') }}" class="btn btn-add-user">
                    <i class="fas fa-plus-circle" aria-hidden="true"></i> Create Your First User
                </a>
            </div>
        @endif
    </div>
</div>

<style>
    /* Button Styles */
    .btn-add-user {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
        font-weight: 600;
        padding: 12px 25px;
    }

    .btn-add-user:hover {
        background: linear-gradient(145deg, #2980b9, #1c5a7a);
        color: white;
    }

    .btn-view {
        background-color: #3498db;
        color: white;
    }

    .btn-edit {
        background-color: #fd7e14;
        color: white;
    }

    .btn-delete {
        background-color: #dc3545;
        color: white;
    }

    /* Table Styling */
    .table th {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
    }

    .badge {
        padding: 6px 12px;
        border-radius: 20px;
        font-weight: 500;
        font-size: 12px;
    }
</style>

@endsection