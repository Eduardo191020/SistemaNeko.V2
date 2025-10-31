/* vistas/scripts/ventasfechacliente.js */
var tabla = null;

$(init);

function init () {
  // Cargar clientes y luego construir la tabla
  $.post("../ajax/venta.php?op=selectCliente", function(r){
    $("#idcliente").html(r);
    try { $('#idcliente').selectpicker('refresh'); } catch(e){}
    construirTabla();
  });

  // Recargar al cambiar filtros (sin reinit)
  $('#fecha_inicio, #fecha_fin').on('change input', function () {
    if (tabla) tabla.ajax.reload(null, false);
  });
  $('#idcliente').on('changed.bs.select change', function () {
    if (tabla) tabla.ajax.reload(null, false);
  });
  $('#btnMostrar').on('click', function(){ if (tabla) tabla.ajax.reload(null, false); });

  // Menú activo
  $('#mConsultaV').addClass("treeview active");
  $('#lConsulasV').addClass("active");
}

function construirTabla () {
  if ($.fn.DataTable.isDataTable('#tbllistado')) {
    $('#tbllistado').DataTable().destroy();
    $('#tbllistado tbody').empty();
  }

  tabla = $('#tbllistado').DataTable({
    aProcessing: true,
    aServerSide: true,
    iDisplayLength: 5,
    lengthMenu: [5, 10, 25, 50, 100],
    order: [[0, 'desc']],

    // Sin 'l' ni 'f': usamos controles propios
    dom: 'Brtip',

    buttons: [
      { extend: 'copyHtml5', text: 'Copy' },
      { extend: 'excelHtml5', text: 'Excel' },
      { extend: 'csvHtml5',  text: 'CSV' },
      { extend: 'pdfHtml5',  text: 'PDF' }
    ],

    ajax: {
      url: '../ajax/consultas.php?op=ventasfechacliente',
      type: 'GET',
      dataType: 'json',
      data: function (d) {
        d.fecha_inicio = $('#fecha_inicio').val();
        d.fecha_fin    = $('#fecha_fin').val();
        d.idcliente    = $('#idcliente').val();
      },
      error: function (xhr) {
        console.log(xhr.responseText || xhr.statusText);
      }
    },

    language: {
      lengthMenu:  'Mostrar : _MENU_ registros',
      paginate:    { previous: 'Anterior', next: 'Siguiente' },
      info:        'Mostrando _START_ a _END_ de _TOTAL_ registros',
      infoEmpty:   'Mostrando 0 a 0 de 0 registros',
      zeroRecords: 'No se encontraron resultados',
      infoFiltered:'(filtrado de _MAX_ registros)',
      buttons: {
        copyTitle: 'Tabla Copiada',
        copySuccess: { _: '%d líneas copiadas', 1: '1 línea copiada' }
      }
    },

    drawCallback: function(){
      // Por si algún global reinyecta length/filter
      $('#tbllistado_wrapper .dataTables_length, #tbllistado_wrapper .dataTables_filter').hide();
    }
  });

  // Anclar contenedor de botones a la toolbar propia (una sola vez)
  var $holder = $('.dt-buttons-holder');
  if ($holder.length && !$holder.find('.dt-buttons').length) {
    $holder.append( tabla.buttons().container() );
  }

  // Controles propios: length + search
  $('#customLength')
    .val(tabla.page.len())
    .off('change')
    .on('change', function () {
      tabla.page.len(parseInt(this.value, 10) || 5).draw();
    });

  $('#customSearch')
    .off('keyup change input')
    .on('keyup change input', function () {
      tabla.search(this.value).draw();
    });
}
