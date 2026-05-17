<?php
// task1/controller/auth_controller.php
// Keep errors out of the response body — corrupted output breaks JSON parsing
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start(); // buffer everything so stray output never corrupts JSON
session_start();
require_once __DIR__ . '/../model/auth_model.php';
ob_clean(); // discard anything that leaked before this point
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Register ──────────────────────────────────────────────────────────
    case 'register':
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $role     = $_POST['role'] ?? '';

        if (empty($name) || empty($email) || empty($password) || empty($role)) {
            echo json_encode(['success' => false, 'error' => 'All fields are required.']);
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
            break;
        }
        if (strlen($password) < 8) {
            echo json_encode(['success' => false, 'error' => 'Password must be at least 8 characters.']);
            break;
        }
        if ($password !== $confirm) {
            echo json_encode(['success' => false, 'error' => 'Passwords do not match.']);
            break;
        }
        if (!in_array($role, ['admin', 'moderator'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid role selected.']);
            break;
        }

        // Handle profile picture upload
        $picturePath = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $uploadDir   = __DIR__ . '/../../public/uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $allowed     = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo       = finfo_open(FILEINFO_MIME_TYPE);
            $mime        = finfo_file($finfo, $_FILES['profile_picture']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) {
                echo json_encode(['success' => false, 'error' => 'Profile picture must be JPEG, PNG, GIF, or WEBP.']);
                break;
            }
            if ($_FILES['profile_picture']['size'] > 2 * 1024 * 1024) {
                echo json_encode(['success' => false, 'error' => 'Profile picture must be under 2MB.']);
                break;
            }
            $ext         = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename    = 'profile_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $filename);
            $picturePath = 'uploads/profiles/' . $filename;
        }

        // Moderators go into pending approval queue; admins register directly
        if ($role === 'moderator') {
            $result = registerPendingModerator($name, $email, $password, $picturePath);
            if ($result['success']) {
                echo json_encode(['success' => true, 'pending' => true,
                    'message' => 'Registration submitted! Please wait for admin approval before logging in.']);
            } else {
                echo json_encode($result);
            }
        } else {
            $result = registerUser($name, $email, $password, $role, $picturePath);
            echo json_encode($result);
        }
        break;

    // ── Login ─────────────────────────────────────────────────────────────
    case 'login':
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember_me']);

        if (empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Email and password are required.']);
            break;
        }

        $result = loginUser($email, $password);
        if (!$result['success']) {
            echo json_encode($result);
            break;
        }

        $user = $result['user'];
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name']    = $user['name'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['email']   = $user['email'];
        $_SESSION['picture'] = $user['profile_picture'];

        if ($remember) {
            $token = bin2hex(random_bytes(32));
            saveRememberToken($user['id'], $token);
            setcookie('remember_token', $token, time() + (30 * 24 * 3600), '/', '', false, true);
        }

        $redirect = ($user['role'] === 'admin') ? '../../task2/views/admin_dashboard.php' : '../../task3/views/mod_dashboard.php';
        echo json_encode(['success' => true, 'role' => $user['role'], 'redirect' => $redirect]);
        break;

    // ── Logout ────────────────────────────────────────────────────────────
    case 'logout':
        session_unset();
        session_destroy();
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        echo json_encode(['success' => true]);
        break;

    // ── Update Profile ────────────────────────────────────────────────────
    case 'updateProfile':
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
            break;
        }
        $id    = (int)$_SESSION['user_id'];
        $name  = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if (empty($name) || empty($email)) {
            echo json_encode(['success' => false, 'error' => 'Name and email are required.']);
            break;
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'error' => 'Invalid email format.']);
            break;
        }

        $picturePath = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $uploadDir = __DIR__ . '/../../public/uploads/profiles/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $allowed   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            $finfo     = finfo_open(FILEINFO_MIME_TYPE);
            $mime      = finfo_file($finfo, $_FILES['profile_picture']['tmp_name']);
            finfo_close($finfo);
            if (!in_array($mime, $allowed)) {
                echo json_encode(['success' => false, 'error' => 'Invalid image type.']);
                break;
            }
            $ext      = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $filename = 'profile_' . time() . '_' . $id . '.' . $ext;
            move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadDir . $filename);
            $picturePath = 'uploads/profiles/' . $filename;
        }

        $result = updateProfile($id, $name, $email, $picturePath);
        if ($result['success']) {
            $_SESSION['name']  = $name;
            $_SESSION['email'] = $email;
            if ($picturePath) $_SESSION['picture'] = $picturePath;
        }
        echo json_encode($result);
        break;

    // ── Change Password ───────────────────────────────────────────────────
    case 'changePassword':
        if (empty($_SESSION['user_id'])) {
            echo json_encode(['success' => false, 'error' => 'Not authenticated.']);
            break;
        }
        $id      = (int)$_SESSION['user_id'];
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_new'] ?? '';

        if (empty($current) || empty($new)) {
            echo json_encode(['success' => false, 'error' => 'All password fields are required.']);
            break;
        }
        if (strlen($new) < 8) {
            echo json_encode(['success' => false, 'error' => 'New password must be at least 8 characters.']);
            break;
        }
        if ($new !== $confirm) {
            echo json_encode(['success' => false, 'error' => 'New passwords do not match.']);
            break;
        }
        $result = changePassword($id, $current, $new);
        echo json_encode($result);
        break;

    // ── Get Categories (AJAX) ─────────────────────────────────────────────
    case 'getCategories':
        $cats = getTopLevelCategories();
        echo json_encode(['success' => true, 'categories' => $cats]);
        break;

    // ── Get Sub-categories ────────────────────────────────────────────────
    case 'getSubCategories':
        $parentId = (int)($_GET['parent_id'] ?? 0);
        $subs     = getSubCategories($parentId);
        echo json_encode(['success' => true, 'subcategories' => $subs]);
        break;

    // ── Get Highlighted Contents ──────────────────────────────────────────
    case 'getHighlighted':
        $contents = getHighlightedContents();
        echo json_encode(['success' => true, 'contents' => $contents]);
        break;

    // ── Get Contents by Category ──────────────────────────────────────────
    case 'getByCategory':
        $catId    = (int)($_GET['category_id'] ?? 0);
        $contents = getContentsByCategory($catId);
        echo json_encode(['success' => true, 'contents' => $contents]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
}
?>
