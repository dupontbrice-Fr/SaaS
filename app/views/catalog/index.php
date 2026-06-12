<?php
$isArchive       = ($subView === 'archives');
$insideCategory  = !is_null($currentCategory);
$insideCatalog   = !is_null($currentCatalog);  // set when browsing a specific catalog
$currentCatId    = $currentCategory['id'] ?? null;
$catalogId       = $currentCatalog['id'] ?? null;
$filter          = $filter ?? 'all';
$returnUrl       = $insideCategory
    ? ($currentCategory['parent_id'] ? '/manage/catalog/browse/' . $currentCategory['parent_id'] : ($insideCatalog ? '/manage/catalog/categories/' . $catalogId : '/manage/catalog'))
    : ($insideCatalog ? '/manage/catalog' : '/manage/catalog');

$palette = ['#6b6ef9','#22c55e','#ef4444','#f59e0b','#06b6d4','#8b5cf6','#ec4899','#14b8a6','#f97316'];
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:4px;">
  <h1 class="page-title" style="margin-bottom:0;">Gestion du catalogue</h1>
</div>

<!-- Breadcrumb -->
<div style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-muted);margin-bottom:16px;flex-wrap:wrap;">
  <a href="/manage/catalog" style="color:var(--text-muted);text-decoration:none;display:flex;align-items:center;gap:4px;">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/></svg>
    Accueil
  </a>
  <?php if ($insideCatalog): ?>
    <span>›</span>
    <a href="/manage/catalog/categories/<?= $catalogId ?>" style="color:var(--text-muted);text-decoration:none;">
      <?= htmlspecialchars($currentCatalog['name']) ?>
    </a>
  <?php endif; ?>
  <?php foreach ($breadcrumb as $bc): ?>
    <span>›</span>
    <a href="/manage/catalog/browse/<?= $bc['id'] ?>" style="color:var(--text-muted);text-decoration:none;display:flex;align-items:center;gap:4px;">
      <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
      <?= htmlspecialchars($bc['name']) ?>
    </a>
  <?php endforeach; ?>
</div>

<?php if (!$insideCategory && !$insideCatalog): ?>
<!-- ══════════════════════════════════════════════════════════════
     TOP LEVEL: Device Panel + Catalog List
     ══════════════════════════════════════════════════════════════ -->

<!-- Catalog List -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;">
  <span style="font-size:14px;font-weight:600;">Catalogues</span>
  <button class="btn btn-primary btn-sm" onclick="openAddCatalogModal()">+ Nouveau catalogue</button>
</div>

<?php if (empty($catalogs)): ?>
<div class="card" style="text-align:center;padding:40px 20px;margin-bottom:20px;">
  <div style="font-size:36px;margin-bottom:12px;">📂</div>
  <div style="font-size:14px;color:var(--text-muted);">Aucun catalogue — créez-en un pour organiser votre contenu par écran.</div>
</div>
<?php else: ?>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;margin-bottom:20px;">
  <?php foreach ($catalogs as $i => $cat): ?>
  <div class="catalog-card" style="cursor:pointer;min-height:120px;" onclick="location='/manage/catalog/categories/<?= $cat['id'] ?>'">
    <div class="catalog-card-placeholder" style="background:<?= $palette[$i % count($palette)] ?>;font-size:14px;">
      <?= htmlspecialchars(strtoupper(substr($cat['name'], 0, 2))) ?>
    </div>
    <div class="catalog-card-overlay">
      <div>
        <div class="catalog-card-label"><?= htmlspecialchars($cat['name']) ?></div>
        <div class="catalog-card-type"><?= (int)$cat['cat_count'] ?> catégorie<?= $cat['cat_count'] != 1 ? 's' : '' ?></div>
      </div>
    </div>
    <div class="catalog-card-actions" style="z-index:2;position:absolute;top:8px;right:8px;">
      <div style="position:relative;">
        <button class="btn btn-secondary btn-sm btn-icon" style="background:rgba(0,0,0,0.55);border:none;"
                onclick="event.preventDefault();event.stopPropagation();toggleCardMenu('catMenu<?= $cat['id'] ?>')">⋮</button>
        <div class="catalog-card-menu" id="catMenu<?= $cat['id'] ?>" style="display:none;">
          <div class="catalog-card-menu-item" onclick="event.stopPropagation();openEditCatalogModal(<?= $cat['id'] ?>)">✏️ Modifier</div>
          <div class="catalog-card-menu-item danger" onclick="event.stopPropagation();deleteCatalog(<?= $cat['id'] ?>)">🗑️ Supprimer</div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- Sans-catalogue shortcut -->
<div style="margin-bottom:20px;">
  <a href="/manage/catalog/categories/0" class="btn btn-secondary btn-sm">
    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>
    Voir les catégories sans catalogue
  </a>
</div>

<?php else: ?>
<!-- ══════════════════════════════════════════════════════════════
     INSIDE A CATALOG (or category) — existing catalog management
     ══════════════════════════════════════════════════════════════ -->

