/* vistas/scripts/ingreso.js
 * Ingresos con toolbar propia (botones + length + search + fechas)
 */

var tabla;          // DataTable principal
var tablaArticulos; // DataTable del modal
var impuesto = 18;
var cont = 0;
var detalles = 0;

// ===============================
// Init
// ===============================
function init () {
  mostrarform(false);
  construirTabla();                 // crea DataTable una sola vez

  $("#formulario").on("submit", function (e) { guardaryeditar(e); });

  // Proveedores
  $.post("../ajax/ingreso.php?op=selectProveedor", function (r) {
    $("#idproveedor").html(r);
    $('#idproveedor').selectpicker('refresh');
  });

  // Menú activo
  $('#mCompras').addClass("treeview active");
  $('#lIngresos').addClass("active");

  // Impuesto por comprobante
  $("#tipo_comprobante").change(marcarImpuesto);

  autoprepararFecha();
}

// ===============================
// UI helpers
// ===============================
function autoprepararFecha () {
  var f = document.getElementById('fecha_hora');
  if (!f) return;
  if (!f.value) {
    var now = new Date();
    var day = ("0" + now.getDate()).slice(-2);
    var month = ("0" + (now.getMonth() + 1)).slice(-2);
    f.value = now.getFullYear() + "-" + month + "-" + day;
  }
}

// ===============================
// Listado (DataTable)
// ===============================
function construirTabla () {
  // Si existía, destruye y limpia (evitamos doble init)
  if ($.fn.DataTable.isDataTable('#tbllistado')) {
    $('#tbllistado').DataTable().destroy();
    $('#tbllistado tbody').empty();
  }

  tabla = $('#tbllistado').DataTable({
    aProcessing: true,
    aServerSide: true,
    iDisplayLength: 5,
    lengthMenu: [5, 10, 25, 50, 100],
    order: [[1, 'desc']],    // fecha desc
    dom: 'Brtip',            // sin l/f nativos
    buttons: [
      { extend: 'copyHtml5', text: 'Copy' },
      { extend: 'excelHtml5', text: 'Excel' },
      { extend: 'csvHtml5',  text: 'CSV' },
      { extend: 'pdfHtml5',  text: 'PDF' }
    ],
    ajax: {
      url: '../ajax/ingreso.php?op=listar',
      type: 'GET',
      dataType: 'json',
      data: function (d) {
        d.desde = $('#filtro_desde').val() || '';
        d.hasta = $('#filtro_hasta').val() || '';
      },
      error: function (e) { console.log(e.responseText); }
    },
    language: {
      lengthMenu: 'Mostrar : _MENU_ registros',
      paginate: { previous: 'Anterior', next: 'Siguiente' },
      info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
      infoEmpty: 'Mostrando 0 a 0 de 0 registros',
      zeroRecords: 'No se encontraron resultados',
      infoFiltered: '(filtrado de _MAX_ registros)',
      buttons: { copyTitle: 'Tabla Copiada', copySuccess: { _: '%d líneas copiadas', 1: '1 línea copiada' } }
    },
    drawCallback: function(){
      // Si algún global inyecta length/filter otra vez, los ocultamos
      $('#tbllistado_wrapper .dataTables_length, #tbllistado_wrapper .dataTables_filter').hide();
    }
  });

  // Anclar botones a la toolbar propia (solo una vez)
  var $holder = $('.dt-buttons-holder');
  if ($holder.length && !$holder.find('.dt-buttons').length) {
    $holder.append( tabla.buttons().container() );
  }

  // Controles propios
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

  // Fechas → reload sin reinit
  $('#filtro_desde, #filtro_hasta').off('change input').on('change input', function(){
    tabla.ajax.reload(null, false);
  });
  $('#btnFiltrar').off('click').on('click', function(){ tabla.ajax.reload(); });
  $('#btnLimpiarFiltro').off('click').on('click', function(){
    $('#filtro_desde').val(''); $('#filtro_hasta').val('');
    tabla.ajax.reload();
  });
}

