<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Scanner</title>
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
            margin: 0; padding: 10px;
        }

        .scan-card {
            background: var(--card-bg);
            border-radius: 35px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 500px;
            margin: 20px auto;
            overflow: hidden;
            position: relative;
        }

        .scan-header {
            background: var(--primary-gradient);
            padding: 30px 20px;
            color: white;
            text-align: center;
        }

        .scan-header img {
            max-height: 60px;
            margin-bottom: 15px;
            background: rgba(255,255,255,0.2);
            padding: 8px;
            border-radius: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }

        /* --- ส่วนจัดการช่องสแกน (หัวใจสำคัญ) --- */
        #reader {
            border: none !important;
            position: relative;
            background: #000;
        }

        /* 1. ซ่อนกรอบสี่เหลี่ยมจางๆ ของโปรแกรมเดิม */
        #reader__scan_region {
            background: transparent !important;
        }
        
        /* 2. ซ่อนรูปเป้าเล็ง (เส้นขาวจาง) ของเดิม */
        #reader__scan_region img {
            display: none !important;
        }

        #reader video {
            width: 100% !important;
            height: auto !important;
            border-radius: 0 !important;
            object-fit: cover;
        }

        /* ปรับแต่งปุ่มภายในโปรแกรม */
        #reader button {
            background: #7c3aed !important;
            color: white !important;
            border: none !important;
            padding: 12px 25px !important;
            border-radius: 15px !important;
            font-weight: 600 !important;
            margin: 20px auto !important;
            cursor: pointer;
            font-family: 'Kanit' !important;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        #reader button:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 15px rgba(124, 58, 237, 0.4);
        }

        /* 3. กรอบ Custom ใหม่ จัดให้ตรงกึ่งกลาง QRBOX เป๊ะๆ */
        .scan-overlay-custom {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 260px; /* ต้องสัมพันธ์กับค่า qrbox ใน script */
            height: 260px;
            transform: translate(-50%, -50%); /* จัดกึ่งกลางสมบูรณ์แบบ */
            pointer-events: none;
            z-index: 10;
        }

        .corner {
            position: absolute;
            width: 45px;
            height: 45px;
            border: 6px solid white;
            border-radius: 4px;
            filter: drop-shadow(0 0 8px rgba(0,0,0,0.5));
        }
        .tl { top: 0; left: 0; border-right: none; border-bottom: none; }
        .tr { top: 0; right: 0; border-left: none; border-bottom: none; }
        .bl { bottom: 0; left: 0; border-right: none; border-top: none; }
        .br { bottom: 0; right: 0; border-left: none; border-top: none; }

        /* ผลลัพธ์ PASS/DENIED */
        .result-overlay {
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            display: none;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            z-index: 999;
            backdrop-filter: blur(15px);
            text-align: center;
            padding: 20px;
            color: white;
        }
        .result-overlay.show { display: flex; animation: zoomIn 0.3s ease-out; }

        @keyframes zoomIn { 
            from { opacity: 0; transform: scale(1.2); } 
            to { opacity: 1; transform: scale(1); } 
        }

        .footer-panel {
            padding: 25px;
            text-align: center;
            color: #64748b;
            background: #fff;
            border-top: 1px solid #f1f5f9;
        }
        .footer-panel a { color: #4f46e5; text-decoration: none; font-weight: bold; }
    </style>
</head>
<body>

<div class="scan-card">
    <div class="scan-header">
        <img src="uploads/logo_1765163792.png" alt="Logo">
        <h3 class="m-0 fw-bold text-uppercase" style="letter-spacing: 1.5px;">Ticket Scanner</h3>
        <p class="small mb-0 opacity-75 font-weight-light">กรุณาวาง QR Code ให้อยู่ในกรอบเพื่อเช็กอิน</p>
    </div>

    <div style="position: relative; background: #000; min-height: 400px; display: flex; align-items: center; justify-content: center;">
        <div id="reader" style="width: 100%;"></div>

        <div id="scan-corners" class="scan-overlay-custom">
            <div class="corner tl"></div>
            <div class="corner tr"></div>
            <div class="corner bl"></div>
            <div class="corner br"></div>
        </div>

        <div id="result-screen" class="result-overlay">
            <div id="res-icon" style="font-size: 7rem; margin-bottom: 10px;"></div>
            <h1 id="res-status" style="font-size: 3.5rem; font-weight: 800; margin-bottom: 0;"></h1>
            <p id="res-info" style="font-size: 1.2rem; opacity: 0.9; margin-bottom: 35px; white-space: pre-line;"></p>
            <button onclick="location.reload()" style="background: white !important; color: #4f46e5 !important; padding: 15px 45px !important; border-radius: 50px !important; font-weight: bold; border: none; box-shadow: 0 10px 25px rgba(0,0,0,0.3);">
                <i class="fas fa-sync-alt mr-2"></i> สแกนใบถัดไป
            </button>
        </div>
    </div>

    <div class="footer-panel">
        <p class="mb-0 small"><i class="fas fa-arrow-left mr-1"></i> กลับไป <a href="orders">หน้าจัดการคำสั่งซื้อ</a></p>
    </div>
</div>

<script>
    function onScanSuccess(decodedText) {
        // หยุดกล้องทันทีเพื่อประหยัดทรัพยากร
        html5QrcodeScanner.clear();
        document.getElementById('scan-corners').style.display = 'none';

        // ส่งไปเช็กที่ฐานข้อมูลผ่าน PHP
        fetch(`check_ticket.php?serial=${encodeURIComponent(decodedText)}`)
            .then(res => res.json())
            .then(data => {
                const screen = document.getElementById('result-screen');
                const status = document.getElementById('res-status');
                const info = document.getElementById('res-info');
                const icon = document.getElementById('res-icon');

                screen.classList.add('show');

                if (data.status === 'success') {
                    screen.style.background = 'rgba(16, 185, 129, 0.96)'; // สีเขียว PASS
                    icon.innerHTML = '<i class="fas fa-check-circle"></i>';
                    status.innerText = 'PASS';
                    info.innerText = `ยินดีต้อนรับเข้าสู่งาน! \n Serial: ${decodedText}`;
                    new Audio('https://www.soundjay.com/buttons/sounds/button-3.mp3').play();
                } else {
                    screen.style.background = 'rgba(220, 38, 38, 0.96)'; // สีแดง DENIED
                    icon.innerHTML = '<i class="fas fa-times-circle"></i>';
                    status.innerText = 'DENIED';
                    info.innerText = data.message;
                    new Audio('https://www.soundjay.com/buttons/sounds/button-10.mp3').play();
                }
            })
            .catch(err => {
                alert("เกิดข้อผิดพลาดในการเชื่อมต่อเซิร์ฟเวอร์");
                location.reload();
            });
    }

    // ตั้งค่า Scanner
    let config = { 
        fps: 25, 
        qrbox: { width: 260, height: 260 }, // ต้องเท่ากับขนาดใน CSS
        aspectRatio: 1.0,
        // เพิ่มเพื่อความเสถียรบนมือถือ
        rememberLastUsedCamera: true,
        supportedScanTypes: [Html5QrcodeScanType.SCAN_TYPE_CAMERA]
    };

    let html5QrcodeScanner = new Html5QrcodeScanner("reader", config, false);
    html5QrcodeScanner.render(onScanSuccess);
</script>

</body>
</html>