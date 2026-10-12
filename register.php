<?php
require __DIR__ . '/config.php';

if (isset($_SESSION['user'])) {
    redirect('dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($username === '' || $email === '' || $password === '' || $confirmPassword === '') {
        $errors[] = 'Please fill in all fields.';
    }

    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long.';
    }

    if ($password !== $confirmPassword) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $checkUsername = $pdo->prepare('SELECT id FROM users WHERE username = :username LIMIT 1');
        $checkUsername->execute(['username' => $username]);

        if ($checkUsername->fetch()) {
            $errors[] = 'Username is already taken.';
        }

        $checkEmail = $pdo->prepare('SELECT id FROM users WHERE email = :email LIMIT 1');
        $checkEmail->execute(['email' => $email]);

        if ($checkEmail->fetch()) {
            $errors[] = 'Email is already registered.';
        }
    }

    if (empty($errors)) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare('INSERT INTO users (username, email, password_hash) VALUES (:username, :email, :password_hash)');
        $stmt->execute([
            'username' => $username,
            'email' => $email,
            'password_hash' => $passwordHash,
        ]);

        setFlash('success_message', 'Registration successful. Please log in.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
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
                <a href="login.php">Login</a>
                <a href="register.php" class="active nav-register">Register</a>
            </div>
        </nav>
    </header>

    <main class="auth-page">
        <div class="auth-shell reveal">
            <div class="auth-visual">
                <div class="auth-visual-inner">
                    <p class="auth-kicker">Join the program</p>
                    <h1>Build your emergency readiness</h1>
                    <p>Create an account to track your training, score scenarios, and progress through critical response modules.</p>
                    <ul class="auth-points">
                        <li>Track disaster response progress</li>
                        <li>Complete interactive survival drills</li>
                        <li>Compare performance on the leaderboard</li>
                    </ul>
                </div>
            </div>

            <div class="auth-card glass-panel">
                <div class="auth-card-header">
                    <p class="eyebrow">Create account</p>
                    <h2>Register</h2>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert error">
                        <ul>
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <form method="post" class="auth-form" data-submit-state>
                    <div class="field-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" autocomplete="username" required>
                    </div>

                    <div class="field-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" autocomplete="email" required>
                    </div>

                    <div class="field-group">
                        <label for="password">Password</label>
                        <div class="password-field">
                            <input type="password" id="password" name="password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" aria-label="Show password">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <div class="field-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="password-field">
                            <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password" required>
                            <button type="button" class="password-toggle" aria-label="Show password">
                                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 12s3.5-6 10-6 10 6 10 6-3.5 6-10 6S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary full-width" data-loading-text="Creating account...">
                        <span>Register</span>
                    </button>
                </form>

                <div class="auth-links">
                    <span>Already have an account?</span>
                    <a href="login.php">Login here</a>
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
