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
        $id = (int) ($_POST['id'] ?? 0);
        $slug = strtolower(trim((string) ($_POST['slug'] ?? '')));
        $title = trim((string) ($_POST['title'] ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $instructions = trim((string) ($_POST['instructions'] ?? ''));
        $learningObjectives = trim((string) ($_POST['learning_objectives'] ?? ''));
        $difficulty = trim((string) ($_POST['difficulty'] ?? 'Intermediate'));
        $status = trim((string) ($_POST['status'] ?? 'active'));
        $safetyInfo = trim((string) ($_POST['safety_info'] ?? ''));
        $completionInfo = trim((string) ($_POST['completion_info'] ?? ''));

        if ($slug === '' || $title === '') {
            setFlash('scenario_error', 'Scenario slug and title are required.');
            redirect('scenarios.php');
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO scenarios (slug, title, description, instructions, learning_objectives, difficulty, status, safety_info, completion_info)
                 VALUES (:slug, :title, :description, :instructions, :learning_objectives, :difficulty, :status, :safety_info, :completion_info)'
            );
            $stmt->execute([
                'slug' => $slug,
                'title' => $title,
                'description' => $description,
                'instructions' => $instructions,
                'learning_objectives' => $learningObjectives,
                'difficulty' => $difficulty,
                'status' => $status,
                'safety_info' => $safetyInfo,
                'completion_info' => $completionInfo,
            ]);

            logAdminActivity($pdo, 'created scenario', 'scenarios', ['slug' => $slug, 'title' => $title]);
            setFlash('scenario_success', 'Scenario created successfully.');
        } else {
            $stmt = $pdo->prepare(
                'UPDATE scenarios
                 SET slug = :slug,
                     title = :title,
                     description = :description,
                     instructions = :instructions,
                     learning_objectives = :learning_objectives,
                     difficulty = :difficulty,
                     status = :status,
                     safety_info = :safety_info,
                     completion_info = :completion_info,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'slug' => $slug,
                'title' => $title,
                'description' => $description,
                'instructions' => $instructions,
                'learning_objectives' => $learningObjectives,
                'difficulty' => $difficulty,
                'status' => $status,
                'safety_info' => $safetyInfo,
                'completion_info' => $completionInfo,
            ]);

            logAdminActivity($pdo, 'updated scenario', 'scenarios', ['id' => $id, 'slug' => $slug]);
            setFlash('scenario_success', 'Scenario updated successfully.');
        }

        redirect('scenarios.php');
    }

    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'inactive';

        $stmt = $pdo->prepare('UPDATE scenarios SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        logAdminActivity($pdo, 'updated scenario status', 'scenarios', ['id' => $id, 'status' => $status]);
        setFlash('scenario_success', 'Scenario status updated.');
        redirect('scenarios.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM scenarios WHERE id = :id');
        $stmt->execute(['id' => $id]);

        logAdminActivity($pdo, 'deleted scenario', 'scenarios', ['id' => $id]);
        setFlash('scenario_success', 'Scenario deleted.');
        redirect('scenarios.php');
    }
}

$editScenario = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editStmt = $pdo->prepare('SELECT * FROM scenarios WHERE id = :id LIMIT 1');
    $editStmt->execute(['id' => $editId]);
    $editScenario = $editStmt->fetch();
}

$scenarios = $pdo->query('SELECT * FROM scenarios ORDER BY title ASC')->fetchAll();
$csrfToken = generateCsrfToken();
$scenarioSuccess = getFlash('scenario_success');
$scenarioError = getFlash('scenario_error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Game Management</title>
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
                <a class="active" href="scenarios.php">Game Management</a>
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
                    <p class="eyebrow">Game Content</p>
                    <h1>Disaster Scenarios</h1>
                </div>
                <div class="topbar-user">
                    <span>Logged in as <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                </div>
            </header>

            <?php if ($scenarioSuccess): ?>
                <div class="alert success"><?php echo htmlspecialchars($scenarioSuccess); ?></div>
            <?php endif; ?>
            <?php if ($scenarioError): ?>
                <div class="alert error"><?php echo htmlspecialchars($scenarioError); ?></div>
            <?php endif; ?>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo $editScenario ? 'Edit Scenario' : 'Create Scenario'; ?></h2>
                </div>
                <form method="post" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="<?php echo $editScenario ? 'update' : 'create'; ?>">
                    <?php if ($editScenario): ?>
                        <input type="hidden" name="id" value="<?php echo (int) $editScenario['id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="field-group">
                            <label for="slug">Slug</label>
                            <input type="text" id="slug" name="slug" value="<?php echo htmlspecialchars($editScenario['slug'] ?? ''); ?>" required>
                        </div>
                        <div class="field-group">
                            <label for="title">Title</label>
                            <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($editScenario['title'] ?? ''); ?>" required>
                        </div>
                        <div class="field-group">
                            <label for="difficulty">Difficulty</label>
                            <input type="text" id="difficulty" name="difficulty" value="<?php echo htmlspecialchars($editScenario['difficulty'] ?? 'Intermediate'); ?>">
                        </div>
                        <div class="field-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo (($editScenario['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (($editScenario['status'] ?? 'active') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" rows="3"><?php echo htmlspecialchars($editScenario['description'] ?? ''); ?></textarea>
                    </div>

                    <div class="field-group">
                        <label for="instructions">Instructions</label>
                        <textarea id="instructions" name="instructions" rows="3"><?php echo htmlspecialchars($editScenario['instructions'] ?? ''); ?></textarea>
                    </div>

                    <div class="field-group">
                        <label for="learning_objectives">Learning Objectives</label>
                        <textarea id="learning_objectives" name="learning_objectives" rows="3"><?php echo htmlspecialchars($editScenario['learning_objectives'] ?? ''); ?></textarea>
                    </div>

                    <div class="field-group">
                        <label for="safety_info">Safety Information</label>
                        <textarea id="safety_info" name="safety_info" rows="3"><?php echo htmlspecialchars($editScenario['safety_info'] ?? ''); ?></textarea>
                    </div>

                    <div class="field-group">
                        <label for="completion_info">Completion Information</label>
                        <textarea id="completion_info" name="completion_info" rows="3"><?php echo htmlspecialchars($editScenario['completion_info'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?php echo $editScenario ? 'Save Changes' : 'Create Scenario'; ?></button>
                        <?php if ($editScenario): ?>
                            <a href="scenarios.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2>Scenario List</h2>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Slug</th>
                                <th>Difficulty</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($scenarios)): ?>
                                <tr>
                                    <td colspan="5" class="table-empty">No scenarios found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($scenarios as $scenario): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($scenario['title']); ?></td>
                                        <td><?php echo htmlspecialchars($scenario['slug']); ?></td>
                                        <td><?php echo htmlspecialchars($scenario['difficulty']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo htmlspecialchars($scenario['status']); ?>"><?php echo htmlspecialchars($scenario['status']); ?></span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="scenarios.php?edit=<?php echo (int) $scenario['id']; ?>" class="btn btn-secondary small">Edit</a>
                                            <form method="post" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?php echo (int) $scenario['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo ($scenario['status'] === 'active') ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="btn btn-secondary small"><?php echo ($scenario['status'] === 'active') ? 'Disable' : 'Enable'; ?></button>
                                            </form>
                                            <form method="post" class="inline-form" onsubmit="return confirm('Delete this scenario?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int) $scenario['id']; ?>">
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
