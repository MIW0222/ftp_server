// task1/js/auth.js

// ── Utility ──────────────────────────────────────────────────────────────────
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = String(text);
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
    if (!el) return;
    el.classList.toggle('show', show);
}

function clearErrors(...ids) {
    ids.forEach(id => showError(id, false));
}

// ── Register ──────────────────────────────────────────────────────────────────
function submitRegister() {
    clearErrors('errName','errEmail','errRole','errPassword','errConfirm');

    const name     = document.getElementById('regName').value.trim();
    const email    = document.getElementById('regEmail').value.trim();
    const role     = document.getElementById('regRole').value;
    const password = document.getElementById('regPassword').value;
    const confirm  = document.getElementById('regConfirm').value;
    const picture  = document.getElementById('regPicture').files[0];

    let valid = true;
    if (!name)                                  { showError('errName');     valid = false; }
    if (!email || !/\S+@\S+\.\S+/.test(email)) { showError('errEmail');    valid = false; }
    if (!role)                                  { showError('errRole');     valid = false; }
    if (password.length < 8)                    { showError('errPassword'); valid = false; }
    if (password !== confirm)                   { showError('errConfirm');  valid = false; }
    if (!valid) return;

    const fd = new FormData();
    fd.append('action',           'register');
    fd.append('name',             name);
    fd.append('email',            email);
    fd.append('role',             role);
    fd.append('password',         password);
    fd.append('confirm_password', confirm);
    if (picture) fd.append('profile_picture', picture);

    fetch('../controller/auth_controller.php', { method: 'POST', body: fd })
        .then(r => {
            if (!r.ok) {
                return r.text().then(t => { throw new Error('HTTP ' + r.status + ': ' + t.substring(0, 200)); });
            }
            return r.text().then(t => {
                try {
                    return JSON.parse(t);
                } catch(e) {
                    throw new Error('Bad JSON: ' + t.substring(0, 300));
                }
            });
        })
        .then(data => {
            if (data.success && data.pending) {
                showFlash('flashMsg', data.message, 'info');
                document.getElementById('regName').value     = '';
                document.getElementById('regEmail').value    = '';
                document.getElementById('regPassword').value = '';
                document.getElementById('regConfirm').value  = '';
            } else if (data.success) {
                showFlash('flashMsg', 'Registration successful! Redirecting to login…', 'success');
                setTimeout(() => { window.location.href = 'login.php'; }, 2000);
            } else {
                showFlash('flashMsg', data.error || 'Registration failed.', 'error');
            }
        })
        .catch(err => showFlash('flashMsg', err.message, 'error'));
}

// ── Login ─────────────────────────────────────────────────────────────────────
function submitLogin() {
    clearErrors('errLoginEmail','errLoginPassword');

    const email    = document.getElementById('loginEmail').value.trim();
    const password = document.getElementById('loginPassword').value;
    const remember = document.getElementById('rememberMe')?.checked || false;

    let valid = true;
    if (!email)    { showError('errLoginEmail');    valid = false; }
    if (!password) { showError('errLoginPassword'); valid = false; }
    if (!valid) return;

    const fd = new FormData();
    fd.append('action',   'login');
    fd.append('email',    email);
    fd.append('password', password);
    if (remember) fd.append('remember_me', '1');

    fetch('../controller/auth_controller.php', { method: 'POST', body: fd })
        .then(r => r.text().then(t => {
            try { return JSON.parse(t); }
            catch(e) { throw new Error('Bad JSON: ' + t.substring(0, 300)); }
        }))
        .then(data => {
            if (data.success) {
                showFlash('flashMsg', 'Login successful! Redirecting…', 'success');
                setTimeout(() => { window.location.href = data.redirect; }, 1200);
            } else {
                showFlash('flashMsg', data.error || 'Login failed.', 'error');
            }
        })
        .catch(err => showFlash('flashMsg', err.message, 'error'));
}

// ── Logout ────────────────────────────────────────────────────────────────────
function doLogout() {
    if (!confirm('Logout?')) return;
    const fd = new FormData();
    fd.append('action', 'logout');
    fetch('../../task1/controller/auth_controller.php', { method: 'POST', body: fd })  // ← Problematic
        .then(() => { window.location.href = '../../task1/views/login.php'; });
}

