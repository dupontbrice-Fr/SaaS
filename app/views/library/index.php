<?php
$typeLabels  = ['image' => 'Images', 'video' => 'Vidéos', 'pdf' => 'PDFs'];
$totalActive = array_sum(array_column($stats, 'count'));
$totalSize   = array_sum(array_column($stats, 'total_size'));
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <h1 class="page-title" style="margin-bottom:0;">Bibliothèques</h1>
  <?php if (!$isArchive): ?>
  <button class="btn btn-primary" onclick="openUploadModal()">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
    IMPORTER UN FICHIER
  </button>
  <?php endif; ?>
</div>

<!-- Stats côte à côte -->
<div class="lib-stats">
  <div class="stat-card lib-stat-card">
    <div class="stat-value"><?= $totalActive ?></div>
    <div class="stat-label">Total fichiers</div>
  </div>
  <?php foreach ($stats as $s): ?>
  <div class="stat-card lib-stat-card">
    <div class="stat-value"><?= $s['count'] ?></div>
    <div class="stat-label"><?= $typeLabels[$s['file_type']] ?? $s['file_type'] ?></div>
  </div>
  <?php endforeach; ?>
  <div class="stat-card lib-stat-card">
    <div class="stat-value" style="font-size:20px;"><?= Upload::formatSize($totalSize) ?></div>
    <div class="stat-label">Espace utilisé</div>
  </div>
  <?php if ($archivedCount > 0): ?>
  <div class="stat-card lib-stat-card">
    <div class="stat-value" style="color:var(--text-muted);"><?= $archivedCount ?></div>
    <div class="stat-label">Archivés</div>
  </div>
  <?php endif; ?>
</div>

<!-- Onglets Bibliothèque / Archives -->
<div class="tabs" style="margin-bottom:16px;">
  <a href="?type=<?= htmlspecialchars($filter) ?>" class="tab <?= !$isArchive ? 'active' : '' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
    Bibliothèque
  </a>
  <?php if (Auth::isAdmin()): ?>
  <a href="?type=<?= htmlspecialchars($filter) ?>&sub=archives" class="tab <?= $isArchive ? 'active' : '' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
    Archives
    <?php if ($archivedCount > 0 && !$isArchive): ?>
    <span style="background:var(--text-muted);color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px;"><?= $archivedCount ?></span>
    <?php endif; ?>
  </a>
  <?php endif; ?>
</div>

<?php if ($isArchive): ?>
<div style="color:var(--text-muted);font-size:13px;margin-bottom:16px;">
  Les fichiers archivés peuvent être restaurés ou supprimés définitivement. Ils ne sont plus comptabilisés dans les statistiques.
</div>
<?php endif; ?>

<!-- Filtres type + recherche + sélection -->
<div style="display:flex;gap:8px;margin-bottom:12px;flex-wrap:wrap;align-items:center;">
  <div style="display:flex;gap:6px;flex-wrap:wrap;">
    <?php $types = ['all' => 'Tout', 'image' => '🖼️ Images', 'video' => '🎬 Vidéos', 'pdf' => '📄 PDFs']; ?>
    <?php foreach ($types as $k => $label): ?>
    <a href="?type=<?= $k ?><?= $isArchive ? '&sub=archives' : '' ?>"
       class="sub-tab <?= ($filter === $k) ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
  <div style="flex:1;"></div>
  <button class="btn btn-secondary btn-sm" onclick="selectAll()" title="Tout sélectionner">☑ Tout</button>
  <form method="GET" style="display:flex;gap:8px;align-items:center;">
    <input type="hidden" name="type" value="<?= htmlspecialchars($filter) ?>">
    <?php if ($isArchive): ?><input type="hidden" name="sub" value="archives"><?php endif; ?>
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-input" placeholder="🔍 Rechercher..." style="width:200px;">
  </form>
</div>

<!-- Grille -->
<?php if (empty($media)): ?>
<div style="text-align:center;padding:60px;color:var(--text-muted);">
  <div style="font-size:48px;margin-bottom:16px;"><?= $isArchive ? '🗂️' : '📂' ?></div>
  <div><?= $isArchive ? 'Aucun fichier archivé.' : 'Aucun fichier dans la bibliothèque.' ?></div>
