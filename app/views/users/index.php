<?php
function roleLabel(string $role): string {
    return match($role) {
        'superadmin' => 'Super Administrateur',
        'admin'      => 'Administrateur',
        default      => 'Utilisateur',
    };
}
function roleBadgeStyle(string $role): string {
    return $role === 'user' ? 'background:var(--bg-hover);color:var(--text-muted);' : '';
}
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;">
  <div>
    <h1 class="page-title" style="margin-bottom:4px;">Administration</h1>
    <p style="color:var(--text-muted);font-size:14px;">Gérez les comptes utilisateurs de votre organisation.</p>
  </div>
  <button class="btn btn-primary" onclick="openModal('modalCreateUser')">
    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
    CRÉER UN UTILISATEUR
  </button>
</div>

<div class="card" style="overflow:hidden;">
  <table style="width:100%;border-collapse:collapse;">
    <thead>
      <tr style="border-bottom:1px solid var(--border);background:var(--bg-hover);">
        <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Email</th>
        <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Niveau</th>
        <th style="padding:12px 16px;text-align:left;font-size:12px;font-weight:600;color:var(--text-muted);text-transform:uppercase;letter-spacing:.5px;">Créé le</th>
        <th style="padding:12px 16px;width:80px;"></th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($users)): ?>
      <tr>
        <td colspan="4" style="padding:48px;text-align:center;color:var(--text-muted);">
          <div style="font-size:36px;margin-bottom:12px;">👤</div>
          Aucun utilisateur trouvé.
        </td>
      </tr>
      <?php else: ?>
      <?php foreach ($users as $u): ?>
      <tr style="border-bottom:1px solid var(--border);">
        <td style="padding:14px 16px;">
          <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:32px;height:32px;border-radius:50%;background:var(--accent);color:#fff;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0;">
              <?= strtoupper(substr($u['email'], 0, 1)) ?>
            </div>
            <span style="font-weight:500;"><?= htmlspecialchars($u['email']) ?></span>
            <?php if ((int)$u['id'] === (int)$currentUser['id']): ?>
              <span style="font-size:11px;background:var(--accent);color:#fff;padding:2px 8px;border-radius:10px;">Vous</span>
            <?php endif; ?>
          </div>
        </td>
        <td style="padding:14px 16px;">
          <span class="badge-info" style="<?= roleBadgeStyle($u['role']) ?>">
            <?= roleLabel($u['role']) ?>
          </span>
        </td>
        <td style="padding:14px 16px;color:var(--text-muted);font-size:13px;">
          <?= date('d/m/Y à H\hi', strtotime($u['created_at'])) ?>
        </td>
        <td style="padding:14px 16px;text-align:right;">
          <?php if ($u['role'] !== 'superadmin' && (int)$u['id'] !== (int)$currentUser['id']): ?>
          <button class="btn btn-danger btn-sm" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars(addslashes($u['email'])) ?>')">
            Supprimer
          </button>
          <?php endif; ?>
        </td>
      </tr>
      <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<!-- Create User Modal -->
<div class="modal-overlay" id="modalCreateUser" style="display:none;">
  <div class="modal" style="max-width:460px;">
    <div class="modal-header">
      <div class="modal-title">Créer un utilisateur</div>
      <button class="modal-close" onclick="closeModal('modalCreateUser')">×</button>
    </div>
    <form id="formCreateUser">
      <div style="padding:20px;display:flex;flex-direction:column;gap:16px;">
        <div class="form-group">
          <label class="form-label">Adresse email <span style="color:var(--red)">*</span></label>
          <input type="email" name="email" class="form-input" placeholder="prenom@exemple.fr" required autocomplete="off">
          <div class="form-hint">Cet email servira d'identifiant de connexion.</div>
        </div>
        <div class="form-group">
          <label class="form-label">Mot de passe <span style="color:var(--red)">*</span></label>
          <input type="password" name="password" class="form-input" placeholder="Minimum 8 caractères" required autocomplete="new-password">
        </div>
        <div class="form-group">
          <label class="form-label">Niveau d'accès</label>
          <select name="role" class="form-select">
            <option value="user">Utilisateur — accès lecture et consultation</option>
            <option value="admin">Administrateur — accès complet</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">CRÉER LE COMPTE</button>
        <button type="button" class="btn btn-secondary" onclick="closeModal('modalCreateUser')">ANNULER</button>
      </div>
    </form>
  </div>
</div>

<script>
document.getElementById('formCreateUser').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = this.querySelector('[type="submit"]');
  btn.disabled = true;
  const fd = new FormData(this);
  const res = await apiPost('/manage/users/create', fd);
  btn.disabled = false;
  if (res.success) {
    toast('Utilisateur créé avec succès');
    closeModal('modalCreateUser');
    this.reset();
    location.reload();
  } else {
    toast(res.error || 'Erreur lors de la création', 'error');
  }
});

async function deleteUser(id, email) {
  if (!await confirmDelete(`Supprimer le compte de "${email}" ?`)) return;
  const res = await apiPost(`/manage/users/${id}/delete`, new FormData());
  if (res.success) {
    toast('Utilisateur supprimé');
    location.reload();
  } else {
    toast(res.error || 'Erreur lors de la suppression', 'error');
  }
}
</script>

<?php include __DIR__ . '/../layout_footer.php'; ?>
