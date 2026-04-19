<?php
header('Content-Type: application/json; charset=utf-8');
require_once 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $json = json_decode(file_get_contents('php://input'), true);
    $data = $json ? $json : $_POST;

    $id    = $data['customer_id'] ?? '';
    $name  = trim($data['customer_name'] ?? '');
    $phone = trim($data['customer_phone'] ?? '');
    $email = trim($data['customer_email'] ?? '');
    $authMethod = strtolower(trim($data['auth_method'] ?? 'password'));

    if (empty($id) || empty($name)) {
        echo json_encode(['status' => 'error', 'message' => 'Thiếu thông tin cần cập nhật']);
        exit();
    }

    try {
        $checkUser = $conn->prepare("SELECT user_id, full_name, phone, email FROM users WHERE user_id = ? AND role = 'customer'");
        $checkUser->execute([$id]);

        $currentUser = $checkUser->fetch(PDO::FETCH_ASSOC);

        if (!$currentUser) {
            echo json_encode(['status' => 'error', 'message' => 'Không tìm thấy khách hàng hợp lệ']);
            exit();
        }

        if ($authMethod === 'google') {
            $email = $currentUser['email'] ?? '';
        }

        $name = $name !== '' ? $name : ($currentUser['full_name'] ?? '');
        $phone = $phone !== '' ? $phone : ($currentUser['phone'] ?? '');
        $email = $email !== '' ? $email : ($currentUser['email'] ?? '');

        if ($email !== '') {
            $checkEmail = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND user_id <> ? LIMIT 1");
            $checkEmail->execute([$email, $id]);
            if ($checkEmail->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode(['status' => 'error', 'message' => 'Email đã được sử dụng bởi tài khoản khác']);
                exit();
            }
        }

        if ($phone !== '') {
            $checkPhone = $conn->prepare("SELECT user_id FROM users WHERE phone = ? AND user_id <> ? LIMIT 1");
            $checkPhone->execute([$phone, $id]);
            if ($checkPhone->fetch(PDO::FETCH_ASSOC)) {
                echo json_encode(['status' => 'error', 'message' => 'Số điện thoại đã được sử dụng bởi tài khoản khác']);
                exit();
            }
        }

        $sql = "UPDATE users SET full_name = ?, phone = ?, email = ? WHERE user_id = ? AND role = 'customer'";
        $stmt = $conn->prepare($sql);
        $stmt->execute([$name, $phone, $email, $id]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Cập nhật thành công!',
            'data' => [
                'user_id' => $id,
                'full_name' => $name,
                'phone' => $phone,
                'email' => $email,
            ],
        ]);
    } catch(PDOException $e) {
        echo json_encode(['status' => 'error', 'message' => 'Lỗi cập nhật: ' . $e->getMessage()]);
    }
}
?>