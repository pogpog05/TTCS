<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        echo json_encode(['status' => 'error', 'message' => 'Vui lòng nhập đầy đủ thông tin']);
        exit();
    }

    try {
        // Tìm tài khoản theo Email hoặc SĐT 
        $stmt = $conn->prepare("SELECT user_id, full_name, email, password, role FROM users WHERE email = ? OR phone = ?");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $isValid = false;
            
            // KIỂM TRA MẬT KHẨU (PHÂN BIỆT HOA THƯỜNG TUYỆT ĐỐI)
            if (password_verify($password, $user['password'])) {
                $isValid = true; 
            } elseif ($user['password'] === $password) {
                $isValid = true; 
            } elseif ($user['password'] === md5($password)) {
                $isValid = true; 
            }

            if ($isValid) {
                echo json_encode([
                    'status' => 'success',
                    'data' => [
                        'user_id' => $user['user_id'],
                        'full_name' => $user['full_name'],
                        'email' => $user['email'],
                        'role' => $user['role'],
                        'auth_method' => 'password'
                    ]
                ]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Tài khoản hoặc mật khẩu không chính xác']);
            }
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy tài khoản trong hệ thống']);
        }
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi hệ thống cơ sở dữ liệu']);
    }
}
?>