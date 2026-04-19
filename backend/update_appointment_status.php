<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

// Hỗ trợ nhận qua GET (URL), POST (FormData) hoặc JSON
$input = $_GET; 
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $input = $json ? $json : $_POST;
}

$appointment_id = isset($input['id']) ? (int)$input['id'] : 0;
$new_status = isset($input['status']) ? $input['status'] : '';

if ($appointment_id && $new_status) {
    try {
        $sql = "UPDATE appointments SET status = ? WHERE appointment_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$new_status, $appointment_id]);
        
        if ($new_status == 'cancelled') {
            $sql_del_sch = "DELETE FROM stylist_schedules WHERE appointment_id = ?";
            $stmt_del = $conn->prepare($sql_del_sch);
            $stmt_del->execute([$appointment_id]);
        }

        if ($new_status == 'confirmed') {
            $message = 'Đã XÁC NHẬN lịch hẹn thành công!';
        } elseif ($new_status == 'completed') {
            $message = 'Đã chốt HOÀN THÀNH lịch hẹn!';
        } else {
            $message = 'Đã HỦY lịch hẹn!';
        }

        echo json_encode(['status' => 'success', 'message' => $message]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi: Thiếu dữ liệu truyền vào!']);
}
?>