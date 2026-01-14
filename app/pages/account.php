<?php
// app/pages/account.php

require_once __DIR__ . '/../supabase.php';
require_once __DIR__ . '/../auth.php';

$tab = strtolower($_GET['tab'] ?? 'perfil');
$tabs = [
  'perfil' => 'Perfil',
  'personalizar' => 'Personalizar',
  'plano' => 'Plano',
  'integracoes' => 'Integrações',
];
if (!isset($tabs[$tab])) $tab = 'perfil';

function tab_url($key)
{
  return rtrim(app_config('base_url', ''), '/') . '/app.php?page=account&tab=' . urlencode($key);
}

/**
 * Remove tudo que não for número e,
 * se vier com DDI 55, remove o 55 pra exibir como você quer.
 */
function phone_display($phone): string
{
  $p = preg_replace('/\D+/', '', (string)$phone);
  if ($p === '') return '';
  if (strlen($p) >= 12 && str_starts_with($p, '55')) {
    $p = substr($p, 2);
  }
  return $p;
}

// ---------------------------------------------------------
// Busca profile no Supabase: full_name, phone e flag Google Calendar
// ---------------------------------------------------------
$full_name  = (string)($_SESSION['user']['name'] ?? '');
$email      = strtolower(trim((string)($_SESSION['user']['email'] ?? '')));
$phone      = '';
$gc_enabled = false; // profiles.google_calendar_enabled

if ($email !== '') {
  try {
    $res = supabase_request('GET', '/rest/v1/profiles', [
      'select' => 'full_name,phone,google_calendar_enabled',
      'email'  => 'eq.' . $email,
      'limit'  => 1,
    ]);

    if (!empty($res['ok']) && !empty($res['data'][0])) {
      $row = $res['data'][0];

      $full_name  = (string)($row['full_name'] ?? $full_name);
      $phone      = (string)($row['phone'] ?? '');
      $gc_enabled = !empty($row['google_calendar_enabled']);
    }
  } catch (\Throwable $e) {
    // silêncio proposital
  }
}

// formata SEM +55
$phone_ui = phone_display($phone);

// salva em sessão (opcional)
$_SESSION['user']['name']  = $full_name;
$_SESSION['user']['phone'] = $phone_ui;

// urls úteis
$base = rtrim(app_config('base_url', ''), '/');
$returnUrl = $base . '/app.php?page=account&tab=integracoes';
$oauthStartUrl = $base . '/api/google/oauth_start.php?return=' . urlencode($returnUrl);
?>
<script>
  window.__PAGE__ = 'account';
</script>

