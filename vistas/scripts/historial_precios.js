var tabla;

function init(){
  // Cargar combo de artículos
  $.post("../ajax/articulo.php?op=selectActivos", function(r){
    $("#filtro_articulo").html(r);
    $("#filtro_articulo").selectpicker('refresh');
  });

  // Construir DataTable (sin filtro => toda la historia)
  construirTabla('');
  
  // Eventos
  $("#filtro_articulo").on('changed.bs.select', function(){
    let idart = $(this).val() || '';
    tabla.ajax.url('../ajax/historial_precios.php?op=listar&idarticulo=' + encodeURIComponent(idart)).load();
  });

  $("#btnRecargarHP").on('click', function(){
    let idart = $("#filtro_articulo").val() || '';
    tabla.ajax.url('../ajax/historial_precios.php?op=listar&idarticulo=' + encodeURIComponent(idart)).load();
  });
}

function construirTabla(idart){
  tabla = $('#tbl_historial').DataTable({
    "aProcessing": true,
    "aServerSide": true,
    dom: 'Bfrtip',
    buttons: ['copyHtml5','excelHtml5','csvHtml5','pdf'],
    "ajax": {
      url: '../ajax/historial_precios.php?op=listar&idarticulo='+encodeURIComponent(idart||''),
      type: "get",
      dataType: "json",
      error: function (e) {
        console.error(e.responseText);
      }
    },
    "iDisplayLength": 10,
    "order": [[8, "desc"]], // por fecha desc
    "language": {
      "url": "../public/datatables/i18n/Spanish.json"
    }
  });
}

/* --- Modal actualizar precio --- */
$("#btnAbrirModalActualizar").on('click', function(){
  // limpia el form
  $("#frmActualizarPrecio")[0].reset();
  $("#articulo_modal").val($("#filtro_articulo").val() || '').trigger('change');
  $("#modalActualizarPrecio").modal('show');
});

// Carga combo del modal (opcional, puedes clonar el del filtro)
$.post("../ajax/articulo.php?op=selectActivos", function(r){
  $("#articulo_modal").html(r);
  $("#articulo_modal").selectpicker('refresh');
});

// Al cambiar artículo en el modal, trae precio vigente
$("#articulo_modal").on('changed.bs.select', function(){
  const idart = $(this).val();
  if(!idart){ $("#precio_actual").val(''); return; }
  $.getJSON('../ajax/historial_precios.php?op=ultimo&idarticulo='+encodeURIComponent(idart), function(res){
    if(res && res.success){
      $("#precio_actual").val( (res.precio_venta || 0).toFixed(2) );
    }else{
      $("#precio_actual").val('');
    }
  });
});

// Enviar actualización
$("#frmActualizarPrecio").on('submit', function(e){
  e.preventDefault();
  const fd = new FormData(this);
  $.ajax({
    url: '../ajax/historial_precios.php?op=actualizar_precio',
    type: 'POST',
    data: fd,
    processData: false,
    contentType: false,
    success: function(r){
      try{
        const json = JSON.parse(r);
        if(json.success){
          bootbox.alert(json.message || 'Actualizado', function(){
            $("#modalActualizarPrecio").modal('hide');
            let idart = $("#filtro_articulo").val() || '';
            tabla.ajax.url('../ajax/historial_precios.php?op=listar&idarticulo='+encodeURIComponent(idart)).load();
          });
        }else{
          bootbox.alert(json.message || 'No se pudo actualizar');
        }
      }catch(_){ console.log(r); }
    }
  });
});

$(init);
