<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu mã khách hàng']);
    exit();
}

$user_id = $_GET['user_id'];

try {
    $stmt = $conn->prepare("SELECT full_name, phone, email, created_at FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        echo json_encode(['status' => 'success', 'data' => $user]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy người dùng']);
    }
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}
?>