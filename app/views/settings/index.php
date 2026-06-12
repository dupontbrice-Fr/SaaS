<?php ?>
<h1 class="page-title">PARAMÈTRES</h1>

<!-- Settings Tabs -->
<div class="tabs" style="margin-bottom:24px;">
  <div class="tab active" onclick="showSettingsTab('fond')">FOND D'ÉCRAN</div>
  <div class="tab" onclick="showSettingsTab('widgets')">WIDGETS</div>
</div>

<form id="formSettings" enctype="multipart/form-data">

<!-- Tab: Fond d'écran -->
<div id="tabFond">

  <div class="card" style="padding:24px; margin-bottom:16px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:16px;color:var(--text-secondary);">Votre logo actuel :</div>
    <?php if (!empty($settings['logo'])): ?>
    <img src="/public/uploads/<?= htmlspecialchars($settings['logo']) ?>" style="height:60px;margin-bottom:16px;border-radius:8px;">
    <?php else: ?>
    <div style="color:var(--text-muted);font-size:13px;margin-bottom:16px;">Aucun logo défini</div>
    <?php endif; ?>
    <div class="upload-zone" data-dropzone="logoFile" data-preview="logoPreview">
      <input type="file" id="logoFile" name="logo" accept="image/*">
      MODIFIER LE LOGO<br><span style="font-weight:400;font-size:11px;">PNG ou JPEG, max 1 Mo</span>
    </div>
    <div id="logoPreview" class="upload-zone-preview" style="display:none;"></div>
  </div>

  <div class="card" style="padding:24px; margin-bottom:16px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:16px;color:var(--text-secondary);">Couleur des filtres</div>
    <div style="display:flex;align-items:center;gap:16px;">
      <input type="color" id="colorPicker" name="filter_color" value="<?= htmlspecialchars($settings['filter_color'] ?? '#6b6ef9') ?>" style="width:48px;height:48px;border:none;background:none;cursor:pointer;border-radius:8px;">
      <div id="colorPreview" style="width:48px;height:48px;border-radius:8px;background:<?= htmlspecialchars($settings['filter_color'] ?? '#6b6ef9') ?>;"></div>
      <input type="text" value="<?= htmlspecialchars($settings['filter_color'] ?? '#6b6ef9') ?>" class="form-input" style="width:120px;" id="colorHex" oninput="updateColor(this.value)">
    </div>
  </div>

  <div class="card" style="padding:24px; margin-bottom:16px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:4px;color:var(--text-secondary);">Délai avant mise en veille des écrans</div>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">* Minimum de 30 secondes</div>
    <div style="display:flex;align-items:center;gap:8px;">
      <span class="text-muted text-xs">HH:MM:SS</span>
      <input type="number" name="screensaver_delay" value="<?= (int)($settings['screensaver_delay'] ?? 300) ?>" min="30" class="form-input" style="width:120px;" placeholder="300">
      <span class="text-muted text-xs">secondes</span>
    </div>
  </div>

  <div class="card" style="padding:24px; margin-bottom:16px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:4px;color:var(--text-secondary);">Message personnalisable</div>
    <div style="font-size:12px;color:var(--text-muted);margin-bottom:12px;">Si le champ est vide, aucun message ne sera affiché sur la/les bornes</div>
    <textarea name="custom_message" class="form-textarea" placeholder="Votre message personnalisé..."><?= htmlspecialchars($settings['custom_message'] ?? '') ?></textarea>
  </div>
</div>

<!-- Tab: Widgets -->
<div id="tabWidgets" style="display:none;">
  <div class="card" style="padding:24px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:16px;color:var(--text-secondary);">Activation des Widgets</div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;">
      <label class="form-check" style="background:var(--bg-input);padding:16px;border-radius:8px;cursor:pointer;">
        <input type="checkbox" name="widget_qr" <?= !empty($settings['widget_qr']) ? 'checked' : '' ?>>
        <div>
          <div style="font-weight:600;">Bouton QR</div>
          <div class="text-muted text-xs">Activer le widget de bouton QR</div>
        </div>
      </label>
      <label class="form-check" style="background:var(--bg-input);padding:16px;border-radius:8px;cursor:pointer;">
        <input type="checkbox" name="widget_pmr" <?= !empty($settings['widget_pmr']) ? 'checked' : '' ?>>
        <div>
          <div style="font-weight:600;">Bouton PMR</div>
          <div class="text-muted text-xs">Activer le widget de bouton PMR</div>
        </div>
      </label>
      <label class="form-check" style="background:var(--bg-input);padding:16px;border-radius:8px;cursor:pointer;">
        <input type="checkbox" name="widget_weather" <?= !empty($settings['widget_weather']) ? 'checked' : '' ?>>
        <div>
          <div style="font-weight:600;">Météo</div>
          <div class="text-muted text-xs">Activer le widget météo</div>
        </div>
      </label>
      <label class="form-check" style="background:var(--bg-input);padding:16px;border-radius:8px;cursor:pointer;">
        <input type="checkbox" name="widget_datetime" <?= !empty($settings['widget_datetime']) ? 'checked' : '' ?>>
        <div>
          <div style="font-weight:600;">Date & Heure</div>
          <div class="text-muted text-xs">Activer le widget de date et heure</div>
        </div>
      </label>
    </div>
  </div>
</div>

<div style="margin-top:24px;">
  <button type="submit" class="btn btn-primary">APPLIQUER</button>
</div>

</form>

<script>
function showSettingsTab(tab) {
  document.getElementById('tabFond').style.display = tab === 'fond' ? 'block' : 'none';
  document.getElementById('tabWidgets').style.display = tab === 'widgets' ? 'block' : 'none';
  document.querySelectorAll('.tab').forEach((t,i) => {
    t.classList.toggle('active', (tab === 'fond' && i === 0) || (tab === 'widgets' && i === 1));
  });
}

function updateColor(val) {
  if (/^#[0-9a-fA-F]{6}$/.test(val)) {
    document.getElementById('colorPicker').value = val;
    document.getElementById('colorPreview').style.background = val;
  }
}
document.getElementById('colorPicker').addEventListener('input', function() {
  document.getElementById('colorHex').value = this.value;
  document.getElementById('colorPreview').style.background = this.value;
});

document.getElementById('formSettings').addEventListener('submit', async function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  const res = await apiPost('/settings/save', fd);
  if (res.success) { toast('Paramètres enregistrés'); setTimeout(() => location.reload(), 800); }
  else toast(res.error || 'Erreur', 'error');
});
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
