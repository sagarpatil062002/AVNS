<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- ApexCharts -->
    <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
    
<!-- Menu Toggle Button (Mobile) -->
<button class="menu-toggle" id="menuToggle">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Vertical Sidebar Navigation -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <span class="logo_name">AVNS</span>
        </div>
        
        <div class="menu-items">
            <ul class="nav-links">
                <li class="active">
                    <a href="dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="link-name">Dashboard</span>
                    </a>
                </li>
                
                <li>
                    <a href="customer_products.php">
                        <i class="fas fa-box-open"></i>
                        <span class="link-name">Products</span>
                    </a>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-ticket-alt"></i>
                        <span class="link-name">Tickets</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="create_ticket.php"><i class="fas fa-plus-circle"></i> Create Ticket</a></li>
                        <li><a href="view_ticket_status.php"><i class="fas fa-eye"></i> View Ticket Status</a></li>
                    </ul>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-file-invoice-dollar"></i>
                        <span class="link-name">Subscription</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="customer_subscription.php"><i class="fas fa-cart-plus"></i> Buy Subscription</a></li>
                        <li><a href="view_subscription.php"><i class="fas fa-file-alt"></i> View Subscription</a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="view_order.php">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="link-name">Order</span>
                    </a>
                </li>
                
                <li class="dropdown">
                    <a href="#">
                        <i class="fas fa-file-contract"></i>
                        <span class="link-name">Quotation</span>
                        <i class="fas fa-chevron-down dropdown-icon"></i>
                    </a>
                    <ul class="dropdown-content">
                        <li><a href="request_quotation.php"><i class="fas fa-file-upload"></i> Create Quotation</a></li>
                        <li><a href="view_Adminquotation.php"><i class="fas fa-file-search"></i> View Quotation</a></li>
                    </ul>
                </li>
                
                <li>
                    <a href="view_invoices.php">
                        <i class="fas fa-file-invoice"></i>
                        <span class="link-name">Invoices</span>
                    </a>
                </li>
                
                <li>
                    <a href="profile.php">
                        <i class="fas fa-user"></i>
                        <span class="link-name">Profile</span>
                    </a>
                </li>
            </ul>
            
            <ul class="logout-mode">
                <li>
                    <a href="../logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="link-name">Logout</span>
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    
    