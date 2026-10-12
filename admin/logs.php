<?php
require __DIR__ . '/../config.php';
requireAdmin('login.php');

$logs = $pdo->query(
    'SELECT l.*, u.username AS admin_name
     FROM admin_activity_logs l
     JOIN users u ON u.id = l.admin_id
     ORDER BY l.created_at DESC
     LIMIT 100'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logs</title>
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
                <a href="dashboard.php">Dashboard</a>
                <a href="scenarios.php">Game Management</a>
                <a href="quizzes.php">Quizzes</a>
                <a href="users.php">Players / Users</a>
                <a href="progress.php">Progress</a>
                <a href="announcements.php">Announcements</a>
                <a href="versions.php">Game Versions</a>
                <a class="active" href="logs.php">Activity Logs</a>
                <a href="settings.php">Settings</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="eyebrow">System Activity</p>
                    <h1>Activity Logs</h1>
                </div>
                <div class="topbar-user">
                    <span>Logged in as <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                </div>
            </header>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2>Recent Admin Activity</h2>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Admin</th>
                                <th>Action</th>
                                <th>Resource</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="5" class="table-empty">No activity logged yet.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($log['created_at']))); ?></td>
                                        <td><?php echo htmlspecialchars($log['admin_name']); ?></td>
                                        <td><?php echo htmlspecialchars($log['action']); ?></td>
                                        <td><?php echo htmlspecialchars($log['resource']); ?></td>
                                        <td><?php echo htmlspecialchars($log['details'] ?? ''); ?></td>
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
