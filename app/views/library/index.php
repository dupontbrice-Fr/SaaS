<?php
$typeLabels = ['image' => 'Images', 'video' => 'Vidéos', 'pdf' => 'PDFs'];
$totalSize = array_sum(array_column($stats, 'total_size'));
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <h1 class="page-title" style="margin-bottom:0;">Bibliothèques</h1>
  <button class="btn btn-primary" onclick="openUploadModal()">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="17 8 12 3 7 8"/><line x1="12" y1="3" x2="12" y2="15"/></svg>
    IMPORTER UN FICHIER
  </button>
</div>

<!-- Stats -->
<div class="grid-4" style="margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-value"><?= $totalCount ?></div>
    <div class="stat-label">Total fichiers</div>
  </div>
  <?php foreach ($stats as $s): ?>
  <div class="stat-card">
    <div class="stat-value"><?= $s['count'] ?></div>
    <div class="stat-label"><?= $typeLabels[$s['file_type']] ?? $s['file_type'] ?></div>
  </div>
  <?php endforeach; ?>
  <div class="stat-card">
    <div class="stat-value" style="font-size:20px;"><?= Upload::formatSize($totalSize) ?></div>
    <div class="stat-label">Espace utilisé</div>
  </div>
</div>

<!-- Filters -->
<div style="display:flex; gap:8px; margin-bottom:16px; flex-wrap:wrap; align-items:center;">
  <div style="display:flex; gap:6px;">
    <?php $types = ['all' => 'Tout', 'image' => '🖼️ Images', 'video' => '🎬 Vidéos', 'pdf' => '📄 PDFs']; ?>
    <?php foreach ($types as $k => $label): ?>
    <a href="?type=<?= $k ?>" class="sub-tab <?= ($filter === $k || ($k === 'all' && $filter === 'all')) ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </div>
  <div style="flex:1;"></div>
  <form method="GET" style="display:flex;gap:8px;align-items:center;">
    <input type="hidden" name="type" value="<?= htmlspecialchars($filter) ?>">
    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="form-input" placeholder="🔍 Rechercher..." style="width:200px;">
  </form>
</div>

<!-- Grid -->
<?php if (empty($media)): ?>
<div style="text-align:center;padding:60px;color:var(--text-muted);">
  <div style="font-size:48px;margin-bottom:16px;">📂</div>
  <div>Aucun fichier dans la bibliothèque</div>
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
    <div style="position:absolute;top:4px;right:4px;opacity:0;" class="lib-delete-btn" data-id="<?= $m['id'] ?>">
      <button class="btn btn-danger btn-sm btn-icon" onclick="deleteMedia(<?= $m['id'] ?>)" title="Supprimer">✕</button>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Pagination -->
<?php if ($totalCount > 24): ?>
<div class="pagination">
  Affichage <?= ($page - 1) * 24 + 1 ?>–<?= min($page * 24, $totalCount) ?> sur <?= $totalCount ?>
  <?php if ($page > 1): ?>
    <a href="?type=<?= $filter ?>&page=<?= $page - 1 ?>" class="page-btn">‹</a>
  <?php endif; ?>
  <?php if ($page * 24 < $totalCount): ?>
    <a href="?type=<?= $filter ?>&page=<?= $page + 1 ?>" class="page-btn">›</a>
  <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- Upload Modal -->
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

<style>
.library-item:hover .lib-delete-btn { opacity: 1; }
</style>

<script>
function openUploadModal() { openModal('modalUpload'); }

document.getElementById('formUpload').addEventListener('submit', async function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  const res = await apiPost('/manage/library/upload', fd);
  if (res.success) { toast('Fichier importé'); closeModal('modalUpload'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
});

async function deleteMedia(id) {
  if (!await confirmDelete('Supprimer ce fichier ?')) return;
  const res = await apiPost(`/manage/library/${id}/delete`, new FormData());
  if (res.success) { toast('Fichier supprimé'); location.reload(); }
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
