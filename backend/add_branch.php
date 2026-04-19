<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Hỗ trợ cả FormData ($_POST) và chuỗi JSON (php://input)
    $json = json_decode(file_get_contents('php://input'), true);
    $data = $json ? $json : $_POST;

    $name    = $data['branch_name'] ?? '';
    $address = $data['branch_address'] ?? '';
    $phone   = $data['branch_phone'] ?? '';
    $email   = $data['branch_email'] ?? '';

    try {
        $sql = "INSERT INTO branches (branch_name, address, phone, email) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $address, $phone, $email]);

        echo json_encode(['status' => 'success', 'message' => 'Thêm chi nhánh mới thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}
?>