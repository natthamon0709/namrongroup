<?php
session_start();
include 'includes/session.php';

include '../config/connect.php';

// --- ส่วนบันทึกข้อมูล (Save Logic) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];
    $email = $_POST['email'];
    $first_name = $_POST['first_name'];
    $last_name = $_POST['last_name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    $role = $_POST['role'];
    $password = $_POST['password'];

    // ตรวจสอบ Email ซ้ำ
    $checkSql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmtCheck = $conn->prepare($checkSql);
    // ถ้าเป็นการเพิ่มใหม่ id จะเป็นค่าว่าง ให้ใส่ 0 แทนเพื่อไม่ให้ error
    $checkId = $id ? $id : 0;
    $stmtCheck->bind_param("si", $email, $checkId);
    $stmtCheck->execute();
    if ($stmtCheck->get_result()->num_rows > 0) {
        $_SESSION['error'] = "อีเมลนี้ถูกใช้งานแล้ว";
    } else {
        if ($id) {
            // --- Update ---
            if (!empty($password)) {
                // ถ้ามีการกรอกรหัสผ่านใหม่ ให้ Hash และอัปเดตด้วย
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $sql = "UPDATE users SET email=?, password=?, first_name=?, last_name=?, phone=?, address=?, role=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("sssssssi", $email, $hashed_password, $first_name, $last_name, $phone, $address, $role, $id);
            } else {
                // ถ้าไม่กรอกรหัสผ่านใหม่ ให้อัปเดตข้อมูลอื่นโดยคงรหัสเดิมไว้
                $sql = "UPDATE users SET email=?, first_name=?, last_name=?, phone=?, address=?, role=? WHERE id=?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssssssi", $email, $first_name, $last_name, $phone, $address, $role, $id);
            }
        } else {
            // --- Insert ---
            // บังคับกรอกรหัสผ่านสำหรับการเพิ่มใหม่
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (email, password, first_name, last_name, phone, address, role) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssss", $email, $hashed_password, $first_name, $last_name, $phone, $address, $role);
        }

        if ($stmt->execute()) {
            $_SESSION['success'] = "บันทึกข้อมูลสมาชิกเรียบร้อยแล้ว";
            header("Location: users");
            exit();
        } else {
            $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
        }
    }
}
// --- จบส่วนบันทึกข้อมูล ---

$active_menu = "users";
$id = isset($_GET['id']) ? $_GET['id'] : null;
$user = null;
$title = "เพิ่มสมาชิกใหม่";

if ($id) {
    $title = "แก้ไขสมาชิก";
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();
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
                        <li class="breadcrumb-item"><a href="users">สมาชิก</a></li>
                        <li class="breadcrumb-item active"><?php echo $title; ?></li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-exclamation-circle mr-1"></i> <?php echo $_SESSION['error']; ?>
                </div>
            <?php unset($_SESSION['error']);
            endif; ?>

            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">ข้อมูลสมาชิก</h3>
                </div>

                <form action="" method="post">
                    <input type="hidden" name="id" value="<?php echo $id; ?>">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>ชื่อจริง <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="first_name" required
                                        value="<?php echo $user ? htmlspecialchars($user['first_name']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>นามสกุล <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="last_name" required
                                        value="<?php echo $user ? htmlspecialchars($user['last_name']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>อีเมล (Username) <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" name="email" required
                                        value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>รหัสผ่าน <?php echo $id ? '<small class="text-muted">(เว้นว่างถ้าไม่ต้องการเปลี่ยน)</small>' : '<span class="text-danger">*</span>'; ?></label>
                                    <input type="password" class="form-control" name="password"
                                        <?php echo $id ? '' : 'required'; ?> placeholder="กำหนดรหัสผ่านอย่างน้อย 6 ตัวอักษร">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>เบอร์โทรศัพท์</label>
                                    <input type="text" class="form-control" name="phone"
                                        value="<?php echo $user ? htmlspecialchars($user['phone']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>สิทธิ์การใช้งาน <span class="text-danger">*</span></label>
                                    <select class="form-control" name="role">
                                        <option value="member" <?php echo ($user && $user['role'] == 'member') ? 'selected' : ''; ?>>สมาชิกทั่วไป (Member)</option>
                                        <option value="admin" <?php echo ($user && $user['role'] == 'admin') ? 'selected' : ''; ?>>ผู้ดูแลระบบ (Admin)</option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>ที่อยู่</label>
                            <textarea class="form-control" name="address" rows="3"><?php echo $user ? htmlspecialchars($user['address']) : ''; ?></textarea>
                        </div>

                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> บันทึกข้อมูล</button>
                        <a href="users" class="btn btn-default float-right">ยกเลิก</a>
                    </div>
                </form>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>