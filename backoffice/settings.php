<?php
session_start();
include 'includes/session.php';

// เชื่อมต่อฐานข้อมูล
include '../config/connect.php';

// --- ส่วนบันทึกข้อมูล (เพิ่มใหม่) ---
// ตรวจสอบว่ามีการกดปุ่มบันทึก (POST) เข้ามาหรือไม่
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. จัดการการอัปโหลดโลโก้
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] == 0) {
        $target_dir = "../uploads/"; // โฟลเดอร์เก็บรูป (สร้างไว้ที่ root)

        // สร้างโฟลเดอร์ถ้ายังไม่มี
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES["site_logo"]["name"], PATHINFO_EXTENSION);
        $new_filename = "logo_" . time() . "." . $file_extension;
        $target_file = $target_dir . $new_filename;
        $path_to_save = "uploads/" . $new_filename; // Path ที่จะเก็บใน DB

        // ตรวจสอบว่าเป็นรูปภาพจริงหรือไม่
        $check = getimagesize($_FILES["site_logo"]["tmp_name"]);
        if ($check !== false) {
            if (move_uploaded_file($_FILES["site_logo"]["tmp_name"], $target_file)) {
                // บันทึก Path รูปภาพลงฐานข้อมูล
                $sql = "INSERT INTO settings (setting_key, setting_value) VALUES ('site_logo', ?) 
                           ON DUPLICATE KEY UPDATE setting_value = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ss", $path_to_save, $path_to_save);
                $stmt->execute();
            }
        }
    }

    // 2. จัดการข้อมูล Text อื่นๆ
    $keys = [
        'site_name',
        'theme_color',
        'currency',
        'contact_phone',
        'contact_email',
        'contact_address',
        'social_facebook',
        'social_line',
        'bank_name',
        'bank_acc_name',
        'bank_acc_num',
        'promptpay_id',
        'meta_title',
        'meta_description',
        'meta_keywords'
    ];

    // เตรียม SQL (ใช้ ON DUPLICATE KEY UPDATE เพื่อให้รองรับทั้งเพิ่มใหม่และแก้ไข)
    $stmt = $conn->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");

    foreach ($keys as $key) {
        if (isset($_POST[$key])) {
            $value = $_POST[$key];
            $stmt->bind_param("sss", $key, $value, $value);
            $stmt->execute();
        }
    }

    // ตั้งค่า Session เพื่อแจ้งเตือนความสำเร็จ
    $_SESSION['success'] = "บันทึกการตั้งค่าเรียบร้อยแล้ว";

    // Refresh หน้าจอ (Redirect กลับมาหน้าเดิมแบบ Clean URL)
    header("Location: settings");
    exit();
}
// --- จบส่วนบันทึกข้อมูล ---

$active_menu = "settings";
include 'includes/header.php';
?>

