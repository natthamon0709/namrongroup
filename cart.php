<?php
ob_start(); // 1. เริ่ม Buffer เพื่อกัน HTML หรือ Whitespace หลุดออกไป
session_start();
include 'config/connect.php';

// --- ส่วนจัดการ Logic (Add/Remove/Update) ---
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = array();
}

$action = isset($_POST['action']) ? $_POST['action'] : (isset($_GET['action']) ? $_GET['action'] : '');
$product_id = isset($_POST['product_id']) ? $_POST['product_id'] : (isset($_GET['id']) ? $_GET['id'] : 0);
$quantity = isset($_POST['quantity']) ? intval($_POST['quantity']) : 1;

// 1. เพิ่มสินค้า (Add)
if ($action == 'add' && $product_id > 0) {
    if (isset($_SESSION['cart'][$product_id])) {
        $_SESSION['cart'][$product_id] += $quantity;
    } else {
        $_SESSION['cart'][$product_id] = $quantity;
    }

    // ถ้าเป็น AJAX Request
    if (isset($_POST['ajax']) && $_POST['ajax'] == 1) {
        // 2. ล้าง Buffer ทั้งหมดก่อนส่ง JSON
        ob_end_clean();

        header('Content-Type: application/json'); // บอก Browser ว่านี่คือ JSON
        echo json_encode([
            'status' => 'success',
            'message' => 'เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว',
            'cart_count' => array_sum($_SESSION['cart'])
        ]);
        exit();
    }

    $_SESSION['success'] = "เพิ่มสินค้าลงตะกร้าเรียบร้อยแล้ว";
    header("Location: cart");
    exit();
}

// 2. ลบสินค้า (Remove)
if ($action == 'remove' && $product_id > 0) {
    if (isset($_SESSION['cart'][$product_id])) {
        unset($_SESSION['cart'][$product_id]);
    }
    $_SESSION['success'] = "ลบสินค้าออกจากตะกร้าแล้ว";
    header("Location: cart");
    exit();
}

// 3. อัปเดตจำนวน (Update)
if ($action == 'update') {
    if (isset($_POST['qty']) && is_array($_POST['qty'])) {
        foreach ($_POST['qty'] as $pid => $qty) {
            if ($qty > 0) {
                $_SESSION['cart'][$pid] = intval($qty);
            } else {
                unset($_SESSION['cart'][$pid]);
            }
        }
    }
    $_SESSION['success'] = "อัปเดตตะกร้าสินค้าแล้ว";
    header("Location: cart");
    exit();
}

// ถ้าไม่มี Action อะไร ให้แสดงผลหน้าเว็บ
// (Output Buffer จะถูกส่งออกไปปกติในส่วน View)
?>
<?php
// --- ส่วนแสดงผล (View) ---
$page_title = htmlspecialchars($settings['site_name']) . " | ตะกร้าสินค้า";
$active_page = "cart";

