<?php
require __DIR__ . '/../config.php';
requireAdmin('login.php');

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(u.username LIKE :search OR u.email LIKE :search OR p.scenario_slug LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($statusFilter === 'not_started' || $statusFilter === 'in_progress' || $statusFilter === 'completed') {
    $conditions[] = 'p.status = :status';
    $params['status'] = $statusFilter;
}

$whereClause = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';

$stmt = $pdo->prepare(
    'SELECT p.*, u.username, u.email
     FROM player_progress p
     JOIN users u ON u.id = p.user_id' . $whereClause . '
     ORDER BY p.updated_at DESC'
);
$stmt->execute($params);
$progressRows = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Progress</title>
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
                <a class="active" href="progress.php">Progress</a>
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
                    <p class="eyebrow">Player Insights</p>
                    <h1>Player Progress</h1>
                </div>
                <div class="topbar-user">
                    <span>Logged in as <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                </div>
            </header>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2>Filter Progress</h2>
                </div>
                <form method="get" class="admin-filter-form">
                    <div class="form-grid compact">
                        <div class="field-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Username, email, scenario">
                        </div>
                        <div class="field-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="not_started" <?php echo $statusFilter === 'not_started' ? 'selected' : ''; ?>>Not Started</option>
                                <option value="in_progress" <?php echo $statusFilter === 'in_progress' ? 'selected' : ''; ?>>In Progress</option>
                                <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary small">Apply Filters</button>
                    </div>
                </form>
            </section>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2>Progress Records</h2>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Player</th>
                                <th>Scenario</th>
                                <th>Status</th>
                                <th>Score</th>
                                <th>Updated</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($progressRows)): ?>
                                <tr>
                                    <td colspan="5" class="table-empty">No progress records found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($progressRows as $row): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                                        <td><?php echo htmlspecialchars(ucfirst($row['scenario_slug'])); ?></td>
                                        <td><span class="status-badge <?php echo htmlspecialchars($row['status']); ?>"><?php echo htmlspecialchars($row['status']); ?></span></td>
                                        <td><?php echo (int) $row['score']; ?></td>
                                        <td><?php echo htmlspecialchars(date('M j, Y H:i', strtotime($row['updated_at']))); ?></td>
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
