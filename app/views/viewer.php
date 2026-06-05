<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>MultiApp Viewer</title>
<style>
* { box-sizing: border-box; margin: 0; padding: 0; }
body { background: #1a1b2e; color: white; font-family: 'Inter', sans-serif; min-height: 100vh; }
:root { --accent: <?= htmlspecialchars($settings['filter_color'] ?? '#6b6ef9') ?>; }

.viewer-header { background: rgba(0,0,0,0.5); padding: 16px 24px; display: flex; align-items: center; justify-content: space-between; }
.viewer-title { font-size: 18px; font-weight: 700; }

.cat-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: 16px; padding: 24px; }
.cat-card { background: var(--accent); border-radius: 12px; padding: 24px; text-align: center; cursor: pointer; transition: all 0.2s; aspect-ratio: 1; display: flex; align-items: center; justify-content: center; font-size: 16px; font-weight: 700; text-transform: uppercase; }
.cat-card:hover { transform: scale(1.05); }
.cat-card img { width: 100%; height: 100%; object-fit: cover; border-radius: 12px; }

.prod-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; padding: 24px; }
.prod-card { background: #1e2040; border: 1px solid #2e3060; border-radius: 12px; overflow: hidden; cursor: pointer; }
.prod-card-img { height: 140px; object-fit: cover; width: 100%; }
.prod-card-title { padding: 12px; font-size: 14px; font-weight: 600; }

.back-btn { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.2); color: white; padding: 8px 16px; border-radius: 8px; cursor: pointer; font-size: 13px; }

.content-view { padding: 24px; }
.content-iframe { width: 100%; height: 70vh; border: none; border-radius: 12px; }

.qr-badge { position: fixed; bottom: 20px; right: 20px; background: white; border-radius: 12px; padding: 12px; }
.qr-badge img { width: 80px; height: 80px; }

.datetime-widget { font-size: 13px; color: rgba(255,255,255,0.7); }
</style>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

<div id="app">
  <!-- Header -->
  <div class="viewer-header">
    <?php if (!empty($settings['logo'])): ?>
    <img src="/public/uploads/<?= htmlspecialchars($settings['logo']) ?>" style="height:40px;">
    <?php else: ?>
    <div class="viewer-title">MultiApp</div>
    <?php endif; ?>
    <div class="datetime-widget" id="datetime"></div>
    <?php if (!empty($settings['custom_message'])): ?>
    <div style="font-size:13px; color:rgba(255,255,255,0.8);"><?= htmlspecialchars($settings['custom_message']) ?></div>
    <?php endif; ?>
  </div>

  <!-- Catalog view (default) -->
  <div id="viewCatalog">
    <div class="cat-grid">
      <?php foreach ($categories as $i => $cat): ?>
      <div class="cat-card" onclick="showCategory(<?= htmlspecialchars(json_encode($cat)) ?>)"
           style="background:<?= ['#6b6ef9','#22c55e','#ef4444','#f59e0b','#06b6d4','#8b5cf6'][$i % 6] ?>;">
        <?php if ($cat['image']): ?>
          <img src="/public/uploads/<?= htmlspecialchars($cat['image']) ?>" alt="">
        <?php else: ?>
          <?= htmlspecialchars($cat['name']) ?>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Products view -->
  <div id="viewProducts" style="display:none;">
    <div style="padding:20px 24px 0; display:flex; align-items:center; gap:12px;">
      <button class="back-btn" onclick="showCatalog()">← Retour</button>
      <span id="catTitle" style="font-size:18px; font-weight:700;"></span>
    </div>
    <div class="prod-grid" id="prodGrid"></div>
  </div>

  <!-- Content view -->
  <div id="viewContent" style="display:none;">
    <div style="padding:20px 24px 0; display:flex; gap:12px; align-items:center;">
      <button class="back-btn" onclick="backToProducts()">← Retour</button>
      <span id="prodTitle" style="font-size:16px; font-weight:600;"></span>
    </div>
    <div class="content-view" id="contentArea"></div>
  </div>
