<?php
$host = "localhost";
$db_name = "barber_shop_db";
$username = "root"; // Mặc định của XAMPP
$password = "";     // Mặc định của XAMPP

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    // Bật chế độ báo lỗi để dễ sửa code
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Kết nối thất bại: " . $e->getMessage());
}
?>