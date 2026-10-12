<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

$allowedCategories = ['Earthquake', 'Fire', 'Typhoon', 'Volcano'];
$requested = trim((string) ($_GET['category'] ?? 'Overall'));
$category = 'Overall';
foreach ($allowedCategories as $allowed) {
    if (strcasecmp($requested, $allowed) === 0) {
        $category = $allowed;
        break;
    }
}

if (strcasecmp($requested, 'Overall') !== 0 && $category === 'Overall') {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid disaster category.', 'leaderboard' => []]);
    exit;
}

try {
    if ($category === 'Overall') {
        $stmt = $pdo->prepare(
            'SELECT username, category, score
             FROM leaderboard
             ORDER BY score DESC, created_at ASC, username ASC
             LIMIT 100'
        );
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare(
            'SELECT username, category, score
             FROM leaderboard
             WHERE category = :category
             ORDER BY score DESC, created_at ASC, username ASC
             LIMIT 100'
        );
        $stmt->execute(['category' => $category]);
    }

    $rows = [];
    $rank = 1;
    foreach ($stmt->fetchAll() as $row) {
        $rows[] = [
            'rank' => $rank++,
            'username' => $row['username'],
            'category' => $row['category'],
            'score' => (int) $row['score'],
        ];
    }

    echo json_encode(['success' => true, 'leaderboard' => $rows]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while loading leaderboard.', 'leaderboard' => []]);
}
