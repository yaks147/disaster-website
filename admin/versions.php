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

    if ($action === 'create' || $action === 'update') {
        $versionNumber = trim((string) ($_POST['version_number'] ?? ''));
        $platform = trim((string) ($_POST['platform'] ?? 'Windows'));
        $status = trim((string) ($_POST['status'] ?? 'active'));
        $releaseNotes = trim((string) ($_POST['release_notes'] ?? ''));
        $releaseDate = trim((string) ($_POST['release_date'] ?? ''));
        $downloadUrl = trim((string) ($_POST['download_url'] ?? ''));

        if ($versionNumber === '') {
            setFlash('version_error', 'Version number is required.');
            redirect('versions.php');
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO game_versions (version_number, platform, status, release_notes, release_date, download_url)
                 VALUES (:version_number, :platform, :status, :release_notes, :release_date, :download_url)'
            );
            $stmt->execute([
                'version_number' => $versionNumber,
                'platform' => $platform,
                'status' => $status,
                'release_notes' => $releaseNotes,
                'release_date' => $releaseDate !== '' ? $releaseDate : null,
                'download_url' => $downloadUrl !== '' ? $downloadUrl : null,
            ]);

            logAdminActivity($pdo, 'created game version', 'game_versions', ['version_number' => $versionNumber]);
            setFlash('version_success', 'Game version created.');
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare(
                'UPDATE game_versions
                 SET version_number = :version_number,
                     platform = :platform,
                     status = :status,
                     release_notes = :release_notes,
                     release_date = :release_date,
                     download_url = :download_url,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'version_number' => $versionNumber,
                'platform' => $platform,
                'status' => $status,
                'release_notes' => $releaseNotes,
                'release_date' => $releaseDate !== '' ? $releaseDate : null,
                'download_url' => $downloadUrl !== '' ? $downloadUrl : null,
            ]);

            logAdminActivity($pdo, 'updated game version', 'game_versions', ['id' => $id]);
            setFlash('version_success', 'Game version updated.');
        }

        redirect('versions.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM game_versions WHERE id = :id');
        $stmt->execute(['id' => $id]);

        logAdminActivity($pdo, 'deleted game version', 'game_versions', ['id' => $id]);
        setFlash('version_success', 'Game version deleted.');
        redirect('versions.php');
    }
}

$editVersion = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editStmt = $pdo->prepare('SELECT * FROM game_versions WHERE id = :id LIMIT 1');
    $editStmt->execute(['id' => $editId]);
    $editVersion = $editStmt->fetch();
}

$versions = $pdo->query('SELECT * FROM game_versions ORDER BY release_date DESC, created_at DESC')->fetchAll();
$csrfToken = generateCsrfToken();
$versionSuccess = getFlash('version_success');
$versionError = getFlash('version_error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Versions</title>
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
                <a class="active" href="versions.php">Game Versions</a>
                <a href="logs.php">Activity Logs</a>
                <a href="settings.php">Settings</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="eyebrow">Game Release</p>
                    <h1>Game Versions</h1>
                </div>
                <div class="topbar-user">
                    <span>Logged in as <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                </div>
            </header>

            <?php if ($versionSuccess): ?>
                <div class="alert success"><?php echo htmlspecialchars($versionSuccess); ?></div>
            <?php endif; ?>
            <?php if ($versionError): ?>
                <div class="alert error"><?php echo htmlspecialchars($versionError); ?></div>
            <?php endif; ?>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo $editVersion ? 'Edit Version' : 'Create Version'; ?></h2>
                </div>
                <form method="post" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="<?php echo $editVersion ? 'update' : 'create'; ?>">
                    <?php if ($editVersion): ?>
                        <input type="hidden" name="id" value="<?php echo (int) $editVersion['id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="field-group">
                            <label for="version_number">Version Number</label>
                            <input type="text" id="version_number" name="version_number" value="<?php echo htmlspecialchars($editVersion['version_number'] ?? ''); ?>" required>
                        </div>
                        <div class="field-group">
                            <label for="platform">Platform</label>
                            <input type="text" id="platform" name="platform" value="<?php echo htmlspecialchars($editVersion['platform'] ?? 'Windows'); ?>">
                        </div>
                        <div class="field-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo (($editVersion['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (($editVersion['status'] ?? 'active') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="release_notes">Release Notes</label>
                        <textarea id="release_notes" name="release_notes" rows="4"><?php echo htmlspecialchars($editVersion['release_notes'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="field-group">
                            <label for="release_date">Release Date</label>
                            <input type="datetime-local" id="release_date" name="release_date" value="<?php echo htmlspecialchars($editVersion['release_date'] ? date('Y-m-d\TH:i', strtotime($editVersion['release_date'])) : ''); ?>">
                        </div>
                        <div class="field-group">
                            <label for="download_url">Download/Update URL</label>
                            <input type="url" id="download_url" name="download_url" value="<?php echo htmlspecialchars($editVersion['download_url'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?php echo $editVersion ? 'Save Changes' : 'Create Version'; ?></button>
                        <?php if ($editVersion): ?>
                            <a href="versions.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2>Version List</h2>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Version</th>
                                <th>Platform</th>
                                <th>Status</th>
                                <th>Release Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($versions)): ?>
                                <tr>
                                    <td colspan="5" class="table-empty">No versions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($versions as $version): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($version['version_number']); ?></td>
                                        <td><?php echo htmlspecialchars($version['platform']); ?></td>
                                        <td><span class="status-badge <?php echo htmlspecialchars($version['status']); ?>"><?php echo htmlspecialchars($version['status']); ?></span></td>
                                        <td><?php echo htmlspecialchars($version['release_date'] ? date('M j, Y H:i', strtotime($version['release_date'])) : 'Not scheduled'); ?></td>
                                        <td class="table-actions">
                                            <a href="versions.php?edit=<?php echo (int) $version['id']; ?>" class="btn btn-secondary small">Edit</a>
                                            <form method="post" class="inline-form" onsubmit="return confirm('Delete this version?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int) $version['id']; ?>">
                                                <button type="submit" class="btn btn-danger small">Delete</button>
                                            </form>
                                        </td>
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
