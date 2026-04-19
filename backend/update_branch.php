<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $data = $json ? $json : $_POST;

    $id      = $data['branch_id'] ?? ''; 
    $name    = trim($data['branch_name'] ?? '');
    $address = trim($data['branch_address'] ?? '');
    $phone   = trim($data['branch_phone'] ?? '');
    $email   = trim($data['branch_email'] ?? '');

    if (empty($id)) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi: Không tìm thấy ID chi nhánh cần cập nhật!']);
        exit;
    }

    try {
        $sql = "UPDATE branches SET branch_name = ?, address = ?, phone = ?, email = ? WHERE branch_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $address, $phone, $email, $id]);

        echo json_encode(['status' => 'success', 'message' => 'Cập nhật chi nhánh thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Truy cập không hợp lệ!']);
}
?>