<?php
header('Content-Type: application/json; charset=utf-8');
require_once '../db_connect.php';

function normalizeDateValue($date, $fallback)
{
    if (empty($date)) {
        return $fallback;
    }

    $obj = DateTime::createFromFormat('Y-m-d', $date);
    if ($obj && $obj->format('Y-m-d') === $date) {
        return $date;
    }

    return $fallback;
}

function calculateDeltaPercent($current, $previous)
{
    $current = (float) $current;
    $previous = (float) $previous;

    if ($previous == 0.0) {
        return $current > 0 ? 100.0 : 0.0;
    }

    return round((($current - $previous) / $previous) * 100, 2);
}

function buildAppointmentFilter($startDate, $endDate, $branchId)
{
    $whereSql = " WHERE DATE(a.appointment_date) BETWEEN ? AND ?";
    $params = [$startDate, $endDate];

    if ($branchId > 0) {
        $whereSql .= " AND a.branch_id = ?";
        $params[] = $branchId;
    }

    return ['where' => $whereSql, 'params' => $params];
}

function getSingleScalar($conn, $sql, $params = [])
{
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}

function getPeriodSummary($conn, $startDate, $endDate, $branchId)
{
    $filter = buildAppointmentFilter($startDate, $endDate, $branchId);

    $sqlSummary = "SELECT 
                    COUNT(*) AS total_appointments,
                    SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed_appointments,
                    SUM(CASE WHEN a.status = 'confirmed' THEN 1 ELSE 0 END) AS confirmed_appointments,
                    SUM(CASE WHEN a.status = 'pending' THEN 1 ELSE 0 END) AS pending_appointments,
                    SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_appointments,
                    COALESCE(SUM(CASE WHEN a.status IN ('confirmed', 'completed') THEN a.total_price ELSE 0 END), 0) AS revenue,
                    SUM(CASE WHEN a.status IN ('confirmed', 'completed') THEN 1 ELSE 0 END) AS revenue_orders,
                    COUNT(DISTINCT a.customer_id) AS unique_customers
                FROM appointments a
                " . $filter['where'];

    $stmtSummary = $conn->prepare($sqlSummary);
    $stmtSummary->execute($filter['params']);
    $summary = $stmtSummary->fetch(PDO::FETCH_ASSOC);

    $sqlRepeat = "SELECT COUNT(*) FROM (
                    SELECT a.customer_id
                    FROM appointments a
                    " . $filter['where'] . "
                    GROUP BY a.customer_id
                    HAVING COUNT(*) >= 2
                  ) repeat_customers";
    $repeatCustomers = (int) getSingleScalar($conn, $sqlRepeat, $filter['params']);

    return [
        'total_appointments' => (int) ($summary['total_appointments'] ?? 0),
        'completed_appointments' => (int) ($summary['completed_appointments'] ?? 0),
        'confirmed_appointments' => (int) ($summary['confirmed_appointments'] ?? 0),
        'pending_appointments' => (int) ($summary['pending_appointments'] ?? 0),
        'cancelled_appointments' => (int) ($summary['cancelled_appointments'] ?? 0),
        'revenue' => (float) ($summary['revenue'] ?? 0),
        'revenue_orders' => (int) ($summary['revenue_orders'] ?? 0),
        'unique_customers' => (int) ($summary['unique_customers'] ?? 0),
        'repeat_customers' => $repeatCustomers
    ];
}

