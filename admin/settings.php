<?php
require __DIR__ . '/../config.php';
requireAdmin('login.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCsrfToken($token)) {
        http_response_code(403);
        die('Invalid CSRF token.');
    }

    $siteName = trim((string) ($_POST['site_name'] ?? 'Disaster Ready'));
    $siteTagline = trim((string) ($_POST['site_tagline'] ?? 'Preparedness Simulation'));

    setFlash('settings_success', 'Settings saved.');
    logAdminActivity($pdo, 'updated settings', 'settings', ['site_name' => $siteName]);
    redirect('settings.php');
}

$settingsSuccess = getFlash('settings_success');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings</title>
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
                <a href="logs.php">Activity Logs</a>
                <a class="active" href="settings.php">Settings</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="eyebrow">System</p>
                    <h1>Settings</h1>
                </div>
                <div class="topbar-user">
                    <span>Logged in as <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                </div>
            </header>

            <?php if ($settingsSuccess): ?>
                <div class="alert success"><?php echo htmlspecialchars($settingsSuccess); ?></div>
            <?php endif; ?>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2>Website Settings</h2>
                </div>
                <form method="post" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">

                    <div class="form-grid">
                        <div class="field-group">
                            <label for="site_name">Site Name</label>
                            <input type="text" id="site_name" name="site_name" value="Disaster Ready">
                        </div>
                        <div class="field-group">
                            <label for="site_tagline">Tagline</label>
                            <input type="text" id="site_tagline" name="site_tagline" value="Preparedness Simulation">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </div>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
