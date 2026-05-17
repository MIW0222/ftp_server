<?php
// task1/views/category.php
// Shows contents under a selected category + sub-category filters
session_start();

$isLoggedIn = !empty($_SESSION['user_id']);
$role       = $_SESSION['role'] ?? 'guest';
$name       = $_SESSION['name'] ?? 'Guest';

// Auto-login via remember cookie
if (!$isLoggedIn && !empty($_COOKIE['remember_token'])) {
    require_once __DIR__ . '/../model/auth_model.php';
    $u = getUserByToken($_COOKIE['remember_token']);
    if ($u) {
        $_SESSION['user_id'] = $u['id'];
        $_SESSION['name']    = $u['name'];
        $_SESSION['role']    = $u['role'];
        $isLoggedIn = true;
        $role       = $u['role'];
        $name       = $u['name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse by Category — ISP Media FTP</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>

<!-- Navbar -->
<nav class="navbar">
    <a class="brand" href="home.php">ISP<span>Media</span></a>
    <ul class="nav-links">
        <li><a href="home.php">Home</a></li>
        <li><a href="category.php" class="active">Browse</a></li>
        <?php if ($isLoggedIn): ?>
            <?php if ($role === 'admin'): ?>
                <li><a href="../../task2/views/admin_dashboard.php">Dashboard</a></li>
            <?php elseif ($role === 'moderator'): ?>
                <li><a href="../../task3/views/mod_dashboard.php">Dashboard</a></li>
            <?php endif; ?>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="#" class="btn-nav" onclick="doLogout()">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
            <li><a href="register.php" class="btn-nav">Register</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="page-wrapper">
    <h1 class="page-title">Browse Content</h1>
    <p class="page-subtitle">Select a category to explore available media</p>

    <!-- Category Tabs (top-level, fetched from DB) -->
    <div id="categoryTabs" class="category-tabs"></div>

    <!-- Sub-category filter row -->
    <div id="subCatRow" style="display:none; margin-bottom:18px;">
        <p style="color:#aaa;font-size:13px;margin-bottom:8px;">Filter by sub-category:</p>
        <div id="subCatTabs" class="category-tabs" style="flex-wrap:wrap;"></div>
    </div>

    <!-- Search within category -->
    <div class="container" style="padding:16px 22px;margin-bottom:16px;">
        <div class="search-bar">
            <input type="text" id="catSearchInput" placeholder="Search within this category…" oninput="onCatSearch()">
        </div>
    </div>

    <!-- Content Grid -->
    <div class="content-grid" id="catContentGrid">
        <p style="color:#aaa;padding:40px;text-align:center;">Select a category above to browse content.</p>
    </div>

</div>

<script>
// Standalone category navigation script (Task 1)
const AUTH_API   = '../controller/auth_controller.php';
const MEMBER_API = '../../task4/controller/member_controller.php';

let activeCategoryId    = null;
let activeSubCategoryId = null;

function escapeHtml(text) {
    const d = document.createElement('div');
    d.textContent = String(text ?? '');
    return d.innerHTML;
}

// ── Load top-level category tabs ──────────────────────────────────────────────
function loadCategoryTabs() {
    fetch(`${AUTH_API}?action=getCategories`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const tabs = document.getElementById('categoryTabs');
            tabs.innerHTML = data.categories.map(cat =>
                `<span class="category-tab" data-id="${cat.id}" onclick="selectCategory(${cat.id}, this)">
                    ${escapeHtml(cat.name)}
                </span>`
            ).join('');
        });
}

// ── Select a top-level category ───────────────────────────────────────────────
function selectCategory(catId, el) {
    activeCategoryId    = catId;
    activeSubCategoryId = null;

    document.querySelectorAll('#categoryTabs .category-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');

    // Load sub-categories
    fetch(`${AUTH_API}?action=getSubCategories&parent_id=${catId}`)
        .then(r => r.json())
        .then(data => {
            const subRow  = document.getElementById('subCatRow');
            const subTabs = document.getElementById('subCatTabs');
            if (data.subcategories && data.subcategories.length) {
                subTabs.innerHTML =
                    `<span class="category-tab active" onclick="selectSubCategory(null, this)">All</span>` +
                    data.subcategories.map(s =>
                        `<span class="category-tab" data-id="${s.id}" onclick="selectSubCategory(${s.id}, this)">
                            ${escapeHtml(s.name)}
                        </span>`
                    ).join('');
                subRow.style.display = 'block';
            } else {
                subRow.style.display = 'none';
                subTabs.innerHTML    = '';
            }
        });

    // Load contents for this category
    fetchContents();
}

// ── Select a sub-category ─────────────────────────────────────────────────────
function selectSubCategory(subId, el) {
    activeSubCategoryId = subId;
    document.querySelectorAll('#subCatTabs .category-tab').forEach(t => t.classList.remove('active'));
    el.classList.add('active');
    fetchContents();
}

// ── Fetch + render contents ───────────────────────────────────────────────────
let searchTimer = null;
function onCatSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(fetchContents, 350);
}

function fetchContents() {
    if (!activeCategoryId) return;
    const q = document.getElementById('catSearchInput').value.trim();
    let url  = `${MEMBER_API}?action=search&q=${encodeURIComponent(q)}&category_id=${activeCategoryId}`;
    if (activeSubCategoryId) url += `&sub_category_id=${activeSubCategoryId}`;

    fetch(url)
        .then(r => r.json())
        .then(data => renderGrid(data.contents || []));
}

function renderGrid(contents) {
    const grid = document.getElementById('catContentGrid');
    if (!contents.length) {
        grid.innerHTML = `<div style="grid-column:1/-1;text-align:center;padding:50px;color:#aaa;">
            <div style="font-size:48px;">📂</div>
            <p style="margin-top:12px;">No content found in this category.</p>
        </div>`;
        return;
    }
    grid.innerHTML = contents.map(c => `
        <div class="content-card">
            <span class="badge">${escapeHtml(c.category_name || 'Uncategorized')}</span>
            <h4 style="margin-top:8px;">${escapeHtml(c.title)}</h4>
            <p>${escapeHtml((c.description || 'No description.').substring(0, 120))}${(c.description||'').length > 120 ? '…' : ''}</p>
            <small style="color:#999;">⬇ ${c.download_count} downloads &nbsp;|&nbsp; 📅 ${escapeHtml(c.uploaded_at.split(' ')[0])}</small>
            <br>
            <a class="download-btn"
               href="../../public/${escapeHtml(c.file_path)}"
               download
               onclick="trackDL(${c.id})">⬇ Download</a>
        </div>
    `).join('');
}

function trackDL(id) {
    fetch(`${MEMBER_API}?action=incrementDownload&id=${id}`).catch(() => {});
}

function doLogout() {
    if (!confirm('Logout?')) return;
    const fd = new FormData();
    fd.append('action', 'logout');
    fetch('../controller/auth_controller.php', { method: 'POST', body: fd })
        .then(() => { window.location.href = 'home.php'; });
}

window.onload = loadCategoryTabs;
</script>
</body>
</html>
