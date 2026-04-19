<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($fullname) || empty($email) || empty($phone) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng điền đủ thông tin']);
        exit();
    }

    try {
        $check = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone = ?");
        $check->execute([$email, $phone]);
        if ($check->rowCount() > 0) {
            echo json_encode(['status' => 'error', 'message' => 'Email hoặc Số điện thoại đã được đăng ký']);
            exit();
        }

        $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role) VALUES (?, ?, ?, ?, 'customer')");
        $stmt->execute([$fullname, $email, $phone, $password]);

        echo json_encode(['status' => 'success', 'message' => 'Đăng ký tài khoản thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống cơ sở dữ liệu']);
    }
}
?>