<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

// Đọc luồng dữ liệu JSON từ JavaScript fetch()
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if ($data) {
    $customer_id = $data['customer_id'];
    $branch_id = $data['branch_id'];
    $service_id = $data['service_id'];
    $stylist_id = $data['stylist_id'];
    $appointment_date = $data['appointment_date'];
    $appointment_time = $data['appointment_time'];
    $notes = $data['notes'] ?? '';

    try {
        // Lấy giá dịch vụ để lưu
        $stmt_price = $conn->prepare("SELECT price FROM services WHERE service_id = ?");
        $stmt_price->execute([$service_id]);
        $service = $stmt_price->fetch(PDO::FETCH_ASSOC);
        $total_price = $service ? $service['price'] : 0;

        $stmt = $conn->prepare("INSERT INTO appointments (customer_id, branch_id, service_id, stylist_id, appointment_date, appointment_time, total_price, notes, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$customer_id, $branch_id, $service_id, $stylist_id, $appointment_date, $appointment_time, $total_price, $notes]);

        echo json_encode(['status' => 'success', 'message' => 'Đặt lịch thành công!']);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi lưu dữ liệu: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Dữ liệu gửi lên không hợp lệ']);
}
?>