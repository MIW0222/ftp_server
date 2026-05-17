// task4/js/member.js
// Used by home.php — handles member-facing search, filter, browse, request box

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

// ── Determine correct base path to task4 controller ──────────────────────────
// home.php lives at task1/views/ → ../../task4/controller/
const MEMBER_API = '../../task4/controller/member_controller.php';

// ── Load Categories ───────────────────────────────────────────────────────────
function loadMemberCategories() {
    fetch(`${MEMBER_API}?action=getCategories`)
        .then(r => r.json())
        .then(data => {
            if (!data.success) return;

            // Category tabs
            const tabsEl = document.getElementById('categoryTabs');
            if (tabsEl) {
                tabsEl.innerHTML = `<span class="category-tab active" data-id="0" onclick="browseAll(this)">🌐 All</span>`;
                data.categories.forEach(cat => {
                    tabsEl.innerHTML += `<span class="category-tab" data-id="${cat.id}" onclick="browseCategory(${cat.id}, this)">${escapeHtml(cat.name)}</span>`;
                });
            }

            // Filter dropdowns
            const filterCat = document.getElementById('filterCategory');
            const reqCat    = document.getElementById('reqCategory');
            data.categories.forEach(cat => {
                if (filterCat) filterCat.innerHTML += `<option value="${cat.id}">${escapeHtml(cat.name)}</option>`;
                if (reqCat)    reqCat.innerHTML    += `<option value="${escapeHtml(cat.name)}">${escapeHtml(cat.name)}</option>`;
            });

            // Load initial content
            browseAll(document.querySelector('.category-tab'));
        })
        .catch(err => console.error('Category load error:', err));
}

// ── Browse All ────────────────────────────────────────────────────────────────
function browseAll(el) {
    setActiveTab(el);
    clearSubCategories();
    doSearch('', null, null);
}

// ── Browse by Category ────────────────────────────────────────────────────────
function browseCategory(catId, el) {
    setActiveTab(el);
    // Load sub-categories for this category
    fetch(`${MEMBER_API}?action=getSubCategories&parent_id=${catId}`)
        .then(r => r.json())
        .then(data => {
            const subSel = document.getElementById('filterSubCategory');
            if (subSel) {
                subSel.innerHTML = '<option value="">All Sub-categories</option>';
                if (data.subcategories && data.subcategories.length) {
                    data.subcategories.forEach(s => {
                        subSel.innerHTML += `<option value="${s.id}">${escapeHtml(s.name)}</option>`;
                    });
                }
            }
            // Also update category filter dropdown
            const filterCat = document.getElementById('filterCategory');
            if (filterCat) filterCat.value = catId;
        });
    doSearch('', catId, null);
}

function clearSubCategories() {
    const subSel = document.getElementById('filterSubCategory');
    if (subSel) subSel.innerHTML = '<option value="">All Sub-categories</option>';
}

function setActiveTab(el) {
    document.querySelectorAll('.category-tab').forEach(t => t.classList.remove('active'));
    if (el) el.classList.add('active');
}

// ── Live Search (AJAX GET /api/contents/search) ───────────────────────────────
let searchDebounce = null;

function liveSearch() {
    clearTimeout(searchDebounce);
    searchDebounce = setTimeout(() => {
        const q      = document.getElementById('searchInput')?.value.trim()       || '';
        const catId  = document.getElementById('filterCategory')?.value           || null;
        const subId  = document.getElementById('filterSubCategory')?.value        || null;
        doSearch(q, catId, subId);
    }, 350);
}

function doSearch(q = '', categoryId = null, subCategoryId = null) {
    let url = `${MEMBER_API}?action=search&q=${encodeURIComponent(q)}`;
    if (categoryId)    url += `&category_id=${categoryId}`;
    if (subCategoryId) url += `&sub_category_id=${subCategoryId}`;

    fetch(url)
        .then(r => r.json())
        .then(data => {
            renderContentGrid(data.contents || []);
        })
        .catch(err => {
            console.error('Search error:', err);
            renderContentGrid([]);
        });
}

