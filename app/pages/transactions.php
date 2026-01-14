<?php
// app/pages/transactions.php
$base = app_config('base_url', '');
?>

<div class="page">

  <!-- Cabeçalho (igual ao print: título + subtítulo) -->
  <div class="page-head">
    <div>
      <h1 class="page-title">Transações</h1>
      <div class="muted small" style="margin-top:4px;">
        Verifique suas transações completas.
      </div>
    </div>
  </div>

  <!-- Barra de abas + busca (igual ao print) -->
  <section class="card">
    <div class="card-pad" style="padding-bottom:12px;">
      <div style="display:flex; align-items:center; gap:12px; flex-wrap:wrap;">
        <!-- Tabs -->
        <div class="segmented" id="txTabs" style="margin:0;">
          <a class="active" href="javascript:void(0)" data-filter="all">Todas</a>
          <a href="javascript:void(0)" data-filter="paid">Pagos</a>
          <a href="javascript:void(0)" data-filter="received">Recebidos</a>
          <a href="javascript:void(0)" data-filter="payable">A pagar</a>
          <a href="javascript:void(0)" data-filter="receivable">A receber</a>
        </div>

        <!-- Busca -->
        <div style="flex:1; min-width:260px;">
          <input
            id="txSearch"
            class="cat-input"
            type="text"
            placeholder="Pesquisar por descrição, categoria..."
            style="width:100%;"
            autocomplete="off"
          />
        </div>
      </div>
    </div>

    <!-- Tabela (colunas iguais ao print) -->
    <div class="table-wrap">
      <table class="table" id="txTable">
        <thead>
          <tr>
            <th>Descrição</th>
            <th class="right">Valor</th>
            <th>Categoria</th>
            <th>Tipo</th>
            <th>Status</th>
            <th>Data</th>
            <th class="right">Ações</th>
          </tr>
        </thead>
        <tbody id="transactionsBody">
          <!-- JS injeta as linhas -->
        </tbody>
      </table>
    </div>

    <!-- Paginação (10 por página — o JS controla) -->
    <div class="card-pad" style="padding-top:12px;">
      <div style="display:flex; align-items:center; justify-content:center; gap:10px;">
        <button class="btn" id="txPrev" type="button"
          style="background:#fff;border:1px solid rgba(15,23,42,10);">
          Anterior
        </button>

        <div id="txPages" style="display:flex; gap:8px; align-items:center;">
          <!-- JS injeta os botões/bolinhas de página -->
        </div>

        <button class="btn" id="txNext" type="button"
          style="background:#fff;border:1px solid rgba(15,23,42,10);">
          Próximo
        </button>
      </div>
    </div>
  </section>

</div>

<!-- Drawer/Modal (mantive seu padrão atual pra editar/adicionar) -->
<div class="backdrop" id="txBackdrop" hidden></div>

<div class="sidebar" id="txModal"
     hidden
     style="inset:0;width:100%;max-width:620px;right:0;left:auto;transform:translateX(110%);">
  <div class="sidebar-head">
    <div style="display:flex;align-items:center;gap:10px;">
      <div class="brand-mark" aria-hidden="true"></div>
      <div style="font-weight:700;" id="txModalTitle">Editar Transação</div>
    </div>
    <button class="icon-btn" id="txClose" type="button">✕</button>
  </div>

  <div style="padding:14px;">
    <input type="hidden" id="txId" value="" />

    <label class="field">
      <span>Descrição</span>
      <input id="txItem" type="text" placeholder="Ex: Mercado" />
    </label>

    <div class="grid-2">
      <label class="field">
        <span>Valor</span>
        <input id="txAmount" type="number" step="0.01" min="0" placeholder="0,00" />
      </label>

      <label class="field">
        <span>Data</span>
        <input id="txDate" type="date" />
      </label>
    </div>

    <div class="grid-2">
      <label class="field">
        <span>Tipo</span>
        <select id="txType" style="width:100%;padding:12px;border:1px solid rgba(15,23,42,10);border-radius:12px;">
          <option value="expense">Despesa</option>
          <option value="income">Receita</option>
        </select>
      </label>

      <label class="field">
        <span>Status</span>
        <select id="txStatus" style="width:100%;padding:12px;border:1px solid rgba(15,23,42,10);border-radius:12px;">
          <option value="paid">Pago</option>
          <option value="due">A Pagar</option>
          <option value="received">Recebido</option>
          <option value="receivable">A Receber</option>
        </select>
      </label>
    </div>

    <label class="field">
      <span>Categoria</span>
      <select id="txCategory" style="width:100%;padding:12px;border:1px solid rgba(15,23,42,10);border-radius:12px;">
        <option value="">Sem categoria</option>
      </select>
    </label>

    <div style="display:flex; gap:10px; margin-top:12px;">
      <button class="btn btn-black" id="txSave" type="button" style="flex:1;">Salvar</button>
      <button class="btn" id="txCancel" type="button" style="flex:1;border:1px solid rgba(15,23,42,10);background:#fff;">
        Cancelar
      </button>
    </div>
  </div>
</div>

<script>
  window.__PAGE__ = 'transactions';
</script>