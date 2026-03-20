<?php
session_start();
include 'includes/session.php';

include '../config/connect.php';

// Check Admin Access (Optional but recommended)
// if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') { exit('Access Denied'); }

// --- Logic Filter (เหมือน orders.php) ---
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$sort_by = isset($_GET['sort_by']) ? $_GET['sort_by'] : 'created_at';
$sort_order = isset($_GET['sort_order']) ? $_GET['sort_order'] : 'DESC';

$where = "1=1";

if (!empty($search)) {
    $search_sql = $conn->real_escape_string($search);
    $where .= " AND (order_number LIKE '%$search_sql%' OR shipping_name LIKE '%$search_sql%' OR shipping_phone LIKE '%$search_sql%')";
}

if (!empty($status)) {
    $status_sql = $conn->real_escape_string($status);
    $where .= " AND status = '$status_sql'";
}

if (!empty($start_date) && !empty($end_date)) {
    $start = $conn->real_escape_string($start_date) . " 00:00:00";
    $end = $conn->real_escape_string($end_date) . " 23:59:59";
    $where .= " AND created_at BETWEEN '$start' AND '$end'";
}

$allowed_sort_cols = ['created_at', 'total_amount', 'order_number'];
if (!in_array($sort_by, $allowed_sort_cols)) {
    $sort_by = 'created_at';
}
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

$sql = "SELECT * FROM orders WHERE $where ORDER BY $sort_by $sort_order";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <title>รายงานคำสั่งซื้อ</title>
    <!-- Google Fonts (Kanit) -->
    <link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Kanit', sans-serif;
            font-size: 12px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th,
        td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }

        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .header {
            text-align: center;
            margin-bottom: 20px;
        }

        h2 {
            margin: 0;
        }

        @media print {
            .no-print {
                display: none;
            }
        }
    </style>
</head>

<body onload="window.print()">

    <div class="header">
        <h2>รายงานคำสั่งซื้อ</h2>
        <p>
            พิมพ์เมื่อ: <?php echo date('d/m/Y H:i'); ?> |
            เงื่อนไข: <?php
                        $status_label = "ทุกสถานะ";
                        if ($status == 'pending') $status_label = 'รอตรวจสอบ';
                        elseif ($status == 'paid') $status_label = 'ชำระแล้ว';
                        elseif ($status == 'shipped') $status_label = 'จัดส่งแล้ว';
                        elseif ($status == 'completed') $status_label = 'สำเร็จ';
                        elseif ($status == 'cancelled') $status_label = 'ยกเลิก';
                        echo "สถานะ: " . $status_label;
                        ?>
            <?php echo ($start_date) ? "| วันที่: $start_date ถึง $end_date" : ""; ?>
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 12%">วันที่</th>
                <th style="width: 13%">เลขที่คำสั่งซื้อ</th>
                <th style="width: 23%">ลูกค้า</th>
                <th style="width: 22%">รายการสินค้า</th>
                <th style="width: 10%">สถานะ</th>
                <th style="width: 10%">วิธีชำระ</th>
                <th style="width: 10%" class="text-right">ยอดรวม</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $total_sum = 0;
            if ($result->num_rows > 0):
                while ($row = $result->fetch_assoc()):
                    $total_sum += $row['total_amount'];

                    // แปลงสถานะเป็นภาษาไทย
                    $status_text = $row['status'];
                    switch ($row['status']) {
                        case 'pending':
                            $status_text = 'รอตรวจสอบ';
                            break;
                        case 'paid':
                            $status_text = 'ชำระแล้ว';
                            break;
                        case 'shipped':
                            $status_text = 'จัดส่งแล้ว';
                            break;
                        case 'completed':
                            $status_text = 'สำเร็จ';
                            break;
                        case 'cancelled':
                            $status_text = 'ยกเลิก';
                            break;
                    }

                    // แปลงวิธีชำระเงิน
                    $pay_method = $row['payment_method'];
                    if ($pay_method == 'promptpay') $pay_method = 'PromptPay';
                    elseif ($pay_method == 'bank_transfer') $pay_method = 'โอนเงิน';
                    elseif ($pay_method == 'cod') $pay_method = 'COD';

                    // ดึงรายการสินค้า
                    $items_str = "";
                    $order_id = $row['id'];
                    $items_sql = "SELECT product_name, quantity FROM order_items WHERE order_id = $order_id";
                    $items_res = $conn->query($items_sql);
                    if ($items_res->num_rows > 0) {
                        $items_list = [];
                        while ($item = $items_res->fetch_assoc()) {
                            $items_list[] = "- " . $item['product_name'] . " (x" . $item['quantity'] . ")";
                        }
                        $items_str = implode("<br>", $items_list);
                    }
            ?>
                    <tr>
                        <td><?php echo date('d/m/Y H:i', strtotime($row['created_at'])); ?></td>
                        <td><?php echo $row['order_number']; ?></td>
                        <td>
                            <?php echo htmlspecialchars($row['shipping_name']); ?><br>
                            <small>โทร: <?php echo htmlspecialchars($row['shipping_phone']); ?></small><br>
                            <small>ที่อยู่: <?php echo nl2br(htmlspecialchars($row['shipping_address'])); ?></small>
                        </td>
                        <td><small><?php echo $items_str; ?></small></td>
                        <td><?php echo $status_text; ?></td>
                        <td><?php echo $pay_method; ?></td>
                        <td class="text-right"><?php echo number_format($row['total_amount'], 2); ?></td>
                    </tr>
                <?php endwhile;
            else: ?>
                <tr>
                    <td colspan="7" class="text-center">ไม่พบข้อมูล</td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-right" style="font-weight: bold;">รวมทั้งสิ้น</td>
                <td class="text-right" style="font-weight: bold;"><?php echo number_format($total_sum, 2); ?></td>
            </tr>
        </tfoot>
    </table>

</body>

</html>