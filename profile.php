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
    :root { --primary-color: #ff3e6c; --concert-gold: #ffc107; --concert-dark: #1a1a1a; }
    .text-primary { color: var(--primary-color) !important; }
    .btn-primary { background-color: var(--primary-color) !important; border-color: var(--primary-color) !important; }
    /* ปรับแต่งหัวข้อชื่อคอนเสิร์ตให้ดูเด่น */
    .event-name {
        font-size: 1.6rem !important;
        font-weight: 900 !important;
        line-height: 1.1;
        margin-bottom: 12px;
    }

    /* ตกแต่งส่วนรอยปรุให้เนียนขึ้น */
    .ticket-horizontal::before, .ticket-horizontal::after {
        background: #1a1a1a !important; /* เปลี่ยนตามสีพื้นหลัง Modal ของคุณ */
        border: 2px solid #ffd700;
    }

    /* ตกแต่งส่วนหางตั๋ว */
    .ticket-stub {
        border-left: 2px dashed rgba(0,0,0,0.1);
    }
    /* Ticket Layout แนวนอน */
    #ticketsContainer {
        display: flex;
        flex-direction: column;
        gap: 30px;
        align-items: center;
        padding: 20px 0;
    }

    .ticket-horizontal {
        display: flex;
        width: 100%;
        max-width: 750px;
        height: 220px;
        background: #fff;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        position: relative;
        border: 2px solid #333;
    }

    /* ฝั่งซ้าย (ข้อมูลหลัก) */
    .ticket-main {
        flex: 3;
        padding: 20px;
        position: relative;
        border-right: 2px dashed #ccc;
        background: linear-gradient(135deg, #fff 0%, #f9f9f9 100%);
    }

    /* ฝั่งขวา (ส่วนหางตั๋ว/Stub) */
    .ticket-stub {
        flex: 1;
        background: var(--concert-gold);
        padding: 15px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        color: #000;
    }

    /* วงกลมรอยปรุ */
    .ticket-horizontal::before, .ticket-horizontal::after {
        content: '';
        position: absolute;
        width: 30px;
        height: 30px;
        background: #222; /* สีเดียวกับ Backdrop ของ Modal */
        border-radius: 50%;
        left: 75%; /* ตำแหน่งรอยปรุ */
        margin-left: -15px;
        z-index: 2;
    }
    .ticket-horizontal::before { top: -15px; }
    .ticket-horizontal::after { bottom: -15px; }

    .event-name {
        font-size: 1.4rem;
        font-weight: 800;
        color: #d63384;
        text-transform: uppercase;
        margin-bottom: 10px;
        line-height: 1.2;
    }

    @media print {
    /* 1. บังคับสีพื้นหลัง */
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        box-sizing: border-box !important;
    }

    /* 2. ตั้งค่าหน้ากระดาษ */
    @page {
        size: A4 portrait;
        margin: 0;
    }

    /* 3. ล้างค่าโครงสร้างเว็บทั้งหมด */
    body { 
        margin: 0 !important; 
        padding: 0 !important; 
        width: 210mm !important; /* บังคับกว้างเท่า A4 */
    }
    body * { visibility: hidden; }

    /* 4. แสดงเฉพาะตั๋ว และล้างค่า Bootstrap Modal */
    #ticketModal, 
    #ticketModal *, 
    #ticketsContainer, 
    #ticketsContainer * { 
        visibility: visible; 
    }

    /* ล้างค่าดักกลางจอของ Bootstrap (สำคัญมาก) */
    .modal { 
        position: absolute !important; 
        display: block !important; 
        padding: 0 !important; 
        margin: 0 !important;
        overflow: visible !important;
    }
    .modal-dialog { 
        max-width: 100% !important; 
        margin: 0 !important; 
        padding: 0 !important; 
        transform: none !important; /* ล้างค่าที่ Bootstrap ดึงกลางจอ */
    }
    .modal-content { 
        background: none !important; 
        border: none !important; 
        box-shadow: none !important;
    }

    /* 5. จัดการ Container ให้ตรงกลางกระดาษจริง */
    #ticketsContainer {
        display: block !important;
        width: 100% !important;
        margin: 0 !important;
        padding-top: 15mm !important;
        text-align: center !important; /* ช่วยให้ inline-block อยู่กลาง */
    }

    /* 6. ปรับขนาดตั๋วให้พอดี (Safe Zone) */
    .ticket-horizontal {
        visibility: visible !important;
        display: inline-flex !important; /* เปลี่ยนเป็น inline-flex เพื่อให้ text-align: center ทำงาน */
        flex-direction: row !important;
        
        margin: 0 auto 5mm auto !important; 
        page-break-inside: avoid;
        border: 1px solid #f9c03d !important;
        background: #fff !important;

        width: 175mm !important; 
        height: 60mm !important;
        position: relative !important;
        overflow: hidden;
        text-align: left !important; /* ให้ข้อความข้างในตั๋วชิดซ้ายปกติ */
    }

    /* 7. ซ่อนปุ่มและขยะส่วนเกิน */
    .modal-header, .btn, .btn-close, .text-center.mt-3, .modal-backdrop { 
        display: none !important; 
    }
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
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
    container.innerHTML = `
        <div class="text-center py-5 text-white">
            <div class="spinner-border text-warning mb-2"></div>
            <br><span class="fw-bold">กำลังเตรียมบัตรความสุข คณะเสียงอีสาน...</span>
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
                // ใช้ QR Server ที่เสถียร
                const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=TICKET_ID:${ticketSerial}`;
                const logoPath = "uploads/logo_1765163792.png";

                container.innerHTML += `
                    <div class="ticket-horizontal" style="display: flex !important; flex-direction: row !important;border: 2px solid #ffd700; background: #fff; margin-bottom: 20px;">
                        <div class="ticket-main" style="flex: 3; padding: 15px; position: relative;">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div class="ticket-logo-box">
                                    <img src="${logoPath}" alt="Logo" style="height: 45px; filter: drop-shadow(1px 1px 2px rgba(0,0,0,0.1));">
                                </div>
                                <div class="text-end">
                                    <span class="badge bg-danger mb-1" style="font-size: 0.7rem; letter-spacing: 1px; border-radius: 50px;">บัตรเข้าชมการแสดง (E-TICKET)</span>
                                    <div class="small fw-bold text-dark">No. #${ticketSerial}</div>
                                </div>
                            </div>

                            <div class="event-name" style="color: #b30000; font-size: 1.5rem; font-weight: 800; margin: 10px 0; line-height: 1.2;">
                                ${item.product_name}
                            </div>
                            
                            <div class="row align-items-end mt-3">
                                <div class="col-7">
                                    <div style="font-size: 0.7rem; color: #666; text-transform: uppercase;">ผู้ซื้อบัตร / บัญชีผู้ใช้งาน</div>
                                    <div class="fw-bold fs-5 text-dark" style="font-family: 'Kanit', sans-serif;"><?php echo htmlspecialchars($user['first_name'].' '.$user['last_name']); ?></div>
                                    <div class="mt-2">
                                        <span class="btn btn-sm btn-primary" style="background-color: #004aad; border: none; border-radius: 50px; font-size: 0.75rem; padding: 4px 15px;">
                                            <i class="bi bi-clock me-1"></i> ประตูเปิด : 17:00 น. เป็นต้นไป
                                        </span>
                                    </div>
                                </div>
                                <div class="col-5 text-end">
                                    <div class="small fw-bold text-success"><i class="bi bi-check-circle-fill"></i> ชำระเงินเรียบร้อยแล้ว</div>
                                    <div style="font-size: 0.6rem; color: #cc0000; font-style: italic; margin-top: 5px;">
                                        *กรุณาเตรียม QR Code นี้ไว้สำหรับสแกนหน้างาน
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="ticket-stub" style="flex: 1; background: linear-gradient(180deg, #ffda44 0%, #ffd700 100%); padding: 15px; display: flex; flex-direction: column; align-items: center; justify-content: center; border-left: 2px dashed #333; position: relative;">
                            
                            <img src="${logoPath}" style="height: 25px; position: absolute; top: 10px; opacity: 0.8;">

                            <div class="qr-wrapper bg-white p-2 mb-2 rounded shadow-sm" style="margin-top: 15px;">
                                <img src="${qrUrl}" style="width: 100px; height: 100px; display: block;" onerror="this.src='https://via.placeholder.com/100?text=QR+Error'">
                            </div>
                            
                            <div class="fw-bold text-dark" style="font-size: 0.8rem;">ใบที่ ${ticketIndex} / ${item.quantity}</div>
                            <div class="text-dark fw-bold" style="font-size: 0.5rem; opacity: 0.7; letter-spacing: 0.5px;">ORDER #${orderNum}</div>
                        </div>
                    </div>
                `;
                ticketIndex++;
            }
        });
    } catch (error) {
        container.innerHTML = '<div class="alert alert-danger">ไม่สามารถโหลดข้อมูลบัตรได้ กรุณาลองใหม่อีกครั้ง</div>';
    }
}
</script>

<?php if (!empty($msg)): ?>
<script>Swal.fire({ icon: '<?php echo $msg_type; ?>', title: 'แจ้งเตือน', text: '<?php echo $msg; ?>' });</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>