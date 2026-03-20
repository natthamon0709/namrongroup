<?php
session_start();
include 'includes/session.php';

include '../config/connect.php';

$id = isset($_GET['id']) ? $_GET['id'] : 0;

// --- Logic อัปเดตสถานะ (Update Status) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $new_status = $_POST['new_status'];

    // กรณีจัดส่งสินค้า (Shipped) -> บันทึกเลขพัสดุ
    if ($new_status == 'shipped') {
        $tracking = $_POST['tracking_number'];
        $carrier = $_POST['carrier'];

        $stmt = $conn->prepare("UPDATE orders SET status = ?, tracking_number = ?, carrier = ? WHERE id = ?");
        $stmt->bind_param("sssi", $new_status, $tracking, $carrier, $id);
    }
    // กรณีอื่นๆ
    else {
        // ถ้าเป็นการยกเลิก คืนสต็อก (Logic เดิม)
        if ($new_status == 'cancelled') {
            $check = $conn->query("SELECT status FROM orders WHERE id = $id")->fetch_assoc();
            if ($check['status'] != 'cancelled') {
                $items = $conn->query("SELECT product_id, quantity FROM order_items WHERE order_id = $id");
                while ($item = $items->fetch_assoc()) {
                    $conn->query("UPDATE products SET stock_quantity = stock_quantity + {$item['quantity']} WHERE id = {$item['product_id']}");
                }
            }
        }

        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $id);
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = "อัปเดตสถานะเรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดต";
    }

    header("Location: order_detail.php?id=" . $id);
    exit();
}

// ดึงข้อมูล Order
$stmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header("Location: orders.php");
    exit();
}

// ดึงข้อมูล Items
$items_sql = "SELECT * FROM order_items WHERE order_id = ?";
$stmt_items = $conn->prepare($items_sql);
$stmt_items->bind_param("i", $id);
$stmt_items->execute();
$items_result = $stmt_items->get_result();

