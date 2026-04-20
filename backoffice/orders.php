<?php
session_start();
include 'includes/session.php';
include '../config/connect.php';

// --- Logic Quick Update Status (คงเดิม) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'quick_update_status') {
    $id = $_POST['order_id'];
    $new_status = $_POST['new_status'];

    if ($new_status == 'shipped') {
        $tracking = $_POST['tracking_number'];
        $carrier = $_POST['carrier'];
        $stmt = $conn->prepare("UPDATE orders SET status = ?, tracking_number = ?, carrier = ? WHERE id = ?");
        $stmt->bind_param("sssi", $new_status, $tracking, $carrier, $id);
    } else {
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
        $_SESSION['success'] = "อัปเดตสถานะคำสั่งซื้อ #$id เรียบร้อยแล้ว";
    } else {
        $_SESSION['error'] = "เกิดข้อผิดพลาดในการอัปเดต";
    }
    header("Location: orders");
    exit();
}

$active_menu = "orders";
include 'includes/header.php';

// --- Logic การค้นหาและกรอง (คงเดิม) ---
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$where = "1=1";
if (!empty($search)) {
    $search_sql = $conn->real_escape_string($search);
    $where .= " AND (order_number LIKE '%$search_sql%' OR shipping_name LIKE '%$search_sql%')";
}
if (!empty($status)) {
    $status_sql = $conn->real_escape_string($status);
    $where .= " AND status = '$status_sql'";
}

