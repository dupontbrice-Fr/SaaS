<?php
function formatBytes(int $bytes): string {
    if ($bytes <= 0) return '0 Bytes';
    if ($bytes >= 1073741824) return round($bytes/1073741824,2).' GB';
    if ($bytes >= 1048576) return round($bytes/1048576,2).' MB';
    return round($bytes/1024,2).' KB';
}
?>

<h1 class="page-title">Liste des écrans</h1>

<div style="margin-bottom:24px;">
  <button class="btn btn-primary" onclick="openModal('modalAddDemo')">
    AJOUTER UN ÉCRAN DÉMO
  </button>
</div>

<!-- Screens grid -->
<div style="display:flex; flex-wrap:wrap; gap:20px;">
<?php if (empty($screens)): ?>
  <div style="text-align:center; padding:60px; color:var(--text-muted); width:100%;">
    <div style="font-size:48px; margin-bottom:16px;">📺</div>
    <div>Aucun écran connecté</div>
    <div style="font-size:13px; margin-top:8px;">Ajoutez un écran démo ou connectez un écran via l'APK</div>
  </div>
<?php else: ?>
  <?php foreach ($screens as $screen): ?>
  <div class="screen-card">
    <div class="screen-card-header">
      <span><?= htmlspecialchars($screen['name']) ?></span>
      <div style="position:relative;">
        <button class="btn btn-secondary btn-sm btn-icon" onclick="toggleDropdown('screenMenu<?= $screen['id'] ?>')">⋮</button>
        <div class="dropdown-menu" id="screenMenu<?= $screen['id'] ?>" style="display:none;">
          <div class="dropdown-item" onclick="openScreenSettings(<?= $screen['id'] ?>)">⚙️ Paramètres</div>
          <div class="dropdown-divider"></div>
          <div class="dropdown-item danger" onclick="deleteScreen(<?= $screen['id'] ?>)">🗑️ Supprimer</div>
        </div>
      </div>
    </div>

    <div class="screen-card-preview">
      <?php if ($screen['preview_image']): ?>
        <img src="/public/uploads/<?= htmlspecialchars($screen['preview_image']) ?>" alt="">
      <?php else: ?>
        <div class="screen-card-preview-placeholder">
          <?php if ($screen['status'] === 'offline'): ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="2" y="7" width="20" height="15" rx="2"/><polyline points="17 2 12 7 7 2"/></svg>
            <span style="color:var(--red); font-size:11px;">Hors ligne</span>
          <?php else: ?>
            <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="var(--green)" stroke-width="1.5"><rect x="2" y="7" width="20" height="15" rx="2"/><polyline points="17 2 12 7 7 2"/></svg>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- License code — always visible if present -->
    <?php if ($screen['license_code']): ?>
    <div style="padding:4px 12px 8px; display:flex; align-items:center; justify-content:space-between; gap:8px;">
      <code style="background:var(--bg-input);padding:3px 10px;border-radius:6px;font-size:13px;font-weight:700;color:var(--accent);letter-spacing:2px;flex:1;text-align:center;"><?= htmlspecialchars($screen['license_code']) ?></code>
      <button class="btn btn-secondary btn-sm" style="font-size:11px;padding:3px 8px;flex-shrink:0;" onclick="navigator.clipboard.writeText('<?= htmlspecialchars($screen['license_code']) ?>').then(()=>toast('Code copié !'))">Copier</button>
    </div>
    <?php endif; ?>

    <!-- Status toggle -->
    <div style="padding:0 12px 4px;">
      <div style="display:flex; align-items:center; justify-content:space-between; cursor:pointer;" onclick="toggleScreenInfo(<?= $screen['id'] ?>)">
        <span style="font-size:12px;">Statut : <span class="<?= $screen['status'] === 'online' ? 'screen-info-value online' : 'screen-info-value offline' ?>"><?= $screen['status'] === 'online' ? 'En ligne' : 'Hors ligne' ?></span></span>
        <svg id="screenInfoArrow<?= $screen['id'] ?>" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="transition:transform 0.2s;"><polyline points="18 15 12 9 6 15"/></svg>
      </div>
    </div>

    <div class="screen-card-info" id="screenInfo<?= $screen['id'] ?>" style="display:<?= $screen['status'] === 'online' ? 'block' : 'none' ?>;">
      <?php if ($screen['license_code']): ?>
      <div class="screen-info-row"><span class="screen-info-label">Code Device :</span><span class="screen-info-value"><?= htmlspecialchars($screen['license_code']) ?></span></div>
      <?php endif; ?>
      <?php if ($screen['resolution']): ?>
      <div class="screen-info-row"><span class="screen-info-label">Résolution :</span><span class="screen-info-value"><?= htmlspecialchars($screen['resolution']) ?></span></div>
      <?php endif; ?>
      <div class="screen-info-row"><span class="screen-info-label">Orientation :</span><span class="screen-info-value"><?= $screen['orientation'] === 'landscape' ? 'Paysage' : 'Portrait' ?></span></div>
      <?php if ($screen['os']): ?>
      <div class="screen-info-row"><span class="screen-info-label">OS :</span><span class="screen-info-value"><?= htmlspecialchars($screen['os']) ?></span></div>
      <?php endif; ?>
      <?php if ($screen['software_version']): ?>
      <div class="screen-info-row"><span class="screen-info-label">Version du logiciel :</span><span class="screen-info-value"><?= htmlspecialchars($screen['software_version']) ?></span></div>
      <?php endif; ?>
    </div>

    <!-- Action buttons -->
    <div class="screen-card-actions">
      <button class="screen-action-btn" title="Logs" onclick="openLogs(<?= $screen['id'] ?>)">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/><line x1="9" y1="3" x2="9" y2="21"/></svg>
      </button>
      <button class="screen-action-btn" title="Prévisualiser" onclick="previewScreen(<?= $screen['id'] ?>)">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
      </button>
      <button class="screen-action-btn" title="QR Code" onclick="openQrCode(<?= $screen['id'] ?>)">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
      </button>
      <button class="screen-action-btn" title="Paramètres" onclick="openScreenSettings(<?= $screen['id'] ?>)">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/></svg>
      </button>
      <button class="screen-action-btn" title="Informations" onclick="openScreenInfo(<?= $screen['id'] ?>)">
        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
      </button>
    </div>
  </div>
  <?php endforeach; ?>
