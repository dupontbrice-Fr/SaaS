<?php
$currentPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$user = Auth::user();
$orgName = $user['org_name'] ?? 'MediaPush';
$userInitials = strtoupper(substr($user['email'] ?? 'A', 0, 1));
$filterColor = Auth::filterColor();

$navItems = [
    ['path' => '/manage/catalog', 'label' => 'Catalogue', 'icon' => 'grid'],
    ['path' => '/manage/banners', 'label' => 'Bannière(s)', 'icon' => 'image'],
    ['path' => '/manage/screensaver', 'label' => 'Écran de veille', 'icon' => 'monitor'],
    ['path' => '/manage/library', 'label' => 'Bibliothèques', 'icon' => 'folder'],
    ['path' => '/manage/screens', 'label' => 'Écrans et Licences', 'icon' => 'tv'],
    ['path' => '/manage/statistics', 'label' => 'Statistiques', 'icon' => 'chart'],
    ['path' => '/manage/certificates', 'label' => 'Certificats', 'icon' => 'award'],
    ['path' => '/support', 'label' => 'Support', 'icon' => 'help'],
];

function isActive(string $path, string $current): bool {
    // Exact match OR starts with path + "/" or "?" to avoid /manage/screens matching /manage/screensaver
    return $current === $path
        || str_starts_with($current, $path . '/')
        || str_starts_with($current, $path . '?');
}

function navIcon(string $name): string {
    $icons = [
        'grid' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>',
        'image' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="9" cy="9" r="2"/><path d="m21 15-3.086-3.086a2 2 0 0 0-2.828 0L6 21"/></svg>',
        'monitor' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>',
        'folder' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/></svg>',
        'tv' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="7" width="20" height="15" rx="2"/><polyline points="17 2 12 7 7 2"/></svg>',
        'chart' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'award' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="6"/><path d="M15.477 12.89L17 22l-5-3-5 3 1.523-9.11"/></svg>',
        'help' => '<svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
    ];
    return $icons[$name] ?? '';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= APP_NAME ?> - <?= htmlspecialchars($orgName) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/public/css/app.css">
  <style>
    .nav-item.active { border-right-color: <?= htmlspecialchars($filterColor) ?>; color: <?= htmlspecialchars($filterColor) ?>; }
    .btn-primary { background: <?= htmlspecialchars($filterColor) ?>; }
    .btn-primary:hover { background: <?= htmlspecialchars($filterColor) ?>cc; }
    .tab.active { color: <?= htmlspecialchars($filterColor) ?>; border-bottom-color: <?= htmlspecialchars($filterColor) ?>; }
    .sub-tab.active { color: <?= htmlspecialchars($filterColor) ?>; border-color: <?= htmlspecialchars($filterColor) ?>; background: <?= htmlspecialchars($filterColor) ?>22; }
    .form-input:focus, .form-select:focus { border-color: <?= htmlspecialchars($filterColor) ?>; }
    .badge-info { background: <?= htmlspecialchars($filterColor) ?>22; color: <?= htmlspecialchars($filterColor) ?>; }
    .upload-zone { background: <?= htmlspecialchars($filterColor) ?>; }
    .upload-zone:hover { background: <?= htmlspecialchars($filterColor) ?>cc; }
    .stat-value { color: <?= htmlspecialchars($filterColor) ?>; }
  </style>
</head>
<body>
<div class="app-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
      <span class="sidebar-logo sidebar-logo-full">
        <span style="color:<?= htmlspecialchars($filterColor) ?>">Multi</span>App
      </span>
      <span class="sidebar-logo sidebar-logo-short" style="display:none;color:<?= htmlspecialchars($filterColor) ?>;font-weight:800;font-size:18px;">M</span>
      <button class="sidebar-toggle" onclick="toggleSidebar()">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>

    <div class="sidebar-section-title">AFFICHAGE INTERACTIF</div>

    <nav class="sidebar-nav">
      <?php foreach ($navItems as $item): ?>
      <a href="<?= $item['path'] ?>" class="nav-item <?= isActive($item['path'], $currentPath) ? 'active' : '' ?>">
        <?= navIcon($item['icon']) ?>
        <span class="nav-label"><?= htmlspecialchars($item['label']) ?></span>
      </a>
      <?php endforeach; ?>
    </nav>

    <div style="padding: 12px 16px; border-top: 1px solid var(--border); font-size: 11px; color: var(--text-muted);">
      © <?= date('Y') ?>, Copyright
    </div>
  </aside>

  <!-- Main -->
  <div class="main-content" id="mainContent">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-left">
        <select class="org-selector" onchange="switchOrg(this.value)">
          <option value=""><?= htmlspecialchars($orgName) ?></option>
        </select>
      </div>
      <div class="topbar-right">
        <a href="/settings" class="topbar-icon-btn" title="Paramètres">
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/></svg>
        </a>

        <div class="dropdown">
          <div class="avatar" onclick="toggleDropdown('userMenu')" title="<?= htmlspecialchars($user['email'] ?? '') ?>">
            <?= $userInitials ?>
          </div>
          <div class="dropdown-menu" id="userMenu" style="display:none;">
            <div class="dropdown-header"><?= htmlspecialchars($user['email'] ?? '') ?></div>
            <div style="padding:2px 16px 8px;font-size:11px;color:var(--text-muted);">
              <?php
                $roleDisplay = match($user['role'] ?? '') {
                    'superadmin' => 'Super Administrateur',
                    'admin'      => 'Administrateur',
                    default      => 'Utilisateur',
                };
              ?>
              Niveau : <strong><?= $roleDisplay ?></strong>
            </div>
            <div class="dropdown-divider"></div>
            <?php if (Auth::isAdmin()): ?>
            <a href="/manage/users" class="dropdown-item">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
              Administration
            </a>
            <div class="dropdown-divider"></div>
            <?php endif; ?>
            <a href="/audit" class="dropdown-item">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
              Journal d'activités
            </a>
            <a href="/settings" class="dropdown-item">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/></svg>
              Paramètres
            </a>
            <div class="dropdown-divider"></div>
            <a href="/logout" class="dropdown-item danger">
              <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
              Déconnexion
            </a>
          </div>
        </div>

        <!-- InfiniTouch Logo area -->
        <div style="margin-left:8px; padding-left:16px; border-left:1px solid var(--border);">
          <span style="font-size:16px; font-weight:800; letter-spacing:1px;">
            <span style="color:white">INFINI</span><span style="color:<?= htmlspecialchars($filterColor) ?>">TOUCH</span>
          </span>
        </div>
      </div>
    </header>

    <!-- Page content rendered by controller -->
    <main class="page-content" id="pageContent">
