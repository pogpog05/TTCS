<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

// 1. Nhận thông số phân trang
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$limit = 3; // Số lịch hẹn trên 1 trang
$offset = ($page - 1) * $limit;

// Nhận các bộ lọc cũ của bạn
$dateFilter = isset($_GET['date']) ? $_GET['date'] : '';
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? trim($_GET['search']) : ''; // Thêm tìm kiếm nếu cần

$params = [];
$whereClauses = ["1=1"]; // Mặc định luôn đúng để nối thêm điều kiện

// 2. Xây dựng điều kiện WHERE (Dùng chung cho cả COUNT và DATA)
if (!empty($dateFilter)) {
    $whereClauses[] = "DATE(a.appointment_date) = ?";
    $params[] = $dateFilter;
}
if (!empty($statusFilter)) {
    $whereClauses[] = "a.status = ?";
    $params[] = $statusFilter;
}
if (!empty($search)) {
    $whereClauses[] = "(u.full_name LIKE ? OR u.phone LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$whereSql = " WHERE " . implode(" AND ", $whereClauses);

try {
    // 3. Câu lệnh SQL đếm tổng số dòng (để chia trang)
    $sqlCount = "SELECT COUNT(*) as total 
                 FROM appointments a 
                 JOIN users u ON a.customer_id = u.user_id 
                 $whereSql";
    
    $stmtCount = $conn->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalRows = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $limit);

    // 4. Câu lệnh SQL lấy dữ liệu (Giữ nguyên các JOIN của bạn)
    $sqlData = "SELECT a.*, u.full_name as cust_name, u.phone as cust_phone, 
                       s.service_name, b.branch_name, st_u.full_name as stylist_name
                FROM appointments a 
                JOIN users u ON a.customer_id = u.user_id 
                JOIN services s ON a.service_id = s.service_id
                JOIN branches b ON a.branch_id = b.branch_id 
                LEFT JOIN stylists st ON a.stylist_id = st.stylist_id
                LEFT JOIN users st_u ON st.user_id = st_u.user_id 
                $whereSql";

    // Thêm sắp xếp và Phân trang (LIMIT, OFFSET)
    $sqlData .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC 
                  LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmtData = $conn->prepare($sqlData);
    $stmtData->execute($params);
    $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    // 5. Trả về JSON theo cấu trúc mới
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