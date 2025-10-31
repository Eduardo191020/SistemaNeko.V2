<?php
// vistas/venta.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Validador central (redirige a ../login.php si no hay sesión)
require_once __DIR__ . '/_requires_auth.php';

$tz     = new DateTimeZone('America/Lima');
$today  = new DateTime('today', $tz);
$minDT  = (clone $today)->modify('-2 days');
$maxDT  = (clone $today)->modify('+2 days');

$valToday = $today->format('Y-m-d');
$valMin   = $minDT->format('Y-m-d');
$valMax   = $maxDT->format('Y-m-d');

// Header del layout
require 'header.php';

// === Permiso del módulo (VENTAS) ===
$canVentas = !empty($_SESSION['ventas']) && (int)$_SESSION['ventas'] === 1;

if ($canVentas) {
?>
<!-- ====== Estilos corporativos (sin romper IDs/JS existentes) ====== -->
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
    font-size:1.1rem; font-weight:600; letter-spacing:.2px; margin:0;
    display:flex; gap:10px; align-items:center;
  }
  .neko-actions .btn{ border-radius:10px; }
  .neko-card__body{ padding:18px; }

  /* Tabla principal */
  #tbllistado thead th{ background:#eef3fb; color:#0b2752; }
  #tbllistado tfoot th{ background:#f8fafc; }

  /* Detalle */
  #detalles thead{ background:#eef3fb !important; }
  #detalles tfoot th{ background:#f8fafc; }
  #total{ margin:0; font-weight:700; }

  .btn-primary{ background:var(--neko-primary); border-color:var(--neko-primary); }
  .btn-primary:hover{ background:var(--neko-primary-dark); border-color:var(--neko-primary-dark); }

  .section-title{
    font-weight:600; color:#0b2752; margin:16px 0 10px; display:flex; align-items:center; gap:8px;
  }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); display:inline-block; }

  .form-group{ margin-bottom:16px; }

  /* Controles de fecha junto al buscador DataTables */
  .dataTables_filter {
    display: flex;
    align-items: center;
    gap: 14px;
    flex-wrap: wrap;
    justify-content: flex-end;
  }
  .dataTables_filter label { margin: 0; }
  .dt-date-filters{
    display:flex; align-items:center; gap:10px; flex-wrap:wrap;
  }
  .dt-date-item{
    display:flex; align-items:center; gap:6px; margin:0;
    font-weight: 500; color:#334155;
  }
  .dt-rounded{
    border-radius: 12px !important;
  }
  .dt-date-filters input[type="date"]{
    width: 155px;
    padding: 6px 10px;
    border: 1px solid #dbe3ef;
    background: #fff;
    transition: box-shadow .15s ease, border-color .15s ease;
  }
  .dt-date-filters input[type="date"]:focus{
    outline:0;
    border-color:#84aef8;
    box-shadow:0 0 0 3px rgba(59,130,246,.15);
  }
  .dt-date-filters .btn{
    padding:6px 10px; font-weight:600;
    box-shadow:0 1px 2px rgba(0,0,0,.05);
  }
</style>

