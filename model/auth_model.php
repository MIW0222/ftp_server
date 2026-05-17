<?php
// task1/model/auth_model.php
session_start();
require_once __DIR__ . '/../../config/db.php';

function registerPendingModerator($name, $email, $password, $picturePath = null) {
    $conn = getDB();
    // Check not already registered (pending or approved)
    $stmt = mysqli_prepare($conn, "SELECT id, status FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $existing = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    if ($existing) {
        mysqli_close($conn);
        $msg = $existing['status'] === 'pending'
            ? 'A registration request for this email is already pending approval.'
            : 'Email already registered.';
        return ['success' => false, 'error' => $msg];
    }

    $hash   = password_hash($password, PASSWORD_DEFAULT);
    $status = 'pending';
    $role   = 'moderator';
    $stmt   = mysqli_prepare($conn,
        "INSERT INTO users (name, email, password_hash, role, status, profile_picture) VALUES (?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $hash, $role, $status, $picturePath);
    $result = mysqli_stmt_execute($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => 'DB error: ' . $err];
    return ['success' => true];
}

function registerUser($name, $email, $password, $role, $picturePath = null) {
    $conn = getDB();
    // Check unique email
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'error' => 'Email already registered.'];
    }
    mysqli_stmt_close($stmt);

    $hash   = password_hash($password, PASSWORD_DEFAULT);
    $status = 'approved'; // admin registrations are always approved immediately
    $stmt = mysqli_prepare($conn, "INSERT INTO users (name, email, password_hash, role, status, profile_picture) VALUES (?,?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssssss', $name, $email, $hash, $role, $status, $picturePath);
    $result = mysqli_stmt_execute($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => 'DB error: ' . $err];
    return ['success' => true];
}

function loginUser($email, $password) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, password_hash, role, status, profile_picture FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    if (!$user || !password_verify($password, $user['password_hash'])) {
        return ['success' => false, 'error' => 'Invalid email or password.'];
    }
    if (($user['status'] ?? 'approved') === 'pending') {
        return ['success' => false, 'error' => 'Your account is pending admin approval. Please wait before logging in.'];
    }
    return ['success' => true, 'user' => $user];
}

function getUserById($id) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, role, profile_picture, created_at FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $user;
}

function updateProfile($id, $name, $email, $picturePath = null) {
    $conn = getDB();
    // Check email unique (excluding self)
    $stmt = mysqli_prepare($conn, "SELECT id FROM users WHERE email = ? AND id != ?");
    mysqli_stmt_bind_param($stmt, 'si', $email, $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        mysqli_stmt_close($stmt);
        mysqli_close($conn);
        return ['success' => false, 'error' => 'Email already in use by another account.'];
    }
    mysqli_stmt_close($stmt);

    if ($picturePath) {
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=?, profile_picture=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'sssi', $name, $email, $picturePath, $id);
    } else {
        $stmt = mysqli_prepare($conn, "UPDATE users SET name=?, email=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssi', $name, $email, $id);
    }
    $result = mysqli_stmt_execute($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => $err];
    return ['success' => true];
}

function changePassword($id, $currentPassword, $newPassword) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT password_hash FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $row  = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if (!$row || !password_verify($currentPassword, $row['password_hash'])) {
        mysqli_close($conn);
        return ['success' => false, 'error' => 'Current password is incorrect.'];
    }
    $newHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt    = mysqli_prepare($conn, "UPDATE users SET password_hash=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'si', $newHash, $id);
    $result  = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => 'Password update failed.'];
    return ['success' => true];
}

function saveRememberToken($userId, $token) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "UPDATE users SET remember_token=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'si', $token, $userId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

function getUserByToken($token) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT id, name, email, role, profile_picture FROM users WHERE remember_token = ?");
    mysqli_stmt_bind_param($stmt, 's', $token);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $user;
}

function getTopLevelCategories() {
    $conn   = getDB();
    $result = mysqli_query($conn, "SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
    $cats   = [];
    while ($row = mysqli_fetch_assoc($result)) $cats[] = $row;
    mysqli_close($conn);
    return $cats;
}

function getHighlightedContents($limit = 8) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn,
        "SELECT c.id, c.title, c.description, c.file_path, c.download_count, c.uploaded_at,
                cat.name AS category_name
         FROM contents c
         LEFT JOIN categories cat ON c.category_id = cat.id
         ORDER BY c.download_count DESC, c.uploaded_at DESC
         LIMIT ?");
    mysqli_stmt_bind_param($stmt, 'i', $limit);
    mysqli_stmt_execute($stmt);
    $res      = mysqli_stmt_get_result($stmt);
    $contents = [];
    while ($row = mysqli_fetch_assoc($res)) $contents[] = $row;
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $contents;
}

function getContentsByCategory($categoryId) {
    $conn = getDB();
    // Fetch top-level + sub-category IDs
    $ids = [$categoryId];
    $sub = mysqli_prepare($conn, "SELECT id FROM categories WHERE parent_id = ?");
    mysqli_stmt_bind_param($sub, 'i', $categoryId);
    mysqli_stmt_execute($sub);
    $subRes = mysqli_stmt_get_result($sub);
    while ($r = mysqli_fetch_assoc($subRes)) $ids[] = $r['id'];
    mysqli_stmt_close($sub);

    $inPlaceholders = implode(',', array_fill(0, count($ids), '?'));
    $types          = str_repeat('i', count($ids));
    $stmt = mysqli_prepare($conn,
        "SELECT c.id, c.title, c.description, c.file_path, c.download_count, c.uploaded_at,
                cat.name AS category_name
         FROM contents c
         LEFT JOIN categories cat ON c.category_id = cat.id
         WHERE c.category_id IN ($inPlaceholders)
         ORDER BY c.uploaded_at DESC");
    mysqli_stmt_bind_param($stmt, $types, ...$ids);
    mysqli_stmt_execute($stmt);
    $res      = mysqli_stmt_get_result($stmt);
    $contents = [];
    while ($row = mysqli_fetch_assoc($res)) $contents[] = $row;
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $contents;
}

function getSubCategories($parentId) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT id, name FROM categories WHERE parent_id = ? ORDER BY name");
    mysqli_stmt_bind_param($stmt, 'i', $parentId);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $subs = [];
    while ($row = mysqli_fetch_assoc($res)) $subs[] = $row;
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $subs;
}
?>
