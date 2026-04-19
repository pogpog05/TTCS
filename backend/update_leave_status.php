<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$request_id = $_GET['id'] ?? 0;
$status = $_GET['status'] ?? ''; // Sẽ nhận 'approved' hoặc 'rejected'

if ($request_id && $status) {
    try {
        $conn->beginTransaction();

        // 1. Cập nhật trạng thái của lá đơn
        $sql = "UPDATE leave_requests SET status = ? WHERE request_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$status, $request_id]);

        // 2. NẾU DUYỆT -> Tự động đổi trạng thái của Thợ Cắt thành "Nghỉ phép"
        if ($status === 'approved') {
            $sql_get_stylist = "SELECT stylist_id FROM leave_requests WHERE request_id = ?";
            $stmt_get = $conn->prepare($sql_get_stylist);
            $stmt_get->execute([$request_id]);
            $stylist = $stmt_get->fetch(PDO::FETCH_ASSOC);

            if ($stylist) {
                $sql_update_stylist = "UPDATE stylists SET status = 'Nghỉ phép' WHERE stylist_id = ?";
                $stmt_update = $conn->prepare($sql_update_stylist);
                $stmt_update->execute([$stylist['stylist_id']]);
            }
        }

        $conn->commit();
        $msg = ($status === 'approved') ? 'Đã DUYỆT đơn! Hệ thống đã tự động chuyển thợ sang trạng thái Nghỉ phép.' : 'Đã TỪ CHỐI đơn xin nghỉ!';
        echo json_encode(['status' => 'success', 'message' => $msg]);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu dữ liệu!']);
}
?>