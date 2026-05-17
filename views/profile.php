<?php
// task1/views/profile.php
session_start();
if (empty($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$role    = $_SESSION['role']    ?? '';
$name    = $_SESSION['name']    ?? '';
$email   = $_SESSION['email']   ?? '';
$picture = $_SESSION['picture'] ?? null;
$initial = strtoupper(substr($name, 0, 1));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile — ISP Media FTP</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<nav class="navbar">
    <a class="brand" href="home.php">ISP<span>Media</span></a>
    <ul class="nav-links">
        <li><a href="home.php">Home</a></li>
        <?php if ($role === 'admin'): ?>
            <li><a href="../../task2/views/admin_dashboard.php">Dashboard</a></li>
        <?php elseif ($role === 'moderator'): ?>
            <li><a href="../../task3/views/mod_dashboard.php">Dashboard</a></li>
        <?php endif; ?>
        <li><a href="profile.php" class="active">Profile</a></li>
        <li><a href="#" class="btn-nav" onclick="doLogout()">Logout</a></li>
    </ul>
</nav>

<div class="page-wrapper">
    <h1 class="page-title">My Profile</h1>
    <p class="page-subtitle">Manage your account details</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;flex-wrap:wrap;">

        <!-- Profile Info Card -->
        <div class="card">
            <div style="text-align:center;margin-bottom:20px;">
                <?php if ($picture): ?>
                    <img src="../../public/<?= htmlspecialchars($picture) ?>" alt="Profile" class="avatar">
                <?php else: ?>
                    <div class="avatar-placeholder"><?= $initial ?></div>
                <?php endif; ?>
                <h3 style="color:#1a1a2e;"><?= htmlspecialchars($name) ?></h3>
                <span style="background:#e53935;color:#fff;padding:3px 12px;border-radius:20px;font-size:12px;font-weight:600;">
                    <?= strtoupper($role) ?>
                </span>
                <p style="color:#888;font-size:13px;margin-top:8px;"><?= htmlspecialchars($email) ?></p>
            </div>

            <h2>Edit Profile</h2>
            <div id="profileFlash"></div>

            <div class="form-group" style="margin-bottom:14px;">
                <label>Full Name</label>
                <input type="text" id="profName" value="<?= htmlspecialchars($name) ?>">
                <span class="error-msg" id="errProfName">Name is required.</span>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label>Email Address</label>
                <input type="email" id="profEmail" value="<?= htmlspecialchars($email) ?>">
                <span class="error-msg" id="errProfEmail">Valid email is required.</span>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label>Profile Picture <small>(optional)</small></label>
                <input type="file" id="profPicture" accept="image/*">
            </div>
            <button type="button" class="btn btn-primary" onclick="submitUpdateProfile()">Save Changes</button>
        </div>

        <!-- Change Password Card -->
        <div class="card">
            <h2>Change Password</h2>
            <div id="pwFlash"></div>
            <div class="form-group" style="margin-bottom:14px;">
                <label>Current Password</label>
                <input type="password" id="curPassword" placeholder="Current password">
                <span class="error-msg" id="errCurPw">Current password is required.</span>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label>New Password <small>(min 8 chars)</small></label>
                <input type="password" id="newPassword" placeholder="New password">
                <span class="error-msg" id="errNewPw">Min 8 characters required.</span>
            </div>
            <div class="form-group" style="margin-bottom:14px;">
                <label>Confirm New Password</label>
                <input type="password" id="confirmNewPw" placeholder="Confirm new password">
                <span class="error-msg" id="errConfirmNewPw">Passwords do not match.</span>
            </div>
            <button type="button" class="btn btn-warning" onclick="submitChangePassword()">Update Password</button>
        </div>

    </div>
</div>

<script src="../js/auth.js"></script>
</body>
</html>
