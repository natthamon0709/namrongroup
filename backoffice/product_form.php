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
    $features = $_POST['features'];
    $specifications = $_POST['specifications'];
    $colors = $_POST['colors'];
    $price = $_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? $_POST['sale_price'] : NULL;
    $stock_quantity = $_POST['stock_quantity'];
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    $is_bestseller = isset($_POST['is_bestseller']) ? 1 : 0;

    if ($id) {
        $sql = "UPDATE products SET name=?, category_id=?, description=?, features=?, specifications=?, colors=?, price=?, sale_price=?, stock_quantity=?, is_featured=?, is_bestseller=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssddiiii", $name, $category_id, $description, $features, $specifications, $colors, $price, $sale_price, $stock_quantity, $is_featured, $is_bestseller, $id);
        $stmt->execute();
        $product_id = $id;
    } else {
        $sql = "INSERT INTO products (name, category_id, description, features, specifications, colors, price, sale_price, stock_quantity, is_featured, is_bestseller) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sissssddiii", $name, $category_id, $description, $features, $specifications, $colors, $price, $sale_price, $stock_quantity, $is_featured, $is_bestseller);
        $stmt->execute();
        $product_id = $conn->insert_id;
    }

    // 2. อัปเดตรูปภาพหลัก
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/products/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $new_name = "prod_" . time() . "." . $ext;
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_name)) {
            $image_path = "uploads/products/" . $new_name;
            $stmt = $conn->prepare("UPDATE products SET image = ? WHERE id = ?");
            $stmt->bind_param("si", $image_path, $product_id);
            $stmt->execute();
        }
    }

    // 3. Gallery
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

    // 4. ลบรูป
    if (isset($_POST['delete_gallery']) && is_array($_POST['delete_gallery'])) {
        foreach ($_POST['delete_gallery'] as $del_id) {
            $stmt = $conn->prepare("SELECT image_path FROM product_images WHERE id = ?");
            $stmt->bind_param("i", $del_id);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                if (file_exists("../" . $row['image_path'])) unlink("../" . $row['image_path']);
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

$id = isset($_GET['id']) ? $_GET['id'] : null;
$product = null;
$gallery_images = [];
$title = "เพิ่มสินค้าใหม่";

if ($id) {
    $title = "แก้ไขสินค้า";
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $product = $stmt->get_result()->fetch_assoc();
    $stmt_img = $conn->prepare("SELECT * FROM product_images WHERE product_id = ?");
    $stmt_img->bind_param("i", $id);
    $stmt_img->execute();
    $gallery_result = $stmt_img->get_result();
    while ($row = $gallery_result->fetch_assoc()) $gallery_images[] = $row;
}

include 'includes/header.php';
?>

<div class="content-wrapper py-3">
    <section class="content">
        <div class="container-fluid">
            <div class="row mb-3 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold text-dark"><i class="fas fa-box-open mr-2"></i><?php echo $title; ?></h1>
                </div>
                <div class="col-sm-6 text-right">
                    <a href="products" class="btn btn-secondary btn-sm shadow-sm"><i class="fas fa-arrow-left mr-1"></i> กลับหน้าหลัก</a>
                </div>
            </div>

            <form action="" method="post" enctype="multipart/form-data">
                <input type="hidden" name="id" value="<?php echo $id; ?>">
                
                <div class="row">
                    <div class="col-lg-8">
                        <div class="card card-outline card-primary shadow-sm">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold">ข้อมูลรายละเอียดสินค้า</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>ชื่อสินค้า <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control form-control-lg" name="name" required
                                        value="<?php echo $product ? htmlspecialchars($product['name']) : ''; ?>" placeholder="ระบุชื่อสินค้า">
                                </div>

                                <div class="form-group">
                                    <label>คำอธิบายย่อ (Short Description)</label>
                                    <textarea class="form-control" name="description" rows="2" placeholder="แสดงในหน้ารวมสินค้า..."><?php echo $product ? htmlspecialchars($product['description']) : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>คุณสมบัติเด่น (Features)</label>
                                    <textarea class="form-control" name="features"><?php echo $product ? $product['features'] : ''; ?></textarea>
                                </div>

                                <div class="form-group">
                                    <label>ข้อมูลจำเพาะ (Specifications)</label>
                                    <textarea class="form-control" name="specifications"><?php echo $product ? $product['specifications'] : ''; ?></textarea>
                                </div>

                                <hr>
                                <div class="row">
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>ราคาปกติ (บาท) <span class="text-danger">*</span></label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control font-weight-bold text-primary" name="price" required
                                                    value="<?php echo $product ? $product['price'] : ''; ?>">
                                                <div class="input-group-append"><span class="input-group-text">฿</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>ราคาลด (ถ้ามี)</label>
                                            <div class="input-group">
                                                <input type="number" step="0.01" class="form-control text-danger" name="sale_price"
                                                    value="<?php echo $product ? $product['sale_price'] : ''; ?>">
                                                <div class="input-group-append"><span class="input-group-text">฿</span></div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group">
                                            <label>คงเหลือในสต็อก <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control" name="stock_quantity" required
                                                value="<?php echo $product ? $product['stock_quantity'] : '0'; ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card card-outline card-info shadow-sm">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold">หมวดหมู่และสถานะ</h3>
                            </div>
                            <div class="card-body">
                                <div class="form-group">
                                    <label>หมวดหมู่สินค้า</label>
                                    <select class="form-control select2" name="category_id" required>
                                        <option value="">-- เลือกหมวดหมู่ --</option>
                                        <?php
                                        $cat_sql = "SELECT * FROM categories ORDER BY name ASC";
                                        $cat_result = $conn->query($cat_sql);
                                        while ($cat = $cat_result->fetch_assoc()):
                                        ?>
                                            <option value="<?php echo $cat['id']; ?>" <?php echo ($product && $product['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($cat['name']); ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label>ตัวเลือกสี</label>
                                    <input type="text" class="form-control" name="colors" placeholder="แดง, เขียว, น้ำเงิน"
                                        value="<?php echo $product ? htmlspecialchars($product['colors']) : ''; ?>">
                                </div>
                                <div class="bg-light p-3 rounded border">
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="is_featured" name="is_featured" value="1" <?php echo ($product && $product['is_featured']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="is_featured">สินค้าแนะนำ (Featured)</label>
                                    </div>
                                    <div class="custom-control custom-switch mt-2">
                                        <input type="checkbox" class="custom-control-input" id="is_bestseller" name="is_bestseller" value="1" <?php echo ($product && $product['is_bestseller']) ? 'checked' : ''; ?>>
                                        <label class="custom-control-label" for="is_bestseller">สินค้าขายดี (Bestseller)</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card card-outline card-warning shadow-sm">
                            <div class="card-header">
                                <h3 class="card-title font-weight-bold">จัดการรูปภาพ</h3>
                            </div>
                            <div class="card-body">
                                <label>รูปภาพหลัก</label>
                                <div class="custom-file mb-3">
                                    <input type="file" class="custom-file-input" id="image" name="image" accept="image/*">
                                    <label class="custom-file-label text-truncate" for="image">เลือกรูปหน้าปก...</label>
                                </div>
                                <?php if ($product && !empty($product['image'])): ?>
                                    <div class="text-center mb-4 p-2 border rounded bg-white">
                                        <img src="../<?php echo $product['image']; ?>" class="img-fluid" style="max-height: 180px; object-fit: contain;">
                                    </div>
                                <?php endif; ?>

                                <hr>
                                <label>รูปภาพเพิ่มเติม (Gallery)</label>
                                <div class="custom-file mb-3">
                                    <input type="file" class="custom-file-input" id="gallery" name="gallery[]" accept="image/*" multiple>
                                    <label class="custom-file-label text-truncate" for="gallery">เลือกไฟล์ภาพอื่นๆ...</label>
                                </div>

                                <?php if (!empty($gallery_images)): ?>
                                    <div class="row no-gutters border rounded p-2 bg-light">
                                        <?php foreach ($gallery_images as $img): ?>
                                            <div class="col-4 p-1">
                                                <div class="position-relative border rounded bg-white overflow-hidden" style="height: 80px;">
                                                    <img src="../<?php echo $img['image_path']; ?>" class="w-100 h-100" style="object-fit: cover;">
                                                    <div class="position-absolute" style="top: 2px; right: 5px;">
                                                        <div class="custom-control custom-checkbox bg-white px-1 rounded shadow-sm border border-danger">
                                                            <input type="checkbox" class="custom-control-input" id="del_<?php echo $img['id']; ?>" name="delete_gallery[]" value="<?php echo $img['id']; ?>">
                                                            <label class="custom-control-label text-danger font-weight-bold" for="del_<?php echo $img['id']; ?>"></label>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                        <div class="col-12 text-center mt-2">
                                            <small class="text-danger font-italic">* ติ๊กถูกที่รูปแล้วกดบันทึกเพื่อลบ</small>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer px-0 pb-0">
                                <button type="submit" class="btn btn-primary btn-lg w-100 shadow"><i class="fas fa-save mr-2"></i> บันทึกข้อมูลสินค้า</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>
<link rel="stylesheet" href="/backoffice/plugins/summernote/summernote-bs4.min.css">
<script src="/backoffice/plugins/summernote/summernote-bs4.min.js"></script>
<script>
    $(document).ready(function() {
        $('.summernote').summernote({
            height: 250,
            placeholder: 'พิมพ์เนื้อหาที่นี่...',
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

        // แสดงชื่อไฟล์ที่เลือกในช่อง Custom File
        $('.custom-file-input').on('change', function() {
            let fileName = $(this).val().split('\\').pop();
            if (this.files.length > 1) {
                $(this).next('.custom-file-label').html(this.files.length + ' ไฟล์ถูกเลือก');
            } else {
                $(this).next('.custom-file-label').addClass("selected").html(fileName);
            }
        });
    });
</script>