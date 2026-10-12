<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only GET requests are allowed.']);
    exit;
}

try {
    $stmt = $pdo->query(
        'SELECT id, title, message, publish_date, status
         FROM announcements
         WHERE status = "published"
         ORDER BY publish_date DESC, created_at DESC'
    );

    echo json_encode([
        'success' => true,
        'announcements' => $stmt->fetchAll(),
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while loading announcements.']);
}
