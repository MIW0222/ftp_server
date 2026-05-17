<?php
// task1/views/home.php
session_start();

// Auto-login via remember_me cookie
if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_token'])) {
    require_once __DIR__ . '/../model/auth_model.php';
    $u = getUserByToken($_COOKIE['remember_token']);
    if ($u) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['name']    = $u['name'];
        $_SESSION['role']    = $u['role'];
        $_SESSION['email']   = $u['email'];
        $_SESSION['picture'] = $u['profile_picture'];
    }
}

$isLoggedIn = !empty($_SESSION['user_id']);
$role       = $_SESSION['role'] ?? 'guest';
$name       = $_SESSION['name'] ?? 'Guest';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ISP Media FTP — Home</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a class="brand" href="home.php">ISP<span>Media</span></a>
    <ul class="nav-links">
        <li><a href="home.php" class="active">Home</a></li>
        <?php if ($isLoggedIn): ?>
            <?php if ($role === 'admin'): ?>
                <li><a href="../../task2/views/admin_dashboard.php">Dashboard</a></li>
            <?php elseif ($role === 'moderator'): ?>
                <li><a href="../../task3/views/mod_dashboard.php">Dashboard</a></li>
            <?php endif; ?>
            <li><a href="profile.php">Profile (<?= htmlspecialchars($name) ?>)</a></li>
            <li><a href="#" class="btn-nav" onclick="doLogout()">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php" class="btn-nav">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="page-wrapper">

    <!-- Hero -->
    <div style="text-align:center;padding:30px 0 20px;">
        <h1 class="page-title" style="font-size:34px;">ISP Media FTP Server</h1>
        <p class="page-subtitle" style="font-size:16px;">Browse & Download Movies, Software, TV Series, Games and more</p>
    </div>

    <!-- Search Bar (Task 4 integration) -->
    <div class="container" style="padding:20px 25px;">
        <div class="search-bar">
            <input type="text" id="searchInput" placeholder="Search by title or description…" oninput="liveSearch()">
            <select id="filterCategory" onchange="liveSearch()">
                <option value="">All Categories</option>
            </select>
            <select id="filterSubCategory" onchange="liveSearch()">
                <option value="">All Sub-categories</option>
            </select>
        </div>
    </div>

    <!-- Category Tabs -->
    <div id="categoryTabs" class="category-tabs" style="padding:0 4px;"></div>

    <!-- Content Grid -->
    <div id="contentArea">
        <div class="content-grid" id="contentGrid">
            <p style="color:#aaa;text-align:center;padding:40px 0;">Loading content…</p>
        </div>
    </div>

    <!-- Request Box (Task 4) -->
    <div class="container" style="margin-top:30px;">
        <h2>📬 Request Content</h2>
        <p style="color:#666;font-size:14px;margin-bottom:16px;">Can't find what you're looking for? Submit a request!</p>
        <div id="requestFlash"></div>
        <div class="form-section">
            <div class="form-row">
                <div class="form-group">
                    <label>Content Title *</label>
                    <input type="text" id="reqTitle" placeholder="e.g. Inception (2010)">
                    <span class="error-msg" id="errReqTitle">Title is required.</span>
                </div>
                <div class="form-group">
                    <label>Category</label>
                    <select id="reqCategory" onchange="loadReqSubCategories()">
                        <option value="">— Select Category —</option>
                    </select>
                </div>
                <div class="form-group" id="reqSubCategoryGroup" style="display:none;">
                    <label>Sub-category <small>(optional)</small></label>
                    <select id="reqSubCategory">
                        <option value="">— Select Sub-category —</option>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex:2;">
                    <label>Message <small>(optional)</small></label>
                    <textarea id="reqMessage" placeholder="Any additional details…"></textarea>
                </div>
            </div>
            <button type="button" class="btn btn-primary" onclick="submitRequest()">Submit Request</button>
        </div>
    </div>

</div><!-- /page-wrapper -->

<script src="../js/auth.js"></script>
<script src="../../task4/js/member.js"></script>
</body>
</html>
