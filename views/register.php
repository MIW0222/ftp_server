<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register — ISP Media FTP</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a class="brand" href="home.php">ISP<span>Media</span></a>
    <ul class="nav-links">
        <li><a href="home.php">Home</a></li>
        <li><a href="login.php">Login</a></li>
        <li><a href="register.php" class="active">Register</a></li>
    </ul>
</nav>

<div class="auth-wrapper">
    <div class="auth-box">
        <h1>Create Account</h1>
        <p class="subtitle">Register as Admin or Moderator</p>

        <div id="flashMsg"></div>

        <div class="form-group">
            <label>Full Name</label>
            <input type="text" id="regName" placeholder="Your full name">
            <span class="error-msg" id="errName">Name is required.</span>
        </div>
        <div class="form-group">
            <label>Email Address</label>
            <input type="email" id="regEmail" placeholder="you@example.com">
            <span class="error-msg" id="errEmail">Valid email is required.</span>
        </div>
        <div class="form-group">
            <label>Role</label>
            <select id="regRole">
                <option value="">— Select Role —</option>
                <option value="admin">Admin</option>
                <option value="moderator">Moderator</option>
            </select>
            <span class="error-msg" id="errRole">Please select a role.</span>
        </div>
        <div class="form-group">
            <label>Password <small>(min. 8 characters)</small></label>
            <input type="password" id="regPassword" placeholder="Password">
            <span class="error-msg" id="errPassword">Password must be at least 8 characters.</span>
        </div>
        <div class="form-group">
            <label>Confirm Password</label>
            <input type="password" id="regConfirm" placeholder="Confirm password">
            <span class="error-msg" id="errConfirm">Passwords do not match.</span>
        </div>
        <div class="form-group">
            <label>Profile Picture <small>(optional)</small></label>
            <input type="file" id="regPicture" accept="image/*">
        </div>

        <button type="button" class="btn btn-primary" style="width:100%;margin-top:8px;" onclick="submitRegister()">
            Register
        </button>

        <div class="auth-footer">
            Already have an account? <a href="login.php">Login here</a>
        </div>
    </div>
</div>

<script src="../js/auth.js"></script>
</body>
</html>
