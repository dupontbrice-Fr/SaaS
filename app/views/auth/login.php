<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MediaPush - Connexion</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/public/css/app.css">
</head>
<body>
<div class="login-page">
  <!-- Background orbs -->
  <div class="login-bg-orb" style="width:400px;height:400px;background:#6b6ef9;top:-100px;right:-100px;"></div>
  <div class="login-bg-orb" style="width:300px;height:300px;background:#8b5cf6;bottom:-50px;left:-50px;"></div>

  <div class="login-card">
    <div class="login-logo">
      <div class="login-logo-text">
        <span style="color:white">MULTI</span><span style="color:#6b6ef9">APP</span>
      </div>
      <div class="login-logo-sub">Plateforme de gestion de contenu interactif</div>
    </div>

    <?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom:16px">
      <?= htmlspecialchars($error) ?>
    </div>
    <?php endif; ?>

    <form method="POST" action="/login">
      <div class="form-group">
        <label class="form-label">Adresse email</label>
        <input type="email" name="email" class="form-input" placeholder="admin@exemple.fr" required autocomplete="email">
      </div>
      <div class="form-group">
        <label class="form-label">Mot de passe</label>
        <input type="password" name="password" class="form-input" placeholder="••••••••" required autocomplete="current-password">
      </div>
      <button type="submit" class="btn btn-primary w-full" style="justify-content:center; padding:12px; font-size:14px; margin-top:8px;">
        SE CONNECTER
      </button>
    </form>

    <div style="text-align:center; margin-top:24px; font-size:12px; color:var(--text-muted);">
      Plateforme MediaPush v<?= APP_VERSION ?> — © <?= date('Y') ?>
    </div>
  </div>
</div>
<script src="/public/js/app.js"></script>
</body>
</html>
