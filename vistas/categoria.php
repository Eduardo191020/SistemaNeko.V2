<?php
// vistas/categoria.php — Estilo corporativo tipo Artículos/Proveedores
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

// Permiso (módulo almacen/categorías). Ajusta si tu flag es distinto.
$canAlmacen = !empty($_SESSION['almacen']) && (int)$_SESSION['almacen'] === 1;

// Paleta corporativa
$nekoPrimary = '#1565c0';
$nekoPrimaryDark = '#0d47a1';
?>
<?php if ($canAlmacen): ?>
<style>
  :root{
    --neko-primary: <?= $nekoPrimary ?>;
    --neko-primary-dark: <?= $nekoPrimaryDark ?>;
    --neko-bg:#f5f7fb;
  }
  .content-wrapper{ background: var(--neko-bg); }
  .neko-card{
    background:#fff; border:1px solid rgba(2,24,54,.06);
    border-radius:14px; box-shadow:0 8px 24px rgba(2,24,54,.06);
    overflow:hidden; margin-top:10px;
  }
  .neko-card__header{
    display:flex; align-items:center; justify-content:space-between;
    background: linear-gradient(90deg, var(--neko-primary-dark), var(--neko-primary));
    color:#fff; padding:14px 18px;
  }
  .neko-card__title{
    font-size:1.1rem; font-weight:600; letter-spacing:.2px; margin:0;
    display:flex; gap:10px; align-items:center;
  }
  .neko-actions .btn{ border-radius:10px; }
  .neko-card__body{ padding:18px; }

  /* Tabla */
  #tbllistado thead th{ background:#eef3fb; color:#0b2752; }
  #tbllistado tfoot th{ background:#f8fafc; }

  .help-hint{ color:#64748b; font-size:.85rem; margin-top:4px; }
  .btn-primary{ background:var(--neko-primary); border-color:var(--neko-primary); }
  .btn-primary:hover{ background:var(--neko-primary-dark); border-color:var(--neko-primary-dark); }
  .form-group{ margin-bottom:16px; }
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="neko-card">
          <!-- Topbar -->
          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-tags"></i> Categoría</h1>
            <div class="neko-actions">
              <a href="../reportes/rptcategorias.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-clipboard"></i> Reporte
              </a>
              <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                <i class="fa fa-plus-circle"></i> Agregar
              </button>
            </div>
          </div>

          <!-- LISTADO -->
          <div class="neko-card__body panel-body table-responsive" id="listadoregistros">
            <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover" style="width:100%">
              <thead>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Estado</th>
              </thead>
              <tbody></tbody>
              <tfoot>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Estado</th>
              </tfoot>
            </table>
          </div>

          <!-- FORMULARIO -->
          <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
            <form name="formulario" id="formulario" method="POST" autocomplete="off">
              <input type="hidden" name="idcategoria" id="idcategoria">

              <div class="row">
                <div class="form-group col-lg-5 col-md-6">
                  <label>Nombre(*):</label>
                  <input type="text" class="form-control" name="nombre" id="nombre" maxlength="50" placeholder="Nombre de la categoría" required>
                </div>

                <div class="form-group col-lg-7 col-md-6">
                  <label>Descripción:</label>
                  <input type="text" class="form-control" name="descripcion" id="descripcion" maxlength="256" placeholder="Descripción de la categoría">
                </div>
              </div>

              <div class="row" style="margin-top:8px;">
                <div class="col-lg-12">
                  <button class="btn btn-primary" type="submit" id="btnGuardar">
                    <i class="fa fa-save"></i> Guardar
                  </button>
                  <button class="btn btn-danger" onclick="cancelarform()" type="button">
                    <i class="fa fa-arrow-circle-left"></i> Cancelar
                  </button>
                </div>
              </div>
            </form>
          </div>
          <!-- /FORMULARIO -->

        </div>
      </div>
    </div>
  </section>
</div>
<?php else: ?>
  <?php require 'noacceso.php'; ?>
<?php endif; ?>

<?php require 'footer.php'; ?>
<script type="text/javascript" src="scripts/categoria.js"></script>
<?php ob_end_flush(); ?>