include 'includes/header.php';
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

    .btn-outline-primary {
        color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    .btn-outline-primary:hover {
        background-color: var(--primary-color) !important;
        color: #fff !important;
    }

    .page-item.active .page-link {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    .form-check-input:checked {
        background-color: var(--primary-color) !important;
        border-color: var(--primary-color) !important;
    }

    /* Animation สำหรับป้ายตะกร้าสินค้า */
    @keyframes cart-bounce {
        0% {
            transform: scale(1);
        }

        50% {
            transform: scale(1.4);
        }

        100% {
            transform: scale(1);
        }
    }

    .cart-animate {
        animation: cart-bounce 0.5s ease;
    }
</style>

<!-- Breadcrumb -->
<div class="bg-light py-3 border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index">หน้าแรก</a></li>
                <li class="breadcrumb-item active" aria-current="page">ตะกร้าสินค้า</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Cart Section -->
<section class="py-5">
    <div class="container">
        <h2 class="fw-bold mb-4">ตะกร้าสินค้าของคุณ</h2>

        <?php if (empty($_SESSION['cart'])): ?>
            <div class="text-center py-5">
                <i class="bi bi-cart-x display-1 text-muted opacity-25"></i>
                <h3 class="mt-3 text-muted">ไม่มีสินค้าในตะกร้า</h3>
                <a href="products" class="btn btn-primary rounded-pill mt-3 px-4">ไปเลือกซื้อสินค้า</a>
            </div>
        <?php else: ?>
            <form action="cart" method="POST">
                <input type="hidden" name="action" value="update">
                <div class="row">
                    <!-- Cart Items List -->
                    <div class="col-lg-8">
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-body p-4">
                                <div class="d-none d-md-flex justify-content-between border-bottom pb-3 mb-3 fw-bold text-muted small">
                                    <div style="width: 50%;">สินค้า</div>
                                    <div style="width: 20%;" class="text-center">ราคา</div>
                                    <div style="width: 15%;" class="text-center">จำนวน</div>
                                    <div style="width: 15%;" class="text-end">รวม</div>
                                </div>

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
                                        $img = !empty($row['image']) ? $row['image'] : "https://placehold.co/100x100?text=No+Image";
                                ?>
                                        <div class="row align-items-center mb-4 pb-4 border-bottom">
                                            <div class="col-md-6 d-flex align-items-center mb-3 mb-md-0">
                                                <div class="flex-shrink-0">
                                                    <img src="<?php echo $img; ?>" class="img-fluid rounded-3 border" style="width: 80px; height: 80px; object-fit: cover;" alt="<?php echo htmlspecialchars($row['name']); ?>">
                                                </div>
                                                <div class="flex-grow-1 ms-3">
                                                    <h6 class="mb-1 fw-bold">
                                                        <a href="product-detail?id=<?php echo $row['id']; ?>" class="text-decoration-none text-dark">
                                                            <?php echo htmlspecialchars($row['name']); ?>
                                                        </a>
                                                    </h6>
                                                    <a href="cart?action=remove&id=<?php echo $row['id']; ?>" class="text-danger text-decoration-none small mt-1" onclick="return confirm('ยืนยันการลบสินค้า?');">
                                                        <i class="bi bi-trash me-1"></i>ลบรายการ
                                                    </a>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-md-center mb-2 mb-md-0">
                                                <span class="d-md-none text-muted">ราคา: </span>
                                                <span class="fw-bold">฿<?php echo number_format($price); ?></span>
                                            </div>
                                            <div class="col-md-2 text-md-center mb-2 mb-md-0">
                                                <div class="input-group input-group-sm w-auto d-inline-flex">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="this.parentNode.querySelector('input').stepDown()">-</button>
                                                    <input type="number" name="qty[<?php echo $row['id']; ?>]" class="form-control text-center p-1" value="<?php echo $qty; ?>" min="1" style="width: 40px;">
                                                    <button class="btn btn-outline-secondary" type="button" onclick="this.parentNode.querySelector('input').stepUp()">+</button>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-md-end text-end">
                                                <span class="d-md-none text-muted">รวม: </span>
                                                <span class="fw-bold text-primary">฿<?php echo number_format($subtotal); ?></span>
                                            </div>
                                        </div>
                                <?php endwhile;
                                } ?>

                                <div class="d-flex justify-content-between align-items-center pt-2">
                                    <a href="products" class="btn btn-outline-dark rounded-pill"><i class="bi bi-arrow-left me-2"></i>ซื้อสินค้าต่อ</a>
                                    <button type="submit" class="btn btn-outline-secondary rounded-pill">อัปเดตตะกร้า</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Order Summary -->
                    <div class="col-lg-4">
                        <div class="card border-0 shadow-sm rounded-4 bg-light">
                            <div class="card-body p-4">
                                <h5 class="fw-bold mb-4">สรุปรายการสั่งซื้อ</h5>

                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">ยอดรวมสินค้า</span>
                                    <span class="fw-bold">฿<?php echo number_format($total_price); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">ค่าจัดส่ง</span>
                                    <span class="text-success">ฟรี</span>
                                </div>

                                <hr class="my-4">

                                <div class="d-flex justify-content-between mb-4">
                                    <span class="h5 fw-bold">ยอดสุทธิ</span>
                                    <span class="h4 fw-bold text-primary">฿<?php echo number_format($total_price); ?></span>
                                </div>

                                <div class="d-grid">
                                    <a href="checkout" class="btn btn-primary btn-lg rounded-pill fw-bold py-3">ดำเนินการชำระเงิน</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</section>

<?php if (isset($_SESSION['success'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: '<?php echo $_SESSION['success']; ?>',
                showConfirmButton: false,
                timer: 1500,
                toast: true,
                position: 'top-end'
            });
        });
    </script>
<?php unset($_SESSION['success']);
endif; ?>

<?php include 'includes/footer.php'; ?>
<?php ob_end_flush(); // ส่ง Output Buffer ออกไป 
?>