<?php
// task3/controller/mod_controller.php
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();
session_start();
require_once __DIR__ . '/../model/mod_model.php';
ob_clean();
header('Content-Type: application/json');

// Moderator gate
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'moderator') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Moderator access only.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Get All Contents ──────────────────────────────────────────────────
    case 'getContents':
        $query      = trim($_GET['q'] ?? '');
        $categoryId = (int)($_GET['category_id'] ?? 0) ?: null;
        if ($query) {
            $contents = searchContentsMod($query, $categoryId);
        } else {
            $contents = getAllContentsMod();
        }
        echo json_encode(['success' => true, 'contents' => $contents]);
        break;

    // ── Add Content ───────────────────────────────────────────────────────
    case 'addContent':
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);

        if (empty($title)) {
            echo json_encode(['success' => false, 'error' => 'Title is required.']);
            break;
        }
        if (!$categoryId) {
            echo json_encode(['success' => false, 'error' => 'Please select a category.']);
            break;
        }
        if (empty($_FILES['content_file']['name'])) {
            echo json_encode(['success' => false, 'error' => 'File is required.']);
            break;
        }

        $uploadDir   = __DIR__ . '/../../public/uploads/contents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $allowedExts = ['mp4','mkv','avi','mov','pdf','zip','exe','iso','jpg','jpeg','png','mp3'];
        $ext         = strtolower(pathinfo($_FILES['content_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            echo json_encode(['success' => false, 'error' => 'File type not allowed.']);
            break;
        }
        if ($_FILES['content_file']['size'] > 500 * 1024 * 1024) {
            echo json_encode(['success' => false, 'error' => 'File too large (max 500MB).']);
            break;
        }

        // MIME check
        $allowedMimes = [
            'video/mp4','video/x-matroska','video/avi','video/quicktime',
            'application/pdf','application/zip','application/x-zip-compressed',
            'application/x-msdownload','application/octet-stream',
            'image/jpeg','image/png','audio/mpeg'
        ];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $_FILES['content_file']['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mime, $allowedMimes) && !in_array($ext, $allowedExts)) {
            echo json_encode(['success' => false, 'error' => 'Invalid file type.']);
            break;
        }

        $filename   = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        move_uploaded_file($_FILES['content_file']['tmp_name'], $uploadDir . $filename);
        $filePath   = 'uploads/contents/' . $filename;
        $uploaderId = (int)$_SESSION['user_id'];

        echo json_encode(addContentMod($title, $description, $categoryId, $filePath, $uploaderId));
        break;

    // ── Delete Content ────────────────────────────────────────────────────
    case 'deleteContent':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID.']); break; }
        echo json_encode(deleteContentMod($id));
        break;

    // ── Get Requests ──────────────────────────────────────────────────────
    case 'getRequests':
        $requests = getContentRequestsMod();
        echo json_encode(['success' => true, 'requests' => $requests]);
        break;

    // ── Update Request Status (AJAX) ──────────────────────────────────────
    case 'updateRequestStatus':
        $id     = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID.']); break; }
        echo json_encode(updateRequestStatus($id, $status));
        break;

    // ── Get Categories ────────────────────────────────────────────────────
    case 'getCategories':
        $cats = getAllCategoriesMod();
        echo json_encode(['success' => true, 'categories' => $cats]);
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
}
ob_end_flush();
?>
