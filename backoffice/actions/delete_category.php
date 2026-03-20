<?php
session_start();
include '../../config/connect.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];

    // ดึงรูปเก่าเพื่อลบ
    $stmt = $conn->prepare("SELECT image FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $cat = $stmt->get_result()->fetch_assoc();

    // ลบข้อมูล
    $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        if ($cat && !empty($cat['image'])) {
            $file = "../../" . $cat['image'];
            if (file_exists($file)) unlink($file);
        }
        $_SESSION['success'] = "ลบหมวดหมู่เรียบร้อยแล้ว";
    }
}
header("Location: ../categories");
exit();
