<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';
try {
    $sql = "SELECT lr.*, u.full_name FROM leave_requests lr 
            JOIN stylists s ON lr.stylist_id = s.stylist_id 
            JOIN users u ON s.user_id = u.user_id 
            WHERE lr.status = 'pending' ORDER BY lr.created_at DESC";
    $stmt = $conn->query($sql);
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>