$sql = "SELECT * FROM orders WHERE $where ORDER BY created_at DESC";
$result = $conn->query($sql);
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        --surface-color: #ffffff;
        --bg-color: #f3f4f6;
    }

    body { background-color: var(--bg-color); font-family: 'Kanit', sans-serif; }
    .content-wrapper { background: transparent; padding: 20px; }
    
    /* Modern Card */
    .card { 
        border: none; 
        border-radius: 20px; 
        box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
        overflow: hidden;
        margin-bottom: 2rem;
    }

    /* Table UI */
    .table { margin-bottom: 0; }
    .table thead th { 
        background-color: #f8fafc; 
        border-bottom: 2px solid #e2e8f0;
        text-transform: uppercase;
        font-size: 0.75rem;
        letter-spacing: 0.05em;
        padding: 15px 20px;
    }
    .table tbody td { padding: 18px 20px; vertical-align: middle; border-bottom: 1px solid #f1f5f9; }

    /* Modern Badges & Select */
    .status-select {
        border: none !important;
        border-radius: 12px !important;
        font-weight: 600 !important;
        padding: 6px 12px !important;
        font-size: 0.8rem;
        cursor: pointer;
        transition: all 0.2s;
    }
    .status-pending { background-color: #fef3c7; color: #92400e; }
    .status-paid { background-color: #dcfce7; color: #15803d; }
    .status-shipped { background-color: #e0e7ff; color: #4338ca; }
    .status-completed { background-color: #f3e8ff; color: #7e22ce; }
    .status-cancelled { background-color: #fee2e2; color: #b91c1c; }

    /* Action Buttons */
    .btn-action {
        width: 38px;
        height: 38px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        margin-left: 5px;
        transition: transform 0.2s;
    }
    .btn-action:hover { transform: translateY(-2px); }
    .btn-view { background: #eff6ff; color: #2563eb; }
    .btn-ticket { background: #faf5ff; color: #9333ea; }
    /* เพิ่มปุ่ม Slip */
    .btn-slip { background: #fff7ed; color: #ea580c; border: none; }

    /* ===== NEW TICKET HORIZONTAL ===== */
    /* ตกแต่งส่วนรอยปรุให้เนียนขึ้น */
        .ticket-horizontal::before, .ticket-horizontal::after {
            background: #1a1a1a !important; /* เปลี่ยนตามสีพื้นหลัง Modal ของคุณ */
            border: 2px solid #ffd700;
        }

        /* ตกแต่งส่วนหางตั๋ว */
        /* .ticket-stub {
            background: linear-gradient(180deg, #ffda44 0%, #ffd700 100%);
        } */
        /* Ticket Layout แนวนอน */
        #ticketContainer {
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
            /* background: var(--concert-gold); */
            padding: 15px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            text-align: center;
            color: #000;
            background: linear-gradient(180deg, #ffda44 0%, #ffd700 100%);
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

<div class="content-wrapper">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="font-weight-bold text-dark">📦 จัดการคำสั่งซื้อ</h2>
        <div class="text-muted small"><?php echo date('d M Y'); ?></div>
    </div>

    <div class="card p-4">
        <form action="" method="GET" class="row align-items-end">
            <div class="col-md-4">
                <label class="small font-weight-bold">ค้นหารายการ</label>
                <input type="text" name="search" class="form-control rounded-pill border-light bg-light" placeholder="เลขที่สั่งซื้อ หรือชื่อลูกค้า..." value="<?php echo $search; ?>">
            </div>
            <div class="col-md-3">
                <label class="small font-weight-bold">สถานะ</label>
                <select name="status" class="form-control rounded-pill border-light bg-light">
                    <option value="">ทั้งหมด</option>
                    <option value="pending" <?php echo $status=='pending'?'selected':''; ?>>รอตรวจสอบ</option>
                    <option value="paid" <?php echo $status=='paid'?'selected':''; ?>>ชำระแล้ว</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary btn-block rounded-pill font-weight-bold shadow-sm">
                    <i class="fas fa-search mr-2"></i> กรองข้อมูล
                </button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>เลขที่สั่งซื้อ</th>
                        <th>ลูกค้า</th>
                        <th>ยอดสุทธิ</th>
                        <th>สถานะ</th>
                        <th class="text-right">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result->num_rows > 0): while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td class="font-weight-bold text-primary">#<?php echo $row['order_number']; ?></td>
                        <td>
                            <div class="font-weight-bold"><?php echo htmlspecialchars($row['shipping_name']); ?></div>
                            <div class="small text-muted"><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></div>
                        </td>
                        <td class="font-weight-bold">฿<?php echo number_format($row['total_amount'], 2); ?></td>
                        <td>
                            <form action="" method="POST">
                                <input type="hidden" name="action" value="quick_update_status">
                                <input type="hidden" name="order_id" value="<?php echo $row['id']; ?>">
                                <select name="new_status" class="status-select status-<?php echo $row['status']; ?>" onchange="handleStatusChange(this)">
                                    <option value="pending" <?php echo $row['status']=='pending'?'selected':''; ?>>รอตรวจสอบ</option>
                                    <option value="paid" <?php echo $row['status']=='paid'?'selected':''; ?>>ชำระแล้ว</option>
                                    <option value="shipped" <?php echo $row['status']=='shipped'?'selected':''; ?>>จัดส่งแล้ว</option>
                                    <option value="completed" <?php echo $row['status']=='completed'?'selected':''; ?>>สำเร็จ</option>
                                    <option value="cancelled" <?php echo $row['status']=='cancelled'?'selected':''; ?>>ยกเลิก</option>
                                </select>
                            </form>
                        </td>
                        <td class="text-right">
                            <?php if(!empty($row['payment_slip'])): ?>
                            <button type="button" class="btn-action btn-slip" onclick="showSlip('../uploads/slips/<?php echo $row['payment_slip']; ?>')" title="ดูสลิป">
                                <i class="fas fa-file-invoice-dollar"></i>
                            </button>
                            <?php endif; ?>

                            <a href="order_detail.php?id=<?php echo $row['id']; ?>" class="btn-action btn-view" title="ดูรายละเอียด">
                                <i class="fas fa-eye"></i>
                            </a>
                            <?php if (in_array($row['status'], ['paid', 'shipped', 'completed'])): ?>
                            <button type="button" class="btn-action btn-ticket border-0" 
                                    onclick="fetchTickets('<?php echo $row['id']; ?>', '<?php echo $row['order_number']; ?>', '<?php echo htmlspecialchars($row['shipping_name']); ?>')" title="พิมพ์ตั๋ว">
                                <i class="fas fa-ticket-alt"></i>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; else: ?>
                    <tr><td colspan="5" class="text-center py-5 text-muted">ไม่พบข้อมูลคำสั่งซื้อ</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="slipModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content" style="border-radius: 20px; border: none;">
            <div class="modal-header border-0">
                <h5 class="modal-title font-weight-bold">หลักฐานการโอนเงิน</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body text-center bg-light">
                <img src="" id="slipImage" class="img-fluid rounded shadow-sm" alt="Payment Slip">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ticketModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content" id="ticketModalContent" style="border-radius: 30px; border: none;">
            <div class="modal-header border-0 p-4">
                <h5 class="modal-title font-weight-bold"><i class="fas fa-qrcode mr-2"></i> E-Tickets</h5>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body bg-light p-4" id="ticketContainer">
                </div>
            <div class="modal-footer border-0 p-4">
                <button type="button" class="btn btn-primary btn-block rounded-pill py-2 font-weight-bold shadow" onclick="window.print()">
                    <i class="fas fa-print mr-2"></i> พิมพ์บัตรทั้งหมด (แยกหน้า)
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="shippingModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 20px;">
            <form action="" method="POST">
                <input type="hidden" name="action" value="quick_update_status">
                <input type="hidden" name="new_status" value="shipped">
                <input type="hidden" name="order_id" id="modal_order_id">
                <div class="modal-header"><h5>ระบุข้อมูลการจัดส่ง</h5></div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>บริษัทขนส่ง</label>
                        <input type="text" name="carrier" class="form-control" required placeholder="เช่น Kerry, Flash...">
                    </div>
                    <div class="form-group">
                        <label>หมายเลขพัสดุ</label>
                        <input type="text" name="tracking_number" class="form-control" required placeholder="Tracking Number">
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="submit" class="btn btn-primary rounded-pill px-4">ยืนยันการจัดส่ง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>

<script>
    function showSlip(src) {
        $('#slipImage').attr('src', src);
        $('#slipModal').modal('show');
    }

    function handleStatusChange(select) {
        if (select.value === 'shipped') {
            $('#modal_order_id').val(select.form.order_id.value);
            $('#shippingModal').modal('show');
        } else {
            select.form.submit();
        }
    }

    function fetchTickets(orderId, orderNum, customerName) {
    const container = document.getElementById('ticketContainer');

    container.innerHTML = `
        <div class="text-center py-5">
            <div class="spinner-border text-warning mb-2"></div>
            <br><span class="fw-bold">กำลังโหลดบัตร...</span>
        </div>
    `;

    $.ajax({
        url: 'get_order_items.php', // ⚠️ ต้องมี API นี้ (ดูด้านล่าง)
        method: 'GET',
        data: { order_id: orderId },
        success: function(response) {
            const items = JSON.parse(response);
            container.innerHTML = '';

            let ticketIndex = 1;

            items.forEach(item => {
                for (let i = 0; i < item.quantity; i++) {

                    const ticketSerial = `${orderNum}-${ticketIndex}`;
                    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=TICKET_ID:${ticketSerial}`;
                    const logoPath = "../uploads/logo_1765163792.png";

                    container.innerHTML += `
                    <div class="ticket-horizontal">

                        <!-- LEFT -->
                        <div class="ticket-main">

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    <img src="${logoPath}" style="height:45px;">
                                </div>

                                <div class="text-end">
                                    <span class="badge bg-danger mb-1" style="font-size:0.7rem;">
                                        บัตรเข้าชมการแสดง (E-TICKET)
                                    </span>
                                    <div class="small fw-bold">No. #${ticketSerial}</div>
                                </div>
                            </div>

                            <div class="event-name" style="color:#b30000; font-size:1.4rem; font-weight:800;">
                                ${item.product_name}
                            </div>

                            <div class="row align-items-end mt-3">
                                <div class="col-7">
                                    <div style="font-size:0.7rem; color:#666;">ผู้ซื้อ</div>
                                    <div class="fw-bold text-dark">
                                        ${customerName}
                                    </div>

                                    <div class="mt-2">
                                        <span class="btn btn-sm btn-primary" style="border-radius:50px; font-size:0.75rem;">
                                            ประตูเปิด : 17:00 น.
                                        </span>
                                    </div>
                                </div>

                                <div class="col-5 text-end">
                                    <div class="small fw-bold text-success">
                                        ✔ ชำระเงินเรียบร้อยแล้ว
                                    </div>
                                    <div style="font-size:0.6rem; color:#cc0000;">
                                        *กรุณาแสดง QR หน้างาน
                                    </div>
                                </div>
                            </div>

                        </div>

                        <!-- RIGHT -->
                        <div class="ticket-stub">

                            <img src="${logoPath}" style="height:25px; position:absolute; top:10px; opacity:0.8;">

                            <div class="qr-wrapper bg-white p-2 mb-2 rounded shadow-sm" style="margin-top:15px;">
                                <img src="${qrUrl}" style="width:100px; height:100px;">
                            </div>

                            <div class="fw-bold text-dark" style="font-size:0.8rem;">
                                ใบที่ ${ticketIndex}
                            </div>

                            <div class="text-dark fw-bold" style="font-size:0.6rem;">
                                ORDER #${orderNum}
                            </div>

                        </div>

                    </div>
                    `;

                    ticketIndex++;
                }
            });

            $('#ticketModal').modal('show');
        },
        error: function() {
            container.innerHTML = `
                <div class="alert alert-danger text-center">
                    โหลดบัตรไม่สำเร็จ
                </div>
            `;
        }
    });
}
</script>