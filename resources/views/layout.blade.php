<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Learning Platform</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Google Fonts for better typography -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    
    <style>
    /* General Page Styling */
    body {
        font-family: 'Poppins', sans-serif;
        background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        margin: 0;
        padding: 0;
        color: #333;
        min-height: 100vh;
    }

    /* Navbar Styling */
    .navbar {
        background: linear-gradient(145deg, #2c3e50, #3498db);
        padding: 15px 20px;
        border-radius: 0;
        box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
        display: flex;
        justify-content: center;
        align-items: center;
        text-align: center;
        position: fixed;
        top: 0;
        width: 100%;
        z-index: 1000;
    }

    .navbar h2 {
        color: white;
        font-weight: 600;
        font-size: 26px;
        letter-spacing: 1px;
        margin-bottom: 0;
        text-align: center;
    }

    .navbar h2::before {
        content: "📚";
        margin-right: 10px;
        font-size: 28px;
    }

    .navbar .navbar-toggler-icon {
        background-color: #ffffff;
    }

    /* Sidebar Styling */
    .sidebar {
        background: linear-gradient(145deg, #2c3e50, #34495e);
        position: fixed;
        top: 70px;
        left: 0;
        width: 250px;
        height: calc(100vh - 120px);
        padding: 20px 15px;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        z-index: 999;
        overflow-y: auto;
    }
    
    .sidebar a {
        display: block;
        color: white;
        background: linear-gradient(145deg, #3498db, #2980b9);
        padding: 12px 20px;
        text-decoration: none;
        font-size: 16px;
        border-radius: 8px;
        margin-bottom: 12px;
        transition: all 0.3s ease;
        border-left: 4px solid transparent;
    }

    .sidebar a:hover {
        background: linear-gradient(145deg, #2980b9, #1c5a7a);
        color: #fff;
        transform: translateX(5px);
        border-left: 4px solid #e74c3c;
    }

    .sidebar a i {
        margin-right: 10px;
        width: 20px;
        text-align: center;
    }

    .sidebar h5 {
        color: #ecf0f1;
        font-size: 18px;
        margin: 25px 0 15px 0;
        padding-bottom: 10px;
        border-bottom: 2px solid #3498db;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 1px;
    }

    /* Content Area */
    .content-wrapper {
        margin-left: 270px;
        padding: 40px;
        transition: all 0.3s ease;
        margin-top: 70px;
        min-height: calc(100vh - 120px);
    }

    .content-card {
        background-color: #fff;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 8px 30px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: 1px solid #eef2f7;
    }

    .content-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    /* Footer Styling */
    .footer {
        position: fixed;
        left: 250px;
        bottom: 0;
        width: calc(100% - 250px);
        background: linear-gradient(145deg, #2c3e50, #34495e);
        color: white;
        text-align: center;
        padding: 15px 20px;
        font-size: 14px;
        z-index: 10;
        border-top: 2px solid #3498db;
    }

    /* Button Styling */
    .btn-custom {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
        border: none;
        padding: 10px 25px;
        border-radius: 8px;
        transition: all 0.3s ease;
    }

    .btn-custom:hover {
        background: linear-gradient(145deg, #2980b9, #1c5a7a);
        transform: translateY(-2px);
        color: white;
        box-shadow: 0 5px 15px rgba(52, 152, 219, 0.3);
    }

    /* Table Styling */
    .table-custom {
        background: white;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
    }

    .table-custom thead {
        background: linear-gradient(145deg, #3498db, #2980b9);
        color: white;
    }

    /* Alert Styling */
    .alert-school {
        background: linear-gradient(145deg, #e3f2fd, #bbdefb);
        border: 1px solid #64b5f6;
        border-left: 4px solid #2196f3;
        border-radius: 8px;
    }

    /* Status Badges */
    .badge-published {
        background: linear-gradient(145deg, #2ecc71, #27ae60);
        color: white;
    }

    .badge-draft {
        background: linear-gradient(145deg, #f39c12, #d35400);
        color: white;
    }

    /* Progress Bar */
    .progress-school {
        height: 8px;
        border-radius: 4px;
        background: #ecf0f1;
    }

    .progress-school .progress-bar {
        background: linear-gradient(145deg, #3498db, #2980b9);
        border-radius: 4px;
    }

    /* Sidebar collapses on mobile */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            padding: 15px;
            top: 0;
            margin-top: 70px;
        }

        .content-wrapper {
            margin-left: 0;
            padding: 20px;
            margin-top: 20px;
        }

        .footer {
            position: relative;
            left: 0;
            width: 100%;
            margin-top: 20px;
        }
    }

    /* Scrollbar Styling */
    .sidebar::-webkit-scrollbar {
        width: 6px;
    }

    .sidebar::-webkit-scrollbar-track {
        background: #2c3e50;
    }

    .sidebar::-webkit-scrollbar-thumb {
        background: #3498db;
        border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-thumb:hover {
        background: #2980b9;
    }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="#"><h2>Online Learning Platform</h2></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Dashboard Section -->
        <h5>Dashboard</h5>
        <a href="{{ url('/dashboard') }}" class="button-hover-effect"><i class="fas fa-tachometer-alt"></i> Dashboard</a>

        <!-- User Management Section -->
        <h5>User Management</h5>
        <a href="{{ url('/users') }}" class="button-hover-effect"><i class="fas fa-users"></i> Users</a>

        <!-- Course Management Section -->
        <h5>Course Management</h5>
        <a href="{{ url('/categories') }}" class="button-hover-effect"><i class="fas fa-folder"></i> Categories</a>
        <a href="{{ url('/courses') }}" class="button-hover-effect"><i class="fas fa-graduation-cap"></i> Courses</a>
        <a href="{{ url('/lessons') }}" class="button-hover-effect"><i class="fas fa-book-open"></i> Lessons</a>

        <!-- Student Management Section -->
        <h5>Student Management</h5>
        <a href="{{ url('/enrollments') }}" class="button-hover-effect"><i class="fas fa-user-plus"></i> Enrollments</a>
        <a href="{{ url('/lesson-progress') }}" class="button-hover-effect"><i class="fas fa-chart-line"></i> Progress</a>
        <a href="{{ url('/certificates') }}" class="button-hover-effect"><i class="fas fa-certificate"></i> Certificates</a>

        <!-- Financial Section -->
        <h5>Financial</h5>
        <a href="{{ url('/payments') }}" class="button-hover-effect"><i class="fas fa-credit-card"></i> Payments</a>

        <!-- Reviews & Feedback -->
        <h5>Reviews & Feedback</h5>
        <a href="{{ url('/reviews') }}" class="button-hover-effect"><i class="fas fa-star"></i> Reviews</a>
    </div>

    <!-- Main Content Area -->
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12">
                <div class="content-card">
                    @yield('content') <!-- Content Injected Here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Online Learning Platform | Empowering Education Through Technology</p>
    </div>

    <!-- Bootstrap JS (for toggling dropdowns and navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Additional JavaScript -->
    <script>
        // Add active class to current navigation item
        document.addEventListener('DOMContentLoaded', function() {
            const currentUrl = window.location.pathname;
            const navLinks = document.querySelectorAll('.sidebar a');
            
            navLinks.forEach(link => {
                if (link.getAttribute('href') === currentUrl) {
                    link.classList.add('active');
                    link.style.background = 'linear-gradient(145deg, #e74c3c, #c0392b)';
                    link.style.borderLeft = '4px solid #f1c40f';
                }
            });
        });

        // Smooth scroll for sidebar
        document.querySelectorAll('.sidebar a').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                if (this.getAttribute('href').startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth',
                            block: 'start'
                        });
                    }
                }
            });
        });
    </script>
</body>
</html>