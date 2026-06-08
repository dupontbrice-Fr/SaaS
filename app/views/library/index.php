<?php
$typeLabels = ['image' => 'Images', 'video' => 'Vidéos', 'pdf' => 'PDFs'];
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

<!-- Stats — côte à côte, wrap à partir de 5 -->
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

<!-- Sub-tabs: Bibliothèque / Archives -->
<div class="tabs" style="margin-bottom:16px;">
  <a href="?type=<?= htmlspecialchars($filter) ?>"
     class="tab <?= !$isArchive ? 'active' : '' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
    Bibliothèque
  </a>
  <a href="?type=<?= htmlspecialchars($filter) ?>&sub=archives"
     class="tab <?= $isArchive ? 'active' : '' ?>">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
    Archives
    <?php if ($archivedCount > 0 && !$isArchive): ?>
    <span style="background:var(--text-muted);color:#fff;border-radius:10px;padding:1px 7px;font-size:11px;margin-left:4px;"><?= $archivedCount ?></span>
    <?php endif; ?>
  </a>
</div>

<?php if ($isArchive): ?>
<div style="color:var(--text-muted);font-size:13px;margin-bottom:16px;">
  Les fichiers archivés peuvent être restaurés ou supprimés définitivement. Ils ne sont plus comptabilisés dans les statistiques.
</div>
<?php endif; ?>

<!-- Type filters + Search -->
<div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; align-items:center;">
  <div style="display:flex; gap:6px;">
    <?php $types = ['all' => 'Tout', 'image' => '🖼️ Images', 'video' => '🎬 Vidéos', 'pdf' => '📄 PDFs']; ?>
    <?php foreach ($types as $k => $label): ?>
    <a href="?type=<?= $k ?><?= $isArchive ? '&sub=archives' : '' ?>"
       class="sub-tab <?= ($filter === $k) ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
  <div style="flex:1;"></div>
  <form method="GET" style="display:flex;gap:8px;align-items:center;">
    <input type="hidden" name="type"  value="<?= htmlspecialchars($filter) ?>">
    <?php if ($isArchive): ?><input type="hidden" name="sub" value="archives"><?php endif; ?>
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-input" placeholder="🔍 Rechercher..." style="width:200px;">
  </form>
</div>

<!-- Grid -->
<?php if (empty($media)): ?>
<div style="text-align:center;padding:60px;color:var(--text-muted);">
  <div style="font-size:48px;margin-bottom:16px;"><?= $isArchive ? '🗂️' : '📂' ?></div>
  <div><?= $isArchive ? 'Aucun fichier archivé.' : 'Aucun fichier dans la bibliothèque.' ?></div>
</div>
<?php else: ?>
<div class="library-grid">
  <?php foreach ($media as $m): ?>
  <div class="library-item" title="<?= htmlspecialchars($m['original_name']) ?>">
    <div class="library-item-preview">
      <?php if ($m['file_type'] === 'image'): ?>
        <img src="/public/uploads/<?= htmlspecialchars($m['path']) ?>" alt="" loading="lazy">
      <?php elseif ($m['file_type'] === 'video'): ?>
        <video src="/public/uploads/<?= htmlspecialchars($m['path']) ?>" muted preload="metadata"></video>
      <?php else: ?>
        <div style="font-size:36px;">📄</div>
      <?php endif; ?>
    </div>
    <div class="library-item-info">
      <div class="library-item-name"><?= htmlspecialchars($m['original_name']) ?></div>
      <div class="library-item-size"><?= Upload::formatSize((int)$m['file_size']) ?></div>
    </div>

    <?php if (!empty($m['admin_protected'])): ?>
    <div style="position:absolute;top:4px;left:4px;" title="Contenu protégé">
      <span style="background:rgba(0,0,0,.55);color:#fff;border-radius:4px;padding:2px 5px;font-size:11px;">🔒</span>
    </div>
    <?php endif; ?>

    <?php $canAct = Auth::isAdmin() || empty($m['admin_protected']); ?>
    <?php if ($canAct): ?>

    <?php if (!$isArchive): ?>
    <!-- Main view: archive button only -->
    <div style="position:absolute;top:4px;right:4px;opacity:0;" class="lib-action-btn">
      <button class="btn btn-secondary btn-sm btn-icon"
              onclick="archiveMedia(<?= $m['id'] ?>)"
              title="Archiver"
              style="background:rgba(0,0,0,.55);border:none;color:#fff;">
        <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="21 8 21 21 3 21 3 8"/><rect x="1" y="3" width="22" height="5"/><line x1="10" y1="12" x2="14" y2="12"/></svg>
      </button>
    </div>
    <?php else: ?>
    <!-- Archive view: restore + hard-delete -->
    <div style="position:absolute;top:4px;right:4px;display:flex;gap:4px;opacity:0;" class="lib-action-btn">
      <button class="btn btn-secondary btn-sm btn-icon"
              onclick="restoreMedia(<?= $m['id'] ?>)"
              title="Restaurer"
              style="background:rgba(34,197,94,.8);border:none;color:#fff;">
        ↩
      </button>
      <button class="btn btn-danger btn-sm btn-icon"
              onclick="deleteMedia(<?= $m['id'] ?>)"
              title="Supprimer définitivement">
        ✕
      </button>
    </div>
    <?php endif; ?>

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

<!-- Upload Modal (main view only) -->
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
.lib-stats {
  display: flex;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 24px;
}
.lib-stat-card {
  flex: 1 1 140px;
  min-width: 0;
}

/* Action buttons visible on hover */
.library-item:hover .lib-action-btn { opacity: 1 !important; }
</style>

<script>
<?php if (!$isArchive): ?>
function openUploadModal() { openModal('modalUpload'); }

document.getElementById('formUpload').addEventListener('submit', async function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  const res = await apiPost('/manage/library/upload', fd);
  if (res.success) { toast('Fichier importé'); closeModal('modalUpload'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
});

async function archiveMedia(id) {
  if (!await confirmDelete('Archiver ce fichier ? Il sera disponible dans l\'onglet Archives.')) return;
  const res = await apiPost(`/manage/library/${id}/archive`, new FormData());
  if (res.success) { toast('Fichier archivé'); location.reload(); }
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
<?php endif; ?>
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
