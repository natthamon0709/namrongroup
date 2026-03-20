<?php
session_start();
include 'config/connect.php';

// 1. ตรวจสอบตะกร้าสินค้า
if (empty($_SESSION['cart'])) {
    header("Location: products");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 0. เตรียมข้อมูลและป้องกัน SQL Injection เบื้องต้น
    $first_name = trim(mysqli_real_escape_string($conn, $_POST['first_name']));
    $last_name  = trim(mysqli_real_escape_string($conn, $_POST['last_name']));
    $email      = trim(mysqli_real_escape_string($conn, $_POST['email']));
    $phone      = trim(mysqli_real_escape_string($conn, $_POST['phone']));
    $address    = trim(mysqli_real_escape_string($conn, $_POST['address']));
    $payment_method = mysqli_real_escape_string($conn, $_POST['paymentMethod']);

    try {
        $current_user_id = null;

        // --- 1. จัดการสมาชิก (รองรับทั้ง Login แล้ว และสมัครใหม่) ---
        if (isset($_SESSION['user_id'])) {
            // กรณี Login อยู่แล้ว: ใช้ ID จาก Session ได้เลย
            $current_user_id = $_SESSION['user_id'];
            
            // อัปเดตที่อยู่และเบอร์ติดต่อล่าสุดจากหน้า Checkout เข้า Profile
            $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, address = ?, phone = ? WHERE id = ?");
            $update_stmt->bind_param("sssssi", $first_name, $last_name, $email, $address, $phone, $current_user_id);
            $update_stmt->execute();
        } else {
            // กรณีไม่ได้ Login: เช็คจากเบอร์โทร
            $check_user = $conn->prepare("SELECT id FROM users WHERE phone = ? LIMIT 1");
            $check_user->bind_param("s", $phone);
            $check_user->execute();
            $user_result = $check_user->get_result();

            if ($user_result->num_rows > 0) {
                // มีเบอร์นี้อยู่แล้ว: ดึง ID มาและอัปเดตข้อมูล
                $existing_user = $user_result->fetch_assoc();
                $current_user_id = $existing_user['id'];
                
                $update_stmt = $conn->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, address = ? WHERE id = ?");
                $update_stmt->bind_param("ssssi", $first_name, $last_name, $email, $address, $current_user_id);
                $update_stmt->execute();
            } else {
                // สมาชิกใหม่: สร้างบัญชีให้โดยใช้เบอร์โทรเป็น Password
                $default_password = password_hash($phone, PASSWORD_DEFAULT);
                $reg_stmt = $conn->prepare("INSERT INTO users (first_name, last_name, phone, email, address, password, role) VALUES (?, ?, ?, ?, ?, ?, 'user')");
                $reg_stmt->bind_param("ssssss", $first_name, $last_name, $phone, $email, $address, $default_password);
                
                if ($reg_stmt->execute()) {
                    $current_user_id = $conn->insert_id;
                } else {
                    throw new Exception("สมัครสมาชิกไม่สำเร็จ: " . $reg_stmt->error);
                }
            }
            
            // สร้าง Session เพื่อให้สถานะเป็น Login
            $_SESSION['user_id'] = $current_user_id;
            $_SESSION['user_name'] = $first_name . " " . $last_name;
        }

        // --- 2. เริ่ม Transaction สำหรับ Order ---
        $conn->begin_transaction();

        $total_amount = 0;
        $order_items = [];
        
        // ป้องกัน SQL Injection สำหรับ IDs
        $cart_ids = array_map('intval', array_keys($_SESSION['cart']));
        $ids_string = implode(',', $cart_ids);
        
        $sql = "SELECT * FROM products WHERE id IN ($ids_string)";
        $result = $conn->query($sql);

        while ($row = $result->fetch_assoc()) {
            $qty = $_SESSION['cart'][$row['id']];
            
            // ตรวจสอบสต็อกสินค้าก่อน (ถ้าสินค้าไม่พอ ให้ Rollback)
            if ($row['stock_quantity'] < $qty) {
                throw new Exception("สินค้า " . $row['name'] . " มีจำนวนไม่พอ (เหลือ $row[stock_quantity])");
            }

            $price = ($row['sale_price'] > 0) ? $row['sale_price'] : $row['price'];
            $total_amount += $price * $qty;
            $order_items[] = [
                'product_id' => $row['id'],
                'product_name' => $row['name'],
                'price' => $price,
                'quantity' => $qty
            ];
        }

        $order_number = '#NGS-' . date('Ymd') . '-' . rand(1000, 9999);
        $shipping_name = $first_name . ' ' . $last_name;

        // บันทึก Order
        $stmt_order = $conn->prepare("INSERT INTO orders (order_number, user_id, total_amount, status, payment_method, shipping_name, shipping_address, shipping_phone, created_at) VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, NOW())");
        $stmt_order->bind_param("sidssss", $order_number, $current_user_id, $total_amount, $payment_method, $shipping_name, $address, $phone);
        
        if (!$stmt_order->execute()) throw new Exception("บันทึก Order ไม่สำเร็จ");
        
        $order_id = $conn->insert_id;

        // บันทึก Items & ตัดสต็อก
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
        foreach ($order_items as $item) {
            $stmt_item->bind_param("iisdi", $order_id, $item['product_id'], $item['product_name'], $item['price'], $item['quantity']);
            $stmt_item->execute();
            
            // ตัดสต็อกสินค้าจริง
            $conn->query("UPDATE products SET stock_quantity = stock_quantity - {$item['quantity']} WHERE id = {$item['product_id']}");
        }

        $conn->commit();
        unset($_SESSION['cart']);

        // --- 3. Redirect ไปยังหน้าที่เลือก ---
        // ตรวจสอบว่า paymentMethod ที่ส่งมาคืออะไร แล้วส่งไปหน้าชำระเงินที่ต้องการ
        header("Location: payment?order_number=" . urlencode($order_number));
        exit();

    } catch (Exception $e) {
        if (isset($conn) && $conn->in_transaction) $conn->rollback();
        // หากเกิด Error จะแจ้งเตือนที่นี่
        die("ระบบขัดข้อง: " . $e->getMessage());
    }
}