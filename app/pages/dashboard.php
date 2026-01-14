<?php
// app/pages/dashboard.php

$period = $_GET['period'] ?? 'month'; // week|month|today
$month  = $_GET['m'] ?? date('n');
$year   = $_GET['y'] ?? date('Y');

$month = max(1, min(12, (int)$month));
$year  = (int)$year;

$months = [
  1=>'Janeiro',2=>'Fevereiro',3=>'Março',4=>'Abril',5=>'Maio',6=>'Junho',
  7=>'Julho',8=>'Agosto',9=>'Setembro',10=>'Outubro',11=>'Novembro',12=>'Dezembro'
];

// Stubs (depois vem do Supabase)
$resultado = 6695.21;
$entradas  = 10000.00;
$saidas    = 2547.98;

$base = app_config('base_url', '');

$prevM = $month - 1; $prevY = $year;
$nextM = $month + 1; $nextY = $year;
if ($prevM < 1) { $prevM = 12; $prevY--; }
if ($nextM > 12) { $nextM = 1;  $nextY++; }
?>
<div class="page">
  <div class="page-head">
    <div class="period-nav">
      <a class="icon-btn"
         aria-label="Mês anterior"
         href="<?= h($base) ?>/app.php?page=dashboard&m=<?= $prevM ?>&y=<?= $prevY ?>&period=<?= h($period) ?>">
        <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
      </a>

      <div class="period-title"><?= h($months[$month]) ?></div>

      <a class="icon-btn"
         aria-label="Próximo mês"
         href="<?= h($base) ?>/app.php?page=dashboard&m=<?= $nextM ?>&y=<?= $nextY ?>&period=<?= h($period) ?>">
        <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
      </a>
    </div>

    <div class="segmented">
      <a class="<?= $period==='week'?'active':'' ?>" href="<?= h($base) ?>/app.php?page=dashboard&m=<?= $month ?>&y=<?= $year ?>&period=week">Semana</a>
      <a class="<?= $period==='month'?'active':'' ?>" href="<?= h($base) ?>/app.php?page=dashboard&m=<?= $month ?>&y=<?= $year ?>&period=month">Mês</a>
      <a class="<?= $period==='today'?'active':'' ?>" href="<?= h($base) ?>/app.php?page=dashboard&m=<?= $month ?>&y=<?= $year ?>&period=today">Hoje</a>
    </div>
  </div>

  <div class="grid-2">
    <section class="card card-pad">
      <div class="card-kpi-top">
        <div>
          <div class="muted">Resultado do Período</div>
          <div class="kpi">R$ <?= number_format($resultado, 2, ',', '.') ?></div>
          <div class="small muted">01/<?= str_pad((string)$month,2,'0',STR_PAD_LEFT) ?>/<?= $year ?> até 31/<?= str_pad((string)$month,2,'0',STR_PAD_LEFT) ?>/<?= $year ?></div>
        </div>
        <div class="kpi-delta down">↓ -74,8%</div>
      </div>

      <div class="mini-line">
        <canvas id="miniLine"></canvas>
      </div>
    </section>

    <section class="card card-pad">
      <div class="card-title-row">
        <div class="card-title">Evolução do Saldo no Período</div>
        <div class="small muted"><?= h($period==='today'?'Hoje':($period==='week'?'Semana':'Mês')) ?></div>
      </div>
      <div class="chart-line">
        <canvas id="saldoLine"></canvas>
      </div>
    </section>
  </div>

  <div class="grid-3">
    <section class="card card-pad">
      <div class="card-title-row">
        <div class="card-title">Entradas</div>
        <span class="pill pill-green">Receitas</span>
      </div>
      <div class="kpi green">R$ <?= number_format($entradas, 2, ',', '.') ?></div>
      <div class="split">
        <div class="small muted">Realizado</div>
        <div class="small green">R$ <?= number_format($entradas, 2, ',', '.') ?></div>
      </div>
      <div class="split">
        <div class="small muted">Previsto (a receber)</div>
        <div class="small muted">R$ 0,00</div>
      </div>
    </section>

    <section class="card card-pad">
      <div class="card-title-row">
        <div class="card-title">Saídas</div>
        <span class="pill pill-red">Despesas</span>
      </div>
      <div class="kpi red">R$ <?= number_format($saidas, 2, ',', '.') ?></div>
      <div class="split">
        <div class="small muted">Realizado</div>
        <div class="small red">R$ <?= number_format($saidas, 2, ',', '.') ?></div>
      </div>
      <div class="split">
        <div class="small muted">Previsto (a pagar)</div>
        <div class="small muted">R$ 2.609,67</div>
      </div>
    </section>

    <section class="card card-pad">
      <div class="card-title-row">
        <div class="card-title">Despesas</div>
        <div class="tabs">
          <button class="tab active" id="tabPago" type="button">Pagos</button>
          <button class="tab" id="tabApagar" type="button">A Pagar</button>
        </div>
      </div>

      <div class="donut-wrap">
        <div class="donut-canvas">
          <canvas id="donut"></canvas>
        </div>
        <div class="donut-legend" id="donutLegend"></div>
      </div>
    </section>
  </div>
