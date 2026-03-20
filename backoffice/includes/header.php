<?php include '/../config/connect.php'; ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="Content-Security-Policy" content="upgrade-insecure-requests">
    <title><?php echo $settings['site_name']; ?> | ผู้ดูแลระบบ</title>

    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="plugins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="dist/css/adminlte.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="shortcut icon" href="../<?php echo $settings['site_logo']; ?>" type="image/x-icon">

    <style>
        body {
            font-family: 'Kanit', sans-serif;
            background-color: #f8f9fa;
        }

        /* ปรับ Sidebar ให้ดูทันสมัย */
        .main-sidebar {
            box-shadow: 0 10px 30px rgba(0,0,0,0.1) !important;
        }

        .nav-pills .nav-link.active {
            background-color: #e91e63 !important; /* สีชมพูแดงตามสไตล์หน้าเว็บ */
            box-shadow: 0 4px 10px rgba(233, 30, 99, 0.3);
        }

        .brand-link {
            border-bottom: 1px solid #4b545c !important;
            padding: 1.2rem 1rem !important;
        }

        /* ปรับ Navbar */
        .main-header {
            border-bottom: 1px solid #eee !important;
            padding: 0.5rem 1rem;
        }

        /* Card ดีไซน์ใหม่ */
        .card {
            border: none !important;
            border-radius: 16px !important;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05) !important;
        }

        .card-header {
            background-color: transparent !important;
            border-bottom: 1px solid #f0f0f0 !important;
            padding: 1.5rem !important;
        }

        /* ปุ่มดูหน้าเว็บ */
        .btn-view-site {
            background-color: #f8f9fa;
            border-radius: 10px;
            color: #333;
            font-weight: 500;
            padding: 5px 15px;
            border: 1px solid #eee;
            transition: 0.3s;
        }

        .btn-view-site:hover {
            background-color: #eee;
            color: #000;
        }
    </style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">

        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
                <li class="nav-item d-none d-sm-inline-block ml-2">
                    <a href="../index" target="_blank" class="btn-view-site">
                        <i class="fas fa-external-link-alt mr-1"></i> ดูหน้าเว็บไซต์
                    </a>
                </li>
            </ul>

            <ul class="navbar-nav ml-auto">
                <li class="nav-item">
                    <a class="nav-link text-muted" href="../logout.php" role="button">
                        <i class="fas fa-sign-out-alt mr-1"></i> ออกจากระบบ
                    </a>
                </li>
            </ul>
        </nav>

        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="index" class="brand-link text-center">
                <span class="brand-text font-weight-bold" style="color: #ff4081;"><?php echo $settings['site_name']; ?></span>
                <span class="brand-text d-block small text-muted">ADMINISTRATION</span>
            </a>

            <div class="sidebar">
                <div class="user-panel mt-4 pb-4 mb-4 d-flex align-items-center">
                    <div class="image">
                        <img src="dist/img/user2-160x160.jpg" class="img-circle elevation-2" style="border: 2px solid #ff4081; padding: 2px;">
                    </div>
                    <div class="info">
                        <a href="#" class="d-block font-weight-bold">Administrator</a>
                        <span class="badge badge-success" style="font-size: 10px;">Online</span>
                    </div>
                </div>

                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu">
                        <li class="nav-item">
                            <a href="./" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'dashboard') ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-th-large"></i>
                                <p>แดชบอร์ด</p>
                            </a>
                        </li>

                        <li class="nav-header text-muted small font-weight-bold mt-3">MANAGEMENT</li>

                        <li class="nav-item">
                            <a href="orders" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'orders') ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-shopping-cart"></i>
                                <p>คำสั่งซื้อ <span class="badge badge-danger right">NEW</span></p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="products" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'products') ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-ticket-alt"></i>
                                <p>จัดการบัตร/สินค้า</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="categories" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'categories') ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-list-ul"></i>
                                <p>หมวดหมู่</p>
                            </a>
                        </li>

                        <li class="nav-item">
                            <a href="users" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'users') ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-user-friends"></i>
                                <p>สมาชิก</p>
                            </a>
                        </li>

                        <li class="nav-header text-muted small font-weight-bold mt-3">SYSTEM</li>

                        <li class="nav-item">
                            <a href="settings" class="nav-link <?php echo (isset($active_menu) && $active_menu == 'settings') ? 'active' : ''; ?>">
                                <i class="nav-icon fas fa-sliders-h"></i>
                                <p>ตั้งค่าเว็บไซต์</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>