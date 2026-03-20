<?php
session_start();
include 'includes/session.php';

include '../config/connect.php';

// --- ส่วนลบสมาชิก (Delete Logic) ---
if (isset($_GET['delete_id'])) {
    $id = $_GET['delete_id'];

    // ป้องกันการลบตัวเอง (ตรวจสอบจาก Session ID ถ้ามีระบบ Login แล้ว)
    // if ($id == $_SESSION['user_id']) { ... } 

    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        $_SESSION['success'] = "ลบสมาชิกเรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาด: " . $stmt->error;
    }

    header("Location: users");
    exit();
}
// --- จบส่วนลบ ---

$active_menu = "users";
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">จัดการสมาชิก</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index">หน้าหลัก</a></li>
                        <li class="breadcrumb-item active">สมาชิก</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <div class="mb-3">
                <a href="user_form" class="btn btn-success">
                    <i class="fas fa-user-plus mr-1"></i> เพิ่มสมาชิกใหม่
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">รายชื่อสมาชิกทั้งหมด</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th>ชื่อ-นามสกุล</th>
                                <th>อีเมล</th>
                                <th>เบอร์โทร</th>
                                <th style="width: 100px;">สิทธิ์</th>
                                <th style="width: 150px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM users ORDER BY id DESC";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                                    <td>
                                        <?php if ($row['role'] == 'admin'): ?>
                                            <span class="badge badge-danger">ผู้ดูแลระบบ</span>
                                        <?php else: ?>
                                            <span class="badge badge-success">สมาชิกทั่วไป</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="user_form?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                            <i class="fas fa-pencil-alt"></i>
                                        </a>
                                        <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "คุณต้องการลบสมาชิกคนนี้ใช่หรือไม่",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ลบเลย',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'users?delete_id=' + id;
            }
        })
    }

    <?php if (isset($_SESSION['success'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'สำเร็จ!',
            text: '<?php echo $_SESSION['success']; ?>',
            showConfirmButton: false,
            timer: 1500
        });
    <?php unset($_SESSION['success']);
    endif; ?>
</script>

<?php include 'includes/footer.php'; ?>