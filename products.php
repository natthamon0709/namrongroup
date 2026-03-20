<?php
include 'config/connect.php';
$page_title = htmlspecialchars($settings['site_name']) . " | สินค้า";
$active_page = "products";

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

<?php
// --- รับค่า Filter ---
$search = isset($_GET['q']) ? $_GET['q'] : '';
$cat_id = isset($_GET['cat']) ? $_GET['cat'] : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';
$min_price = isset($_GET['min_price']) && $_GET['min_price'] != '' ? $_GET['min_price'] : 0;
$max_price = isset($_GET['max_price']) && $_GET['max_price'] != '' ? $_GET['max_price'] : 999999;

// --- สร้าง SQL Query ---
$sql = "SELECT * FROM products WHERE 1=1 ";
$params = [];
$types = "";

// 1. ค้นหาชื่อ
if (!empty($search)) {
    $sql .= " AND name LIKE ? ";
    $search_param = "%{$search}%";
    $params[] = $search_param;
    $types .= "s";
}

// 2. กรองหมวดหมู่
if (!empty($cat_id)) {
    $sql .= " AND category_id = ? ";
    $params[] = $cat_id;
    $types .= "i";
}

// 3. กรองราคา
if (isset($_GET['min_price']) || isset($_GET['max_price'])) {
    $sql .= " AND price BETWEEN ? AND ? ";
    $params[] = $min_price;
    $params[] = $max_price;
    $types .= "dd";
}

// --- Sorting ---
if ($sort == '1') {
    $sql .= " ORDER BY price ASC";
} elseif ($sort == '2') {
    $sql .= " ORDER BY price DESC";
} else {
    $sql .= " ORDER BY id DESC"; // Default: มาใหม่
}

// Prepare & Execute
$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result_prod = $stmt->get_result();
$total_products = $result_prod->num_rows;
?>

<!-- Breadcrumb -->
<div class="bg-light py-3 border-bottom">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="index">หน้าแรก</a></li>
                <li class="breadcrumb-item active" aria-current="page">สินค้าทั้งหมด</li>
            </ol>
        </nav>
    </div>
</div>

