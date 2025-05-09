<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super_Admin Dashboard Panel</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Iconscout CSS -->
    <link rel="stylesheet" href="https://unicons.iconscout.com/release/v4.0.0/css/line.css">
    <style>
        body {
            overflow-x: hidden;
        }
        .sidebar {
            width: 250px;
            height: 100vh;
            position: fixed;
            top: 0;
            left: 0;
            background-color:#0e4bf1;
            color: white;
            transition: transform 0.3s ease-in-out;
        }
        .sidebar.hidden {
            transform: translateX(-100%);
        }
        .sidebar ul li {
            margin: 10px 0;
        }
        .sidebar .nav-link {
            color: white;
            font-size: 1rem;
        }
        .toggle-btn {
            position: absolute;
            top: 10px;
            left: 10px;
            z-index: 1000;
            color: white;
            background: #0e4bf1;
            border: none;
        }
        .dynamic-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
            background-color: #f8f9fa;
            transition: margin-left 0.3s ease-in-out;
        }
        .dynamic-content.collapsed {
            margin-left: 0;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <h5 class="text-center py-3">Admin Modules</h5>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="loadContent('dashboard')">
                    <i class="uil uil-estate me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="loadContent('user-management')">
                    <i class="uil uil-user me-2"></i>User Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="loadContent('product-management')">
                    <i class="uil uil-box me-2"></i>Product Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="loadContent('ticket-management')">
                    <i class="uil uil-ticket me-2"></i>Ticket Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="loadContent('subscription-management')">
                    <i class="uil uil-receipt me-2"></i>Subscription Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="loadContent('order-management')">
                    <i class="uil uil-clipboard me-2"></i>Order Management
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#" onclick="loadContent('freelancer-management')">
                    <i class="uil uil-laptop me-2"></i>Freelancer Management
                </a>
            </li>
        </ul>
    </div>

    <!-- Toggle Button -->
    <button id="toggleBtn" class="toggle-btn">
        <i class="uil uil-bars"></i>
    </button>

    <!-- Dynamic Content Area -->
    <div id="main-content" class="dynamic-content">
        <h2>Welcome to the Admin Dashboard</h2>
        <p>Select a module from the left sidebar to begin.</p>
    </div>

    <!-- Bootstrap JS Bundle -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Toggle sidebar visibility
        const toggleBtn = document.getElementById('toggleBtn');
        const sidebar = document.getElementById('sidebar');
        const mainContent = document.getElementById('main-content');

        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('hidden');
            mainContent.classList.toggle('collapsed');
        });

        // Function to load dynamic content into the main content area
        function loadContent(module) {
            const content = {
                'dashboard': '<h2>Dashboard</h2><p>Welcome to the Dashboard module.</p>',
                'user-management': '<h2>User Management</h2><p>Manage your users here.</p>',
                'product-management': '<h2>Product Management</h2><p>Manage products efficiently.</p>',
                'ticket-management': '<h2>Ticket Management</h2><p>Track and manage tickets.</p>',
                'subscription-management': '<h2>Subscription Management</h2><p>Create and manage subscriptions.</p>',
                'order-management': '<h2>Order Management</h2><p>View and handle orders.</p>',
                'freelancer-management': '<h2>Freelancer Management</h2><p>Manage freelancers and their work.</p>'
            };
            document.getElementById('main-content').innerHTML = content[module] || '<h2>Module Not Found</h2>';
        }
    </script>
</body>
</html>