<?php endif; ?>
</div>

<!-- ══ MODALS ══════════════════════════════════════════════════ -->

<!-- Add Demo Screen -->
<div class="modal-overlay" id="modalAddDemo" style="display:none;">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header">
      <div class="modal-title">Ajouter un écran démo</div>
      <button class="modal-close" onclick="closeModal('modalAddDemo')">×</button>
    </div>
    <form id="formAddDemo">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px; margin-bottom:12px;">
        <div class="form-input-floating">
          <input type="text" name="name" placeholder=" " required>
          <label>Nom de l'écran</label>
        </div>
        <div class="form-input-floating">
          <select name="orientation">
            <option value="landscape">Paysage</option>
            <option value="portrait">Portrait</option>
          </select>
          <label>Orientation</label>
        </div>
      </div>
      <div class="form-input-floating">
        <select name="duration">
          <option value="30">30min</option>
          <option value="60">1h</option>
          <option value="120">2h</option>
          <option value="480">8h</option>
          <option value="1440">24h</option>
        </select>
        <label>Durée de validité</label>
      </div>
      <div class="form-input-floating">
        <select name="catalog_type">
          <option value="catalog">Catalogue</option>
        </select>
        <label>Catalogue</label>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddDemo')">ANNULER</button>
        <button type="submit" class="btn btn-primary">AJOUTER</button>
      </div>
    </form>
  </div>
</div>

<!-- Demo Screen Created — Success Modal -->
<div class="modal-overlay" id="modalDemoSuccess" style="display:none;">
  <div class="modal" style="max-width:400px; text-align:center;">
    <div class="modal-header" style="justify-content:center; border-bottom:none; padding-bottom:0;">
      <div class="modal-title">✅ Écran démo créé !</div>
    </div>
    <div style="padding:8px 24px 20px;">
      <p style="font-size:13px; color:var(--text-muted); margin-bottom:16px;">Votre clé de licence a été générée automatiquement. Saisissez-la dans l'APK Android pour connecter cet écran.</p>
      <div id="demoLicenseCode" style="background:var(--bg-input); border-radius:10px; padding:18px; font-family:monospace; font-size:24px; font-weight:700; letter-spacing:4px; color:var(--accent); margin-bottom:8px; cursor:pointer;" onclick="copyDemoCode()" title="Cliquer pour copier">—</div>
      <p style="font-size:11px; color:var(--text-muted);">Cliquez sur le code pour le copier</p>
    </div>
    <div class="modal-footer" style="justify-content:center; gap:10px;">
      <button class="btn btn-primary" onclick="copyDemoCode()">📋 COPIER LE CODE</button>
      <button class="btn btn-secondary" onclick="closeModal('modalDemoSuccess'); location.reload();">FERMER</button>
    </div>
  </div>
</div>