<!-- Main Content -->
<section class="py-5">
    <div class="container">
        <div class="row">
            <!-- Sidebar Filter -->
            <div class="col-lg-3 mb-4">
                <button class="btn btn-outline-dark w-100 d-lg-none mb-3" type="button" data-bs-toggle="offcanvas" data-bs-target="#filterOffcanvas">
                    <i class="bi bi-funnel-fill me-2"></i> ตัวกรองสินค้า
                </button>

                <div class="filter-sidebar d-none d-lg-block bg-white p-3 rounded shadow-sm border">

                    <!-- Search Sidebar -->
                    <div class="mb-4 border-bottom pb-4">
                        <div class="filter-header fw-bold mb-2">ค้นหา</div>
                        <form action="products" method="GET">
                            <div class="input-group">
                                <input type="text" class="form-control form-control-sm" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="ชื่อสินค้า...">
                                <button class="btn btn-primary btn-sm" type="submit"><i class="bi bi-search"></i></button>
                            </div>
                            <?php if ($cat_id) echo '<input type="hidden" name="cat" value="' . $cat_id . '">'; ?>
                        </form>
                    </div>

                    <!-- Categories Filter -->
                    <div class="mb-4 border-bottom pb-4">
                        <div class="filter-header fw-bold mb-2">หมวดหมู่</div>
                        <div class="list-group list-group-flush small">
                            <a href="products" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo empty($cat_id) ? 'active bg-light text-primary fw-bold' : ''; ?> border-0 px-0">
                                ทั้งหมด
                                <i class="bi bi-chevron-right small text-muted"></i>
                            </a>
                            <?php
                            $cat_sql = "SELECT * FROM categories";
                            $cat_res = $conn->query($cat_sql);
                            while ($cat = $cat_res->fetch_assoc()):
                            ?>
                                <a href="products?cat=<?php echo $cat['id']; ?><?php echo $search ? '&q=' . $search : ''; ?>" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center <?php echo ($cat_id == $cat['id']) ? 'active bg-light text-primary fw-bold' : ''; ?> border-0 px-0">
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                    <i class="bi bi-chevron-right small text-muted"></i>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>

                    <!-- Price Filter -->
                    <div class="mb-4">
                        <div class="filter-header fw-bold mb-2">ช่วงราคา</div>
                        <form action="products" method="GET">
                            <?php if ($search) echo '<input type="hidden" name="q" value="' . htmlspecialchars($search) . '">'; ?>
                            <?php if ($cat_id) echo '<input type="hidden" name="cat" value="' . $cat_id . '">'; ?>
                            <?php if ($sort) echo '<input type="hidden" name="sort" value="' . $sort . '">'; ?>

                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="number" name="min_price" class="form-control form-control-sm text-center" placeholder="Min" value="<?php echo isset($_GET['min_price']) ? $_GET['min_price'] : ''; ?>">
                                <span class="text-muted">-</span>
                                <input type="number" name="max_price" class="form-control form-control-sm text-center" placeholder="Max" value="<?php echo isset($_GET['max_price']) ? $_GET['max_price'] : ''; ?>">
                            </div>
                            <button type="submit" class="btn btn-outline-primary btn-sm w-100">กรองราคา</button>
                        </form>
                    </div>

                    <!-- Reset Filter -->
                    <?php if ($search || $cat_id || isset($_GET['min_price'])): ?>
                        <div class="mt-3">
                            <a href="products" class="btn btn-light btn-sm w-100 text-danger"><i class="bi bi-x-circle me-1"></i> ล้างตัวกรอง</a>
                        </div>
                    <?php endif; ?>

                </div>
            </div>

            <!-- Product List Area -->
            <div class="col-lg-9">
                <!-- Toolbar -->
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-4 bg-white p-3 rounded shadow-sm">
                    <span class="text-muted small mb-2 mb-md-0">พบ <?php echo $total_products; ?> รายการ <?php echo $search ? "คำค้น: '<strong>$search</strong>'" : ""; ?></span>
                    <div class="d-flex align-items-center">
                        <label class="me-2 d-none d-sm-block text-nowrap small text-muted">เรียงตาม:</label>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="location = this.value;">
                            <?php
                            $qs = $_GET;
                            $qs['sort'] = '3';
                            $link_new = "products?" . http_build_query($qs);
                            $qs['sort'] = '1';
                            $link_low = "products?" . http_build_query($qs);
                            $qs['sort'] = '2';
                            $link_high = "products?" . http_build_query($qs);
                            ?>
                            <option value="<?php echo $link_new; ?>" <?php echo ($sort == '3' || $sort == '') ? 'selected' : ''; ?>>มาใหม่ล่าสุด</option>
                            <option value="<?php echo $link_low; ?>" <?php echo ($sort == '1') ? 'selected' : ''; ?>>ราคา ต่ำ > สูง</option>
                            <option value="<?php echo $link_high; ?>" <?php echo ($sort == '2') ? 'selected' : ''; ?>>ราคา สูง > ต่ำ</option>
                        </select>
                    </div>
                </div>

                <!-- Products Grid -->
                <div class="row row-cols-1 row-cols-sm-2 row-cols-lg-3 g-4">
                    <?php
                    if ($total_products > 0):
                        while ($prod = $result_prod->fetch_assoc()):
                            $img = !empty($prod['image']) ? $prod['image'] : "https://placehold.co/300x300/f8f9fa/333333?text=No+Image";
                    ?>
                            <div class="col">
                                <div class="card product-card h-100">
                                    <?php if ($prod['sale_price'] > 0):
                                        $discount = round((($prod['price'] - $prod['sale_price']) / $prod['price']) * 100);
                                    ?>
                                        <div class="badge-sale">-<?php echo $discount; ?>%</div>
                                    <?php endif; ?>

                                    <div class="product-img-wrapper">
                                        <a href="product-detail?id=<?php echo $prod['id']; ?>">
                                            <img src="<?php echo $img; ?>" class="product-img" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                                        </a>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h5 class="card-title fs-6 fw-bold">
                                            <a href="product-detail?id=<?php echo $prod['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($prod['name']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text small text-muted text-truncate"><?php echo htmlspecialchars(strip_tags($prod['description'])); ?></p>
                                        <div class="mt-auto">
                                            <div class="mb-2 d-flex justify-content-between align-items-center">
                                                <?php if ($prod['sale_price'] > 0): ?>
                                                    <div>
                                                        <span class="text-decoration-line-through text-muted small me-1">฿<?php echo number_format($prod['price']); ?></span>
                                                        <span class="fs-5 fw-bold text-danger">฿<?php echo number_format($prod['sale_price']); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="fs-5 fw-bold text-dark">฿<?php echo number_format($prod['price']); ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <!-- ปุ่มจัดการสินค้า -->
                                            <div class="d-flex gap-2">
                                                <!-- ปุ่มดูรายละเอียด -->
                                                <a href="product-detail?id=<?php echo $prod['id']; ?>" class="btn btn-outline-dark w-100 py-2 btn-sm flex-grow-1">
                                                    รายละเอียด
                                                </a>

                                                <!-- ปุ่มหยิบใส่ตะกร้า (AJAX Form) -->
                                                <?php if ($prod['stock_quantity'] > 0): ?>
                                                    <form class="add-to-cart-form flex-shrink-0" action="cart" method="POST">
                                                        <input type="hidden" name="action" value="add">
                                                        <input type="hidden" name="product_id" value="<?php echo $prod['id']; ?>">
                                                        <input type="hidden" name="quantity" value="1">
                                                        <input type="hidden" name="ajax" value="1">
                                                        <button type="submit" class="btn btn-primary h-100 btn-sm px-3" data-bs-toggle="tooltip" title="หยิบใส่ตะกร้า">
                                                            <i class="bi bi-cart-plus"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary h-100 btn-sm px-3 flex-shrink-0" disabled title="สินค้าหมด">
                                                        <i class="bi bi-x-circle"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile;
                    else: ?>
                        <div class="col-12 text-center py-5">
                            <i class="bi bi-search display-4 text-muted opacity-50 mb-3"></i>
                            <h4 class="text-muted">ไม่พบสินค้าที่คุณค้นหา</h4>
                            <p class="text-muted small">ลองเปลี่ยนคำค้นหา หรือล้างตัวกรอง</p>
                            <a href="products" class="btn btn-primary mt-2">ดูสินค้าทั้งหมด</a>
                        </div>
                    <?php endif; ?>
                </div>

            </div>
        </div>
    </div>