</div>

<script>
(() => {
  // Mini line do Resultado do Período
  const mini = document.getElementById('miniLine');
  new Chart(mini, {
    type: 'line',
    data: {
      labels: ['1','2','3','4','5','6','7'],
      datasets: [{ data: [50,48,46,44,42,40,38], tension: .4, borderWidth: 2, pointRadius: 0 }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display:false }, tooltip: { enabled:false } },
      scales: { x: { display:false }, y: { display:false } }
    }
  });

  // Evolução do saldo
  const saldo = document.getElementById('saldoLine');
  new Chart(saldo, {
    type: 'line',
    data: {
      labels: ['01','05','10','15','20','25','31'],
      datasets: [
        { label:'Saldo', data:[7000,6800,6400,6200,6000,5900,6695], tension:.35, borderWidth:2, pointRadius:0, fill:true }
      ]
    },
    options: {
      responsive:true,
      maintainAspectRatio:false,
      plugins:{ legend:{ display:true } },
      interaction:{ mode:'index', intersect:false },
      scales:{
        x:{ grid:{ display:false } },
        y:{ grid:{ color:'rgba(0,0,0,.06)' } }
      }
    }
  });

  // Donut (Pagos vs A pagar)
  const donutEl = document.getElementById('donut');
  const legendEl = document.getElementById('donutLegend');

  const dataPago = [
    { label:'Saúde', value:679.00 },
    { label:'Mercado', value:567.70 },
    { label:'Pets', value:469.90 },
    { label:'Utilidades', value:381.38 },
    { label:'Cuidados pessoais', value:200.00 },
  ];

  const dataApagar = [
    { label:'Vestuário', value:1249.99 },
    { label:'Outros', value:788.26 },
    { label:'Pets', value:355.62 },
    { label:'Utilidades', value:134.99 },
    { label:'Impostos', value:80.90 },
  ];

  function renderLegend(items) {
    legendEl.innerHTML = items.map(i => `
      <div class="legend-row">
        <span class="dot"></span>
        <span class="legend-name">${i.label}</span>
        <span class="legend-val">R$ ${i.value.toFixed(2).replace('.',',')}</span>
      </div>
    `).join('');
  }

  const donut = new Chart(donutEl, {
    type: 'doughnut',
    data: {
      labels: dataPago.map(x=>x.label),
      datasets: [{ data: dataPago.map(x=>x.value), borderWidth: 0, cutout: '70%' }]
    },
    options: {
      responsive:true,
      maintainAspectRatio:false,
      plugins:{ legend:{ display:false } }
    }
  });

  renderLegend(dataPago);

  const tabPago = document.getElementById('tabPago');
  const tabApagar = document.getElementById('tabApagar');

  function setActive(a, b) { a.classList.add('active'); b.classList.remove('active'); }

  tabPago.addEventListener('click', () => {
    setActive(tabPago, tabApagar);
    donut.data.labels = dataPago.map(x=>x.label);
    donut.data.datasets[0].data = dataPago.map(x=>x.value);
    donut.update();
    renderLegend(dataPago);
  });

  tabApagar.addEventListener('click', () => {
    setActive(tabApagar, tabPago);
    donut.data.labels = dataApagar.map(x=>x.label);
    donut.data.datasets[0].data = dataApagar.map(x=>x.value);
    donut.update();
    renderLegend(dataApagar);
  });
})();
</script>