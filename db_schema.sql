-- USERS TABLE
CREATE TABLE
    IF NOT EXISTS user (
        user_id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        is_verified BOOLEAN DEFAULT FALSE,
        two_fa_enabled BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

-- ACTIVITY LOGS
CREATE TABLE
    IF NOT EXISTS activity (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        type ENUM ('walking', 'running', 'cycling', 'weightlifting'),
        start_time DATETIME,
        end_time DATETIME,
        duration DECIMAL(5, 2),
        distance DECIMAL(6, 2),
        calories_burned DECIMAL(6, 2),
        steps INT DEFAULT 0,
        reps INT DEFAULT 0,
        challenge_id INT,
        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE,
        FOREIGN KEY (challenge_id) REFERENCES challenge (id) ON DELETE SET NULL
    );

-- SLEEP SESSIONS
CREATE TABLE
    IF NOT EXISTS sleep_sessions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        start_time DATETIME,
        end_time DATETIME,
        quality VARCHAR(100),
        synced_with_google_fit BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );

-- CHALLENGES
CREATE TABLE
    IF NOT EXISTS challenge (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        title VARCHAR(255) NOT NULL,
        goal_type ENUM ('steps', 'distance', 'calories', 'duration') NOT NULL,
        target_value DECIMAL(10, 2) NOT NULL,
        due_date DATE NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        progress_value DECIMAL(10, 2) DEFAULT 0,
        is_completed BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
    );

-- DEVICE SYNC SETTINGS
CREATE TABLE
    IF NOT EXISTS sync_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        wearable_sync_enabled BOOLEAN DEFAULT FALSE,
        google_fit_connected BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );

-- DAILY SUMMARY
CREATE TABLE
    IF NOT EXISTS daily_summary (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        date DATE NOT NULL,
        total_steps INT DEFAULT 0,
        total_distance DECIMAL(6, 2) DEFAULT 0.00,
        total_calories_burned DECIMAL(6, 2) DEFAULT 0.00,
        total_duration DECIMAL(5, 2) DEFAULT 0.00,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );

-- ANALYTICS CACHE (optional local/server store)
CREATE TABLE
    IF NOT EXISTS analytics_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        report_type ENUM ('daily', 'weekly', 'monthly'),
        generated_on DATE,
        data TEXT,
        FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
    );