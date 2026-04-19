<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

if (!isset($_GET['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu mã khách hàng']);
    exit();
}

$user_id = $_GET['user_id'];

try {
    $sql = "SELECT a.*, s.service_name, b.branch_name, u_stylist.full_name AS stylist_name
            FROM appointments a
            JOIN services s ON a.service_id = s.service_id
            JOIN branches b ON a.branch_id = b.branch_id
            LEFT JOIN stylists st ON a.stylist_id = st.stylist_id
            LEFT JOIN users u_stylist ON st.user_id = u_stylist.user_id
            WHERE a.customer_id = ?
            ORDER BY a.appointment_date DESC, a.appointment_time DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'data' => $appointments]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}
?>