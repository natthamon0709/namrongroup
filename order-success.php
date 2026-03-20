<?php
include 'config/connect.php';
$page_title = htmlspecialchars($settings['site_name']) . " | สั่งซื้อสำเร็จ";
$active_page = "";

include 'includes/header.php';

$order_number = isset($_GET['order_number']) ? htmlspecialchars($_GET['order_number']) : '-';
?>

<!-- เพิ่ม CSS Override เพื่อให้ใช้สีตามการตั้งค่า -->
<style>
    .text-primary {
        color: var(--primary-color) !important;
    }

    .btn-primary {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    .btn-primary:hover {
        background-color: var(--primary-color) !important;
        filter: brightness(0.9);
        border-color: var(--primary-color) !important;
    }

    .btn-outline-dark:hover {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
        color: #fff !important;
    }
</style>

<section class="py-5 my-5">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6 text-center">
                <!-- Success Icon -->
                <div class="mb-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size: 5rem;"></i>
                </div>

                <h1 class="fw-bold mb-3">ขอบคุณสำหรับคำสั่งซื้อ!</h1>
                <p class="lead text-muted mb-4">
                    เราได้รับคำสั่งซื้อของคุณเรียบร้อยแล้ว<br>
                    หมายเลขคำสั่งซื้อของคุณคือ: <span class="fw-bold text-dark fs-4 d-block mt-2"><?php echo $order_number; ?></span>
                </p>

                <!-- Info Box -->
                <div class="card bg-light border-0 rounded-4 p-4 mb-4 text-start shadow-sm">
                    <h5 class="fw-bold mb-3 text-primary"><i class="bi bi-envelope me-2"></i>สิ่งที่ต้องทำต่อไป?</h5>
                    <ul class="text-muted mb-0 ps-3">
                        <li class="mb-2">หากคุณเลือก <strong>โอนเงินผ่านธนาคาร</strong> โปรดแจ้งชำระเงินในเมนู "ประวัติการสั่งซื้อ"</li>
                        <li class="mb-2">สินค้าจะถูกจัดเตรียมและจัดส่งภายใน 1-2 วันทำการ</li>
                        <li>คุณสามารถติดตามสถานะสินค้าได้ในหน้า <a href="profile#orders" class="text-decoration-none fw-bold text-primary">ประวัติการสั่งซื้อ</a></li>
                    </ul>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2 d-sm-flex justify-content-sm-center">
                    <a href="index" class="btn btn-primary btn-lg rounded-pill px-5">กลับสู่หน้าแรก</a>
                    <a href="products" class="btn btn-outline-dark btn-lg rounded-pill px-5">ช้อปปิ้งต่อ</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>