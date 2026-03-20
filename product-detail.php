<?php
include 'config/connect.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;

// ดึงข้อมูลอีเวนต์/บัตร
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header("Location: index");
    exit();
}

// ดึงรูปภาพ Gallery (เช่น ผังที่นั่ง หรือบรรยากาศงาน)
$stmt_img = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
$stmt_img->bind_param("i", $id);
$stmt_img->execute();
$gallery = $stmt_img->get_result();

$page_title = htmlspecialchars($product['name']) . " | จองบัตร";
$active_page = "products";

include 'includes/header.php';
?>

<style>
    :root {
        --ticket-primary: #ff3e6c;
        --ticket-success: #28a745;
    }

    /* Ticket Layout */
    .event-header-box {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        overflow: hidden;
    }

    .event-info-list {
        list-style: none;
        padding: 0;
    }

    .event-info-list li {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        font-size: 1.1rem;
    }

    .event-info-list i {
        width: 40px;
        height: 40px;
        background: #fff0f3;
        color: var(--ticket-primary);
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
    }

    .price-tag-big {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 15px;
        border-left: 5px solid var(--ticket-primary);
    }

    .btn-booking {
        background: var(--ticket-primary);
        color: white;
        border: none;
        padding: 15px 30px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
        transition: 0.3s;
    }

    .btn-booking:hover {
        background: #e3355f;
        color: white;
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(255, 62, 108, 0.4);
    }

    .gallery-thumb {
        cursor: pointer;
        transition: 0.2s;
    }

    .gallery-thumb:hover {
        border-color: var(--ticket-primary) !important;
        opacity: 0.8;
    }

    /* Tab Styling */
    .nav-pills .nav-link.active {
        background-color: var(--ticket-primary) !important;
    }
</style>

<div class="bg-light py-3 border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index" class="text-decoration-none text-muted">หน้าแรก</a></li>
                <li class="breadcrumb-item"><a href="products" class="text-decoration-none text-muted">รายการอีเวนต์</a></li>
                <li class="breadcrumb-item active text-dark fw-bold" aria-current="page"><?php echo htmlspecialchars($product['name']); ?></li>
            </ol>
        </nav>
    </div>
</div>

