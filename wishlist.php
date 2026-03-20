<?php
session_start();
include 'config/connect.php';

// --- ส่วนจัดการ Logic (Toggle) ---
if (isset($_GET['action']) && $_GET['action'] == 'toggle') {

    // ตรวจสอบ Login
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['error'] = "กรุณาเข้าสู่ระบบก่อนเพิ่มสินค้าที่ชอบ";
        header("Location: login");
        exit();
    }

    $user_id = $_SESSION['user_id'];
    $product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

    if ($product_id > 0) {
        // เช็คว่ามีอยู่แล้วไหม
        $check = $conn->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
        $check->bind_param("ii", $user_id, $product_id);
        $check->execute();
        $result = $check->get_result();

        if ($result->num_rows > 0) {
            // มีอยู่แล้ว -> ลบ (Unlike)
            $delete = $conn->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $delete->bind_param("ii", $user_id, $product_id);
            $delete->execute();
            $_SESSION['success'] = "ลบสินค้าออกจากรายการที่ชอบแล้ว";
        } else {
            // ยังไม่มี -> เพิ่ม (Like)
            $insert = $conn->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $insert->bind_param("ii", $user_id, $product_id);
            $insert->execute();
            $_SESSION['success'] = "เพิ่มสินค้าในรายการที่ชอบแล้ว";
        }
    }

    // กลับไปหน้าเดิม
    if (isset($_SERVER['HTTP_REFERER'])) {
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } else {
        header("Location: wishlist");
    }
    exit();
}
// --- จบส่วน Logic ---

// --- ส่วนแสดงผล (View) ---
$page_title = "สิ่งที่อยากได้ - Namrong Group";
$active_page = "profile";

include 'includes/header.php';
?>

<div class="bg-light py-3 border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index">หน้าแรก</a></li>
                <li class="breadcrumb-item active" aria-current="page">สิ่งที่อยากได้</li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <h2 class="fw-bold mb-4">รายการที่ชอบ</h2>

        <?php if (!isset($_SESSION['user_id'])): ?>
            <div class="text-center py-5">
                <p class="text-muted">กรุณาเข้าสู่ระบบเพื่อดูรายการที่ชอบ</p>
                <a href="login" class="btn btn-primary rounded-pill">เข้าสู่ระบบ</a>
            </div>
        <?php else: ?>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-4 g-4">
                <?php
                $user_id = $_SESSION['user_id'];
                $sql = "SELECT p.* FROM wishlist w 
                                JOIN products p ON w.product_id = p.id 
                                WHERE w.user_id = ? ORDER BY w.created_at DESC";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0):
                    while ($prod = $result->fetch_assoc()):
                        $img = !empty($prod['image']) ? $prod['image'] : "https://via.placeholder.com/300x300/f8f9fa/333333?text=No+Image";
                ?>
                        <div class="col">
                            <div class="card product-card h-100">
                                <div class="product-img-wrapper">
                                    <a href="product-detail?id=<?php echo $prod['id']; ?>">
                                        <img src="<?php echo $img; ?>" class="product-img" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                                    </a>
                                    <!-- ปุ่มลบจาก Wishlist -->
                                    <a href="wishlist?action=toggle&id=<?php echo $prod['id']; ?>" class="btn btn-sm btn-danger position-absolute top-0 end-0 m-2 rounded-circle" style="width: 32px; height: 32px; padding: 0; display: flex; align-items: center; justify-content: center;">
                                        <i class="bi bi-x-lg" style="font-size: 12px;"></i>
                                    </a>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h5 class="card-title fs-6 fw-bold">
                                        <a href="product-detail?id=<?php echo $prod['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($prod['name']); ?>
                                        </a>
                                    </h5>
                                    <div class="mt-auto">
                                        <div class="mb-2">
                                            <span class="fs-5 fw-bold text-dark">฿<?php echo number_format($prod['price']); ?></span>
                                        </div>
                                        <a href="product-detail?id=<?php echo $prod['id']; ?>" class="btn btn-add-cart w-100 py-2 btn-sm">ดูรายละเอียด</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile;
                else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="bi bi-heart display-1 text-muted opacity-25"></i>
                        <h4 class="mt-3 text-muted">ยังไม่มีรายการที่ชอบ</h4>
                        <a href="products" class="btn btn-outline-primary rounded-pill mt-3 px-4">ไปเลือกซื้อสินค้า</a>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>
    </div>
</section>

<!-- SweetAlert2 -->
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