</div>
<?php else: ?>
<div class="library-grid" id="libGrid">
  <?php foreach ($media as $m):
    $canAct = Auth::isAdmin() || empty($m['admin_protected']);
    $links  = $linkageMap[$m['path']] ?? [];
    $uploadedBy  = $m['uploaded_by'] ?? null;
    $uploadDate  = !empty($m['created_at'])  ? date('d/m/Y', strtotime($m['created_at']))  : '—';
    $archiveDate = !empty($m['archived_at']) ? date('d/m/Y', strtotime($m['archived_at'])) : '—';
  ?>
  <div class="library-item" data-id="<?= $m['id'] ?>">

    <!-- Checkbox sélection -->
    <div class="lib-checkbox-wrap">
      <input type="checkbox" class="lib-checkbox" data-id="<?= $m['id'] ?>"
             onclick="event.stopPropagation();toggleSelection(this)">
    </div>

    <!-- Aperçu -->
    <div class="library-item-preview">
      <?php if ($m['file_type'] === 'image'): ?>
        <img src="/public/uploads/<?= htmlspecialchars($m['path']) ?>" alt="" loading="lazy">
      <?php elseif ($m['file_type'] === 'video'): ?>
        <video src="/public/uploads/<?= htmlspecialchars($m['path']) ?>" muted preload="metadata"></video>
      <?php else: ?>
        <div style="font-size:36px;">📄</div>
      <?php endif; ?>
    </div>

    <!-- Badge protégé -->
    <?php if (!empty($m['admin_protected'])): ?>
    <div class="lib-lock-badge" title="Contenu protégé (admin)">🔒</div>
    <?php endif; ?>

    <!-- Infos -->
    <div class="library-item-info">
      <div class="library-item-name" title="<?= htmlspecialchars($m['original_name']) ?>">
        <?= htmlspecialchars($m['original_name']) ?>
      </div>
      <div class="library-item-meta">
        <?= Upload::formatSize((int)$m['file_size']) ?> ·
        <?= $isArchive ? ('Archivé le ' . $archiveDate) : $uploadDate ?>
      </div>
      <?php if ($uploadedBy): ?>
      <div class="library-item-uploader" title="Importé par <?= htmlspecialchars($uploadedBy) ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        <?= htmlspecialchars($uploadedBy) ?>
      </div>
      <?php endif; ?>
      <div class="library-item-usage">
        <?php if (empty($links)): ?>
          <span class="lib-usage-none">Aucun</span>
        <?php else:
          $shown = array_slice($links, 0, 2);
          foreach ($shown as $lk): ?>
            <span class="lib-usage-tag" title="<?= htmlspecialchars($lk['name']) ?>"><?= htmlspecialchars($lk['type']) ?></span>
          <?php endforeach;
          if (count($links) > 2): ?>
            <span class="lib-usage-tag">+<?= count($links) - 2 ?></span>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Boutons d'action (survol) -->
    <?php if ($canAct): ?>
    <div class="lib-action-btn" style="position:absolute;top:4px;right:4px;display:flex;gap:4px;opacity:0;">
      <?php if (!$isArchive): ?>
      <button class="btn btn-secondary btn-sm btn-icon"
              onclick="event.stopPropagation();archiveMedia(<?= $m['id'] ?>)"
              title="Archiver"
              style="background:rgba(0,0,0,.6);border:none;color:#fff;">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
      </button>
      <?php else: ?>
      <button class="btn btn-sm btn-icon"
              onclick="event.stopPropagation();restoreMedia(<?= $m['id'] ?>)"
              title="Restaurer"
              style="background:rgba(34,197,94,.8);border:none;color:#fff;border-radius:6px;padding:4px 6px;cursor:pointer;">
        ↩
      </button>
      <button class="btn btn-danger btn-sm btn-icon"
              onclick="event.stopPropagation();deleteMedia(<?= $m['id'] ?>)"
              title="Supprimer définitivement">
        ✕
      </button>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalCount > 24): ?>