</section>

<!-- Offcanvas Filter for Mobile -->
<div class="offcanvas offcanvas-start" tabindex="-1" id="filterOffcanvas">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title fw-bold"><i class="bi bi-funnel-fill me-2"></i>ตัวกรองสินค้า</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <!-- Mobile Search -->
        <div class="mb-4">
            <div class="filter-header fw-bold mb-2">ค้นหา</div>
            <form action="products" method="GET">
                <div class="input-group">
                    <input type="text" class="form-control" name="q" value="<?php echo htmlspecialchars($search); ?>" placeholder="ชื่อสินค้า...">
                    <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                </div>
            </form>
        </div>

        <div class="mb-4">
            <div class="filter-header fw-bold mb-2">หมวดหมู่</div>
            <div class="list-group list-group-flush">
                <a href="products" class="list-group-item list-group-item-action border-0 px-0">ทั้งหมด</a>
                <?php
                $cat_res->data_seek(0);
                while ($cat = $cat_res->fetch_assoc()):
                ?>
                    <a href="products?cat=<?php echo $cat['id']; ?>" class="list-group-item list-group-item-action border-0 px-0 <?php echo ($cat_id == $cat['id']) ? 'text-primary fw-bold' : ''; ?>">
                        <?php echo htmlspecialchars($cat['name']); ?>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="mb-4">
            <div class="filter-header fw-bold mb-2">ช่วงราคา</div>
            <form action="products" method="GET">
                <?php if ($cat_id) echo '<input type="hidden" name="cat" value="' . $cat_id . '">'; ?>
                <div class="d-flex align-items-center gap-2 mb-2">
                    <input type="number" name="min_price" class="form-control" placeholder="Min" value="<?php echo isset($_GET['min_price']) ? $_GET['min_price'] : ''; ?>">
                    <span class="text-muted">-</span>
                    <input type="number" name="max_price" class="form-control" placeholder="Max" value="<?php echo isset($_GET['max_price']) ? $_GET['max_price'] : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary w-100">ดูผลลัพธ์</button>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript สำหรับจัดการ AJAX Add to Cart -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // เลือกทุก Form ที่มี Class .add-to-cart-form
        const forms = document.querySelectorAll('.add-to-cart-form');

        forms.forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(form);

                fetch('cart', { // ส่งไปที่ cart.php (Clean URL)
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        const contentType = response.headers.get("content-type");
                        if (contentType && contentType.indexOf("application/json") !== -1) {
                            return response.json();
                        } else {
                            console.error("Oops, we haven't got JSON!", response);
                            throw new Error("Server response is not JSON");
                        }
                    })
                    .then(data => {
                        if (data.status === 'success') {
                            // ใช้ Swal ถ้าโหลดได้ ถ้าไม่ได้ใช้ alert ปกติ
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'สำเร็จ!',
                                    text: data.message,
                                    showConfirmButton: false,
                                    timer: 1500,
                                    toast: true,
                                    position: 'top-end'
                                });
                            } else {
                                alert(data.message);
                            }

                            // ฟังก์ชันอนิเมชัน
                            const animateBadge = (element) => {
                                element.classList.remove('cart-animate');
                                void element.offsetWidth;
                                element.classList.add('cart-animate');
                            };

                            // อัปเดตตัวเลขตะกร้า
                            const cartBadge = document.querySelector('.cart-badge');
                            if (cartBadge) {
                                cartBadge.textContent = data.cart_count;
                                animateBadge(cartBadge);
                            } else {
                                const cartIcon = document.querySelector('.cart-icon');
                                if (cartIcon) {
                                    const newBadge = document.createElement('span');
                                    newBadge.className = 'badge rounded-pill bg-danger cart-badge';
                                    newBadge.textContent = data.cart_count;
                                    cartIcon.appendChild(newBadge);
                                    animateBadge(newBadge);
                                }
                            }
                        } else {
                            if (typeof Swal !== 'undefined') {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'ผิดพลาด',
                                    text: 'ไม่สามารถเพิ่มสินค้าได้'
                                });
                            } else {
                                alert('ไม่สามารถเพิ่มสินค้าได้');
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('เกิดข้อผิดพลาดในการเชื่อมต่อ');
                    });
            });
        });
    });
</script>

<?php include 'includes/footer.php'; ?>