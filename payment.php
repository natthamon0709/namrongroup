<?php
session_start();
include 'config/connect.php';

// ตรวจสอบว่ามีค่าส่งมาจริงไหม และตัดช่องว่างทิ้ง
if (!isset($_GET['order_number']) || empty(trim($_GET['order_number']))) {
    header("Location: index");
    exit();
}

$order_number = trim($_GET['order_number']);

// ดึงข้อมูลคำสั่งซื้อ (เพิ่มความชัวร์ด้วยการใช้ SQL Query ที่แสดง Error ได้)
$stmt = $conn->prepare("SELECT * FROM orders WHERE order_number = ? LIMIT 1");
if (!$stmt) {
    die("SQL Prepare Error: " . $conn->error);
}

$stmt->bind_param("s", $order_number);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    // ถ้าหาไม่เจอ ให้ลอง echo ค่าที่รับมาดูเพื่อ DEBUG
    echo "<div style='text-align:center; padding:50px;'>";
    echo "<h3>ไม่พบคำสั่งซื้อหมายเลข: " . htmlspecialchars($order_number) . "</h3>";
    echo "<p>กรุณาตรวจสอบในหน้า <a href='profile#orders'>ประวัติการสั่งซื้อ</a> ของคุณ</p>";
    echo "</div>";
    exit();
}

// --- Logic การจัดการคำสั่งซื้อ ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. แจ้งชำระเงิน (อัปโหลดสลิป)
    if (isset($_FILES['slip_image']) && $order['status'] == 'pending') {
        if ($_FILES['slip_image']['error'] == 0) {
            $target_dir = "uploads/slips/";
            if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

            $ext = pathinfo($_FILES["slip_image"]["name"], PATHINFO_EXTENSION);
            $new_name = "slip_" . $order['id'] . "_" . time() . "." . $ext;

            if (move_uploaded_file($_FILES["slip_image"]["tmp_name"], $target_dir . $new_name)) {
                $slip_path = "uploads/slips/" . $new_name;

                // *** แก้ไขตรงนี้: อัปเดตแค่รูปสลิป ไม่เปลี่ยนสถานะเป็น paid ***
                $update = $conn->prepare("UPDATE orders SET slip_image = ? WHERE id = ?");
                $update->bind_param("si", $slip_path, $order['id']);

                if ($update->execute()) {
                    // อาจจะ Redirect ไปหน้า Success พร้อมข้อความว่า "รอการตรวจสอบ"
                    $_SESSION['success'] = "แจ้งชำระเงินเรียบร้อยแล้ว กรุณารอเจ้าหน้าที่ตรวจสอบ";
                    header("Location: order-success?order_number=" . $order_number);
                    exit();
                }
            }
        }
        $error_msg = "เกิดข้อผิดพลาดในการอัปโหลดสลิป";
    }

    // 2. เปลี่ยนวิธีการชำระเงิน
    if (isset($_POST['action']) && $_POST['action'] == 'change_method' && $order['status'] == 'pending') {
        $new_method = $_POST['payment_method'];
        $update = $conn->prepare("UPDATE orders SET payment_method = ? WHERE id = ?");
        $update->bind_param("si", $new_method, $order['id']);
        $update->execute();
        header("Location: payment?order_number=" . $order_number);
        exit();
    }

    // 3. ยกเลิกคำสั่งซื้อ
    if (isset($_POST['action']) && $_POST['action'] == 'cancel_order' && $order['status'] == 'pending') {
        // คืนสต็อก
        $stmt_items = $conn->prepare("SELECT product_id, quantity FROM order_items WHERE order_id = ?");
        $stmt_items->bind_param("i", $order['id']);
        $stmt_items->execute();
        $items = $stmt_items->get_result();
        while ($item = $items->fetch_assoc()) {
            $conn->query("UPDATE products SET stock_quantity = stock_quantity + {$item['quantity']} WHERE id = {$item['product_id']}");
        }

        $update = $conn->prepare("UPDATE orders SET status = 'cancelled' WHERE id = ?");
        $update->bind_param("i", $order['id']);
        $update->execute();

        header("Location: profile#orders");
        exit();
    }
}

$page_title = htmlspecialchars($settings['site_name']) . " | ชำระเงิน";
include 'includes/header.php';
?>

