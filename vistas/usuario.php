<?php
// vistas/usuario.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Validador central (redirige a ../login.php si no hay sesión)
require_once __DIR__ . '/_requires_auth.php';

// Header del layout
require 'header.php';

// === Permiso del módulo (ACCESO/USUARIOS) ===
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

  .btn-primary{ background:var(--neko-primary); border-color:var(--neko-primary); }
  .btn-primary:hover{ background:var(--neko-primary-dark); border-color:var(--neko-primary-dark); }

  .section-title{
    font-weight:600; color:#0b2752; margin:16px 0 10px; display:flex; align-items:center; gap:8px;
  }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); display:inline-block; }

  .form-group{ margin-bottom:16px; }

  /* Panel permisos */
  .nk-permisos { max-height:220px; overflow:auto; margin-bottom:0; }
  .nk-ul-permisos { list-style:none; padding-left:0; margin:0; }

  /* Campo imagen */
  .nk-avatar { border:2px solid #e5e7eb; border-radius:10px; object-fit:cover; }

  /* Password helpers */
  .pwd-req { font-size:.85em; margin:3px 0; }
  .input-eye {
    position:absolute; right:12px; top:50%;
    transform:translateY(-50%); cursor:pointer; opacity:.75; user-select:none;
  }
  .input-eye:hover { opacity:1; }
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
            <h1 class="neko-card__title"><i class="fa fa-users"></i> Usuarios</h1>
            <div class="neko-actions">
              <a href="../reportes/rptusuarios.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
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
                <th>Tipo Doc.</th>
                <th>Número</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Cargo</th>
                <th>Foto</th>
                <th>Estado</th>
              </thead>
              <tbody></tbody>
              <tfoot>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Tipo Doc.</th>
                <th>Número</th>
                <th>Teléfono</th>
                <th>Email</th>
                <th>Cargo</th>
                <th>Foto</th>
                <th>Estado</th>
              </tfoot>
            </table>
          </div>

          <!-- FORMULARIO (mantiene IDs esperados por scripts/usuario.js) -->
          <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
            <form name="formulario" id="formulario" method="POST" enctype="multipart/form-data" novalidate>
              <input type="hidden" name="idusuario" id="idusuario">

              <h4 class="section-title"><span class="dot"></span> Paso 1: Identificación</h4>
              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Tipo Documento (*):</label>
                  <select class="form-control" name="tipo_documento" id="tipo_documento" required>
                    <option value="">Seleccione...</option>
                    <option value="DNI">DNI</option>
                    <option value="RUC">RUC</option>
                    <option value="Carnet de Extranjería">Carnet de Extranjería</option>
                  </select>
                  <small class="text-muted">Selecciona el tipo de documento</small>
                </div>
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Número de Documento (*):</label>
                  <input type="text" class="form-control" name="num_documento" id="num_documento" required>
                  <small class="text-muted">Ingresa el número de documento</small>
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12">
                  <label>Nombre Completo / Razón Social (*):</label>
                  <input type="text" class="form-control" name="nombre" id="nombre" maxlength="100" placeholder="Se autocompletará con RENIEC/SUNAT" readonly required>
                  <small class="text-info"><i class="fa fa-info-circle"></i> Este campo se llena automáticamente al validar el documento</small>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Paso 2: Datos de Contacto</h4>
              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Email (*):</label>
                  <div style="position:relative;">
                    <input type="email" class="form-control" name="email" id="email" maxlength="50" placeholder="ejemplo@dominio.com" required>
                    <span id="email-status" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:1.1rem;opacity:.8;"></span>
                  </div>
                  <small class="text-muted">Se usará como usuario de acceso</small>
                </div>
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Teléfono (*):</label>
                  <input type="text" class="form-control" name="telefono" id="telefono" maxlength="15" placeholder="Número de teléfono" required>
                  <small class="text-muted">Solo números, guiones y espacios</small>
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Dirección:</label>
                  <input type="text" class="form-control" name="direccion" id="direccion" maxlength="70" placeholder="Dirección completa">
                </div>
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Rol (*):</label>
                  <select class="form-control" name="cargo" id="cargo" required>
                    <option value="">Seleccione...</option>
                    <option value="Administrador">Administrador</option>
                    <option value="Almacenero">Almacenero</option>
                    <option value="Vendedor">Vendedor</option>
                  </select>
                  <small class="text-muted">Rol del usuario en el sistema</small>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Paso 3: Seguridad y Accesos</h4>
              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                  <label>Contraseña (*):</label>
                  <div style="position:relative;">
                    <input type="password" class="form-control" name="clave" id="clave" maxlength="64" placeholder="Mínimo 10 caracteres" required>
                    <span class="input-eye" id="toggleClave">👁️</span>
                  </div>
                  <div id="pwd-strength" style="margin-top:8px; display:none;">
                    <div class="pwd-req" id="r-len"><i class="fa fa-times text-danger"></i> 10-64 caracteres</div>
                    <div class="pwd-req" id="r-up"><i class="fa fa-times text-danger"></i> 1 mayúscula</div>
                    <div class="pwd-req" id="r-low"><i class="fa fa-times text-danger"></i> 1 minúscula</div>
                    <div class="pwd-req" id="r-num"><i class="fa fa-times text-danger"></i> 1 número</div>
                    <div class="pwd-req" id="r-spe"><i class="fa fa-times text-danger"></i> 1 especial</div>
                  </div>
                </div>
                <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                  <label>Permisos (*):</label>
                  <div class="well well-sm nk-permisos">
                    <ul id="permisos" class="nk-ul-permisos"><!-- Se llenan dinámicamente --></ul>
                  </div>
                  <small class="text-muted">Selecciona los módulos a los que tendrá acceso</small>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Paso 4: Foto de Perfil (Opcional)</h4>
              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12">
                  <label>Imagen:</label>
                  <input type="file" class="form-control" name="imagen" id="imagen" accept="image/jpeg,image/png,image/gif">
                  <input type="hidden" name="imagenactual" id="imagenactual">
                  <small class="text-muted">JPG/PNG/GIF (máx. 2MB)</small>
                </div>
                <div class="form-group col-lg-6 col-md-6 col-sm-6 col-xs-12 text-center">
                  <img src="" width="150" height="150" id="imagenmuestra" class="nk-avatar" style="display:none;">
                </div>
              </div>

              <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:12px;">
                <button class="btn btn-primary btn-lg" type="submit" id="btnGuardar">
                  <i class="fa fa-save"></i> Guardar Usuario
                </button>
                <button class="btn btn-danger btn-lg" onclick="cancelarform()" type="button">
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

<?php
} else {
  require 'noacceso.php';
}

// Footer del layout
require 'footer.php';
?>
<!-- Scripts específicos de esta vista -->
<script type="text/javascript" src="scripts/usuario.js"></script>

<!-- Helpers mínimos (no interfieren con usuario.js) -->
<script>
  (function ready(fn){document.readyState!=='loading'?fn():document.addEventListener('DOMContentLoaded',fn)})(function(){
    // Mostrar/ocultar formulario (si tu usuario.js ya lo maneja, esto no molesta)
    // Ojito de contraseña
    var inputClave = document.getElementById('clave');
    var toggle = document.getElementById('toggleClave');
    if (toggle && inputClave) {
      toggle.addEventListener('click', function(){
        var type = inputClave.getAttribute('type') === 'password' ? 'text' : 'password';
        inputClave.setAttribute('type', type);
        this.textContent = (type === 'password') ? '👁️' : '🙈';
      });
      inputClave.addEventListener('focus', function(){
        var p = document.getElementById('pwd-strength'); if (p) p.style.display='block';
      });
    }
  });
</script>
<?php
ob_end_flush();