$active_menu = "orders";
include 'includes/header.php';
?>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">รายละเอียดคำสั่งซื้อ #<?php echo $order['order_number']; ?></h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="orders.php">คำสั่งซื้อ</a></li>
                        <li class="breadcrumb-item active">รายละเอียด</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">

            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                    <i class="fas fa-check-circle mr-1"></i> <?php echo $_SESSION['success']; ?>
                </div>
            <?php unset($_SESSION['success']);
            endif; ?>

            <div class="row">
                <!-- Left Column: รายละเอียดสินค้า & สถานะ -->
                <div class="col-md-8">
                    <!-- สถานะคำสั่งซื้อ -->
                    <div class="card">
                        <div class="card-header bg-light">
                            <h3 class="card-title">สถานะ:
                                <?php
                                switch ($order['status']) {
                                    case 'pending':
                                        echo '<span class="badge badge-warning text-dark">รอตรวจสอบ</span>';
                                        break;
                                    case 'paid':
                                        echo '<span class="badge badge-success">ชำระเงินแล้ว (รอจัดส่ง)</span>';
                                        break;
                                    case 'shipped':
                                        echo '<span class="badge badge-primary">จัดส่งแล้ว</span>';
                                        break;
                                    case 'completed':
                                        echo '<span class="badge badge-success">สำเร็จ</span>';
                                        break;
                                    case 'cancelled':
                                        echo '<span class="badge badge-danger">ยกเลิก</span>';
                                        break;
                                }
                                ?>
                            </h3>
                            <div class="card-tools">
                                วันที่สั่งซื้อ: <?php echo date('d/m/Y H:i', strtotime($order['created_at'])); ?>
                            </div>
                        </div>
                        <div class="card-body">

                            <!-- แสดงเลขพัสดุถ้ามีการจัดส่งแล้ว -->
                            <?php if (!empty($order['tracking_number'])): ?>
                                <div class="alert alert-info">
                                    <h5><i class="fas fa-truck"></i> ข้อมูลการจัดส่ง</h5>
                                    <strong>ขนส่ง:</strong> <?php echo htmlspecialchars($order['carrier']); ?><br>
                                    <strong>หมายเลขพัสดุ:</strong> <?php echo htmlspecialchars($order['tracking_number']); ?>
                                </div>
                            <?php endif; ?>

                            <!-- ปุ่มเปลี่ยนสถานะ -->
                            <div class="btn-group-vertical w-100 mb-3">
                                <?php if ($order['status'] == 'pending'): ?>
                                    <form action="" method="POST" class="w-100" onsubmit="return confirm('ยืนยันการชำระเงินถูกต้อง?');">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="new_status" value="paid">
                                        <button type="submit" class="btn btn-success btn-block mb-2"><i class="fas fa-check mr-1"></i> ยืนยันยอดเงินถูกต้อง (ปรับเป็นชำระแล้ว)</button>
                                    </form>
                                <?php endif; ?>

                                <?php if ($order['status'] == 'paid'): ?>
                                    <!-- ฟอร์มแจ้งจัดส่งสินค้า -->
                                    <div class="card card-primary card-outline col-12">
                                        <div class="card-header">
                                            <h5 class="card-title m-0">แจ้งจัดส่งสินค้า</h5>
                                        </div>
                                        <div class="card-body">
                                            <form action="" method="POST" onsubmit="return confirm('ยืนยันการจัดส่งสินค้า?');">
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="new_status" value="shipped">

                                                <div class="form-group">
                                                    <label>บริษัทขนส่ง</label>
                                                    <select class="form-control" name="carrier" required>
                                                        <option value="">-- เลือกขนส่ง --</option>
                                                        <option value="Kerry Express">Kerry Express</option>
                                                        <option value="Flash Express">Flash Express</option>
                                                        <option value="J&T Express">J&T Express</option>
                                                        <option value="ไปรษณีย์ไทย (EMS)">ไปรษณีย์ไทย (EMS)</option>
                                                        <option value="อื่นๆ">อื่นๆ</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label>หมายเลขพัสดุ (Tracking No.)</label>
                                                    <input type="text" class="form-control" name="tracking_number" placeholder="เช่น TH12345678" required>
                                                </div>

                                                <button type="submit" class="btn btn-primary btn-block"><i class="fas fa-shipping-fast mr-1"></i> บันทึกข้อมูลการจัดส่ง</button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- ปุ่มจบงาน (ถ้าส่งแล้ว) -->
                                <?php if ($order['status'] == 'shipped'): ?>
                                    <form action="" method="POST" class="w-100 mt-2">
                                        <input type="hidden" name="action" value="update_status">
                                        <input type="hidden" name="new_status" value="completed">
                                        <button type="submit" class="btn btn-success btn-block"><i class="fas fa-check-circle mr-1"></i> ปิดงาน (เสร็จสิ้น)</button>
                                    </form>
                                <?php endif; ?>
                            </div>

                            <?php if ($order['status'] != 'cancelled' && $order['status'] != 'completed' && $order['status'] != 'shipped'): ?>
                                <form action="" method="POST" onsubmit="return confirm('คำเตือน: การยกเลิกจะคืนสต็อกสินค้า คุณแน่ใจหรือไม่?');">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="new_status" value="cancelled">
                                    <button type="submit" class="btn btn-outline-danger btn-block btn-sm">ยกเลิกคำสั่งซื้อนี้</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- รายการสินค้า (ตารางเดิม) -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">รายการสินค้า</h3>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>สินค้า</th>
                                        <th>ราคา</th>
                                        <th>จำนวน</th>
                                        <th class="text-right">รวม</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($item = $items_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                                            <td>฿<?php echo number_format($item['price']); ?></td>
                                            <td><?php echo $item['quantity']; ?></td>
                                            <td class="text-right">฿<?php echo number_format($item['price'] * $item['quantity']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="3" class="text-right">ยอดสุทธิ:</th>
                                        <th class="text-right text-primary h5">฿<?php echo number_format($order['total_amount']); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Right Column: ข้อมูลลูกค้า & สลิป (เหมือนเดิม) -->
                <div class="col-md-4">

                    <!-- หลักฐานการโอนเงิน -->
                    <div class="card card-outline card-warning">
                        <div class="card-header">
                            <h3 class="card-title">หลักฐานการชำระเงิน</h3>
                        </div>
                        <div class="card-body text-center">
                            <?php if (!empty($order['slip_image'])): ?>
                                <a href="../<?php echo $order['slip_image']; ?>" target="_blank">
                                    <img src="../<?php echo $order['slip_image']; ?>" class="img-fluid border rounded" alt="Slip">
                                </a>
                                <p class="mt-2 mb-0 text-muted small">คลิกที่รูปเพื่อดูภาพขนาดใหญ่</p>
                            <?php else: ?>
                                <div class="py-4 text-muted">
                                    <i class="fas fa-file-invoice-dollar fa-3x mb-2"></i>
                                    <p>ยังไม่มีการแนบสลิป</p>
                                </div>
                            <?php endif; ?>

                            <hr>
                            <strong>วิธีการชำระ:</strong>
                            <?php
                            if ($order['payment_method'] == 'promptpay') echo 'PromptPay QR';
                            elseif ($order['payment_method'] == 'bank_transfer') echo 'โอนเงินธนาคาร';
                            else echo 'เก็บเงินปลายทาง (COD)';
                            ?>
                        </div>
                    </div>

                    <!-- ที่อยู่จัดส่ง -->
                    <div class="card card-outline card-primary">
                        <div class="card-header">
                            <h3 class="card-title">ที่อยู่จัดส่ง</h3>
                        </div>
                        <div class="card-body">
                            <strong><?php echo htmlspecialchars($order['shipping_name']); ?></strong><br>
                            <p class="text-muted mb-2">
                                <?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?>
                            </p>
                            <i class="fas fa-phone-alt mr-1"></i> <?php echo htmlspecialchars($order['shipping_phone']); ?>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </section>
</div>

<?php include 'includes/footer.php'; ?>