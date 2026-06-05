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
