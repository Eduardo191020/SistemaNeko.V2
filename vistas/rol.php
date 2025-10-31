<?php
// vistas/rol.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Si usas validador central, descomenta:
// require_once __DIR__ . '/_requires_auth.php';

require 'header.php';

// === Permiso del módulo (ACCESO/ROLES) ===
$canAcceso = !empty($_SESSION['acceso']) && (int)$_SESSION['acceso'] === 1;

if ($canAcceso) {
?>
<!-- ====== Estilos corporativos (alineados a venta.php) ====== -->
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
  .form-group{ margin-bottom:16px; }

  /* Pequeños helpers */
  .section-title{
    font-weight:600; color:#0b2752; margin:6px 0 14px; display:flex; align-items:center; gap:8px;
  }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); display:inline-block; }
</style>

<!--Contenido-->
<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="neko-card">

          <!-- Header visual -->
          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-shield"></i> Roles</h1>
            <div class="neko-actions">
              <!-- Si deseas reporte de roles, agrega tu ruta aquí -->
              <!-- <a href="../reportes/rptroles.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-clipboard"></i> Reporte
              </a> -->
              <button class="btn btn-success" id="btnagregar" onclick="mostrarform(true)">
                <i class="fa fa-plus-circle"></i> Agregar
              </button>
            </div>
          </div>

          <!-- LISTADO -->
          <div class="neko-card__body panel-body table-responsive" id="listadoregistros">
            <table id="tbllistado" class="table table-striped table-bordered table-condensed table-hover">
              <thead>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Estado</th>
                <th>Creado</th>
              </thead>
              <tbody></tbody>
              <tfoot>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Estado</th>
                <th>Creado</th>
              </tfoot>
            </table>
          </div>

          <!-- FORMULARIO -->
          <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
            <form name="formulario" id="formulario" method="POST" novalidate>
              <h4 class="section-title"><span class="dot"></span> Datos del rol</h4>

              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-8 col-xs-12">
                  <label>Nombre (*):</label>
                  <input type="hidden" name="idrol" id="idrol">
                  <input
                    type="text"
                    class="form-control"
                    name="nombre"
                    id="nombre"
                    maxlength="50"
                    autocomplete="off"
                    pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,50}$"
                    title="Solo letras y espacios (3 a 50 caracteres)"
                    oninput="soloLetras(this)"
                    placeholder="Ej. Supervisor"
                    required
                  >
                </div>
              </div>

              <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:6px;">
                <button class="btn btn-primary" type="submit" id="btnGuardar">
                  <i class="fa fa-save"></i> Guardar
                </button>
                <button class="btn btn-danger" type="button" onclick="cancelarform()">
                  <i class="fa fa-arrow-circle-left"></i> Cancelar
                </button>
              </div>
            </form>
          </div>
          <!--/FORMULARIO-->

        </div><!-- /neko-card -->
      </div>
    </div>
  </section>
</div>
<!--Fin-Contenido-->

<?php
} else {
  require 'noacceso.php';
}

require 'footer.php';
?>
<script type="text/javascript" src="scripts/rol.js"></script>

<!-- Helpers mínimos (no interfieren con rol.js) -->
<script>
  // Restringe a letras y espacios (mantiene tu comportamiento actual)
  function soloLetras(el){
    if(!el) return;
    el.value = el.value.replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]+/g, ' ').replace(/\s{2,}/g, ' ').trimStart();
  }
</script>
<?php
ob_end_flush();
?>