<div class="pagination">
  Affichage <?= ($page - 1) * 24 + 1 ?>–<?= min($page * 24, $totalCount) ?> sur <?= $totalCount ?>
  <?php if ($page > 1): ?>
    <a href="?type=<?= $filter ?><?= $isArchive ? '&sub=archives' : '' ?>&page=<?= $page - 1 ?>" class="page-btn">‹</a>
  <?php endif; ?>
  <?php if ($page * 24 < $totalCount): ?>
    <a href="?type=<?= $filter ?><?= $isArchive ? '&sub=archives' : '' ?>&page=<?= $page + 1 ?>" class="page-btn">›</a>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Barre d'actions groupées (fixe en bas) -->
<div id="bulkBar">
  <span id="bulkCount" style="font-size:13px;font-weight:600;"></span>
  <button class="btn btn-secondary btn-sm" onclick="clearSelection()">Désélectionner</button>
  <?php if (!$isArchive): ?>
  <button class="btn btn-primary btn-sm" onclick="bulkArchive()">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
    Archiver la sélection
  </button>
  <?php else: ?>
  <button class="btn btn-secondary btn-sm" onclick="bulkRestore()" style="background:rgba(34,197,94,.15);color:#22c55e;border-color:#22c55e;">
    ↩ Restaurer la sélection
  </button>
  <button class="btn btn-danger btn-sm" onclick="bulkDelete()">
    ✕ Supprimer la sélection
  </button>
  <?php endif; ?>
</div>

<!-- Modal upload -->
<?php if (!$isArchive): ?>
<div class="modal-overlay" id="modalUpload" style="display:none;">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <div class="modal-title">Importer un fichier</div>
      <button class="modal-close" onclick="closeModal('modalUpload')">×</button>
    </div>
    <form id="formUpload" enctype="multipart/form-data">
      <div class="upload-zone" data-dropzone="libFile" data-preview="libFilePreview">
        <input type="file" id="libFile" name="file" accept="image/*,video/mp4,application/pdf">
        AJOUTER UN FICHIER<br>
        <span style="font-weight:400;font-size:11px;">Images (20 Mo), Vidéos MP4 (1 Go), PDF (50 Mo)</span>
      </div>
      <div id="libFilePreview" class="upload-zone-preview" style="display:none;"></div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">IMPORTER</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalUpload')">ANNULER</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<style>
/* Stats côte à côte */
.lib-stats { display:flex; flex-wrap:wrap; gap:12px; margin-bottom:24px; }
.lib-stat-card { flex:1 1 140px; min-width:0; }

/* Checkbox sélection */
.lib-checkbox-wrap {
  position: absolute;
  top: 6px;
  left: 6px;
  z-index: 4;
  opacity: 0;
  transition: opacity 0.15s;
}
.library-item:hover .lib-checkbox-wrap,
.library-item.is-selected .lib-checkbox-wrap,
#libGrid.selection-mode .lib-checkbox-wrap { opacity: 1; }
.lib-checkbox { width:17px; height:17px; cursor:pointer; accent-color:var(--accent); }
.library-item.is-selected {
  border-color: var(--accent);
  box-shadow: 0 0 0 2px color-mix(in srgb, var(--accent) 30%, transparent);
}

/* Badge protégé */
.lib-lock-badge {
  position: absolute;
  top: 6px;
  left: 30px;
  background: rgba(0,0,0,.55);
  color: #fff;
  border-radius: 4px;
  padding: 2px 5px;
  font-size: 10px;
  z-index: 3;
}

/* Infos enrichies */
.library-item-meta {
  font-size: 10px;
  color: var(--text-muted);
  margin-top: 3px;
}
.library-item-uploader {
  font-size: 10px;
  color: var(--text-secondary);
  overflow: hidden;
  text-overflow: ellipsis;
  white-space: nowrap;
  margin-top: 2px;
  display: flex;
  align-items: center;
  gap: 3px;
}
.library-item-usage {
  display: flex;
  flex-wrap: wrap;
  gap: 3px;
  margin-top: 5px;
}
.lib-usage-none {
  font-size: 9px;
  color: var(--text-muted);
  font-style: italic;
}
.lib-usage-tag {
  font-size: 9px;
  background: color-mix(in srgb, var(--accent) 15%, transparent);
  color: var(--accent);
  padding: 1px 5px;
  border-radius: 4px;
  font-weight: 600;
  white-space: nowrap;
}

