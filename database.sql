CREATE DATABASE IF NOT EXISTS disaster_game;

USE disaster_game;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS leaderboard (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    username VARCHAR(50) NOT NULL,
    category VARCHAR(30) NOT NULL,
    score INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS scenarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(120) NOT NULL,
    description TEXT,
    instructions TEXT,
    learning_objectives TEXT,
    difficulty VARCHAR(40) NOT NULL DEFAULT 'Intermediate',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    safety_info TEXT,
    completion_info TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS quiz_questions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scenario_id INT NOT NULL,
    question TEXT NOT NULL,
    option_a TEXT NOT NULL,
    option_b TEXT NOT NULL,
    option_c TEXT NOT NULL,
    option_d TEXT NOT NULL,
    correct_answer VARCHAR(10) NOT NULL,
    explanation TEXT,
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (scenario_id) REFERENCES scenarios(id) ON DELETE CASCADE,
    KEY idx_questions_scenario (scenario_id)
);

CREATE TABLE IF NOT EXISTS announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    message TEXT NOT NULL,
    publish_date TIMESTAMP NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'draft',
    created_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    KEY idx_announcements_status (status),
    KEY idx_announcements_publish_date (publish_date)
);

CREATE TABLE IF NOT EXISTS game_versions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    version_number VARCHAR(50) NOT NULL,
    platform VARCHAR(50) NOT NULL DEFAULT 'Windows',
    status VARCHAR(20) NOT NULL DEFAULT 'active',
    release_notes TEXT,
    release_date TIMESTAMP NULL,
    download_url VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS player_progress (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    scenario_slug VARCHAR(50) NOT NULL,
    status VARCHAR(20) NOT NULL DEFAULT 'not_started',
    score INT NOT NULL DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_progress (user_id, scenario_slug),
    KEY idx_progress_user (user_id),
    KEY idx_progress_scenario (scenario_slug)
);

CREATE TABLE IF NOT EXISTS admin_activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admin_id INT NOT NULL,
    action VARCHAR(120) NOT NULL,
    resource VARCHAR(120) NOT NULL,
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_logs_admin (admin_id),
    KEY idx_logs_created_at (created_at)
);

INSERT IGNORE INTO scenarios (slug, title, description, instructions, learning_objectives, difficulty, status, safety_info, completion_info)
VALUES
    ('earthquake', 'Earthquake', 'Practice safe response steps during sudden shaking, including drop, cover, and hold on.', 'Stay calm, drop under cover, protect your head, and wait for the shaking to stop before moving.', 'Understand safe actions during earthquake shaking and how to avoid common hazards.', 'Intermediate', 'active', 'Avoid doorways, windows, and elevators; move away from falling objects.', 'Scenario complete once the player chooses safe actions and confirms evacuation readiness.'),
    ('fire', 'Fire', 'Learn how to respond quickly to smoke, alarms, and fire emergencies.', 'Alert others, use the nearest exit, and stay low if there is smoke.', 'Recognize alarm signals, identify escape routes, and apply safe evacuation habits.', 'Intermediate', 'active', 'Do not use elevators; feel doors before opening them and use a safe exit route.', 'Scenario complete once the player identifies the safest exit path and avoids unsafe actions.'),
    ('typhoon', 'Typhoon', 'Prepare for strong winds, flooding, and sheltering during a storm emergency.', 'Move to a safe sheltered area, secure loose items, and follow official weather advisories.', 'Understand how to prepare for severe weather and respond to flood and wind threats.', 'Intermediate', 'active', 'Avoid flooded roads, downed power lines, and unsecured outdoor areas.', 'Scenario complete once the player chooses the safest shelter strategy and avoids hazardous areas.'),
    ('volcano', 'Volcano', 'Review ash fall safety, evacuation coordination, and emergency communication when volcanic activity rises.', 'Monitor official alerts, evacuate when directed, and protect your airway if ash is present.', 'Recognize volcano warning signs, plan evacuation steps, and protect health during ash exposure.', 'Advanced', 'active', 'Use masks or cloths to reduce ash inhalation and stay out of low-lying areas.', 'Scenario complete once the player chooses protective actions and follows official guidance.');

INSERT IGNORE INTO game_versions (version_number, platform, status, release_notes, release_date)
VALUES ('1.0.0', 'Windows', 'active', 'Initial game version tracked through the admin dashboard.', CURRENT_TIMESTAMP);
