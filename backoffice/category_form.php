<?php
session_start();
include 'includes/session.php';

include '../config/connect.php';

// --- ส่วนบันทึกข้อมูล (Save Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $image_path = "";

    // Upload Image
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target_dir = "../uploads/categories/"; // Path อ้างอิงจาก backoffice
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);

        $ext = pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION);
        $new_name = "cat_" . time() . "." . $ext;

        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_dir . $new_name)) {
            $image_path = "uploads/categories/" . $new_name; // Path ที่เก็บใน DB
        }
    }

    if ($id) {
        // Update
        $sql = "UPDATE categories SET name=?";
        if (!empty($image_path)) $sql .= ", image=?";
        $sql .= " WHERE id=?";

        $stmt = $conn->prepare($sql);
        if (!empty($image_path)) {
            $stmt->bind_param("ssi", $name, $image_path, $id);
        } else {
            $stmt->bind_param("si", $name, $id);
        }
    } else {
        // Insert
        $sql = "INSERT INTO categories (name, image) VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $name, $image_path);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "บันทึกข้อมูลหมวดหมู่เรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
    }
    // echo $_FILES['image']['error']; exit();

    header("Location: categories");
    exit();
}
// --- จบส่วนบันทึกข้อมูล ---

$active_menu = "categories";

$id = isset($_GET['id']) ? $_GET['id'] : null;
$category = null;
$title = "เพิ่มหมวดหมู่";

if ($id) {
    $title = "แก้ไขหมวดหมู่";
    $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $category = $stmt->get_result()->fetch_assoc();
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
                        <li class="breadcrumb-item"><a href="categories">หมวดหมู่</a></li>
                        <li class="breadcrumb-item active"><?php echo $title; ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card card-info">
                <div class="card-header">
                    <h3 class="card-title">ข้อมูลหมวดหมู่</h3>
                </div>
                <form action="" method="post" enctype="multipart/form-data">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <div class="card-body">
                        <div class="form-group">
                            <label>ชื่อหมวดหมู่ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" required
                                value="<?php echo $category ? htmlspecialchars($category['name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label>รูปภาพปกหมวดหมู่</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input" id="catImage" name="image" accept="image/*">
                                    <label class="custom-file-label" for="catImage">เลือกไฟล์...</label>
                                </div>
                            </div>
                            <?php if ($category && !empty($category['image'])): ?>
                                <div class="mt-2">
                                    <img src="../<?php echo $category['image']; ?>" height="100" class="border rounded">
                                    <p class="text-muted small mt-1">รูปปัจจุบัน</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-info"><i class="fas fa-save mr-1"></i> บันทึกข้อมูล</button>
                        <a href="categories" class="btn btn-default float-right">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    $(function() {
        // แสดงชื่อไฟล์เมื่อเลือกรูป
        $(".custom-file-input").on("change", function() {
            var fileName = $(this).val().split("\\").pop();
            $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
        });
    })
</script>