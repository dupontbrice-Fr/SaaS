<?php ?>
<h1 class="page-title">Gestion des écrans de veille</h1>

<div style="margin-bottom:20px;">
  <button class="btn btn-primary" onclick="openModal('modalAddScreensaver')">AJOUTER UN ÉLÉMENT</button>
</div>

<div class="card">
  <table>
    <thead><tr><th style="width:40px;"></th><th style="width:60px;">ÉLÉMENT</th><th>NOM</th><th>STATUT</th><th>FAIT PAR</th><th style="text-align:right;">ACTIONS</th></tr></thead>
    <tbody>
    <?php if (empty($screensavers)): ?>
      <tr><td colspan="6" style="text-align:center;color:var(--text-muted);padding:40px;">Aucun élément</td></tr>
    <?php else: ?>
    <?php foreach ($screensavers as $ss): ?>
    <tr>
      <td><span class="drag-handle">⠿</span></td>
      <td>
        <?php if ($ss['file_path']): ?>
          <img src="/public/uploads/<?= htmlspecialchars($ss['file_path']) ?>" style="width:50px;height:35px;object-fit:cover;border-radius:4px;">
        <?php else: ?>
          <div style="width:50px;height:35px;background:var(--bg-input);border-radius:4px;display:flex;align-items:center;justify-content:center;">🌐</div>
        <?php endif; ?>
      </td>
      <td><strong style="color:var(--text-primary);"><?= htmlspecialchars($ss['name']) ?></strong></td>
      <td><span class="badge <?= $ss['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>"><?= $ss['status'] === 'active' ? 'Actif' : 'Inactif' ?></span></td>
      <td><span class="text-muted text-sm"><?= htmlspecialchars(Auth::user()['org_name'] ?? '') ?></span></td>
      <td style="text-align:right;">
        <div style="display:flex;gap:6px;justify-content:flex-end;">
          <button class="btn btn-secondary btn-sm" onclick="editScreensaver(<?= $ss['id'] ?>)">Modifier</button>
          <button class="btn btn-danger btn-sm" onclick="deleteScreensaver(<?= $ss['id'] ?>)">Supprimer</button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
  <?php if (!empty($screensavers)): ?>
  <div style="padding:12px 16px; color:var(--text-muted); font-size:12px;">
    <?= count($screensavers) ?> sur <?= count($screensavers) ?> &nbsp; Éléments par page : <strong>10</strong>
  </div>
  <?php endif; ?>
</div>

<!-- Modal -->
<div class="modal-overlay" id="modalAddScreensaver" style="display:none;">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div class="modal-title" id="ssModalTitle">Ajouter un élément</div>
      <button class="modal-close" onclick="closeModal('modalAddScreensaver')">×</button>
    </div>
    <form id="formScreensaver" enctype="multipart/form-data">
      <input type="hidden" id="ssId" name="ss_id" value="">
      <div id="ssMediaZone">
        <input type="file" id="ssFile" name="file" accept="image/*,video/mp4,application/pdf" style="display:none;">
        <input type="hidden" id="ssLibMediaId" name="library_media_id" value="">
        <div class="upload-zone mp-trigger" id="ssPickerZone" onclick="openSsPicker()" style="cursor:pointer;">
          AJOUTER UN FICHIER<br><span style="font-weight:400;font-size:11px;">OU GLISSER DÉPOSER · DEPUIS LA BIBLIOTHÈQUE</span>
        </div>
        <div id="ssFilePreview" class="upload-zone-preview" style="display:none;"></div>
      </div>
      <div class="form-input-floating">
        <input type="text" id="ssName" name="name" placeholder=" " required>
        <label>Titre</label>
      </div>
      <div class="form-input-floating">
        <select id="ssFileType" name="file_type" onchange="toggleSsType(this.value)">
          <option value="media">Media (JPG, PNG, JPEG, MP4, PDF)</option>
          <option value="url">URL</option>
        </select>
        <label>Type de fichier</label>
      </div>
      <div id="ssUrlField" style="display:none;">
        <div class="form-input-floating">
          <input type="url" id="ssUrl" name="url" placeholder=" ">
          <label>URL</label>
        </div>
        <small class="text-muted">URL requise pour l'écran de veille</small>
      </div>
      <div class="form-input-floating">
        <select id="ssStatus" name="status">
          <option value="active">Actif</option>
          <option value="inactive">Inactif</option>
        </select>
        <label>Select Status</label>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id="ssSubmitBtn">VALIDER</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddScreensaver')">ANNULER</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Media picker for screensaver ──
(function() {
  const zone = document.getElementById('ssPickerZone');
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.opacity = '0.7'; });
  zone.addEventListener('dragleave', () => { zone.style.opacity = '1'; });
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.style.opacity = '1';
    if (e.dataTransfer.files[0]) applySsMedia({ type: 'upload', file: e.dataTransfer.files[0] });
  });
})();

function openSsPicker() {
  MediaPicker.open({ accept: 'image/*,video/mp4,application/pdf', onSelect: applySsMedia });
}

function applySsMedia({ type, file, media }) {
  const inp = document.getElementById('ssFile');
  const lid = document.getElementById('ssLibMediaId');
  const prv = document.getElementById('ssFilePreview');
  if (type === 'upload') { setFileInput(inp, file); lid.value = ''; showFilePreview(file, prv); }
  else                   { inp.value = ''; lid.value = media.id; showLibraryPreview(media, prv); }
}

document.getElementById('formScreensaver').addEventListener('submit', async function(e) {
  e.preventDefault();
  const id = document.getElementById('ssId').value;
  const fd = new FormData(this);
  const url = id ? `/manage/screensaver/${id}` : '/manage/screensaver/add';
  const res = await apiPost(url, fd);
  if (res.success) { toast(id ? 'Élément modifié' : 'Élément ajouté'); closeModal('modalAddScreensaver'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
});
function toggleSsType(v) { document.getElementById('ssUrlField').style.display = v === 'url' ? 'block' : 'none'; document.getElementById('ssMediaZone').style.display = v === 'media' ? 'block' : 'none'; }
async function editScreensaver(id) {
  const d = await apiGet(`/manage/screensaver/${id}/get`);
  document.getElementById('ssId').value = d.id;
  document.getElementById('ssName').value = d.name;
  document.getElementById('ssFileType').value = d.file_type;
  document.getElementById('ssStatus').value = d.status;
  document.getElementById('ssUrl').value = d.url || '';
  document.getElementById('ssLibMediaId').value = '';
  document.getElementById('ssFilePreview').style.display = 'none';
  toggleSsType(d.file_type);
  document.getElementById('ssModalTitle').textContent = 'Modifier l\'élément';
  document.getElementById('ssSubmitBtn').textContent = 'VALIDER';
  openModal('modalAddScreensaver');
}
async function deleteScreensaver(id) {
  if (!await confirmDelete('Supprimer cet élément ?')) return;
  const res = await apiPost(`/manage/screensaver/${id}/delete`, new FormData());
  if (res.success) { toast('Élément supprimé'); location.reload(); }
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
