<?php
ob_start(); // ← THÊM DÒNG NÀY, đầu tiên
ini_set('display_errors', 0);
header('Content-Type: application/json; charset=utf-8');

require_once '../db_connect.php';

// Kiểm tra action
$action = isset($_GET['action']) ? $_GET['action'] : '';

try {
    switch($action) {
        case 'add':
            addReview();
            break;
        case 'get_by_stylist':
            getReviewsByStylist();
            break;
        case 'get_by_appointment':
            getReviewByAppointment();
            break;
        case 'get_average_rating':
            getAverageRating();
            break;
        case 'delete':
            deleteReview();
            break;
        case 'update':
            updateReview();
            break;
        case 'get_all':
            getAllReviews();
            break;
        default:
            echo json_encode(['status' => 'error', 'message' => 'Action không hợp lệ']);
    }
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
}

// ===== FUNCTION: Thêm Review Mới =====
function addReview() {
    global $conn; // Đã đổi sang $conn
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['status' => 'error', 'message' => 'Chỉ hỗ trợ POST']);
        return;
    }
    
    $appointment_id = isset($_POST['appointment_id']) ? intval($_POST['appointment_id']) : 0;
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    $stylist_id = isset($_POST['stylist_id']) ? intval($_POST['stylist_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    
    if (!$appointment_id || !$customer_id || !$stylist_id || $rating < 1 || $rating > 5) {
        echo json_encode(['status' => 'error', 'message' => 'Dữ liệu không hợp lệ']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT status FROM appointments WHERE appointment_id = ? AND customer_id = ?");
    $stmt->execute([$appointment_id, $customer_id]);
    $appointment = $stmt->fetch();
    
    if (!$appointment || $appointment['status'] !== 'completed') {
        echo json_encode(['status' => 'error', 'message' => 'Lịch hẹn chưa hoàn thành hoặc không tồn tại']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT review_id FROM reviews WHERE appointment_id = ?");
    $stmt->execute([$appointment_id]);
    if ($stmt->fetch()) {
        echo json_encode(['status' => 'error', 'message' => 'Bạn đã đánh giá lịch hẹn này rồi']);
        return;
    }
    
    $stmt = $conn->prepare("INSERT INTO reviews (appointment_id, customer_id, stylist_id, rating, comment) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$appointment_id, $customer_id, $stylist_id, $rating, $comment]);
    
    echo json_encode(['status' => 'success', 'message' => 'Đánh giá thành công!', 'review_id' => $conn->lastInsertId()]);
}

// ===== FUNCTION: Lấy Reviews theo Stylist =====
function getReviewsByStylist() {
    global $conn;
    
    $stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 0;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = 5;
    $offset = ($page - 1) * $per_page;
    
    if (!$stylist_id) {
        echo json_encode(['status' => 'error', 'message' => 'stylist_id bắt buộc']);
        return;
    }
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews WHERE stylist_id = ?");
    $stmt->execute([$stylist_id]);
    $total = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("
        SELECT r.review_id, r.rating, r.comment, r.created_at, u.full_name as customer_name
        FROM reviews r
        JOIN users u ON r.customer_id = u.user_id
        WHERE r.stylist_id = :sid
        ORDER BY r.created_at DESC
        LIMIT :offset, :limit
    ");
    $stmt->bindValue(':sid', $stylist_id, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => (int)$total,
        'current_page' => $page,
        'total_pages' => ceil($total / $per_page)
    ]);
}

// ===== FUNCTION: Lấy Review theo Appointment =====
function getReviewByAppointment() {
    global $conn;
    $appointment_id = isset($_GET['appointment_id']) ? intval($_GET['appointment_id']) : 0;
    
    $stmt = $conn->prepare("
        SELECT r.review_id, r.rating, r.comment, r.created_at, u.full_name as customer_name
        FROM reviews r
        LEFT JOIN users u ON r.customer_id = u.user_id
        WHERE r.appointment_id = ?
    ");
    $stmt->execute([$appointment_id]);
    $review = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode(['status' => 'success', 'data' => $review, 'has_review' => !!$review]);
}

// ===== FUNCTION: Lấy Rating Trung Bình =====
function getAverageRating() {
    global $conn;
    $stylist_id = isset($_GET['stylist_id']) ? intval($_GET['stylist_id']) : 0;
    
    $stmt = $conn->prepare("
        SELECT AVG(rating) as avg_r, COUNT(*) as total,
        SUM(CASE WHEN rating = 5 THEN 1 ELSE 0 END) as r5,
        SUM(CASE WHEN rating = 4 THEN 1 ELSE 0 END) as r4,
        SUM(CASE WHEN rating = 3 THEN 1 ELSE 0 END) as r3,
        SUM(CASE WHEN rating = 2 THEN 1 ELSE 0 END) as r2,
        SUM(CASE WHEN rating = 1 THEN 1 ELSE 0 END) as r1
        FROM reviews WHERE stylist_id = ?
    ");
    $stmt->execute([$stylist_id]);
    $res = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'status' => 'success',
        'data' => [
            'average_rating' => round($res['avg_r'] ?? 0, 2),
            'total_reviews' => (int)$res['total'],
            'distribution' => [5 => (int)$res['r5'], 4 => (int)$res['r4'], 3 => (int)$res['r3'], 2 => (int)$res['r2'], 1 => (int)$res['r1']]
        ]
    ]);
}

// ===== FUNCTION: Xóa Review =====
function deleteReview() {
    global $conn;
    $review_id = isset($_GET['review_id']) ? intval($_GET['review_id']) : 0;
    $user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0; 

    $stmt = $conn->prepare("SELECT customer_id FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch();
    
    if (!$review || ($user_id !== 0 && $review['customer_id'] !== $user_id)) {
        echo json_encode(['status' => 'error', 'message' => 'Không có quyền xóa']);
        return;
    }
    
    $stmt = $conn->prepare("DELETE FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    echo json_encode(['status' => 'success', 'message' => 'Xóa thành công']);
}

// ===== FUNCTION: Cập Nhật Review =====
function updateReview() {
    global $conn;
    $review_id = isset($_POST['review_id']) ? intval($_POST['review_id']) : 0;
    $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
    $comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';
    $customer_id = isset($_POST['customer_id']) ? intval($_POST['customer_id']) : 0;
    
    $stmt = $conn->prepare("SELECT customer_id FROM reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $review = $stmt->fetch();
    
    if (!$review || $review['customer_id'] !== $customer_id) {
        echo json_encode(['status' => 'error', 'message' => 'Không có quyền cập nhật']);
        return;
    }
    
    $stmt = $conn->prepare("UPDATE reviews SET rating = ?, comment = ? WHERE review_id = ?");
    $stmt->execute([$rating, $comment, $review_id]);
    echo json_encode(['status' => 'success', 'message' => 'Cập nhật thành công']);
}

// ===== FUNCTION: Lấy tất cả Reviews =====
function getAllReviews() {
    global $conn;
    $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
    $per_page = 10;
    $offset = ($page - 1) * $per_page;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM reviews");
    $stmt->execute();
    $total = $stmt->fetch()['total'];
    
    $stmt = $conn->prepare("
        SELECT r.*, c.full_name as customer_name, s.full_name as stylist_name
        FROM reviews r
        JOIN users c ON r.customer_id = c.user_id
        JOIN users s ON r.stylist_id = s.user_id
        ORDER BY r.created_at DESC
        LIMIT :offset, :limit
    ");
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
    $stmt->execute();
    
    echo json_encode([
        'status' => 'success',
        'data' => $stmt->fetchAll(PDO::FETCH_ASSOC),
        'total' => (int)$total,
        'current_page' => $page,
        'total_pages' => ceil($total / $per_page)
    ]);
}
?>