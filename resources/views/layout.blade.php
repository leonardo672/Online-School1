<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electrical Goods Store Management System</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Google Fonts for better typography -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500&display=swap" rel="stylesheet">
    
    <style>
    /* General Page Styling */
    body {
        font-family: 'Roboto', sans-serif;
        background: #F4F5F7;
        margin: 0;
        padding: 0;
        color: #333;
    }

    /* Navbar Styling */
    .navbar {
    background: linear-gradient(145deg,rgb(38, 46, 49),rgb(31, 44, 61)); /* Gradient Background */
    padding: 10px 20px;  /* Reduced padding */
    border-radius: 0;
    box-shadow: 0 6px 18px rgba(0, 0, 0, 0.1);
    display: flex;  /* Use flexbox to center content */
    justify-content: center;  /* Center content horizontally */
    align-items: center;  /* Center content vertically */
    text-align: center;
    }

    .navbar h2 {
    color: white;
    font-family: 'Times New Roman', serif; /* Times New Roman font */
    font-weight: 600;
    font-size: 24px;  /* Adjusted font size */
    letter-spacing: 1px;
    text-transform: uppercase;
    transform: translateX(60%);
    margin-bottom: 0;
    text-align: center;
    }

    .navbar h2::before {
    content: "⚙️"; /* Gear icon for management */
    margin-right: 10px; /* Space between the icon and the text */
    font-size: 30px; /* Adjust the size of the gear */
    
    }

    .navbar .navbar-toggler-icon {
        background-color: #ffffff;
    }

    /* Sidebar Styling */
    .sidebar {
        background: linear-gradient(145deg,rgb(38, 46, 49),rgb(31, 44, 61));
        /* color: #fff; */
        position: fixed;
        top: 0;
        left: 0;
        width: 250px;  /* Reduced width for a smaller sidebar */
        height: 100vh;
        padding: 8px 8px;
        box-shadow: 4px 0 20px rgba(0, 0, 0, 0.2);
        transition: all 0.3s ease;
        z-index: 999;
        margin-top: 65px; /* Add margin to prevent overlap with the header */
    }
    
    .sidebar a {
        display: block;
        color:rgb(255, 255, 255);
        background-color:rgb(20, 88, 145);
        padding: 5px 20px;
        text-decoration: none;
        font-size: 20px;  /* Reduced font size for a more compact look */
        border-radius: 5px;
        margin-bottom: 20px;
        font-family: 'Times New Roman', serif; 
        transition: all 0.3s ease;
    }

    .sidebar a:hover {
        background-color:rgb(112, 169, 192);
        color: #fff;
        transform: scale(1.05);
        font-family: 'Times New Roman', serif; /* Times New Roman font */
        font-weight: 400;
        font-size: 20px; 
    }

    .sidebar a i {
        margin-right: 10px;
    }

    .sidebar h5 {
        color: #B8B8B8;
        font-size: 20px;  /* Reduced font size */
        margin-bottom: 20px;
        text-align: center;
        font-family: 'Times New Roman', serif; /* Times New Roman font */
    }

    /* Content Area */
    .content-wrapper {
        margin-left: 230px;  /* Adjusted margin to match smaller sidebar width */
        padding: 30px;
        transition: all 0.3s ease;
        margin-top: 5px; /* Space for header */
    }

    .col-md-12 {
        background-color: #fff;
        padding: 10px;
        border-radius: 15px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        transition: all 0.3s ease;
    }

    .col-md-12:hover {
        transform: scale(1.02);
    }

    /* Footer Styling */
    .footer {
    position: fixed;  /* Fixed position */
    left: 250px;  /* Matches the sidebar width to place it under the sidebar */
    bottom: 0;  /* Position it at the bottom of the page */
    width: 100%;  /* Set width to 100% of the screen width, or adjust based on requirement */
    background: linear-gradient(145deg,rgb(38, 46, 49),rgb(31, 44, 61));  /* Dark background color */
    color: white;  /* White text */
    text-align: center;  /* Center the text */
    padding: 10px 20px;  /* Small padding for the footer */
    font-size: 14px;  /* Smaller font size */
    z-index: 10;  /* Ensure it stays on top of other elements */
    transform: translateX(-10%);
    }

    /* Button Styling - Remove Borders Between Buttons */
    button, .btn, a.btn {
        border: none;
        outline: none;
        box-shadow: none;
        text-decoration: none;
    }

    /* Animations for Interactive Elements */
    .button-hover-effect {
        position: relative;
        overflow: hidden; 
        color:rgb(32, 63, 104);
        border-radius: 10px;
    }

    .button-hover-effect::after {
        content: '';
        position: absolute;
        top: 50%;
        left: 50%;
        width: 300%;
        height: 300%;
        background: rgba(43, 111, 189, 0.5);
        transition: all 0.5s ease;
        transform: translate(-50%, -50%);
        border-radius: 50%;
        pointer-events: none;

    }

    .button-hover-effect:hover::after {
        width: 0;
        height: 0;
    }

    .button-hover-effect:hover {
        background: #00C6FF;
        color: #fff;
        transform: scale(1.05);
    }

    /* Sidebar collapses on mobile */
    @media (max-width: 768px) {
        .sidebar {
            width: 100%;
            height: auto;
            position: relative;
            padding: 20px;
        }

        .content-wrapper {
            margin-left: 0;
            padding: 20px;
        }

        .col-md-12 {
            padding: 20px;
        }
    }
    </style>
</head>

<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container">
            <a class="navbar-brand" href="#"><h2>Electrical Goods Store Management</h2></a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- User Management Section -->
        <h5>User Management</h5>
        <a href="{{ url('/users') }}" class="button-hover-effect"><i class="fas fa-users"></i> Users</a>
        <a href="{{ url('/customers') }}" class="button-hover-effect"><i class="fas fa-user-friends"></i> Customers</a>

        <!-- Product Management Section -->
        <h5>Product Management</h5>
        <a href="{{ url('/categoriess') }}" class="button-hover-effect"><i class="fas fa-cogs"></i> Categories</a>
        <a href="{{ url('/brands') }}" class="button-hover-effect"><i class="fas fa-tag"></i> Brands</a>
        <a href="{{ url('/products') }}" class="button-hover-effect"><i class="fas fa-box"></i> Products</a>

        <!-- Order Management Section -->
        <h5>Order Management</h5>
        <a href="{{ url('/orders') }}" class="button-hover-effect"><i class="fas fa-shopping-cart"></i> Orders</a>
        <a href="{{ url('/order_items') }}" class="button-hover-effect"><i class="fas fa-box-open"></i> Order Items</a>
        <a href="{{ url('/stock_movements') }}" class="button-hover-effect"><i class="fas fa-truck"></i> Stock Movements</a>
    </div>

    <!-- Main Content Area -->
    <div class="content-wrapper">
        <div class="row">
            <div class="col-md-12">
                @yield('content') <!-- Content Injected Here -->
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        <p>&copy; 2024 Electrical Goods Store | All Rights Reserved</p>
    </div>

    <!-- Bootstrap JS (for toggling dropdowns and navbar) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>