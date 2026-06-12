<?php
$filterColor = Auth::filterColor();
?>

<h1 class="page-title">Gestion des bannières</h1>

<div style="margin-bottom:20px;">
  <button class="btn btn-primary" onclick="openAddBanner()">
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
        <th>CATALOGUE</th>
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
          <?php $mime = pathinfo($b['file_path'], PATHINFO_EXTENSION); ?>
          <?php if (in_array(strtolower($mime), ['mp4', 'webm'])): ?>
            <div style="width:50px;height:35px;background:var(--bg-input);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:18px;">🎬</div>
          <?php else: ?>
            <img src="/public/uploads/<?= htmlspecialchars($b['file_path']) ?>" style="width:50px;height:35px;object-fit:cover;border-radius:4px;">
          <?php endif; ?>
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
      <td>
        <?php if ($b['catalog_name']): ?>
          <span style="font-size:13px;"><?= htmlspecialchars($b['catalog_name']) ?></span>
        <?php else: ?>
          <span class="text-muted text-sm">Tous les catalogues</span>
        <?php endif; ?>
      </td>
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

      <!-- Prévisualisation du média actuel (visible uniquement en mode édition) -->
      <div id="bannerCurrentMedia" style="display:none;margin-bottom:12px;">
        <div style="font-size:12px;color:var(--text-muted);margin-bottom:6px;font-weight:600;">MÉDIA ACTUEL</div>
        <div id="bannerCurrentMediaContent" style="border-radius:8px;overflow:hidden;background:var(--bg-input);max-height:180px;display:flex;align-items:center;justify-content:center;"></div>
        <div style="font-size:11px;color:var(--text-muted);margin-top:4px;">Sélectionnez un nouveau fichier ci-dessous pour le remplacer.</div>
      </div>

      <input type="file" id="bannerFile" name="file" accept="image/*,video/mp4,application/pdf" style="display:none;">
      <input type="hidden" id="bannerLibMediaId" name="library_media_id" value="">
      <div class="upload-zone mp-trigger" id="bannerPickerZone" onclick="openBannerPicker()" style="cursor:pointer;">
        AJOUTER UN FICHIER<br><span style="font-weight:400;font-size:11px;">OU GLISSER DÉPOSER · DEPUIS LA BIBLIOTHÈQUE</span>
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
        <label>Type de fichier</label>
      </div>

      <div id="bannerUrlField" style="display:none;">
        <div class="form-input-floating">
          <input type="url" id="bannerUrl" name="url" placeholder=" ">
          <label>URL</label>
        </div>
      </div>

      <div class="form-input-floating">
        <select id="bannerStatus" name="status">
          <option value="active">Actif</option>
          <option value="inactive">Inactif</option>
        </select>
        <label>Statut</label>
      </div>

      <div class="form-input-floating">
        <select id="bannerCatalog" name="catalog_id">
          <option value="">Tous les catalogues (aucun filtre)</option>
          <?php foreach ($catalogs as $cat): ?>
          <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Catalogue</label>
      </div>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id="bannerSubmitBtn">VALIDER</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddBanner')">ANNULER</button>
      </div>
    </form>
  </div>
</div>

<script>
// ── Media picker for banners ──
(function() {
  const zone = document.getElementById('bannerPickerZone');
  zone.addEventListener('dragover', e => { e.preventDefault(); zone.style.opacity = '0.7'; });
  zone.addEventListener('dragleave', () => { zone.style.opacity = '1'; });
  zone.addEventListener('drop', e => {
    e.preventDefault(); zone.style.opacity = '1';
    if (e.dataTransfer.files[0]) applyBannerMedia({ type: 'upload', file: e.dataTransfer.files[0] });
  });
})();

function openBannerPicker() {
  MediaPicker.open({ accept: 'image/*,video/mp4,application/pdf', onSelect: applyBannerMedia });
}

function applyBannerMedia({ type, file, media }) {
  const fileInput = document.getElementById('bannerFile');
  const libId     = document.getElementById('bannerLibMediaId');
  const preview   = document.getElementById('bannerFilePreview');
  if (type === 'upload') {
    setFileInput(fileInput, file);
    libId.value = '';
    showFilePreview(file, preview);
  } else {
    fileInput.value = '';
    libId.value = media.id;
    showLibraryPreview(media, preview);
  }
}

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

function resetBannerModal() {
  document.getElementById('bannerId').value = '';
  document.getElementById('bannerName').value = '';
  document.getElementById('bannerFileType').value = 'media';
  document.getElementById('bannerStatus').value = 'active';
  document.getElementById('bannerUrl').value = '';
  document.getElementById('bannerCatalog').value = '';
  document.getElementById('bannerLibMediaId').value = '';
  document.getElementById('bannerFilePreview').style.display = 'none';
  document.getElementById('bannerCurrentMedia').style.display = 'none';
  document.getElementById('bannerCurrentMediaContent').innerHTML = '';
  document.getElementById('bannerModalTitle').textContent = 'Ajouter une bannière';
  document.getElementById('bannerSubmitBtn').textContent = 'VALIDER';
  toggleBannerType('media');
}

async function editBanner(id) {
  const b = await apiGet(`/manage/banners/${id}/get`);
  resetBannerModal();

  document.getElementById('bannerId').value = b.id;
  document.getElementById('bannerName').value = b.name;
  document.getElementById('bannerFileType').value = b.file_type;
  document.getElementById('bannerStatus').value = b.status;
  document.getElementById('bannerUrl').value = b.url || '';
  document.getElementById('bannerCatalog').value = b.catalog_id || '';
  document.getElementById('bannerModalTitle').textContent = 'Modifier la bannière';
  document.getElementById('bannerSubmitBtn').textContent = 'ENREGISTRER';
  toggleBannerType(b.file_type);

  // Prévisualisation du média actuel
  if (b.file_url) {
    const ext = b.file_url.split('.').pop().toLowerCase();
    const contentDiv = document.getElementById('bannerCurrentMediaContent');
    if (['mp4', 'webm'].includes(ext)) {
      contentDiv.innerHTML = `<video src="${b.file_url}" style="max-width:100%;max-height:160px;border-radius:6px;" controls muted></video>`;
    } else if (['pdf'].includes(ext)) {
      contentDiv.innerHTML = `<div style="padding:20px;font-size:28px;text-align:center;">📄<br><span style="font-size:12px;color:var(--text-muted);">${b.file_url.split('/').pop()}</span></div>`;
    } else {
      contentDiv.innerHTML = `<img src="${b.file_url}" style="max-width:100%;max-height:160px;object-fit:contain;border-radius:6px;">`;
    }
    document.getElementById('bannerCurrentMedia').style.display = 'block';
  } else if (b.file_type === 'url' && b.url) {
    const contentDiv = document.getElementById('bannerCurrentMediaContent');
    contentDiv.innerHTML = `<div style="padding:16px;font-size:13px;color:var(--text-muted);word-break:break-all;">🌐 ${b.url}</div>`;
    document.getElementById('bannerCurrentMedia').style.display = 'block';
  }

  openModal('modalAddBanner');
}

function openAddBanner() {
  resetBannerModal();
  openModal('modalAddBanner');
}

async function deleteBanner(id) {
  if (!await confirmDelete('Supprimer cette bannière ?')) return;
  const res = await apiPost(`/manage/banners/${id}/delete`, new FormData());
  if (res.success) { toast('Bannière supprimée'); location.reload(); }
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