<!-- QR Code Modal -->
<div class="modal-overlay" id="modalQrCode" style="display:none;">
  <div class="modal" style="max-width:480px; text-align:center;">
    <div class="modal-header">
      <div class="modal-title">Accéder à l'écran depuis un appareil mobile</div>
      <button class="modal-close" onclick="closeModal('modalQrCode')">×</button>
    </div>
    <div style="padding:20px 0;">
      <img id="qrCodeImg" src="" alt="QR Code" style="width:220px;height:220px;border-radius:12px;background:white;padding:12px;">
    </div>
    <div class="form-input-floating" style="display:flex; align-items:center; gap:8px;">
      <input type="text" id="qrCodeUrl" value="" readonly class="form-input" style="flex:1;">
      <label>Lien de l'écran</label>
    </div>
    <div class="modal-footer" style="justify-content:center; margin-top:16px;">
      <button class="btn btn-primary" onclick="downloadQrCode()">TÉLÉCHARGER LE QR CODE</button>
    </div>
  </div>
</div>

<!-- Logs Modal -->
<div class="modal-overlay" id="modalLogs" style="display:none;">
  <div class="modal" style="max-width:800px;">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="logsTitle">Logs</div>
        <div style="display:flex; gap:8px; margin-top:8px;">
          <span style="width:12px;height:12px;background:var(--red);border-radius:50%;display:inline-block;"></span>
          <span style="width:12px;height:12px;background:var(--yellow);border-radius:50%;display:inline-block;"></span>
          <span style="width:12px;height:12px;background:var(--green);border-radius:50%;display:inline-block;"></span>
          <span style="font-size:11px;color:var(--text-muted);margin-left:8px;">Différent automatique</span>
        </div>
      </div>
      <button class="modal-close" onclick="closeModal('modalLogs')">×</button>
    </div>
    <div class="logs-terminal" id="logsContent">Chargement des logs...</div>
  </div>
</div>

<!-- Screen Settings Modal -->
<div class="modal-overlay" id="modalScreenSettings" style="display:none;">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <div class="modal-title" id="settingsTitle">Gestion de l'écran</div>
      <button class="modal-close" onclick="closeModal('modalScreenSettings')">×</button>
    </div>

    <div style="display:grid; grid-template-columns:1fr auto; gap:16px; align-items:center; padding:16px 0; border-bottom:1px solid var(--border); margin-bottom:16px;">
      <div>
        <div style="font-size:14px; font-weight:600; margin-bottom:12px;">Gestion de la licence</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
          <span style="color:var(--text-muted);">Licence active</span><span id="sLicense" class="font-bold"></span>
          <span style="color:var(--text-muted);">Dernier lancement</span><span id="sLastLaunch"></span>
        </div>
      </div>
      <div style="display:flex; flex-direction:column; gap:8px;">
        <button class="btn btn-danger btn-sm" onclick="disconnectScreen()">↪ DÉCONNECTER L'ÉCRAN</button>
        <button class="btn btn-danger btn-sm" onclick="formatScreen()">🖥 FORMATER L'ÉCRAN</button>
      </div>
    </div>

    <div style="display:grid; grid-template-columns:1fr auto; gap:16px; align-items:center; padding:16px 0; border-bottom:1px solid var(--border); margin-bottom:16px;">
      <div>
        <div style="font-size:14px; font-weight:600; margin-bottom:12px;">Gestion du stockage</div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
          <span style="color:var(--text-muted);">Stockage utilisé sur l'écran</span><span id="sStorageScreen"></span>
          <span style="color:var(--text-muted);">Stockage utilisé par la licence</span><span id="sStorageLicense"></span>
          <span style="color:var(--text-muted);">Stockage disponible sur l'écran</span><span id="sStorageAvailable"></span>
        </div>
      </div>
      <div style="display:flex; flex-direction:column; gap:8px;">
        <button class="btn btn-primary btn-sm" onclick="cleanStorage()">🗑 NETTOYER LE STOCKAGE</button>
        <button class="btn btn-danger btn-sm" onclick="clearLicenseContent()">✕ EFFACER LE CONTENU LICENCE</button>
      </div>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center;">
      <div style="font-size:14px; font-weight:600;">Gestion de l'application</div>
      <button class="btn btn-primary btn-sm" onclick="restartApp()">↻ REDÉMARRER L'APPLICATION</button>
    </div>

    <input type="hidden" id="currentScreenId">
  </div>
</div>

