// MultiApp - Main JavaScript

// ── Toast Notifications ──────────────────────────────────────────
function toast(message, type = 'success', duration = 3500) {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    container.className = 'toast-container';
    document.body.appendChild(container);
  }

  const t = document.createElement('div');
  t.className = `toast ${type}`;
  const icon = type === 'success' ? '✓' : '✗';
  t.innerHTML = `<span style="font-size:16px">${icon}</span> ${message}`;
  container.appendChild(t);

  setTimeout(() => {
    t.style.animation = 'slideIn 0.2s ease reverse';
    setTimeout(() => t.remove(), 200);
  }, duration);
}

// ── Modal ──────────────────────────────────────────────────────
function openModal(modalId) {
  const m = document.getElementById(modalId);
  if (m) { m.style.display = 'flex'; document.body.style.overflow = 'hidden'; }
}
function closeModal(modalId) {
  const m = document.getElementById(modalId);
  if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
}

// Close on overlay click
document.addEventListener('click', function(e) {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.style.display = 'none';
    document.body.style.overflow = '';
  }
});

// ── AJAX Helpers ──────────────────────────────────────────────
async function apiPost(url, formData) {
  try {
    const res = await fetch(url, { method: 'POST', body: formData });
    return await res.json();
  } catch (e) {
    return { success: false, error: e.message };
  }
}

async function apiGet(url) {
  try {
    const res = await fetch(url);
    return await res.json();
  } catch (e) {
    return { error: e.message };
  }
}

async function apiDelete(url) {
  try {
    const fd = new FormData();
    fd.append('_method', 'DELETE');
    const res = await fetch(url, { method: 'POST', body: fd });
    return await res.json();
  } catch (e) {
    return { success: false, error: e.message };
  }
}

// ── File Upload Dropzone ──────────────────────────────────────
function initDropzone(zoneEl, inputEl, previewEl) {
  zoneEl.addEventListener('click', () => inputEl.click());
  zoneEl.addEventListener('dragover', e => { e.preventDefault(); zoneEl.style.opacity = '0.7'; });
  zoneEl.addEventListener('dragleave', () => { zoneEl.style.opacity = '1'; });
  zoneEl.addEventListener('drop', e => {
    e.preventDefault();
    zoneEl.style.opacity = '1';
    if (e.dataTransfer.files[0]) {
      inputEl.files = e.dataTransfer.files;
      showFilePreview(inputEl.files[0], previewEl);
    }
  });
  inputEl.addEventListener('change', () => {
    if (inputEl.files[0]) showFilePreview(inputEl.files[0], previewEl);
  });
}

function showFilePreview(file, previewEl) {
  if (!previewEl) return;
  const size = file.size > 1048576 ? (file.size / 1048576).toFixed(2) + ' MB' : (file.size / 1024).toFixed(0) + ' KB';
  previewEl.innerHTML = `<span>📄</span> ${file.name} <span class="text-muted">(${size})</span>`;
  previewEl.style.display = 'flex';
}

// ── Dropdown Toggle ──────────────────────────────────────────
document.addEventListener('click', function(e) {
  // Close all dropdowns when clicking outside
  document.querySelectorAll('.dropdown-menu.open').forEach(m => {
    if (!m.closest('.dropdown').contains(e.target)) {
      m.classList.remove('open');
      m.style.display = 'none';
    }
  });
});

function toggleDropdown(menuId) {
  const menu = document.getElementById(menuId);
  if (!menu) return;
  const isOpen = menu.style.display === 'block';
  // Close all others
  document.querySelectorAll('.dropdown-menu').forEach(m => { m.style.display = 'none'; });
  menu.style.display = isOpen ? 'none' : 'block';
}

// ── Confirm Delete ────────────────────────────────────────────
function confirmDelete(message = 'Confirmer la suppression ?') {
  return new Promise(resolve => {
    if (confirm(message)) resolve(true);
    else resolve(false);
  });
}

// ── Catalog Reorder (drag and drop) ──────────────────────────
let dragSrc = null;
function initDragDrop(container) {
  if (!container) return;
  container.querySelectorAll('[draggable]').forEach(item => {
    item.addEventListener('dragstart', e => { dragSrc = item; e.dataTransfer.effectAllowed = 'move'; });
    item.addEventListener('dragover', e => { e.preventDefault(); e.dataTransfer.dropEffect = 'move'; item.style.opacity = '0.5'; });
    item.addEventListener('dragleave', () => { item.style.opacity = '1'; });
    item.addEventListener('drop', e => {
      e.preventDefault();
      item.style.opacity = '1';
      if (dragSrc !== item) {
        // Swap
        const parent = item.parentNode;
        const srcIdx = [...parent.children].indexOf(dragSrc);
        const dstIdx = [...parent.children].indexOf(item);
        if (srcIdx < dstIdx) parent.insertBefore(dragSrc, item.nextSibling);
        else parent.insertBefore(dragSrc, item);
        saveReorder(parent);
      }
    });
  });
}