<!-- FILTER TABS -->
<div class="tabs">
  <?php
  $baseUrl = $insideCategory ? '/manage/catalog/browse/' . $currentCatId : ($insideCatalog ? '/manage/catalog/categories/' . $catalogId : '/manage/catalog');
  $subParam = $isArchive ? '&sub=archives' : '';
  ?>
  <a href="<?= $baseUrl ?>?filter=all<?= $subParam ?>"
     class="tab <?= $filter==='all' && !$isArchive ? 'active' : '' ?>"
     onclick="setFilter('all');return false;">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
    CATÉGORIE
  </a>
  <?php if ($insideCategory): ?>
  <a href="<?= $baseUrl ?>?filter=products<?= $subParam ?>"
     class="tab <?= $filter==='products' ? 'active' : '' ?>"
     onclick="setFilter('products');return false;">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
    PRODUIT
  </a>
  <?php endif; ?>
  <span class="tab" onclick="enableReorder()">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="9" x2="21" y2="9"/><line x1="3" y1="15" x2="21" y2="15"/></svg>
    RÉORGANISER
  </span>
  <div style="margin-left:auto;display:flex;gap:6px;align-items:center;">
    <a href="<?= $baseUrl ?>" class="sub-tab <?= !$isArchive ? 'active' : '' ?>">Catalogue</a>
    <a href="<?= $baseUrl ?>?sub=archives" class="sub-tab <?= $isArchive ? 'active' : '' ?>">Archives</a>
  </div>
</div>

<?php if ($isArchive): ?>
<!-- ── ARCHIVES ── -->
<div style="color:var(--text-muted);font-size:13px;margin-bottom:16px;">Les éléments archivés peuvent être restaurés ou supprimés définitivement.</div>

<h3 style="font-size:14px;font-weight:600;color:var(--text-secondary);margin-bottom:10px;">Catégories archivées</h3>
<?php if (empty($categories)): ?>
  <p class="text-muted text-sm" style="margin-bottom:20px;">Aucune catégorie archivée.</p>
<?php else: ?>
<div class="card" style="margin-bottom:20px;">
  <table><thead><tr><th>Visuel</th><th>Titre</th><th>Archivée le</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($categories as $cat): ?>
  <tr>
    <td><?php if ($cat['image']): ?><img src="/public/uploads/<?= htmlspecialchars($cat['image']) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover;"><?php else: ?><div style="width:40px;height:40px;border-radius:6px;background:var(--accent);"></div><?php endif; ?></td>
    <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
    <td class="text-sm text-muted"><?= date('d/m/Y H:i',strtotime($cat['archived_at'])) ?></td>
    <td><div style="display:flex;gap:6px;"><button class="btn btn-secondary btn-sm" onclick="restoreCategory(<?= $cat['id'] ?>)">Restaurer</button><button class="btn btn-danger btn-sm" onclick="hardDeleteCategory(<?= $cat['id'] ?>)">Supprimer</button></div></td>
  </tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>

<h3 style="font-size:14px;font-weight:600;color:var(--text-secondary);margin-top:24px;margin-bottom:10px;">Produits archivés</h3>
<?php if (empty($products)): ?>
  <p class="text-muted text-sm" style="margin-bottom:20px;">Aucun produit archivé.</p>
<?php else: ?>
<div class="card" style="margin-bottom:20px;">
  <table><thead><tr><th>Visuel</th><th>Titre</th><th>Catégorie</th><th>Archivé le</th><th>Actions</th></tr></thead><tbody>
  <?php foreach ($products as $prod): ?>
  <?php
    $archFileIcon = match($prod['file_mime']??'') { 'video/mp4'=>'🎬','application/pdf'=>'📄',default=>'🖼️' };
    if ($prod['file_type']==='url') $archFileIcon='🌐';
  ?>
  <tr>
    <td><?php if ($prod['thumbnail']): ?><img src="/public/uploads/<?= htmlspecialchars($prod['thumbnail']) ?>" style="width:40px;height:40px;border-radius:6px;object-fit:cover;"><?php else: ?><div style="width:40px;height:40px;border-radius:6px;background:var(--bg-input);display:flex;align-items:center;justify-content:center;font-size:18px;"><?= $archFileIcon ?></div><?php endif; ?></td>
    <td><strong><?= htmlspecialchars($prod['name']) ?></strong></td>
    <td class="text-sm text-muted"><?= htmlspecialchars($prod['category_name'] ?? '—') ?></td>
    <td class="text-sm text-muted"><?= date('d/m/Y H:i',strtotime($prod['archived_at'])) ?></td>
    <td><div style="display:flex;gap:6px;"><button class="btn btn-secondary btn-sm" onclick="restoreProduct(<?= $prod['id'] ?>)">Restaurer</button><button class="btn btn-danger btn-sm" onclick="hardDeleteProduct(<?= $prod['id'] ?>)">Supprimer</button></div></td>
  </tr>
  <?php endforeach; ?>
  </tbody></table>
