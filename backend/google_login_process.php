<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

define('GOOGLE_CLIENT_ID', '89603645494-8802h5d9olbt5l1lofgviauji36o1urh.apps.googleusercontent.com');

function getRequestData()
{
    $contentType = $_SERVER['CONTENT_TYPE'] ?? $_SERVER['HTTP_CONTENT_TYPE'] ?? '';
    if (stripos($contentType, 'application/json') !== false) {
        $rawInput = file_get_contents('php://input');
        $data = json_decode($rawInput, true);
        return is_array($data) ? $data : [];
    }

    return $_POST;
}

function requestTokenInfo($credential)
{
    $url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);

    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        curl_close($ch);
        return $response;
    }

    return @file_get_contents($url);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['status' => 'error', 'message' => 'Phương thức không hợp lệ']);
    exit();
}

$data = getRequestData();
$credential = trim($data['credential'] ?? '');

if ($credential === '') {
    echo json_encode(['status' => 'error', 'message' => 'Thiếu mã xác thực Google']);
    exit();
}

$tokenInfoResponse = requestTokenInfo($credential);
if (!$tokenInfoResponse) {
    echo json_encode(['status' => 'error', 'message' => 'Không thể xác thực với Google']);
    exit();
}

$tokenInfo = json_decode($tokenInfoResponse, true);
if (!is_array($tokenInfo) || empty($tokenInfo['email'])) {
    echo json_encode(['status' => 'error', 'message' => 'Google token không hợp lệ']);
    exit();
}

if (($tokenInfo['aud'] ?? '') !== GOOGLE_CLIENT_ID) {
    echo json_encode(['status' => 'error', 'message' => 'Google token không khớp với ứng dụng của bạn']);
    exit();
}

$emailVerified = $tokenInfo['email_verified'] ?? false;
if ($emailVerified !== true && $emailVerified !== 'true') {
    echo json_encode(['status' => 'error', 'message' => 'Email Google chưa được xác minh']);
    exit();
}

$email = trim($tokenInfo['email']);
$fullName = trim($tokenInfo['name'] ?? $tokenInfo['given_name'] ?? 'Google User');

if ($fullName === '') {
    $fullName = 'Google User';
}

$randomPassword = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);

try {
    $stmt = $conn->prepare("SELECT user_id, full_name, email, role FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $insertStmt = $conn->prepare("INSERT INTO users (full_name, phone, email, password, role) VALUES (?, ?, ?, ?, 'customer')");
        $insertStmt->execute([$fullName, '', $email, $randomPassword]);

        $newUserId = $conn->lastInsertId();

        echo json_encode([
            'status' => 'success',
            'data' => [
                'user_id' => $newUserId,
                'full_name' => $fullName,
                'email' => $email,
                'role' => 'customer',
                'auth_method' => 'google'
            ],
            'message' => 'Đăng nhập Google thành công và đã tạo tài khoản mới'
        ]);
        exit();
    }

    echo json_encode([
        'status' => 'success',
        'data' => [
            'user_id' => $user['user_id'],
            'full_name' => $user['full_name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'auth_method' => 'google'
        ]
    ]);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống cơ sở dữ liệu']);
}
?>