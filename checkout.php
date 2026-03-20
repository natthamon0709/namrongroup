<?php
session_start();
include 'config/connect.php';

if (empty($_SESSION['cart'])) {
    header("Location: cart");
    exit();
}

$page_title = htmlspecialchars($settings['site_name']) . " | ข้อมูลการชำระ";
$active_page = "cart";

include 'includes/header.php';

$user = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
}
?>

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

    .form-check-input:checked {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    .badge.bg-primary {
        background-color: var(--primary-color) !important;
    }

    /* กรอบเลือกการชำระเงิน */
    .payment-option {
        border: 1px solid #dee2e6;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .payment-option:hover {
        border-color: var(--primary-color);
        background-color: #f8f9fa;
    }

    .payment-option.selected {
        border-color: var(--primary-color);
        background-color: #f0f8ff;
    }
</style>

<div class="bg-light py-3 border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index">หน้าแรก</a></li>
                <li class="breadcrumb-item"><a href="cart">ตะกร้าสินค้า</a></li>
                <li class="breadcrumb-item active" aria-current="page">ชำระเงิน</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <form action="place_order" method="POST" class="needs-validation" novalidate>
            <div class="row g-5">
                <!-- Billing Details -->
                <div class="col-lg-8">
                    <h4 class="mb-4 fw-bold">ที่อยู่สำหรับจัดส่ง</h4>

                    <?php if (!$user): ?>
                        <div class="alert alert-warning mb-4">
                            <i class="bi bi-exclamation-circle me-2"></i> เป็นสมาชิกอยู่แล้ว? <a href="login" class="alert-link">คลิกที่นี่เพื่อเข้าสู่ระบบ</a>
                        </div>
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-sm-6">
                            <label for="firstName" class="form-label">ชื่อ</label>
                            <input type="text" class="form-control" id="firstName" name="first_name" required value="<?php echo $user ? htmlspecialchars($user['first_name']) : ''; ?>">
                        </div>
                        <div class="col-sm-6">
                            <label for="lastName" class="form-label">นามสกุล</label>
                            <input type="text" class="form-control" id="lastName" name="last_name" required value="<?php echo $user ? htmlspecialchars($user['last_name']) : ''; ?>">
                        </div>
                        <div class="col-12">
                            <label for="email" class="form-label">อีเมล</label>
                            <input type="email" class="form-control" id="email" name="email" required value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>">
                        </div>
                        <div class="col-12">
                            <label for="phone" class="form-label">เบอร์โทรศัพท์</label>
                            <input type="text" class="form-control" id="phone" name="phone" required value="<?php echo $user ? htmlspecialchars($user['phone']) : ''; ?>">
                        </div>
                        <div class="col-12">
                            <label for="address" class="form-label">ที่อยู่</label>
                            <textarea class="form-control" id="address" name="address" rows="3" required><?php echo $user ? htmlspecialchars($user['address']) : ''; ?></textarea>
                        </div>
                    </div>

                    <hr class="my-4">

                    <h4 class="mb-3 fw-bold">วิธีการชำระเงิน</h4>
                    <p class="text-muted small mb-3">กรุณาเลือกวิธีการชำระเงินที่คุณสะดวก (คุณจะต้องแนบสลิปในขั้นตอนถัดไป)</p>

                    <div class="payment-methods">
                        <!-- ตัวเลือก 1: โอนเงินธนาคาร -->
                        <label class="payment-option d-flex align-items-center w-100">
                            <input id="bank_transfer" name="paymentMethod" type="radio" class="form-check-input me-3" value="bank_transfer" checked>
                            <div>
                                <h6 class="mb-0 fw-bold"><i class="bi bi-bank me-2"></i>โอนเงินผ่านธนาคาร</h6>
                                <small class="text-muted">โอนเงินเข้าบัญชีธนาคารและแนบสลิป</small>
                            </div>
                        </label>

                        <!-- ตัวเลือก 2: PromptPay QR -->
                        <?php if (!empty($settings['promptpay_id'])): ?>
                            <label class="payment-option d-flex align-items-center w-100">
                                <input id="promptpay" name="paymentMethod" type="radio" class="form-check-input me-3" value="promptpay">
                                <div>
                                    <h6 class="mb-0 fw-bold"><i class="bi bi-qr-code-scan me-2"></i>สแกนจ่าย (PromptPay)</h6>
                                    <small class="text-muted">ระบบจะสร้าง QR Code ตามยอดเงินให้สแกนทันที</small>
                                </div>
                            </label>
                        <?php endif; ?>

                        <!-- ตัวเลือก 3: เก็บเงินปลายทาง (ถ้าต้องการ) -->
                        <!-- <label class="payment-option d-flex align-items-center w-100">
                                <input id="cod" name="paymentMethod" type="radio" class="form-check-input me-3" value="cod">
                                <div>
                                    <h6 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-2"></i>เก็บเงินปลายทาง (COD)</h6>
                                    <small class="text-muted">ชำระเงินเมื่อได้รับสินค้า</small>
                                </div>
                            </label> -->
                    </div>
                </div>

                <!-- Order Summary -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 bg-light">
                        <div class="card-body p-4">
                            <h5 class="d-flex justify-content-between align-items-center mb-3">
                                <span class="text-primary fw-bold">รายการสินค้า</span>
                                <span class="badge bg-primary rounded-pill"><?php echo array_sum($_SESSION['cart']); ?></span>
                            </h5>
                            <ul class="list-group mb-3 border-0">
                                <?php
                                $total_price = 0;
                                if (!empty($_SESSION['cart'])) {
                                    $ids = implode(',', array_keys($_SESSION['cart']));
                                    $sql = "SELECT * FROM products WHERE id IN ($ids)";
                                    $result = $conn->query($sql);
                                    while ($row = $result->fetch_assoc()):
                                        $qty = $_SESSION['cart'][$row['id']];
                                        $price = ($row['sale_price'] > 0) ? $row['sale_price'] : $row['price'];
                                        $subtotal = $price * $qty;
                                        $total_price += $subtotal;
                                ?>
                                        <li class="list-group-item d-flex justify-content-between lh-sm border-0 bg-transparent px-0 pb-3 border-bottom">
                                            <div>
                                                <h6 class="my-0"><?php echo htmlspecialchars($row['name']); ?></h6>
                                                <small class="text-muted">จำนวน: <?php echo $qty; ?></small>
                                            </div>
                                            <span class="text-muted">฿<?php echo number_format($subtotal); ?></span>
                                        </li>
                                <?php endwhile;
                                } ?>
                                <li class="list-group-item d-flex justify-content-between bg-transparent border-0 px-0 pt-3">
                                    <span>ยอดรวม (บาท)</span>
                                    <strong>฿<?php echo number_format($total_price); ?></strong>
                                </li>
                            </ul>
                            <hr class="my-4">
                            <button class="w-100 btn btn-primary btn-lg rounded-pill fw-bold" type="submit">ยืนยันการสั่งซื้อ</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<script>
    // เพิ่มลูกเล่นให้กรอบเลือกการชำระเงินเปลี่ยนสีเมื่อถูกเลือก
    document.querySelectorAll('input[name="paymentMethod"]').forEach((elem) => {
        elem.addEventListener("change", function(event) {
            document.querySelectorAll('.payment-option').forEach((el) => el.classList.remove('selected'));
            event.target.closest('.payment-option').classList.add('selected');
        });
    });
    // Set initial selected state
    document.querySelector('input[name="paymentMethod"]:checked').closest('.payment-option').classList.add('selected');
</script>

<?php include 'includes/footer.php'; ?>