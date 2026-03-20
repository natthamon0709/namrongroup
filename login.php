<?php
session_start();
include 'config/connect.php';

// ตรวจสอบว่าล็อกอินอยู่แล้วหรือไม่
if (isset($_SESSION['user_id'])) {
    header("Location: index");
    exit();
}

$error_msg = "";
$success_msg = "";

// --- ส่วนจัดการ Form Submit ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {

    // 1. กรณีเข้าสู่ระบบ (Login)
    if (isset($_POST['action']) && $_POST['action'] == 'login') {
        $email = $_POST['email'];
        $password = $_POST['password'];

        $stmt = $conn->prepare("SELECT id, password, first_name, last_name, role FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                // รหัสผ่านถูกต้อง -> สร้าง Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['first_name'] . " " . $user['last_name'];
                $_SESSION['user_role'] = $user['role'];

                // ตรวจสอบสิทธิ์และ Redirect
                if ($user['role'] == 'admin') {
                    header("Location: backoffice/index");
                } else {
                    header("Location: index");
                }
                exit();
            } else {
                $error_msg = "รหัสผ่านไม่ถูกต้อง";
            }
        } else {
            $error_msg = "ไม่พบอีเมลนี้ในระบบ";
        }
    }

    // 2. กรณีสมัครสมาชิก (Register)
    if (isset($_POST['action']) && $_POST['action'] == 'register') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

        if ($password !== $confirm_password) {
            $error_msg = "รหัสผ่านไม่ตรงกัน";
        } else {
            // เช็คอีเมลซ้ำ
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error_msg = "อีเมลนี้ถูกใช้งานแล้ว";
            } else {
                // บันทึกลงฐานข้อมูล
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $role = 'member'; // สมัครหน้าเว็บเป็น member เสมอ

                $stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssss", $first_name, $last_name, $email, $phone, $hashed_password, $role);

                if ($stmt->execute()) {
                    $success_msg = "สมัครสมาชิกสำเร็จ! กรุณาเข้าสู่ระบบ";
                    // อาจจะ Redirect ไป tab login หรือ refresh หน้า
                } else {
                    $error_msg = "เกิดข้อผิดพลาด: " . $stmt->error;
                }
            }
        }
    }
}

$page_title = "เข้าสู่ระบบ / สมัครสมาชิก - Namrong Group";
$active_page = "login";

include 'includes/header.php';
?>

<style>
    /* เพิ่ม CSS Override เพื่อให้ใช้สีตามการตั้งค่า */
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

    /* Custom Styles */
    .login-bg {
        /* background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); */
        min-height: calc(100vh - 76px);
    }

    .auth-card {
        border: none;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        backdrop-filter: blur(10px);
        background: rgba(255, 255, 255, 0.95);
    }

    .nav-pills .nav-link {
        border-radius: 15px;
        padding: 12px 20px;
        font-weight: 600;
        color: #6c757d;
        transition: all 0.3s;
    }

    .nav-pills .nav-link.active {
        background-color: var(--primary-color);
        color: white !important;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        /* ปรับเป็นเงาสีดำจางๆ เพื่อให้เข้ากับทุกสี */
    }

    .form-floating>.form-control:focus~label,
    .form-floating>.form-control:not(:placeholder-shown)~label {
        color: var(--primary-color);
        transform: scale(0.85) translateY(-0.5rem) translateX(0.15rem);
    }

    .form-control:focus {
        box-shadow: none;
        border-color: var(--primary-color);
        background-color: #f8faff;
    }

    .btn-social {
        transition: transform 0.2s;
        border: 1px solid #dee2e6;
    }

    .btn-social:hover {
        transform: translateY(-2px);
        background-color: #f8f9fa;
    }

    .password-toggle {
        cursor: pointer;
        z-index: 10;
    }
</style>

