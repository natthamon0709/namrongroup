<?php
session_start();
include 'config/connect.php';

// --- ส่วนที่ 1: Internal API Logic ---
if (isset($_GET['fetch_items']) && isset($_GET['order_id'])) {
    header('Content-Type: application/json');
    $order_id = $_GET['order_id'];
    $sql = "SELECT product_name, quantity FROM order_items WHERE order_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    echo json_encode($items);
    exit();
}

// ตรวจสอบสถานะล็อกอิน
if (!isset($_SESSION['user_id'])) {
    header("Location: login");
    exit();
}

$user_id = $_SESSION['user_id'];
$msg = "";
$msg_type = "";

// --- ส่วนที่ 2: จัดการ Logic POST ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 2.1 แก้ไขโปรไฟล์
    if (isset($_POST['action']) && $_POST['action'] == 'update_profile') {
        $first_name = $_POST['first_name'];
        $last_name = $_POST['last_name'];
        $phone = $_POST['phone'];
        $address = $_POST['address'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        $password_sql = "";
        $params = [$first_name, $last_name, $phone, $address, $user_id];
        $types = "ssssi";

        if (!empty($new_password)) {
            if ($new_password === $confirm_password) {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $password_sql = ", password = ?";
                array_splice($params, 4, 0, $hashed_password);
                $types = "sssssi";
            } else {
                $msg = "รหัสผ่านใหม่ไม่ตรงกัน";
                $msg_type = "error";
            }
        }

        if (empty($msg)) {
            $sql = "UPDATE users SET first_name=?, last_name=?, phone=?, address=? $password_sql WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $_SESSION['user_name'] = $first_name . " " . $last_name;
                $msg = "บันทึกข้อมูลเรียบร้อยแล้ว";
                $msg_type = "success";
            }
        }
    }

    // 2.2 แจ้งชำระเงิน (อัปโหลดสลิปเพิ่ม)
    if (isset($_POST['action']) && $_POST['action'] == 'upload_slip') {
        $order_id = $_POST['order_id'];
        if (isset($_FILES['slip_image']) && $_FILES['slip_image']['error'] == 0) {
            $ext = pathinfo($_FILES['slip_image']['name'], PATHINFO_EXTENSION);
            $new_name = 'slip_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $target = 'uploads/slips/' . $new_name;

            if (move_uploaded_file($_FILES['slip_image']['tmp_name'], $target)) {
                $stmt = $conn->prepare("UPDATE orders SET slip_image = ? WHERE id = ? AND user_id = ?");
                $stmt->bind_param("sii", $target, $order_id, $user_id);
                if ($stmt->execute()) {
                    $msg = "อัปโหลดสลิปเรียบร้อยแล้ว ระบบกำลังตรวจสอบ";
                    $msg_type = "success";
                }
            } else {
                $msg = "ไม่สามารถบันทึกไฟล์ได้";
                $msg_type = "error";
            }
        }
    }
}

// --- ส่วนที่ 3: ดึงข้อมูลเพื่อแสดงผล ---
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$orders_sql = "SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC";
$stmt_orders = $conn->prepare($orders_sql);
$stmt_orders->bind_param("i", $user_id);
$stmt_orders->execute();
$orders_result = $stmt_orders->get_result();

$page_title = "บัญชีของฉัน - Concert Ticket";
include 'includes/header.php';
?>

