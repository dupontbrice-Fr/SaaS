<?php
$periods = ['today' => "Aujourd'hui", 'yesterday' => 'Hier', 'week' => 'Cette semaine', 'last_week' => 'La semaine dernière', 'month' => 'Ce mois-ci', 'last_month' => 'Le mois dernier', 'all' => 'Depuis toujours'];
?>

<h1 class="page-title">Statistiques</h1>

<!-- Period selector -->
<div style="display:flex; gap:6px; flex-wrap:wrap; margin-bottom:16px;">
  <?php foreach ($periods as $k => $label): ?>
  <a href="?period=<?= $k ?>" class="sub-tab <?= $period === $k ? 'active' : '' ?>"><?= $label ?></a>
  <?php endforeach; ?>
</div>

<!-- Controls -->
<div style="display:flex; gap:12px; flex-wrap:wrap; align-items:center; margin-bottom:20px;">
  <select class="form-input" style="width:200px;" onchange="window.location.href='?period=<?= $period ?>&screen_id='+this.value">
    <option value="">Tous les écrans</option>
    <?php foreach ($screens as $s): ?>
    <option value="<?= $s['id'] ?>" <?= ($screenId == $s['id']) ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
    <?php endforeach; ?>
  </select>
  <label style="display:flex;align-items:center;gap:6px;font-size:13px;color:var(--text-secondary);">
    <input type="checkbox" <?= $showExternal ? 'checked' : '' ?> onchange="window.location.href='?period=<?= $period ?>&external='+this.checked"> Afficher les clics externes
  </label>
  <div style="flex:1;"></div>
  <a href="/manage/statistics/export" class="btn btn-secondary btn-sm">
    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/></svg>
    EXPORTER (.XLSX)
  </a>
</div>

<!-- Stats cards -->
<div class="grid-4" style="margin-bottom:24px;">
  <div class="stat-card">
    <div class="stat-value"><?= $totalAll['c'] ?? 0 ?></div>
    <div class="stat-label">Totaux</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= $totalMonth['c'] ?? 0 ?></div>
    <div class="stat-label">Ce mois-ci</div>
  </div>
  <div class="stat-card">
    <div class="stat-value"><?= $totalToday['c'] ?? 0 ?></div>
    <div class="stat-label">Aujourd'hui</div>
  </div>
  <div class="stat-card" style="font-size:12px;">
    <div style="color:var(--text-muted);font-size:11px;margin-bottom:4px;">FUSEAU HORAIRE</div>
    <div style="font-size:13px;color:var(--text-primary);">(UTC+01:00) Europe/Paris</div>
  </div>
</div>

<!-- Chart -->
<div class="card" style="padding:20px; margin-bottom:20px;">
  <div style="font-size:14px; font-weight:600; margin-bottom:16px;">Nombre de clics</div>
  <div style="position:relative; height:200px;">
    <canvas id="clicksChart"></canvas>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:20px;">
  <!-- Top Categories -->
  <div class="card" style="padding:16px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:12px;">Catégories les plus visitées</div>
    <?php if (empty($topCategories)): ?>
      <div class="text-muted text-sm">Aucune donnée</div>
    <?php else: ?>
    <?php foreach ($topCategories as $tc): ?>
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px;">
      <span><?= htmlspecialchars($tc['item_name']) ?></span>
      <span class="text-accent font-bold"><?= $tc['count'] ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
  <!-- Top Products -->
  <div class="card" style="padding:16px;">
    <div style="font-size:13px;font-weight:600;margin-bottom:12px;">Produits les plus consultés</div>
    <?php if (empty($topProducts)): ?>
      <div class="text-muted text-sm">Aucune donnée</div>
    <?php else: ?>
    <?php foreach ($topProducts as $tp): ?>
    <div style="display:flex;justify-content:space-between;padding:6px 0;border-bottom:1px solid var(--border);font-size:13px;">
      <span><?= htmlspecialchars($tp['item_name']) ?></span>
      <span class="text-accent font-bold"><?= $tp['count'] ?></span>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<!-- Detail Table -->
<div class="card">
  <table>
    <thead><tr><th>TYPE</th><th>NOM DU BLOC</th><th>LICENSE</th><th>NOMBRE DE CLICS</th></tr></thead>
    <tbody>
    <?php if (empty($clicks)): ?>
      <tr><td colspan="4" style="text-align:center;color:var(--text-muted);padding:30px;">Aucune donnée pour cette période</td></tr>
    <?php else: ?>
    <?php
    // Group by name+license
    $grouped = [];
    foreach ($clicks as $c) {
      $key = $c['item_type'].'|'.$c['item_name'].'|'.($c['license_code'] ?? '');
      if (!isset($grouped[$key])) $grouped[$key] = ['type' => ucfirst($c['item_type']), 'name' => $c['item_name'], 'license' => $c['license_code'] ?? '—', 'count' => 0];
      $grouped[$key]['count']++;
    }
    foreach ($grouped as $g):
    ?>
    <tr>
      <td><?= htmlspecialchars($g['type']) ?></td>
      <td><?= htmlspecialchars($g['name']) ?></td>
      <td><?= htmlspecialchars($g['license']) ?></td>
      <td><strong><?= $g['count'] ?></strong></td>
    </tr>
    <?php endforeach; ?>
    <?php endif; ?>
    </tbody>
  </table>
</div>

<script>
const chartData = <?= json_encode($chartData) ?>;
document.addEventListener('DOMContentLoaded', () => initStatsChart(chartData));
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
