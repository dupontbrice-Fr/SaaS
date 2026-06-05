<?php ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <h1 class="page-title" style="margin-bottom:0;">Liste des certificats</h1>
  <div style="display:flex;gap:8px;align-items:center;">
    <div class="form-input-floating" style="margin-bottom:0;">
      <select class="form-select" onchange="sortCerts(this.value)" style="width:180px;">
        <option value="updated_at">Dernière modification</option>
        <option value="name">Nom</option>
      </select>
      <label>Trier par</label>
    </div>
    <a href="/manage/certificates/zip" class="btn btn-secondary">
      <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
      TÉLÉCHARGER (.ZIP)
    </a>
  </div>
</div>

<div class="card">
  <table>
    <thead>
      <tr>
        <th style="width:60px;">APERÇU</th>
        <th>CERTIFICAT</th>
        <th>MODIFICATION</th>
        <th style="width:100px;"></th>
      </tr>
    </thead>
    <tbody>
    <?php if (empty($certs)): ?>
      <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:40px;">
        <div style="font-size:36px;margin-bottom:12px;">🏆</div>
        Aucun certificat. Générez-en un depuis la page Catalogue.
      </td></tr>
    <?php else: ?>
    <?php foreach ($certs as $cert): ?>
    <tr>
      <td>
        <?php if (!empty($cert['thumbnail'])): ?>
          <img src="/public/uploads/<?= htmlspecialchars($cert['thumbnail']) ?>" style="width:50px;height:35px;object-fit:cover;border-radius:4px;">
        <?php else: ?>
          <div style="width:50px;height:35px;background:var(--bg-input);border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:18px;">📄</div>
        <?php endif; ?>
      </td>
      <td>
        <strong style="color:var(--text-primary);"><?= htmlspecialchars($cert['product_name']) ?></strong>
      </td>
      <td>
        <span class="text-muted text-sm">Mis à jour le <?= date('j F Y H:i', strtotime($cert['updated_at'])) ?></span>
      </td>
      <td>
        <div style="display:flex;gap:6px;">
          <a href="/manage/certificates/download/<?= $cert['product_id'] ?>" class="btn btn-primary btn-sm" target="_blank" title="Télécharger le certificat">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
          </a>
          <button class="btn btn-danger btn-sm" onclick="deleteCert(<?= $cert['id'] ?>)" title="Supprimer">
            <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v2"/></svg>
          </button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
  <?php if (!empty($certs)): ?>
  <div style="padding:12px 16px; display:flex; align-items:center; justify-content:space-between; font-size:12px; color:var(--text-muted);">
    <span>1–<?= count($certs) ?> sur <?= $total['c'] ?? count($certs) ?></span>
    <span>Éléments par page : 10</span>
  </div>
  <?php endif; ?>
</div>

<script>
async function deleteCert(id) {
  if (!await confirmDelete('Supprimer ce certificat ?')) return;
  const res = await apiPost(`/manage/certificates/${id}/delete`, new FormData());
  if (res.success) { toast('Certificat supprimé'); location.reload(); }
}
function sortCerts(val) { window.location.href = '?sort=' + val; }
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
