<?php
session_start();

define('DB_HOST', 'localhost');
define('DB_NAME', 'disaster_game');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed. Please create the database first.');
}

function redirect($page) {
    header('Location: ' . $page);
    exit;
}

function setFlash($key, $message) {
    $_SESSION[$key] = $message;
}

function getFlash($key) {
    if (isset($_SESSION[$key])) {
        $message = $_SESSION[$key];
        unset($_SESSION[$key]);
        return $message;
    }

    return null;
}

function ensureDatabaseSchema(PDO $pdo) {
    $userColumns = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'")->fetchAll(PDO::FETCH_COLUMN);

    if (!in_array('role', $userColumns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER password_hash");
    }

    if (!in_array('is_active', $userColumns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER role");
    }

    if (!in_array('last_login_at', $userColumns, true)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login_at TIMESTAMP NULL DEFAULT NULL AFTER is_active");
    }

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS scenarios (
            id INT AUTO_INCREMENT PRIMARY KEY,
            slug VARCHAR(50) NOT NULL UNIQUE,
            title VARCHAR(120) NOT NULL,
            description TEXT,
            instructions TEXT,
            learning_objectives TEXT,
            difficulty VARCHAR(40) NOT NULL DEFAULT "Intermediate",
            status VARCHAR(20) NOT NULL DEFAULT "active",
            safety_info TEXT,
            completion_info TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS quiz_questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            scenario_id INT NOT NULL,
            question TEXT NOT NULL,
            option_a TEXT NOT NULL,
            option_b TEXT NOT NULL,
            option_c TEXT NOT NULL,
            option_d TEXT NOT NULL,
            correct_answer VARCHAR(10) NOT NULL,
            explanation TEXT,
            status VARCHAR(20) NOT NULL DEFAULT "active",
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (scenario_id) REFERENCES scenarios(id) ON DELETE CASCADE,
            KEY idx_questions_scenario (scenario_id)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS announcements (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(150) NOT NULL,
            message TEXT NOT NULL,
            publish_date TIMESTAMP NULL,
            status VARCHAR(20) NOT NULL DEFAULT "draft",
            created_by INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_announcements_status (status),
            KEY idx_announcements_publish_date (publish_date)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS game_versions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            version_number VARCHAR(50) NOT NULL,
            platform VARCHAR(50) NOT NULL DEFAULT "Windows",
            status VARCHAR(20) NOT NULL DEFAULT "active",
            release_notes TEXT,
            release_date TIMESTAMP NULL,
            download_url VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS player_progress (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            scenario_slug VARCHAR(50) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT "not_started",
            score INT NOT NULL DEFAULT 0,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_progress (user_id, scenario_slug),
            KEY idx_progress_user (user_id),
            KEY idx_progress_scenario (scenario_slug)
        )'
    );

    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS admin_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_id INT NOT NULL,
            action VARCHAR(120) NOT NULL,
            resource VARCHAR(120) NOT NULL,
            details JSON NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            KEY idx_logs_admin (admin_id),
            KEY idx_logs_created_at (created_at)
        )'
    );

    $versionCount = (int) $pdo->query('SELECT COUNT(*) FROM game_versions')->fetchColumn();
    if ($versionCount === 0) {
        $pdo->exec(
            "INSERT INTO game_versions (version_number, platform, status, release_notes, release_date)
             VALUES ('1.0.0', 'Windows', 'active', 'Initial game version tracked through the admin dashboard.', CURRENT_TIMESTAMP)"
        );
    }
}

function currentUser() {
    if (empty($_SESSION['user'])) {
        return null;
    }

    return $_SESSION['user'];
}

function isAdminUser() {
    $user = currentUser();
    return $user !== null && (($user['role'] ?? 'user') === 'admin');
}

function requireLogin($redirectPage = 'login.php') {
    if (!currentUser()) {
        redirect($redirectPage);
    }
}

function requireAdmin($redirectPage = '../login.php') {
    requireLogin($redirectPage);

    if (!isAdminUser()) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><body><h1>Access denied</h1><p>You do not have permission to view this page.</p></body></html>';
        exit;
    }
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    $expectedToken = $_SESSION['csrf_token'] ?? '';
    return is_string($token) && is_string($expectedToken) && hash_equals($expectedToken, $token);
}

