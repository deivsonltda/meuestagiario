<?php
require_once __DIR__ . '/../app/bootstrap.php';
$base = app_config('base_url', '');
?>
<!doctype html>
<html lang="pt-br">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?= h(app_config('app_name')) ?> — Redefinir senha</title>
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

    <h1 class="auth-title">Defina uma nova senha</h1>
    <p class="auth-subtitle">Crie uma senha forte para proteger sua conta</p>

    <div class="alert" id="resetMsg" style="display:none;"></div>

    <form class="auth-form" id="resetForm">
      <label class="field">
        <span>Nova senha</span>
        <input type="password" id="newPass" placeholder="Mínimo 8 caracteres" minlength="8" required />
      </label>

      <button class="btn btn-black" type="submit">Salvar nova senha</button>

      <div class="auth-links">
        <a href="<?= h($base) ?>/index.php">Voltar ao login</a>
      </div>
    </form>
  </div>

<script>
(function(){
  function getHashParam(name){
    const h = (window.location.hash || '').replace(/^#/, '');
    const p = new URLSearchParams(h);
    return p.get(name) || '';
  }
  const accessToken = getHashParam('access_token');
  const msg = document.getElementById('resetMsg');
  const form = document.getElementById('resetForm');
  const pass = document.getElementById('newPass');

  function show(text, ok){
    msg.style.display = 'block';
    msg.textContent = text;
    msg.style.background = ok ? '#ecfdf5' : '';
    msg.style.borderColor = ok ? 'rgba(22,163,74,.25)' : '';
    msg.style.color = ok ? '#14532d' : '';
  }

  if(!accessToken){
    show('Link inválido ou expirado. Solicite novamente a recuperação de senha.', false);
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    if(!accessToken) return;

    const newPassword = (pass.value || '').trim();
    if(newPassword.length < 8){
      show('A senha deve ter no mínimo 8 caracteres.', false);
      return;
    }

    try{
      const r = await fetch('<?= h($base) ?>/api/reset_password.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        credentials: 'same-origin',
        body: JSON.stringify({ access_token: accessToken, password: newPassword })
      });
      const data = await r.json();
      if(!r.ok || data.ok === false) throw new Error(data.error?.message || data.error || 'Falha.');

      show('Senha redefinida com sucesso. Você já pode fazer login.', true);
    }catch(err){
      show('Erro: ' + err.message, false);
    }
  });
})();
</script>
</body>
</html>