<section class="login-bg py-5 d-flex align-items-center">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-8 col-xl-5">

                <div class="text-center mb-4">
                    <div class="d-inline-flex align-items-center justify-content-center bg-white rounded-circle shadow-sm p-3 mb-3">
                        <i class="bi bi-bag-heart-fill text-primary display-6"></i>
                    </div>
                    <h2 class="fw-bold text-dark">ยินดีต้อนรับกลับมา!</h2>
                    <p class="text-muted">เข้าสู่ระบบเพื่อจัดการบัญชีของคุณ</p>
                </div>

                <div class="card auth-card overflow-hidden">
                    <div class="card-header bg-transparent border-0 p-3 pb-0">
                        <ul class="nav nav-pills nav-fill bg-light rounded-4 p-2" id="authTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo empty($success_msg) ? 'active' : ''; ?>" id="login-tab" data-bs-toggle="tab" data-bs-target="#login-pane" type="button" role="tab"><i class="bi bi-box-arrow-in-right me-2"></i>เข้าสู่ระบบ</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link <?php echo !empty($success_msg) ? 'active' : ''; ?>" id="register-tab" data-bs-toggle="tab" data-bs-target="#register-pane" type="button" role="tab"><i class="bi bi-person-plus me-2"></i>สมัครสมาชิก</button>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body p-4 p-md-5">
                        <!-- Alert Messages -->
                        <?php if (!empty($error_msg)): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <i class="bi bi-exclamation-circle me-2"></i> <?php echo $error_msg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($success_msg)): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <i class="bi bi-check-circle me-2"></i> <?php echo $success_msg; ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <div class="tab-content" id="authTabContent">

                            <!-- Login Form -->
                            <div class="tab-pane fade <?php echo empty($success_msg) ? 'show active' : ''; ?>" id="login-pane" role="tabpanel" tabindex="0">
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="login">
                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control rounded-3" id="loginEmail" name="email" placeholder="name@example.com" required>
                                        <label for="loginEmail">อีเมล</label>
                                    </div>

                                    <div class="position-relative mb-3">
                                        <div class="form-floating">
                                            <input type="password" class="form-control rounded-3" id="loginPassword" name="password" placeholder="Password" required>
                                            <label for="loginPassword">รหัสผ่าน</label>
                                        </div>
                                        <span class="password-toggle position-absolute top-50 end-0 translate-middle-y me-3 text-muted" onclick="togglePassword('loginPassword', this)">
                                            <i class="bi bi-eye"></i>
                                        </span>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <div class="form-check">
                                            <input type="checkbox" class="form-check-input" id="rememberMe">
                                            <label class="form-check-label text-muted small" for="rememberMe">จดจำฉันไว้ในระบบ</label>
                                        </div>
                                        <a href="#" class="small text-decoration-none fw-bold text-primary">ลืมรหัสผ่าน?</a>
                                    </div>

                                    <div class="d-grid mb-4">
                                        <button type="submit" class="btn btn-primary rounded-pill py-3 fw-bold shadow-sm">เข้าสู่ระบบ</button>
                                    </div>

                                    <div class="position-relative text-center mb-4">
                                        <hr class="text-muted opacity-25">
                                        <span class="position-absolute top-50 start-50 translate-middle bg-white px-3 text-muted small text-nowrap">หรือดำเนินการต่อด้วย</span>
                                    </div>

                                    <div class="row g-2">
                                        <div class="col">
                                            <button class="btn btn-social bg-white w-100 py-2 rounded-3 text-dark small fw-bold" type="button">
                                                Google
                                            </button>
                                        </div>
                                        <div class="col">
                                            <button class="btn btn-social bg-white w-100 py-2 rounded-3 text-dark small fw-bold" type="button">
                                                Facebook
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>

                            <!-- Register Form -->
                            <div class="tab-pane fade <?php echo !empty($success_msg) ? 'show active' : ''; ?>" id="register-pane" role="tabpanel" tabindex="0">
                                <form action="" method="POST">
                                    <input type="hidden" name="action" value="register">
                                    <div class="row g-3 mb-3">
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control rounded-3" id="registerName" name="first_name" placeholder="ชื่อ" required>
                                                <label for="registerName">ชื่อ</label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-floating">
                                                <input type="text" class="form-control rounded-3" id="registerLastName" name="last_name" placeholder="นามสกุล" required>
                                                <label for="registerLastName">นามสกุล</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-floating mb-3">
                                        <input type="email" class="form-control rounded-3" id="registerEmail" name="email" placeholder="name@example.com" required>
                                        <label for="registerEmail">อีเมล</label>
                                    </div>

                                    <div class="form-floating mb-3">
                                        <input type="tel" class="form-control rounded-3" id="registerPhone" name="phone" placeholder="08x-xxx-xxxx">
                                        <label for="registerPhone">เบอร์โทรศัพท์</label>
                                    </div>

                                    <div class="position-relative mb-3">
                                        <div class="form-floating">
                                            <input type="password" class="form-control rounded-3" id="registerPassword" name="password" placeholder="Password" required>
                                            <label for="registerPassword">รหัสผ่าน</label>
                                        </div>
                                        <span class="password-toggle position-absolute top-50 end-0 translate-middle-y me-3 text-muted" onclick="togglePassword('registerPassword', this)">
                                            <i class="bi bi-eye"></i>
                                        </span>
                                    </div>

                                    <div class="position-relative mb-4">
                                        <div class="form-floating">
                                            <input type="password" class="form-control rounded-3" id="confirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                                            <label for="confirmPassword">ยืนยันรหัสผ่าน</label>
                                        </div>
                                    </div>

                                    <div class="form-check mb-4">
                                        <input type="checkbox" class="form-check-input" id="agreeTerms" required>
                                        <label class="form-check-label small text-muted" for="agreeTerms">
                                            ฉันยอมรับ <a href="#" class="text-primary text-decoration-none">เงื่อนไขการให้บริการ</a> และ <a href="#" class="text-primary text-decoration-none">นโยบายความเป็นส่วนตัว</a>
                                        </label>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-success rounded-pill py-3 fw-bold shadow-sm">สร้างบัญชีผู้ใช้</button>
                                    </div>
                                </form>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="text-center mt-5 text-muted small opacity-75">
                    &copy; 2024 Namrong Group. All rights reserved.
                </div>

            </div>
        </div>
    </div>
</section>

<script>
    function togglePassword(inputId, icon) {
        const input = document.getElementById(inputId);
        const iconEl = icon.querySelector('i');

        if (input.type === "password") {
            input.type = "text";
            iconEl.classList.remove('bi-eye');
            iconEl.classList.add('bi-eye-slash');
        } else {
            input.type = "password";
            iconEl.classList.remove('bi-eye-slash');
            iconEl.classList.add('bi-eye');
        }
    }
</script>

<?php include 'includes/footer.php'; ?>