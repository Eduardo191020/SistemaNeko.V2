<?php
// vistas/historial_precios.php — Estilo corporativo tipo categoría/artículo
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
require_once __DIR__ . '/_requires_auth.php';
require 'header.php';

/**
 * Permisos: dejamos visible si tiene Almacén o Compras.
 * Si quieres restringirlo a uno solo, cambia la expresión.
 */
$canVerHistorial = ( !empty($_SESSION['almacen']) && (int)$_SESSION['almacen']===1 )
                 || ( !empty($_SESSION['compras']) && (int)$_SESSION['compras']===1 );

// Paleta corporativa (igual que en categoría)
$nekoPrimary     = '#1565c0';
$nekoPrimaryDark = '#0d47a1';
?>
<?php if ($canVerHistorial): ?>
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

  .btn-primary{ background:var(--neko-primary); border-color:var(--neko-primary); }
  .btn-primary:hover{ background:var(--neko-primary-dark); border-color:var(--neko-primary-dark); }

  .form-group{ margin-bottom:14px; }
  .w-220{ width: 220px; }
  .text-muted-small{ color:#64748b; font-size:.85rem; }

  /* Tablas */
  #tbl_vigentes thead th,
  #tbl_mov thead th{ background:#eef3fb; color:#0b2752; }
  #tbl_vigentes tfoot th,
  #tbl_mov tfoot th{ background:#f8fafc; }
</style>

<div class="content-wrapper">
  <section class="content">
    <div class="row">
      <div class="col-md-12">

        <div class="neko-card">
          <!-- Header -->
          <div class="neko-card__header">
            <h1 class="neko-card__title">
              <i class="fa fa-tags"></i> Historial de Precios
            </h1>
            <div class="neko-actions">
              <button class="btn btn-light" id="btnRecargar" style="background:#e3f2fd;border:0;color:#0d47a1;">
                <i class="fa fa-refresh"></i> Recargar
              </button>
              <button class="btn btn-success" id="btnAbrirModal">
                <i class="fa fa-money"></i> Actualizar precio
              </button>
            </div>
          </div>

          <!-- Filtros -->
          <div class="neko-card__body" style="padding-bottom:8px;">
            <div class="row" style="margin-bottom:12px;">
              <div class="col-md-6">
                <label>Filtrar por artículo:</label>
                <select id="filtro_articulo" class="form-control selectpicker" data-live-search="true" title="Seleccione artículo" data-size="8"></select>
                <div class="text-muted-small">Puedes dejarlo vacío para ver todo.</div>
              </div>
            </div>
          </div>

          <!-- Pestañas -->
          <ul class="nav nav-tabs" role="tablist" style="margin: 0 18px;">
            <li role="presentation" class="active">
              <a href="#vigentes" aria-controls="vigentes" role="tab" data-toggle="tab">
                <i class="fa fa-check-circle"></i> Precios vigentes
              </a>
            </li>
            <li role="presentation">
              <a href="#movimientos" aria-controls="movimientos" role="tab" data-toggle="tab">
                <i class="fa fa-history"></i> Movimientos
              </a>
            </li>
          </ul>

          <div class="tab-content neko-card__body">
            <!-- Vigentes -->
            <div role="tabpanel" class="tab-pane active" id="vigentes">
              <div class="panel-body table-responsive" id="listado_vigentes">
                <table id="tbl_vigentes" class="table table-striped table-bordered table-condensed table-hover" style="width:100%">
                  <thead>
                    <th>ID</th>
                    <th>Artículo</th>
                    <th>Precio venta</th>
                    <th>Precio compra</th>
                    <th>Stock</th>
                  </thead>
                  <tbody></tbody>
                  <tfoot>
                    <th>ID</th>
                    <th>Artículo</th>
                    <th>Precio venta</th>
                    <th>Precio compra</th>
                    <th>Stock</th>
                  </tfoot>
                </table>
              </div>
            </div>

            <!-- Movimientos -->
            <div role="tabpanel" class="tab-pane" id="movimientos">
              <div class="panel-body table-responsive" id="listado_movimientos">
                <table id="tbl_mov" class="table table-striped table-bordered table-condensed table-hover" style="width:100%">
                  <thead>
                    <th>#</th>
                    <th>Artículo</th>
                    <th>Código</th>
                    <th>Precio anterior</th>
                    <th>Precio nuevo</th>
                    <th>Motivo</th>
                    <th>Fuente</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                  </thead>
                  <tbody></tbody>
                  <tfoot>
                    <th>#</th>
                    <th>Artículo</th>
                    <th>Código</th>
                    <th>Precio anterior</th>
                    <th>Precio nuevo</th>
                    <th>Motivo</th>
                    <th>Fuente</th>
                    <th>Usuario</th>
                    <th>Fecha</th>
                  </tfoot>
                </table>
              </div>
            </div>
          </div>
          <!-- /Body -->
        </div>

      </div>
    </div>
  </section>
</div>

<!-- Modal: Actualizar Precio -->
<div class="modal fade" id="mdlPrecio" tabindex="-1" role="dialog" aria-labelledby="lblMdlPrecio">
  <div class="modal-dialog" role="document">
    <form id="frmPrecio" method="post" autocomplete="off">
      <div class="modal-content">
        <div class="modal-header" style="background:var(--neko-primary);color:#fff;">
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
          <h4 class="modal-title" id="lblMdlPrecio">
            <i class="fa fa-money"></i> Actualizar precio de artículo
          </h4>
        </div>

        <div class="modal-body">
          <input type="hidden" id="idarticulo_mdl" name="idarticulo">

          <div class="form-group">
            <label>Artículo:</label>
            <select id="sel_articulo_mdl" class="form-control selectpicker" data-live-search="true" title="Seleccione artículo" data-size="8" required></select>
          </div>

          <div class="row">
            <div class="form-group col-sm-6">
              <label>Precio actual:</label>
              <input type="text" class="form-control" id="precio_actual" name="precio_actual" readonly>
            </div>
            <div class="form-group col-sm-6">
              <label>Precio nuevo (*):</label>
              <input type="number" class="form-control" step="0.01" min="0" id="precio_nuevo" name="precio_nuevo" required>
            </div>
          </div>

          <div class="form-group">
            <label>Motivo / comentario:</label>
            <input type="text" class="form-control" id="motivo" name="motivo" maxlength="120" placeholder="Ej. Ajuste por proveedor, corrección, etc.">
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-default" data-dismiss="modal">
            <i class="fa fa-times"></i> Cancelar
          </button>
          <button type="submit" class="btn btn-primary">
            <i class="fa fa-save"></i> Actualizar precio
          </button>
        </div>
      </div>
    </form>
  </div>
</div>

<?php else: ?>
  <?php require 'noacceso.php'; ?>
<?php endif; ?>

<?php require 'footer.php'; ?>

<!-- Inline mínimo funcional -->
<script>
(function(){
  // Marcar menú activo si añadiste el item simple (sin treeview)
  document.addEventListener('DOMContentLoaded', function(){
    var li = document.querySelector('aside .sidebar-menu li > a[href="historial_precios.php"]');
    if (li && li.parentElement){ li.parentElement.classList.add('active'); }
  });

  // ---- Helpers
  function cargarArticulosEnSelect($sel){
    // Usa tu endpoint real para artículos activos
    $.post("../ajax/articulo.php?op=selectActivos", function(r){
      $sel.html(r);
      try{ $sel.selectpicker('refresh'); }catch(_){}
    });
  }

  // ---- DataTables
  var tblV = $('#tbl_vigentes').DataTable({
    aProcessing:true, aServerSide:true,
    dom:'Bfrtip', buttons:['copyHtml5','excelHtml5','csvHtml5','pdf'],
    ajax:{
      url:'../ajax/historial_precios.php?op=listar_vigentes',
      type:'get',
      data:function(d){
        d.idarticulo = $('#filtro_articulo').val() || 0;
      },
      dataType:'json',
      error:function(e){ console.error(e.responseText); }
    },
    iDisplayLength:10,
    order:[[1,'asc']],
    columns:[
      {"data":0},{"data":1},{"data":2},{"data":3},{"data":4}
    ]
  });

  var tblM = $('#tbl_mov').DataTable({
    aProcessing:true, aServerSide:true,
    dom:'Bfrtip', buttons:['copyHtml5','excelHtml5','csvHtml5','pdf'],
    ajax:{
      url:'../ajax/historial_precios.php?op=listar_movimientos',
      type:'get',
      data:function(d){
        d.idarticulo = $('#filtro_articulo').val() || 0;
      },
      dataType:'json',
      error:function(e){ console.error(e.responseText); }
    },
    iDisplayLength:10,
    order:[[8,'desc']],
    columns:[
      {"data":0},{"data":1},{"data":2},{"data":3},{"data":4},{"data":5},{"data":6},{"data":7},{"data":8}
    ]
  });

  // ---- Filtro
  cargarArticulosEnSelect($('#filtro_articulo'));
  $('#filtro_articulo').on('changed.bs.select', function(){
    tblV.ajax.reload(null,false);
    tblM.ajax.reload(null,false);
  });

  // ---- Recargar
  $('#btnRecargar').on('click', function(){
    tblV.ajax.reload(null,false);
    tblM.ajax.reload(null,false);
  });

  // ---- Modal de actualización
  $('#btnAbrirModal').on('click', function(){
    cargarArticulosEnSelect($('#sel_articulo_mdl'));
    $('#precio_actual').val('');
    $('#precio_nuevo').val('');
    $('#motivo').val('');
    $('#idarticulo_mdl').val('');
    $('#mdlPrecio').modal('show');
  });

  // Al elegir artículo en el modal, obtener precio vigente
  $('#sel_articulo_mdl').on('changed.bs.select', function(){
    var idart = $(this).val();
    if(!idart){ $('#precio_actual').val(''); $('#idarticulo_mdl').val(''); return; }
    $.getJSON('../ajax/historial_precios.php?op=ultimo&idarticulo='+encodeURIComponent(idart))
      .done(function(resp){
        if(resp && resp.success){
          var pv = (resp.precio_venta ?? 0);
          $('#precio_actual').val( (pv.toFixed ? pv.toFixed(2) : pv) );
          $('#idarticulo_mdl').val(idart);
        }else{
          $('#precio_actual').val('');
          $('#idarticulo_mdl').val('');
        }
      }).fail(function(){
        $('#precio_actual').val(''); $('#idarticulo_mdl').val('');
      });
  });

  // Envío del formulario de actualización
  $('#frmPrecio').on('submit', function(e){
    e.preventDefault();
    var formData = new FormData(this);
    $.ajax({
      url: '../ajax/historial_precios.php?op=actualizar_precio',
      type: 'POST',
      data: formData,
      contentType:false, processData:false
    })
    .done(function(r){
      try{
        var j = JSON.parse(r);
        if(j.success){
          bootbox.alert(j.message || 'Precio actualizado', function(){
            $('#mdlPrecio').modal('hide');
            tblV.ajax.reload(null,false);
            tblM.ajax.reload(null,false);
          });
        }else{
          bootbox.alert(j.message || 'No se pudo actualizar el precio');
        }
      }catch(_){
        console.error('Respuesta inesperada:', r);
        bootbox.alert('Respuesta inesperada del servidor');
      }
    })
    .fail(function(){ bootbox.alert('Error de comunicación'); });
  });

})();
</script>

<!-- Si ya tienes un JS dedicado, lo puedes mantener (no es obligatorio) -->
<!-- <script type="text/javascript" src="scripts/historial_precios.js"></script> -->
<?php ob_end_flush(); ?>
