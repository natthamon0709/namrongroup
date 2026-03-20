<?php
// กำหนดค่าตัวแปรสำหรับหน้านี้
$page_title = "จองบัตร - Namrong Group";
$active_page = "home";

// เรียกใช้ส่วนหัวจากโฟลเดอร์ includes
include 'includes/header.php';
?>

<style>
    :root {
        --ticket-primary: #ff3e6c; /* สีชมพู-แดงแบบเว็บขายบัตร */
        --ticket-dark: #1a1a1a;
    }

    /* Hero Section - ปรับให้ภาพเต็มจอและดูพรีเมียม */
    .hero-banner {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        transition: transform 0.3s ease;
    }
    
    /* Card Style - ปรับให้เหมือนบัตรเข้าชมงาน */
    .event-card {
        border: none;
        border-radius: 15px;
        transition: all 0.3s ease;
        overflow: hidden;
        background: #fff;
    }

    .event-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 15px 35px rgba(0,0,0,0.15);
    }

    .event-img-wrapper {
        position: relative;
        height: 220px;
        overflow: hidden;
    }

    .event-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    /* สถานะบัตร (มุมภาพ) */
    .badge-status {
        position: absolute;
        top: 15px;
        left: 15px;
        padding: 5px 15px;
        border-radius: 50px;
        font-weight: 600;
        font-size: 0.8rem;
        z-index: 2;
    }

    /* รายละเอียดวันเวลา */
    .event-date-box {
        background: var(--ticket-primary);
        color: white;
        padding: 8px;
        border-radius: 8px;
        text-align: center;
        min-width: 60px;
    }

    .btn-book {
        background: var(--ticket-primary);
        color: white;
        border-radius: 10px;
        font-weight: 600;
        border: none;
        padding: 10px;
        transition: 0.3s;
    }

    .btn-book:hover {
        background: #e3355f;
        color: white;
    }

    .text-limit-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<header class="py-4">
    <div class="container">
        <div class="hero-banner">
            <img src="uploads/banner.png" alt="Featured Event" class="w-100 img-fluid">
        </div>
    </div>
</header>

<section id="events" class="py-5">
    <div class="container">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <div>
                <h6 class="text-uppercase text-danger fw-bold mb-1">Upcoming Events</h6>
                <h2 class="fw-bold m-0">อิสานสานสัมพันธ์​ล้านนา</h2>
            </div>
            <a href="products" class="btn btn-outline-dark btn-sm rounded-pill px-4">ดูอีเวนต์ทั้งหมด</a>
        </div>

        <div class="row g-4">
            <?php
            // ดึงข้อมูลบัตร/อีเวนต์ (ใช้ Logic เดิม)
            $sql_prod = "SELECT * FROM products ORDER BY is_featured DESC, id DESC LIMIT 4";
            $result_prod = $conn->query($sql_prod);

            if ($result_prod->num_rows > 0):
                while ($prod = $result_prod->fetch_assoc()):
                    $img = !empty($prod['image']) ? $prod['image'] : "https://placehold.co/600x400/png?text=No+Image";
            ?>
                    <div class="col-12 col-md-6 col-lg-3">
                        <div class="card event-card h-100 shadow-sm">
                            
                            <?php if ($prod['is_bestseller']): ?>
                                <span class="badge-status bg-warning text-dark">Selling Fast!</span>
                            <?php else: ?>
                                <span class="badge-status bg-dark text-white">Available</span>
                            <?php endif; ?>

                            <div class="event-img-wrapper">
                                <a href="product-detail?id=<?php echo $prod['id']; ?>">
                                    <img src="<?php echo $img; ?>" class="event-img" alt="<?php echo htmlspecialchars($prod['name']); ?>">
                                </a>
                            </div>

                            <div class="card-body p-4">
                                <div class="d-flex gap-3 mb-3">
                                    <!-- <div class="event-date-box">
                                        <div class="small text-uppercase">Mar</div>
                                        <div class="fw-bold fs-5">23</div>
                                    </div> -->
                                    <div>
                                        <h5 class="card-title fw-bold mb-1 text-limit-2">
                                            <a href="product-detail?id=<?php echo $prod['id']; ?>" class="text-decoration-none text-dark">
                                                <?php echo htmlspecialchars($prod['name']); ?>
                                            </a>
                                        </h5>
                                        <p class="small text-muted mb-0"><i class="bi bi-geo-alt-fill me-1"></i> BITEC Bangna, TH</p>
                                    </div>
                                </div>

                                <hr class="my-3 opacity-25">

                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <p class="small text-muted mb-0">ราคาเริ่มต้น</p>
                                        <?php if ($prod['sale_price'] > 0): ?>
                                            <span class="fw-bold fs-5 text-danger">฿<?php echo number_format($prod['sale_price']); ?></span>
                                        <?php else: ?>
                                            <span class="fw-bold fs-5 text-dark">฿<?php echo number_format($prod['price']); ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <a href="product-detail?id=<?php echo $prod['id']; ?>" class="btn btn-book px-4">
                                        จองบัตร
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile;
            else: ?>
                <div class="col-12 text-center py-5">
                    <img src="https://cdn-icons-png.flaticon.com/512/1437/1437185.png" width="80" class="opacity-25 mb-3">
                    <p class="text-muted">ยังไม่มีรายการเปิดจองในขณะนี้</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="bg-dark text-white p-5 rounded-4 shadow-lg overflow-hidden position-relative">
            <div class="row align-items-center position-relative" style="z-index: 2;">
                <div class="col-lg-8">
                    <h2 class="fw-bold">ติดตามข่าวสารการเปิดจองบัตรก่อนใคร</h2>
                    <p class="text-white-50 mb-0">รับการแจ้งเตือนอีเวนต์สำคัญและโปรโมชั่นพิเศษผ่านทางอีเมลของคุณ</p>
                </div>
                <div class="col-lg-4 text-lg-end mt-4 mt-lg-0">
                    <div class="input-group">
                        <input type="email" class="form-control border-0 px-4" placeholder="อีเมลของคุณ">
                        <button class="btn btn-danger px-4">ติดตาม</button>
                    </div>
                </div>
            </div>
            <div style="position:absolute; right:-50px; bottom:-50px; width:200px; height:200px; background:var(--ticket-primary); filter:blur(100px); opacity:0.3;"></div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>