<?php
session_start();
include 'includes/session.php';

include '../config/connect.php';
$active_menu = "products";
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">จัดการสินค้า</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="index">หน้าหลัก</a></li>
                        <li class="breadcrumb-item active">สินค้า</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <!-- ปุ่มเพิ่มสินค้า -->
            <div class="mb-3">
                <a href="product_form" class="btn btn-success">
                    <i class="fas fa-plus mr-1"></i> เพิ่มสินค้าใหม่
                </a>
            </div>

            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">รายการสินค้าทั้งหมด</h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered table-striped table-hover">
                        <thead>
                            <tr>
                                <th style="width: 50px;">ID</th>
                                <th style="width: 80px;">รูปภาพ</th>
                                <th>ชื่อสินค้า</th>
                                <th>หมวดหมู่</th>
                                <th>ราคา</th>
                                <th>สต็อก</th>
                                <th style="width: 150px;">จัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // ดึงข้อมูลสินค้า JOIN กับหมวดหมู่
                            $sql = "SELECT p.*, c.name as category_name 
                        FROM products p 
                        LEFT JOIN categories c ON p.category_id = c.id 
                        ORDER BY p.id DESC";
                            $result = $conn->query($sql);

                            if ($result->num_rows > 0):
                                while ($row = $result->fetch_assoc()):
                            ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td>
                                            <?php if (!empty($row['image'])): ?>
                                                <img src="../<?php echo $row['image']; ?>" width="50" class="img-thumbnail">
                                            <?php else: ?>
                                                <span class="text-muted small">ไม่มีรูป</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($row['name']); ?>
                                            <?php if ($row['is_bestseller']) echo '<span class="badge badge-danger ml-1">ขายดี</span>'; ?>
                                            <?php if ($row['is_featured']) echo '<span class="badge badge-warning ml-1">แนะนำ</span>'; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['category_name'] ?? '-'); ?></td>
                                        <td>
                                            <?php if ($row['sale_price'] > 0): ?>
                                                <del class="text-muted small"><?php echo number_format($row['price']); ?></del>
                                                <span class="text-danger font-weight-bold"><?php echo number_format($row['sale_price']); ?></span>
                                            <?php else: ?>
                                                <?php echo number_format($row['price']); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $stock_class = ($row['stock_quantity'] <= 5) ? 'text-danger font-weight-bold' : '';
                                            echo '<span class="' . $stock_class . '">' . number_format($row['stock_quantity']) . '</span>';
                                            ?>
                                        </td>
                                        <td>
                                            <a href="product_form?id=<?php echo $row['id']; ?>" class="btn btn-info btn-sm">
                                                <i class="fas fa-pencil-alt"></i>
                                            </a>
                                            <button type="button" class="btn btn-danger btn-sm" onclick="confirmDelete(<?php echo $row['id']; ?>)">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile;
                            else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">ไม่พบข้อมูลสินค้า</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'ยืนยันการลบ?',
            text: "คุณต้องการลบสินค้านี้ใช่หรือไม่ การกระทำนี้ไม่สามารถย้อนกลับได้",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'ลบเลย!',
            cancelButtonText: 'ยกเลิก'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'actions/delete_product.php?id=' + id;
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

    <?php if (isset($_SESSION['error'])): ?>
        Swal.fire({
            icon: 'error',
            title: 'ผิดพลาด!',
            text: '<?php echo $_SESSION['error']; ?>'
        });
    <?php unset($_SESSION['error']);
    endif; ?>
</script>

<?php include 'includes/footer.php'; ?>