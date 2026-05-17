-- =============================================================================
-- ISP Media FTP Server — Database Schema
-- ftp_isp_db
-- Run this manually OR let db.php auto-create on first request.
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `ftp_isp_db`
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE `ftp_isp_db`;

-- ── users ─────────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
    `id`              INT          NOT NULL AUTO_INCREMENT,
    `name`            VARCHAR(100) NOT NULL,
    `email`           VARCHAR(150) NOT NULL,
    `password_hash`   VARCHAR(255) NOT NULL,
    `role`            ENUM('admin','moderator') NOT NULL,
    `status`          ENUM('pending','approved') NOT NULL DEFAULT 'approved',
    `profile_picture` VARCHAR(255) DEFAULT NULL,
    `remember_token`  VARCHAR(64)  DEFAULT NULL,
    `created_at`      DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── categories ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `categories` (
    `id`         INT          NOT NULL AUTO_INCREMENT,
    `name`       VARCHAR(100) NOT NULL,
    `parent_id`  INT          DEFAULT NULL,
    `created_at` DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_cat_parent`
        FOREIGN KEY (`parent_id`) REFERENCES `categories` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── contents ──────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `contents` (
    `id`             INT          NOT NULL AUTO_INCREMENT,
    `title`          VARCHAR(255) NOT NULL,
    `description`    TEXT         DEFAULT NULL,
    `file_path`      VARCHAR(255) NOT NULL,
    `category_id`    INT          DEFAULT NULL,
    `uploader_id`    INT          DEFAULT NULL,
    `download_count` INT          DEFAULT 0,
    `uploaded_at`    DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `fk_content_category`
        FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`)
        ON DELETE SET NULL,
    CONSTRAINT `fk_content_uploader`
        FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`)
        ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- pending_moderators table removed — status column on users table is used instead

-- ── content_requests ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `content_requests` (
    `id`                 INT          NOT NULL AUTO_INCREMENT,
    `requester_ip`       VARCHAR(45)  DEFAULT NULL,
    `content_title`      VARCHAR(255) NOT NULL,
    `category_requested` VARCHAR(100) DEFAULT NULL,
    `message`            TEXT         DEFAULT NULL,
    `status`             ENUM('pending','fulfilled','rejected') DEFAULT 'pending',
    `created_at`         DATETIME     DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── Seed: Top-level categories ────────────────────────────────────────────────
INSERT IGNORE INTO `categories` (`id`, `name`, `parent_id`) VALUES
(1, 'Movies',    NULL),
(2, 'Software',  NULL),
(3, 'TV Series', NULL),
(4, 'Games',     NULL),
(5, 'Music',     NULL),
(6, 'Books',     NULL);

-- ── Seed: Sub-categories ─────────────────────────────────────────────────────
INSERT IGNORE INTO `categories` (`id`, `name`, `parent_id`) VALUES
-- Movies sub-categories
(7,  'Action',        1),
(8,  'Comedy',        1),
(9,  'Horror',        1),
(10, 'Drama',         1),
(11, 'Sci-Fi',        1),
-- Software sub-categories
(12, 'Operating Systems', 2),
(13, 'Productivity',      2),
(14, 'Security Tools',    2),
-- TV Series sub-categories
(15, 'Drama Series',      3),
(16, 'Comedy Series',     3),
(17, 'Anime',             3),
(18, 'Documentary',       3),
(19, 'Reality TV',        3),
(20, 'Sci-Fi Series',     3),
(21, 'Crime & Thriller',  3),
-- Games sub-categories
(22, 'PC Games',       4),
(23, 'Mobile Games',   4),
(24, 'Console Games',  4),
-- Music sub-categories
(25, 'Pop',            5),
(26, 'Rock',           5),
(27, 'Hip-Hop',        5),
-- Books sub-categories
(28, 'Fiction',        6),
(29, 'Non-Fiction',    6),
(30, 'Science Fiction',6),
(31, 'Mystery & Thriller', 6),
(32, 'Biography',      6),
(33, 'Fantasy',        6),
(34, 'Self-Help',      6),
(35, 'History',        6);
