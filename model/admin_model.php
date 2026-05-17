<?php
// task2/model/admin_model.php
require_once __DIR__ . '/../../config/db.php';

// ── Moderator Management ──────────────────────────────────────────────────────
function getAllModerators() {
    $conn   = getDB();
    $result = mysqli_query($conn, "SELECT id, name, email, profile_picture, created_at FROM users WHERE role='moderator' AND status='approved' ORDER BY created_at DESC");
    $mods   = [];
    if ($result) while ($row = mysqli_fetch_assoc($result)) $mods[] = $row;
    mysqli_close($conn);
    return $mods;
}

function getPendingModerators() {
    $conn   = getDB();
    $result = mysqli_query($conn,
        "SELECT id, name, email, profile_picture, created_at FROM users WHERE role='moderator' AND status='pending' ORDER BY created_at ASC");
    $rows = [];
    if ($result) while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_close($conn);
    return $rows;
}

function approveModerator($userId) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn,
        "UPDATE users SET status='approved' WHERE id=? AND role='moderator' AND status='pending'");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    $result = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => $err];
    if ($affected === 0) return ['success' => false, 'error' => 'Pending request not found.'];
    return ['success' => true];
}

function declineModerator($userId) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn,
        "DELETE FROM users WHERE id=? AND role='moderator' AND status='pending'");
    mysqli_stmt_bind_param($stmt, 'i', $userId);
    $result = mysqli_stmt_execute($stmt);
    $affected = mysqli_stmt_affected_rows($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => $err];
    if ($affected === 0) return ['success' => false, 'error' => 'Pending request not found.'];
    return ['success' => true];
}

function addModeratorByAdmin($name, $email, $password) {
    $conn = getDB();
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
    $role   = 'moderator';
    $status = 'approved';
    $stmt   = mysqli_prepare($conn,
        "INSERT INTO users (name, email, password_hash, role, status) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sssss', $name, $email, $hash, $role, $status);
    $result = mysqli_stmt_execute($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => 'DB error: ' . $err];
    return ['success' => true];
}

function deleteModerator($id) {
    $conn = getDB();
    // Reassign their contents to null uploader
    $stmt = mysqli_prepare($conn, "UPDATE contents SET uploader_id=NULL WHERE uploader_id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    $stmt = mysqli_prepare($conn, "DELETE FROM users WHERE id=? AND role='moderator'");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $result = mysqli_stmt_execute($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => $err];
    return ['success' => true];
}

// ── Content Management ────────────────────────────────────────────────────────
function getAllContentsAdmin() {
    $conn   = getDB();
    $result = mysqli_query($conn,
        "SELECT c.id, c.title, c.description, c.file_path, c.download_count, c.uploaded_at,
                cat.name AS category_name, u.name AS uploader_name
         FROM contents c
         LEFT JOIN categories cat ON c.category_id = cat.id
         LEFT JOIN users u ON c.uploader_id = u.id
         ORDER BY c.uploaded_at DESC");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_close($conn);
    return $rows;
}

function getContentByIdAdmin($id) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "SELECT * FROM contents WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $row;
}

function addContent($title, $description, $categoryId, $filePath, $uploaderId) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn,
        "INSERT INTO contents (title, description, category_id, file_path, uploader_id) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'ssisi', $title, $description, $categoryId, $filePath, $uploaderId);
    $result = mysqli_stmt_execute($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => $err];
    return ['success' => true];
}

function updateContent($id, $title, $description, $categoryId, $filePath = null) {
    $conn = getDB();
    if ($filePath) {
        $stmt = mysqli_prepare($conn,
            "UPDATE contents SET title=?, description=?, category_id=?, file_path=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssisi', $title, $description, $categoryId, $filePath, $id);
    } else {
        $stmt = mysqli_prepare($conn,
            "UPDATE contents SET title=?, description=?, category_id=? WHERE id=?");
        mysqli_stmt_bind_param($stmt, 'ssii', $title, $description, $categoryId, $id);
    }
    $result = mysqli_stmt_execute($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => $err];
    return ['success' => true];
}

function deleteContent($id) {
    $conn = getDB();
    // Get file path first
    $stmt = mysqli_prepare($conn, "SELECT file_path FROM contents WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $row  = mysqli_fetch_assoc($res);
    mysqli_stmt_close($stmt);

    if ($row && $row['file_path']) {
        $fullPath = __DIR__ . '/../../public/' . $row['file_path'];
        if (file_exists($fullPath)) unlink($fullPath);
    }

    $stmt = mysqli_prepare($conn, "DELETE FROM contents WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $result = mysqli_stmt_execute($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => $err];
    return ['success' => true];
}

// ── Dashboard Stats ───────────────────────────────────────────────────────────
function getDashboardStats() {
    $conn  = getDB();
    $stats = [];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM contents");
    $stats['total_contents'] = (int)mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM categories");
    $stats['total_categories'] = (int)mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users WHERE role='moderator'");
    $stats['total_moderators'] = (int)mysqli_fetch_assoc($r)['cnt'];

    $r = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users WHERE role='moderator' AND status='pending'");
    $stats['pending_requests'] = (int)mysqli_fetch_assoc($r)['cnt'];

    mysqli_close($conn);
    return $stats;
}

function getAllCategoriesFlat() {
    $conn   = getDB();
    $result = mysqli_query($conn,
        "SELECT c.id, c.name, p.name AS parent_name
         FROM categories c
         LEFT JOIN categories p ON c.parent_id = p.id
         ORDER BY p.name, c.name");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_close($conn);
    return $rows;
}

function getAllRequestsAdmin() {
    $conn   = getDB();
    $result = mysqli_query($conn,
        "SELECT * FROM content_requests ORDER BY created_at DESC");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_close($conn);
    return $rows;
}

function updateRequestStatusAdmin($id, $status) {
    $allowed = ['pending', 'fulfilled', 'rejected'];
    if (!in_array($status, $allowed)) {
        return ['success' => false, 'error' => 'Invalid status.'];
    }

    $conn = getDB();
    $stmt = mysqli_prepare($conn, "UPDATE content_requests SET status=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'si', $status, $id);
    $result = mysqli_stmt_execute($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);

    if (!$result) return ['success' => false, 'error' => $err];
    return ['success' => true];
}
function deleteContentRequest($id) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "DELETE FROM content_requests WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    $result  = mysqli_stmt_execute($stmt);
    $err     = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => $err];
    return ['success' => true];
}
?>