function logAdminActivity(PDO $pdo, $action, $resource, $details = []) {
    $user = currentUser();

    if (!$user || ($user['role'] ?? 'user') !== 'admin') {
        return;
    }

    $detailsJson = json_encode($details);
    $stmt = $pdo->prepare(
        'INSERT INTO admin_activity_logs (admin_id, action, resource, details) VALUES (:admin_id, :action, :resource, :details)'
    );
    $stmt->execute([
        'admin_id' => (int) $user['id'],
        'action' => $action,
        'resource' => $resource,
        'details' => $detailsJson ?: '{}',
    ]);
}

function ensureDefaultScenarios(PDO $pdo) {
    $existingCount = (int) $pdo->query('SELECT COUNT(*) FROM scenarios')->fetchColumn();

    if ($existingCount > 0) {
        return;
    }

    $defaultScenarios = [
        [
            'slug' => 'earthquake',
            'title' => 'Earthquake',
            'description' => 'Practice safe response steps during sudden shaking, including drop, cover, and hold on.',
            'instructions' => 'Stay calm, drop under cover, protect your head, and wait for the shaking to stop before moving.',
            'learning_objectives' => 'Understand safe actions during earthquake shaking and how to avoid common hazards.',
            'difficulty' => 'Intermediate',
            'status' => 'active',
            'safety_info' => 'Avoid doorways, windows, and elevators; move away from falling objects.',
            'completion_info' => 'Scenario complete once the player chooses safe actions and confirms evacuation readiness.',
        ],
        [
            'slug' => 'fire',
            'title' => 'Fire',
            'description' => 'Learn how to respond quickly to smoke, alarms, and fire emergencies.',
            'instructions' => 'Alert others, use the nearest exit, and stay low if there is smoke.',
            'learning_objectives' => 'Recognize alarm signals, identify escape routes, and apply safe evacuation habits.',
            'difficulty' => 'Intermediate',
            'status' => 'active',
            'safety_info' => 'Do not use elevators; feel doors before opening them and use a safe exit route.',
            'completion_info' => 'Scenario complete once the player identifies the safest exit path and avoids unsafe actions.',
        ],
        [
            'slug' => 'typhoon',
            'title' => 'Typhoon',
            'description' => 'Prepare for strong winds, flooding, and sheltering during a storm emergency.',
            'instructions' => 'Move to a safe sheltered area, secure loose items, and follow official weather advisories.',
            'learning_objectives' => 'Understand how to prepare for severe weather and respond to flood and wind threats.',
            'difficulty' => 'Intermediate',
            'status' => 'active',
            'safety_info' => 'Avoid flooded roads, downed power lines, and unsecured outdoor areas.',
            'completion_info' => 'Scenario complete once the player chooses the safest shelter strategy and avoids hazardous areas.',
        ],
        [
            'slug' => 'volcano',
            'title' => 'Volcano',
            'description' => 'Review ash fall safety, evacuation coordination, and emergency communication when volcanic activity rises.',
            'instructions' => 'Monitor official alerts, evacuate when directed, and protect your airway if ash is present.',
            'learning_objectives' => 'Recognize volcano warning signs, plan evacuation steps, and protect health during ash exposure.',
            'difficulty' => 'Advanced',
            'status' => 'active',
            'safety_info' => 'Use masks or cloths to reduce ash inhalation and stay out of low-lying areas.',
            'completion_info' => 'Scenario complete once the player chooses protective actions and follows official guidance.',
        ],
    ];

    foreach ($defaultScenarios as $scenario) {
        $stmt = $pdo->prepare(
            'INSERT INTO scenarios (slug, title, description, instructions, learning_objectives, difficulty, status, safety_info, completion_info)
             VALUES (:slug, :title, :description, :instructions, :learning_objectives, :difficulty, :status, :safety_info, :completion_info)'
        );
        $stmt->execute($scenario);
    }
}

ensureDatabaseSchema($pdo);
ensureDefaultScenarios($pdo);
