// task2/js/admin.js

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text ?? '');
    return div.innerHTML;
}

function showFlash(containerId, message, type = 'success') {
    const el = document.getElementById(containerId);
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

// ── Load Stats ────────────────────────────────────────────────────────────────
function loadStats() {
    fetch('../controller/admin_controller.php?action=getStats')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const s    = data.stats;
            const grid = document.getElementById('statsGrid');
            grid.innerHTML = `
                <div class="stat-card"><div class="stat-number">${s.total_contents}</div><div class="stat-label">Total Contents</div></div>
                <div class="stat-card"><div class="stat-number">${s.total_categories}</div><div class="stat-label">Categories</div></div>
                <div class="stat-card"><div class="stat-number">${s.total_moderators}</div><div class="stat-label">Moderators</div></div>
                <div class="stat-card"><div class="stat-number">${s.pending_requests}</div><div class="stat-label">Pending Requests</div></div>
            `;
        })
        .catch(err => console.error('loadStats error:', err));
}

// ── Moderators ────────────────────────────────────────────────────────────────
function loadModerators() {
    fetch('../controller/admin_controller.php?action=getModerators')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('modTableBody');
            if (!data.success || !(data.moderators || []).length) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;">No moderators found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.moderators.map(m => `
                <tr>
                    <td>${escapeHtml(m.name)}</td>
                    <td>${escapeHtml(m.email)}</td>
                    <td>${escapeHtml(m.created_at)}</td>
                    <td>
                        <button class="btn btn-danger btn-sm" onclick="deleteModerator(${m.id}, '${escapeHtml(m.name)}')">🗑 Delete</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(err => {
            console.error('loadModerators error:', err);
            const tbody = document.getElementById('modTableBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:red;">Failed to load. Check console.</td></tr>';
        });
}

// ── Pending Moderator Approvals ───────────────────────────────────────────────
function loadPending() {
    fetch('../controller/admin_controller.php?action=getPendingModerators')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('pendingTableBody');
            if (!data.success || !(data.pending || []).length) {
                tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:#888;">No pending registrations.</td></tr>';
                return;
            }
            tbody.innerHTML = data.pending.map(m => `
                <tr>
                    <td>${escapeHtml(m.name)}</td>
                    <td>${escapeHtml(m.email)}</td>
                    <td>${escapeHtml(m.created_at)}</td>
                    <td>
                        <button class="btn btn-success btn-sm" onclick="approveMod(${m.id}, '${escapeHtml(m.name)}')">✔ Approve</button>
                        <button class="btn btn-danger btn-sm"  onclick="declineMod(${m.id}, '${escapeHtml(m.name)}')">✘ Decline</button>
                    </td>
                </tr>
            `).join('');
        })
        .catch(err => {
            console.error('loadPending error:', err);
            const tbody = document.getElementById('pendingTableBody');
            if (tbody) tbody.innerHTML = '<tr><td colspan="4" style="text-align:center;color:red;">Failed to load. Check console.</td></tr>';
        });
}

function approveMod(id, name) {
    if (!confirm(`Approve "${name}" as a moderator?`)) return;
    const fd = new FormData();
    fd.append('action', 'approveModerator');
    fd.append('id', id);
    fetch('../controller/admin_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('modFlash', `${name} has been approved as a moderator.`, 'success');
                loadPending();
                loadModerators();
                loadStats();
            } else {
                showFlash('modFlash', data.error || 'Approval failed.', 'error');
            }
        });
}

function declineMod(id, name) {
    if (!confirm(`Decline and remove the registration request from "${name}"?`)) return;
    const fd = new FormData();
    fd.append('action', 'declineModerator');
    fd.append('id', id);
    fetch('../controller/admin_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('modFlash', `Registration request from ${name} has been declined.`, 'info');
                loadPending();
                loadStats();
            } else {
                showFlash('modFlash', data.error || 'Decline failed.', 'error');
            }
        });
}

function deleteModerator(id, name) {
    if (!confirm(`Delete moderator "${name}"? Their uploaded contents will be kept but unassigned.`)) return;
    const fd = new FormData();
    fd.append('action', 'deleteModerator');
    fd.append('id', id);
    fetch('../controller/admin_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('modFlash', 'Moderator deleted.', 'success');
                loadModerators();
                loadStats();
            } else {
                showFlash('modFlash', data.error || 'Delete failed.', 'error');
            }
        });
}

// ── Categories dropdown ───────────────────────────────────────────────────────
function loadCategoryDropdowns() {
    fetch('../controller/admin_controller.php?action=getCategories')
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;
            const opts = data.categories.map(c =>
                `<option value="${c.id}">${c.parent_name ? escapeHtml(c.parent_name) + ' → ' : ''}${escapeHtml(c.name)}</option>`
            ).join('');
            ['ctCategory', 'editCtCategory'].forEach(id => {
                const el = document.getElementById(id);
                if (el) el.innerHTML = '<option value="">— Select —</option>' + opts;
            });
        });
}

