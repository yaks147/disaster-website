<?php
require __DIR__ . '/config.php';

$categories = ['Overall', 'Earthquake', 'Fire', 'Typhoon', 'Volcano'];
$requested = trim((string) ($_GET['category'] ?? 'Overall'));
$category = 'Overall';
foreach ($categories as $allowed) {
    if (strcasecmp($requested, $allowed) === 0) {
        $category = $allowed;
        break;
    }
}

if ($category === 'Overall') {
    $stmt = $pdo->prepare('SELECT username, category, score FROM leaderboard ORDER BY score DESC, created_at ASC, username ASC LIMIT 100');
    $stmt->execute();
} else {
    $stmt = $pdo->prepare('SELECT username, category, score FROM leaderboard WHERE category = :category ORDER BY score DESC, created_at ASC, username ASC LIMIT 100');
    $stmt->execute(['category' => $category]);
}
$leaders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboard</title>
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
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="dashboard.php">Dashboard</a>
                <?php endif; ?>
                <a href="leaderboard.php" class="active">Leaderboard</a>
                <?php if (isset($_SESSION['user'])): ?>
                    <div class="nav-user">
                        <span class="nav-user-name">Hi, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                        <a href="logout.php" class="nav-logout">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="nav-register">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main class="dashboard-page container leaderboard-page">
        <section class="dashboard-header reveal">
            <div>
                <p class="eyebrow">Top preparedness scores</p>
                <h1>Leaderboard</h1>
            </div>
        </section>

        <section class="leaderboard-controls reveal">
            <div class="category-tabs">
                <?php foreach ($categories as $tab): ?>
                    <a class="tab-btn <?php echo $tab === $category ? 'active' : ''; ?>"
                       href="?category=<?php echo urlencode($tab); ?>"><?php echo htmlspecialchars($tab); ?></a>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="leaderboard-panel reveal">
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Rank</th>
                            <th>Player</th>
                            <th>Score</th>
                            <th>Disaster category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$leaders): ?>
                            <tr><td colspan="4" class="table-empty">No scores yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($leaders as $index => $leader): ?>
                                <tr class="leader-row top-<?php echo $index + 1; ?>">
                                    <td>
                                        <?php if ($index === 0): ?>
                                            <span class="medal medal-gold">🥇</span>
                                        <?php elseif ($index === 1): ?>
                                            <span class="medal medal-silver">🥈</span>
                                        <?php elseif ($index === 2): ?>
                                            <span class="medal medal-bronze">🥉</span>
                                        <?php endif; ?>
                                        <span class="rank-text"><?php echo $index + 1; ?></span>
                                    </td>
                                    <td><?php echo htmlspecialchars($leader['username']); ?></td>
                                    <td><?php echo (int) $leader['score']; ?></td>
                                    <td><?php echo htmlspecialchars($leader['category']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
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
