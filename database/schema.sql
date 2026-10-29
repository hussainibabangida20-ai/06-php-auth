CREATE DATABASE IF NOT EXISTS swd_auth
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE swd_auth;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(160) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at DATETIME NULL DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY users_email_unique (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email VARCHAR(160) NOT NULL,
    ip VARCHAR(45) NOT NULL,
    attempted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY login_attempts_email_time (email, attempted_at)
) ENGINE=InnoDB;
