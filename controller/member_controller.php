<?php
// task4/controller/member_controller.php
// No auth required — public endpoint for members (unregistered users)
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();
session_start();
require_once __DIR__ . '/../model/member_model.php';
ob_clean();
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Search / Browse Contents (AJAX GET) ───────────────────────────────
    // Endpoint: /api/contents/search?q=&category_id=&sub_category_id=
    case 'search':
        $query         = trim($_GET['q'] ?? '');
        $categoryId    = (int)($_GET['category_id'] ?? 0)     ?: null;
        $subCategoryId = (int)($_GET['sub_category_id'] ?? 0) ?: null;

        // Server-side: sanitize search input
        $query = htmlspecialchars(strip_tags($query), ENT_QUOTES, 'UTF-8');

        $contents = searchContents($query, $categoryId, $subCategoryId);
        echo json_encode(['success' => true, 'contents' => $contents]);
        break;

    // ── Increment Download Count ──────────────────────────────────────────
    case 'incrementDownload':
        $id = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
        if ($id) incrementDownloadCount($id);
        echo json_encode(['success' => true]);
        break;

    // ── Submit Content Request (AJAX POST) ────────────────────────────────
    // Endpoint: /api/requests/add
    case 'addRequest':
        $title    = trim($_POST['content_title'] ?? '');
        $category = trim($_POST['category_requested'] ?? '');
        $message  = trim($_POST['message'] ?? '');

        if (empty($title)) {
            echo json_encode(['success' => false, 'error' => 'Content title is required.']);
            break;
        }
        if (strlen($title) > 255) {
            echo json_encode(['success' => false, 'error' => 'Title too long (max 255 characters).']);
            break;
        }

        $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $result = addContentRequest($title, $category, $message, $ip);
        echo json_encode($result);
        break;

    // ── Get Top-Level Categories ──────────────────────────────────────────
    case 'getCategories':
        $cats = getTopLevelCategoriesMember();
        echo json_encode(['success' => true, 'categories' => $cats]);
        break;

    // ── Get Sub-categories ────────────────────────────────────────────────
    case 'getSubCategories':
        $parentId = (int)($_GET['parent_id'] ?? 0);
        if (!$parentId) {
            echo json_encode(['success' => false, 'error' => 'parent_id required.']);
            break;
        }
        $subs = getSubCategoriesMember($parentId);
        echo json_encode(['success' => true, 'subcategories' => $subs]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
}
ob_end_flush();
?>
