<?php
require_once __DIR__ . '/../app/bootstrap.php';

$base = app_config('base_url', '');

// captura tracking do link
$track = [
  'lid' => trim((string)($_GET['lid'] ?? '')),
  'ref' => trim((string)($_GET['ref'] ?? '')),
  'utm_source'   => trim((string)($_GET['utm_source'] ?? '')),
  'utm_medium'   => trim((string)($_GET['utm_medium'] ?? '')),
  'utm_campaign' => trim((string)($_GET['utm_campaign'] ?? '')),
  'utm_content'  => trim((string)($_GET['utm_content'] ?? '')),
  'utm_term'     => trim((string)($_GET['utm_term'] ?? '')),
];

// salva cookie para o bot/N8N conferir depois
$cookiePayload = json_encode($track, JSON_UNESCAPED_UNICODE);
setcookie('sb_reg_track', $cookiePayload, [
  'expires' => time() + (60 * 60 * 24 * 30), // 30 dias
  'path' => '/',
  'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
  'httponly' => false, // JS pode ler se precisar (se não precisar, muda pra true)
  'samesite' => 'Lax',
]);

$success = isset($_GET['success']) && $_GET['success'] === '1';
$error = trim((string)($_GET['error'] ?? ''));
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= h(app_config('app_name')) ?> — Cadastro</title>
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

    <h1 class="auth-title">Crie sua conta</h1>
    <p class="auth-subtitle">Preencha seus dados para acessar a plataforma</p>

    <?php if ($success): ?>
      <div class="alert" style="background:#ecfdf5;border-color:rgba(22,163,74,.25);color:#14532d;">
        Cadastro concluído. <a href="<?= h($base) ?>/index.php" style="font-weight:700;">Entre e faça login</a>.
      </div>
    <?php endif; ?>

    <?php if ($error): ?>
      <div class="alert"><?= h($error) ?></div>
    <?php endif; ?>

    <form method="post" action="<?= h($base) ?>/api/register.php" class="auth-form">
      <input type="hidden" name="lid" value="<?= h($track['lid']) ?>">
      <input type="hidden" name="ref" value="<?= h($track['ref']) ?>">
      <input type="hidden" name="utm_source" value="<?= h($track['utm_source']) ?>">
      <input type="hidden" name="utm_medium" value="<?= h($track['utm_medium']) ?>">
      <input type="hidden" name="utm_campaign" value="<?= h($track['utm_campaign']) ?>">
      <input type="hidden" name="utm_content" value="<?= h($track['utm_content']) ?>">
      <input type="hidden" name="utm_term" value="<?= h($track['utm_term']) ?>">

      <label class="field">
        <span>Nome completo</span>
        <input type="text" name="full_name" placeholder="Digite seu nome completo" required />
      </label>

      <label class="field">
        <span>E-mail</span>
        <input type="email" name="email" placeholder="Digite seu e-mail" autocomplete="email" required />
      </label>

      <label class="field">
        <span>Telefone</span>
        <input type="tel" name="phone" placeholder="(DDD) 9xxxx-xxxx" required />
      </label>

      <label class="field">
        <span>Senha</span>
        <input type="password" name="password" placeholder="Crie uma senha" minlength="8" required />
      </label>

      <button class="btn btn-black" type="submit">Criar conta</button>

      <div class="auth-links">
        <a href="<?= h($base) ?>/index.php">Já tenho conta</a>
      </div>
    </form>

    <div class="muted small" style="margin-top:10px;text-align:center;">
      Ao criar sua conta, seu dispositivo pode receber um cookie para concluir o rastreio do cadastro.
    </div>
  </div>
</body>
</html>