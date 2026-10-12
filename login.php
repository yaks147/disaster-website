<?php
require __DIR__ . '/config.php';

if (isset($_SESSION['user'])) {
    if (($user = $_SESSION['user']) && (($user['role'] ?? 'user') === 'admin')) {
        redirect('admin/dashboard.php');
    }

    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginIdentifier = trim($_POST['login_identifier'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($loginIdentifier === '' || $password === '') {
        $error = 'Please enter your username/email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT id, username, email, password_hash, role, is_active FROM users WHERE username = :value OR email = :value LIMIT 1');
        $stmt->execute(['value' => $loginIdentifier]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash']) && (int) $user['is_active'] === 1) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role'] ?? 'user',
            ];

            $updateStmt = $pdo->prepare('UPDATE users SET last_login_at = CURRENT_TIMESTAMP WHERE id = :id');
            $updateStmt->execute(['id' => $user['id']]);

            if (($user['role'] ?? 'user') === 'admin') {
                redirect('admin/dashboard.php');
            }

            redirect('dashboard.php');
        } else {
            $error = 'Invalid username/email or password.';
        }
    }
}

$successMessage = getFlash('success_message');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-body">
    <header class="site-header">
        <nav class="navbar container">
            <a href="index.php" class="brand" aria-label="Disaster Ready home">
                <span class="brand-mark">⚡</span>
                <span class="brand-text">
                    <span class="brand-title">Disaster Ready</span>
                    <span class="brand-subtitle">Preparedness Simulation</span>
                </span>
            </a>
            <button class="nav-toggle" aria-label="Toggle navigation">☰</button>
            <div class="nav-links">
                <a href="index.php">Home</a>
                <a href="index.php#disaster-guides">Disaster Guides</a>
                <a href="leaderboard.php">Leaderboard</a>
                <a href="login.php" class="active">Login</a>
                <a href="register.php" class="nav-register">Register</a>
            </div>
        </nav>
    </header>

    <main class="auth-page">
        <div class="auth-shell reveal">
            <div class="auth-visual">
                <div class="auth-visual-inner">
                    <p class="auth-kicker">Preparedness program</p>
                    <h1>Continue your emergency training</h1>
                    <p>Review safe action plans, revisit response drills, and keep your disaster readiness on track.</p>
                    <ul class="auth-points">
                        <li>Earthquake response drills</li>
                        <li>Fire escape planning</li>
                        <li>Typhoon and volcano readiness</li>
                    </ul>
                </div>
            </div>

            <div class="auth-card glass-panel">
                <div class="auth-card-header">
                    <p class="eyebrow">Welcome back</p>
                    <h2>Login</h2>
                </div>

                <?php if ($successMessage): ?>
                    <div class="alert success"><?php echo htmlspecialchars($successMessage); ?></div>
                <?php endif; ?>

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
                        <span>Login</span>
                    </button>
                </form>

                <div class="auth-links">
                    <a href="register.php">Create account</a>
                </div>
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

    <script src="script.js"></script>
</body>
</html>
