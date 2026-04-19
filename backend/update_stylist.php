<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $data = $json ? $json : $_POST;

    $stylist_id = $data['stylist_id'] ?? '';
    $name       = trim($data['stylist_name'] ?? '');
    $phone      = trim($data['stylist_phone'] ?? '');
    $branch     = empty($data['branch_id']) ? NULL : $data['branch_id'];
    
    $status_input = $data['stylist_status'] ?? 'active';
    $status = ($status_input == 'active') ? 'Đang làm việc' : 'Nghỉ phép';

    try {
        $conn->beginTransaction();

        $stmt_find = $conn->prepare("SELECT user_id FROM stylists WHERE stylist_id = ?");
        $stmt_find->execute([$stylist_id]);
        $row = $stmt_find->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new Exception("Không tìm thấy dữ liệu thợ cắt này.");
        }
        $user_id = $row['user_id'];

        $sql_user = "UPDATE users SET full_name = ?, phone = ? WHERE user_id = ?";
        $stmt_user = $conn->prepare($sql_user);
        $stmt_user->execute([$name, $phone, $user_id]);

        $sql_stylist = "UPDATE stylists SET branch_id = ?, status = ? WHERE stylist_id = ?";
        $stmt_stylist = $conn->prepare($sql_stylist);
        $stmt_stylist->execute([$branch, $status, $stylist_id]);

        $conn->commit();
        echo json_encode(['status' => 'success', 'message' => 'Cập nhật thông tin thợ cắt thành công!']);
    } catch(Exception $e) {
        $conn->rollBack();
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ!']);
}
?>