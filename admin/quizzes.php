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
        $scenarioId = (int) ($_POST['scenario_id'] ?? 0);
        $question = trim((string) ($_POST['question'] ?? ''));
        $optionA = trim((string) ($_POST['option_a'] ?? ''));
        $optionB = trim((string) ($_POST['option_b'] ?? ''));
        $optionC = trim((string) ($_POST['option_c'] ?? ''));
        $optionD = trim((string) ($_POST['option_d'] ?? ''));
        $correctAnswer = strtoupper(trim((string) ($_POST['correct_answer'] ?? '')));
        $explanation = trim((string) ($_POST['explanation'] ?? ''));
        $status = trim((string) ($_POST['status'] ?? 'active'));

        if ($scenarioId <= 0 || $question === '' || $optionA === '' || $optionB === '' || $optionC === '' || $optionD === '' || !in_array($correctAnswer, ['A', 'B', 'C', 'D'], true)) {
            setFlash('quiz_error', 'Please fill in all required fields correctly.');
            redirect('quizzes.php');
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare(
                'INSERT INTO quiz_questions (scenario_id, question, option_a, option_b, option_c, option_d, correct_answer, explanation, status)
                 VALUES (:scenario_id, :question, :option_a, :option_b, :option_c, :option_d, :correct_answer, :explanation, :status)'
            );
            $stmt->execute([
                'scenario_id' => $scenarioId,
                'question' => $question,
                'option_a' => $optionA,
                'option_b' => $optionB,
                'option_c' => $optionC,
                'option_d' => $optionD,
                'correct_answer' => $correctAnswer,
                'explanation' => $explanation,
                'status' => $status,
            ]);

            logAdminActivity($pdo, 'created quiz question', 'quiz_questions', ['scenario_id' => $scenarioId]);
            setFlash('quiz_success', 'Question created successfully.');
        } else {
            $id = (int) ($_POST['id'] ?? 0);
            $stmt = $pdo->prepare(
                'UPDATE quiz_questions
                 SET scenario_id = :scenario_id,
                     question = :question,
                     option_a = :option_a,
                     option_b = :option_b,
                     option_c = :option_c,
                     option_d = :option_d,
                     correct_answer = :correct_answer,
                     explanation = :explanation,
                     status = :status,
                     updated_at = CURRENT_TIMESTAMP
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'scenario_id' => $scenarioId,
                'question' => $question,
                'option_a' => $optionA,
                'option_b' => $optionB,
                'option_c' => $optionC,
                'option_d' => $optionD,
                'correct_answer' => $correctAnswer,
                'explanation' => $explanation,
                'status' => $status,
            ]);

            logAdminActivity($pdo, 'updated quiz question', 'quiz_questions', ['id' => $id]);
            setFlash('quiz_success', 'Question updated successfully.');
        }

        redirect('quizzes.php');
    }

    if ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM quiz_questions WHERE id = :id');
        $stmt->execute(['id' => $id]);

        logAdminActivity($pdo, 'deleted quiz question', 'quiz_questions', ['id' => $id]);
        setFlash('quiz_success', 'Question deleted.');
        redirect('quizzes.php');
    }

    if ($action === 'toggle_status') {
        $id = (int) ($_POST['id'] ?? 0);
        $status = $_POST['status'] ?? 'inactive';
        $stmt = $pdo->prepare('UPDATE quiz_questions SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);

        logAdminActivity($pdo, 'updated quiz status', 'quiz_questions', ['id' => $id, 'status' => $status]);
        setFlash('quiz_success', 'Question status updated.');
        redirect('quizzes.php');
    }
}

$editQuestion = null;
if (isset($_GET['edit'])) {
    $editId = (int) $_GET['edit'];
    $editStmt = $pdo->prepare('SELECT * FROM quiz_questions WHERE id = :id LIMIT 1');
    $editStmt->execute(['id' => $editId]);
    $editQuestion = $editStmt->fetch();
}

$questions = $pdo->query(
    'SELECT q.*, s.title AS scenario_title
     FROM quiz_questions q
     JOIN scenarios s ON s.id = q.scenario_id
     ORDER BY s.title ASC, q.id ASC'
)->fetchAll();