</div>

<?php if (!empty($settings['widget_qr'])): ?>
<div class="qr-badge">
  <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=<?= urlencode(APP_URL . '/viewer?token=' . $screen['token']) ?>" alt="QR">
</div>
<?php endif; ?>

<script>
let currentCategory = null;
const token = '<?= htmlspecialchars($screen['token']) ?>';

// DateTime widget
function updateDatetime() {
  const now = new Date();
  document.getElementById('datetime').textContent = now.toLocaleDateString('fr-FR', {weekday:'long',day:'numeric',month:'long',year:'numeric'}) + ' — ' + now.toLocaleTimeString('fr-FR');
}
updateDatetime();
setInterval(updateDatetime, 1000);

function showCategory(cat) {
  currentCategory = cat;
  document.getElementById('catTitle').textContent = cat.name;
  document.getElementById('viewCatalog').style.display = 'none';
  document.getElementById('viewProducts').style.display = 'block';
  document.getElementById('viewContent').style.display = 'none';

  // Track click
  fetch('/api/track', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ token, item_type:'category', item_id: cat.id, item_name: cat.name }) });

  const grid = document.getElementById('prodGrid');
  grid.innerHTML = (cat.products || []).map(p => `
    <div class="prod-card" onclick="showProduct(${JSON.stringify(p).replace(/"/g,'&quot;')})">
      ${p.thumbnail_url || p.file_url ? `<img class="prod-card-img" src="${p.thumbnail_url || p.file_url}" alt="">` : `<div style="height:140px;background:#12132a;display:flex;align-items:center;justify-content:center;font-size:36px;">${p.file_type === 'url' ? '🌐' : '📄'}</div>`}
      <div class="prod-card-title">${p.name}</div>
    </div>
  `).join('') || '<div style="padding:40px;color:rgba(255,255,255,0.4);text-align:center;">Aucun produit</div>';
}

function showProduct(prod) {
  document.getElementById('viewProducts').style.display = 'none';
  document.getElementById('viewContent').style.display = 'block';
  document.getElementById('prodTitle').textContent = prod.name;

  fetch('/api/track', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ token, item_type:'product', item_id: prod.id, item_name: prod.name }) });

  const area = document.getElementById('contentArea');
  if (prod.file_type === 'url') {
    area.innerHTML = `<iframe src="${prod.url}" class="content-iframe" sandbox="allow-scripts allow-same-origin allow-forms allow-popups"></iframe>`;
  } else if (prod.file_url) {
    const mime = prod.file_mime || '';
    if (mime.startsWith('image')) {
      area.innerHTML = `<img src="${prod.file_url}" style="max-width:100%;max-height:80vh;border-radius:12px;display:block;margin:0 auto;">`;
    } else if (mime === 'video/mp4') {
      area.innerHTML = `<video src="${prod.file_url}" controls autoplay style="width:100%;border-radius:12px;max-height:70vh;"></video>`;
    } else if (mime === 'application/pdf') {
      area.innerHTML = `<iframe src="${prod.file_url}" class="content-iframe"></iframe>`;
    }
  } else {
    area.innerHTML = '<div style="text-align:center;padding:40px;color:rgba(255,255,255,0.4);">Contenu non disponible</div>';
  }
}

function showCatalog() {
  document.getElementById('viewCatalog').style.display = 'block';
  document.getElementById('viewProducts').style.display = 'none';
  document.getElementById('viewContent').style.display = 'none';
}
function backToProducts() {
  document.getElementById('viewProducts').style.display = 'block';
  document.getElementById('viewContent').style.display = 'none';
  document.getElementById('contentArea').innerHTML = '';
}

// Heartbeat
setInterval(() => {
  fetch('/api/v1/screen/heartbeat', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ token }) });
}, 30000);
</script>
</body>
</html>