<!-- Screen Info Modal -->
<div class="modal-overlay" id="modalScreenInfo" style="display:none;">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div class="modal-title" id="infoTitle">Informations sur l'écran</div>
      <button class="modal-close" onclick="closeModal('modalScreenInfo')">×</button>
    </div>

    <div style="display:flex; justify-content:center; margin-bottom:16px;">
      <div style="width:12px;height:12px;border-radius:50%;margin-top:4px;" id="infoStatus"></div>
      <span id="infoStatusText" style="font-size:13px; color:var(--text-muted); margin-left:8px;"></span>
    </div>

    <div class="card" style="padding:16px; margin-bottom:16px;">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
        <span style="color:var(--text-muted);">Fabricant</span><span id="iManufacturer"></span>
        <span style="color:var(--text-muted);">Numéro du modèle</span><span id="iModel"></span>
        <span style="color:var(--text-muted);">Numéro de série</span><span id="iSerial"></span>
        <span style="color:var(--text-muted);">Identifiant Android</span><span id="iAndroid"></span>
        <span style="color:var(--text-muted);">Firmware</span><span id="iFirmware"></span>
      </div>
    </div>

    <div style="font-size:13px; text-align:center; color:var(--text-muted); margin-bottom:16px;" id="iStorage"></div>

    <div style="font-size:12px; color:var(--text-muted); text-align:center; margin-bottom:16px;">Informations sur le logiciel</div>
    <div class="card" style="padding:16px; margin-bottom:16px;">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
        <span style="color:var(--text-muted);">Licence active</span><span id="iLicense"></span>
        <span style="color:var(--text-muted);">Version du logiciel</span><span id="iVersion"></span>
        <span style="color:var(--text-muted);">Dernier lancement</span><span id="iLastLaunch"></span>
      </div>
    </div>

    <div style="font-size:12px; color:var(--text-muted); text-align:center; margin-bottom:16px;">Informations générales</div>
    <div class="card" style="padding:16px; margin-bottom:16px;">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
        <span style="color:var(--text-muted);">Nom de l'écran</span><span id="iName"></span>
        <span style="color:var(--text-muted);">Emplacement</span><span id="iLocation" style="color:var(--text-muted);">Non renseigné</span>
      </div>
    </div>

    <div style="font-size:12px; color:var(--text-muted); text-align:center; margin-bottom:16px;">Informations sur l'écran</div>
    <div class="card" style="padding:16px;">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:8px; font-size:13px;">
        <span style="color:var(--text-muted);">Résolution de l'écran</span><span id="iResolution"></span>
        <span style="color:var(--text-muted);">Orientation de l'écran</span><span id="iOrientation"></span>
      </div>
    </div>
  </div>
</div>

<script>
let currentQrData = null;

// Add demo screen
document.getElementById('formAddDemo').addEventListener('submit', async function(e) {
  e.preventDefault();
  const fd = new FormData(this);
  const res = await apiPost('/manage/screens/demo/add', fd);
  if (res.success) {
    closeModal('modalAddDemo');
    document.getElementById('demoLicenseCode').textContent = res.license_code || '—';
    openModal('modalDemoSuccess');
  } else {
    toast(res.error || 'Erreur', 'error');
  }
});

function copyDemoCode() {
  const code = document.getElementById('demoLicenseCode').textContent;
  navigator.clipboard.writeText(code).then(() => toast('Code copié !'));
}

async function deleteScreen(id) {
  if (!await confirmDelete('Supprimer cet écran ?')) return;
  const res = await apiPost(`/manage/screens/${id}/delete`, new FormData());
  if (res.success) { toast('Écran supprimé'); location.reload(); }
}

function toggleScreenInfo(id) {
  const info = document.getElementById(`screenInfo${id}`);
  const arrow = document.getElementById(`screenInfoArrow${id}`);
  const show = info.style.display === 'none';
  info.style.display = show ? 'block' : 'none';
  arrow.style.transform = show ? 'rotate(180deg)' : '';
}

async function openQrCode(id) {
  const data = await apiGet(`/manage/screens/${id}/qrcode`);
  if (!data.success) { toast('Erreur', 'error'); return; }
  document.getElementById('qrCodeImg').src = data.qr;
  document.getElementById('qrCodeUrl').value = data.url;
  currentQrData = data;
  openModal('modalQrCode');
}

function downloadQrCode() {
  if (!currentQrData) return;
  window.open(currentQrData.qr, '_blank');
}

