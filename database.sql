
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);




CREATE TABLE IF NOT EXISTS comic_letter (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    is_verified BOOLEAN DEFAULT FALSE,
    is_subscribed BOOLEAN DEFAULT FALSE,
    otp VARCHAR(10),
    otp_expiry TIMESTAMP,
    preferred_time TIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_sent_at DATETIME,
    send_attempts INT DEFAULT 0,
    status ENUM('pending','active','unsubscribed','bounced') DEFAULT 'pending',
    subscription_token VARCHAR(64) UNIQUE,
    unsubscribe_token VARCHAR(64) UNIQUE,
    timezone VARCHAR(50) DEFAULT 'UTC',
    source VARCHAR(50) DEFAULT 'website',
    user_agent TEXT,
    ip_address VARCHAR(45)
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
