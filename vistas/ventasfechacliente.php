<?php
// vistas/ventasfechacliente.php (versión pulida + toolbar propia)
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

if (!isset($_SESSION["nombre"])) { header("Location: login.html"); exit; }

require 'header.php';

if (!empty($_SESSION['consultav']) && (int)$_SESSION['consultav'] === 1) {
  $hoy = date("Y-m-d");
?>
<style>
  :root{
    --neko-primary:#1565c0;
    --neko-primary-dark:#0d47a1;
    --neko-bg:#f5f7fb;
    --neko-border:#dbe3ef;
  }
  .content-wrapper{ background:var(--neko-bg); }

  .neko-card{
    background:#fff; border:1px solid rgba(2,24,54,.08);
    border-radius:14px; box-shadow:0 10px 28px rgba(2,24,54,.06);
    overflow:hidden; margin-top:12px;
  }
  .neko-card__header{
    display:flex; align-items:center; justify-content:space-between;
    background:linear-gradient(90deg,var(--neko-primary-dark),var(--neko-primary));
    color:#fff; padding:14px 18px;
  }
  .neko-card__title{
    font-size:1.05rem; font-weight:600; letter-spacing:.2px; margin:0;
    display:flex; gap:10px; align-items:center;
  }
  .neko-card__body{ padding:18px; }

  /* ===== Filtros ===== */
  .filters-row{
    display:grid; grid-template-columns: 1fr 1fr 1.6fr; gap:16px; margin-bottom:14px;
  }
  .filters-row .field{ display:flex; flex-direction:column; }
  .filters-row label{ font-weight:600; color:#0b2752; margin-bottom:6px; }
  .filters-row .form-control{
    height:38px; padding:7px 10px; border:1px solid var(--neko-border); border-radius:10px;
    transition: box-shadow .15s ease, border-color .15s ease;
  }
  .filters-row .form-control:focus{
    outline:0; border-color:#84aef8; box-shadow:0 0 0 3px rgba(59,130,246,.14);
  }
  .filters-row .combo{ display:flex; gap:10px; }
  .bootstrap-select{ width:100% !important; }
  .bootstrap-select>.dropdown-toggle{
    height:38px; border-radius:10px; border:1px solid var(--neko-border);
  }
  .btn-show{
    height:38px; display:inline-flex; align-items:center; gap:6px;
    padding:0 14px; border-radius:10px;
    background:var(--neko-primary); border-color:var(--neko-primary);
  }
  .btn-show:hover{ background:var(--neko-primary-dark); border-color:var(--neko-primary-dark); }

  /* ===== Toolbar propia ===== */
  .dt-toolbar{
    display:flex; align-items:center; justify-content:space-between;
    gap:14px; flex-wrap:nowrap; margin:10px 0 8px;
  }
  .dt-left{ display:flex; align-items:center; gap:8px; }
  .dt-right{ display:flex; align-items:center; gap:8px; }
  .dt-right label{ margin:0; font-weight:600; color:#0b2752; }
  .dt-right .form-control{ height:32px; padding:4px 8px; border:1px solid var(--neko-border); border-radius:10px; }

  /* Botones DataTables fijos */
  .dt-buttons-holder{ display:flex; align-items:center; gap:8px; min-height:38px; }
  #tbllistado_wrapper .dt-buttons{ display:flex; gap:8px; float:none !important; }
  #tbllistado_wrapper .dt-buttons .dt-button{ margin:0; }

  /* Encabezados de tabla */
  #tbllistado thead th{ background:#eef3fb; color:#0b2752; border-bottom-color:#dde6f3; }
  #tbllistado tfoot th{ background:#f8fafc; }

  /* Ocultar Mostrar/Buscar nativos (anti-duplicado) */
  #tbllistado_wrapper .dataTables_length,
  #tbllistado_wrapper .dataTables_filter{ display:none !important; }

  /* Responsive */
  @media (max-width: 1200px){
    .filters-row{ grid-template-columns: 1fr 1fr; }
    .filters-row .field:last-child{ grid-column:1 / -1; }
  }
  @media (max-width: 768px){
    .filters-row{ grid-template-columns: 1fr; }
    .dt-toolbar{ flex-wrap:wrap; }
  }
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="neko-card">
          <div class="neko-card__header">
            <h1 class="neko-card__title">
              <i class="fa fa-line-chart"></i> Consulta de Ventas por fecha y cliente
            </h1>
            <div></div>
          </div>

          <div class="neko-card__body">
            <!-- Filtros -->
            <div class="filters-row">
              <div class="field">
                <label for="fecha_inicio">Fecha Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" value="<?php echo $hoy; ?>">
              </div>
              <div class="field">
                <label for="fecha_fin">Fecha Fin</label>
                <input type="date" class="form-control" name="fecha_fin" id="fecha_fin" value="<?php echo $hoy; ?>">
              </div>
              <div class="field">
                <label for="idcliente">Cliente</label>
                <div class="combo">
                  <select name="idcliente" id="idcliente" class="form-control selectpicker" data-live-search="true" required></select>
                  <button class="btn btn-success btn-show" type="button" id="btnMostrar">
                    <i class="fa fa-search"></i> Mostrar
                  </button>
                </div>
              </div>
            </div>

            <!-- Toolbar propia -->
            <div class="dt-toolbar">
              <div class="dt-left">
                <div class="dt-buttons-holder"></div>
              </div>
              <div class="dt-right">
                <label>Mostrar :</label>
                <select id="customLength" class="form-control input-sm" style="width:auto;">
                  <option value="5">5</option>
                  <option value="10">10</option>
                  <option value="25">25</option>
                  <option value="50">50</option>
                  <option value="100">100</option>
                </select>
                <label>registros</label>

                <label style="margin-left:16px;">Buscar:</label>
                <input id="customSearch" class="form-control input-sm" style="width:240px;" placeholder="Cliente, usuario, comprobante...">
              </div>
            </div>

            <!-- Tabla -->
            <div class="panel-body table-responsive" id="listadoregistros">
              <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover" style="width:100%;">
                <thead>
                  <th>Fecha</th>
                  <th>Usuario</th>
                  <th>Cliente</th>
                  <th>Comprobante</th>
                  <th>Número</th>
                  <th>Total Venta</th>
                  <th>Impuesto</th>
                  <th>Estado</th>
                </thead>
                <tbody></tbody>
                <tfoot>
                  <th>Fecha</th>
                  <th>Usuario</th>
                  <th>Cliente</th>
                  <th>Comprobante</th>
                  <th>Número</th>
                  <th>Total Venta</th>
                  <th>Impuesto</th>
                  <th>Estado</th>
                </tfoot>
              </table>
            </div>
          </div><!-- /body -->
        </div><!-- /card -->

      </div>
    </div>
  </section>
</div>

<?php
} else { require 'noacceso.php'; }

require 'footer.php';
?>
<script type="text/javascript" src="scripts/ventasfechacliente.js"></script>
<?php ob_end_flush(); ?>