</div>
<?php endif; ?>

<?php else: ?>
<!-- ── CATALOGUE ── -->

<div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;align-items:center;">
  <?php if ($filter !== 'products'): ?>
  <button class="btn btn-primary" onclick="openAddCategoryModal()">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
    + CATÉGORIE
  </button>
  <?php endif; ?>
  <?php if ($insideCategory && $filter !== 'all' || $insideCategory): ?>
  <button class="btn btn-primary" onclick="openAddProductModal(<?= $currentCatId ?? 'null' ?>)">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14"/><path d="M5 12h14"/></svg>
    + PRODUIT
  </button>
  <?php endif; ?>
  <div style="flex:1;"></div>
  <input type="text" class="form-input" placeholder="🔍 Rechercher..." style="width:220px;" oninput="filterCards(this.value)">
</div>

<div style="display:flex;gap:8px;margin-bottom:16px;flex-wrap:wrap;">
  <?php if (!empty($categories)): ?>
  <span class="badge badge-info"><?= count($categories) ?> catégorie<?= count($categories)>1?'s':'' ?></span>
  <?php endif; ?>
  <?php if (!empty($products)): ?>
  <span class="badge badge-info"><?= count($products) ?> produit<?= count($products)>1?'s':'' ?></span>
  <?php endif; ?>
  <?php if ($insideCatalog && !$insideCategory && empty($categories)): ?>
  <span class="text-muted text-sm">Aucune catégorie — cliquez sur "+ CATÉGORIE" pour commencer</span>
  <?php elseif ($insideCategory && empty($categories) && empty($products)): ?>
  <span class="text-muted text-sm">Dossier vide — ajoutez une sous-catégorie ou un produit</span>
  <?php endif; ?>
</div>

<div id="catalogGrid" style="display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:16px;">

  <?php if ($filter !== 'products'): ?>
  <?php foreach ($categories as $i => $cat): ?>
  <div class="catalog-card cat-item" data-id="<?= $cat['id'] ?>" data-type="category" data-name="<?= htmlspecialchars($cat['name']) ?>" draggable="true" style="cursor:pointer;">
    <a href="/manage/catalog/browse/<?= $cat['id'] ?>" style="display:block;width:100%;height:100%;text-decoration:none;position:absolute;inset:0;z-index:1;"></a>
    <?php if ($cat['image']): ?>
      <img src="/public/uploads/<?= htmlspecialchars($cat['image']) ?>" class="catalog-card-img" alt="">
    <?php else: ?>
      <div class="catalog-card-placeholder" style="background:<?= $palette[$i % count($palette)] ?>;">
        <?= htmlspecialchars(strtoupper($cat['name'])) ?>
      </div>
    <?php endif; ?>
    <div class="catalog-card-overlay">
      <div>
        <div class="catalog-card-label"><?= htmlspecialchars($cat['name']) ?></div>
        <div class="catalog-card-type">Catégorie</div>
      </div>
      <span class="badge <?= $cat['status']==='active'?'badge-success':'badge-danger' ?>" style="font-size:9px;"><?= $cat['status']==='active'?'Actif':'Inactif' ?></span>
    </div>
    <div class="catalog-card-actions" style="z-index:2;position:absolute;top:8px;right:8px;">
      <div style="position:relative;">
        <button class="btn btn-secondary btn-sm btn-icon" style="background:rgba(0,0,0,0.55);border:none;"
                onclick="event.preventDefault();event.stopPropagation();toggleCardMenu('catMenu<?= $cat['id'] ?>')">⋮</button>
        <div class="catalog-card-menu" id="catMenu<?= $cat['id'] ?>" style="display:none;">
          <div class="catalog-card-menu-item" onclick="event.stopPropagation();openEditCategory(<?= $cat['id'] ?>)">✏️ Modifier</div>
          <div class="catalog-card-menu-item danger" onclick="event.stopPropagation();archiveCategory(<?= $cat['id'] ?>)">🗑️ Archiver</div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

  <?php if ($filter !== 'all' || $insideCategory): ?>
  <?php foreach ($products as $prod): ?>
  <?php
  $fileIcon = match($prod['file_mime']??'') { 'video/mp4'=>'🎬','application/pdf'=>'📄',default=>'🖼️' };
  if ($prod['file_type']==='url') $fileIcon='🌐';
  ?>
  <div class="catalog-card prod-item" data-id="<?= $prod['id'] ?>" data-type="product" data-name="<?= htmlspecialchars($prod['name']) ?>" draggable="true">
    <?php if ($prod['thumbnail']): ?>
      <img src="/public/uploads/<?= htmlspecialchars($prod['thumbnail']) ?>" class="catalog-card-img" alt="">
    <?php elseif ($prod['file_path'] && in_array($prod['file_mime']??'',['image/jpeg','image/png','image/jpg'])): ?>
      <img src="/public/uploads/<?= htmlspecialchars($prod['file_path']) ?>" class="catalog-card-img" alt="">
    <?php else: ?>
      <div class="catalog-card-placeholder" style="background:var(--bg-input);font-size:32px;flex-direction:column;gap:6px;">
        <span><?= $fileIcon ?></span>
        <span style="font-size:10px;color:var(--text-muted);"><?= strtoupper($prod['file_type']==='url'?'URL':($prod['file_mime']?explode('/',$prod['file_mime'])[1]??'':'-')) ?></span>
      </div>
    <?php endif; ?>
    <div class="catalog-card-overlay">
      <div>
        <div class="catalog-card-label"><?= htmlspecialchars($prod['name']) ?></div>
        <div class="catalog-card-type">Produit <?= $prod['file_type']==='url'?'URL':ucfirst(explode('/',$prod['file_mime']??'media')[1]??'Media') ?></div>
      </div>
      <span class="badge <?= $prod['status']==='active'?'badge-success':'badge-danger' ?>" style="font-size:9px;"><?= $prod['status']==='active'?'Actif':'Inactif' ?></span>
    </div>
    <div class="catalog-card-actions" style="z-index:2;position:absolute;top:8px;right:8px;">
      <div style="position:relative;">
        <button class="btn btn-secondary btn-sm btn-icon" style="background:rgba(0,0,0,0.55);border:none;"
                onclick="event.stopPropagation();toggleCardMenu('prodMenu<?= $prod['id'] ?>')">⋮</button>
        <div class="catalog-card-menu" id="prodMenu<?= $prod['id'] ?>" style="display:none;">
          <div class="catalog-card-menu-item" onclick="event.stopPropagation();openEditProduct(<?= $prod['id'] ?>)">✏️ Modifier</div>
          <div class="catalog-card-menu-item" onclick="event.stopPropagation();duplicateProduct(<?= $prod['id'] ?>)">📋 Dupliquer</div>
          <div class="catalog-card-menu-item" onclick="event.stopPropagation();openCertModal(<?= $prod['id'] ?>)">🏆 Certificat</div>
          <?php if ($prod['file_path']): ?>
          <div class="catalog-card-menu-item" onclick="event.stopPropagation();window.open('/public/uploads/<?= htmlspecialchars($prod['file_path']) ?>','_blank')">👁️ Visualiser</div>
          <?php elseif ($prod['url']): ?>
          <div class="catalog-card-menu-item" onclick="event.stopPropagation();window.open('<?= htmlspecialchars($prod['url']) ?>','_blank')">👁️ Prévisualiser</div>
          <?php endif; ?>
          <div class="catalog-card-menu-item danger" onclick="event.stopPropagation();archiveProduct(<?= $prod['id'] ?>)">🗑️ Archiver</div>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
  <?php endif; ?>

