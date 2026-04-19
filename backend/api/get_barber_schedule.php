<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

$user_id = $_GET['user_id'] ?? 0;
try {
    $stmt_find = $conn->prepare("SELECT stylist_id FROM stylists WHERE user_id = ?");
    $stmt_find->execute([$user_id]);
    $stylist = $stmt_find->fetch(PDO::FETCH_ASSOC);
    if (!$stylist) { echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy hồ sơ thợ cắt!']); exit; }

    $response_data = [];
    $sql_app = "SELECT a.appointment_date, a.appointment_time, a.notes, a.status, u.full_name as cust_name, u.phone as cust_phone, s.service_name
                FROM appointments a JOIN users u ON a.customer_id = u.user_id JOIN services s ON a.service_id = s.service_id
                WHERE a.stylist_id = ? AND a.appointment_date >= CURDATE() ORDER BY a.appointment_date ASC, a.appointment_time ASC";
    $stmt_app = $conn->prepare($sql_app);
    $stmt_app->execute([$stylist['stylist_id']]);
    $response_data['appointments'] = $stmt_app->fetchAll(PDO::FETCH_ASSOC);

    // Lấy ngày start và end
    $sql_leave = "SELECT * FROM leave_requests WHERE stylist_id = ? ORDER BY created_at DESC LIMIT 10";
    $stmt_leave = $conn->prepare($sql_leave);
    $stmt_leave->execute([$stylist['stylist_id']]);
    $response_data['leave_history'] = $stmt_leave->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $response_data]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>