// ===============================
// Form helpers
// ===============================
function limpiar () {
  $("#idingreso").val("");
  $("#idproveedor").val(""); $("#idproveedor").selectpicker('refresh');
  $("#tipo_comprobante").val("Boleta"); $("#tipo_comprobante").selectpicker('refresh');
  $("#serie_comprobante").val("");
  $("#num_comprobante").val("");
  $("#impuesto").val("0");
  $("#total_compra").val("");

  $(".filas").remove();
  $("#total").html("S/. 0.00");
  detalles = 0;

  autoprepararFecha();
}

function mostrarform (flag) {
  if (flag) {
    $("#listadoregistros").hide();
    $("#formularioregistros").show();
    $("#btnagregar").hide();

    listarArticulos();
    $("#btnGuardar").hide();
    $("#btnCancelar").show();
    $("#btnAgregarArt").show();
    detalles = 0;
  } else {
    $("#listadoregistros").show();
    $("#formularioregistros").hide();
    $("#btnagregar").show();
  }
}

function cancelarform () {
  limpiar();
  mostrarform(false);
}

// ===============================
// Modal Artículos
// ===============================
function listarArticulos () {
  tablaArticulos = $('#tblarticulos').dataTable({
    aProcessing: true,
    aServerSide: true,
    dom: 'Bfrtip',
    buttons: [],
    ajax: {
      url: '../ajax/ingreso.php?op=listarArticulos',
      type: "get",
      dataType: "json",
      error: function (e) { console.log(e.responseText); }
    },
    bDestroy: true,
    iDisplayLength: 5,
    order: [[1, "asc"]]
  }).DataTable();
}

// ===============================
// Guardar / Editar
// ===============================
function guardaryeditar (e) {
  e.preventDefault();
  var formData = new FormData($("#formulario")[0]);

  $.ajax({
    url: "../ajax/ingreso.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      bootbox.alert(datos);
      if (/registrado|actualizado/i.test(datos)) {
        mostrarform(false);
        tabla.ajax.reload(null, false);
      }
    }
  });

  limpiar();
}

// ===============================
// Mostrar / Anular
// ===============================
function mostrar (idingreso) {
  mostrarform(true);

  $.ajax({
    url: "../ajax/ingreso.php?op=mostrar",
    type: "POST",
    data: { idingreso: idingreso },
    dataType: "json",
    success: function (data) {
      if (typeof data === 'string') data = JSON.parse(data);

      $("#idproveedor").val(data.idproveedor).selectpicker('refresh');
      $("#tipo_comprobante").val(data.tipo_comprobante).selectpicker('refresh');
      $("#serie_comprobante").val(data.serie_comprobante || '');
      $("#num_comprobante").val(data.num_comprobante || '');
      $("#fecha_hora").val(data.fecha || '');
      $("#impuesto").val(data.impuesto || '0');
      $("#idingreso").val(data.idingreso || idingreso);

      $("#btnGuardar").hide();
      $("#btnCancelar").show();
      $("#btnAgregarArt").hide();
    },
    error: function (xhr) {
      const msg = xhr.responseText ? xhr.responseText.substr(0, 500) : 'Sin detalle';
      console.error('Error AJAX op=mostrar:', xhr.status, xhr.responseText);
      bootbox.alert("Error al cargar el ingreso (" + xhr.status + ").\n\n" + msg);
    }
  });

  $.ajax({
    url: "../ajax/ingreso.php?op=listarDetalle&id=" + encodeURIComponent(idingreso),
    type: "GET",
    success: function (html) { $("#detalles").html(html); },
    error: function (xhr) {
      console.error('Error AJAX op=listarDetalle:', xhr.status, xhr.responseText);
      $("#detalles").html(
        '<thead><th>Opciones</th><th>Artículo</th><th>Cantidad</th><th>Precio Compra</th><th>Precio Venta</th><th>Subtotal</th></thead>' +
        '<tbody><tr><td colspan="6">No se pudo cargar el detalle (' + xhr.status + ').</td></tr></tbody>' +
        '<tfoot><th>TOTAL</th><th></th><th></th><th></th><th></th><th><h4 id="total">S/. 0.00</h4></th></tfoot>'
      );
    }
  });
}

