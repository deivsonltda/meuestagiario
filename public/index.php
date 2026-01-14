<?php
require_once __DIR__ . '/../app/auth.php';

if (is_logged_in()) redirect('/app.php');

$error = trim((string)($_GET['error'] ?? ''));
$base  = app_config('base_url', '');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= h(app_config('app_name')) ?> — Login</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= h($base) ?>/assets/css/app.css">
</head>
<body class="auth-body">
  <div class="auth-card">
    <div class="auth-brand">
      <div class="brand-mark" aria-hidden="true"></div>
      <div class="brand-name"><?= h(app_config('app_name')) ?></div>
    </div>

    <h1 class="auth-title">Bem-vindo de volta</h1>
    <p class="auth-subtitle">Entre com seu email e senha</p>

    <?php if ($error): ?>
      <div class="alert"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= h($base) ?>/api/login.php" class="auth-form">
      <label class="field">
        <span>Email</span>
        <input type="email" name="email" placeholder="Digite seu e-mail" autocomplete="email" required />
      </label>

      <label class="field">
        <span>Senha</span>
        <input type="password" name="password" placeholder="Digite sua senha" autocomplete="current-password" required />
      </label>

      <button class="btn btn-black" type="submit">Entrar</button>

      <div class="auth-links">
        <a href="<?= h($base) ?>/forgot.php">Esqueci minha senha</a>
      </div>
    </form>
  </div>
</body>
</html>