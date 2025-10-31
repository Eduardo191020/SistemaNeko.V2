<?php
// vistas/proveedor.php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

$canCompras = !empty($_SESSION['compras']) && (int)$_SESSION['compras'] === 1;

// Paleta corporativa (alineada al header azul)
$nekoPrimary = '#1565c0';
$nekoPrimaryDark = '#0d47a1';
?>
<?php if ($canCompras): ?>
<!-- ====== Estilos específicos de la vista ====== -->
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
  .neko-card .neko-card__header{
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

  /* Cinta de secciones del formulario */
  .section-title{
    font-weight:600; color:#0b2752; margin:16px 0 10px; display:flex; align-items:center; gap:8px;
  }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); display:inline-block; }

  /* Inputs con icono */
  .input-group-addon{
    background:#eef3fb; border-color:#d8e2f1; color:#1b3350; font-weight:600;
  }

  /* Ayudas y chips de estado */
  .help-hint{ color:#64748b; font-size:.85rem; margin-top:4px; }
  .chip{
    display:inline-flex; align-items:center; gap:6px;
    padding:4px 8px; border-radius:999px; font-size:.78rem; font-weight:600;
  }
  .chip.info{ background:#e3f2fd; color:#0d47a1; }
  .chip.ok{ background:#e8f5e9; color:#1b5e20; }
  .chip.warn{ background:#fff3e0; color:#e65100; }
  .chip.err{ background:#ffebee; color:#b71c1c; }

  .readonly{ background:#f3f4f6 !important; color:#475569 !important; }
  .btn-primary{ background:var(--neko-primary); border-color:var(--neko-primary); }
  .btn-primary:hover{ background:var(--neko-primary-dark); border-color:var(--neko-primary-dark); }

  /* Tabla */
  #tbllistado thead th{ background:#eef3fb; color:#0b2752; }
  #tbllistado tfoot th{ background:#f8fafc; }
</style>

<!-- ====== Contenido ====== -->
<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">
        <div class="neko-card">

          <div class="neko-card__header">
            <h1 class="neko-card__title">
              <i class="fa fa-truck"></i> Proveedores
            </h1>
            <div class="neko-actions">
              <a href="../reportes/rptproveedores.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
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
          <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
            <form name="formulario" id="formulario" method="POST" autocomplete="off">
              <input type="hidden" name="idpersona" id="idpersona">
              <input type="hidden" name="tipo_persona" id="tipo_persona" value="Proveedor">

              <h4 class="section-title"><span class="dot"></span> Datos del documento</h4>

              <div class="row">
                <div class="form-group col-lg-3 col-md-4 col-sm-6 col-xs-12">
                  <label>Tipo Documento:</label>
                  <select class="form-control selectpicker" name="tipo_documento" id="tipo_documento" required>
                    <option value="RUC">RUC</option>
                    <!-- Si quisieras habilitar DNI más adelante:
                    <option value="DNI">DNI</option> -->
                  </select>
                </div>

                <div class="form-group col-lg-4 col-md-6 col-sm-6 col-xs-12">
                  <label>Número Documento:</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-id-card-o"></i></span>
                    <input type="text" class="form-control" name="num_documento" id="num_documento"
                           placeholder="Documento" required>
                  </div>
                  <div class="help-hint" id="ayuda_doc">RUC: 11 dígitos</div>
                </div>

                <div class="form-group col-lg-5 col-md-12 col-sm-12 col-xs-12">
                  <label>Estado de verificación:</label><br>
                  <span id="docStatus" class="chip info"><i class="fa fa-info-circle"></i> Esperando número…</span>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Datos básicos</h4>

              <div class="row">
                <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                  <label>Nombre (autocompletado):</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-building"></i></span>
                    <input type="text" class="form-control readonly" name="nombre" id="nombre"
                           placeholder="Nombre del proveedor" readonly required>
                  </div>
                </div>

                <div class="form-group col-lg-6 col-md-6 col-sm-12 col-xs-12">
                  <label>Dirección (autocompletada):</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-map-marker"></i></span>
                    <input type="text" class="form-control readonly" name="direccion" id="direccion"
                           placeholder="Dirección" readonly>
                  </div>
                </div>
              </div>

              <h4 class="section-title"><span class="dot"></span> Contacto</h4>

              <div class="row">
                <div class="form-group col-lg-3 col-md-4 col-sm-6 col-xs-12">
                  <label>Teléfono:</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-phone"></i></span>
                    <input type="text" class="form-control" name="telefono" id="telefono"
                           placeholder="Teléfono (9 dígitos)" maxlength="9" inputmode="numeric" pattern="\d{9}" required>
                  </div>
                  <div class="help-hint">Solo números, exactamente 9 dígitos.</div>
                </div>

                <div class="form-group col-lg-4 col-md-6 col-sm-6 col-xs-12">
                  <label>Email:</label>
                  <div class="input-group">
                    <span class="input-group-addon"><i class="fa fa-envelope-o"></i></span>
                    <input type="email" class="form-control" name="email" id="email" maxlength="50" placeholder="Email">
                  </div>
                </div>
              </div>

              <div class="row">
                <div class="form-group col-lg-12 col-md-12 col-sm-12 col-xs-12" style="margin-top:6px;">
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
<script type="text/javascript" src="scripts/proveedor.js"></script>
<?php ob_end_flush(); ?>
