<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $sql = "UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$id]);

        $sql_del_sch = "DELETE FROM stylist_schedules WHERE appointment_id = ?";
        $stmt_del = $conn->prepare($sql_del_sch);
        $stmt_del->execute([$id]);

        echo json_encode(['status' => 'success', 'message' => 'Đã hủy lịch hẹn thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu mã lịch hẹn']);
}
?>