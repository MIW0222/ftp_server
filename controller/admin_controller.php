<?php
// task2/controller/admin_controller.php
error_reporting(0);
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ob_start();
session_start();
require_once __DIR__ . '/../model/admin_model.php';
ob_clean();
header('Content-Type: application/json');

// Admin gate
if (empty($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Unauthorized. Admin access only.']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

switch ($action) {

    // ── Dashboard Stats ───────────────────────────────────────────────────
    case 'getStats':
        $stats = getDashboardStats();
        echo json_encode(['success' => true, 'stats' => $stats]);
        break;

    // ── Get All Moderators ────────────────────────────────────────────────
    case 'getModerators':
        $mods = getAllModerators();
        echo json_encode(['success' => true, 'moderators' => $mods]);
        break;

    // ── Get Pending Moderator Registrations ───────────────────────────────
    case 'getPendingModerators':
        $pending = getPendingModerators();
        $encoded = json_encode(['success' => true, 'pending' => $pending]);
        if ($encoded === false) {
            // json_encode failed — sanitize strings and retry
            array_walk_recursive($pending, function(&$v) {
                if (is_string($v)) $v = mb_convert_encoding($v, 'UTF-8', 'UTF-8');
            });
            $encoded = json_encode(['success' => true, 'pending' => $pending], JSON_PARTIAL_OUTPUT_ON_ERROR);
        }
        echo $encoded;
        break;

    // ── Approve Moderator ─────────────────────────────────────────────────
    case 'approveModerator':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID.']); break; }
        echo json_encode(approveModerator($id));
        break;

    // ── Decline Moderator ─────────────────────────────────────────────────
    case 'declineModerator':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID.']); break; }
        echo json_encode(declineModerator($id));
        break;

    // ── Add Moderator by Admin ────────────────────────────────────────────
    case 'addModerator':
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = $_POST['password']      ?? '';
        $confirm  = $_POST['confirm']       ?? '';
        if (empty($name) || empty($email) || empty($password)) {
            echo json_encode(['success' => false, 'error' => 'Name, email and password are required.']);
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
        echo json_encode(addModeratorByAdmin($name, $email, $password));
        break;

    // ── Delete Moderator ──────────────────────────────────────────────────
    case 'deleteModerator':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID.']); break; }
        echo json_encode(deleteModerator($id));
        break;

    // ── Get All Contents ──────────────────────────────────────────────────
    case 'getContents':
        $contents = getAllContentsAdmin();
        echo json_encode(['success' => true, 'contents' => $contents]);
        break;

    // ── Get Content by ID ─────────────────────────────────────────────────
    case 'getContentById':
        $id      = (int)($_GET['id'] ?? 0);
        $content = getContentByIdAdmin($id);
        if ($content) {
            echo json_encode(['success' => true, 'content' => $content]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Content not found.']);
        }
        break;

    // ── Upload Content ────────────────────────────────────────────────────
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

        // File validation
        $uploadDir    = __DIR__ . '/../../public/uploads/contents/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $allowedMimes = [
            'video/mp4','video/x-matroska','video/avi','video/quicktime',
            'application/pdf','application/zip','application/x-zip-compressed',
            'application/x-msdownload','application/octet-stream',
            'image/jpeg','image/png','audio/mpeg'
        ];
        $allowedExts  = ['mp4','mkv','avi','mov','pdf','zip','exe','iso','jpg','jpeg','png','mp3'];
        $finfo        = finfo_open(FILEINFO_MIME_TYPE);
        $mime         = finfo_file($finfo, $_FILES['content_file']['tmp_name']);
        finfo_close($finfo);
        $ext          = strtolower(pathinfo($_FILES['content_file']['name'], PATHINFO_EXTENSION));

        if (!in_array($mime, $allowedMimes) && !in_array($ext, $allowedExts)) {
            echo json_encode(['success' => false, 'error' => 'File type not allowed.']);
            break;
        }
        if ($_FILES['content_file']['size'] > 500 * 1024 * 1024) { // 500MB
            echo json_encode(['success' => false, 'error' => 'File size exceeds 500MB limit.']);
            break;
        }

        $filename  = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
        move_uploaded_file($_FILES['content_file']['tmp_name'], $uploadDir . $filename);
        $filePath  = 'uploads/contents/' . $filename;
        $uploaderId = (int)$_SESSION['user_id'];

        $result = addContent($title, $description, $categoryId, $filePath, $uploaderId);
        echo json_encode($result);
        break;

    // ── Update Content ────────────────────────────────────────────────────
    case 'updateContent':
        $id          = (int)($_POST['id'] ?? 0);
        $title       = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId  = (int)($_POST['category_id'] ?? 0);

        if (!$id || empty($title) || !$categoryId) {
            echo json_encode(['success' => false, 'error' => 'ID, title and category are required.']);
            break;
        }

        $filePath = null;
        if (!empty($_FILES['content_file']['name'])) {
            $uploadDir    = __DIR__ . '/../../public/uploads/contents/';
            $allowedExts  = ['mp4','mkv','avi','mov','pdf','zip','exe','iso','jpg','jpeg','png','mp3'];
            $ext          = strtolower(pathinfo($_FILES['content_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowedExts)) {
                echo json_encode(['success' => false, 'error' => 'File type not allowed.']);
                break;
            }
            $filename  = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            move_uploaded_file($_FILES['content_file']['tmp_name'], $uploadDir . $filename);
            $filePath  = 'uploads/contents/' . $filename;
        }

        echo json_encode(updateContent($id, $title, $description, $categoryId, $filePath));
        break;

    // ── Delete Content ────────────────────────────────────────────────────
    case 'deleteContent':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID.']); break; }
        echo json_encode(deleteContent($id));
        break;

    // ── Get Categories ────────────────────────────────────────────────────
    case 'getCategories':
        $cats = getAllCategoriesFlat();
        echo json_encode(['success' => true, 'categories' => $cats]);
        break;

    // ── Get All Requests ──────────────────────────────────────────────────
    case 'getRequests':
        $requests = getAllRequestsAdmin();
        echo json_encode(['success' => true, 'requests' => $requests]);
        break;

    case 'updateRequestStatus':
        $id     = (int)($_POST['id'] ?? 0);
        $status = trim($_POST['status'] ?? '');
        if (!$id) { 
            echo json_encode(['success' => false, 'error' => 'Invalid ID.']); 
            break; 
        }
        echo json_encode(updateRequestStatusAdmin($id, $status));
        break;

    // ── Delete Content Request ────────────────────────────────────────────
    case 'deleteRequest':
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { echo json_encode(['success' => false, 'error' => 'Invalid ID.']); break; }
        echo json_encode(deleteContentRequest($id));
        break;

    default:
        echo json_encode(['success' => false, 'error' => 'Invalid action.']);
}
ob_end_flush();
?>
