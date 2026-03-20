<?php
session_start();
include 'includes/session.php';

include '../config/connect.php';

// --- ส่วนบันทึกข้อมูล (Save Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $features = $_POST['features'];         // ข้อมูลจาก Summernote
    $specifications = $_POST['specifications']; // ข้อมูลจาก Summernote
    $colors = $_POST['colors'];
    $price = $_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? $_POST['sale_price'] : NULL;
    $stock_quantity = $_POST['stock_quantity'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;

    // 1. จัดการข้อมูลหลัก (Insert / Update)
    if ($id) {
        // Update
        $sql = "UPDATE products SET name=?, category_id=?, description=?, features=?, specifications=?, colors=?, price=?, sale_price=?, stock_quantity=?, is_featured=?, is_bestseller=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssddiiii", $name, $category_id, $description, $features, $specifications, $colors, $price, $sale_price, $stock_quantity, $is_featured, $is_bestseller, $id);
        $stmt->execute();
        $product_id = $id;
    } else {
        // Insert
        $sql = "INSERT INTO products (name, category_id, description, features, specifications, colors, price, sale_price, stock_quantity, is_featured, is_bestseller) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssddiii", $name, $category_id, $description, $features, $specifications, $colors, $price, $sale_price, $stock_quantity, $is_featured, $is_bestseller);
        $stmt->execute();
        $product_id = $conn->insert_id;
    }

    // 2. อัปเดตรูปภาพหลัก (ถ้ามี)
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/products/"; // Path อ้างอิงจาก backoffice
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $new_name = "prod_" . time() . "." . $ext;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_name)) {
            $image_path = "uploads/products/" . $new_name; // Path ที่เก็บใน DB
            $stmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
            $stmt->bind_param("si", $image_path, $product_id);
            $stmt->execute();
        }
    }

    // 3. เพิ่มรูปภาพ Gallery (ถ้ามี)
    if (isset($_FILES['gallery'])) {
        $target_dir = "../uploads/products/gallery/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $total_files = count($_FILES['gallery']['name']);
        for ($i = 0; $i < $total_files; $i++) {
            if ($_FILES['gallery']['error'][$i] == 0) {
                $ext = pathinfo($_FILES['gallery']['name'][$i], PATHINFO_EXTENSION);
                $new_name = "gallery_" . $product_id . "_" . time() . "_" . $i . "." . $ext;

                if (move_uploaded_file($_FILES['gallery']['tmp_name'][$i], $target_dir . $new_name)) {
                    $gal_path = "uploads/products/gallery/" . $new_name;
                    $stmt = $conn->prepare("INSERT INTO product_images (product_id, image_path) VALUES (?, ?)");
                    $stmt->bind_param("is", $product_id, $gal_path);
                    $stmt->execute();
                }
            }
        }
    }

    // 4. ลบรูป Gallery ที่ถูกเลือก
    if (isset($_POST['delete_gallery']) && is_array($_POST['delete_gallery'])) {
        foreach ($_POST['delete_gallery'] as $del_id) {
            $stmt = $conn->prepare("SELECT image_path FROM product_images WHERE id = ?");
            $stmt->bind_param("i", $del_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (file_exists("../" . $row['image_path'])) {
                    unlink("../" . $row['image_path']);
                }
            }
            $stmt = $conn->prepare("DELETE FROM product_images WHERE id = ?");
            $stmt->bind_param("i", $del_id);
            $stmt->execute();
        }
    }

    $_SESSION['success'] = "บันทึกข้อมูลสินค้าเรียบร้อยแล้ว";
    header("Location: products");
    exit();
}
// --- จบส่วนบันทึกข้อมูล ---

$active_menu = "products";

// เตรียมข้อมูลสำหรับแสดงผลฟอร์ม
$id = isset($_GET['id']) ? $_GET['id'] : null;
$product = null;
$gallery_images = [];
$title = "เพิ่มสินค้าใหม่";

if ($id) {
    $title = "แก้ไขสินค้า";
    // ดึงข้อมูลสินค้า
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();

    // ดึงรูปภาพ Gallery
    $stmt_img = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $stmt_img->bind_param("i", $id);
    $stmt_img->execute();
    $gallery_result = $stmt_img->get_result();
    while ($row = $gallery_result->fetch_assoc()) {
        $gallery_images[] = $row;
    }
}

