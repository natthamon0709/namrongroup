<?php
// ตั้งค่าการเชื่อมต่อฐานข้อมูล
$servername = "localhost";
$username = "root"; // ชื่อผู้ใช้ฐานข้อมูล (เปลี่ยนตามการตั้งค่าของคุณ)
$password = "";     // รหัสผ่านฐานข้อมูล (เปลี่ยนตามการตั้งค่าของคุณ)
$dbname = "nextgen_shop"; // ชื่อฐานข้อมูลที่เราสร้างไว้

// สร้างการเชื่อมต่อ (Create connection)
$conn = new mysqli($servername, $username, $password, $dbname);

// ตรวจสอบการเชื่อมต่อ (Check connection)
if ($conn->connect_error) {
    // ใน Production ควร log error ลงไฟล์แทนการแสดงผลหน้าจอ
    die("Connection failed: " . $conn->connect_error);
}

// ตั้งค่าภาษาให้รองรับภาษาไทย (UTF-8)
$conn->set_charset("utf8mb4");

// ตั้งค่า Timezone เป็นประเทศไทย
date_default_timezone_set('Asia/Bangkok');

// ---------------------------------------------------------
// ดึงค่าการตั้งค่าเว็บไซต์ (Global Settings)
// ---------------------------------------------------------
// ดึงข้อมูลจากตาราง settings มาเก็บไว้ในตัวแปร array $settings
// เพื่อให้เรียกใช้ได้สะดวกในทุกหน้า เช่น $settings['site_name']

$settings = array(); // ตัวแปรเก็บค่า settings ทั้งหมด

$sql_settings = "SELECT setting_key, setting_value FROM settings";
$result_settings = $conn->query($sql_settings);

if ($result_settings) {
    if ($result_settings->num_rows > 0) {
        while ($row = $result_settings->fetch_assoc()) {
            // key = ชื่อ setting, value = ค่าที่ตั้งไว้
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }
}

// ตัวอย่างการใช้งานในหน้าอื่น:
// include 'config/connect.php';
// echo $settings['site_name'];
