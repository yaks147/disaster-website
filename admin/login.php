<?php
require __DIR__ . '/../config.php';

if (isset($_SESSION['user'])) {
    $currentUser = $_SESSION['user'];
    if (($currentUser['role'] ?? 'user') === 'admin') {
        redirect('dashboard.php');
    }

    redirect('../dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginIdentifier = trim((string) ($_POST['login_identifier'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($loginIdentifier === '' || $password === '') {
        $error = 'Please enter your username/email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role, is_active FROM users WHERE username = :value OR email = :value LIMIT 1');
        $stmt->execute(['value' => $loginIdentifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash']) && (int) $user['is_active'] === 1) {
            if (($user['role'] ?? 'user') !== 'admin') {
                $error = 'This account does not have administrator access.';
            } else {
                $_SESSION['user'] = [
                    'id' => (int) $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'role' => $user['role'] ?? 'user',
                ];

                $updateStmt = $pdo->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
                $updateStmt->execute(['id' => (int) $user['id']]);

                redirect('dashboard.php');
            }
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body class="auth-body admin-auth-body">
    <header class="site-header">
        <nav class="navbar container">
            <a href="../index.php" class="brand" aria-label="Disaster Ready home">
                <span class="brand-mark">⚡</span>
                <span class="brand-text">
                    <span class="brand-title">Disaster Ready</span>
                    <span class="brand-subtitle">Preparedness Simulation</span>
                </span>
            </a>
            <div class="nav-links">
                <a href="../index.php">Home</a>
                <a href="../leaderboard.php">Leaderboard</a>
            </div>
        </nav>
    </header>

    <main class="auth-page">
        <div class="auth-shell reveal">
            <div class="auth-visual auth-visual-admin">
                <div class="auth-visual-inner">
                    <p class="auth-kicker">Authorized personnel only</p>
                    <h1>Admin access</h1>
                    <p>Manage scenarios, quiz content, player records, and system updates for the disaster preparedness platform.</p>
                    <ul class="auth-points">
                        <li>Monitor player progression</li>
                        <li>Review system activity</li>
                        <li>Control site content and announcements</li>
                    </ul>
                </div>
            </div>

            <div class="auth-card glass-panel admin-auth-card">
                <div class="auth-card-header">
                    <p class="eyebrow">Protected access</p>
                    <h2>Admin login</h2>
                </div>

                <?php if ($error): ?>
                    <div class="alert error"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="post" class="auth-form" data-submit-state>
                    <div class="field-group">
                        <label for="login_identifier">Username or Email</label>
                        <input type="text" id="login_identifier" name="login_identifier" autocomplete="username" required>
                    </div>

                    <div class="field-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" autocomplete="current-password" required>
                            <button type="button" class="password-toggle" aria-label="Show password">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary full-width" data-loading-text="Signing in...">
                        <span>Sign in</span>
                    </button>
                </form>
            </div>
        </div>
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

    <script src="../script.js"></script>
</body>
</html>