async function saveReorder(container) {
  const items = [...container.querySelectorAll('[data-id]')].map((el, i) => ({
    id: el.dataset.id,
    type: el.dataset.type || 'product',
    name: el.dataset.name || '',
    position: i + 1
  }));
  await fetch('/manage/catalog/reorder', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ items })
  });
}

// ── Stats Chart ──────────────────────────────────────────────
function initStatsChart(data) {
  const canvas = document.getElementById('clicksChart');
  if (!canvas || !window.Chart) return;

  const labels = data.map(d => d.day);
  const values = data.map(d => parseInt(d.count));

  new Chart(canvas, {
    type: 'line',
    data: {
      labels,
      datasets: [{
        label: 'Clics',
        data: values,
        borderColor: '#6b6ef9',
        backgroundColor: 'rgba(107, 110, 249, 0.1)',
        fill: true,
        tension: 0.4,
        pointBackgroundColor: '#6b6ef9',
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a3c4', font: { size: 11 } } },
        y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#a0a3c4', font: { size: 11 } }, beginAtZero: true }
      }
    }
  });
}

// ── Color Picker ─────────────────────────────────────────────
function initColorPicker() {
  const picker = document.getElementById('colorPicker');
  const preview = document.getElementById('colorPreview');
  if (!picker || !preview) return;
  picker.addEventListener('input', () => { preview.style.background = picker.value; });
}

// ── Media Picker ──────────────────────────────────────────────
function setFileInput(inputEl, file) {
  try {
    const dt = new DataTransfer();
    dt.items.add(file);
    inputEl.files = dt.files;
  } catch(e) {}
}

function showLibraryPreview(media, previewEl) {
  if (!previewEl) return;
  const icons = { image: '🖼️', video: '🎬', pdf: '📄' };
  const icon  = icons[media.file_type] || '📄';
  previewEl.innerHTML = `<span>${icon}</span> ${media.original_name} <span class="text-muted">(Bibliothèque)</span>`;
  previewEl.style.display = 'flex';
}

const MediaPicker = (() => {
  let _cb     = null;
  let _built  = false;
  let _curType = 'all';

  function _acceptToTypes(accept) {
    const t = [];
    if (accept.includes('image'))       t.push('image');
    if (accept.includes('video'))       t.push('video');
    if (accept.includes('pdf'))         t.push('pdf');
    return t.length ? t : ['image', 'video', 'pdf'];
  }

  function _build() {
    _built = true;
    const el = document.createElement('div');
    el.id = '_mpModal';
    el.className = 'modal-overlay';
    el.style.display = 'none';
    el.innerHTML = `
      <div class="modal" style="max-width:720px;width:95vw;padding:0;overflow:hidden;">
        <div class="modal-header" style="padding:16px 20px;">
          <div class="modal-title">Sélectionner un média</div>
          <button class="modal-close" onclick="MediaPicker.close()">×</button>
        </div>
        <div class="mp-tabs">
          <button class="mp-tab mp-tab-active" id="_mpTabUpload" onclick="MediaPicker.showTab('upload')">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
            Ajouter un fichier
          </button>
          <button class="mp-tab" id="_mpTabLib" onclick="MediaPicker.showTab('library')">
            <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
            Bibliothèque
          </button>
        </div>
        <div id="_mpUploadPanel" style="padding:20px;">
          <div class="upload-zone" id="_mpDropZone" style="cursor:pointer;margin:0;">
            <input type="file" id="_mpFileInput" style="display:none;">
            AJOUTER UN FICHIER<br>
            <span style="font-weight:400;font-size:11px;">OU GLISSER DÉPOSER</span>
          </div>
        </div>
        <div id="_mpLibPanel" style="display:none;padding:16px 20px 20px;">
          <div id="_mpLibFilters" style="display:flex;gap:6px;margin-bottom:14px;flex-wrap:wrap;"></div>
          <div id="_mpLibGrid" class="mp-lib-grid"></div>
        </div>
      </div>`;
    document.body.appendChild(el);

    const input = document.getElementById('_mpFileInput');
    const zone  = document.getElementById('_mpDropZone');
    zone.addEventListener('click',    () => input.click());
    zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.opacity = '0.7'; });
    zone.addEventListener('dragleave',() => { zone.style.opacity = '1'; });
    zone.addEventListener('drop',     e => {
      e.preventDefault(); zone.style.opacity = '1';
      if (e.dataTransfer.files[0]) _selectFile(e.dataTransfer.files[0]);
    });
    input.addEventListener('change', () => { if (input.files[0]) _selectFile(input.files[0]); });
    el.addEventListener('click', e => { if (e.target === el) MediaPicker.close(); });
  }

  function _selectFile(file) {
    MediaPicker.close();
    if (_cb) _cb({ type: 'upload', file });
  }

  function _buildFilters(types) {
    const filtersEl = document.getElementById('_mpLibFilters');
    filtersEl.innerHTML = '';
    const opts = [{ key: 'all', label: 'Tout' }];
    if (types.includes('image')) opts.push({ key: 'image', label: '🖼️ Images' });
    if (types.includes('video')) opts.push({ key: 'video', label: '🎬 Vidéos' });
    if (types.includes('pdf'))   opts.push({ key: 'pdf',   label: '📄 PDFs' });
    opts.forEach(opt => {
      const btn = document.createElement('a');
      btn.className = 'sub-tab' + (opt.key === 'all' ? ' active' : '');
      btn.textContent = opt.label;
      btn.style.cursor = 'pointer';
      btn.onclick = () => {
        filtersEl.querySelectorAll('.sub-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        _curType = opt.key;
        _loadLibrary(opt.key);
      };
      filtersEl.appendChild(btn);
    });
  }

  async function _loadLibrary(type) {
    const grid = document.getElementById('_mpLibGrid');
    grid.innerHTML = '<div class="mp-lib-msg">Chargement...</div>';
    const qs   = type !== 'all' ? `?type=${type}` : '';
    const data = await apiGet(`/manage/library/picker${qs}`);

    if (!data.media || !data.media.length) {
      grid.innerHTML = '<div class="mp-lib-msg">Aucun fichier dans la bibliothèque.</div>';
      return;
    }
    grid.innerHTML = '';
    data.media.forEach(m => {
      const item = document.createElement('div');
      item.className = 'mp-lib-item';
      item.title     = m.original_name;
      const thumb    = m.file_type === 'image'
        ? `<img src="/public/uploads/${m.path}" alt="" loading="lazy">`
        : `<div class="mp-lib-item-icon">${m.file_type === 'video' ? '🎬' : '📄'}</div>`;
      item.innerHTML = `<div class="mp-lib-item-thumb">${thumb}</div><div class="mp-lib-item-name">${m.original_name}</div>`;
      item.onclick   = () => { MediaPicker.close(); if (_cb) _cb({ type: 'library', media: m }); };
      grid.appendChild(item);
    });
  }

  function open({ accept = 'image/*,video/mp4,application/pdf', onSelect }) {
    if (!_built) _build();
    _cb      = onSelect;
    _curType = 'all';
    document.getElementById('_mpFileInput').accept = accept;
    _buildFilters(_acceptToTypes(accept));
    MediaPicker.showTab('upload');
    document.getElementById('_mpModal').style.display = 'flex';
    document.body.style.overflow = 'hidden';
  }

  function showTab(tab) {
    const isLib = tab === 'library';
    document.getElementById('_mpTabUpload').className = 'mp-tab' + (isLib ? '' : ' mp-tab-active');
    document.getElementById('_mpTabLib').className    = 'mp-tab' + (isLib ? ' mp-tab-active' : '');
    document.getElementById('_mpUploadPanel').style.display = isLib ? 'none' : 'block';
    document.getElementById('_mpLibPanel').style.display    = isLib ? 'block' : 'none';
    if (isLib) _loadLibrary(_curType);
  }

  function close() {
    const m = document.getElementById('_mpModal');
    if (m) { m.style.display = 'none'; document.body.style.overflow = ''; }
    const inp = document.getElementById('_mpFileInput');
    if (inp) inp.value = '';
  }

  return { open, close, showTab };
})();

// ── Init on DOM Ready ─────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
  initColorPicker();

  // Init all dropzones
  document.querySelectorAll('[data-dropzone]').forEach(zone => {
    const inputId = zone.dataset.dropzone;
    const input = document.getElementById(inputId);
    const previewId = zone.dataset.preview;
    const preview = previewId ? document.getElementById(previewId) : null;
    if (input) initDropzone(zone, input, preview);
  });

  // Close modals on ESC
  document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
      document.querySelectorAll('.modal-overlay').forEach(m => {
        m.style.display = 'none';
        document.body.style.overflow = '';
      });
    }
  });
});
