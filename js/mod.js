// task3/js/mod.js

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text ?? '');
    return div.innerHTML;
}

function showFlash(id, message, type = 'success') {
    const el = document.getElementById(id);
    if (!el) return;
    el.innerHTML = `<div class="flash flash-${type}">${escapeHtml(message)}</div>`;
    setTimeout(() => { el.innerHTML = ''; }, 5000);
}

function showError(id, show = true) {
    const el = document.getElementById(id);
    if (el) el.classList.toggle('show', show);
}

function clearErrors(...ids) {
    ids.forEach(id => showError(id, false));
}

function doLogout() {
    if (!confirm('Logout?')) return;
    const fd = new FormData();
    fd.append('action', 'logout');
    fetch('../../task1/controller/auth_controller.php', { method: 'POST', body: fd })
        .then(() => { window.location.href = '../../task1/views/login.php'; });
}

// ── Categories ────────────────────────────────────────────────────────────────
function loadCategoryDropdowns() {
    fetch('../controller/mod_controller.php?action=getCategories')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const opts = data.categories.map(c =>
                `<option value="${c.id}">${c.parent_name ? escapeHtml(c.parent_name) + ' → ' : ''}${escapeHtml(c.name)}</option>`
            ).join('');
            ['modCtCategory', 'modFilterCat'].forEach(id => {
                const el = document.getElementById(id);
                if (!el) return;
                const base = id === 'modFilterCat' ? '<option value="">All Categories</option>' : '<option value="">— Select —</option>';
                el.innerHTML = base + opts;
            });
        });
}

// ── Upload Content ────────────────────────────────────────────────────────────
function uploadContentMod() {
    clearErrors('errModCtTitle','errModCtCat','errModCtFile');

    const title      = document.getElementById('modCtTitle').value.trim();
    const desc       = document.getElementById('modCtDesc').value.trim();
    const categoryId = document.getElementById('modCtCategory').value;
    const file       = document.getElementById('modCtFile').files[0];

    let valid = true;
    if (!title)      { showError('errModCtTitle'); valid = false; }
    if (!categoryId) { showError('errModCtCat');   valid = false; }
    if (!file)       { showError('errModCtFile');  valid = false; }
    if (!valid) return;

    const fd = new FormData();
    fd.append('action',       'addContent');
    fd.append('title',        title);
    fd.append('description',  desc);
    fd.append('category_id',  categoryId);
    fd.append('content_file', file);

    showFlash('uploadFlash', 'Uploading…', 'info');

    fetch('../controller/mod_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('uploadFlash', 'Content uploaded successfully!', 'success');
                loadContents();
                document.getElementById('modCtTitle').value = '';
                document.getElementById('modCtDesc').value  = '';
                document.getElementById('modCtFile').value  = '';
            } else {
                showFlash('uploadFlash', data.error || 'Upload failed.', 'error');
            }
        })
        .catch(() => showFlash('uploadFlash', 'Network error.', 'error'));
}

// ── Load / Search Contents ────────────────────────────────────────────────────
function loadContents() {
    const q   = document.getElementById('modSearch')?.value.trim() || '';
    const cat = document.getElementById('modFilterCat')?.value || '';
    const url = `../controller/mod_controller.php?action=getContents&q=${encodeURIComponent(q)}&category_id=${cat}`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('modContentBody');
            if (!data.success || !data.contents.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No contents found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.contents.map(c => `
                <tr>
                    <td>${escapeHtml(c.title)}</td>
                    <td>${escapeHtml(c.category_name || '—')}</td>
                    <td>${escapeHtml(c.uploader_name || 'Unknown')}</td>
                    <td>${c.download_count}</td>
                    <td>${escapeHtml(c.uploaded_at)}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteContent(${c.id}, '${escapeHtml(c.title)}')">🗑 Delete</button>
                    </td>
                </tr>
            `).join('');
        });
}

let searchTimer = null;
function searchContents() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(loadContents, 350);
}

// ── Delete Content ────────────────────────────────────────────────────────────
function deleteContent(id, title) {
    if (!confirm(`Delete "${title}"? This cannot be undone.`)) return;
    const fd = new FormData();
    fd.append('action', 'deleteContent');
    fd.append('id', id);
    fetch('../controller/mod_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('uploadFlash', 'Content deleted.', 'success');
                loadContents();
            } else {
                showFlash('uploadFlash', data.error || 'Delete failed.', 'error');
            }
        });
}

// ── Load Requests ─────────────────────────────────────────────────────────────
function loadRequests() {
    fetch('../controller/mod_controller.php?action=getRequests')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('modRequestBody');
            if (!data.success || !data.requests.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No requests found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.requests.map(req => `
                <tr>
                    <td>${escapeHtml(req.content_title)}</td>
                    <td>${escapeHtml(req.category_requested || '—')}</td>
                    <td>${escapeHtml(req.message || '—')}</td>
                    <td>${escapeHtml(req.created_at)}</td>
                    <td><span class="status-badge status-${req.status}">${req.status}</span></td>
                    <td>
                        <button class="btn btn-success btn-sm"   onclick="updateStatus(${req.id}, 'fulfilled')">✔ Fulfill</button>
                        <button class="btn btn-danger btn-sm"    onclick="updateStatus(${req.id}, 'rejected')">✘ Reject</button>
                        <button class="btn btn-secondary btn-sm" onclick="updateStatus(${req.id}, 'pending')">↩ Reset</button>
                    </td>
                </tr>
            `).join('');
        });
}

// ── Update Request Status (AJAX — no page reload) ─────────────────────────────
function updateStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'updateRequestStatus');
    fd.append('id',     id);
    fd.append('status', status);
    fetch('../controller/mod_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('reqFlash', `Request marked as "${status}".`, 'success');
                loadRequests();
            } else {
                showFlash('reqFlash', data.error || 'Status update failed.', 'error');
            }
        })
        .catch(() => showFlash('reqFlash', 'Network error.', 'error'));
}

// ── Init ──────────────────────────────────────────────────────────────────────
window.onload = function () {
    loadCategoryDropdowns();
    loadContents();
    loadRequests();
};
