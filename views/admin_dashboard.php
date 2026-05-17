<?php
// task2/views/admin_dashboard.php
session_start();
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../../task1/views/login.php');
    exit;
}
$name = $_SESSION['name'] ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard — ISP Media FTP</title>
    <link rel="stylesheet" href="../../task1/assets/style.css">
</head>
<body>

<nav class="navbar">
    <a class="brand" href="../../task1/views/home.php">ISP<span>Media</span></a>
    <ul class="nav-links">
        <li><a href="../../task1/views/home.php">Home</a></li>
        <li><a href="admin_dashboard.php" class="active">Dashboard</a></li>
        <li><a href="../../task1/views/profile.php">Profile</a></li>
        <li><a href="#" class="btn-nav" onclick="doLogout()">Logout</a></li>
    </ul>
</nav>

<div class="page-wrapper">
    <h1 class="page-title">Admin Dashboard</h1>
    <p class="page-subtitle">Welcome back, <?= htmlspecialchars($name) ?> · Full system control</p>

    <!-- Stats -->
    <div class="stats-grid" id="statsGrid">
        <div class="stat-card"><div class="stat-number">…</div><div class="stat-label">Total Contents</div></div>
        <div class="stat-card"><div class="stat-number">…</div><div class="stat-label">Categories</div></div>
        <div class="stat-card"><div class="stat-number">…</div><div class="stat-label">Moderators</div></div>
        <div class="stat-card"><div class="stat-number">…</div><div class="stat-label">Pending Requests</div></div>
    </div>

    <!-- ── Moderator Management ──────────────────────────────────────────── -->
    <div class="container">
        <h2>👥 Moderator Registrations</h2>
        <div id="modFlash"></div>

        <h3>⏳ Pending Approval</h3>
        <p style="color:#666;font-size:13px;margin-bottom:12px;">
            These users registered as moderators and are awaiting your approval.
        </p>
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Registered</th><th>Actions</th></tr>
            </thead>
            <tbody id="pendingTableBody">
                <tr><td colspan="4" style="text-align:center;">Loading…</td></tr>
            </tbody>
        </table>

        <h3 style="margin-top:28px;">✅ Approved Moderators</h3>
        <table>
            <thead>
                <tr><th>Name</th><th>Email</th><th>Joined</th><th>Actions</th></tr>
            </thead>
            <tbody id="modTableBody">
                <tr><td colspan="4" style="text-align:center;">Loading…</td></tr>
            </tbody>
        </table>

        <h3 style="margin-top:28px;">➕ Add New Moderator</h3>
        <p style="color:#666;font-size:13px;margin-bottom:12px;">
            Create a moderator account directly. The account will be approved immediately.
        </p>
        <div class="form-section">
            <div class="form-row">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" id="newModName" placeholder="e.g. Jane Doe">
                    <span class="error-msg" id="errNewModName">Name is required.</span>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="newModEmail" placeholder="e.g. jane@example.com">
                    <span class="error-msg" id="errNewModEmail">A valid email is required.</span>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" id="newModPassword" placeholder="Min. 8 characters">
                    <span class="error-msg" id="errNewModPassword">Password is required.</span>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" id="newModConfirm" placeholder="Repeat password">
                    <span class="error-msg" id="errNewModConfirm">Passwords do not match.</span>
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="addModerator()">➕ Create Moderator</button>
        </div>
    </div>

    <!-- ── Content Management ────────────────────────────────────────────── -->
    <div class="container">
        <h2>🎬 Manage Contents</h2>
        <div id="contentFlash"></div>

        <div class="form-section">
            <h3>Upload New Content</h3>
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>Title</label>
                    <input type="text" id="ctTitle" placeholder="Content title">
                    <span class="error-msg" id="errCtTitle">Title is required.</span>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select id="ctCategory">
                        <option value="">— Select —</option>
                    </select>
                    <span class="error-msg" id="errCtCat">Category is required.</span>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>Description</label>
                    <textarea id="ctDescription" placeholder="Brief description of this content"></textarea>
                </div>
                <div class="form-group">
                    <label>File <small>(.mp4 .mkv .pdf .zip .exe etc.)</small></label>
                    <input type="file" id="ctFile">
                    <span class="error-msg" id="errCtFile">File is required.</span>
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="uploadContent()">⬆ Upload Content</button>
        </div>

        <h3>All Uploaded Contents</h3>
        <table>
            <thead>
                <tr><th>Title</th><th>Category</th><th>Uploader</th><th>Downloads</th><th>Uploaded</th><th>Actions</th></tr>
            </thead>
            <tbody id="contentTableBody">
                <tr><td colspan="6" style="text-align:center;">Loading…</td></tr>
            </tbody>
        </table>
    </div>

    <!-- Edit Content Panel -->
    <div id="editContentPanel" class="update-form">
        <h3>✏️ Edit Content</h3>
        <input type="hidden" id="editCtId">
        <div class="form-row">
            <div class="form-group" style="flex:2;">
                <label>Title</label>
                <input type="text" id="editCtTitle">
            </div>
            <div class="form-group">
                <label>Category</label>
                <select id="editCtCategory"></select>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="flex:2;">
                <label>Description</label>
                <textarea id="editCtDescription"></textarea>
            </div>
            <div class="form-group">
                <label>Replace File <small>(optional)</small></label>
                <input type="file" id="editCtFile">
            </div>
        </div>
        <button type="button" class="btn btn-warning" onclick="saveEditContent()">💾 Save Changes</button>
        <button type="button" class="btn cancel-btn" onclick="document.getElementById('editContentPanel').style.display='none'">Cancel</button>
    </div>

    <!-- ── Content Requests ──────────────────────────────────────────────── -->
    <div class="container">
        <h2>📬 Content Requests</h2>
        <table>
        <thead>
            <tr><th>Title</th><th>Category</th><th>Message</th><th>Status</th><th>Submitted</th><th>Actions</th></tr>
        </thead>
        <tbody id="requestsTableBody">
            <tr><td colspan="6" style="text-align:center;">Loading…</td></tr>
        </tbody>
        </table>
    </div>

</div>

<script src="../js/admin.js"></script>
</body>
</html>
