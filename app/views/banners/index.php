<?php
$filterColor = Auth::filterColor();
?>

<h1 class="page-title">Gestion des bannières</h1>

<div style="margin-bottom:20px;">
  <button class="btn btn-primary" onclick="openModal('modalAddBanner')">
    AJOUTER UNE BANNIÈRE
  </button>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <th style="width:40px;"></th>
        <th style="width:60px;">BANNIÈRE</th>
        <th>NOM</th>
        <th>STATUT</th>
        <th>TYPE</th>
        <th>ÉCRAN</th>
        <th>ACTIONS</th>
      </tr>
    </thead>
    <tbody id="bannersBody">
    <?php if (empty($banners)): ?>
      <tr><td colspan="7" style="text-align:center;color:var(--text-muted);padding:40px;">Aucune bannière</td></tr>
    <?php else: ?>
    <?php foreach ($banners as $b): ?>
    <tr data-id="<?= $b['id'] ?>">
      <td><span class="drag-handle">⠿</span></td>
      <td>
        <?php if ($b['file_path']): ?>
          <img src="/public/uploads/<?= htmlspecialchars($b['file_path']) ?>" style="width:50px;height:35px;object-fit:cover;border-radius:4px;">
        <?php elseif ($b['url']): ?>
          <div style="width:50px;height:35px;background:var(--bg-input);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:18px;">🌐</div>
        <?php else: ?>
          <div style="width:50px;height:35px;background:var(--bg-input);border-radius:4px;"></div>
        <?php endif; ?>
      </td>
      <td><strong style="color:var(--text-primary);"><?= htmlspecialchars($b['name']) ?></strong></td>
      <td>
        <span class="badge <?= $b['status'] === 'active' ? 'badge-success' : 'badge-danger' ?>">
          <?= $b['status'] === 'active' ? 'Actif' : 'Inactif' ?>
        </span>
      </td>
      <td><?= $b['file_type'] === 'url' ? 'URL' : 'Media' ?></td>
      <td><span class="text-muted text-sm">—</span></td>
      <td>
        <div style="display:flex;gap:6px;">
          <button class="btn btn-secondary btn-sm" onclick="editBanner(<?= $b['id'] ?>)">Modifier</button>
          <button class="btn btn-danger btn-sm" onclick="deleteBanner(<?= $b['id'] ?>)">Supprimer</button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal: Add/Edit Banner -->
<div class="modal-overlay" id="modalAddBanner" style="display:none;">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div class="modal-title" id="bannerModalTitle">Ajouter une bannière</div>
      <button class="modal-close" onclick="closeModal('modalAddBanner')">×</button>
    </div>
    <form id="formBanner" enctype="multipart/form-data">
      <input type="hidden" id="bannerId" name="banner_id" value="">

      <div class="upload-zone" data-dropzone="bannerFile" data-preview="bannerFilePreview">
        <input type="file" id="bannerFile" name="file" accept="image/*,video/mp4,application/pdf">
        AJOUTER UN FICHIER<br><span style="font-weight:400;font-size:11px;">OU GLISSER DÉPOSER</span>
      </div>
      <div id="bannerFilePreview" class="upload-zone-preview" style="display:none;"></div>

      <div class="form-input-floating">
        <input type="text" id="bannerName" name="name" placeholder=" " required>
        <label>Titre</label>
      </div>

      <div class="form-input-floating">
        <select id="bannerFileType" name="file_type" onchange="toggleBannerType(this.value)">
          <option value="media">Media (JPG, PNG, JPEG, MP4, PDF)</option>
          <option value="url">URL</option>
        </select>
        <label>Type de fichier produit</label>
      </div>

      <div id="bannerUrlField" style="display:none;">
        <div class="form-input-floating">
          <input type="url" id="bannerUrl" name="url" placeholder=" ">
          <label>URL</label>
        </div>
        <small class="text-muted">URL requise pour la bannière</small>
      </div>

      <div class="form-input-floating">
        <select id="bannerStatus" name="status">
          <option value="active">Actif</option>
          <option value="inactive">Inactif</option>
        </select>
        <label>Statut</label>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id="bannerSubmitBtn">VALIDER</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddBanner')">ANNULER</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('formBanner').addEventListener('submit', async function(e) {
  e.preventDefault();
  const id = document.getElementById('bannerId').value;
  const fd = new FormData(this);
  const url = id ? `/manage/banners/${id}` : '/manage/banners/add';
  const res = await apiPost(url, fd);
  if (res.success) { toast(id ? 'Bannière modifiée' : 'Bannière ajoutée'); closeModal('modalAddBanner'); location.reload(); }
  else toast(res.error || 'Erreur', 'error');
});

function toggleBannerType(val) {
  document.getElementById('bannerUrlField').style.display = val === 'url' ? 'block' : 'none';
}

async function editBanner(id) {
  const b = await apiGet(`/manage/banners/${id}/get`);
  document.getElementById('bannerId').value = b.id;
  document.getElementById('bannerName').value = b.name;
  document.getElementById('bannerFileType').value = b.file_type;
  document.getElementById('bannerStatus').value = b.status;
  document.getElementById('bannerUrl').value = b.url || '';
  document.getElementById('bannerModalTitle').textContent = 'Modifier la bannière';
  document.getElementById('bannerSubmitBtn').textContent = 'ENREGISTRER';
  toggleBannerType(b.file_type);
  openModal('modalAddBanner');
}

async function deleteBanner(id) {
  if (!await confirmDelete('Supprimer cette bannière ?')) return;
  const res = await apiPost(`/manage/banners/${id}/delete`, new FormData());
  if (res.success) { toast('Bannière supprimée'); location.reload(); }
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
