<?php
session_start();
include '../config/connect.php';
$active_menu = "dashboard";

// ตรวจสอบสิทธิ์ Admin
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login");
    exit();
}

// --- [ดึงข้อมูลสถิติเดิมคงไว้ตาม Logic ของคุณ] ---
$sales_query = "SELECT SUM(total_amount) FROM orders WHERE status IN ('paid', 'shipped', 'completed')";
$total_sales_all_time = $conn->query($sales_query)->fetch_row()[0] ?? 0;
$pending_payment_query = "SELECT COUNT(id) FROM orders WHERE status = 'pending'";
$pending_payment_count = $conn->query($pending_payment_query)->fetch_row()[0] ?? 0;
$ready_to_ship_query = "SELECT COUNT(id) FROM orders WHERE status = 'paid'";
$ready_to_ship_count = $conn->query($ready_to_ship_query)->fetch_row()[0] ?? 0;
$low_stock_threshold = 10;
$low_stock_query = "SELECT COUNT(id) FROM products WHERE stock_quantity <= $low_stock_threshold";
$low_stock_count = $conn->query($low_stock_query)->fetch_row()[0] ?? 0;

// Best Sellers & Recent Orders
$best_sellers_sql = "SELECT p.name, p.id, p.image, SUM(oi.quantity) as total_sold FROM order_items oi JOIN products p ON oi.product_id = p.id GROUP BY p.id ORDER BY total_sold DESC LIMIT 5";
$best_sellers_result = $conn->query($best_sellers_sql);
$recent_orders_sql = "SELECT id, order_number, total_amount, shipping_name, status, created_at FROM orders ORDER BY created_at DESC LIMIT 5";
$recent_orders_result = $conn->query($recent_orders_sql);

// Chart Logic
$report_year = isset($_GET['chart_year']) ? intval($_GET['chart_year']) : date('Y');
$sales_chart_data = ['labels' => [], 'data' => []];
for ($month = 1; $month <= 12; $month++) {
    if ($report_year == date('Y') && $month > date('n')) break;
    $month_name = date("M", strtotime("$report_year-$month-01"));
    $start = date("$report_year-$month-01 00:00:00");
    $end = date("Y-m-t 23:59:59", strtotime("$report_year-$month-01"));
    $s_query = "SELECT SUM(total_amount) FROM orders WHERE status IN ('paid', 'shipped', 'completed') AND created_at BETWEEN '$start' AND '$end'";
    $sales = $conn->query($s_query)->fetch_row()[0] ?? 0;
    $sales_chart_data['labels'][] = $month_name;
    $sales_chart_data['data'][] = round($sales, 2);
}

