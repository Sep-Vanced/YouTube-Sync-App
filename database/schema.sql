CREATE DATABASE IF NOT EXISTS youtube_sync_app
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE youtube_sync_app;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    google_id VARCHAR(100) NOT NULL UNIQUE,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(190) NOT NULL,
    profile_picture VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS channels (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    channel_id VARCHAR(50) NOT NULL UNIQUE,
    title VARCHAR(255) NOT NULL,
    description TEXT DEFAULT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_channels_title (title)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS videos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    video_id VARCHAR(50) NOT NULL UNIQUE,
    channel_id VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    thumbnail VARCHAR(255) DEFAULT NULL,
    published_at DATETIME NOT NULL,
    INDEX idx_videos_channel_id (channel_id),
    INDEX idx_videos_published_at (published_at),
    CONSTRAINT fk_videos_channel_id
        FOREIGN KEY (channel_id) REFERENCES channels(channel_id)
        ON DELETE CASCADE
        ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
