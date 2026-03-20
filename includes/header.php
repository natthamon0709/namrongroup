<?php
// เริ่มต้น Session เพื่อเช็คสถานะ Login (ถ้ายังไม่ได้เริ่ม)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// ตรวจสอบว่ามีการเชื่อมต่อฐานข้อมูลหรือยัง ถ้ายังให้เชื่อมต่อ
if (!isset($conn)) {
    // ใช้ __DIR__ เพื่ออ้างอิงตำแหน่งไฟล์ที่แน่นอน (ป้องกันปัญหา Path ผิด)
    $connect_path = __DIR__ . '/../config/connect.php';
    if (file_exists($connect_path)) {
        include_once $connect_path;
    }
}

// กำหนดค่า Default ป้องกัน Error กรณีไม่มีข้อมูล
$site_name = isset($settings['site_name']) ? htmlspecialchars($settings['site_name']) : 'Namrong Group';
$theme_color = isset($settings['theme_color']) ? htmlspecialchars($settings['theme_color']) : '#0d6efd';
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- SEO Meta Tags จากฐานข้อมูล -->
    <title><?php echo isset($page_title) ? $page_title : $site_name; ?></title>
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <meta name="description" content="<?php echo isset($settings['meta_description']) ? htmlspecialchars($settings['meta_description']) : ''; ?>">
    <meta name="keywords" content="<?php echo isset($settings['meta_keywords']) ? htmlspecialchars($settings['meta_keywords']) : ''; ?>">
    <!-- Favicon -->
    <link rel="shortcut icon" href="<?php echo $settings['site_logo']; ?>" type="image/x-icon">
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Google Fonts (Kanit) -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">

    <style>
        :root {
            /* ดึงสีธีมจากฐานข้อมูล */
            --primary-color: <?php echo $theme_color; ?>;
            --secondary-color: #6c757d;
        }

        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8f9fa;
            color: #333;
        }

        /* --- Navbar & General Styles --- */
        .navbar {
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            background-color: #ffffff;
            padding-top: 1rem;
            padding-bottom: 1rem;
        }

        .navbar-brand {
            font-weight: 700;
            color: var(--primary-color) !important;
            font-size: 1.5rem;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
        }

        /* ปรับขนาดโลโก้ให้พอดี */
        .navbar-brand img {
            max-height: 40px;
            width: auto;
            margin-right: 10px;
        }

        .nav-link {
            font-weight: 500;
            color: #555;
        }

        .nav-link.active {
            color: var(--primary-color) !important;
            font-weight: 600;
        }

        .cart-icon {
            position: relative;
            font-size: 1.2rem;
            color: #333;
        }

        .cart-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            font-size: 0.7rem;
            padding: 0.25em 0.5em;
        }

        /* --- Hero Section --- */
        .hero-section {
            background-color: <?php echo $theme_color; ?>;
            padding: 80px 0;
            margin-bottom: 3rem;
            border-radius: 0 0 50px 50px;
            position: relative;
            overflow: hidden;
        }

        .hero-title {
            font-weight: 800;
            color: white;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* --- Product Card --- */
        .product-card {
            border: none;
            transition: all 0.3s ease;
            height: 100%;
            background: #fff;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.02);
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .product-img-wrapper {
            position: relative;
            overflow: hidden;
            padding-top: 100%;
        }

        .product-img {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .product-card:hover .product-img {
            transform: scale(1.1);
        }

        .badge-sale {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #dc3545;
            color: white;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            z-index: 2;
        }

        .btn-add-cart {
            background-color: #333;
            color: white;
            border: none;
            border-radius: 6px;
            transition: background 0.2s;
        }

        .btn-add-cart:hover {
            background-color: var(--primary-color);
            color: white;
        }

        /* --- Categories --- */
        .category-card {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            color: white;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            transition: transform 0.3s;
        }

        .category-card:hover {
            transform: scale(1.02);
            color: white;
        }

        .category-bg {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.6);
            z-index: 1;
        }

        .category-title {
            position: relative;
            z-index: 2;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 2px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        /* --- Sidebar Filters --- */
        .filter-sidebar {
            background-color: #fff;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.03);
        }

        .filter-header {
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 1.1rem;
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 10px;
        }

        .form-check-label {
            cursor: pointer;
            font-size: 0.95rem;
        }

        .price-input {
            max-width: 80px;
            text-align: center;
        }

        /* --- Pagination --- */
        .page-link {
            color: #333;
            border: none;
            margin: 0 5px;
            border-radius: 5px !important;
            font-weight: 500;
        }

        .page-link:hover,
        .page-item.active .page-link {
            background-color: var(--primary-color);
            color: white;
        }

        /* --- Breadcrumb --- */
        .breadcrumb-item a {
            text-decoration: none;
            color: var(--secondary-color);
        }

        .breadcrumb-item.active {
            color: var(--primary-color);
            font-weight: 500;
        }

        /* --- Footer Styles --- */
        .footer {
            background-color: #212529;
            color: #adb5bd;
            padding-top: 4rem;
            padding-bottom: 2rem;
            margin-top: 5rem;
        }

        .footer a {
            color: #adb5bd;
            text-decoration: none;
            transition: color 0.2s;
        }

        .footer a:hover {
            color: #fff;
        }

        /* Dropdown User Menu Style */
        .nav-item.dropdown .dropdown-toggle::after {
            margin-left: 0.5em;
        }

        .user-avatar-sm {
            width: 35px;
            height: 35px;
            background-color: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 14px;
        }
    </style>
</head>

<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index">
                <!-- ตรวจสอบว่ามีโลโก้หรือไม่ -->
                <?php if (isset($settings['site_logo']) && !empty($settings['site_logo']) && file_exists($settings['site_logo'])): ?>
                    <img src="<?php echo $settings['site_logo']; ?>" alt="<?php echo $site_name; ?>">
                <?php else: ?>
                    <i class="bi bi-bag-heart-fill me-2"></i>
                <?php endif; ?>

                <!-- แสดงชื่อร้าน -->
                <?php echo $site_name; ?>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <form class="d-flex mx-auto my-2 my-lg-0 w-50 d-none d-lg-flex" role="search" action="products" method="GET">
                    <div class="input-group">
                        <input class="form-control bg-light border-0" type="search" name="q" placeholder="ค้นหาสินค้า..." aria-label="Search">
                        <button class="btn btn-light border-0" type="submit"><i class="bi bi-search"></i></button>
                    </div>
                </form>
                <ul class="navbar-nav ms-auto align-items-center">
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($active_page) && $active_page == 'home') ? 'active' : ''; ?>" href="index">หน้าแรก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo (isset($active_page) && $active_page == 'products') ? 'active' : ''; ?>" href="products">สินค้า</a>
                    </li>
                    <!-- <li class="nav-item">
                        <a class="nav-link <?php echo (isset($active_page) && $active_page == 'categories') ? 'active' : ''; ?>" href="products">หมวดหมู่</a>
                    </li> -->
                    <li class="nav-item ms-3 me-3">
                        <a href="cart" class="cart-icon">
                            <i class="bi bi-cart3"></i>
                            <span class="badge rounded-pill bg-danger cart-badge"></span>
                        </a>
                    </li>

                    <!-- ส่วนตรวจสอบการเข้าสู่ระบบ -->
                    <?php 
                        // ต้องมั่นใจว่ามี session_start() อยู่บนสุดของไฟล์ index.php หรือไฟล์นี้
                        if (isset($_SESSION['user_id'])): 
                            // ป้องกัน Error ถ้า user_name เป็นค่าว่างหรือ null
                            $display_name = isset($_SESSION['user_name']) && !empty($_SESSION['user_name']) ? $_SESSION['user_name'] : 'User';
                    ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center gap-2" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <div class="user-avatar-sm">
                                    <?php echo mb_substr($_SESSION['user_name'], 0, 1, 'UTF-8'); ?>
                                </div>
                                <span class="d-none d-lg-block"><?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end border-0 shadow-sm rounded-3 mt-2">
                                <li><a class="dropdown-item" href="profile"><i class="bi bi-person me-2"></i>บัญชีของฉัน</a></li>
                                <li><a class="dropdown-item" href="profile#orders"><i class="bi bi-box-seam me-2"></i>ประวัติการสั่งซื้อ</a></li>

                                <!-- แสดงเมนู Admin เฉพาะแอดมิน -->
                                <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin'): ?>
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li><a class="dropdown-item text-primary fw-bold" href="backoffice/index"><i class="bi bi-speedometer2 me-2"></i>ระบบหลังบ้าน</a></li>
                                <?php endif; ?>

                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item text-danger" href="logout"><i class="bi bi-box-arrow-right me-2"></i>ออกจากระบบ</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="login" class="btn btn-outline-primary rounded-pill px-4 btn-sm">เข้าสู่ระบบ</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
            <!-- Mobile Search -->
            <form class="d-flex mt-3 w-100 d-lg-none" role="search" action="products" method="GET">
                <input class="form-control me-2" type="search" name="q" placeholder="ค้นหาสินค้า..." aria-label="Search">
                <button class="btn btn-outline-success" type="submit">ค้นหา</button>
            </form>
        </div>
    </nav>