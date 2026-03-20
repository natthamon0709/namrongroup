<?php
include '../config/connect.php';
if(isset($_GET['order_id'])) {
    $id = $conn->real_escape_string($_GET['order_id']);
    // นับรวมจำนวนสินค้าทั้งหมดใน Order นั้นๆ
    $res = $conn->query("SELECT SUM(quantity) as total FROM order_items WHERE order_id = '$id'");
    $row = $res->fetch_assoc();
    echo json_encode(['total_quantity' => $row['total'] ?? 1]);
}
?>