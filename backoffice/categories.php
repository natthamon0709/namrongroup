<?php
session_start();
include '../config/connect.php';
$active_menu = "categories";
include 'includes/header.php';
include 'includes/session.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">จัดการหมวดหมู่สินค้า</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index">หน้าหลัก</a></li>
                        <li class="breadcrumb-item active">หมวดหมู่</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="mb-3">
                <a href="category_form" class="btn btn-success">
                    <i class="fas fa-plus mr-1"></i> เพิ่มหมวดหมู่
                </a>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <table class="table table-striped projects">
                        <thead>
                            <tr>
                                <th style="width: 10%">ID</th>
                                <th style="width: 20%">รูปภาพ</th>
                                <th>ชื่อหมวดหมู่</th>
                                <th style="width: 20%">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $sql = "SELECT * FROM categories ORDER BY id DESC";
                            $result = $conn->query($sql);
                            while ($row = $result->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?php echo $row['id']; ?></td>
                                    <td>
                                        <?php if (!empty($row['image'])): ?>
                                            <img src="../<?php echo $row['image']; ?>" class="img-thumbnail" style="height: 50px;">
                                        <?php else: ?>
                                            <span class="text-muted small">ไม่มีรูป</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['name']); ?></td>
                                    <td class="project-actions">
                                        <a class="btn btn-info btn-sm" href="category_form?id=<?php echo $row['id']; ?>">
                                            <i class="fas fa-pencil-alt"></i> แก้ไข
                                        </a>
                                        <button class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                            <i class="fas fa-trash"></i> ลบ
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
            text: "หากลบหมวดหมู่นี้ สินค้าในหมวดหมู่นี้จะไม่มีสังกัด",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            confirmButtonText: 'ลบเลย'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'actions/delete_category.php?id=' + id;
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