<!-- Content Wrapper -->
<div class="content-wrapper">
    <!-- Content Header -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">ตั้งค่าเว็บไซต์</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index">หน้าหลัก</a></li>
                        <li class="breadcrumb-item active">ตั้งค่าเว็บไซต์</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <section class="content">
        <div class="container-fluid">

            <div class="row">
                <div class="col-md-12">
                    <!-- Form Card -->
                    <div class="card card-primary card-outline">
                        <div class="card-header p-2">
                            <ul class="nav nav-pills">
                                <li class="nav-item"><a class="nav-link active" href="#general" data-toggle="tab">ข้อมูลทั่วไป</a></li>
                                <li class="nav-item"><a class="nav-link" href="#contact" data-toggle="tab">การติดต่อ & Social</a></li>
                                <li class="nav-item"><a class="nav-link" href="#payment" data-toggle="tab">การชำระเงิน</a></li>
                                <li class="nav-item"><a class="nav-link" href="#seo" data-toggle="tab">SEO & Google</a></li>
                            </ul>
                        </div><!-- /.card-header -->

                        <div class="card-body">
                            <!-- เปลี่ยน action เป็นค่าว่าง เพื่อส่งข้อมูลกลับมาที่ไฟล์นี้ -->
                            <form class="form-horizontal" action="" method="post" enctype="multipart/form-data">
                                <div class="tab-content">

                                    <!-- Tab 1: General Info -->
                                    <div class="active tab-pane" id="general">
                                        <div class="form-group row">
                                            <label for="inputSiteName" class="col-sm-2 col-form-label">ชื่อร้านค้า</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="inputSiteName" name="site_name"
                                                    value="<?php echo isset($settings['site_name']) ? $settings['site_name'] : ''; ?>"
                                                    placeholder="เช่น Namrong Group">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputLogo" class="col-sm-2 col-form-label">โลโก้ร้าน</label>
                                            <div class="col-sm-10">
                                                <div class="input-group">
                                                    <div class="custom-file">
                                                        <input type="file" class="custom-file-input" id="inputLogo" name="site_logo">
                                                        <label class="custom-file-label" for="inputLogo">เลือกไฟล์รูปภาพ...</label>
                                                    </div>
                                                </div>
                                                <!-- แสดงโลโก้ปัจจุบัน -->
                                                <?php if (isset($settings['site_logo']) && !empty($settings['site_logo'])): ?>
                                                    <div class="mt-2">
                                                        <small class="text-muted">โลโก้ปัจจุบัน:</small><br>
                                                        <!-- อ้างอิง Path กลับไปที่ root -->
                                                        <img src="../<?php echo $settings['site_logo']; ?>" class="border p-1" height="50">
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputTheme" class="col-sm-2 col-form-label">ธีมสีหลัก</label>
                                            <div class="col-sm-10">
                                                <input type="color" class="form-control form-control-color" id="inputTheme" name="theme_color"
                                                    value="<?php echo isset($settings['theme_color']) ? $settings['theme_color'] : '#0d6efd'; ?>"
                                                    title="Choose your color">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputCurrency" class="col-sm-2 col-form-label">สกุลเงิน</label>
                                            <div class="col-sm-10">
                                                <select class="form-control" name="currency">
                                                    <option value="THB" <?php echo (isset($settings['currency']) && $settings['currency'] == 'THB') ? 'selected' : ''; ?>>บาท (THB)</option>
                                                    <option value="USD" <?php echo (isset($settings['currency']) && $settings['currency'] == 'USD') ? 'selected' : ''; ?>>ดอลลาร์ (USD)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tab 2: Contact -->
                                    <div class="tab-pane" id="contact">
                                        <div class="form-group row">
                                            <label for="inputPhone" class="col-sm-2 col-form-label">เบอร์โทรศัพท์</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="inputPhone" name="contact_phone"
                                                    value="<?php echo isset($settings['contact_phone']) ? $settings['contact_phone'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputEmail" class="col-sm-2 col-form-label">อีเมลติดต่อ</label>
                                            <div class="col-sm-10">
                                                <input type="email" class="form-control" id="inputEmail" name="contact_email"
                                                    value="<?php echo isset($settings['contact_email']) ? $settings['contact_email'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputAddress" class="col-sm-2 col-form-label">ที่อยู่ร้าน</label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control" id="inputAddress" name="contact_address" rows="3"><?php echo isset($settings['contact_address']) ? $settings['contact_address'] : ''; ?></textarea>
                                            </div>
                                        </div>
                                        <hr>
                                        <h5 class="mb-3">Social Media Links</h5>
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label"><i class="fab fa-facebook text-primary"></i> Facebook</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="social_facebook"
                                                    value="<?php echo isset($settings['social_facebook']) ? $settings['social_facebook'] : ''; ?>"
                                                    placeholder="URL">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label class="col-sm-2 col-form-label"><i class="fab fa-line text-success"></i> Line ID</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" name="social_line"
                                                    value="<?php echo isset($settings['social_line']) ? $settings['social_line'] : ''; ?>"
                                                    placeholder="@yourshop">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tab 3: Payment -->
                                    <div class="tab-pane" id="payment">
                                        <div class="alert alert-info">ข้อมูลนี้จะไปแสดงในหน้า "ชำระเงิน"</div>
                                        <div class="form-group row">
                                            <label for="inputBankName" class="col-sm-2 col-form-label">ชื่อธนาคาร</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="inputBankName" name="bank_name"
                                                    value="<?php echo isset($settings['bank_name']) ? $settings['bank_name'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputAccName" class="col-sm-2 col-form-label">ชื่อบัญชี</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="inputAccName" name="bank_acc_name"
                                                    value="<?php echo isset($settings['bank_acc_name']) ? $settings['bank_acc_name'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputAccNum" class="col-sm-2 col-form-label">เลขที่บัญชี</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="inputAccNum" name="bank_acc_num"
                                                    value="<?php echo isset($settings['bank_acc_num']) ? $settings['bank_acc_num'] : ''; ?>">
                                            </div>
                                        </div>
                                        <hr>
                                        <div class="form-group row">
                                            <label for="inputPromptPay" class="col-sm-2 col-form-label">PromptPay ID</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="inputPromptPay" name="promptpay_id"
                                                    value="<?php echo isset($settings['promptpay_id']) ? $settings['promptpay_id'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Tab 4: SEO -->
                                    <div class="tab-pane" id="seo">
                                        <div class="form-group row">
                                            <label for="inputMetaTitle" class="col-sm-2 col-form-label">Meta Title</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="inputMetaTitle" name="meta_title"
                                                    value="<?php echo isset($settings['meta_title']) ? $settings['meta_title'] : ''; ?>">
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputMetaDesc" class="col-sm-2 col-form-label">Meta Description</label>
                                            <div class="col-sm-10">
                                                <textarea class="form-control" id="inputMetaDesc" name="meta_description" rows="3"><?php echo isset($settings['meta_description']) ? $settings['meta_description'] : ''; ?></textarea>
                                            </div>
                                        </div>
                                        <div class="form-group row">
                                            <label for="inputKeywords" class="col-sm-2 col-form-label">Keywords</label>
                                            <div class="col-sm-10">
                                                <input type="text" class="form-control" id="inputKeywords" name="meta_keywords"
                                                    value="<?php echo isset($settings['meta_keywords']) ? $settings['meta_keywords'] : ''; ?>">
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="form-group row mt-4">
                                    <div class="offset-sm-2 col-sm-10">
                                        <button type="submit" class="btn btn-success"><i class="fas fa-save mr-1"></i> บันทึกการตั้งค่า</button>
                                    </div>
                                </div>

                            </form>
                        </div><!-- /.card-body -->
                    </div>
                    <!-- /.card -->
                </div>
            </div>
        </div><!-- /.container-fluid -->
    </section>
</div>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_SESSION['success'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                icon: 'success',
                title: 'สำเร็จ!',
                text: '<?php echo $_SESSION['success']; ?>',
                showConfirmButton: false,
                timer: 1500
            });
        });
    </script>
<?php unset($_SESSION['success']);
endif; ?>

<?php include 'includes/footer.php'; ?>