<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

try {
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($page < 1) $page = 1;
    $limit = 5; 
    $offset = ($page - 1) * $limit;
    
    $sqlCount = "SELECT COUNT(*) as total FROM stylists s INNER JOIN users u ON s.user_id = u.user_id";
    $stmtCount = $conn->query($sqlCount);
    $totalRows = $stmtCount->fetch(PDO::FETCH_ASSOC)['total'];
    $totalPages = ceil($totalRows / $limit);


    $sqlVip = "SELECT stylist_id FROM appointments GROUP BY stylist_id ORDER BY COUNT(*) DESC LIMIT 3";
    $stmtVip = $conn->query($sqlVip);
    $vipIds = $stmtVip->fetchAll(PDO::FETCH_COLUMN); 

    $orderBy = "s.stylist_id DESC"; 
    if (!empty($vipIds)) {
        $vipIdsStr = implode(',', $vipIds);
        $orderBy = "s.stylist_id IN ($vipIdsStr) DESC, s.stylist_id DESC";
    }

    $sqlData = "SELECT s.stylist_id, s.branch_id, s.status, u.full_name, u.phone, u.email, b.branch_name 
                FROM stylists s 
                INNER JOIN users u ON s.user_id = u.user_id 
                LEFT JOIN branches b ON s.branch_id = b.branch_id
                ORDER BY $orderBy 
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;

    $stmtData = $conn->query($sqlData);
    $data = $stmtData->fetchAll(PDO::FETCH_ASSOC);

    foreach ($data as &$row) {
        $row['is_vip'] = in_array($row['stylist_id'], $vipIds);
    }

    echo json_encode([
        'status' => 'success', 
        'data' => $data, 
        'total_pages' => $totalPages, 
        'current_page' => $page
    ]);
} catch(PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>