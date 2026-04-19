<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

try {
    $sql = "SELECT * FROM branches ORDER BY branch_id ASC";
    $stmt = $conn->query($sql);
    echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>