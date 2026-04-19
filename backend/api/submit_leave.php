<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $user_id    = $_POST['user_id'] ?? 0;
    $start_date = $_POST['start_date'] ?? '';
    $end_date   = $_POST['end_date'] ?? '';
    $type       = $_POST['leave_type'] ?? '';
    $reason     = $_POST['leave_reason'] ?? '';

    try {
        // Kiểm tra xem User này đã có hồ sơ trong bảng stylists chưa
        $stmt_find = $conn->prepare("SELECT stylist_id FROM stylists WHERE user_id = ?");
        $stmt_find->execute([$user_id]);
        $stylist = $stmt_find->fetch(PDO::FETCH_ASSOC);

        if (!$stylist) {
            echo json_encode(['status' => 'error', 'message' => 'Lỗi: Tài khoản của bạn chưa được liên kết với hồ sơ Thợ cắt! Vui lòng báo Admin kiểm tra lại.']);
            exit;
        }

        $sql = "INSERT INTO leave_requests (stylist_id, start_date, end_date, leave_type, reason, status) VALUES (?, ?, ?, ?, ?, 'pending')";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$stylist['stylist_id'], $start_date, $end_date, $type, $reason]);

        echo json_encode(['status' => 'success', 'message' => 'Đã gửi đơn xin nghỉ phép thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi DB: ' . $e->getMessage()]);
    }
}
?>