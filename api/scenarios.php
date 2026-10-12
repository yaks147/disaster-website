<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Only GET requests are allowed.']);
    exit;
}

try {
    $scenarioParam = trim((string) ($_GET['scenario'] ?? ''));

    if ($scenarioParam !== '') {
        $stmt = $pdo->prepare(
            'SELECT id, slug, title, description, instructions, learning_objectives, difficulty, status, safety_info, completion_info
             FROM scenarios
             WHERE slug = :slug AND status = "active"
             LIMIT 1'
        );
        $stmt->execute(['slug' => $scenarioParam]);
        $scenario = $stmt->fetch();

        if (!$scenario) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Scenario not found.']);
            exit;
        }

        echo json_encode(['success' => true, 'scenario' => $scenario]);
        exit;
    }

    $stmt = $pdo->query(
        'SELECT id, slug, title, description, instructions, learning_objectives, difficulty, status, safety_info, completion_info
         FROM scenarios
         WHERE status = "active"
         ORDER BY title ASC'
    );

    $scenarios = $stmt->fetchAll();
    echo json_encode(['success' => true, 'scenarios' => $scenarios]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error while loading scenarios.']);
}