try {
    $today = new DateTime('today');
    $defaultEndDate = $today->format('Y-m-d');
    $defaultStartDate = (clone $today)->modify('-29 days')->format('Y-m-d');

    $startDate = normalizeDateValue($_GET['start_date'] ?? '', $defaultStartDate);
    $endDate = normalizeDateValue($_GET['end_date'] ?? '', $defaultEndDate);
    $branchId = isset($_GET['branch_id']) ? (int) $_GET['branch_id'] : 0;
    if ($branchId < 0) {
        $branchId = 0;
    }

    $startObj = new DateTime($startDate);
    $endObj = new DateTime($endDate);
    if ($startObj > $endObj) {
        $temp = $startObj;
        $startObj = $endObj;
        $endObj = $temp;
    }

    $startDate = $startObj->format('Y-m-d');
    $endDate = $endObj->format('Y-m-d');

    $periodDays = (int) $startObj->diff($endObj)->days + 1;
    $prevEndObj = (clone $startObj)->modify('-1 day');
    $prevStartObj = (clone $prevEndObj)->modify('-' . ($periodDays - 1) . ' days');

    $prevStartDate = $prevStartObj->format('Y-m-d');
    $prevEndDate = $prevEndObj->format('Y-m-d');

    $currentSummary = getPeriodSummary($conn, $startDate, $endDate, $branchId);
    $previousSummary = getPeriodSummary($conn, $prevStartDate, $prevEndDate, $branchId);

    $sqlNewCustomers = "SELECT COUNT(*) FROM users 
                        WHERE role = 'customer' AND DATE(created_at) BETWEEN ? AND ?";
    $newCustomers = (int) getSingleScalar($conn, $sqlNewCustomers, [$startDate, $endDate]);
    $prevNewCustomers = (int) getSingleScalar($conn, $sqlNewCustomers, [$prevStartDate, $prevEndDate]);

    $pendingLeaveRequests = (int) getSingleScalar(
        $conn,
        "SELECT COUNT(*) FROM leave_requests WHERE status = 'pending'"
    );

    $completionRate = $currentSummary['total_appointments'] > 0
        ? round(($currentSummary['completed_appointments'] / $currentSummary['total_appointments']) * 100, 2)
        : 0.0;

    $cancellationRate = $currentSummary['total_appointments'] > 0
        ? round(($currentSummary['cancelled_appointments'] / $currentSummary['total_appointments']) * 100, 2)
        : 0.0;

    $avgTicket = $currentSummary['revenue_orders'] > 0
        ? round($currentSummary['revenue'] / $currentSummary['revenue_orders'], 2)
        : 0.0;

    $filter = buildAppointmentFilter($startDate, $endDate, $branchId);

    $sqlDailyTrend = "SELECT 
                        DATE(a.appointment_date) AS report_date,
                        COUNT(*) AS total_appointments,
                        SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed_appointments,
                        SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_appointments,
                        COALESCE(SUM(CASE WHEN a.status IN ('confirmed', 'completed') THEN a.total_price ELSE 0 END), 0) AS revenue
                      FROM appointments a
                      " . $filter['where'] . "
                      GROUP BY DATE(a.appointment_date)
                      ORDER BY report_date ASC";
    $stmtDaily = $conn->prepare($sqlDailyTrend);
    $stmtDaily->execute($filter['params']);
    $dailyTrend = $stmtDaily->fetchAll(PDO::FETCH_ASSOC);

    $sqlStatusDistribution = "SELECT a.status, COUNT(*) AS total
                              FROM appointments a
                              " . $filter['where'] . "
                              GROUP BY a.status
                              ORDER BY total DESC";
    $stmtStatus = $conn->prepare($sqlStatusDistribution);
    $stmtStatus->execute($filter['params']);
    $statusDistribution = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);

    $sqlTopServices = "SELECT 
                        s.service_name,
                        COUNT(*) AS total_appointments,
                        COALESCE(SUM(CASE WHEN a.status IN ('confirmed', 'completed') THEN a.total_price ELSE 0 END), 0) AS revenue
                      FROM appointments a
                      JOIN services s ON a.service_id = s.service_id
                      " . $filter['where'] . "
                      GROUP BY s.service_id, s.service_name
                      ORDER BY total_appointments DESC, revenue DESC
                      LIMIT 5";
    $stmtServices = $conn->prepare($sqlTopServices);
    $stmtServices->execute($filter['params']);
    $topServices = $stmtServices->fetchAll(PDO::FETCH_ASSOC);

    $sqlTopStylists = "SELECT 
                        st.stylist_id,
                        u.full_name AS stylist_name,
                        COUNT(*) AS total_appointments,
                        COALESCE(SUM(CASE WHEN a.status IN ('confirmed', 'completed') THEN a.total_price ELSE 0 END), 0) AS revenue
                      FROM appointments a
                      JOIN stylists st ON a.stylist_id = st.stylist_id
                      JOIN users u ON st.user_id = u.user_id
                      " . $filter['where'] . " AND a.stylist_id IS NOT NULL
                      GROUP BY st.stylist_id, u.full_name
                      ORDER BY total_appointments DESC, revenue DESC
                      LIMIT 5";
    $stmtStylists = $conn->prepare($sqlTopStylists);
    $stmtStylists->execute($filter['params']);
    $topStylists = $stmtStylists->fetchAll(PDO::FETCH_ASSOC);

    $sqlBranchPerformance = "SELECT 
                            b.branch_id,
                            b.branch_name,
                            COUNT(*) AS total_appointments,
                            SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed_appointments,
                            COALESCE(SUM(CASE WHEN a.status IN ('confirmed', 'completed') THEN a.total_price ELSE 0 END), 0) AS revenue
                          FROM appointments a
                          JOIN branches b ON a.branch_id = b.branch_id
                          " . $filter['where'] . "
                          GROUP BY b.branch_id, b.branch_name
                          ORDER BY revenue DESC, total_appointments DESC
                          LIMIT 10";
    $stmtBranches = $conn->prepare($sqlBranchPerformance);
    $stmtBranches->execute($filter['params']);
    $branchPerformance = $stmtBranches->fetchAll(PDO::FETCH_ASSOC);

    $sqlPeakHours = "SELECT 
                        CONCAT(LPAD(HOUR(a.appointment_time), 2, '0'), ':00') AS hour_slot,
                        COUNT(*) AS total
                     FROM appointments a
                     " . $filter['where'] . " AND a.status != 'cancelled'
                     GROUP BY HOUR(a.appointment_time)
                     ORDER BY total DESC, HOUR(a.appointment_time) ASC
                     LIMIT 5";
    $stmtPeakHours = $conn->prepare($sqlPeakHours);
    $stmtPeakHours->execute($filter['params']);
    $peakHours = $stmtPeakHours->fetchAll(PDO::FETCH_ASSOC);

    $deltas = [
        'total_appointments_pct' => calculateDeltaPercent($currentSummary['total_appointments'], $previousSummary['total_appointments']),
        'completed_appointments_pct' => calculateDeltaPercent($currentSummary['completed_appointments'], $previousSummary['completed_appointments']),
        'revenue_pct' => calculateDeltaPercent($currentSummary['revenue'], $previousSummary['revenue']),
        'unique_customers_pct' => calculateDeltaPercent($currentSummary['unique_customers'], $previousSummary['unique_customers']),
        'repeat_customers_pct' => calculateDeltaPercent($currentSummary['repeat_customers'], $previousSummary['repeat_customers']),
        'new_customers_pct' => calculateDeltaPercent($newCustomers, $prevNewCustomers)
    ];

    $alerts = [];
    if ($currentSummary['total_appointments'] === 0) {
        $alerts[] = [
            'level' => 'info',
            'message' => 'Khoảng thời gian đang chọn chưa có lịch hẹn nào phát sinh.'
        ];
    }
    if ($cancellationRate >= 20) {
        $alerts[] = [
            'level' => 'warning',
            'message' => 'Tỷ lệ hủy lịch đang cao (>= 20%). Nên kiểm tra chất lượng xác nhận lịch và phân bổ khung giờ.'
        ];
    }
    if ($currentSummary['pending_appointments'] >= 15) {
        $alerts[] = [
            'level' => 'warning',
            'message' => 'Số lịch hẹn chờ duyệt đang lớn. Admin nên xử lý sớm để tránh trễ trải nghiệm khách hàng.'
        ];
    }
    if ($deltas['revenue_pct'] <= -15) {
        $alerts[] = [
            'level' => 'warning',
            'message' => 'Doanh thu giảm mạnh so với kỳ trước (>= 15%). Nên rà soát chiến dịch và hiệu suất chi nhánh.'
        ];
    }
    if ($pendingLeaveRequests > 0) {
        $alerts[] = [
            'level' => 'info',
            'message' => 'Hiện có ' . $pendingLeaveRequests . ' đơn xin nghỉ phép đang chờ duyệt.'
        ];
    }

    echo json_encode([
        'status' => 'success',
        'filters' => [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'branch_id' => $branchId
        ],
        'summary' => [
            'total_appointments' => $currentSummary['total_appointments'],
            'completed_appointments' => $currentSummary['completed_appointments'],
            'confirmed_appointments' => $currentSummary['confirmed_appointments'],
            'pending_appointments' => $currentSummary['pending_appointments'],
            'cancelled_appointments' => $currentSummary['cancelled_appointments'],
            'revenue' => $currentSummary['revenue'],
            'completion_rate' => $completionRate,
            'cancellation_rate' => $cancellationRate,
            'avg_ticket' => $avgTicket,
            'unique_customers' => $currentSummary['unique_customers'],
            'repeat_customers' => $currentSummary['repeat_customers'],
            'new_customers' => $newCustomers,
            'pending_leave_requests' => $pendingLeaveRequests
        ],
        'comparison' => [
            'previous_period' => [
                'start_date' => $prevStartDate,
                'end_date' => $prevEndDate
            ],
            'deltas' => $deltas
        ],
        'charts' => [
            'daily_trend' => $dailyTrend,
            'status_distribution' => $statusDistribution,
            'top_services' => $topServices,
            'top_stylists' => $topStylists,
            'branch_performance' => $branchPerformance,
            'peak_hours' => $peakHours
        ],
        'alerts' => $alerts,
        'notes' => [
            'new_customers' => 'Khách mới được tính theo tài khoản customer tạo mới trong khoảng thời gian đã chọn.'
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Lỗi xử lý thống kê: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>