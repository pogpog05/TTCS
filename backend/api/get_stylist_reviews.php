<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

$stylist_id = $_GET['stylist_id'] ?? 0;

if (!$stylist_id) {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu ID thợ']);
    exit;
}

try {
    $stmt = $conn->prepare("SELECT r.review_id, r.rating, r.comment, r.created_at, u.user_name 
                            FROM reviews r
                            JOIN users u ON r.user_id = u.user_id
                            WHERE r.stylist_id = ?
                            ORDER BY r.created_at DESC");
    $stmt->execute([$stylist_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Tính rating trung bình
    $avg_rating = 0;
    if (!empty($reviews)) {
        $total = array_sum(array_column($reviews, 'rating'));
        $avg_rating = round($total / count($reviews), 1);
    }

    echo json_encode([
        'status' => 'success',
        'average_rating' => $avg_rating,
        'total_reviews' => count($reviews),
        'reviews' => $reviews
    ]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>