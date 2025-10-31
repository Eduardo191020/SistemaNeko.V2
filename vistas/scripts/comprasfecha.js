/* vistas/scripts/comprasfecha.js */
var tabla = null;

function init () {
  // Inicializa una sola vez
  construirTabla();

  // Al cambiar fechas, recarga (sin reinit)
  $('#fecha_inicio, #fecha_fin').on('change input', function () {
    if (tabla) tabla.ajax.reload(null, false);
  });

  // Marcar menú activo (dejé tus ids)
  $('#mConsultaC').addClass('treeview active');
  $('#lConsulasC').addClass('active');
}

function construirTabla () {
  if ($.fn.DataTable.isDataTable('#tbllistado')) {
    // por si vienes de otra versión
    $('#tbllistado').DataTable().destroy();
    $('#tbllistado tbody').empty();
  }

  tabla = $('#tbllistado').DataTable({
    aProcessing: true,
    aServerSide: true,
    iDisplayLength: 5,
    lengthMenu: [5, 10, 25, 75, 100],
    order: [[0, 'desc']],

    // Sin 'l' ni 'f' para que no aparezcan Mostrar/Buscar nativos
    dom: 'Brtip',

    buttons: [
      { extend: 'copyHtml5', text: 'Copy' },
      { extend: 'excelHtml5', text: 'Excel' },
      { extend: 'csvHtml5',  text: 'CSV' },
      { extend: 'pdfHtml5',  text: 'PDF' }
    ],

    // Enviamos SIEMPRE las fechas actuales
    ajax: {
      url: '../ajax/consultas.php?op=comprasfecha',
      type: 'GET',
      dataType: 'json',
      data: function (d) {
        d.fecha_inicio = $('#fecha_inicio').val();
        d.fecha_fin    = $('#fecha_fin').val();
      },
      error: function (xhr) { console.log(xhr.responseText || xhr.statusText); }
    },

    language: {
      lengthMenu: 'Mostrar : _MENU_ registros',
      paginate:   { previous: 'Anterior', next: 'Siguiente' },
      info:       'Mostrando _START_ a _END_ de _TOTAL_ registros',
      infoEmpty:  'Mostrando 0 a 0 de 0 registros',
      zeroRecords:'No se encontraron resultados',
      infoFiltered:'(filtrado de _MAX_ registros)',
      buttons: {
        copyTitle: 'Tabla Copiada',
        copySuccess: { _: '%d líneas copiadas', 1: '1 línea copiada' }
      }
    },

    // Oculta length/filter si algún script global los inyecta
    drawCallback: function () {
      $('#tbllistado_wrapper .dataTables_length, #tbllistado_wrapper .dataTables_filter').hide();
    }
  });

  // Anclar contenedor de botones a un holder fijo si lo tienes en el HTML;
  // si no existe, se queda donde DataTables lo dibuja (pero ya no "salta"
  // porque no re-inicializamos la tabla).
  var $holder = $('.dt-buttons-holder');
  if ($holder.length && !$holder.find('.dt-buttons').length) {
    $holder.append( tabla.buttons().container() );
  }

  // Estabilizar layout de botones
  estabilizarBotones();
}

function estabilizarBotones () {
  // Asegura estilo plano y sin saltos
  var $btns = $('#tbllistado_wrapper .dt-buttons');
  $btns.css({ display: 'flex', gap: '8px', float: 'none' });
  $btns.find('.dt-button').css({ margin: 0 });
}

// ===== util =====
$(init);
