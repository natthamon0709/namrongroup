<?php
// ตรวจสอบสิทธิ์ Admin (ป้องกันการเข้าถึงหากไม่ได้ล็อกอิน)
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
    header("Location: ../login");
    exit();
}