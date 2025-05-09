<html>
<head>
    <style>
        /* General styles */
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            background-color: #f9f9f9;
        }

        h2 {
            text-align: center;
            font-size: 36px;
            color: #4b3f72;
            margin-top: 30px;
        }

        /* Header Section */
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #f8f9fa;
            padding: 10px 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .logo img {
            height: 40px;
        }

        nav ul {
            list-style: none;
            display: flex;
            margin: 0;
            padding: 0;
        }

        nav ul li {
            margin-left: 20px;
        }

        nav ul li a {
            text-decoration: none;
            color: #333;
            font-size: 16px;
        }

        nav ul li a:hover {
            color: #007bff;
        }

        /* Carousel Section */
        main {
            position: relative;
        }

        .carousel {
            position: relative;
            width: 100%;
            height: 400px;
            overflow: hidden;
        }

        .carousel img {
            width: 100%;
            height: 400px;
            object-fit: cover;
            display: none;
        }

        .carousel img.active {
            display: block;
        }

        .overlay {
            position: absolute;
            bottom: 20px;
            left: 20px;
            color: #fff;
            background-color: rgba(0, 0, 0, 0.5);
            padding: 10px 20px;
            font-size: 24px;
        }

        .carousel-controls {
            position: absolute;
            top: 50%;
            width: 100%;
            display: flex;
            justify-content: space-between;
            transform: translateY(-50%);
        }

        .prev, .next {
            background-color: rgba(0, 0, 0, 0.5);
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
        }

        /* Portfolio Section */
        .portfolio {
            padding: 50px 0;
        }

        .portfolio-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .portfolio-item {
            background-color: #fff;
            border-radius: 20px;
            width: 200px;
            padding: 20px;
            text-align: center;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }

        .portfolio-item img {
            width: 60px;
            height: 60px;
        }

        .portfolio-item:hover {
            transform: translateY(-10px);
        }

        /* Who We Are Section */
        .who-we-are {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            padding: 50px;
            background-color: #f5f5f5;
        }

        .text-section {
            width: 55%;
        }

        .text-section h2 {
            font-size: 36px;
            color: #39335F;
            margin-bottom: 20px;
        }

        .text-section p {
            font-size: 16px;
            line-height: 1.6;
            color: #555;
            margin-bottom: 20px;
        }

        .image-section {
            width: 40%;
        }

        .image-section img {
            width: 100%;
            border-radius: 10px;
            object-fit: cover;
        }

        /* Vision & Values Section */
        .vision-values-container {
            padding: 50px;
            background-color: #f9f9f9;
        }

        .container {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }

        .vision, .values {
            width: 45%;
            background-color: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .vision h2, .values h2 {
            font-size: 2rem;
            color: #4b3f72;
            margin-bottom: 20px;
        }

        .vision p, .values p {
            font-size: 1rem;
            margin-bottom: 10px;
            color: #555;
        }

        /* Footer Section */
        footer {
            position: fixed;
            bottom: 10px;
            left: 10px;
        }

        .whatsapp img {
            width: 40px;
            height: 40px;
        }

        .social-media {
            position: fixed;
            top: 200px;
            right: 10px;
            display: flex;
            flex-direction: column;
        }

        .social-media a {
            margin-bottom: 10px;
        }

        .social-media img {
            width: 30px;
            height: 30px;
        }

        .dropdown-content {
        display: none;
        position: absolute;
        background-color: #f9f9f9;
        min-width: 160px;
        box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
        z-index: 1;
    }

    .dropdown-content a {
        color: black;
        padding: 12px 16px;
        text-decoration: none;
        display: block;
    }

    .dropdown-content a:hover {
        background-color: #f1f1f1;
    }

    /* Show dropdown on hover */
    .dropdown:hover .dropdown-content {
        display: block;
    }
    </style>
</head>
<body>
<header>
    <div class="logo">
        <img src="logo.jpeg" alt="AVNS Technosoft Logo">
    </div>
    <nav>
        <ul>
            <li><a href="#">Home</a></li>
            <li class="dropdown">
                <a href="#">Login</a>
                <div class="dropdown-content">
                    <a href="Customer\login.php">customer Login</a>
                    <a href="Super_Admin\login.php">Super_Admin Login</a>
                    <a href="Sub_Admin\login.php">Employees Login</a>
                    <a href="Freelancer\login.php">FreeLancer Login</a>
                    <a href="distributor\login.php">Distributor Login</a>
                </div>
            </li>
            <li class="dropdown">
                <a href="#">Register</a>
                <div class="dropdown-content">
                    <a href="Customer\register.php">customer Register</a>
                    <a href="Super_Admin\register.php">Super_Admin Register</a>
                    <a href="Sub_Admin\login.php">Employee Register </a>
                    <a href="Freelancer\register.php">FreeLancer Register</a>
                    <a href="distributor\register.php">Distributor Register</a>
                </div>
            </li>
            
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</header>
</body>
<html>