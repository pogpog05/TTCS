<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $sql = "DELETE FROM services WHERE service_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Xóa dịch vụ thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Không thể xóa dịch vụ này vì đã có lịch hẹn liên quan!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID dịch vụ!']);
}
?>