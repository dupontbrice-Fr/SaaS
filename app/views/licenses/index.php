<?php ?>
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;">
  <h1 class="page-title" style="margin-bottom:0;">Gestion des licences</h1>
  <button class="btn btn-primary" onclick="openModal('modalCreateLicense')">
    + GÉNÉRER DES LICENCES
  </button>
</div>

<div class="card">
  <table>
    <thead><tr><th>CODE DEVICE</th><th>STATUT</th><th>ÉCRAN ASSOCIÉ</th><th>CRÉÉ LE</th><th>ACTIONS</th></tr></thead>
    <tbody>
    <?php if (empty($licenses)): ?>
      <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:40px;">Aucune licence. Générez-en une pour connecter un écran.</td></tr>
    <?php else: ?>
    <?php foreach ($licenses as $l): ?>
    <tr>
      <td>
        <code style="background:var(--bg-input);padding:4px 10px;border-radius:4px;font-size:14px;font-weight:700;color:var(--accent);letter-spacing:2px;"><?= htmlspecialchars($l['code']) ?></code>
      </td>
      <td>
        <span class="badge <?= $l['active'] ? 'badge-success' : 'badge-danger' ?>">
          <?= $l['active'] ? 'Active' : 'Révoquée' ?>
        </span>
      </td>
      <td>
        <?php if ($l['screen_name']): ?>
          <span style="color:var(--text-primary);"><?= htmlspecialchars($l['screen_name']) ?></span>
          <span class="badge <?= $l['screen_status'] === 'online' ? 'badge-success' : 'badge-danger' ?>" style="margin-left:6px;font-size:9px;"><?= $l['screen_status'] === 'online' ? 'En ligne' : 'Hors ligne' ?></span>
        <?php else: ?>
          <span class="text-muted">Non assignée</span>
        <?php endif; ?>
      </td>
      <td class="text-muted text-sm"><?= date('d/m/Y H:i', strtotime($l['created_at'])) ?></td>
      <td>
        <div style="display:flex;gap:6px;">
          <?php if ($l['active']): ?>
            <button class="btn btn-secondary btn-sm" onclick="revokeLicense(<?= $l['id'] ?>)">Révoquer</button>
          <?php else: ?>
            <button class="btn btn-primary btn-sm" onclick="activateLicense(<?= $l['id'] ?>)">Activer</button>
          <?php endif; ?>
          <button class="btn btn-danger btn-sm" onclick="deleteLicense(<?= $l['id'] ?>)">Supprimer</button>
        </div>
      </td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<div style="margin-top:20px; padding:16px; background:var(--bg-card); border-radius:12px; border:1px solid var(--border);">
  <div style="font-size:13px; font-weight:600; margin-bottom:8px;">Comment connecter un écran ?</div>
  <ol style="color:var(--text-muted); font-size:13px; padding-left:20px; line-height:2;">
    <li>Installez l'APK MultiApp sur l'écran Android</li>
    <li>Au premier lancement, l'application demande un <strong>Code Device</strong></li>
    <li>Entrez l'un des codes générés ci-dessus</li>
    <li>L'écran apparaît automatiquement dans "Liste des écrans" et est En ligne</li>
  </ol>
</div>

<!-- Modal Create License -->
<div class="modal-overlay" id="modalCreateLicense" style="display:none;">
  <div class="modal" style="max-width:400px;">
    <div class="modal-header">
      <div class="modal-title">Générer des licences</div>
      <button class="modal-close" onclick="closeModal('modalCreateLicense')">×</button>
    </div>
    <form id="formCreateLicense">
      <div class="form-input-floating">
        <input type="number" name="count" value="1" min="1" max="10" placeholder=" " required>
        <label>Nombre de licences à générer</label>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">GÉNÉRER</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalCreateLicense')">ANNULER</button>
      </div>
    </form>
    <div id="generatedCodes" style="display:none;margin-top:16px;">
      <div style="font-size:13px;font-weight:600;margin-bottom:8px;">Codes générés :</div>
      <div id="codesList"></div>
    </div>
  </div>
</div>

<script>
document.getElementById('formCreateLicense').addEventListener('submit', async function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  const res = await apiPost('/manage/licenses/create', fd);
  if (res.success) {
    document.getElementById('generatedCodes').style.display = 'block';
    document.getElementById('codesList').innerHTML = res.licenses.map(l =>
      `<div style="padding:8px;background:var(--bg-input);border-radius:6px;margin-bottom:6px;font-family:monospace;font-size:16px;letter-spacing:3px;color:var(--accent);font-weight:700;">${l.code}</div>`
    ).join('');
    setTimeout(() => { closeModal('modalCreateLicense'); location.reload(); }, 3000);
  } else toast(res.error || 'Erreur', 'error');
});

async function revokeLicense(id) {
  if (!await confirmDelete('Révoquer cette licence ?')) return;
  const res = await apiPost(`/manage/licenses/${id}/revoke`, new FormData());
  if (res.success) { toast('Licence révoquée'); location.reload(); }
}
async function activateLicense(id) {
  const res = await apiPost(`/manage/licenses/${id}/activate`, new FormData());
  if (res.success) { toast('Licence activée'); location.reload(); }
}
async function deleteLicense(id) {
  if (!await confirmDelete('Supprimer cette licence ? L\'écran associé sera déconnecté.')) return;
  const res = await apiPost(`/manage/licenses/${id}/delete`, new FormData());
  if (res.success) { toast('Licence supprimée'); location.reload(); }
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
