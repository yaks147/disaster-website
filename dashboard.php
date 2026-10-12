<?php
require __DIR__ . '/config.php';

if (!isset($_SESSION['user'])) {
    redirect('login.php');
}

$user = $_SESSION['user'];

if (($user['role'] ?? 'user') === 'admin') {
    redirect('admin/dashboard.php');
}

$disasterCategories = [
    'Earthquake' => ['icon' => '🌎', 'description' => 'Practice safe response steps and evacuation planning.'],
    'Fire' => ['icon' => '🔥', 'description' => 'Learn quick actions for fire alarms, exits, and safe escapes.'],
    'Typhoon' => ['icon' => '🌧️', 'description' => 'Review sheltering, warning signs, and flooding preparation.'],
    'Volcano' => ['icon' => '⛰️', 'description' => 'Study ash safety, routes, and emergency communication steps.'],
];

$scoreStmt = $pdo->prepare('SELECT category, score FROM leaderboard WHERE user_id = :user_id');
$scoreStmt->execute(['user_id' => (int) $user['id']]);
$playerScores = [];
while ($row = $scoreStmt->fetch()) {
    $playerScores[$row['category']] = (int) $row['score'];
}

$leadingScores = [];
foreach ($disasterCategories as $category => $meta) {
    $leadingScores[$category] = $playerScores[$category] ?? 0;
}

$availableScores = array_values($leadingScores);
$overallScore = $availableScores ? (int) round(array_sum($availableScores) / count($availableScores)) : 0;
$bestScore = $availableScores ? max($availableScores) : 0;

$rankStmt = $pdo->prepare('SELECT username, category, score FROM leaderboard ORDER BY score DESC, created_at ASC, username ASC');
$rankStmt->execute();
$rankRows = $rankStmt->fetchAll();

$scoreTotals = [];
foreach ($rankRows as $row) {
    $username = $row['username'];
    $scoreTotals[$username] = ($scoreTotals[$username] ?? 0) + (int) $row['score'];
}
arsort($scoreTotals);

$leaderboardRank = null;
$rankIndex = 1;
foreach (array_keys($scoreTotals) as $leaderName) {
    if ($leaderName === $user['username']) {
        $leaderboardRank = $rankIndex;
        break;
    }
    $rankIndex++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="site-header">
        <nav class="navbar container">
            <a href="index.php" class="brand" aria-label="Disaster Ready home">
                <span class="brand-mark">⚡</span>
                <span class="brand-text">
                    <span class="brand-title">Disaster Ready</span>
                    <span class="brand-subtitle">Preparedness Simulation</span>
                </span>
            </a>
            <button class="nav-toggle" aria-label="Toggle navigation">☰</button>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="index.php#disaster-guides">Disaster Guides</a>
                <a href="leaderboard.php">Leaderboard</a>
                <a href="dashboard.php" class="active">Dashboard</a>
                <div class="nav-user">
                    <span class="nav-user-name">Hi, <?php echo htmlspecialchars($user['username']); ?></span>
                    <a href="logout.php" class="nav-logout">Logout</a>
                </div>
            </div>
        </nav>
    </header>

    <main class="dashboard-page container">
        <section class="dashboard-header reveal">
            <div>
                <p class="eyebrow">Preparedness Center</p>
                <h1>Welcome, <?php echo htmlspecialchars($user['username']); ?></h1>
            </div>
            <div class="dashboard-header-actions">
                <span class="welcome-tag">Player profile</span>
                <a href="logout.php" class="btn btn-secondary">Logout</a>
            </div>
        </section>

        <section class="stats-strip reveal">
            <div class="stat-panel highlight">
                <span class="stat-label">Overall score</span>
                <strong class="stat-value"><?php echo $overallScore; ?><span>/100</span></strong>
                <small>Across all disaster categories</small>
            </div>
            <div class="stat-panel">
                <span class="stat-label">Best score</span>
                <strong class="stat-value"><?php echo $bestScore; ?><span>/100</span></strong>
                <small>Highest scenario result</small>
            </div>
            <div class="stat-panel">
                <span class="stat-label">Leaderboard rank</span>
                <strong class="stat-value"><?php echo $leaderboardRank ? '#' . $leaderboardRank : 'N/A'; ?></strong>
                <small><?php echo $leaderboardRank ? 'Current standing' : 'Complete a challenge to unlock'; ?></small>
            </div>
        </section>

        <section class="progress-grid">
            <?php foreach ($disasterCategories as $category => $meta): ?>
                <?php
                    $score = $leadingScores[$category];
                    $progress = min(100, max(0, $score));
                    $status = $score >= 80 ? 'Excellent' : ($score >= 60 ? 'Strong' : ($score >= 40 ? 'Building' : 'Start')); 
                ?>
                <article class="progress-card reveal">
                    <div class="progress-head">
                        <div class="card-icon large"><?php echo $meta['icon']; ?></div>
                        <span class="score-badge"><?php echo $status; ?></span>
                    </div>
                    <h3><?php echo htmlspecialchars($category); ?></h3>
                    <p><?php echo htmlspecialchars($meta['description']); ?></p>

                    <div class="score-row">
                        <span class="score-label">Best score</span>
                        <span class="score-value"><?php echo $score; ?>/100</span>
                    </div>
                    <div class="progress-bar">
                        <span style="width: <?php echo $progress; ?>%"></span>
                    </div>

                    <button type="button" class="btn btn-primary small">Open Scenario</button>
                </article>
            <?php endforeach; ?>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container footer-content">
            <div>
                <strong>Disaster Ready</strong>
                <span>Disaster Preparedness Simulation</span>
            </div>
            <p>© 2026 Disaster Preparedness Simulation</p>
        </div>
    </footer>

    <script src="script.js"></script>
</body>
</html>
