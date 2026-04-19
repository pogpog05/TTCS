<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $data = $json ? $json : $_POST;

    $name  = trim($data['customer_name'] ?? '');
    $phone = trim($data['customer_phone'] ?? '');
    $email = trim($data['customer_email'] ?? '');
    
    // Mật khẩu mặc định 123456
    $password = password_hash('123456', PASSWORD_DEFAULT);

    try {
        $sql = "INSERT INTO users (full_name, phone, email, password, role) VALUES (?, ?, ?, ?, 'customer')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $phone, $email, $password]);

        echo json_encode(['status' => 'success', 'message' => 'Thêm khách hàng thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi (Có thể SĐT/Email đã tồn tại): ' . $e->getMessage()]);
    }
}
?>