include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0"><?php echo $title; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="products">สินค้า</a></li>
                        <li class="breadcrumb-item active"><?php echo $title; ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <!-- Form เริ่มต้น -->
            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $id; ?>">

                <div class="row">
                    <!-- Left Column -->
                    <div class="col-md-8">
                        <div class="card card-primary">
                            <div class="card-header">
                                <h3 class="card-title">ข้อมูลหลัก</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>ชื่อสินค้า <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" required
                                        value="<?php echo $product ? htmlspecialchars($product['name']) : ''; ?>">
                                </div>

                                <div class="form-group">
                                    <label>รายละเอียดสินค้า (Short Description)</label>
                                    <textarea class="form-control" name="description" rows="3"><?php echo $product ? htmlspecialchars($product['description']) : ''; ?></textarea>
                                </div>

                                <!-- Summernote Fields -->
                                <div class="form-group">
                                    <label>คุณสมบัติเด่น (Features)</label>
                                    <textarea class="form-control summernote" name="features"><?php echo $product ? htmlspecialchars($product['features']) : ''; ?></textarea>
                                    <small class="text-muted">ใช้สำหรับใส่ Bullet points หรือรายการจุดเด่น</small>
                                </div>

                                <div class="form-group">
                                    <label>ข้อมูลจำเพาะ (Specifications)</label>
                                    <textarea class="form-control summernote" name="specifications"><?php echo $product ? htmlspecialchars($product['specifications']) : ''; ?></textarea>
                                    <small class="text-muted">ใช้สำหรับใส่ตารางสเปคสินค้า</small>
                                </div>

                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>ราคาปกติ <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" class="form-control" name="price" required
                                                value="<?php echo $product ? $product['price'] : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>ราคาลด</label>
                                            <input type="number" step="0.01" class="form-control" name="sale_price"
                                                value="<?php echo $product ? $product['sale_price'] : ''; ?>">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>จำนวนสต็อก <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="stock_quantity" required
                                                value="<?php echo $product ? $product['stock_quantity'] : '0'; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column -->
                    <div class="col-md-4">
                        <div class="card card-info">
                            <div class="card-header">
                                <h3 class="card-title">การตั้งค่าเพิ่มเติม</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>หมวดหมู่ <span class="text-danger">*</span></label>
                                    <select class="form-control" name="category_id" required>
                                        <option value="">-- เลือกหมวดหมู่ --</option>
                                        <?php
                                        $cat_sql = "SELECT * FROM categories ORDER BY name ASC";
                                        $cat_result = $conn->query($cat_sql);
                                        while ($cat = $cat_result->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $cat['id']; ?>"
                                                <?php echo ($product && $product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label>ตัวเลือกสี (Colors)</label>
                                    <input type="text" class="form-control" name="colors" placeholder="เช่น ดำ, ขาว, เงิน"
                                        value="<?php echo $product ? htmlspecialchars($product['colors']) : ''; ?>">
                                    <small class="text-muted">คั่นด้วยเครื่องหมายจุลภาค (,)</small>
                                </div>

                                <div class="form-group">
                                    <label>รูปภาพหลัก</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="productImage" name="image" accept="image/*">
                                        <label class="custom-file-label" for="productImage">เลือกไฟล์...</label>
                                    </div>
                                    <?php if ($product && !empty($product['image'])): ?>
                                        <div class="mt-2 text-center">
                                            <img src="../<?php echo $product['image']; ?>" class="img-fluid border rounded" style="max-height: 150px;">
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label>รูปภาพเพิ่มเติม (Gallery)</label>
                                    <div class="custom-file">
                                        <input type="file" class="custom-file-input" id="galleryImage" name="gallery[]" accept="image/*" multiple>
                                        <label class="custom-file-label" for="galleryImage">เลือกหลายไฟล์ได้...</label>
                                    </div>

                                    <?php if (!empty($gallery_images)): ?>
                                        <div class="row mt-2">
                                            <?php foreach ($gallery_images as $img): ?>
                                                <div class="col-4 mb-2 text-center relative">
                                                    <img src="../<?php echo $img['image_path']; ?>" class="img-thumbnail" style="height: 60px; width: 60px; object-fit: cover;">
                                                    <div class="custom-control custom-checkbox mt-1">
                                                        <input type="checkbox" class="custom-control-input" id="del_img_<?php echo $img['id']; ?>" name="delete_gallery[]" value="<?php echo $img['id']; ?>">
                                                        <label class="custom-control-label text-danger small" for="del_img_<?php echo $img['id']; ?>">ลบ</label>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label>สถานะ</label>
                                    <div class="custom-control custom-checkbox">
                                        <input class="custom-control-input" type="checkbox" id="is_featured" name="is_featured" value="1"
                                            <?php echo ($product && $product['is_featured']) ? 'checked' : ''; ?>>
                                        <label for="is_featured" class="custom-control-label">สินค้าแนะนำ</label>
                                    </div>
                                    <div class="custom-control custom-checkbox mt-2">
                                        <input class="custom-control-input" type="checkbox" id="is_bestseller" name="is_bestseller" value="1"
                                            <?php echo ($product && $product['is_bestseller']) ? 'checked' : ''; ?>>
                                        <label for="is_bestseller" class="custom-control-label">สินค้าขายดี</label>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <button type="submit" class="btn btn-primary w-100"><i class="fas fa-save mr-1"></i> บันทึกข้อมูล</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>

<!-- Summernote Script -->
<script src="plugins/summernote/summernote-bs4.min.js"></script>
<script>
    $(function() {
        // ใช้งาน Summernote กับคลาส .summernote
        $('.summernote').summernote({
            height: 200,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture']],
                ['view', ['fullscreen', 'codeview']]
            ]
        });

        // แสดงชื่อไฟล์เมื่อเลือกรูป
        $(".custom-file-input").on("change", function() {
            // สำหรับ input multiple (Gallery)
            if (this.files.length > 1) {
                $(this).siblings(".custom-file-label").addClass("selected").html(this.files.length + " ไฟล์ถูกเลือก");
            } else {
                var fileName = $(this).val().split("\\").pop();
                $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
            }
        });
    })
</script>