<?php
// vistas/articulo.php  —  Estilo corporativo y formulario ordenado
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

$canAlmacen = !empty($_SESSION['almacen']) && (int)$_SESSION['almacen'] === 1;

// Paleta (igual a proveedores)
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

  .section-title{
    font-weight:600; color:#0b2752; margin:6px 0 16px; display:flex; align-items:center; gap:8px;
  }
  .section-title .dot{ width:8px; height:8px; border-radius:999px; background:var(--neko-primary); display:inline-block; }

  #tbllistado thead th{ background:#eef3fb; color:#0b2752; }
  #tbllistado tfoot th{ background:#f8fafc; }
  #tbllistado td:nth-child(8) img{
    width:48px !important; height:48px !important; object-fit:cover;
    border-radius:6px; border:1px solid #e5e7eb;
  }

  .help-hint{ color:#64748b; font-size:.85rem; margin-top:4px; }
  .readonly{ background:#f3f4f6 !important; color:#475569 !important; }
  .btn-primary{ background:var(--neko-primary); border-color:var(--neko-primary); }
  .btn-primary:hover{ background:var(--neko-primary-dark); border-color:var(--neko-primary-dark); }

  /* Separación vertical consistente entre grupos */
  .form-group{ margin-bottom:16px; }
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="neko-card">
          <!-- Topbar -->
          <div class="neko-card__header">
            <h1 class="neko-card__title"><i class="fa fa-cubes"></i> Artículos</h1>
            <div class="neko-actions">
              <a href="../reportes/rptarticulos.php" target="_blank" class="btn btn-light" style="background:#e3f2fd;border:0;color:#0d47a1;">
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
                <th>Categoría</th>
                <th>Código</th>
                <th>Stock</th>
                <th>Precio Compra</th>
                <th>Precio Venta</th>
                <th>Imagen</th>
                <th>Estado</th>
              </thead>
              <tbody></tbody>
              <tfoot>
                <th>Opciones</th>
                <th>Nombre</th>
                <th>Categoría</th>
                <th>Código</th>
                <th>Stock</th>
                <th>Precio Compra</th>
                <th>Precio Venta</th>
                <th>Imagen</th>
                <th>Estado</th>
              </tfoot>
            </table>
          </div>

          <!-- FORMULARIO ORDENADO -->
          <div class="neko-card__body panel-body" id="formularioregistros" style="display:none;">
            <form name="formulario" id="formulario" method="POST" enctype="multipart/form-data" autocomplete="off">
              <input type="hidden" name="idarticulo" id="idarticulo">

              <h4 class="section-title"><span class="dot"></span> Datos del artículo</h4>

              <!-- Fila 1: Nombre + Categoría -->
              <div class="row">
                <div class="form-group col-lg-8 col-md-8">
                  <label>Nombre(*):</label>
                  <input type="text" class="form-control" name="nombre" id="nombre"
                         maxlength="100" placeholder="Nombre del artículo" required
                         title="Solo letras y espacios (3 a 50 caracteres)">
                  <div class="help-hint">Usa un nombre claro y único.</div>
                </div>

                <div class="form-group col-lg-4 col-md-4">
                  <label>Categoría(*):</label>
                  <select id="idcategoria" name="idcategoria" class="form-control selectpicker"
                          data-live-search="true" required></select>
                </div>
              </div>

              <!-- Fila 2: Stock + Precio compra + Precio venta -->
              <div class="row">
                <div class="form-group col-lg-3 col-md-3 col-sm-6">
                  <label>Stock(*):</label>
                  <input type="number" class="form-control" name="stock" id="stock"
                         min="0" step="1" required>
                </div>

                <div class="form-group col-lg-3 col-md-3 col-sm-6">
                  <label>Precio compra(*):</label>
                  <input type="text" class="form-control" name="precio_compra" id="precio_compra"
                         placeholder="0.00" inputmode="decimal"
                         pattern="^\d{1,7}(\.\d{1,2})?$"
                         title="Solo números (máx. 2 decimales)" required>
                  <div class="help-hint">Base para sugerir el precio de venta.</div>
                </div>

                <div class="form-group col-lg-3 col-md-3 col-sm-6">
                  <label>Precio venta(*):</label>
                  <input type="text" class="form-control" name="precio_venta" id="precio_venta"
                         placeholder="0.00" inputmode="decimal"
                         pattern="^\d{1,7}(\.\d{1,2})?$"
                         title="Solo números (máx. 2 decimales)" required>
                  <div class="help-hint" id="pv_sugerido_hint">Sugerido: —</div>
                </div>
              </div>

              <!-- Fila 3: Descripción -->
              <div class="row">
                <div class="form-group col-lg-12">
                  <label>Descripción:</label>
                  <input type="text" class="form-control" name="descripcion" id="descripcion"
                         maxlength="256" placeholder="Detalle o especificación">
                </div>
              </div>

              <!-- Fila 4: Imagen (izq) + Código (der) -->
              <div class="row">
                <div class="form-group col-lg-6">
                  <label>Imagen:</label>
                  <input type="file" class="form-control" name="imagen" id="imagen"
                         accept="image/x-png,image/gif,image/jpeg,image/jpg,image/png">
                  <input type="hidden" name="imagenactual" id="imagenactual">
                  <div class="help-hint">JPG/PNG. Recomendado 600×480.</div>
                  <img src="" id="imagenmuestra" style="width:150px;height:120px;object-fit:cover;border:1px solid #e5e7eb;border-radius:6px;margin-top:8px;display:none;">
                </div>

                <div class="form-group col-lg-6">
                  <label>Código de barras:</label>
                  <div class="input-group">
                    <input type="text" class="form-control" name="codigo" id="codigo"
                           placeholder="EAN/UPC (8 a 13 dígitos)"
                           inputmode="numeric" pattern="^\d{8,13}$"
                           title="Solo números (8 a 13 dígitos)">
                    <span class="input-group-btn">
                      <button class="btn btn-success" type="button" onclick="generarbarcode()">
                        <i class="fa fa-barcode"></i> Generar
                      </button>
                      <button class="btn btn-info" type="button" onclick="imprimir()">
                        <i class="fa fa-print"></i> Imprimir
                      </button>
                    </span>
                  </div>
                  <div id="print" style="margin-top:8px; display:none;">
                    <svg id="barcode"></svg>
                  </div>
                </div>
              </div>

              <!-- Botones -->
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
<!-- Scripts -->
<script type="text/javascript" src="../public/js/JsBarcode.all.min.js"></script>
<script type="text/javascript" src="../public/js/jquery.PrintArea.js"></script>
<script type="text/javascript" src="scripts/articulo.js"></script>
<?php ob_end_flush(); ?>