<style>
    :root { --primary-color: #ff3e6c; }
    .text-primary { color: var(--primary-color) !important; }
    .btn-primary { background-color: var(--primary-color) !important; border-color: var(--primary-color) !important; }
    .list-group-item.active { background-color: var(--primary-color) !important; border-color: var(--primary-color) !important; }
    
    #ticketsContainer {
        display: flex;
        flex-direction: column;
        gap: 25px;
        align-items: center;
        padding: 10px 0;
    }
    .ticket-visual {
        background: white;
        border-radius: 20px;
        overflow: hidden;
        position: relative;
        box-shadow: 0 15px 35px rgba(0,0,0,0.2);
        width: 100%;
        max-width: 380px;
        border: 1px solid #eee;
    }
    .ticket-divider {
        height: 2px;
        border-top: 2px dashed #eee;
        position: relative;
        margin: 10px 0;
    }
    .ticket-divider::before, .ticket-divider::after {
        content: '';
        position: absolute;
        top: -12px;
        width: 24px;
        height: 24px;
        background: #222; 
        border-radius: 50%;
    }
    .ticket-divider::before { left: -14px; }
    .ticket-divider::after { right: -14px; }

    @media print {
        @page { size: A4; margin: 1cm; }
        body * { visibility: hidden; background: none !important; }
        #ticketModal, .modal-dialog, .modal-content, #ticketsContainer, #ticketsContainer * { visibility: visible; }
        #ticketModal { position: absolute; left: 0; top: 0; width: 100%; background: white !important; }
        .modal-dialog { max-width: 100% !important; margin: 0 !important; padding: 0 !important; }
        .modal-content { border: none !important; box-shadow: none !important; }
        #ticketsContainer { display: block !important; padding: 0 !important; margin: 0 !important; }
        .ticket-visual { visibility: visible; box-shadow: none !important; border: 2px solid #333 !important; margin: 0 auto 30px auto !important; page-break-inside: avoid; width: 90% !important; max-width: 450px !important; }
        .ticket-divider::before, .ticket-divider::after { background: white !important; border: 1px solid #333; }
        .btn, .modal-header, .btn-close, .text-center.mt-3 { display: none !important; }
        .bg-primary { background-color: #ff3e6c !important; -webkit-print-color-adjust: exact; }
    }
</style>

<div class="bg-light py-3 border-bottom">
    <div class="container"><nav aria-label="breadcrumb"><ol class="breadcrumb mb-0"><li class="breadcrumb-item"><a href="index">หน้าแรก</a></li><li class="breadcrumb-item active">บัญชีของฉัน</li></ol></nav></div>
</div>

<section class="py-5 bg-light">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-3">
                <div class="card border-0 shadow-sm rounded-4 mb-4 text-center p-4">
                    <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center text-white mx-auto mb-3" style="width: 80px; height: 80px; font-size: 30px;">
                        <?php echo mb_substr($user['first_name'], 0, 1, 'UTF-8'); ?>
                    </div>
                    <h5 class="fw-bold mb-0"><?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></h5>
                </div>
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                    <div class="list-group list-group-flush" id="profileTabs">
                        <a class="list-group-item list-group-item-action py-3 active" data-bs-toggle="list" href="#profile">ข้อมูลส่วนตัว</a>
                        <a class="list-group-item list-group-item-action py-3" data-bs-toggle="list" href="#orders">ประวัติการจองบัตร</a>
                        <a href="logout" class="list-group-item list-group-item-action py-3 text-danger">ออกจากระบบ</a>
                    </div>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="profile">
                        <div class="card border-0 shadow-sm rounded-4 p-4">
                            <h5 class="fw-bold mb-4">แก้ไขข้อมูลส่วนตัว</h5>
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="update_profile">
                                <div class="row g-3">
                                    <div class="col-md-6"><label class="small text-muted">ชื่อ</label><input type="text" class="form-control" name="first_name" value="<?php echo htmlspecialchars($user['first_name']); ?>"></div>
                                    <div class="col-md-6"><label class="small text-muted">นามสกุล</label><input type="text" class="form-control" name="last_name" value="<?php echo htmlspecialchars($user['last_name']); ?>"></div>
                                    <div class="col-12 mt-4 text-end"><button type="submit" class="btn btn-primary rounded-pill px-4">บันทึกการแก้ไข</button></div>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="tab-pane fade" id="orders">
                        <div class="card border-0 shadow-sm rounded-4">
                            <div class="card-header bg-white py-3 px-4"><h5 class="fw-bold mb-0">รายการจองของฉัน</h5></div>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="px-4">เลขคำสั่งซื้อ</th>
                                            <th>สถานะ</th>
                                            <th class="text-end">ยอดรวม</th>
                                            <th class="text-center">จัดการ</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($orders_result->num_rows > 0): ?>
                                            <?php while ($order = $orders_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td class="px-4 fw-bold text-primary">#<?php echo $order['order_number']; ?></td>
                                                    <td>
                                                        <?php
                                                        $s = $order['status'];
                                                        $class = ($s=='paid' || $s=='completed') ? 'bg-success' : (($s=='pending') ? 'bg-warning text-dark' : 'bg-danger');
                                                        $text = ($s=='pending' && !empty($order['slip_image'])) ? 'รอตรวจสอบชำระเงิน' : $s;
                                                        ?>
                                                        <span class="badge rounded-pill <?php echo $class; ?>"><?php echo $text; ?></span>
                                                    </td>
                                                    <td class="text-end fw-bold">฿<?php echo number_format($order['total_amount']); ?></td>
                                                    <td class="text-center">
                                                        <?php if (in_array($order['status'], ['paid', 'shipped', 'completed'])): ?>
                                                            <button class="btn btn-sm btn-dark rounded-pill shadow-sm" onclick="viewTicket('<?php echo $order['order_number']; ?>', '<?php echo $order['id']; ?>')">
                                                                <i class="bi bi-ticket-perforated me-1"></i> ดูบัตร (E-Ticket)
                                                            </button>
                                                        <?php elseif ($order['status'] == 'pending' && empty($order['slip_image'])): ?>
                                                            <button class="btn btn-sm btn-primary rounded-pill shadow-sm" onclick="openSlipModal('<?php echo $order['id']; ?>', '<?php echo $order['order_number']; ?>')">
                                                                <i class="bi bi-cloud-upload me-1"></i> แจ้งชำระเงิน
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        <?php else: ?>
                                            <tr><td colspan="4" class="text-center py-4 text-muted">ไม่พบประวัติการจอง</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="uploadSlipModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">แจ้งชำระเงิน สำหรับรายการ #<span id="modal_order_number"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="" method="POST" enctype="multipart/form-data">
                <div class="modal-body py-4">
                    <input type="hidden" name="action" value="upload_slip">
                    <input type="hidden" name="order_id" id="modal_order_id">
                    <div class="mb-3">
                        <label class="form-label small text-muted">เลือกไฟล์สลิปการโอนเงิน</label>
                        <input type="file" class="form-control rounded-pill" name="slip_image" accept="image/*" required>
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i> หลังจากแจ้งชำระเงิน เจ้าหน้าที่จะตรวจสอบภายใน 24 ชม.
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary rounded-pill w-100 py-2 fw-bold">ยืนยันการส่งสลิป</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="ticketModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 bg-transparent">
            <div class="modal-header border-0 pb-0 justify-content-end">
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0">
                <div id="ticketsContainer"></div>
            </div>
            <div class="text-center mt-3 mb-4">
                <button class="btn btn-warning rounded-pill px-4 shadow-lg fw-bold" onclick="window.print()"><i class="bi bi-printer me-2"></i> พิมพ์บัตรทั้งหมด</button>
            </div>
        </div>
    </div>
</div>

<script>
    // ฟังก์ชันเปิด Modal อัปโหลดสลิป
    function openSlipModal(id, number) {
        document.getElementById('modal_order_id').value = id;
        document.getElementById('modal_order_number').innerText = number;
        new bootstrap.Modal(document.getElementById('uploadSlipModal')).show();
    }

    async function viewTicket(orderNum, orderId) {
        const container = document.getElementById('ticketsContainer');
        // แสดง Loading ระหว่างรอข้อมูล
        container.innerHTML = `
            <div class="text-center py-5">
                <div class="spinner-border text-primary mb-2"></div>
                <br><span class="text-muted">กำลังเตรียมตั๋วของคุณ...</span>
            </div>`;
        
        const ticketModal = new bootstrap.Modal(document.getElementById('ticketModal'));
        ticketModal.show();

        try {
            const response = await fetch(`?fetch_items=1&order_id=${orderId}`);
            const items = await response.json();
            
            container.innerHTML = ''; 
            let ticketIndex = 1;

            items.forEach(item => {
                for (let i = 0; i < item.quantity; i++) {
                    const ticketSerial = `${orderNum}-${ticketIndex}`;
                    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=TICKET_ID:${ticketSerial}`;

                    container.innerHTML += `
                        <div class="ticket-box" style="margin-bottom: 30px; border: 1px solid #eee; border-radius: 20px; overflow: hidden; background: #fff; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 380px; margin-left: auto; margin-right: auto;">
                            <div class="ticket-header d-flex align-items-center justify-content-between p-3" style="background: #007bff; color: white;">
                                <div class="ticket-logo">
                                    <img src="uploads/logo_1765163792.png" alt="Logo" style="max-height: 35px; border-radius: 5px; background: rgba(255,255,255,0.2); padding: 2px;">
                                </div>
                                <div class="ticket-info text-end" style="line-height: 1.2;">
                                    <div style="font-size: 0.8rem; font-weight: bold; text-transform: uppercase;">Concert E-Ticket</div>
                                    <div style="font-size: 0.7rem; opacity: 0.9;">ใบที่ ${ticketIndex} / ORDER #${orderNum}</div>
                                </div>
                            </div>
                        
                        <div class="ticket-body text-center p-4">
                            <div class="ticket-event-title font-weight-bold" style="font-size: 1.1rem; color: #333; margin-bottom: 15px; min-height: 2.4em;">
                                ${item.product_name}
                            </div>
                            
                            <div class="qr-wrapper border p-3 rounded-4 shadow-sm" style="display: inline-block; background: #fff; margin-bottom: 10px;">
                                <img src="${qrUrl}" style="width: 160px; height: 160px; display: block;">
                            </div>
                        </div>

                        <div class="ticket-divider" style="position: relative; border-top: 2px dashed #ddd; margin: 0 20px;">
                            <div style="position: absolute; width: 20px; height: 20px; background: #f3f4f6; border-radius: 50%; top: -11px; left: -31px; border: 1px solid #eee;"></div>
                            <div style="position: absolute; width: 20px; height: 20px; background: #f3f4f6; border-radius: 50%; top: -11px; right: -31px; border: 1px solid #eee;"></div>
                        </div>

                        <div class="ticket-footer-info d-flex justify-content-between align-items-end p-3 px-4">
                            <div class="text-start">
                                <div class="small text-muted" style="font-size: 0.7rem;">ผู้ซื้อ</div>
                                <div class="fw-bold" style="color:#333; font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="small text-muted" style="font-size: 0.7rem;">สถานะ</div>
                                <div class="fw-bold text-success" style="font-size: 0.85rem;">ชำระแล้ว</div>
                            </div>
                        </div>

                            <div class="ticket-serial text-center p-2 small text-muted" style="background: #f8fafc; font-size: 0.65rem; border-top: 1px solid #f1f5f9; letter-spacing: 0.5px;">
                                SERIAL: ${ticketSerial} | สแกนที่หน้าประตูทางเข้า
                            </div>
                        </div>
                    `;
                    ticketIndex++;
                }
            });
        } catch (error) {
            console.error(error);
            container.innerHTML = '<div class="alert alert-danger mx-3">ไม่สามารถโหลดข้อมูลตั๋วได้ กรุณาลองใหม่อีกครั้ง</div>';
        }
    }
</script>

<?php if (!empty($msg)): ?>
<script>Swal.fire({ icon: '<?php echo $msg_type; ?>', title: 'แจ้งเตือน', text: '<?php echo $msg; ?>' });</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>