// ── Render Content Grid ───────────────────────────────────────────────────────
function renderContentGrid(contents) {
    const grid = document.getElementById('contentGrid');
    if (!grid) return;

    if (!contents.length) {
        grid.innerHTML = `
            <div style="grid-column:1/-1;text-align:center;padding:50px 20px;color:#aaa;">
                <div style="font-size:48px;margin-bottom:16px;">📂</div>
                <p style="font-size:16px;">No content found. Try a different search or category.</p>
            </div>`;
        return;
    }

    grid.innerHTML = contents.map(c => `
        <div class="content-card">
            <span class="badge">${escapeHtml(c.category_name || 'Uncategorized')}</span>
            <h4 style="margin-top:8px;">${escapeHtml(c.title)}</h4>
            <p>${escapeHtml((c.description || 'No description available.').substring(0, 120))}${(c.description || '').length > 120 ? '…' : ''}</p>
            <small style="color:#999;">⬇ ${c.download_count} downloads &nbsp;|&nbsp; 📅 ${escapeHtml(c.uploaded_at.split(' ')[0])}</small>
            <br>
            <a class="download-btn"
               href="../../public/${escapeHtml(c.file_path)}"
               download
               onclick="trackDownload(${c.id})">
                ⬇ Download
            </a>
        </div>
    `).join('');
}

// ── Track Download Count ──────────────────────────────────────────────────────
function trackDownload(id) {
    fetch(`${MEMBER_API}?action=incrementDownload&id=${id}`).catch(() => {});
}

// ── Load Sub-categories for Request Box ───────────────────────────────────────
function loadReqSubCategories() {
    const reqCat   = document.getElementById('reqCategory');
    const group    = document.getElementById('reqSubCategoryGroup');
    const subSel   = document.getElementById('reqSubCategory');
    const catId    = reqCat ? reqCat.value : '';

    if (!catId || !group || !subSel) { if (group) group.style.display = 'none'; return; }

    fetch(`${MEMBER_API}?action=getSubCategories&parent_id=${catId}`)
        .then(r => r.json())
        .then(data => {
            subSel.innerHTML = '<option value="">— Select Sub-category —</option>';
            if (data.subcategories && data.subcategories.length) {
                data.subcategories.forEach(s => {
                    subSel.innerHTML += `<option value="${escapeHtml(s.name)}">${escapeHtml(s.name)}</option>`;
                });
                group.style.display = '';
            } else {
                group.style.display = 'none';
            }
        })
        .catch(() => { group.style.display = 'none'; });
}

// ── Submit Content Request (AJAX POST /api/requests/add) ─────────────────────
function submitRequest() {
    showError('errReqTitle', false);

    const title       = document.getElementById('reqTitle')?.value.trim()    || '';
    const category    = document.getElementById('reqCategory')?.value        || '';
    const subCategory = document.getElementById('reqSubCategory')?.value     || '';
    const message     = document.getElementById('reqMessage')?.value.trim()  || '';

    // JS validation
    if (!title) {
        showError('errReqTitle', true);
        document.getElementById('reqTitle').focus();
        return;
    }
    if (title.length > 255) {
        showFlash('requestFlash', 'Title is too long (max 255 characters).', 'error');
        return;
    }

    const categoryLabel = subCategory
        ? `${category} > ${subCategory}`
        : category;

    const fd = new FormData();
    fd.append('action',             'addRequest');
    fd.append('content_title',      title);
    fd.append('category_requested', categoryLabel);
    fd.append('message',            message);

    fetch(`${MEMBER_API}`, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showFlash('requestFlash', '✅ Your request has been submitted! Our team will review it.', 'success');
                document.getElementById('reqTitle').value   = '';
                document.getElementById('reqCategory').value = '';
                const subSel = document.getElementById('reqSubCategory');
                if (subSel) subSel.value = '';
                const subGroup = document.getElementById('reqSubCategoryGroup');
                if (subGroup) subGroup.style.display = 'none';
                document.getElementById('reqMessage').value = '';
            } else {
                showFlash('requestFlash', data.error || 'Request submission failed.', 'error');
            }
        })
        .catch(() => showFlash('requestFlash', 'Network error. Please try again.', 'error'));
}

// ── Init ──────────────────────────────────────────────────────────────────────
window.addEventListener('DOMContentLoaded', () => {
    loadMemberCategories();
});