// ── Contents ──────────────────────────────────────────────────────────────────
function loadContents() {
    fetch('../controller/admin_controller.php?action=getContents')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('contentTableBody');
            if (!data.success || !data.contents.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No contents found. Upload the first one!</td></tr>';
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
                        <button class="btn btn-warning btn-sm" onclick="editContent(${c.id})">✏️ Edit</button>
                        <button class="btn btn-danger btn-sm"  onclick="deleteContent(${c.id}, '${escapeHtml(c.title)}')">🗑</button>
                    </td>
                </tr>
            `).join('');
        });
}

function uploadContent() {
    clearErrors('errCtTitle','errCtCat','errCtFile');
    const title       = document.getElementById('ctTitle').value.trim();
    const description = document.getElementById('ctDescription').value.trim();
    const categoryId  = document.getElementById('ctCategory').value;
    const file        = document.getElementById('ctFile').files[0];

    let valid = true;
    if (!title)      { showError('errCtTitle'); valid = false; }
    if (!categoryId) { showError('errCtCat');   valid = false; }
    if (!file)       { showError('errCtFile');  valid = false; }
    if (!valid) return;

    const fd = new FormData();
    fd.append('action',       'addContent');
    fd.append('title',        title);
    fd.append('description',  description);
    fd.append('category_id',  categoryId);
    fd.append('content_file', file);

    showFlash('contentFlash', 'Uploading…', 'info');

    fetch('../controller/admin_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('contentFlash', 'Content uploaded successfully!', 'success');
                loadContents();
                loadStats();
                document.getElementById('ctTitle').value = '';
                document.getElementById('ctDescription').value = '';
                document.getElementById('ctCategory').value = '';
                document.getElementById('ctFile').value = '';
            } else {
                showFlash('contentFlash', data.error || 'Upload failed.', 'error');
            }
        })
        .catch(() => showFlash('contentFlash', 'Network error.', 'error'));
}

function editContent(id) {
    fetch(`../controller/admin_controller.php?action=getContentById&id=${id}`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) { alert('Failed to load content.'); return; }
            const c = data.content;
            document.getElementById('editCtId').value          = c.id;
            document.getElementById('editCtTitle').value       = c.title;
            document.getElementById('editCtDescription').value = c.description || '';
            document.getElementById('editCtCategory').value    = c.category_id;
            const panel = document.getElementById('editContentPanel');
            panel.style.display = 'block';
            panel.scrollIntoView({ behavior: 'smooth' });
        });
}

function saveEditContent() {
    const id          = document.getElementById('editCtId').value;
    const title       = document.getElementById('editCtTitle').value.trim();
    const description = document.getElementById('editCtDescription').value.trim();
    const categoryId  = document.getElementById('editCtCategory').value;
    const file        = document.getElementById('editCtFile').files[0];

    if (!title || !categoryId) { alert('Title and category are required.'); return; }

    const fd = new FormData();
    fd.append('action',      'updateContent');
    fd.append('id',          id);
    fd.append('title',       title);
    fd.append('description', description);
    fd.append('category_id', categoryId);
    if (file) fd.append('content_file', file);

    fetch('../controller/admin_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('contentFlash', 'Content updated!', 'success');
                document.getElementById('editContentPanel').style.display = 'none';
                loadContents();
            } else {
                showFlash('contentFlash', data.error || 'Update failed.', 'error');
            }
        });
}

function deleteContent(id, title) {
    if (!confirm(`Delete "${title}"? This will permanently remove the file.`)) return;
    const fd = new FormData();
    fd.append('action', 'deleteContent');
    fd.append('id', id);
    fetch('../controller/admin_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('contentFlash', 'Content deleted.', 'success');
                loadContents();
                loadStats();
            } else {
                showFlash('contentFlash', data.error || 'Delete failed.', 'error');
            }
        });
}

// ── Requests ──────────────────────────────────────────────────────────────────
function loadRequests() {
    fetch('../controller/admin_controller.php?action=getRequests')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('requestsTableBody');
            if (!data.success || !data.requests.length) {
                tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">No requests yet.</td></tr>';
                return;
            }
            tbody.innerHTML = data.requests.map(r => `
                <tr>
                    <td>${escapeHtml(r.content_title)}</td>
                    <td>${escapeHtml(r.category_requested || '—')}</td>
                    <td>${escapeHtml(r.message || '—')}</td>
                    <td><span class="status-badge status-${r.status}">${r.status}</span></td>
                    <td>${escapeHtml(r.created_at)}</td>
                </tr>
            `).join('');
        });
}

// ── Add Moderator by Admin ───────────────────────────────────────────────────
function addModerator() {
    const name     = document.getElementById('newModName')?.value.trim()    || '';
    const email    = document.getElementById('newModEmail')?.value.trim()   || '';
    const password = document.getElementById('newModPassword')?.value       || '';
    const confirm  = document.getElementById('newModConfirm')?.value        || '';

    // Clear errors
    ['errNewModName','errNewModEmail','errNewModPassword','errNewModConfirm'].forEach(id => showError(id, false));

    let valid = true;
    if (!name)     { showError('errNewModName', true);     valid = false; }
    if (!email)    { showError('errNewModEmail', true);    valid = false; }
    if (!password) { showError('errNewModPassword', true); valid = false; }
    if (password && password !== confirm) { showError('errNewModConfirm', true); valid = false; }
    if (!valid) return;

    const fd = new FormData();
    fd.append('action',   'addModerator');
    fd.append('name',     name);
    fd.append('email',    email);
    fd.append('password', password);
    fd.append('confirm',  confirm);

    fetch('../controller/admin_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('modFlash', `Moderator "${name}" created successfully.`, 'success');
                document.getElementById('newModName').value     = '';
                document.getElementById('newModEmail').value    = '';
                document.getElementById('newModPassword').value = '';
                document.getElementById('newModConfirm').value  = '';
                loadModerators();
                loadStats();
            } else {
                showFlash('modFlash', data.error || 'Failed to create moderator.', 'error');
            }
        })
        .catch(err => { console.error('addModerator error:', err); showFlash('modFlash', 'Network error.', 'error'); });
}

// ── Confirm & Execute Request Actions (Admin) ────────────────────────────────
function confirmAdminRequestAction(id, status, message) {
    if (!confirm(message)) return;
    updateAdminRequestStatus(id, status);
}

function confirmDeleteAdminRequest(id, title) {
    if (!confirm(`Permanently delete the request "${title}"? This cannot be undone.`)) return;
    deleteAdminRequest(id);
}

function deleteAdminRequest(id) {
    const fd = new FormData();
    fd.append('action', 'deleteRequest');
    fd.append('id',     id);
    fetch('../controller/admin_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('adminReqFlash', 'Request deleted.', 'success');
                loadAdminRequests();
                loadStats();
            } else {
                showFlash('adminReqFlash', data.error || 'Delete failed.', 'error');
            }
        })
        .catch(() => showFlash('adminReqFlash', 'Network error.', 'error'));
}

function updateAdminRequestStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'updateRequestStatus');
    fd.append('id', id);
    fd.append('status', status);

    fetch('../controller/admin_controller.php', { 
        method: 'POST', 
        body: fd 
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            showFlash('adminReqFlash', `Request marked as "${status}".`, 'success');
            loadAdminRequests();
        } else {
            showFlash('adminReqFlash', data.error || 'Failed to update status.', 'error');
        }
    })
    .catch(() => showFlash('adminReqFlash', 'Network error.', 'error'));
}

// ── Load Requests for Admin ───────────────────────────────────────────
function loadAdminRequests() {
    fetch('../controller/admin_controller.php?action=getRequests')
        .then(r => r.json())
        .then(data => {
            const tbody = document.getElementById('requestsTableBody');
            if (!data.success || !data.requests.length) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;">No requests found.</td></tr>';
                return;
            }
            tbody.innerHTML = data.requests.map(req => `
                <tr>
                    <td>${escapeHtml(req.content_title)}</td>
                    <td>${escapeHtml(req.category_requested || '—')}</td>
                    <td>${escapeHtml(req.message || '—')}</td>
                    <td><span class="status-badge status-${req.status}">${req.status}</span></td>
                    <td>${escapeHtml(req.created_at)}</td>
                    <td>
                        <button class="btn btn-success btn-sm"   onclick="confirmAdminRequestAction(${req.id}, 'fulfilled', 'Mark this request as fulfilled?')">✔ Fulfill</button>
                        <button class="btn btn-danger btn-sm"    onclick="confirmAdminRequestAction(${req.id}, 'rejected',  'Reject this request?')">✘ Reject</button>
                        <button class="btn btn-secondary btn-sm" onclick="confirmAdminRequestAction(${req.id}, 'pending',   'Reset this request back to pending?')">↩ Reset</button>
                        <button class="btn btn-dark btn-sm"      onclick="confirmDeleteAdminRequest(${req.id}, '${escapeHtml(req.content_title)}')">🗑 Delete</button>
                    </td>
                </tr>
            `).join('');
        });
}

// ── Init ──────────────────────────────────────────────────────────────────────
window.onload = function () {
    loadStats();
    loadPending();
    loadModerators();
    loadCategoryDropdowns();
    loadContents();
    loadRequests();
    loadAdminRequests();
};