<section class="py-5">
    <div class="container">
        <div class="event-header-box p-3 p-md-5">
            <div class="row gx-5">
                <div class="col-lg-5 mb-4 mb-lg-0">
                    <div class="position-relative">
                        <img src="<?php echo !empty($product['image']) ? $product['image'] : 'https://placehold.co/600x800?text=Poster'; ?>"
                            class="img-fluid rounded-4 shadow-sm w-100"
                            alt="Event Poster" id="mainImage">
                        
                        <?php if ($product['stock_quantity'] <= 5 && $product['stock_quantity'] > 0): ?>
                            <div class="position-absolute top-0 end-0 m-3 badge bg-danger p-2 px-3">บัตรใกล้หมด!</div>
                        <?php endif; ?>
                    </div>

                    <?php if ($gallery->num_rows > 0): ?>
                        <div class="d-flex gap-2 mt-3 overflow-auto">
                            <div onclick="changeImage('<?php echo $product['image']; ?>')" class="border rounded-3 p-1 gallery-thumb">
                                <img src="<?php echo $product['image']; ?>" width="70" height="70" class="object-fit-cover rounded">
                            </div>
                            <?php while ($img = $gallery->fetch_assoc()): ?>
                                <div onclick="changeImage('<?php echo $img['image_path']; ?>')" class="border rounded-3 p-1 gallery-thumb">
                                    <img src="<?php echo $img['image_path']; ?>" width="70" height="70" class="object-fit-cover rounded">
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="col-lg-7">
                    <div class="mb-3">
                        <span class="badge bg-outline-dark border text-dark mb-2"><?php echo htmlspecialchars($product['category_name']); ?></span>
                        <h1 class="fw-bold display-6"><?php echo htmlspecialchars($product['name']); ?></h1>
                    </div>

                    <!-- <ul class="event-info-list my-4">
                        <li><i class="bi bi-calendar-event"></i> <span>วันที่จัดงาน: <strong>25 มีนาคม 2569</strong></span></li>
                        <li><i class="bi bi-geo-alt"></i> <span>สถานที่: <strong>ศูนย์ประชุมล้านนา / BITEC</strong></span></li>
                        <li><i class="bi bi-door-open"></i> <span>ประตูเปิด: 18:00 น.</span></li>
                    </ul> -->

                    <div class="price-tag-big mb-4">
                        <div class="small text-muted mb-1">ราคาบัตร (Ticket Price)</div>
                        <?php if ($product['sale_price'] > 0): ?>
                            <span class="text-decoration-line-through text-muted fs-5">฿<?php echo number_format($product['price']); ?></span>
                            <span class="h2 fw-bold text-danger ms-2">฿<?php echo number_format($product['sale_price']); ?></span>
                        <?php else: ?>
                            <span class="h2 fw-bold text-dark">฿<?php echo number_format($product['price']); ?></span>
                        <?php endif; ?>
                    </div>

                    <form id="addToCartForm" class="bg-white rounded-4 border p-4 shadow-sm">
                        <input type="hidden" name="action" value="add">
                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                        <input type="hidden" name="ajax" value="1">

                        <div class="row align-items-end">
                            <?php if (!empty($product['colors'])): ?>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">เลือกโซนที่นั่ง / ประเภทบัตร</label>
                                <select class="form-select form-select-lg" name="color">
                                    <?php
                                    $zones = explode(',', $product['colors']);
                                    foreach ($zones as $zone):
                                    ?>
                                        <option value="<?php echo trim($zone); ?>">Zone <?php echo trim($zone); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php endif; ?>

                            <div class="col-md-4 mb-3">
                                <label class="form-label fw-bold">จำนวนบัตร</label>
                                <div class="input-group input-group-lg">
                                    <button class="btn btn-outline-secondary" type="button" onclick="this.parentNode.querySelector('input[type=number]').stepDown()">-</button>
                                    <input type="number" name="quantity" class="form-control text-center" value="1" min="1" max="10">
                                    <button class="btn btn-outline-secondary" type="button" onclick="this.parentNode.querySelector('input[type=number]').stepUp()">+</button>
                                </div>
                            </div>

                            <div class="col-12 mt-3">
                                <button type="submit" class="btn btn-booking btn-lg w-100 rounded-pill" <?php echo ($product['stock_quantity'] <= 0) ? 'disabled' : ''; ?>>
                                    <i class="bi bi-ticket-perforated me-2"></i>
                                    <?php echo ($product['stock_quantity'] > 0) ? 'ยืนยันการจองบัตร' : 'บัตรหมดแล้ว (Sold Out)'; ?>
                                </button>
                            </div>
                        </div>
                    </form>

                    <div class="mt-4 d-flex align-items-center gap-3">
                        <div class="small text-muted"><i class="bi bi-shield-check text-success me-1"></i> ชำระเงินได้หลายช่องทาง</div>
                        <div class="small text-muted"><i class="bi bi-qr-code-scan text-success me-1"></i> รับ E-Ticket ทันที</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <ul class="nav nav-tabs mb-4 border-bottom-0 justify-content-center" id="myTab" role="tablist">
            <li class="nav-item">
                <button class="nav-link active fw-bold px-4 border-0" data-bs-toggle="tab" data-bs-target="#desc">รายละเอียดงาน</button>
            </li>
            <li class="nav-item">
                <button class="nav-link fw-bold px-4 border-0" data-bs-toggle="tab" data-bs-target="#policy">เงื่อนไขการเข้างาน</button>
            </li>
        </ul>
        <div class="tab-content bg-white p-4 rounded-4 shadow-sm">
            <div class="tab-pane fade show active" id="desc">
                <?php echo nl2br($product['description']); ?>
                <hr class="my-4">
                <?php echo $product['features']; ?>
            </div>
            <div class="tab-pane fade" id="policy">
                <div class="row">
                    <div class="col-md-6">
                        <h5 class="fw-bold"><i class="bi bi-info-circle me-2"></i>ระเบียบการจอง</h5>
                        <ul class="text-muted">
                            <li>จำกัดการซื้อไม่เกิน 10 ใบต่อ 1 รายการสั่งซื้อ</li>
                            <li>หากชำระเงินไม่ทันเวลาที่กำหนด รายการจองจะถูกยกเลิกอัตโนมัติ</li>
                            <li>บัตรไม่สามารถเปลี่ยนเป็นเงินสดหรือคืนเงินได้</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h5 class="fw-bold"><i class="bi bi-exclamation-triangle me-2"></i>ข้อควรระวัง</h5>
                        <p class="text-muted">โปรดแสดง E-Ticket (QR Code) พร้อมบัตรประชาชนตัวจริง ณ จุดลงทะเบียนเพื่อรับสายรัดข้อมือเข้างาน</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    function changeImage(src) {
        document.getElementById('mainImage').src = src;
    }

    // Logic AJAX เดิมใช้ได้เลยครับ แค่เปลี่ยน Alert เล็กน้อย
    document.getElementById('addToCartForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        fetch('cart', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.status === 'success') {
                Swal.fire({
                    icon: 'success',
                    title: 'ใส่ตะกร้าเรียบร้อย',
                    text: 'กรุณาชำระเงินภายในเวลาที่กำหนดเพื่อยืนยันบัตร',
                    confirmButtonText: 'ไปที่ตะกร้าสินค้า',
                    showCancelButton: true,
                    cancelButtonText: 'จองใบอื่นเพิ่ม'
                }).then((result) => {
                    if (result.isConfirmed) { window.location.href = 'cart'; }
                });
                // อัปเดตตัวเลขตะกร้าด้านบน (ถ้ามี)
                if(document.querySelector('.cart-badge')) document.querySelector('.cart-badge').textContent = data.cart_count;
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>