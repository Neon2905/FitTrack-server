-- USERS TABLE
CREATE TABLE
    IF NOT EXISTS user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) UNIQUE NOT NULL,
        name VARCHAR(255),
        email VARCHAR(255) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        date_of_birth DATE,
        gender ENUM ('male', 'female', 'other'),
        weight FLOAT,
        profile_picture_url VARCHAR(255),
        is_verified BOOLEAN DEFAULT TRUE,
        two_fa_enabled BOOLEAN DEFAULT TRUE
    );

-- CHALLENGES
CREATE TABLE
    IF NOT EXISTS challenge (
        uuid INT,
        user_id INT,
        PRIMARY KEY (uuid, user_id),
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

-- ACTIVITY LOGS
CREATE TABLE
    IF NOT EXISTS activity (
        uuid INT,
        user_id INT,
        PRIMARY KEY (uuid, user_id),
        type ENUM ('walking', 'running', 'cycling', 'weightlifting', 'unknown') NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME DEFAULT NULL,
        duration BIGINT NOT NULL,
        distance DOUBLE DEFAULT 0.0,
        calories DOUBLE NOT NULL,
        steps INT DEFAULT 0,
        reps INT DEFAULT 0,
        challenge_id INT NULL,
        tracks JSON DEFAULT NULL,
        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
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
        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
    );

-- DEVICE SYNC SETTINGS
CREATE TABLE
    IF NOT EXISTS sync_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        wearable_sync_enabled BOOLEAN DEFAULT FALSE,
        google_fit_connected BOOLEAN DEFAULT FALSE,
        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
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
        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
    );

-- ANALYTICS CACHE (optional local/server store)
CREATE TABLE
    IF NOT EXISTS analytics_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        report_type ENUM ('daily', 'weekly', 'monthly'),
        generated_on DATE,
        data TEXT,
        FOREIGN KEY (user_id) REFERENCES user (id) ON DELETE CASCADE
    );