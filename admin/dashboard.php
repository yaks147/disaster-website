<?php
require __DIR__ . '/../config.php';
requireAdmin('login.php');

$playersCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
$activePlayersCount = (int) $pdo->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();
$completedScenariosCount = (int) $pdo->query("SELECT COUNT(*) FROM player_progress WHERE status = 'completed'")->fetchColumn();
$totalSessionsCount = (int) $pdo->query('SELECT COUNT(*) FROM leaderboard')->fetchColumn();

$recentPlayers = $pdo->query(
    'SELECT id, username, email, created_at FROM users ORDER BY created_at DESC LIMIT 5'
)->fetchAll();

$recentCompletions = $pdo->query(
    'SELECT p.updated_at, p.scenario_slug, p.status, p.score, u.username
     FROM player_progress p
     JOIN users u ON u.id = p.user_id
     WHERE p.status = "completed"
     ORDER BY p.updated_at DESC
     LIMIT 5'
)->fetchAll();

$recentLogs = $pdo->query(
    'SELECT l.*, u.username AS admin_name
     FROM admin_activity_logs l
     JOIN users u ON u.id = l.admin_id
     ORDER BY l.created_at DESC
     LIMIT 5'
)->fetchAll();

$latestAnnouncement = $pdo->query(
    'SELECT * FROM announcements WHERE status = "published" ORDER BY publish_date DESC LIMIT 1'
)->fetch();

$currentVersion = $pdo->query(
    'SELECT * FROM game_versions WHERE status = "active" ORDER BY release_date DESC, created_at DESC LIMIT 1'
)->fetch();

$dashboardStats = [
    ['label' => 'Total Players', 'value' => number_format($playersCount), 'caption' => 'All registered accounts'],
    ['label' => 'Active Players', 'value' => number_format($activePlayersCount), 'caption' => 'Accounts currently enabled'],
    ['label' => 'Completed Scenarios', 'value' => number_format($completedScenariosCount), 'caption' => 'Completed player progress records'],
    ['label' => 'Total Game Sessions', 'value' => number_format($totalSessionsCount), 'caption' => 'Saved score entries'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="admin-body">
    <div class="admin-layout">
        <aside class="admin-sidebar">
            <div class="admin-brand">
                <span class="brand-mark">⚡</span>
                <div>
                    <strong>Disaster Ready</strong>
                    <small>Admin</small>
                </div>
            </div>

            <nav class="admin-nav">
                <a class="active" href="dashboard.php">Dashboard</a>
                <a href="scenarios.php">Game Management</a>
                <a href="quizzes.php">Quizzes</a>
                <a href="users.php">Players / Users</a>
                <a href="progress.php">Progress</a>
                <a href="announcements.php">Announcements</a>
                <a href="versions.php">Game Versions</a>
                <a href="logs.php">Activity Logs</a>
                <a href="settings.php">Settings</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="eyebrow">Overview</p>
                    <h1>Dashboard</h1>
                </div>
                <div class="topbar-user">
                    <span>Logged in as <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                    <a href="../logout.php" class="btn btn-secondary small">Logout</a>
                </div>
            </header>

            <section class="stats-strip admin-stats">
                <?php foreach ($dashboardStats as $stat): ?>
                    <article class="stat-panel highlight">
                        <span class="stat-label"><?php echo htmlspecialchars($stat['label']); ?></span>
                        <strong class="stat-value"><?php echo $stat['value']; ?></strong>
                        <small><?php echo htmlspecialchars($stat['caption']); ?></small>
                    </article>
                <?php endforeach; ?>
            </section>

            <section class="admin-grid">
                <article class="admin-panel">
                    <div class="panel-header">
                        <h2>Recent Players</h2>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentPlayers)): ?>
                                    <tr>
                                        <td colspan="3" class="table-empty">No players yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentPlayers as $player): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($player['username']); ?></td>
                                            <td><?php echo htmlspecialchars($player['email']); ?></td>
                                            <td><?php echo htmlspecialchars(date('M j, Y', strtotime($player['created_at']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>

                <article class="admin-panel">
                    <div class="panel-header">
                        <h2>Recent Scenario Completions</h2>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>Player</th>
                                    <th>Scenario</th>
                                    <th>Score</th>
                                    <th>Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($recentCompletions)): ?>
                                    <tr>
                                        <td colspan="4" class="table-empty">No completed scenarios yet.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($recentCompletions as $completion): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($completion['username']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($completion['scenario_slug'])); ?></td>
                                            <td><?php echo (int) $completion['score']; ?></td>
                                            <td><?php echo htmlspecialchars(date('M j, Y', strtotime($completion['updated_at']))); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </article>
            </section>

            <section class="admin-grid">
                <article class="admin-panel">
                    <div class="panel-header">
                        <h2>Recent Admin Changes</h2>
                    </div>
                    <div class="feed-list">
                        <?php if (empty($recentLogs)): ?>
                            <div class="empty-state">No recent admin activity.</div>
                        <?php else: ?>
                            <?php foreach ($recentLogs as $log): ?>
                                <div class="feed-item">
                                    <strong><?php echo htmlspecialchars($log['action']); ?></strong>
                                    <span><?php echo htmlspecialchars($log['admin_name']); ?></span>
                                    <small><?php echo htmlspecialchars($log['resource']); ?></small>
                                    <time><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($log['created_at']))); ?></time>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </article>

                <article class="admin-panel">
                    <div class="panel-header">
                        <h2>Game Version</h2>
                    </div>
                    <div class="info-stack">
                        <?php if ($currentVersion): ?>
                            <div class="kpi-row">
                                <span>Current Version</span>
                                <strong><?php echo htmlspecialchars($currentVersion['version_number']); ?></strong>
                            </div>
                            <div class="kpi-row">
                                <span>Platform</span>
                                <strong><?php echo htmlspecialchars($currentVersion['platform']); ?></strong>
                            </div>
                            <div class="kpi-row">
                                <span>Status</span>
                                <strong><?php echo htmlspecialchars($currentVersion['status']); ?></strong>
                            </div>
                            <p><?php echo nl2br(htmlspecialchars($currentVersion['release_notes'] ?? 'No release notes recorded yet.')); ?></p>
                        <?php else: ?>
                            <div class="empty-state">No active game version is configured.</div>
                        <?php endif; ?>
                    </div>
                </article>
            </section>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2>Recent Announcements</h2>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Published Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $recentAnnouncements = $pdo->query(
                                'SELECT * FROM announcements ORDER BY publish_date DESC, created_at DESC LIMIT 5'
                            )->fetchAll();
                            ?>
                            <?php if (empty($recentAnnouncements)): ?>
                                <tr>
                                    <td colspan="3" class="table-empty">No announcements yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($recentAnnouncements as $announcement): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                        <td><span class="status-badge <?php echo htmlspecialchars($announcement['status']); ?>"><?php echo htmlspecialchars($announcement['status']); ?></span></td>
                                        <td><?php echo htmlspecialchars($announcement['publish_date'] ? date('M j, Y', strtotime($announcement['publish_date'])) : 'Not scheduled'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </main>
    </div>
</body>
</html>