<style>
    .text-primary {
        color: var(--primary-color) !important;
    }

    .btn-primary {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    .qr-container {
        border: 2px solid var(--primary-color);
        border-radius: 15px;
        padding: 20px;
        background: #fff;
        display: inline-block;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">

            <div class="text-center mb-4">
                <h2 class="fw-bold">ชำระเงิน</h2>
                <p class="text-muted">หมายเลขคำสั่งซื้อ: <span class="text-dark fw-bold"><?php echo $order_number; ?></span></p>
                <h3 class="text-primary fw-bold display-6">฿<?php echo number_format($order['total_amount'], 2); ?></h3>

                <?php if ($order['status'] != 'pending'): ?>
                    <div class="alert alert-<?php echo ($order['status'] == 'cancelled') ? 'danger' : 'success'; ?> mt-3">
                        สถานะ: <?php
                                if ($order['status'] == 'paid') echo 'ชำระเงินแล้ว (รอจัดส่ง)';
                                elseif ($order['status'] == 'shipped') echo 'จัดส่งแล้ว';
                                elseif ($order['status'] == 'cancelled') echo 'ยกเลิก';
                                else echo ucfirst($order['status']);
                                ?>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($order['status'] == 'pending'): ?>
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body p-4 p-md-5">

                        <!-- ถ้าแนบสลิปแล้ว ให้แสดงสถานะรอตรวจสอบ -->
                        <?php if (!empty($order['slip_image'])): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-clock-history text-warning display-1 mb-3"></i>
                                <h4 class="fw-bold">รอการตรวจสอบ</h4>
                                <p class="text-muted">คุณได้แนบหลักฐานการโอนเงินแล้ว<br>กรุณารอเจ้าหน้าที่ตรวจสอบความถูกต้อง</p>
                                <a href="profile#orders" class="btn btn-outline-primary rounded-pill px-4 mt-2">ดูประวัติการสั่งซื้อ</a>
                            </div>
                        <?php else: ?>
                            <!-- ส่วนสลับวิธีการชำระเงิน -->
                            <div class="d-flex justify-content-end mb-3">
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-arrow-repeat me-1"></i> เปลี่ยนวิธีชำระเงิน
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form action="" method="POST">
                                                <input type="hidden" name="action" value="change_method">
                                                <input type="hidden" name="payment_method" value="promptpay">
                                                <button class="dropdown-item" type="submit">PromptPay QR</button>
                                            </form>
                                        </li>
                                        <li>
                                            <form action="" method="POST">
                                                <input type="hidden" name="action" value="change_method">
                                                <input type="hidden" name="payment_method" value="bank_transfer">
                                                <button class="dropdown-item" type="submit">โอนเงินธนาคาร</button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- Payment Info (QR / Bank) -->
                            <?php if ($order['payment_method'] == 'promptpay'): ?>
                                <div class="text-center mb-4">
                                    <div class="alert alert-info border-0 d-inline-block">
                                        <i class="bi bi-qr-code-scan me-2"></i> สแกน QR Code เพื่อชำระเงิน
                                    </div>
                                    <div class="mt-3">
                                        <div class="qr-container shadow-sm">
                                            <img src="https://promptpay.io/<?php echo $settings['promptpay_id']; ?>/<?php echo $order['total_amount']; ?>.png" alt="PromptPay QR" class="img-fluid" style="max-width: 250px;">
                                            <div class="mt-2 text-center">
                                                <img src="https://upload.wikimedia.org/wikipedia/commons/c/c5/PromptPay-logo.png" height="30" alt="PromptPay">
                                            </div>
                                        </div>
                                        <p class="mt-3 text-muted small">ชื่อบัญชี: ริตา กุลมงคล</p>
                                    </div>
                                </div>
                            <?php elseif ($order['payment_method'] == 'bank_transfer'): ?>
                                <div class="text-center mb-4">
                                    <div class="alert alert-light border">
                                        <h5 class="fw-bold mb-3"><i class="bi bi-bank me-2"></i>โอนเงินผ่านธนาคาร</h5>
                                        <p class="mb-1 text-muted">ธนาคาร</p>
                                        <p class="fw-bold"><?php echo htmlspecialchars($settings['bank_name']); ?></p>
                                        <hr class="my-2">
                                        <p class="mb-1 text-muted">เลขที่บัญชี</p>
                                        <h4 class="text-primary fw-bold mb-2"><?php echo htmlspecialchars($settings['bank_acc_num']); ?></h4>
                                        <p class="mb-0 text-muted">ชื่อบัญชี: <?php echo htmlspecialchars($settings['bank_acc_name']); ?></p>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <hr>

                            <!-- ฟอร์มแนบสลิป -->
                            <div class="mt-4">
                                <h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2"></i>แนบหลักฐานการโอนเงิน</h5>
                                <?php if (isset($error_msg)): ?>
                                    <div class="alert alert-danger"><?php echo $error_msg; ?></div>
                                <?php endif; ?>

                                <form action="" method="POST" enctype="multipart/form-data">
                                    <div class="mb-3">
                                        <label for="slipImage" class="form-label text-muted">อัปโหลดรูปภาพสลิป (jpg, png)</label>
                                        <input type="file" class="form-control" id="slipImage" name="slip_image" accept="image/*" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary btn-lg rounded-pill fw-bold">แจ้งชำระเงิน</button>
                                    </div>
                                </form>
                            </div>

                            <!-- ปุ่มยกเลิก -->
                            <div class="mt-4 text-center">
                                <form action="" method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะยกเลิกคำสั่งซื้อนี้?');">
                                    <input type="hidden" name="action" value="cancel_order">
                                    <button type="submit" class="btn btn-link text-danger text-decoration-none small">ยกเลิกคำสั่งซื้อ</button>
                                </form>
                            </div>
                        <?php endif; ?>

                    </div>
                </div>
            <?php else: ?>
                <div class="text-center mt-4">
                    <a href="index" class="btn btn-outline-primary rounded-pill">กลับหน้าแรก</a>
                </div>
            <?php endif; ?>

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>