// ── Update Profile ────────────────────────────────────────────────────────────
function submitUpdateProfile() {
    clearErrors('errProfName','errProfEmail');

    const name    = document.getElementById('profName').value.trim();
    const email   = document.getElementById('profEmail').value.trim();
    const picture = document.getElementById('profPicture')?.files[0];

    let valid = true;
    if (!name)                                { showError('errProfName');  valid = false; }
    if (!email || !/\S+@\S+\.\S+/.test(email)) { showError('errProfEmail'); valid = false; }
    if (!valid) return;

    const fd = new FormData();
    fd.append('action', 'updateProfile');
    fd.append('name',   name);
    fd.append('email',  email);
    if (picture) fd.append('profile_picture', picture);

    fetch('../controller/auth_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('profileFlash', 'Profile updated successfully!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showFlash('profileFlash', data.error || 'Update failed.', 'error');
            }
        })
        .catch(() => showFlash('profileFlash', 'Network error.', 'error'));
}

// ── Change Password ───────────────────────────────────────────────────────────
function submitChangePassword() {
    clearErrors('errCurPw','errNewPw','errConfirmNewPw');

    const current = document.getElementById('curPassword').value;
    const newPw   = document.getElementById('newPassword').value;
    const confirm = document.getElementById('confirmNewPw').value;

    let valid = true;
    if (!current)          { showError('errCurPw');         valid = false; }
    if (newPw.length < 8)  { showError('errNewPw');         valid = false; }
    if (newPw !== confirm) { showError('errConfirmNewPw');   valid = false; }
    if (!valid) return;

    const fd = new FormData();
    fd.append('action',           'changePassword');
    fd.append('current_password', current);
    fd.append('new_password',     newPw);
    fd.append('confirm_new',      confirm);

    fetch('../controller/auth_controller.php', { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('pwFlash', 'Password changed successfully!', 'success');
                document.getElementById('curPassword').value  = '';
                document.getElementById('newPassword').value  = '';
                document.getElementById('confirmNewPw').value = '';
            } else {
                showFlash('pwFlash', data.error || 'Password change failed.', 'error');
            }
        })
        .catch(() => showFlash('pwFlash', 'Network error.', 'error'));
}

function loadHighlighted(el) {
    document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');
    fetch('../controller/auth_controller.php?action=getHighlighted')
        .then(r => r.json())
        .then(data => {
            renderContentGrid(data.contents || []);
        });
}

function loadByCategory(catId, el) {
    document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');
    // Load sub-categories
    fetch(`../controller/auth_controller.php?action=getSubCategories&parent_id=${catId}`)
        .then(r => r.json())
        .then(data => {
            const subSel = document.getElementById('filterSubCategory');
            if (subSel) {
                subSel.innerHTML = '<option value="">All Sub-categories</option>';
                (data.subcategories || []).forEach(s => {
                    subSel.innerHTML += `<option value="${s.id}">${escapeHtml(s.name)}</option>`;
                });
            }
        });
    fetch(`../controller/auth_controller.php?action=getByCategory&category_id=${catId}`)
        .then(r => r.json())
        .then(data => renderContentGrid(data.contents || []));
}

function renderContentGrid(contents) {
    const grid = document.getElementById('contentGrid');
    if (!grid) return;
    if (!contents.length) {
        grid.innerHTML = '<p style="color:#aaa;text-align:center;padding:40px 0;">No content found.</p>';
        return;
    }
    grid.innerHTML = contents.map(c => `
        <div class="content-card">
            <span class="badge">${escapeHtml(c.category_name || 'Uncategorized')}</span>
            <h4 style="margin-top:8px;">${escapeHtml(c.title)}</h4>
            <p>${escapeHtml((c.description || '').substring(0, 100))}${(c.description || '').length > 100 ? '…' : ''}</p>
            <small style="color:#999;">⬇ ${c.download_count} downloads</small>
            <br>
            <a class="download-btn" href="../../public/${escapeHtml(c.file_path)}" download onclick="incrementDownload(${c.id})">⬇ Download</a>
        </div>
    `).join('');
}

function incrementDownload(id) {
    // Fires and forgets — Task 4 handles the counter
    fetch(`../../task4/controller/member_controller.php?action=incrementDownload&id=${id}`);
}

window.onload = function () {
    loadCategoryTabs();
    loadHighlighted(null);
};
