<?php
// vistas/cliente.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

require_once __DIR__ . '/_requires_auth.php';

// (Opcional pero recomendado) helper con can('Permiso')
// Si aún no lo tienes, puedes comentarlo y se usará el flag legacy.
$canHelper = false;
$authzPath = __DIR__ . '/_authz.php';
if (file_exists($authzPath)) {
  require_once $authzPath;
  if (function_exists('can')) { $canHelper = true; }
}

/* =========================
   AUTORIZACIÓN PARA CLIENTES
   =========================
   Clientes pertenece al módulo "Ventas".
   1) Si existe can('Ventas'), úsalo.
   2) Fallback legacy: $_SESSION['ventas'] == 1
*/
$canClientes = $canHelper ? can('Ventas')
                          : (!empty($_SESSION['ventas']) && (int)$_SESSION['ventas'] === 1);

require 'header.php';

if ($canClientes):
?>
<style>
  :root{ --neko-primary:#1565c0; --neko-primary-dark:#0d47a1; --neko-bg:#f5f7fb; }
  .content-wrapper{ background:var(--neko-bg); }
  .neko-card{ background:#fff; border:1px solid rgba(2,24,54,.06); border-radius:14px; box-shadow:0 8px 24px rgba(2,24,54,.06); overflow:hidden; margin-top:10px; }
  .neko-card__header{ display:flex; align-items:center; justify-content:space-between; background:linear-gradient(90deg,var(--neko-primary-dark),var(--neko-primary)); color:#fff; padding:14px 18px; }
  .neko-card__title{ font-size:1.1rem; font-weight:600; letter-spacing:.2px; margin:0; display:flex; gap:10px; align-items:center; }
  .neko-actions .btn{ border-radius:10px; }
  .neko-card__body{ padding:18px; }
  #tbllistado thead th{ background:#eef3fb; color:#0b2752; }
  #tbllistado tfoot th{ background:#f8fafc; }
  .section-title{ font-weight:600; color:#0b2752; margin:16px 0 10px; display:flex; align-items:center; gap:8px; }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); }
  input[readonly].disabled{ background:#f3f4f6 !important; cursor:not-allowed; }
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="neko-card">

          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-users"></i> Cliente</h1>
            <div class="neko-actions">
              <a href="../reportes/rptclientes.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-clipboard"></i> Reporte
              </a>
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
                <th>Documento</th>
                <th>Número</th>
                <th>Teléfono</th>
                <th>Email</th>
              </thead>
              <tbody></tbody>
              <tfoot>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Documento</th>
                <th>Número</th>
                <th>Teléfono</th>
                <th>Email</th>
              </tfoot>
            </table>
          </div>

          <!-- FORMULARIO -->
          <div class="neko-card__body panel-body" id="formularioregistros">
            <form name="formulario" id="formulario" method="POST">
              <!-- Necesarios para guardado -->
              <input type="hidden" id="idpersona" name="idpersona">
              <input type="hidden" id="tipo_persona" name="tipo_persona" value="Cliente">
              <!-- espejo para cuando el select esté disabled -->
              <input type="hidden" id="tipo_documento_hidden" name="tipo_documento" value="DNI">

              <h4 class="section-title"><span class="dot"></span> Datos del documento</h4>

              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Tipo Documento:</label>
                  <select class="form-control selectpicker" id="tipo_documento_view">
                    <option value="DNI" selected>DNI</option>
                  </select>
                  <small class="text-muted">DNI (8 dígitos)</small>
                </div>

                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12" id="wrap-numdoc">
                  <label>Número Documento:</label>
                  <div class="input-group">
                    <span class="input-group-btn">
                      <button type="button" id="btnBuscarDoc" class="btn btn-default" title="Consultar RENIEC">
                        <i class="fa fa-search"></i>
                      </button>
                    </span>
                    <input type="text" class="form-control" name="num_documento" id="num_documento"
                           placeholder="DNI" maxlength="8" inputmode="numeric" pattern="\d{8}" autocomplete="off">
                  </div>
                  <small id="estadoDoc" style="display:block;margin-top:6px;color:#374151;font-weight:600;">Esperando número…</small>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Datos básicos</h4>

              <div class="row">
                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                  <label>Nombre (autocompletado):</label>
                  <input type="text" class="form-control disabled" name="nombre" id="nombre" maxlength="100" placeholder="Nombre" readonly>
                  <small class="text-muted">Se llena automáticamente desde RENIEC (DNI).</small>
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                  <label>Dirección (autocompletada):</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                    <input type="text" class="form-control disabled" name="direccion" id="direccion" placeholder="Dirección" readonly>
                  </div>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Contacto</h4>

              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Teléfono:</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                    <input type="text" class="form-control" name="telefono" id="telefono" maxlength="20" placeholder="Teléfono">
                  </div>
                  <small class="text-muted">Solo números; opcional.</small>
                </div>

                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Email:</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                    <input type="email" class="form-control" name="email" id="email" maxlength="50" placeholder="Email">
                  </div>
                </div>
              </div>

              <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:12px;">
                <button class="btn btn-primary" type="submit" id="btnGuardar">
                  <i class="fa fa-save"></i> Guardar
                </button>
                <button id="btnCancelar" class="btn btn-danger" type="button" onclick="cancelarform()">
                  <i class="fa fa-arrow-circle-left"></i> Cancelar
                </button>
              </div>
            </form>
          </div>
          <!-- /FORMULARIO -->

        </div>
      </div>
    </div>
  </section>
</div>

<?php
else:
  // Sin permiso
  require 'noacceso.php';
endif;

require 'footer.php';
?>
<script type="text/javascript" src="scripts/cliente.js"></script>
<?php
ob_end_flush();
