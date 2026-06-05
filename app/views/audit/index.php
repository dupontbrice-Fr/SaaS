<?php ?>
<h1 class="page-title">Journal d'activités</h1>
<div class="text-muted text-sm" style="margin-bottom:20px;"><?= $total['c'] ?? 0 ?> événements pour <?= htmlspecialchars(Auth::user()['org_name']) ?> et ses sous-organisations</div>

<div class="card" style="margin-bottom:24px;">
  <div style="padding:16px;">
    <?php if (empty($logs)): ?>
    <div class="text-muted" style="text-align:center;padding:30px;">Aucun événement</div>
    <?php else: ?>
    <?php foreach ($logs as $log): ?>
    <?php
    $date = date('j F Y \à H\hi', strtotime($log['created_at']));
    $action = match($log['action']) {
      'created' => 'a créé ' . match($log['entity_type']) {
        'category' => 'une nouvelle catégorie',
        'product' => 'un nouveau produit',
        'banner' => 'une nouvelle bannière',
        'screensaver' => 'un nouvel écran de veille',
        'certificate' => 'un nouveau certificat',
        'screen_demo' => 'un nouvel écran de démonstration',
        default => 'un nouvel élément'
      },
      'modified' => 'a modifié "' . htmlspecialchars($log['field_name'] ?? '') . '" sur ' . match($log['entity_type']) {
        'category' => 'la catégorie',
        'product' => 'le produit',
        'banner' => 'la bannière',
        'screensaver' => "l'écran de veille",
        default => "l'élément"
      },
      'deleted' => 'a supprimé ' . match($log['entity_type']) {
        'category' => 'la catégorie',
        'product' => 'le produit',
        'banner' => 'la bannière',
        'screensaver' => "l'écran de veille",
        'certificate' => 'le certificat',
        default => "l'élément"
      },
      'moved' => 'a déplacé "' . htmlspecialchars($log['entity_name'] ?? '') . '" dans la catégorie "' . htmlspecialchars($log['new_value'] ?? '') . '"',
      'moved_to_root' => 'a déplacé "' . htmlspecialchars($log['entity_name'] ?? '') . '" à la racine du catalogue',
      default => $log['action']
    };
    $entityName = $log['entity_name'] ?? '';
    ?>
    <div style="padding:10px 0; border-bottom:1px solid var(--border); font-size:13px; color:var(--text-secondary);">
      <span style="color:var(--text-muted);"><?= $date ?> :</span>
      <strong style="color:var(--text-primary);"><?= htmlspecialchars($log['user_email']) ?></strong>
      <?= $action ?>
      <?php if (!in_array($log['action'], ['moved', 'moved_to_root'])): ?>
        <strong>"<?= htmlspecialchars($entityName) ?>"</strong>
      <?php endif; ?>
      <span style="color:var(--text-muted);">("<?= htmlspecialchars(Auth::user()['org_name']) ?>")</span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Login History -->
<h2 style="font-size:16px;font-weight:600;margin-bottom:16px;">Historique des connexions</h2>
<div class="card">
  <table>
    <thead><tr><th>DATE/HEURE (HEURE DE PARIS)</th><th>EMAIL</th><th>URL DE CONNEXION</th></tr></thead>
    <tbody>
    <?php if (empty($logins)): ?>
      <tr><td colspan="3" style="text-align:center;color:var(--text-muted);padding:20px;">Aucune connexion enregistrée</td></tr>
    <?php else: ?>
    <?php foreach ($logins as $l): ?>
    <tr>
      <td><?= date('d/m/Y H:i:s', strtotime($l['logged_at'])) ?></td>
      <td><?= htmlspecialchars($l['user_email']) ?></td>
      <td><?= htmlspecialchars($l['url'] ?? '') ?></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<?php include __DIR__ . '/../layout_footer.php'; ?>
