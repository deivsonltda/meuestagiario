<?php
$base = app_config('base_url');
?>
<script>window.__PAGE__='agenda';</script>

<div class="page">
  <div class="page-head">
    <div>
      <h1 class="page-title">Minha Agenda</h1>
      <div class="muted small" style="margin-top:4px;">
        Verifique seus compromissos e afazeres. Você pode integrar sua agenda do Google na aba "minha conta".
      </div>
    </div>
  </div>

  <div class="agenda-wrap">
    <!-- CALENDÁRIO -->
    <section class="card agenda-cal">
      <div class="card-pad">
        <div class="agenda-cal-head">
          <div class="agenda-cal-nav">
            <button class="icon-btn" id="btnPrevMonth" title="Mês anterior" type="button" aria-label="Mês anterior">
              <i class="fa-solid fa-chevron-left" aria-hidden="true"></i>
            </button>

            <div class="agenda-cal-month" id="calMonthLabel">—</div>

            <button class="icon-btn" id="btnNextMonth" title="Próximo mês" type="button" aria-label="Próximo mês">
              <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
            </button>
          </div>

          <button class="btn" id="btnToday" type="button">Hoje</button>
        </div>

        <div class="agenda-dow">
          <div>DOM</div><div>SEG</div><div>TER</div><div>QUA</div><div>QUI</div><div>SEX</div><div>SÁB</div>
        </div>

        <div class="agenda-grid" id="calGrid"></div>
      </div>
    </section>

    <!-- PAINEL DO DIA -->
    <section class="card agenda-day">
      <div class="card-pad">
        <div class="agenda-day-title" id="dayTitle">—</div>
        <div class="agenda-day-list" id="dayList"></div>
      </div>
    </section>
  </div>
</div>