<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Only POST requests are allowed.',
    ]);
    exit;
}

if (!empty($_POST)) {
    $input = $_POST;
} else {
    $rawInput = file_get_contents('php://input');
    $decodedInput = json_decode($rawInput, true);
    $input = is_array($decodedInput) ? $decodedInput : [];
}

$username = trim((string) ($input['username'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');
$confirmPassword = (string) ($input['confirm_password'] ?? '');

if ($username === '' || $email === '' || $password === '') {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Username, email, and password are required.',
    ]);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Please enter a valid email address.',
    ]);
    exit;
}

if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 6 characters long.',
    ]);
    exit;
}

if ($confirmPassword !== '' && $password !== $confirmPassword) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Passwords do not match.',
    ]);
    exit;
}

try {
    $usernameCheck = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
    $usernameCheck->execute(['username' => $username]);

    if ($usernameCheck->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Username is already taken.',
        ]);
        exit;
    }

    $emailCheck = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
    $emailCheck->execute(['email' => $email]);

    if ($emailCheck->fetch()) {
        http_response_code(409);
        echo json_encode([
            'success' => false,
            'message' => 'Email is already registered.',
        ]);
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)');
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password_hash' => $passwordHash,
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Registration successful',
        'username' => $username,
        'email' => $email,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error while creating the account.',
    ]);
}
