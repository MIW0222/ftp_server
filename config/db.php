<?php
// config/db.php — Shared DB connection (used by all tasks)
session_start();
function getDB() {
    $host   = 'localhost';
    $user   = 'root';
    $pass   = '';
    $dbname = 'ftp_isp_db';

    $conn = mysqli_connect($host, $user, $pass);
    if (!$conn) {
        die(json_encode(['success' => false, 'error' => 'DB connection failed: ' . mysqli_connect_error()]));
    }

    // Create database if not exists
    if (!mysqli_query($conn, "CREATE DATABASE IF NOT EXISTS `$dbname`")) {
        die(json_encode(['success' => false, 'error' => 'Cannot create DB: ' . mysqli_error($conn)]));
    }
    mysqli_select_db($conn, $dbname);

    // ── users ──────────────────────────────────────────────────────────────────
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS users (
        id              INT AUTO_INCREMENT PRIMARY KEY,
        name            VARCHAR(100)  NOT NULL,
        email           VARCHAR(150)  NOT NULL UNIQUE,
        password_hash   VARCHAR(255)  NOT NULL,
        role            ENUM('admin','moderator') NOT NULL,
        status          ENUM('pending','approved') NOT NULL DEFAULT 'approved',
        profile_picture VARCHAR(255)  DEFAULT NULL,
        remember_token  VARCHAR(64)   DEFAULT NULL,
        created_at      DATETIME      DEFAULT CURRENT_TIMESTAMP
    )");
    // Add status column if upgrading from older schema
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN IF NOT EXISTS status ENUM('pending','approved') NOT NULL DEFAULT 'approved'");

    // ── categories ────────────────────────────────────────────────────────────
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS categories (
        id         INT AUTO_INCREMENT PRIMARY KEY,
        name       VARCHAR(100) NOT NULL,
        parent_id  INT          DEFAULT NULL,
        created_at DATETIME     DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
    )");

    // ── contents ──────────────────────────────────────────────────────────────
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS contents (
        id             INT AUTO_INCREMENT PRIMARY KEY,
        title          VARCHAR(255) NOT NULL,
        description    TEXT,
        file_path      VARCHAR(255) NOT NULL,
        category_id    INT          DEFAULT NULL,
        uploader_id    INT          DEFAULT NULL,
        download_count INT          DEFAULT 0,
        uploaded_at    DATETIME     DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL,
        FOREIGN KEY (uploader_id) REFERENCES users(id) ON DELETE SET NULL
    )");

    // pending_moderators table removed — status column on users is used instead

    // ── content_requests ──────────────────────────────────────────────────────
    mysqli_query($conn, "CREATE TABLE IF NOT EXISTS content_requests (
        id                 INT AUTO_INCREMENT PRIMARY KEY,
        requester_ip       VARCHAR(45)  DEFAULT NULL,
        content_title      VARCHAR(255) NOT NULL,
        category_requested VARCHAR(100) DEFAULT NULL,
        message            TEXT,
        status             ENUM('pending','fulfilled','rejected') DEFAULT 'pending',
        created_at         DATETIME DEFAULT CURRENT_TIMESTAMP
    )");

    // Seed default categories if empty
    $check = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM categories");
    $row   = $check ? mysqli_fetch_assoc($check) : ['cnt' => 1];
    if ((int)$row['cnt'] === 0) {
        $seeds = [
            ['Movies',    null],
            ['Software',  null],
            ['TV Series', null],
            ['Games',     null],
            ['Music',     null],
            ['Books',     null],
        ];
        $stmt = mysqli_prepare($conn, "INSERT INTO categories (name, parent_id) VALUES (?, ?)");
        foreach ($seeds as [$name, $pid]) {
            mysqli_stmt_bind_param($stmt, 'si', $name, $pid);
            mysqli_stmt_execute($stmt);
        }
        // Sub-categories for Movies
        $moviesId = mysqli_insert_id($conn) - 4; // rough; fetch properly
        $res = mysqli_query($conn, "SELECT id FROM categories WHERE name='Movies' LIMIT 1");
        $moviesRow = mysqli_fetch_assoc($res);
        $mid = $moviesRow['id'];
        $subs = [['Action',$mid],['Comedy',$mid],['Horror',$mid],['Drama',$mid]];
        foreach ($subs as [$name, $pid]) {
            mysqli_stmt_bind_param($stmt, 'si', $name, $pid);
            mysqli_stmt_execute($stmt);
        }
        $res2 = mysqli_query($conn, "SELECT id FROM categories WHERE name='Games' LIMIT 1");
        $gRow = mysqli_fetch_assoc($res2);
        $gid  = $gRow['id'];
        $gsubs = [['PC Games',$gid],['Mobile Games',$gid],['Console Games',$gid]];
        foreach ($gsubs as [$name, $pid]) {
            mysqli_stmt_bind_param($stmt, 'si', $name, $pid);
            mysqli_stmt_execute($stmt);
        }
        mysqli_stmt_close($stmt);
    }

    return $conn;
}
?>
