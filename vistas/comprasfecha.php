<?php
// vistas/comprasfecha.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

if (!isset($_SESSION["nombre"])) { header("Location: login.html"); exit; }

require 'header.php';

if (!empty($_SESSION['consultac']) && (int)$_SESSION['consultac'] === 1) {
  $hoy = date("Y-m-d");
?>
<style>
  :root{
    --neko-primary:#1565c0;
    --neko-primary-dark:#0d47a1;
    --neko-bg:#f5f7fb;
  }
  .content-wrapper{ background:var(--neko-bg); }

  .neko-card{
    background:#fff; border:1px solid rgba(2,24,54,.06);
    border-radius:14px; box-shadow:0 8px 24px rgba(2,24,54,.06);
    overflow:hidden; margin-top:10px;
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

  /* Filtros */
  .filters-row{
    display:grid; grid-template-columns:1fr 1fr; gap:18px; margin-bottom:14px;
  }
  .filters-row label{ font-weight:600; color:#0b2752; display:block; margin-bottom:6px; }
  .filters-row .form-control{ height:36px; padding:6px 10px; border:1px solid #dbe3ef; border-radius:10px; }

  /* Toolbar: izquierda (botones), derecha (length + search propios) */
  .dt-toolbar{
    display:flex; align-items:center; justify-content:space-between;
    gap:14px;
    flex-wrap:nowrap;        /* clave: evita “saltos” */
    margin-bottom:10px;
  }
  .dt-left{ display:flex; align-items:center; gap:8px; }
  .dt-right{ display:flex; align-items:center; gap:8px; }
  .dt-right label{ margin:0; font-weight:600; color:#0b2752; }
  .dt-right .form-control{ height:32px; padding:4px 8px; border:1px solid #dbe3ef; border-radius:10px; }

  /* Contenedor fijo para Buttons (evita movimiento en redraw) */
  .dt-buttons-holder{
    display:flex; align-items:center; gap:8px;
    min-height:38px;
  }
  /* Contenedor real de Buttons (de DT) en línea */
  .dt-buttons{ display:flex; gap:8px; }
  .dt-buttons .dt-button{ margin:0; }
  .dt-buttons .dt-button:not(:last-child){ margin-right:0; }

  /* Encabezados de tabla */
  #tbllistado thead th{ background:#eef3fb; color:#0b2752; }
  #tbllistado tfoot th{ background:#f8fafc; }

  /* Kill switch anti-duplicado por si un global inyecta l/f */
  #tbllistado_wrapper .dataTables_length,
  #tbllistado_wrapper .dataTables_filter{ display:none !important; }

  /* Responsive: en pantallas pequeñas permitimos envolver */
  @media (max-width: 992px){
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
              <i class="fa fa-calendar-check-o"></i> Consulta de Compras por fecha
            </h1>
            <div></div>
          </div>

          <div class="neko-card__body">
            <!-- Filtros -->
            <div class="filters-row">
              <div>
                <label for="fecha_inicio">Fecha Inicio</label>
                <input type="date" class="form-control" name="fecha_inicio" id="fecha_inicio" value="<?php echo $hoy; ?>">
              </div>
              <div>
                <label for="fecha_fin">Fecha Fin</label>
                <input type="date" class="form-control" name="fecha_fin" id="fecha_fin" value="<?php echo $hoy; ?>">
              </div>
            </div>

            <!-- Toolbar custom -->
            <div class="dt-toolbar">
              <div class="dt-left">
                <!-- Aquí DataTables anclará sus botones (via comprasfecha.js) -->
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
                <input id="customSearch" class="form-control input-sm" style="width:240px;" placeholder="Proveedor, usuario, comprobante...">
              </div>
            </div>

            <!-- Tabla -->
            <div class="panel-body table-responsive" id="listadoregistros">
              <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover" style="width:100%;">
                <thead>
                  <th>Fecha</th>
                  <th>Usuario</th>
                  <th>Proveedor</th>
                  <th>Comprobante</th>
                  <th>Número</th>
                  <th>Total Compra</th>
                  <th>Impuesto</th>
                  <th>Estado</th>
                </thead>
                <tbody></tbody>
                <tfoot>
                  <th>Fecha</th>
                  <th>Usuario</th>
                  <th>Proveedor</th>
                  <th>Comprobante</th>
                  <th>Número</th>
                  <th>Total Compra</th>
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

<!-- JS específico de la vista -->
<script type="text/javascript" src="scripts/comprasfecha.js"></script>
<?php ob_end_flush(); ?>
