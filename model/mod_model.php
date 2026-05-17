<?php
// task3/model/mod_model.php
require_once __DIR__ . '/../../config/db.php';

function getAllContentsMod() {
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

function addContentMod($title, $description, $categoryId, $filePath, $uploaderId) {
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

function deleteContentMod($id) {
    $conn = getDB();
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

function getContentRequestsMod() {
    $conn   = getDB();
    $result = mysqli_query($conn,
        "SELECT * FROM content_requests ORDER BY created_at DESC");
    $rows = [];
    while ($row = mysqli_fetch_assoc($result)) $rows[] = $row;
    mysqli_close($conn);
    return $rows;
}

function updateRequestStatus($id, $status) {
    $allowed = ['pending', 'fulfilled', 'rejected'];
    if (!in_array($status, $allowed)) return ['success' => false, 'error' => 'Invalid status.'];

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

function getAllCategoriesMod() {
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

function searchContentsMod($query, $categoryId = null) {
    $conn  = getDB();
    $query = '%' . $query . '%';
    if ($categoryId) {
        $stmt = mysqli_prepare($conn,
            "SELECT c.id, c.title, c.description, c.file_path, c.download_count, c.uploaded_at,
                    cat.name AS category_name, u.name AS uploader_name
             FROM contents c
             LEFT JOIN categories cat ON c.category_id = cat.id
             LEFT JOIN users u ON c.uploader_id = u.id
             WHERE (c.title LIKE ? OR c.description LIKE ?) AND c.category_id = ?
             ORDER BY c.uploaded_at DESC");
        mysqli_stmt_bind_param($stmt, 'ssi', $query, $query, $categoryId);
    } else {
        $stmt = mysqli_prepare($conn,
            "SELECT c.id, c.title, c.description, c.file_path, c.download_count, c.uploaded_at,
                    cat.name AS category_name, u.name AS uploader_name
             FROM contents c
             LEFT JOIN categories cat ON c.category_id = cat.id
             LEFT JOIN users u ON c.uploader_id = u.id
             WHERE c.title LIKE ? OR c.description LIKE ?
             ORDER BY c.uploaded_at DESC");
        mysqli_stmt_bind_param($stmt, 'ss', $query, $query);
    }
    mysqli_stmt_execute($stmt);
    $res  = mysqli_stmt_get_result($stmt);
    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $rows;
}
?>