</div>

<?php if ($insideCatalog && !$insideCategory && !empty($categories)): ?>
<div style="margin-top:16px;font-size:12px;color:var(--text-muted);">💡 Cliquez sur une catégorie pour l'ouvrir et y ajouter des produits.</div>
<?php endif; ?>

<?php endif; // end !isArchive ?>
<?php endif; // end top-level vs inside-catalog ?>

<!-- ══ MODALS ════════════════════════════════════════════════════ -->

<!-- Modal: Add / Edit Catalog -->
<div class="modal-overlay" id="modalAddCatalog" style="display:none;">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <div class="modal-title" id="modalCatalogTitle">Créer un catalogue</div>
      <button class="modal-close" onclick="closeModal('modalAddCatalog')">×</button>
    </div>
    <form id="formCatalog">
      <input type="hidden" id="catalogEditId" value="">
      <div class="form-input-floating">
        <input type="text" id="catalogName" name="name" placeholder=" " required>
        <label>Nom du catalogue</label>
      </div>
      <div class="form-input-floating">
        <textarea id="catalogDesc" name="description" placeholder=" " rows="2" style="resize:vertical;"></textarea>
        <label>Description (optionnel)</label>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id="catalogSubmitBtn">CRÉER</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddCatalog')">ANNULER</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Add / Edit Category -->
