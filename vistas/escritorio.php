<?php
// Activamos el almacenamiento en buffer
ob_start();

require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

if (!empty($_SESSION['escritorio']) && (int)$_SESSION['escritorio'] === 1) {

  require_once "../modelos/Consultas.php";
  $consulta = new Consultas();

  // Totales hoy (con fallback)
  $rsptac = $consulta->totalcomprahoy();  $regc = $rsptac ? $rsptac->fetch_object() : null;  $totalc = $regc->total_compra ?? 0;
  $rsptav = $consulta->totalventahoy();   $regv = $rsptav ? $rsptav->fetch_object() : null;  $totalv = $regv->total_venta  ?? 0;

  // Compras últimos 10 días
  $compras10 = $consulta->comprasultimos_10dias();
  $fechasc = ''; $totalesc = '';
  if ($compras10) {
    while ($reg = $compras10->fetch_object()) {
      $fechasc  .= '"' . $reg->fecha . '",';
      $totalesc .= (isset($reg->total) ? (float)$reg->total : 0) . ',';
    }
  }
  $fechasc  = rtrim($fechasc, ',');
  $totalesc = rtrim($totalesc, ',');

  // Ventas últimos 12 meses
  $ventas12 = $consulta->ventasultimos_12meses();
  $fechasv = ''; $totalesv = '';
  if ($ventas12) {
    while ($reg = $ventas12->fetch_object()) {
      $fechasv  .= '"' . $reg->fecha . '",';
      $totalesv .= (isset($reg->total) ? (float)$reg->total : 0) . ',';
    }
  }
  $fechasv  = rtrim($fechasv, ',');
  $totalesv = rtrim($totalesv, ',');
  ?>
  <style>
    :root{
      --neko-primary:#1565c0;
      --neko-primary-dark:#0d47a1;
      --neko-bg:#f5f7fb;
      --card-border:1px solid rgba(2,24,54,.06);
      --shadow:0 8px 24px rgba(2,24,54,.06);
    }
    .content-wrapper{ background:var(--neko-bg); }
    .neko-card{
      background:#fff; border:var(--card-border);
      border-radius:14px; box-shadow:var(--shadow); overflow:hidden; margin-top:10px;
    }
    .neko-card__header{
      display:flex; align-items:center; justify-content:space-between;
      background:linear-gradient(90deg, var(--neko-primary-dark), var(--neko-primary));
      color:#fff; padding:14px 18px;
    }
    .neko-card__title{
      font-size:1.1rem; font-weight:600; letter-spacing:.2px; margin:0;
      display:flex; gap:10px; align-items:center;
    }
    .neko-card__body{ padding:18px; }

    /* Tarjetas KPI */
    .kpi{
      display:flex; align-items:center; gap:14px;
      background:#fff; border:var(--card-border); border-radius:12px; box-shadow:var(--shadow);
      padding:14px 16px; height:100%;
    }
    .kpi__icon{
      width:46px; height:46px; display:grid; place-items:center; border-radius:10px;
      background:#e3f2fd; color:#0d47a1; font-size:20px;
    }
    .kpi__label{ color:#334155; margin:0; font-size:.95rem; }
    .kpi__value{ margin:0; font-weight:700; color:#0b2752; font-size:1.25rem; }

    /* Contenedor de gráfico (altura fija para evitar bucle infinito) */
    .chart-card{
      background:#fff; border:var(--card-border); border-radius:12px; box-shadow:var(--shadow);
      padding:14px 16px;
    }
    .chart-card h4{
      margin:0 0 10px; font-size:1rem; color:#0b2752; font-weight:600;
    }
    .chart-holder{
      position: relative;
      height: 280px;      /* ajustable: 240–320px */
      width: 100%;
    }

    /* Utilidades */
    .mb-16{ margin-bottom:16px; }
  </style>

  <div class="content-wrapper">
    <section class="content">
      <div class="row">
        <div class="col-md-12">

          <div class="neko-card">
            <div class="neko-card__header">
              <h1 class="neko-card__title">
                <i class="fa fa-dashboard"></i> Escritorio
              </h1>
              <div class="neko-actions"><!-- reservado --></div>
            </div>

            <div class="neko-card__body">
              <!-- KPIs -->
              <div class="row mb-16">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon"><i class="ion ion-bag"></i></div>
                    <div>
                      <p class="kpi__label">Compras de hoy</p>
                      <h3 class="kpi__value">S/ <?php echo number_format((float)$totalc, 2, '.', ''); ?></h3>
                      <a href="ingreso.php" class="small text-primary">Ir a Compras <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="kpi">
                    <div class="kpi__icon"><i class="ion ion-bag"></i></div>
                    <div>
                      <p class="kpi__label">Ventas de hoy</p>
                      <h3 class="kpi__value">S/ <?php echo number_format((float)$totalv, 2, '.', ''); ?></h3>
                      <a href="venta.php" class="small text-primary">Ir a Ventas <i class="fa fa-arrow-circle-right"></i></a>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Gráficos -->
              <div class="row">
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="chart-card">
                    <h4>Compras de los últimos 10 días</h4>
                    <div class="chart-holder">
                      <canvas id="compras"></canvas>
                    </div>
                  </div>
                </div>
                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12 mb-16">
                  <div class="chart-card">
                    <h4>Ventas de los últimos 12 meses</h4>
                    <div class="chart-holder">
                      <canvas id="ventas"></canvas>
                    </div>
                  </div>
                </div>
              </div>
            </div> <!-- /body -->
          </div> <!-- /card -->

        </div>
      </div>
    </section>
  </div>

  <?php require 'footer.php'; ?>

  <!-- IMPORTANTE: deja solo uno -->
  <script src="../public/js/Chart.bundle.min.js"></script>
  <!-- <script src="../public/js/chart.min.js"></script>  --><!-- NO usar junto con el anterior -->

  <script>
    // Vars globales para destruir si se reinyecta
    var chartCompras, chartVentas;

    // --------- Compras (10 días) ----------
    (function(){
      var el = document.getElementById("compras");
      if (!el) return;
      var ctx = el.getContext('2d');
      if (chartCompras) { chartCompras.destroy(); }
      chartCompras = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: [<?php echo $fechasc ?: ''; ?>],
          datasets: [{
            label: 'Compras en S/ (últimos 10 días)',
            data: [<?php echo $totalesc ?: ''; ?>],
            backgroundColor: 'rgba(21, 101, 192, 0.25)',
            borderColor: 'rgba(21, 101, 192, 1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,   // usa la altura de .chart-holder
          scales: {
            yAxes: [{ ticks: { beginAtZero: true } }]
          },
          legend: { display: true }
        }
      });
    })();

    // --------- Ventas (12 meses) ----------
    (function(){
      var el = document.getElementById("ventas");
      if (!el) return;
      var ctx = el.getContext('2d');
      if (chartVentas) { chartVentas.destroy(); }
      chartVentas = new Chart(ctx, {
        type: 'bar',
        data: {
          labels: [<?php echo $fechasv ?: ''; ?>],
          datasets: [{
            label: 'Ventas en S/ (últimos 12 meses)',
            data: [<?php echo $totalesv ?: ''; ?>],
            backgroundColor: 'rgba(13, 71, 161, 0.25)',
            borderColor: 'rgba(13, 71, 161, 1)',
            borderWidth: 1
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          scales: {
            yAxes: [{ ticks: { beginAtZero: true } }]
          },
          legend: { display: true }
        }
      });
    })();
  </script>

  <?php
} else {
  require 'noacceso.php';
  require 'footer.php';
}

ob_end_flush();