$scenarios = $pdo->query('SELECT id, title FROM scenarios ORDER BY title ASC')->fetchAll();
$csrfToken = generateCsrfToken();
$quizSuccess = getFlash('quiz_success');
$quizError = getFlash('quiz_error');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Management</title>
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
                <a class="active" href="quizzes.php">Quizzes</a>
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
                    <p class="eyebrow">Assessment Content</p>
                    <h1>Quiz Questions</h1>
                </div>
                <div class="topbar-user">
                    <span>Logged in as <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                </div>
            </header>

            <?php if ($quizSuccess): ?>
                <div class="alert success"><?php echo htmlspecialchars($quizSuccess); ?></div>
            <?php endif; ?>
            <?php if ($quizError): ?>
                <div class="alert error"><?php echo htmlspecialchars($quizError); ?></div>
            <?php endif; ?>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2><?php echo $editQuestion ? 'Edit Question' : 'Create Question'; ?></h2>
                </div>
                <form method="post" class="admin-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="<?php echo $editQuestion ? 'update' : 'create'; ?>">
                    <?php if ($editQuestion): ?>
                        <input type="hidden" name="id" value="<?php echo (int) $editQuestion['id']; ?>">
                    <?php endif; ?>

                    <div class="form-grid">
                        <div class="field-group">
                            <label for="scenario_id">Scenario</label>
                            <select id="scenario_id" name="scenario_id" required>
                                <?php foreach ($scenarios as $scenario): ?>
                                    <option value="<?php echo (int) $scenario['id']; ?>" <?php echo (($editQuestion['scenario_id'] ?? '') == $scenario['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($scenario['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="field-group">
                            <label for="status">Status</label>
                            <select id="status" name="status">
                                <option value="active" <?php echo (($editQuestion['status'] ?? 'active') === 'active') ? 'selected' : ''; ?>>Active</option>
                                <option value="inactive" <?php echo (($editQuestion['status'] ?? 'active') === 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                            </select>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="question">Question</label>
                        <textarea id="question" name="question" rows="3" required><?php echo htmlspecialchars($editQuestion['question'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-grid">
                        <div class="field-group">
                            <label for="option_a">Option A</label>
                            <input type="text" id="option_a" name="option_a" value="<?php echo htmlspecialchars($editQuestion['option_a'] ?? ''); ?>" required>
                        </div>
                        <div class="field-group">
                            <label for="option_b">Option B</label>
                            <input type="text" id="option_b" name="option_b" value="<?php echo htmlspecialchars($editQuestion['option_b'] ?? ''); ?>" required>
                        </div>
                        <div class="field-group">
                            <label for="option_c">Option C</label>
                            <input type="text" id="option_c" name="option_c" value="<?php echo htmlspecialchars($editQuestion['option_c'] ?? ''); ?>" required>
                        </div>
                        <div class="field-group">
                            <label for="option_d">Option D</label>
                            <input type="text" id="option_d" name="option_d" value="<?php echo htmlspecialchars($editQuestion['option_d'] ?? ''); ?>" required>
                        </div>
                        <div class="field-group">
                            <label for="correct_answer">Correct Answer</label>
                            <select id="correct_answer" name="correct_answer" required>
                                <option value="A" <?php echo (($editQuestion['correct_answer'] ?? 'A') === 'A') ? 'selected' : ''; ?>>A</option>
                                <option value="B" <?php echo (($editQuestion['correct_answer'] ?? 'A') === 'B') ? 'selected' : ''; ?>>B</option>
                                <option value="C" <?php echo (($editQuestion['correct_answer'] ?? 'A') === 'C') ? 'selected' : ''; ?>>C</option>
                                <option value="D" <?php echo (($editQuestion['correct_answer'] ?? 'A') === 'D') ? 'selected' : ''; ?>>D</option>
                            </select>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="explanation">Explanation</label>
                        <textarea id="explanation" name="explanation" rows="3"><?php echo htmlspecialchars($editQuestion['explanation'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary"><?php echo $editQuestion ? 'Save Changes' : 'Create Question'; ?></button>
                        <?php if ($editQuestion): ?>
                            <a href="quizzes.php" class="btn btn-secondary">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
            </section>

            <section class="admin-panel">
                <div class="panel-header">
                    <h2>Question List</h2>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Scenario</th>
                                <th>Question</th>
                                <th>Correct</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($questions)): ?>
                                <tr>
                                    <td colspan="5" class="table-empty">No questions found.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($questions as $question): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($question['scenario_title']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($question['question'], 0, 80)); ?><?php echo strlen($question['question']) > 80 ? '...' : ''; ?></td>
                                        <td><?php echo htmlspecialchars($question['correct_answer']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo htmlspecialchars($question['status']); ?>"><?php echo htmlspecialchars($question['status']); ?></span>
                                        </td>
                                        <td class="table-actions">
                                            <a href="quizzes.php?edit=<?php echo (int) $question['id']; ?>" class="btn btn-secondary small">Edit</a>
                                            <form method="post" class="inline-form">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="id" value="<?php echo (int) $question['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo ($question['status'] === 'active') ? 'inactive' : 'active'; ?>">
                                                <button type="submit" class="btn btn-secondary small"><?php echo ($question['status'] === 'active') ? 'Disable' : 'Enable'; ?></button>
                                            </form>
                                            <form method="post" class="inline-form" onsubmit="return confirm('Delete this question?');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo (int) $question['id']; ?>">
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
