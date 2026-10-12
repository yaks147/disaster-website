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
        $title = trim((string) ($_POST['title'] ?? ''));
        $message = trim((string) ($_POST['message'] ?? ''));
        $publishDate = trim((string) ($_POST['publish_date'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'draft'));

        if ($title === '' || $message === '') {
            setFlash('announcement_error', 'Title and message are required.');
            redirect('announcements.php');
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO announcements (title, message, publish_date, status, created_by) VALUES (:title, :message, :publish_date, :status, :created_by)'
            );
            $stmt->execute([
                'title' => $title,
                'message' => $message,
                'publish_date' => $publishDate !== '' ? $publishDate : null,
                'status' => $status,
                'created_by' => $_SESSION['user']['id'],
            ]);

            logAdminActivity($pdo, 'created announcement', 'announcements', ['title' => $title]);
            setFlash('announcement_success', 'Announcement created successfully.');
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare(
                'UPDATE announcements
                 SET title = :title,
                     message = :message,
                     publish_date = :publish_date,
                     status = :status,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'title' => $title,
                'message' => $message,
                'publish_date' => $publishDate !== '' ? $publishDate : null,
                'status' => $status,
            ]);

            logAdminActivity($pdo, 'updated announcement', 'announcements', ['id' => $id]);
            setFlash('announcement_success', 'Announcement updated successfully.');
        }

        redirect('announcements.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM announcements WHERE id = :id');
        $stmt->execute(['id' => $id]);

        logAdminActivity($pdo, 'deleted announcement', 'announcements', ['id' => $id]);
        setFlash('announcement_success', 'Announcement deleted.');
        redirect('announcements.php');
    }
}

$editAnnouncement = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editStmt = $pdo->prepare('SELECT * FROM announcements WHERE id = :id LIMIT 1');
    $editStmt->execute(['id' => $editId]);
    $editAnnouncement = $editStmt->fetch();
}

$announcements = $pdo->query('SELECT * FROM announcements ORDER BY publish_date DESC, created_at DESC')->fetchAll();
$csrfToken = generateCsrfToken();
$announcementSuccess = getFlash('announcement_success');
$announcementError = getFlash('announcement_error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
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
                <a class="active" href="announcements.php">Announcements</a>
                <a href="versions.php">Game Versions</a>
                <a href="logs.php">Activity Logs</a>
                <a href="settings.php">Settings</a>
                <a href="../logout.php">Logout</a>
            </nav>
        </aside>

        <main class="admin-main">
            <header class="admin-topbar">
                <div>
                    <p class="eyebrow">Website Content</p>
                    <h1>Announcements</h1>
                </div>
                <div class="topbar-user">
                    <span>Logged in as <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                </div>
            </header>

            <?php if ($announcementSuccess): ?>
                <div class="alert success"><?php echo htmlspecialchars($announcementSuccess); ?></div>
            <?php endif; ?>
            <?php if ($announcementError): ?>
                <div class="alert error"><?php echo htmlspecialchars($announcementError); ?></div>
            <?php endif; ?>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo $editAnnouncement ? 'Edit Announcement' : 'Create Announcement'; ?></h2>
                </div>
                <form method="post" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="<?php echo $editAnnouncement ? 'update' : 'create'; ?>">
                    <?php if ($editAnnouncement): ?>
                        <input type="hidden" name="id" value="<?php echo (int) $editAnnouncement['id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="field-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($editAnnouncement['title'] ?? ''); ?>" required>
                        </div>
                        <div class="field-group">
                            <label for="publish_date">Publish Date</label>
                            <input type="datetime-local" id="publish_date" name="publish_date" value="<?php echo htmlspecialchars($editAnnouncement['publish_date'] ? date('Y-m-d\TH:i', strtotime($editAnnouncement['publish_date'])) : ''); ?>">
                        </div>
                        <div class="field-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="draft" <?php echo (($editAnnouncement['status'] ?? 'draft') === 'draft') ? 'selected' : ''; ?>>Draft</option>
                                <option value="published" <?php echo (($editAnnouncement['status'] ?? 'draft') === 'published') ? 'selected' : ''; ?>>Published</option>
                                <option value="archived" <?php echo (($editAnnouncement['status'] ?? 'draft') === 'archived') ? 'selected' : ''; ?>>Archived</option>
                            </select>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" rows="4" required><?php echo htmlspecialchars($editAnnouncement['message'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?php echo $editAnnouncement ? 'Save Changes' : 'Create Announcement'; ?></button>
                        <?php if ($editAnnouncement): ?>
                            <a href="announcements.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2>Announcement List</h2>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Publish Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($announcements)): ?>
                                <tr>
                                    <td colspan="4" class="table-empty">No announcements found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($announcements as $announcement): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($announcement['title']); ?></td>
                                        <td><span class="status-badge <?php echo htmlspecialchars($announcement['status']); ?>"><?php echo htmlspecialchars($announcement['status']); ?></span></td>
                                        <td><?php echo htmlspecialchars($announcement['publish_date'] ? date('M j, Y H:i', strtotime($announcement['publish_date'])) : 'Not scheduled'); ?></td>
                                        <td class="table-actions">
                                            <a href="announcements.php?edit=<?php echo (int) $announcement['id']; ?>" class="btn btn-secondary small">Edit</a>
                                            <form method="post" class="inline-form" onsubmit="return confirm('Delete this announcement?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int) $announcement['id']; ?>">
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