<!--Contenido-->
<div class="content-wrapper">
  <!-- Main content -->
  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="neko-card">
          <!-- Header visual -->
          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-shopping-cart"></i> Venta</h1>
            <div class="neko-actions">
              <a href="../reportes/rptventas.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-clipboard"></i> Reporte
              </a>
              <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                <i class="fa fa-plus-circle"></i> Agregar
              </button>
            </div>
          </div>

          <!-- Filtros de fecha (se reubican a la derecha junto al buscador DT) -->
          <div id="dateFilters" style="display:none">
            <div class="dt-date-filters">
              <label class="dt-date-item">
                <span>Desde</span>
                <input type="date" id="filtro_desde" class="form-control input-sm dt-rounded">
              </label>
              <label class="dt-date-item">
                <span>Hasta</span>
                <input type="date" id="filtro_hasta" class="form-control input-sm dt-rounded">
              </label>
              <button type="button" id="btnFiltrar" class="btn btn-primary btn-sm dt-rounded">
                <i class="fa fa-filter"></i> Filtrar
              </button>
              <button type="button" id="btnLimpiarFiltro" class="btn btn-default btn-sm dt-rounded" title="Limpiar filtro">
                <i class="fa fa-eraser"></i>
              </button>
            </div>
          </div>

          <!-- LISTADO -->
          <div class="neko-card__body panel-body table-responsive" id="listadoregistros">
            <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
              <thead>
                <th>Opciones</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Usuario</th>
                <th>Documento</th>
                <th>Número</th>
                <th>Total Venta</th>
                <th>Estado</th>
              </thead>
              <tbody></tbody>
              <tfoot>
                <th>Opciones</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Usuario</th>
                <th>Documento</th>
                <th>Número</th>
                <th>Total Venta</th>
                <th>Estado</th>
              </tfoot>
            </table>
          </div>

          <!-- FORMULARIO (mismos IDs que usan los JS clásicos de ventas) -->
          <div class="neko-card__body panel-body" style="height: 100%;" id="formularioregistros">
            <form name="formulario" id="formulario" method="POST">

              <h4 class="section-title"><span class="dot"></span> Datos de la venta</h4>

              <div class="row">
                <div class="form-group col-lg-8 col-md-8 col-sm-8 col-xs-12">
                  <label>Cliente(*):</label>
                  <input type="hidden" name="idventa" id="idventa">
                  <select id="idcliente" name="idcliente" class="form-control selectpicker" data-live-search="true" required>
                  </select>
                </div>

                <div class="form-group col-lg-4 col-md-4 col-sm-4 col-xs-12">
                  <label>Fecha(*):</label>
                  <input
                    type="date"
                    class="form-control"
                    name="fecha_hora"
                    id="fecha_hora"
                    required
                    value="<?= $valToday ?>"
                    min="<?= $valMin ?>"
                    max="<?= $valMax ?>"
                    title="Solo entre <?= $minDT->format('d/m/Y') ?> y <?= $maxDT->format('d/m/Y') ?>."
                  >
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Tipo Comprobante(*):</label>
                  <select name="tipo_comprobante" id="tipo_comprobante" class="form-control selectpicker" required>
                    <option value="Boleta">Boleta</option>
                    <option value="Factura">Factura</option>
                    <option value="Ticket">Ticket</option>
                  </select>
                </div>

                <div class="form-group col-lg-2 col-md-2 col-sm-6 col-xs-12">
                  <label>Serie:</label>
                  <input type="input" class="form-control" name="serie_comprobante" id="serie_comprobante" maxlength="7" placeholder="Serie" onkeypress='return event.charCode >= 48 && event.charCode <= 57'>
                </div>

                <div class="form-group col-lg-2 col-md-2 col-sm-6 col-xs-12">
                  <label>Número:</label>
                  <input type="input" class="form-control" name="num_comprobante" id="num_comprobante" maxlength="10" placeholder="Número" onkeypress='return event.charCode >= 48 && event.charCode <= 57' required>
                </div>

                <div class="form-group col-lg-2 col-md-2 col-sm-6 col-xs-12">
                  <label>Impuesto:</label>
                  <input type="text" class="form-control" name="impuesto" id="impuesto" onkeypress='return event.charCode >= 48 && event.charCode <= 57' required>
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-3 col-md-3 col-sm-6 col-xs-12">
                  <a data-toggle="modal" href="#myModal">
                    <button id="btnAgregarArt" type="button" class="btn btn-primary">
                      <span class="fa fa-plus"></span> Agregar Artículos
                    </button>
                  </a>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Detalle de la venta</h4>

              <div class="col-lg-12 col-sm-12 col-md-12 col-xs-12 table-responsive">
                <table id="detalles" class="table table-striped table-bordered table-condensed table-hover">
                  <thead>
                    <th>Opciones</th>
                    <th>Artículo</th>
                    <th>Cantidad</th>
                    <th>Precio Venta</th>
                    <th>Descuento</th>
                    <th>Subtotal</th>
                  </thead>
                  <tfoot>
                    <th>TOTAL</th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th>
                      <h4 id="total">S/. 0.00</h4>
                      <input type="hidden" name="total_venta" id="total_venta">
                    </th>
                  </tfoot>
                  <tbody></tbody>
                </table>
              </div>

              <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:12px;">
                <button class="btn btn-primary" type="submit" id="btnGuardar">
                  <i class="fa fa-save"></i> Guardar
                </button>

                <button id="btnCancelar" class="btn btn-danger" onclick="cancelarform()" type="button">
                  <i class="fa fa-arrow-circle-left"></i> Cancelar
                </button>
              </div>
            </form>
          </div>
          <!--/FORMULARIO-->

        </div><!-- /neko-card -->
      </div><!-- /.col -->
    </div><!-- /.row -->
  </section><!-- /.content -->
</div><!-- /.content-wrapper -->
<!--Fin-Contenido-->

<!-- Modal: Selección de artículos -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-dialog" style="width: 960px;">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
        <h4 class="modal-title">Seleccione un Artículo</h4>
      </div>
      <div class="modal-body table-responsive">
        <table id="tblarticulos" class="table table-striped table-bordered table-condensed table-hover">
          <thead>
            <th>Opciones</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Código</th>
            <th>Stock</th>
            <th>Precio Venta</th>
            <th>Imagen</th>
          </thead>
          <tbody></tbody>
          <tfoot>
            <th>Opciones</th>
            <th>Nombre</th>
            <th>Categoría</th>
            <th>Código</th>
            <th>Stock</th>
            <th>Precio Venta</th>
            <th>Imagen</th>
          </tfoot>
        </table>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php
} else {
  require 'noacceso.php';
}

// Footer del layout (carga JS base: jQuery, Bootstrap, DataTables, etc.)
require 'footer.php';
?>
<!-- Scripts específicos de esta vista -->
<script type="text/javascript" src="scripts/venta.js"></script>
<?php
ob_end_flush();