include 'includes/header.php';
?>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #FF416C 0%, #FF4B2B 100%);
        --info-gradient: linear-gradient(135deg, #1e3c72 0%, #2a5298 100%);
        --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
        --warning-gradient: linear-gradient(135deg, #f09819 0%, #edde5d 100%);
    }

    body { background-color: #f4f7f6; font-family: 'Kanit', sans-serif; }
    .content-wrapper { background: transparent; padding: 20px; }
    
    /* Modern Stat Boxes */
    .stat-card {
        border: none;
        border-radius: 20px;
        transition: transform 0.3s;
        overflow: hidden;
        color: white;
        box-shadow: 0 10px 20px rgba(0,0,0,0.05);
    }
    .stat-card:hover { transform: translateY(-5px); }
    .stat-card .inner { padding: 25px; }
    .stat-card h3 { font-size: 1.8rem; font-weight: 700; margin-bottom: 5px; }
    .stat-card p { opacity: 0.9; font-weight: 300; }
    .stat-card .icon { position: absolute; right: 20px; top: 20px; font-size: 3rem; opacity: 0.2; }
    
    .bg-custom-sales { background: var(--info-gradient); }
    .bg-custom-pending { background: var(--warning-gradient); }
    .bg-custom-ship { background: var(--primary-gradient); }
    .bg-custom-stock { background: var(--success-gradient); }
    .bg-custom-stock.danger { background: linear-gradient(135deg, #cb2d3e 0%, #ef473a 100%); }

    /* Card Styling */
    .card { border: none; border-radius: 20px; box-shadow: 0 5px 15px rgba(0,0,0,0.03); margin-bottom: 30px; }
    .card-header { background: transparent; border-bottom: 1px solid #f0f0f0; padding: 20px; }
    .card-title { font-weight: 600; color: #333; }
    
    /* Table Styling */
    .table thead th { border: none; color: #999; font-weight: 400; font-size: 0.85rem; text-transform: uppercase; }
    .table td { vertical-align: middle; padding: 15px 10px; border-top: 1px solid #f9f9f9; }
    
    /* Badge Styling */
    .badge-modern { padding: 6px 12px; border-radius: 10px; font-weight: 400; font-size: 0.8rem; }
    
    /* Product List Styling */
    .product-img-modern { width: 45px; height: 45px; border-radius: 12px; object-fit: cover; }
</style>

<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <h1 class="font-weight-bold mb-4">ยินดีต้อนรับ, ผู้ดูแลระบบ 👋</h1>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-3 col-6">
                    <div class="stat-card bg-custom-sales mb-4">
                        <div class="inner">
                            <h3>฿<?php echo number_format($total_sales_all_time); ?></h3>
                            <p>ยอดขายรวมทั้งหมด</p>
                        </div>
                        <div class="icon"><i class="fas fa-chart-line"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="stat-card bg-custom-pending mb-4">
                        <div class="inner">
                            <h3><?php echo number_format($pending_payment_count); ?></h3>
                            <p>รอตรวจสอบการชำระ</p>
                        </div>
                        <div class="icon"><i class="fas fa-wallet"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="stat-card bg-custom-ship mb-4">
                        <div class="inner">
                            <h3><?php echo number_format($ready_to_ship_count); ?></h3>
                            <p>บัตรที่พร้อมส่ง/ใช้งาน</p>
                        </div>
                        <div class="icon"><i class="fas fa-ticket-alt"></i></div>
                    </div>
                </div>
                <div class="col-lg-3 col-6">
                    <div class="stat-card <?php echo ($low_stock_count > 0) ? 'bg-custom-stock danger' : 'bg-custom-stock'; ?> mb-4">
                        <div class="inner">
                            <h3><?php echo number_format($low_stock_count); ?></h3>
                            <p>สินค้าใกล้หมดสต็อก</p>
                        </div>
                        <div class="icon"><i class="fas fa-cubes"></i></div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title"><i class="fas fa-chart-area mr-2 text-primary"></i>แนวโน้มยอดขาย</h3>
                            <form action="" method="GET" class="ml-auto">
                                <select name="chart_year" class="form-control-sm border-0 bg-light" onchange="this.form.submit()">
                                    <?php for($y=date('Y'); $y>=date('Y')-2; $y--): ?>
                                        <option value="<?=$y?>" <?=$report_year==$y?'selected':''?>>ปี <?=$y+543?></option>
                                    <?php endfor; ?>
                                </select>
                            </form>
                        </div>
                        <div class="card-body">
                            <canvas id="modernSalesChart" height="300"></canvas>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title"><i class="fas fa-shopping-basket mr-2 text-danger"></i>ออเดอร์ล่าสุด</h3>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table mb-0">
                                    <tbody>
                                        <?php while($order = $recent_orders_result->fetch_assoc()): 
                                            $st_color = ($order['status']=='paid') ? 'success' : (($order['status']=='pending') ? 'warning' : 'secondary');
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="d-block font-weight-bold">#<?=$order['order_number']?></span>
                                                <small class="text-muted"><?=htmlspecialchars($order['shipping_name'])?></small>
                                            </td>
                                            <td class="text-right">
                                                <span class="d-block">฿<?=number_format($order['total_amount'])?></span>
                                                <span class="badge badge-<?=$st_color?>-light badge-modern"><?=ucfirst($order['status'])?></span>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer bg-white border-0 text-center">
                            <a href="orders" class="btn btn-light btn-sm btn-block rounded-pill">ดูทั้งหมด</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">🏆 สินค้าขายดี 5 อันดับแรก</h3>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php while($row = $best_sellers_result->fetch_assoc()): ?>
                                <li class="list-group-item d-flex align-items-center border-0 py-3">
                                    <img src="../<?=$row['image']?>" class="product-img-modern mr-3 shadow-sm">
                                    <div class="flex-grow-1">
                                        <h6 class="mb-0 font-weight-bold text-dark"><?=htmlspecialchars($row['name'])?></h6>
                                        <small class="text-muted">ขายไปแล้ว <?=number_format($row['total_sold'])?> ชิ้น</small>
                                    </div>
                                    <span class="badge badge-primary badge-pill px-3">อันดับ 1</span>
                                </li>
                                <?php endwhile; ?>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">⚠️ สินค้าสต็อกต่ำ</h3>
                        </div>
                        <div class="card-body p-0">
                            <?php 
                            $low_stock_result = $conn->query("SELECT * FROM products WHERE stock_quantity <= $low_stock_threshold ORDER BY stock_quantity ASC LIMIT 5");
                            while($p = $low_stock_result->fetch_assoc()): ?>
                            <div class="d-flex align-items-center p-3 border-bottom">
                                <div class="mr-3 text-center" style="width: 40px;">
                                    <span class="h5 font-weight-bold text-danger"><?=$p['stock_quantity']?></span>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-0"><?=htmlspecialchars($p['name'])?></h6>
                                    <div class="progress mt-1" style="height: 4px;">
                                        <div class="progress-bar bg-danger" style="width: <?=$p['stock_quantity']*10?>%"></div>
                                    </div>
                                </div>
                                <a href="product_form.php?id=<?=$p['id']?>" class="btn btn-sm btn-light ml-3"><i class="fas fa-plus"></i> เติม</a>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const ctx = document.getElementById('modernSalesChart').getContext('2d');
    const gradient = ctx.createLinearGradient(0, 0, 0, 400);
    gradient.addColorStop(0, 'rgba(30, 60, 114, 0.4)');
    gradient.addColorStop(1, 'rgba(30, 60, 114, 0.0)');

    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($sales_chart_data['labels']); ?>,
            datasets: [{
                label: 'ยอดขายรายเดือน',
                data: <?php echo json_encode($sales_chart_data['data']); ?>,
                borderColor: '#1e3c72',
                borderWidth: 3,
                backgroundColor: gradient,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#fff',
                pointBorderColor: '#1e3c72',
                pointRadius: 4
            }]
        },
        options: {
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { display: false }, ticks: { callback: v => '฿' + v.toLocaleString() } },
                x: { grid: { display: false } }
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>