<div class="modal-overlay" id="modalAddCategory" style="display:none;">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="modalCatTitle">Ajouter une catégorie</div>
        <div class="modal-subtitle" id="modalCatSubtitle">Attention, la catégorie sera aussi ajoutée dans vos sous organisations</div>
      </div>
      <button class="modal-close" onclick="closeModal('modalAddCategory')">×</button>
    </div>
    <form id="formCategory" enctype="multipart/form-data">
      <input type="hidden" id="catId"           name="cat_id"    value="">
      <input type="hidden" id="catParentId"    name="parent_id" value="<?= $currentCatId ?? '' ?>">
      <input type="hidden" id="catCatalogIdHidden" name="catalog_id" value="<?= $catalogId ?? '' ?>">
      <div id="catCatalogSelectWrap" style="display:none;margin-top:4px;">
        <div class="form-input-floating">
          <select id="catCatalogIdSelect">
            <option value="">Aucun catalogue</option>
            <?php foreach ($allCatalogs ?? [] as $cl): ?>
            <option value="<?= $cl['id'] ?>"><?= htmlspecialchars($cl['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <label>Ajouter à un catalogue</label>
        </div>
      </div>

      <input type="file" id="catImage" name="image" accept="image/jpeg,image/jpg,image/png" style="display:none;">
      <input type="hidden" id="catLibMediaId" name="library_media_id" value="">
      <div class="upload-zone mp-trigger" id="catPickerZone" onclick="openCatPicker()" style="cursor:pointer;">
        AJOUTER UN FICHIER<br><span style="font-weight:400;font-size:11px;">OU GLISSER DÉPOSER · DEPUIS LA BIBLIOTHÈQUE</span>
      </div>
      <div id="catImagePreview" class="upload-zone-preview" style="display:none;margin-bottom:12px;"></div>

      <div class="form-input-floating">
        <input type="text" id="catName" name="name" placeholder=" " required>
        <label>Titre</label>
      </div>
      <div class="form-input-floating">
        <select id="catStatus" name="status">
          <option value="active">Actif</option>
          <option value="inactive">Inactif</option>
        </select>
        <label>Statut</label>
      </div>
      <label class="form-check"><input type="checkbox" name="show_title" id="catShowTitle" checked><span class="form-check-label">Afficher le titre sur l'écran</span></label>
      <label class="form-check"><input type="checkbox" name="viewable_external" id="catViewExt" checked><span class="form-check-label">Visualisable depuis un appareil externe</span></label>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id="catSubmitBtn">AJOUTER</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddCategory')">ANNULER</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Add / Edit Product -->
<div class="modal-overlay" id="modalAddProduct" style="display:none;">
  <div class="modal" style="max-width:560px;">
    <div class="modal-header">
      <div>
        <div class="modal-title" id="modalProdTitle">Ajouter un produit</div>
        <div class="modal-subtitle">Attention, le produit sera aussi modifié dans vos sous organisations</div>
      </div>
      <button class="modal-close" onclick="closeModal('modalAddProduct')">×</button>
    </div>
    <form id="formProduct" enctype="multipart/form-data">
      <input type="hidden" id="prodId" name="prod_id" value="">

      <input type="file" id="prodFile" name="file" accept="image/*,video/mp4,application/pdf" style="display:none;">
      <input type="hidden" id="prodLibMediaId" name="library_media_id" value="">
      <div class="upload-zone mp-trigger" id="prodFilePickerZone" onclick="openProdFilePicker()" style="cursor:pointer;">
        AJOUTER UN FICHIER<br><span style="font-weight:400;font-size:11px;">OU GLISSER DÉPOSER · DEPUIS LA BIBLIOTHÈQUE · 20 MO (IMG), 1 GO (VIDÉO), 50 MO (PDF)</span>
      </div>
      <div id="prodFilePreview" class="upload-zone-preview" style="display:none;margin-bottom:8px;"></div>

      <input type="file" id="prodThumbnail" name="thumbnail" accept="image/*" style="display:none;">
      <input type="hidden" id="prodLibThumbId" name="library_thumb_id" value="">
      <div class="upload-zone mp-trigger" id="prodThumbPickerZone" onclick="openProdThumbPicker()" style="cursor:pointer;margin-top:8px;">
        AJOUTER UNE MINIATURE<br><span style="font-weight:400;font-size:11px;">OU GLISSER DÉPOSER · DEPUIS LA BIBLIOTHÈQUE · 20 MO MAX</span>
      </div>
      <div id="prodThumbPreview" class="upload-zone-preview" style="display:none;margin-bottom:8px;"></div>

      <div class="form-input-floating" style="margin-top:12px;">
        <input type="text" id="prodName" name="name" placeholder=" " required>
        <label>Titre</label>
      </div>
      <div class="form-input-floating">
        <select id="prodFileType" name="file_type" onchange="toggleProdFileType(this.value)">
          <option value="media">Media (JPG, PNG, JPEG, MP4, PDF)</option>
          <option value="url">Site Internet</option>
        </select>
        <label>Type de fichier produit</label>
      </div>
      <div id="prodUrlField" style="display:none;">
        <div class="form-input-floating">
          <input type="text" id="prodUrl" name="url" placeholder=" ">
          <label>Url du site</label>
        </div>
      </div>
      <div class="form-input-floating">
        <select id="prodCategoryId" name="category_id" required>
          <option value="">— Sélectionnez une catégorie —</option>
          <?php foreach ($allCategories as $cat): ?>
          <option value="<?= $cat['id'] ?>" <?= ($currentCatId == $cat['id'] ? 'selected' : '') ?>><?= htmlspecialchars($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
        <label>Catégorie *</label>
      </div>
      <div class="form-input-floating">
        <select id="prodStatus" name="status">
          <option value="active">Actif</option>
          <option value="inactive">Inactif</option>
        </select>
        <label>Statut</label>
      </div>
      <label class="form-check"><input type="checkbox" name="downloadable" checked><span class="form-check-label">Fichier téléchargeable</span></label>
      <label class="form-check"><input type="checkbox" name="show_title" checked><span class="form-check-label">Afficher le titre sur l'écran</span></label>
      <div id="prodUrlOptions" style="display:none;">
        <label class="form-check"><input type="checkbox" name="enable_cache" checked><span class="form-check-label">Activer le cache pour un produit url</span></label>
        <label class="form-check"><input type="checkbox" name="protected_nav" checked><span class="form-check-label">Navigation protégée sur l'url</span></label>
      </div>
      <label class="form-check"><input type="checkbox" name="viewable_external" checked><span class="form-check-label">Visualisable depuis un appareil externe</span></label>

      <div class="modal-footer">
        <button type="submit" class="btn btn-primary" id="prodSubmitBtn">AJOUTER</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalAddProduct')">ANNULER</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Certificate -->
<div class="modal-overlay" id="modalCertificate" style="display:none;">
  <div class="modal" style="max-width:400px;text-align:center;">
    <div class="modal-header"><div class="modal-title">Certificat d'affichage</div><button class="modal-close" onclick="closeModal('modalCertificate')">×</button></div>
    <p style="color:var(--text-secondary);margin-bottom:20px;">Générer et télécharger le certificat PDF pour ce produit.</p>
    <input type="hidden" id="certProductId">
    <div class="modal-footer" style="justify-content:center;">
      <button class="btn btn-primary" onclick="downloadCertificate()">⬇ TÉLÉCHARGER LE CERTIFICAT</button>
      <button class="btn btn-secondary" onclick="closeModal('modalCertificate')">ANNULER</button>
    </div>
  </div>
</div>

<style>
.catalog-card { position: relative; }
.catalog-card-actions { opacity: 0; transition: opacity 0.15s; }
.catalog-card:hover .catalog-card-actions { opacity: 1; }
.catalog-card-placeholder { display:flex;align-items:center;justify-content:center;text-align:center;padding:12px;font-size:13px;font-weight:700;color:white;letter-spacing:0.5px;text-transform:uppercase; }
</style>

<script>
// ── Media pickers ──
(function() {
  function setupDrop(zoneId, handler) {
    const z = document.getElementById(zoneId);
    if (!z) return;
    z.addEventListener('dragover', e => { e.preventDefault(); z.style.opacity = '0.7'; });
    z.addEventListener('dragleave', () => { z.style.opacity = '1'; });
    z.addEventListener('drop', e => {
      e.preventDefault(); z.style.opacity = '1';
      if (e.dataTransfer.files[0]) handler({ type: 'upload', file: e.dataTransfer.files[0] });
    });
  }
  setupDrop('catPickerZone',      applyCatMedia);
  setupDrop('prodFilePickerZone', applyProdFile);
  setupDrop('prodThumbPickerZone',applyProdThumb);
})();

function openCatPicker() {
  MediaPicker.open({ accept: 'image/jpeg,image/jpg,image/png', onSelect: applyCatMedia });
}
function applyCatMedia({ type, file, media }) {
  const inp = document.getElementById('catImage');
  const lid = document.getElementById('catLibMediaId');
  const prv = document.getElementById('catImagePreview');
  if (type === 'upload') { setFileInput(inp, file); lid.value = ''; showFilePreview(file, prv); }
  else                   { inp.value = ''; lid.value = media.id; showLibraryPreview(media, prv); }
}

function openProdFilePicker() {
  MediaPicker.open({ accept: 'image/*,video/mp4,application/pdf', onSelect: applyProdFile });
}
function applyProdFile({ type, file, media }) {
  const inp = document.getElementById('prodFile');
  const lid = document.getElementById('prodLibMediaId');
  const prv = document.getElementById('prodFilePreview');
  if (type === 'upload') { setFileInput(inp, file); lid.value = ''; showFilePreview(file, prv); }
  else                   { inp.value = ''; lid.value = media.id; showLibraryPreview(media, prv); }
}

function openProdThumbPicker() {
  MediaPicker.open({ accept: 'image/*', onSelect: applyProdThumb });
}
function applyProdThumb({ type, file, media }) {
  const inp = document.getElementById('prodThumbnail');
  const lid = document.getElementById('prodLibThumbId');
  const prv = document.getElementById('prodThumbPreview');
  if (type === 'upload') { setFileInput(inp, file); lid.value = ''; showFilePreview(file, prv); }
  else                   { inp.value = ''; lid.value = media.id; showLibraryPreview(media, prv); }
}

// ── Catalog CRUD ──
function openAddCatalogModal() {
  document.getElementById('catalogEditId').value = '';
  document.getElementById('catalogName').value = '';
  document.getElementById('catalogDesc').value = '';
  document.getElementById('modalCatalogTitle').textContent = 'Créer un catalogue';
  document.getElementById('catalogSubmitBtn').textContent = 'CRÉER';
  openModal('modalAddCatalog');
}

async function openEditCatalogModal(id) {
  closeAllMenus();
  const data = await apiGet(`/manage/catalog/catalog/${id}/get`);
  if (!data || data.error) { toast('Erreur', 'error'); return; }
  document.getElementById('catalogEditId').value = data.id;
  document.getElementById('catalogName').value = data.name;
  document.getElementById('catalogDesc').value = data.description || '';
  document.getElementById('modalCatalogTitle').textContent = 'Modifier le catalogue';
  document.getElementById('catalogSubmitBtn').textContent = 'ENREGISTRER';
  openModal('modalAddCatalog');
}

document.getElementById('formCatalog').addEventListener('submit', async function(e) {
  e.preventDefault();
  const id  = document.getElementById('catalogEditId').value;
  const url = id ? `/manage/catalog/catalog/${id}` : '/manage/catalog/catalog/create';
  const res = await apiPost(url, new FormData(this));
  if (res.success) { toast(id ? 'Catalogue modifié ✓' : 'Catalogue créé ✓'); closeModal('modalAddCatalog'); setTimeout(() => location.reload(), 400); }
  else toast(res.error || 'Erreur', 'error');
});

async function deleteCatalog(id) {
  closeAllMenus();
  if (!await confirmDelete('Supprimer ce catalogue ? Les catégories ne seront pas supprimées.')) return;
  const res = await apiPost(`/manage/catalog/catalog/${id}/delete`, new FormData());
  if (res.success) { toast('Catalogue supprimé'); location.reload(); }
}

// ── Category helpers ──
function openAddCategoryModal() {
  document.getElementById('catId').value = '';
  document.getElementById('catName').value = '';
  document.getElementById('catStatus').value = 'active';
  document.getElementById('catShowTitle').checked = true;
  document.getElementById('catViewExt').checked = true;
  document.getElementById('catLibMediaId').value = '';
  document.getElementById('catImagePreview').style.display = 'none';
  document.getElementById('modalCatTitle').textContent = 'Ajouter une catégorie';
  document.getElementById('catSubmitBtn').textContent = 'AJOUTER';
  document.getElementById('catParentId').value = '<?= $currentCatId ?? '' ?>';
  document.getElementById('catCatalogIdHidden').setAttribute('name', 'catalog_id');
  document.getElementById('catCatalogIdHidden').value = '<?= $catalogId ?? '' ?>';
  document.getElementById('catCatalogIdSelect').removeAttribute('name');
  document.getElementById('catCatalogSelectWrap').style.display = 'none';
  openModal('modalAddCategory');
}

async function openEditCategory(id) {
  closeAllMenus();
  const cat = await apiGet(`/manage/catalog/category/${id}/get`);
  if (!cat || cat.error) { toast('Erreur chargement','error'); return; }
  document.getElementById('catId').value = cat.id;
  document.getElementById('catName').value = cat.name;
  document.getElementById('catStatus').value = cat.status;
  document.getElementById('catShowTitle').checked = !!parseInt(cat.show_title);
  document.getElementById('catViewExt').checked   = !!parseInt(cat.viewable_external);
  document.getElementById('catLibMediaId').value = '';
  document.getElementById('catImagePreview').style.display = 'none';
  document.getElementById('modalCatTitle').textContent = 'Modifier la catégorie';
  document.getElementById('catSubmitBtn').textContent = 'ENREGISTRER';
  document.getElementById('catCatalogIdHidden').removeAttribute('name');
  document.getElementById('catCatalogIdSelect').setAttribute('name', 'catalog_id');
  document.getElementById('catCatalogIdSelect').value = cat.catalog_id || '';
  document.getElementById('catCatalogSelectWrap').style.display = 'block';
  openModal('modalAddCategory');
}

document.getElementById('formCategory').addEventListener('submit', async function(e) {
  e.preventDefault();
  const id  = document.getElementById('catId').value;
  const url = id ? `/manage/catalog/category/${id}` : '/manage/catalog/category/add';
  const res = await apiPost(url, new FormData(this));
  if (res.success) { toast(id ? 'Catégorie modifiée ✓' : 'Catégorie ajoutée ✓'); closeModal('modalAddCategory'); setTimeout(() => location.reload(), 500); }
  else toast(res.error || 'Erreur', 'error');
});

async function archiveCategory(id) {
  closeAllMenus();
  if (!await confirmDelete('Archiver cette catégorie ainsi que tous les produits et sous-catégories qu\'elle contient ?')) return;
  const res = await apiPost(`/manage/catalog/category/${id}/delete`, new FormData());
  if (res.success) { toast('Archivée'); location.reload(); }
}
async function restoreCategory(id) {
  const res = await apiPost(`/manage/catalog/category/${id}/restore`, new FormData());
  if (res.success) { toast('Restaurée'); location.reload(); }
}
async function hardDeleteCategory(id) {
  if (!await confirmDelete('Supprimer définitivement ?')) return;
  const res = await apiPost(`/manage/catalog/category/${id}/hard-delete`, new FormData());
  if (res.success) { toast('Supprimée'); location.reload(); }
}

// ── Product helpers ──
function openAddProductModal(catId) {
  document.getElementById('prodId').value = '';
  document.getElementById('formProduct').reset();
  document.getElementById('prodLibMediaId').value = '';
  document.getElementById('prodLibThumbId').value = '';
  document.getElementById('prodFilePreview').style.display = 'none';
  document.getElementById('prodThumbPreview').style.display = 'none';
  document.getElementById('modalProdTitle').textContent = 'Ajouter un produit';
  document.getElementById('prodSubmitBtn').textContent = 'AJOUTER';
  if (catId) document.getElementById('prodCategoryId').value = catId;
  toggleProdFileType('media');
  openModal('modalAddProduct');
}

async function openEditProduct(id) {
  closeAllMenus();
  const prod = await apiGet(`/manage/catalog/product/${id}/get`);
  if (!prod || prod.error) { toast('Erreur', 'error'); return; }
  document.getElementById('prodId').value = prod.id;
  document.getElementById('prodName').value = prod.name;
  document.getElementById('prodFileType').value = prod.file_type;
  document.getElementById('prodStatus').value = prod.status;
  document.getElementById('prodUrl').value = prod.url || '';
  document.getElementById('prodCategoryId').value = prod.category_id || '';
  document.getElementById('prodLibMediaId').value = '';
  document.getElementById('prodLibThumbId').value = '';
  document.getElementById('prodFilePreview').style.display = 'none';
  document.getElementById('prodThumbPreview').style.display = 'none';
  document.getElementById('modalProdTitle').textContent = 'Modifier le produit';
  document.getElementById('prodSubmitBtn').textContent = 'ENREGISTRER';
  toggleProdFileType(prod.file_type);
  openModal('modalAddProduct');
}

document.getElementById('formProduct').addEventListener('submit', async function(e) {
  e.preventDefault();
  const id  = document.getElementById('prodId').value;
  const url = id ? `/manage/catalog/product/${id}` : '/manage/catalog/product/add';
  const res = await apiPost(url, new FormData(this));
  if (res.success) { toast(id ? 'Produit modifié ✓' : 'Produit ajouté ✓'); closeModal('modalAddProduct'); setTimeout(() => location.reload(), 500); }
  else toast(res.error || 'Erreur', 'error');
});

function toggleProdFileType(val) {
  document.getElementById('prodUrlField').style.display   = val === 'url' ? 'block' : 'none';
  document.getElementById('prodUrlOptions').style.display = val === 'url' ? 'block' : 'none';
}

async function archiveProduct(id) {
  closeAllMenus();
  if (!await confirmDelete('Archiver ce produit ?')) return;
  const res = await apiPost(`/manage/catalog/product/${id}/delete`, new FormData());
  if (res.success) { toast('Archivé'); location.reload(); }
}
async function restoreProduct(id) {
  const res = await apiPost(`/manage/catalog/product/${id}/restore`, new FormData());
  if (res.success) { toast('Restauré'); location.reload(); }
}
async function hardDeleteProduct(id) {
  if (!await confirmDelete('Supprimer définitivement ?')) return;
  const res = await apiPost(`/manage/catalog/product/${id}/hard-delete`, new FormData());
  if (res.success) { toast('Supprimé'); location.reload(); }
}
async function duplicateProduct(id) {
  closeAllMenus();
  const res = await apiPost(`/manage/catalog/product/${id}/duplicate`, new FormData());
  if (res.success) { toast('Dupliqué ✓'); location.reload(); }
}
function openCertModal(id) {
  closeAllMenus();
  document.getElementById('certProductId').value = id;
  openModal('modalCertificate');
}
async function downloadCertificate() {
  const id = document.getElementById('certProductId').value;
  await apiPost(`/manage/certificates/generate/${id}`, new FormData());
  window.open(`/manage/certificates/download/${id}`, '_blank');
  closeModal('modalCertificate');
  toast('Certificat généré ✓');
}

// ── Filters & menus ──
function setFilter(f) {
  const url = new URL(window.location.href);
  url.searchParams.set('filter', f);
  window.location.href = url.toString();
}
function enableReorder() {
  toast('Glissez-déposez les cartes pour réorganiser');
  if (typeof initDragDrop === 'function') initDragDrop(document.getElementById('catalogGrid'));
}
function toggleCardMenu(menuId) {
  closeAllMenus();
  const m = document.getElementById(menuId);
  if (m) m.style.display = 'block';
}
function closeAllMenus() {
  document.querySelectorAll('.catalog-card-menu, .dropdown-menu').forEach(m => m.style.display = 'none');
}
document.addEventListener('click', closeAllMenus);
function filterCards(q) {
  q = q.toLowerCase();
  document.querySelectorAll('.catalog-card').forEach(card => {
    const label = (card.dataset.name || '').toLowerCase();
    card.style.display = label.includes(q) ? '' : 'none';
  });
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
