<?php
// task3/views/mod_dashboard.php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'moderator') {
    header('Location: ../../task1/views/login.php');
    exit;
}
$name = $_SESSION['name'] ?? 'Moderator';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderator Dashboard — ISP Media FTP</title>
    <link rel="stylesheet" href="../../task1/assets/style.css">
</head>
<body>

<nav class="navbar">
    <a class="brand" href="../../task1/views/home.php">ISP<span>Media</span></a>
    <ul class="nav-links">
        <li><a href="../../task1/views/home.php">Home</a></li>
        <li><a href="mod_dashboard.php" class="active">Dashboard</a></li>
        <li><a href="../../task1/views/profile.php">Profile</a></li>
        <li><a href="#" class="btn-nav" onclick="doLogout()">Logout</a></li>
    </ul>
</nav>

<div class="page-wrapper">
    <h1 class="page-title">Moderator Dashboard</h1>
    <p class="page-subtitle">Welcome, <?= htmlspecialchars($name) ?> · Manage media contents &amp; requests</p>

    <!-- ── Upload New Content ─────────────────────────────────────────────── -->
    <div class="container">
        <h2>⬆ Add New Content</h2>
        <div id="uploadFlash"></div>

        <div class="form-section">
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>Title *</label>
                    <input type="text" id="modCtTitle" placeholder="Content title">
                    <span class="error-msg" id="errModCtTitle">Title is required.</span>
                </div>
                <div class="form-group">
                    <label>Category *</label>
                    <select id="modCtCategory">
                        <option value="">— Select —</option>
                    </select>
                    <span class="error-msg" id="errModCtCat">Category is required.</span>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>Description</label>
                    <textarea id="modCtDesc" placeholder="Brief description"></textarea>
                </div>
                <div class="form-group">
                    <label>File <small>(.mp4 .mkv .pdf .zip .exe etc.)</small></label>
                    <input type="file" id="modCtFile">
                    <span class="error-msg" id="errModCtFile">File is required.</span>
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="uploadContentMod()">⬆ Upload Content</button>
        </div>
    </div>

    <!-- ── All Contents ───────────────────────────────────────────────────── -->
    <div class="container">
        <h2>🎬 All Contents</h2>

        <div class="search-bar" style="margin-bottom:16px;">
            <input type="text" id="modSearch" placeholder="Search by title or description…" oninput="searchContents()">
            <select id="modFilterCat" onchange="searchContents()">
                <option value="">All Categories</option>
            </select>
        </div>

        <table>
            <thead>
                <tr><th>Title</th><th>Category</th><th>Uploader</th><th>Downloads</th><th>Uploaded</th><th>Actions</th></tr>
            </thead>
            <tbody id="modContentBody">
                <tr><td colspan="6" style="text-align:center;">Loading…</td></tr>
            </tbody>
        </table>
    </div>

    <!-- ── Content Requests ──────────────────────────────────────────────── -->
    <div class="container">
        <h2>📬 Content Requests</h2>
        <p style="color:#666;font-size:13px;margin-bottom:12px;">Update the status of member requests below.</p>
        <div id="reqFlash"></div>
        <table>
            <thead>
                <tr><th>Title</th><th>Category</th><th>Message</th><th>Submitted</th><th>Status</th><th>Actions</th></tr>
            </thead>
            <tbody id="modRequestBody">
                <tr><td colspan="6" style="text-align:center;">Loading…</td></tr>
            </tbody>
        </table>
    </div>

</div>

<script src="../js/mod.js"></script>
</body>
</html>
