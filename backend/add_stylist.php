<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $data = $json ? $json : $_POST;

    $name   = trim($data['stylist_name'] ?? '');
    $phone  = trim($data['stylist_phone'] ?? '');
    $email  = trim($data['stylist_email'] ?? '');
    $branch = empty($data['branch_id']) ? NULL : $data['branch_id'];
    $status = 'Đang làm việc'; 
    $password = password_hash('123456', PASSWORD_DEFAULT); // Mật khẩu mặc định 123456

    try {
        $conn->beginTransaction();

        // 1. Thêm vào bảng users trước
        $sql_user = "INSERT INTO users (full_name, phone, email, password, role) VALUES (?, ?, ?, ?, 'stylist')";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->execute([$name, $phone, $email, $password]);
        $user_id = $conn->lastInsertId();

        // 2. Thêm vào bảng stylists
        $sql_stylist = "INSERT INTO stylists (user_id, branch_id, status) VALUES (?, ?, ?)";
        $stmt_stylist = $conn->prepare($sql_stylist);
        $stmt_stylist->execute([$user_id, $branch, $status]);

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Thêm thợ cắt thành công!']);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}
?>