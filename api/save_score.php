<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only POST requests are allowed.']);
    exit;
}

if (!isset($_SESSION['user']['id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Login required.']);
    exit;
}

if (!empty($_POST)) {
    $input = $_POST;
} else {
    $decodedInput = json_decode(file_get_contents('php://input'), true);
    $input = is_array($decodedInput) ? $decodedInput : [];
}

$allowedCategories = [
    'earthquake' => 'Earthquake',
    'fire' => 'Fire',
    'typhoon' => 'Typhoon',
    'volcano' => 'Volcano',
];
$categoryKey = strtolower(trim((string) ($input['category'] ?? '')));
$score = filter_var($input['score'] ?? null, FILTER_VALIDATE_INT);

if (!isset($allowedCategories[$categoryKey])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid disaster category.']);
    exit;
}

if ($score === false || $score < 0 || $score > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Score must be a whole number from 0 to 100.']);
    exit;
}

try {
    $userStmt = $pdo->prepare('SELECT id, username FROM users WHERE id = :id LIMIT 1');
    $userStmt->execute(['id' => (int) $_SESSION['user']['id']]);
    $user = $userStmt->fetch();

    if (!$user) {
        session_unset();
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Login required.']);
        exit;
    }

    $category = $allowedCategories[$categoryKey];
    $stmt = $pdo->prepare(
        'INSERT INTO leaderboard (user_id, username, category, score)
         VALUES (:user_id, :username, :category, :score)
         ON DUPLICATE KEY UPDATE
            username = VALUES(username),
            created_at = IF(VALUES(score) > score, CURRENT_TIMESTAMP, created_at),
            score = GREATEST(score, VALUES(score))'
    );
    $stmt->execute([
        'user_id' => (int) $user['id'],
        'username' => $user['username'],
        'category' => $category,
        'score' => $score,
    ]);

    $bestStmt = $pdo->prepare('SELECT score FROM leaderboard WHERE user_id = :user_id AND category = :category LIMIT 1');
    $bestStmt->execute(['user_id' => (int) $user['id'], 'category' => $category]);
    $bestScore = (int) $bestStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'message' => 'Score saved!',
        'best_score' => $bestScore,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while saving score.']);
}