<div class="page">
  <div class="account-tabs">
    <?php foreach ($tabs as $key => $label): ?>
      <a class="account-tab <?= $tab === $key ? 'active' : '' ?>" href="<?= tab_url($key) ?>">
        <?= h($label) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if ($tab === 'perfil'): ?>
    <div class="card">
      <div class="card-pad">
        <div class="account-head">
          <div>
            <h2 class="account-title">Minhas Informações</h2>
          </div>
          <div class="account-actions">
            <button class="btn btn-ghost" id="btnSaveProfile" type="button">Salvar</button>
            <button class="btn btn-ghost" id="btnChangePassword" type="button">Alterar senha</button>
          </div>
        </div>

        <div class="account-grid-3">
          <div class="form-row">
            <label>NOME</label>
            <input id="accFullName" type="text" value="<?= h($full_name); ?>" />
          </div>

          <div class="form-row">
            <label>E-MAIL</label>
            <input type="text" value="<?= h($email); ?>" disabled />
          </div>

          <div class="form-row">
            <label>TELEFONE</label>
            <input id="accPhone" type="text" value="<?= h($phone_ui); ?>" placeholder="Ex: 81998517063" />
          </div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-pad">
        <h3 class="account-subtitle">Convidar Novo Usuário</h3>
        <p class="muted small" style="margin-top:6px;">
          Gere um código e envie para a pessoa que deseja se conectar à sua conta.
          Ela poderá enviar esse código no WhatsApp para iniciar o cadastro automaticamente.
        </p>

        <div class="account-center">
          <button class="btn btn-black" style="width:auto; padding:12px 18px;" type="button">
            + Gerar Código de Convite
          </button>
        </div>
      </div>
    </div>

  <?php elseif ($tab === 'personalizar'): ?>
    <div class="card">
      <div class="card-pad">
        <h2 class="account-title">Personalizar Assistente - (Beta)</h2>
        <p class="muted small" style="margin-top:6px;">
          Ajuste o estilo de respostas dos registros e relatórios do seu assessor no WhatsApp.
        </p>

        <div class="account-grid-2" style="margin-top:12px;">
          <div class="form-row">
            <label>Humor da IA</label>
            <select class="account-select">
              <option>Humorada</option>
              <option>Neutra</option>
              <option>Séria</option>
            </select>
          </div>

          <div class="form-row">
            <label>Tamanho das Respostas</label>
            <select class="account-select">
              <option>Curta</option>
              <option>Média</option>
              <option>Longa</option>
            </select>
          </div>

          <div class="form-row">
            <label>Horário do Lembrete Diário (Em breve)</label>
            <input type="time" value="08:00" />
          </div>

          <div class="form-row">
            <label>Tempo Padrão de Lembrete (Em breve)</label>
            <select class="account-select">
              <option>2 horas</option>
              <option>4 horas</option>
              <option>1 dia</option>
            </select>
          </div>
        </div>

        <div class="account-center" style="margin-top:14px;">
          <button class="btn btn-black" style="width:auto; padding:12px 18px;" type="button">
            Salvar Personalizações
          </button>
        </div>
      </div>
    </div>

  <?php elseif ($tab === 'plano'): ?>
    <div class="card">
      <div class="card-pad">
        <div class="plan-head">
          <h2 class="account-title">Gerenciar Assinatura</h2>
          <div class="plan-toggle">
            <span class="muted small">Mensal</span>
            <label class="switch">
              <input type="checkbox" />
              <span class="slider"></span>
            </label>
            <span class="muted small">Anual</span>
          </div>
        </div>

        <div class="plan-card">
          <div class="plan-left">
            <div class="plan-name">MeuEstagiário - Completo</div>
            <div class="muted small">Acesso total às funcionalidades</div>

            <ul class="plan-list">
              <li>✓ Perguntas para seu Assessor IA</li>
              <li>✓ Dashboard para acompanhamento</li>
              <li>✓ Controle de transações</li>
              <li>✓ Saldo e fluxo de caixa</li>
              <li>✓ Lembretes diários</li>
              <li>✓ Agenda integrada</li>
              <li>✓ Ajuda com mensagens</li>
            </ul>

            <div class="plan-price">
              <span class="plan-value">R$ 29,90</span> <span class="muted small">/mês</span>
            </div>
          </div>

          <div class="plan-right">
            <button class="btn btn-black" style="width:auto; padding:12px 18px;" type="button">
              Assinar plano
            </button>
          </div>
        </div>
      </div>
    </div>

  <?php else: /* integracoes */ ?>
    <div class="card">
      <div class="card-pad">
        <div class="integr-head">
          <div>
            <h3 class="integr-title">Integração Google Agenda (Beta)</h3>
            <p class="integr-desc">
              Conecte a sua agenda do Google para que os compromissos da sua agenda
              sejam recebidos no MeuAssessor.com. Você pode conectar quantos e-mails quiser.
            </p>
          </div>

          <label class="integr-toggle">
            <span>Ativar integração com Google Agenda</span>
            <div class="switch">
              <input type="checkbox" id="gcToggle" checked>
              <span class="slider"></span>
            </div>
          </label>
        </div>

        <!-- ESTADO VAZIO -->
        <div id="gcEmpty" class="gc-empty">
          <p>Você ainda não conectou nenhuma conta do Google Agenda.</p>
        </div>

        <div class="integr-action">
          <a href="/api/google/oauth_start.php" class="gc-btn">
            Conectar ao Google Agenda
          </a>
        </div>

      </div>
    </div>
  <?php endif; ?>
</div>