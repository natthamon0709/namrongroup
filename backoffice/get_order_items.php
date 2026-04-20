<?php
include '../config/connect.php';

$order_id = $_GET['order_id'];

$stmt = $conn->prepare("SELECT product_name, quantity FROM order_items WHERE order_id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

$data = [];
while($row = $result->fetch_assoc()){
    $data[] = $row;
}

echo json_encode($data);
