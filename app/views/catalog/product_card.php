<?php
$fileIcon = match($prod['file_mime'] ?? '') {
    'video/mp4' => '🎬',
    'application/pdf' => '📄',
    default => '🖼️'
};
if ($prod['file_type'] === 'url') $fileIcon = '🌐';
?>
<div class="catalog-card" data-id="<?= $prod['id'] ?>" data-type="product" data-name="<?= htmlspecialchars($prod['name']) ?>" draggable="true">
  <?php if ($prod['thumbnail']): ?>
    <img src="/public/uploads/<?= htmlspecialchars($prod['thumbnail']) ?>" class="catalog-card-img" alt="">
  <?php elseif ($prod['file_path'] && in_array($prod['file_mime'] ?? '', ['image/jpeg','image/png','image/jpg'])): ?>
    <img src="/public/uploads/<?= htmlspecialchars($prod['file_path']) ?>" class="catalog-card-img" alt="">
  <?php else: ?>
    <div class="catalog-card-placeholder" style="background:var(--bg-input); font-size:32px; flex-direction:column; gap:8px;">
      <span><?= $fileIcon ?></span>
      <span style="font-size:11px; color:var(--text-muted);"><?= htmlspecialchars(strtoupper($prod['file_type'])) ?></span>
    </div>
  <?php endif; ?>

  <div class="catalog-card-overlay">
    <div>
      <div class="catalog-card-label"><?= htmlspecialchars($prod['name']) ?></div>
      <div class="catalog-card-type">Produit <?= strtoupper($prod['file_type'] === 'url' ? 'URL' : ($prod['file_mime'] ?? '')) ?></div>
    </div>
    <div>
      <?php if ($prod['status'] === 'active'): ?>
        <span class="badge badge-success" style="font-size:9px;">Actif</span>
      <?php else: ?>
        <span class="badge badge-danger" style="font-size:9px;">Inactif</span>
      <?php endif; ?>
    </div>
  </div>

  <div class="catalog-card-actions">
    <div style="position:relative;">
      <button class="btn btn-secondary btn-sm btn-icon" style="background:rgba(0,0,0,0.5);border:none;" onclick="toggleCardMenu(event, 'prodMenu<?= $prod['id'] ?>')">⋮</button>
      <div class="catalog-card-menu" id="prodMenu<?= $prod['id'] ?>" style="display:none;">
        <div class="catalog-card-menu-item" onclick="openEditProduct(<?= $prod['id'] ?>)">
          ✏️ Modifier
        </div>
        <div class="catalog-card-menu-item" onclick="duplicateProduct(<?= $prod['id'] ?>)">
          📋 Dupliquer
        </div>
        <div class="catalog-card-menu-item" onclick="openCertModal(<?= $prod['id'] ?>)">
          🏆 Certificat
        </div>
        <?php if ($prod['file_type'] === 'media' && $prod['file_path']): ?>
        <div class="catalog-card-menu-item" onclick="window.open('/public/uploads/<?= htmlspecialchars($prod['file_path']) ?>','_blank')">
          👁️ Visualiser
        </div>
        <?php endif; ?>
        <div class="catalog-card-menu-item danger" onclick="archiveProduct(<?= $prod['id'] ?>)">
          🗑️ Archiver
        </div>
      </div>
    </div>
  </div>
</div>
