<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $data = $json ? $json : $_POST;

    $name        = trim($data['service_name'] ?? '');
    $price       = $data['service_price'] ?? 0;
    $duration    = $data['service_duration'] ?? 0;
    $description = trim($data['service_description'] ?? '');

    try {
        $sql = "INSERT INTO services (service_name, price, duration, description) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $price, $duration, $description]);

        echo json_encode(['status' => 'success', 'message' => 'Thêm dịch vụ thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
}
?>