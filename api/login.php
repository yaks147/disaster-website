<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

if (!empty($_POST)) {
    $input = $_POST;
} else {
    $decodedInput = json_decode(file_get_contents('php://input'), true);
    $input = is_array($decodedInput) ? $decodedInput : [];
}

$loginIdentifier = trim((string) ($input['username'] ?? $input['email'] ?? $input['login_identifier'] ?? ''));
$password = (string) ($input['password'] ?? '');

if ($loginIdentifier === '' || $password === '') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Username/email and password are required.']);
    exit;
}

try {
    $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role, is_active FROM users WHERE username = :value OR email = :value LIMIT 1');
    $stmt->execute(['value' => $loginIdentifier]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash']) && (int) $user['is_active'] === 1) {
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role'] ?? 'user',
        ];

        $updateStmt = $pdo->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
        $updateStmt->execute(['id' => (int) $user['id']]);

        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'username' => $user['username'],
            'email' => $user['email'],
            'user_id' => (int) $user['id'],
            'role' => $user['role'] ?? 'user',
        ]);
        exit;
    }

    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Invalid username or password']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while logging in.']);
}
