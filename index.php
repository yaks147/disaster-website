<?php require __DIR__ . '/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disaster Preparedness Simulation</title>
    <meta name="description" content="Disaster Preparedness Simulation helps users learn how to respond before, during, and after major emergencies.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&family=Orbitron:wght@500;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body class="home-page">
    <header class="site-header">
        <nav class="navbar container" aria-label="Main navigation">
            <a href="index.php" class="brand" aria-label="Disaster Ready home">
                <span class="brand-mark">⚡</span>
                <span class="brand-text">
                    <span class="brand-title">Disaster Ready</span>
                    <span class="brand-subtitle">Preparedness Simulation</span>
                </span>
            </a>
            <button class="nav-toggle" aria-label="Toggle navigation">☰</button>
            <div class="nav-links">
                <a href="index.php" class="active">Home</a>
                <a href="#disaster-guides">Disasters</a>
                <a href="#safety-guide">Safety Guide</a>
                <a href="leaderboard.php">Leaderboard</a>
                <?php if (isset($_SESSION['user'])): ?>
                    <a href="dashboard.php">Play Game</a>
                    <div class="nav-user">
                        <span class="nav-user-name">Hi, <?php echo htmlspecialchars($_SESSION['user']['username']); ?></span>
                        <a href="logout.php" class="nav-logout">Logout</a>
                    </div>
                <?php else: ?>
                    <a href="login.php">Login</a>
                    <a href="register.php" class="nav-register">Register</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>

    <main>
        <section class="hero" aria-label="Disaster preparedness hero section">
            <div class="hero-bg" aria-hidden="true">
                <div class="hero-layer hero-base"></div>
                <div class="hero-layer hero-reveal"></div>
                <div class="hero-vignette"></div>
            </div>

            <div class="container hero-inner">
                <div class="hero-copy reveal-item">
                    <p class="eyebrow">School Safety Program</p>
                    <h1 class="hero-title" aria-label="Disaster preparedness simulation">
                        <span class="title-word" style="--index: 0;">DISASTER</span>
                        <span class="title-word" style="--index: 1;">PREPAREDNESS</span>
                        <span class="title-word" style="--index: 2;">SIMULATION</span>
                    </h1>
                    <p class="hero-subtitle">Learn how to respond before, during, and after disasters through interactive survival scenarios.</p>

                    <div class="hero-actions">
                        <?php if (isset($_SESSION['user'])): ?>
                            <a href="dashboard.php" class="btn btn-primary">START TRAINING</a>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary">START TRAINING</a>
                        <?php endif; ?>
                        <a href="#disaster-guides" class="btn btn-secondary">VIEW SCENARIOS</a>
                    </div>

                    <div class="quick-icons" aria-label="Quick disaster preparation links">
                        <a href="#disaster-guides" class="icon-button" aria-label="Training exercises">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.75a7.25 7.25 0 0 1 7.25 7.25c0 4.83-3.48 9.09-8 11.36-4.52-2.27-8-6.53-8-11.36A7.25 7.25 0 0 1 12 2.75Zm0 3.25a3.5 3.5 0 1 0 0 7 3.5 3.5 0 0 0 0-7Zm-5.7 9.03a7.95 7.95 0 0 0 11.4 0l-1.76-1.46A5.46 5.46 0 0 1 12 15.5a5.46 5.46 0 0 1-3.94-1.68L7.3 15.03Z"/></svg>
                            <span>Training</span>
                        </a>
                        <a href="#safety-guide" class="icon-button" aria-label="Safety guide">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5 5 5v6.2c0 4.22 2.64 8.15 7 10.3 4.36-2.15 7-6.08 7-10.3V5l-7-2.5Zm0 3.22 3.5 1.26v3.97c0 2.76-1.7 5.32-3.5 6.63-1.8-1.31-3.5-3.87-3.5-6.63V7l3.5-1.28Zm-1.2 2.78h2.4v2.9h2.9v2.4h-2.9v2.9h-2.4v-2.9H8.1v-2.4h2.7v-2.9Z"/></svg>
                            <span>Safety Guide</span>
                        </a>
                        <a href="#safety-guide" class="icon-button" aria-label="Emergency information">
                            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11.25 3.5a8.75 8.75 0 1 1 0 17.5 8.75 8.75 0 0 1 0-17.5Zm0 4.25a1.2 1.2 0 0 0-1.2 1.2v3.65a1.2 1.2 0 0 0 2.4 0V9.0a1.2 1.2 0 0 0-1.2-1.25Zm0 8.4a1.32 1.32 0 1 0 0-2.64 1.32 1.32 0 0 0 0 2.64Z"/></svg>
                            <span>Emergency Info</span>
                        </a>
                    </div>
                </div>

                <div class="hero-side">
                    <div class="page-indicator reveal-item">
                        <span class="page-number">01 / 04</span>
                        <span class="page-label">Disaster Training</span>
                    </div>

                    <div class="featured-card glass-card reveal-item">
                        <p class="card-kicker">Featured Scenario</p>
                        <h2>EARTHQUAKE</h2>
                        <p class="scenario-tag">Drop • Cover • Hold</p>
                        <p>Learn the correct actions to take during strong earthquake shaking.</p>
                        <a href="dashboard.php" class="inline-link">START TRAINING</a>
                    </div>

                    <div class="module-card glass-card reveal-item" id="safety-guide">
                        <p class="card-kicker">Training Modules</p>
                        <div class="spec-list" aria-label="Training module list">
                            <div class="spec-row"><span>Earthquake</span><strong>Drop • Cover • Hold</strong></div>
                            <div class="spec-row"><span>Fire</span><strong>Evacuate Safely</strong></div>
                            <div class="spec-row"><span>Typhoon</span><strong>Prepare • Shelter</strong></div>
                            <div class="spec-row"><span>Volcano</span><strong>Evacuate • Stay Alert</strong></div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <section class="section container scenario-section" id="disaster-guides">
            <div class="section-heading reveal-item">
                <p class="eyebrow">Emergency Topics</p>
                <h2>Simulation Scenarios</h2>
            </div>

            <div class="cards">
                <article class="card reveal-item">
                    <div class="card-icon" aria-hidden="true">🌎</div>
                    <span class="card-tag">Earthquake</span>
                    <h3>Earthquake</h3>
                    <p>Drop, cover, and hold on. Learn safe actions and evacuation steps.</p>
                </article>

                <article class="card reveal-item">
                    <div class="card-icon" aria-hidden="true">🔥</div>
                    <span class="card-tag">Fire</span>
                    <h3>Fire</h3>
                    <p>Understand fire prevention, alert systems, and safe escape routes.</p>
                </article>

                <article class="card reveal-item">
                    <div class="card-icon" aria-hidden="true">🌧️</div>
                    <span class="card-tag">Typhoon</span>
                    <h3>Typhoon</h3>
                    <p>Plan for strong winds, flooding, and safe sheltering procedures.</p>
                </article>

                <article class="card reveal-item">
                    <div class="card-icon" aria-hidden="true">⛰️</div>
                    <span class="card-tag">Volcano</span>
                    <h3>Volcano</h3>
                    <p>Learn evacuation safety, ash precautions, and emergency response.</p>
                </article>
            </div>
        </section>
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