function anular (idingreso) {
  bootbox.confirm("¿Está Seguro de anular el ingreso?", function (result) {
    if (result) {
      $.post("../ajax/ingreso.php?op=anular", { idingreso: idingreso }, function (e) {
        bootbox.alert(e);
        tabla.ajax.reload(null, false);
      });
    }
  });
}

// ===============================
// Detalle (form)
// ===============================
$("#btnGuardar").hide();

function marcarImpuesto () {
  var tipo = $("#tipo_comprobante option:selected").text();
  if (tipo === 'Factura') { $("#impuesto").val(impuesto); }
  else { $("#impuesto").val("0"); }
}

// id, nombre, precio_compra, precio_venta
function agregarDetalle (idarticulo, articulo, pcompra, pventa) {
  var cantidad = 1;

  if (idarticulo !== "") {
    var subtotal = cantidad * pcompra;

    var fila  = '<tr class="filas" id="fila' + cont + '">';
        fila +=   '<td><button type="button" class="btn btn-danger" onclick="eliminarDetalle(' + cont + ')">X</button></td>';
        fila +=   '<td><input type="hidden" name="idarticulo[]" value="' + idarticulo + '">' + articulo + '</td>';
        fila +=   '<td><input type="number" name="cantidad[]" value="' + cantidad + '" min="1" oninput="modificarSubototales()"></td>';
        fila +=   '<td><input type="number" name="precio_compra[]" value="' + Number(pcompra).toFixed(2) + '" step="0.01" min="0" readonly></td>';
        fila +=   '<td><input type="number" name="precio_venta[]"  value="' + Number(pventa ).toFixed(2) + '" step="0.01" min="0" readonly></td>';
        fila +=   '<td><span name="subtotal" id="subtotal' + cont + '">' + subtotal.toFixed(2) + '</span></td>';
        fila +=   '<td><button type="button" onclick="modificarSubototales()" class="btn btn-info"><i class="fa fa-refresh"></i></button></td>';
        fila += '</tr>';

    cont++;
    detalles = detalles + 1;
    $('#detalles tbody').append(fila);
    modificarSubototales();
  } else {
    alert("Error al ingresar el detalle, revisar los datos del artículo");
  }
}

function modificarSubototales () {
  var cant = document.getElementsByName("cantidad[]");
  var prec = document.getElementsByName("precio_compra[]");
  var sub  = document.getElementsByName("subtotal");

  for (var i = 0; i < cant.length; i++) {
    var scalc = (parseFloat(cant[i].value || 0) * parseFloat(prec[i].value || 0)).toFixed(2);
    sub[i].value = scalc;
    document.getElementsByName("subtotal")[i].innerHTML = scalc;
  }
  calcularTotales();
}

function calcularTotales () {
  var sub = document.getElementsByName("subtotal");
  var total = 0.0;
  for (var i = 0; i < sub.length; i++) {
    total += parseFloat(sub[i].value || "0");
  }
  $("#total").html("S/. " + total.toFixed(2));
  $("#total_compra").val(total.toFixed(2));
  evaluar();
}

function evaluar () {
  if (detalles > 0) { $("#btnGuardar").show(); }
  else { $("#btnGuardar").hide(); cont = 0; }
}

function eliminarDetalle (indice) {
  $("#fila" + indice).remove();
  detalles = detalles - 1;
  calcularTotales();
  evaluar();
}

// Exponer para botones inline
window.agregarDetalle = agregarDetalle;
window.mostrar        = mostrar;
window.anular         = anular;

init();
