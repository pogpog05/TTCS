<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $sql = "DELETE FROM branches WHERE branch_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Xóa chi nhánh thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Không thể xóa chi nhánh vì đang có dữ liệu (thợ/lịch) liên quan!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID chi nhánh!']);
}
?>