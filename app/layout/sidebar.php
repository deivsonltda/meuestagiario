<?php
$base = app_config('base_url', '');
$current = $_GET['page'] ?? 'dashboard';
?>
<aside class="sidebar" id="sidebar">
  <div class="sidebar-head">
    <div class="logo">
      <div class="brand-mark"></div>
      <div class="brand-text"><?= h(app_config('app_name')) ?></div>
    </div>
    <button class="icon-btn" id="btnCloseSidebar" aria-label="Fechar menu">✕</button>
  </div>

  <div class="sidebar-links">
    <a class="<?= $current === 'dashboard' ? 'active' : '' ?>" href="<?= h($base) ?>/app.php?page=dashboard">Visão Geral</a>
    <a class="<?= $current === 'transactions' ? 'active' : '' ?>" href="<?= h($base) ?>/app.php?page=transactions">Transações</a>
    <a class="<?= $current === 'cards' ? 'active' : '' ?>" href="<?= h($base) ?>/app.php?page=cards">Cartões de crédito</a>
    <a class="<?= $current === 'categories' ? 'active' : '' ?>" href="<?= h($base) ?>/app.php?page=categories">Minhas Categorias</a>
    <a class="<?= $current === 'agenda' ? 'active' : '' ?>" href="<?= h($base) ?>/app.php?page=agenda">Agenda</a>

    <!-- Minha Conta como item normal do menu (igual ao seu print) -->
    <a class="<?= $current === 'account' ? 'active' : '' ?>" href="<?= h($base) ?>/app.php?page=account&tab=perfil">Minha Conta</a>
  </div>

  <!-- Sair separado embaixo -->
  <div class="sidebar-footer">
    <a href="<?= h($base) ?>/logout.php">Sair</a>
  </div>

</aside>
<div class="backdrop" id="backdrop"></div>