/* Action buttons on hover */
.library-item:hover .lib-action-btn { opacity: 1 !important; }

/* Barre actions groupées */
#bulkBar {
  position: fixed;
  bottom: 20px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 100;
  background: var(--bg-card);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 10px 18px;
  display: none;
  align-items: center;
  gap: 10px;
  box-shadow: 0 8px 32px rgba(0,0,0,.45);
  white-space: nowrap;
}
</style>

<script>
const selectedIds = new Set();

function toggleSelection(checkbox) {
  const id   = parseInt(checkbox.dataset.id);
  const card = checkbox.closest('.library-item');
  if (checkbox.checked) { selectedIds.add(id);    card.classList.add('is-selected'); }
  else                  { selectedIds.delete(id); card.classList.remove('is-selected'); }
  updateBulkBar();
}

function updateBulkBar() {
  const n   = selectedIds.size;
  const bar = document.getElementById('bulkBar');
  document.getElementById('bulkCount').textContent = n + ' fichier' + (n > 1 ? 's' : '') + ' sélectionné' + (n > 1 ? 's' : '');
  bar.style.display = n > 0 ? 'flex' : 'none';
  document.getElementById('libGrid')?.classList.toggle('selection-mode', n > 0);
}

function selectAll() {
  document.querySelectorAll('.lib-checkbox').forEach(cb => {
    cb.checked = true;
    selectedIds.add(parseInt(cb.dataset.id));
    cb.closest('.library-item').classList.add('is-selected');
  });
  updateBulkBar();
}

function clearSelection() {
  selectedIds.clear();
  document.querySelectorAll('.lib-checkbox').forEach(cb => {
    cb.checked = false;
    cb.closest('.library-item').classList.remove('is-selected');
  });
  updateBulkBar();
}

async function jsonPost(url, ids) {
  const res = await fetch(url, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ids: [...ids] })
  });
  return res.json().catch(() => ({ success: false }));
}

<?php if (!$isArchive): ?>
function openUploadModal() { openModal('modalUpload'); }

document.getElementById('formUpload').addEventListener('submit', async function(e) {
  e.preventDefault();
  const res = await apiPost('/manage/library/upload', new FormData(this));
  if (res.success) { toast('Fichier importé ✓'); closeModal('modalUpload'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
});

async function archiveMedia(id) {
  if (!await confirmDelete('Archiver ce fichier ?')) return;
  const res = await apiPost(`/manage/library/${id}/archive`, new FormData());
  if (res.success) { toast('Fichier archivé'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
}

async function bulkArchive() {
  if (!selectedIds.size) return;
  if (!await confirmDelete(`Archiver ${selectedIds.size} fichier(s) sélectionné(s) ?`)) return;
  const res = await jsonPost('/manage/library/bulk-archive', selectedIds);
  if (res.success) { toast('Fichiers archivés ✓'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
}
<?php else: ?>
async function restoreMedia(id) {
  const res = await apiPost(`/manage/library/${id}/restore`, new FormData());
  if (res.success) { toast('Fichier restauré ✓'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
}

async function deleteMedia(id) {
  if (!await confirmDelete('Supprimer définitivement ce fichier ? Cette action est irréversible.')) return;
  const res = await apiPost(`/manage/library/${id}/delete`, new FormData());
  if (res.success) { toast('Fichier supprimé'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
}

async function bulkRestore() {
  if (!selectedIds.size) return;
  const res = await jsonPost('/manage/library/bulk-restore', selectedIds);
  if (res.success) { toast('Fichiers restaurés ✓'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
}

async function bulkDelete() {
  if (!selectedIds.size) return;
  if (!await confirmDelete(`Supprimer définitivement ${selectedIds.size} fichier(s) ? Cette action est irréversible.`)) return;
  const res = await jsonPost('/manage/library/bulk-delete', selectedIds);
  if (res.success) { toast('Fichiers supprimés'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
}
<?php endif; ?>
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
