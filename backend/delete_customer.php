<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $sql = "DELETE FROM users WHERE user_id = ? AND role = 'customer'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Xóa khách hàng thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Không thể xóa khách hàng do có lịch sử đặt hẹn!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID khách hàng!']);
}
?>