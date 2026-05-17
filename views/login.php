<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — ISP Media FTP</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<nav class="navbar">
    <a class="brand" href="home.php">ISP<span>Media</span></a>
    <ul class="nav-links">
        <li><a href="home.php">Home</a></li>
        <li><a href="login.php" class="active">Login</a></li>
        <li><a href="register.php">Register</a></li>
    </ul>
</nav>

<div class="auth-wrapper">
    <div class="auth-box">
        <h1>Welcome Back</h1>
        <p class="subtitle">Sign in to your ISP Media account</p>

        <div id="flashMsg"></div>

        <div class="form-group">
            <label>Email Address</label>
            <input type="email" id="loginEmail" placeholder="you@example.com">
            <span class="error-msg" id="errLoginEmail">Email is required.</span>
        </div>
        <div class="form-group">
            <label>Password</label>
            <input type="password" id="loginPassword" placeholder="Password">
            <span class="error-msg" id="errLoginPassword">Password is required.</span>
        </div>

        <div style="display:flex;align-items:center;gap:8px;margin:12px 0;">
            <input type="checkbox" id="rememberMe" style="width:auto;">
            <label for="rememberMe" style="font-size:13px;color:#555;cursor:pointer;">Remember me for 30 days</label>
        </div>

        <button type="button" class="btn btn-primary" style="width:100%;" onclick="submitLogin()">
            Sign In
        </button>

        <div class="auth-footer">
            New here? <a href="register.php">Create an account</a><br><br>
            <a href="home.php">← Browse as Guest</a>
        </div>
    </div>
</div>

<script src="../js/auth.js"></script>
</body>
</html>
