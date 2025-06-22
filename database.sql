CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);


CREATE TABLE IF NOT EXISTS comic_subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    is_subscribed BOOLEAN DEFAULT FALSE,
    otp VARCHAR(10),
    otp_expiry DATETIME,
    preferred_time TIME DEFAULT '08:00:00',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



CREATE TABLE IF NOT EXISTS email_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    job_id VARCHAR(64) UNIQUE NOT NULL,
    queue_name VARCHAR(50) DEFAULT 'postcomic',
    recipient_email VARCHAR(255) NOT NULL,
    comic_data JSON,
    status ENUM('latest','active','waiting','completed','failed','delayed','paused') DEFAULT 'waiting',
    priority INT DEFAULT 0,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    started_at TIMESTAMP NULL,
    completed_at TIMESTAMP NULL,
    failed_at TIMESTAMP NULL,
    next_retry_at TIMESTAMP NULL,
    error_message TEXT,
    process_time_ms INT DEFAULT 0,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



















CREATE TABLE IF NOT EXISTS queue_stats (
    id INT AUTO_INCREMENT PRIMARY KEY,
    queue_name VARCHAR(50) NOT NULL,
    total_jobs INT DEFAULT 0,
    completed_jobs INT DEFAULT 0,
    failed_jobs INT DEFAULT 0,
    active_jobs INT DEFAULT 0,
    waiting_jobs INT DEFAULT 0,
    delayed_jobs INT DEFAULT 0,
    paused_jobs INT DEFAULT 0,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_queue (queue_name)
);

CREATE TABLE IF NOT EXISTS cron_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cron_type VARCHAR(50) NOT NULL,
    status ENUM('started','completed','failed') DEFAULT 'started',
    jobs_created INT DEFAULT 0,
    jobs_processed INT DEFAULT 0,
    execution_time_ms INT DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);