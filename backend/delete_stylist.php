<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

$id = $_GET['id'] ?? 0;

if ($id) {
    try {
        $conn->beginTransaction();

        $stmt_find = $conn->prepare("SELECT user_id FROM stylists WHERE stylist_id = ?");
        $stmt_find->execute([$id]);
        $row = $stmt_find->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $user_id = $row['user_id'];
            
            // Xóa trong bảng stylists
            $stmt_del_stylist = $conn->prepare("DELETE FROM stylists WHERE stylist_id = ?");
            $stmt_del_stylist->execute([$id]);

            // Xóa user tương ứng trong bảng users
            $stmt_del_user = $conn->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt_del_user->execute([$user_id]);
        }

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Xóa thợ cắt thành công!']);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Không thể xóa do thợ này đang có lịch hẹn!']);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID thợ cắt!']);
}
?>