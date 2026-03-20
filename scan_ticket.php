<link href="https://fonts.googleapis.com/css2?family=Kanit:wght@300;400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
<script src="https://unpkg.com/html5-qrcode"></script>

<style>
    :root {
        --primary-gradient: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
        --card-bg: #ffffff;
    }

    body {
        background: #f0f2f5;
        font-family: 'Kanit', sans-serif;
        margin: 0; padding: 20px;
    }

    .scan-card {
        background: var(--card-bg);
        border-radius: 35px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.1);
        max-width: 500px;
        margin: auto;
        overflow: hidden;
    }

    .scan-header {
        background: var(--primary-gradient);
        padding: 30px 20px;
        color: white;
        text-align: center;
    }

    .scan-header img {
        max-height: 50px;
        margin-bottom: 15px;
        background: rgba(255,255,255,0.2);
        padding: 5px;
        border-radius: 12px;
    }

    /* ตกแต่งส่วน Camera */
    #reader {
        border: none !important;
        position: relative;
    }

    /* ปรับแต่งปุ่มภายในของ Library ให้สวย */
    #reader button {
        background: #7c3aed !important;
        color: white !important;
        border: none !important;
        padding: 12px 25px !important;
        border-radius: 15px !important;
        font-weight: 600 !important;
        margin: 15px auto !important;
        cursor: pointer;
        font-family: 'Kanit' !important;
    }

    #reader__dashboard_section_csr button {
        background: #6366f1 !important;
    }

    /* กรอบสแกน 4 มุม (Overlay) - ปรับใหม่ให้ไม่ซ้อน */
    .scan-overlay-custom {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        width: 250px;
        height: 250px;
        pointer-events: none;
        z-index: 100;
    }

    .corner {
        position: absolute;
        width: 35px;
        height: 35px;
        border: 5px solid white;
        border-radius: 4px;
    }
    .tl { top: 0; left: 0; border-right: none; border-bottom: none; }
    .tr { top: 0; right: 0; border-left: none; border-bottom: none; }
    .bl { bottom: 0; left: 0; border-right: none; border-top: none; }
    .br { bottom: 0; right: 0; border-left: none; border-top: none; }

    /* ผลลัพธ์ Glassmorphism */
    .result-overlay {
        position: absolute;
        top: 0; left: 0; width: 100%; height: 100%;
        display: none;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        z-index: 200;
        backdrop-filter: blur(12px);
        text-align: center;
        padding: 20px;
    }
    .result-overlay.show { display: flex; animation: fadeIn 0.4s; }

    @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

    .footer-panel {
        padding: 20px;
        text-align: center;
        color: #64748b;
        font-size: 0.9rem;
    }
    .footer-panel a { color: #4f46e5; text-decoration: none; font-weight: bold; }
</style>

<div class="scan-card">
    <div class="scan-header">
        <img src="uploads/logo_1765163792.png" alt="Logo">
        <h3 class="m-0 fw-bold"><i class="fas fa-camera"></i> Ticket Scanner</h3>
        <p class="small mb-0 opacity-75">สแกน QR Code เพื่อเช็กอินเข้างาน</p>
    </div>

    <div style="position: relative;">
        <div id="reader"></div>

        <div id="scan-corners" class="scan-overlay-custom">
            <div class="corner tl"></div>
            <div class="corner tr"></div>
            <div class="corner bl"></div>
            <div class="corner br"></div>
        </div>

        <div id="result-screen" class="result-overlay">
            <div id="res-icon" style="font-size: 5rem; margin-bottom: 15px;"></div>
            <h1 id="res-status" style="font-weight: 800; color: white;"></h1>
            <p id="res-info" style="color: white; margin-bottom: 25px;"></p>
            <button onclick="location.reload()" style="background: white !important; color: #4f46e5 !important; padding: 10px 30px !important; border-radius: 50px !important; font-weight: bold; border: none;">
                <i class="fas fa-sync-alt"></i> สแกนใบถัดไป
            </button>
        </div>
    </div>

    <div class="footer-panel">
        <p><i class="fas fa-arrow-left"></i> กลับไป <a href="orders">หน้าจัดการคำสั่งซื้อ</a></p>
    </div>
</div>

<script>
    function onScanSuccess(decodedText) {
        // หยุดกล้องทันที
        html5QrcodeScanner.clear();
        document.getElementById('scan-corners').style.display = 'none';

        // ส่งไปเช็กที่ PHP
        fetch(`check_ticket.php?serial=${encodeURIComponent(decodedText)}`)
            .then(res => res.json())
            .then(data => {
                const screen = document.getElementById('result-screen');
                const status = document.getElementById('res-status');
                const info = document.getElementById('res-info');
                const icon = document.getElementById('res-icon');

                screen.classList.add('show');

                if (data.status === 'success') {
                    screen.style.background = 'rgba(16, 185, 129, 0.9)'; // สีเขียว
                    icon.innerText = '✅';
                    status.innerText = 'PASS';
                    info.innerText = `Serial: ${decodedText}\nยินดีต้อนรับเข้าสู่กิจกรรม!`;
                    new Audio('https://www.soundjay.com/buttons/sounds/button-3.mp3').play();
                } else {
                    screen.style.background = 'rgba(239, 68, 68, 0.9)'; // สีแดง
                    icon.innerText = '❌';
                    status.innerText = 'DENIED';
                    info.innerText = data.message;
                    new Audio('https://www.soundjay.com/buttons/sounds/button-10.mp3').play();
                }
            });
    }

    let config = { 
        fps: 20, 
        qrbox: { width: 250, height: 250 },
        aspectRatio: 1.0 
    };

    let html5QrcodeScanner = new Html5QrcodeScanner("reader", config, false);
    html5QrcodeScanner.render(onScanSuccess);
</script>