async function openLogs(id) {
  openModal('modalLogs');
  document.getElementById('logsContent').innerHTML = '<span style="color:var(--text-muted)">Chargement...</span>';
  const data = await apiGet(`/manage/screens/${id}/logs`);
  const logs = data.logs || [];
  document.getElementById('logsTitle').textContent = `Logs — ${data.screen?.name || ''}`;
  if (!logs.length) {
    document.getElementById('logsContent').innerHTML = '<span style="color:var(--text-muted)">Aucun log disponible</span>';
    return;
  }
  document.getElementById('logsContent').innerHTML = logs.map(l =>
    `<div class="log-line log-${l.level}">[${l.created_at}] ${l.message}</div>`
  ).join('');
}

async function openScreenSettings(id) {
  const data = await apiGet(`/manage/screens/${id}/settings`);
  document.getElementById('currentScreenId').value = id;
  document.getElementById('settingsTitle').textContent = `Gestion de l'écran ${data.name || ''}`;
  document.getElementById('sLicense').textContent = data.license_code || 'Non assignée';
  document.getElementById('sLastLaunch').textContent = data.last_launch || '—';
  document.getElementById('sStorageScreen').textContent = formatBytes(parseInt(data.storage_used_screen || 0));
  document.getElementById('sStorageLicense').textContent = formatBytes(parseInt(data.storage_used_license || 0));
  document.getElementById('sStorageAvailable').textContent = formatBytes(parseInt(data.storage_available || 0));
  openModal('modalScreenSettings');
}

async function openScreenInfo(id) {
  const d = await apiGet(`/manage/screens/${id}/info`);
  document.getElementById('infoTitle').textContent = `Informations sur l'écran ${d.name || ''}`;
  document.getElementById('infoStatus').style.background = d.status === 'online' ? 'var(--green)' : 'var(--red)';
  document.getElementById('infoStatusText').textContent = d.os || '';
  document.getElementById('iManufacturer').textContent = d.manufacturer || 'Non disponible';
  document.getElementById('iModel').textContent = d.model_number || 'Non disponible';
  document.getElementById('iSerial').textContent = d.serial_number || 'Non disponible';
  document.getElementById('iAndroid').textContent = d.android_id || 'Non disponible';
  document.getElementById('iFirmware').textContent = d.firmware || 'Non disponible';
  document.getElementById('iStorage').textContent = `Espace contenu local (licence ${d.license_code || ''}) : ${formatBytes(parseInt(d.storage_used_license || 0))}`;
  document.getElementById('iLicense').textContent = d.license_code || '—';
  document.getElementById('iVersion').textContent = d.software_version || '—';
  document.getElementById('iLastLaunch').textContent = d.last_launch || '—';
  document.getElementById('iName').textContent = d.name || '—';
  document.getElementById('iResolution').textContent = d.resolution || '—';
  document.getElementById('iOrientation').textContent = d.orientation === 'landscape' ? 'Paysage' : 'Portrait';
  openModal('modalScreenInfo');
}

async function disconnectScreen() {
  const id = document.getElementById('currentScreenId').value;
  if (!await confirmDelete('Déconnecter cet écran ?')) return;
  const res = await apiPost(`/manage/screens/${id}/disconnect`, new FormData());
  if (res.success) { toast('Écran déconnecté'); closeModal('modalScreenSettings'); location.reload(); }
}
async function formatScreen() {
  const id = document.getElementById('currentScreenId').value;
  if (!await confirmDelete('Formater cet écran ? Toutes les données seront effacées.')) return;
  const res = await apiPost(`/manage/screens/${id}/format`, new FormData());
  if (res.success) { toast('Écran formaté'); closeModal('modalScreenSettings'); }
}
async function restartApp() {
  const id = document.getElementById('currentScreenId').value;
  const res = await apiPost(`/manage/screens/${id}/restart`, new FormData());
  toast(res.message || 'Commande envoyée');
}
async function cleanStorage() {
  const id = document.getElementById('currentScreenId').value;
  const res = await apiPost(`/manage/screens/${id}/clean-storage`, new FormData());
  if (res.success) { toast('Stockage nettoyé'); location.reload(); }
}
async function clearLicenseContent() {
  const id = document.getElementById('currentScreenId').value;
  if (!await confirmDelete('Effacer le contenu de la licence ?')) return;
  toast('Contenu effacé');
}
async function previewScreen(id) {
  const d = await apiGet(`/manage/screens/${id}/preview`);
  if (d.token) window.open(`/viewer?token=${d.token}`, '_blank');
}

function formatBytes(bytes) {
  if (bytes <= 0) return '0 Bytes';
  if (bytes >= 1073741824) return (bytes/1073741824).toFixed(2)+' GB';
  if (bytes >= 1048576) return (bytes/1048576).toFixed(2)+' MB';
  return (bytes/1024).toFixed(2)+' KB';
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
