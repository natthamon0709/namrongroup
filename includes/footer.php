<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row gy-4">
            <div class="col-lg-4">
                <h5 class="text-white mb-3 fw-bold">
                    <i class="bi bi-bag-heart-fill text-primary me-2"></i>
                    <?php echo isset($settings['site_name']) ? htmlspecialchars($settings['site_name']) : 'Namrong Group'; ?>
                </h5>
                <p class="small">
                    <?php echo isset($settings['meta_description']) ? htmlspecialchars($settings['meta_description']) : 'ร้านค้าออนไลน์ที่คุณไว้วางใจได้ สินค้าคุณภาพดี ส่งไว พร้อมบริการหลังการขายที่เป็นเลิศ'; ?>
                </p>

                <!-- ข้อมูลติดต่อ -->
                <div class="mt-3 small">
                    <?php if (!empty($settings['contact_address'])): ?>
                        <p class="mb-1"><i class="bi bi-geo-alt me-2 text-primary"></i><?php echo nl2br(htmlspecialchars($settings['contact_address'])); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($settings['contact_phone'])): ?>
                        <p class="mb-1"><i class="bi bi-telephone me-2 text-primary"></i><?php echo htmlspecialchars($settings['contact_phone']); ?></p>
                    <?php endif; ?>

                    <?php if (!empty($settings['contact_email'])): ?>
                        <p class="mb-1"><i class="bi bi-envelope me-2 text-primary"></i><?php echo htmlspecialchars($settings['contact_email']); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Social Media -->
                <div class="d-flex gap-3 mt-4">
                    <?php if (!empty($settings['social_facebook'])): ?>
                        <a href="<?php echo htmlspecialchars($settings['social_facebook']); ?>" target="_blank" class="fs-4"><i class="bi bi-facebook"></i></a>
                    <?php endif; ?>

                    <?php if (!empty($settings['social_line'])): ?>
                        <a href="#" class="fs-4" title="Line: <?php echo htmlspecialchars($settings['social_line']); ?>"><i class="bi bi-line"></i></a>
                    <?php endif; ?>

                    <!-- Default Social Icon (ถ้าไม่มีข้อมูล) -->
                    <?php if (empty($settings['social_facebook']) && empty($settings['social_line'])): ?>
                        <a href="#" class="fs-4"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="fs-4"><i class="bi bi-instagram"></i></a>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-2 col-6">
                <h6 class="text-white text-uppercase mb-3 fw-bold">ช้อปปิ้ง</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="products">สินค้าทั้งหมด</a></li>
                    <li class="mb-2"><a href="products?sort=new">สินค้ามาใหม่</a></li>
                    <li class="mb-2"><a href="products?sort=promotion">โปรโมชั่น</a></li>
                    <li class="mb-2"><a href="#">แบรนด์</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-6">
                <h6 class="text-white text-uppercase mb-3 fw-bold">บริการลูกค้า</h6>
                <ul class="list-unstyled small">
                    <li class="mb-2"><a href="#">ติดตามคำสั่งซื้อ</a></li>
                    <li class="mb-2"><a href="#">วิธีการชำระเงิน</a></li>
                    <li class="mb-2"><a href="#">การจัดส่งสินค้า</a></li>
                    <li class="mb-2"><a href="#">นโยบายการคืนสินค้า</a></li>
                    <li class="mb-2"><a href="#">ติดต่อเรา</a></li>
                </ul>
            </div>

            <div class="col-lg-4">
                <h6 class="text-white text-uppercase mb-3 fw-bold">ช่องทางการชำระเงิน</h6>
                <div class="d-flex gap-2 flex-wrap">
                    <span class="bg-white p-1 rounded text-dark px-2 small fw-bold">VISA</span>
                    <span class="bg-white p-1 rounded text-dark px-2 small fw-bold">MasterCard</span>
                    <span class="bg-white p-1 rounded text-dark px-2 small fw-bold">PromptPay</span>
                    <span class="bg-white p-1 rounded text-dark px-2 small fw-bold">COD</span>
                </div>

                <?php if (!empty($settings['bank_name'])): ?>
                    <div class="mt-4 small text-muted">
                        <h6 class="text-white mb-2 fw-bold">โอนเงินผ่านธนาคาร</h6>
                        <p class="mb-1"><?php echo htmlspecialchars($settings['bank_name']); ?></p>
                        <p class="mb-0">เลขที่: <?php echo htmlspecialchars($settings['bank_acc_num']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="text-center pt-4 mt-4 border-top border-secondary small">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo isset($settings['site_name']) ? htmlspecialchars($settings['site_name']) : 'Namrong Group'; ?>. All rights reserved.</p>
        </div>
    </div>
</footer>

<script>
    $(function() {
        $('.summernote').summernote();
    });
</script>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SweetAlert2 CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>