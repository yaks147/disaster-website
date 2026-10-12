<?php
require __DIR__ . '/../config.php';
requireAdmin('login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'toggle_active') {
        $id = (int) ($_POST['id'] ?? 0);
        $active = (int) ($_POST['active'] ?? 0);
        $stmt = $pdo->prepare('UPDATE users SET is_active = :is_active WHERE id = :id');
        $stmt->execute(['is_active' => $active, 'id' => $id]);

        logAdminActivity($pdo, 'updated user status', 'users', ['id' => $id, 'is_active' => $active]);
        setFlash('user_success', 'User status updated.');
        redirect('users.php');
    }
}

$search = trim((string) ($_GET['search'] ?? ''));
$statusFilter = trim((string) ($_GET['status'] ?? 'all'));
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;

$conditions = [];
$params = [];

if ($search !== '') {
    $conditions[] = '(username LIKE :search OR email LIKE :search)';
    $params['search'] = '%' . $search . '%';
}

if ($statusFilter === 'active' || $statusFilter === 'inactive') {
    $conditions[] = 'is_active = :is_active';
    $params['is_active'] = ($statusFilter === 'active') ? 1 : 0;
}

$whereClause = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$totalStmt = $pdo->prepare('SELECT COUNT(*) FROM users' . $whereClause);
$totalStmt->execute($params);
$totalUsers = (int) $totalStmt->fetchColumn();

$totalPages = max(1, (int) ceil($totalUsers / $perPage));
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare(
    'SELECT u.*, 
        (SELECT COUNT(*) FROM player_progress WHERE user_id = u.id AND status = "completed") AS completed_scenarios,
        (SELECT COUNT(*) FROM leaderboard WHERE user_id = u.id) AS game_sessions,
        (SELECT MAX(score) FROM leaderboard WHERE user_id = u.id) AS best_score
     FROM users u' . $whereClause . ' ORDER BY u.created_at DESC LIMIT :limit OFFSET :offset'
);

foreach ($params as $key => $value) {
    $listStmt->bindValue(':' . $key, $value);
}
$listStmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
$listStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$listStmt->execute();
$users = $listStmt->fetchAll();

$csrfToken = generateCsrfToken();
$userSuccess = getFlash('user_success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
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
                <a class="active" href="users.php">Players / Users</a>
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
                    <p class="eyebrow">User Management</p>
                    <h1>Players / Users</h1>
                </div>
                <div class="topbar-user">
                    <span>Logged in as <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                </div>
            </header>

            <?php if ($userSuccess): ?>
                <div class="alert success"><?php echo htmlspecialchars($userSuccess); ?></div>
            <?php endif; ?>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2>Search and Filter</h2>
                </div>
                <form method="get" class="admin-filter-form">
                    <div class="form-grid compact">
                        <div class="field-group">
                            <label for="search">Search</label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Username or email">
                        </div>
                        <div class="field-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="all" <?php echo $statusFilter === 'all' ? 'selected' : ''; ?>>All</option>
                                <option value="active" <?php echo $statusFilter === 'active' ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo $statusFilter === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
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
                    <h2>User Directory</h2>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Joined</th>
                                <th>Completed</th>
                                <th>Sessions</th>
                                <th>Best Score</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="9" class="table-empty">No users found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $userRow): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($userRow['username']); ?></td>
                                        <td><?php echo htmlspecialchars($userRow['email']); ?></td>
                                        <td><span class="status-badge <?php echo htmlspecialchars($userRow['role'] ?? 'user'); ?>"><?php echo htmlspecialchars($userRow['role'] ?? 'user'); ?></span></td>
                                        <td><span class="status-badge <?php echo ((int) $userRow['is_active'] === 1 ? 'active' : 'inactive'); ?>"><?php echo ((int) $userRow['is_active'] === 1 ? 'Active' : 'Inactive'); ?></span></td>
                                        <td><?php echo htmlspecialchars(date('M j, Y', strtotime($userRow['created_at']))); ?></td>
                                        <td><?php echo (int) $userRow['completed_scenarios']; ?></td>
                                        <td><?php echo (int) $userRow['game_sessions']; ?></td>
                                        <td><?php echo (int) $userRow['best_score']; ?></td>
                                        <td class="table-actions">
                                            <form method="post" class="inline-form" onsubmit="return confirm('Update this user status?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="id" value="<?php echo (int) $userRow['id']; ?>">
                                                <input type="hidden" name="active" value="<?php echo ((int) $userRow['is_active'] === 1 ? 0 : 1); ?>">
                                                <button type="submit" class="btn btn-secondary small"><?php echo ((int) $userRow['is_active'] === 1 ? 'Disable' : 'Enable'); ?></button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                            <a href="users.php?search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($statusFilter); ?>&page=<?php echo $pageNumber; ?>" class="btn btn-secondary small <?php echo $pageNumber === $page ? 'active-page' : ''; ?>"><?php echo $pageNumber; ?></a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </section>
        </main>
    </div>
</body>
</html>
