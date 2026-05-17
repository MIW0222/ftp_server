<?php
session_start();
// task4/model/member_model.php
require_once __DIR__ . '/../../config/db.php';

function searchContents($query = '', $categoryId = null, $subCategoryId = null) {
    $conn        = getDB();
    $conditions  = [];
    $params      = [];
    $types       = '';

    if (!empty($query)) {
        $conditions[] = '(c.title LIKE ? OR c.description LIKE ?)';
        $like = '%' . $query . '%';
        $params[] = $like;
        $params[] = $like;
        $types   .= 'ss';
    }

    if ($subCategoryId) {
        $conditions[] = 'c.category_id = ?';
        $params[]     = $subCategoryId;
        $types       .= 'i';
    } elseif ($categoryId) {
        // Include sub-categories
        $subStmt = mysqli_prepare($conn, "SELECT id FROM categories WHERE parent_id = ?");
        mysqli_stmt_bind_param($subStmt, 'i', $categoryId);
        mysqli_stmt_execute($subStmt);
        $subRes = mysqli_stmt_get_result($subStmt);
        $ids    = [$categoryId];
        while ($r = mysqli_fetch_assoc($subRes)) $ids[] = $r['id'];
        mysqli_stmt_close($subStmt);

        $placeholders  = implode(',', array_fill(0, count($ids), '?'));
        $conditions[]  = "c.category_id IN ($placeholders)";
        foreach ($ids as $id) { $params[] = $id; $types .= 'i'; }
    }

    $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';
    $sql   = "SELECT c.id, c.title, c.description, c.file_path, c.download_count, c.uploaded_at,
                     cat.name AS category_name
              FROM contents c
              LEFT JOIN categories cat ON c.category_id = cat.id
              $where
              ORDER BY c.download_count DESC, c.uploaded_at DESC";

    if ($params) {
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
    } else {
        $res = mysqli_query($conn, $sql);
    }

    $rows = [];
    while ($row = mysqli_fetch_assoc($res)) $rows[] = $row;
    if (isset($stmt)) mysqli_stmt_close($stmt);
    mysqli_close($conn);
    return $rows;
}

function incrementDownloadCount($id) {
    $conn = getDB();
    $stmt = mysqli_prepare($conn, "UPDATE contents SET download_count = download_count + 1 WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
}

function addContentRequest($title, $categoryRequested, $message, $ip) {
    if (empty(trim($title))) return ['success' => false, 'error' => 'Content title is required.'];
    // Sanitize
    $title             = htmlspecialchars(strip_tags(trim($title)), ENT_QUOTES, 'UTF-8');
    $categoryRequested = htmlspecialchars(strip_tags(trim($categoryRequested)), ENT_QUOTES, 'UTF-8');
    $message           = htmlspecialchars(strip_tags(trim($message)), ENT_QUOTES, 'UTF-8');

    $conn   = getDB();
    $status = 'pending';
    $stmt   = mysqli_prepare($conn,
        "INSERT INTO content_requests (requester_ip, content_title, category_requested, message, status) VALUES (?,?,?,?,?)");
    mysqli_stmt_bind_param($stmt, 'sssss', $ip, $title, $categoryRequested, $message, $status);
    $result = mysqli_stmt_execute($stmt);
    $err    = mysqli_error($conn);
    mysqli_stmt_close($stmt);
    mysqli_close($conn);
    if (!$result) return ['success' => false, 'error' => $err];
    return ['success' => true];
}

function getTopLevelCategoriesMember() {
    $conn   = getDB();
    $result = mysqli_query($conn, "SELECT id, name FROM categories WHERE parent_id IS NULL ORDER BY name");
    $cats   = [];
    while ($row = mysqli_fetch_assoc($result)) $cats[] = $row;
    mysqli_close($conn);
    return $cats;
}

function getSubCategoriesMember($parentId) {
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
