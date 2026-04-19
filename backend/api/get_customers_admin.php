<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

// 1. Nhận thông số phân trang từ JS gửi lên
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 5; // Số khách hàng hiển thị trên 1 trang (bạn có thể tự đổi)
$offset = ($page - 1) * $limit;

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$params = [];

// 2. Câu lệnh đếm tổng số dòng (Dùng để tính ra tổng số trang)
$sqlCount = "SELECT COUNT(*) as total FROM users WHERE role = 'customer'";

// 3. Câu lệnh lấy dữ liệu (Giữ nguyên hàm DATE_FORMAT của bạn)
$sqlData = "SELECT user_id, full_name, phone, email, DATE_FORMAT(created_at, '%d/%m/%Y') as created_at FROM users WHERE role = 'customer'";

// 4. Nếu có từ khóa tìm kiếm -> Nối thêm vào CẢ 2 câu lệnh
if (!empty($search)) {
    $searchCondition = " AND (full_name LIKE ? OR phone LIKE ? OR email LIKE ?)";
    $sqlCount .= $searchCondition;
    $sqlData .= $searchCondition;
    $params = ["%$search%", "%$search%", "%$search%"];
}

try {
    // --- Bước A: Thực thi đếm số trang ---
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalRows = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $limit); // Làm tròn lên để ra số trang

    // --- Bước B: Thực thi lấy dữ liệu thật ---
    // Nối thêm ORDER BY, LIMIT và OFFSET vào cuối câu truy vấn
    // (Ép kiểu int trực tiếp vào chuỗi để tránh lỗi PDO khi dùng chung với mảng $params)
    $sqlData .= " ORDER BY user_id DESC LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
    
    $stmtData = $conn->prepare($sqlData);
    $stmtData->execute($params);
    $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    // 5. Trả về JSON bao gồm cả data và cục thông tin phân trang
    echo json_encode([
        'status' => 'success', 
        'data' => $data, 
        'total_pages' => $totalPages, 
        'current_page' => $page
    ]);

} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi CSDL: ' . $e->getMessage()]);
}
?>