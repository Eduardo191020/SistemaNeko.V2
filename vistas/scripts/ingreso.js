/* vistas/scripts/ingreso.js
 * Listado INGRESOS + Filtro por fechas + OJITO (child-row con detalle)
 */

var tabla;            // DataTable del listado principal
var tablaArticulos;   // DataTable del modal de artículos

// ===============================
// Inicialización
// ===============================
function init () {
  mostrarform(false);
  listar();                 // listado principal

  // Guardar
  $("#formulario").on("submit", function (e) { guardaryeditar(e); });

  // Cargar proveedores
  $.post("../ajax/ingreso.php?op=selectProveedor", function (r) {
    $("#idproveedor").html(r);
    $('#idproveedor').selectpicker('refresh');
  });

  // Menú activo
  $('#mCompras').addClass("treeview active");
  $('#lIngresos').addClass("active");

  // Impuesto según tipo de comprobante
  $("#tipo_comprobante").change(marcarImpuesto);

  // Fecha hoy por defecto (si no llega desde PHP)
  autoprepararFecha();
}

// ===============================
// Utilidades UI
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
// Limpieza y formularios
// ===============================
function limpiar () {
  $("#idingreso").val("");
  $("#idproveedor").val("");
  $("#idproveedor").selectpicker('refresh');
  $("#tipo_comprobante").val("Boleta");
  $("#tipo_comprobante").selectpicker('refresh');
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

    listarArticulos(); // cargar modal artículos
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
// Listados
// ===============================
function listar() {
  tabla = $('#tbllistado').DataTable({
    lengthMenu: [5, 10, 25, 75, 100],
    aProcessing: true,
    aServerSide: true,
    dom: '<"row"<"col-sm-6"B><"col-sm-6"f>>rtip',
    buttons: ['copyHtml5','excelHtml5','csvHtml5','pdf'],
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
      buttons: { copyTitle: 'Tabla Copiada', copySuccess: { _: '%d líneas copiadas', 1: '1 línea copiada' } },
      search: 'Buscar:'
    },
    bDestroy: true,
    iDisplayLength: 5,
    order: [[1, 'desc']],
    initComplete: function () {
      // Mueve los filtros de fecha al lado del buscador
      var $filter = $('#tbllistado_wrapper .dataTables_filter');
      if ($('#dateFilters').length) { $('#dateFilters').appendTo($filter).show(); }

      // Eventos de filtro
      $('#filtro_desde, #filtro_hasta').off('change keyup').on('change keyup', function () {
        tabla.ajax.reload(null, false);
      });
      $('#btnFiltrar').off('click').on('click', function () { tabla.ajax.reload(); });
      $('#btnLimpiarFiltro').off('click').on('click', function () {
        $('#filtro_desde').val(''); $('#filtro_hasta').val(''); tabla.ajax.reload();
      });
    }
  });

  // Ajuste de page length por si el usuario lo cambia
  $('#tbllistado_length select').off('change').on('change', function () {
    tabla.page.len(parseInt(this.value, 10)).draw();
  });
}

// ===============================
// Guardado / edición
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
      if (/registrado/i.test(datos) || /Registrado/i.test(datos)) {
        mostrarform(false);
        listar();
      }
    }
  });

  limpiar();
}

// ===============================
// Mostrar / Anular
// ===============================
function mostrar (idingreso) {
  // Abre el formulario de lectura
  mostrarform(true);

  // 1) Cabecera
  $.ajax({
    url: "../ajax/ingreso.php?op=mostrar",
    type: "POST",
    data: { idingreso: idingreso },
    dataType: "json",
    success: function (data) {
      try {
        if (typeof data === 'string') data = JSON.parse(data);
        if (data && data.error) { bootbox.alert("Error: " + data.error); return; }

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
      } catch (err) {
        console.error('Fallo parseando JSON de op=mostrar:', err, data);
        bootbox.alert("No se pudo interpretar la respuesta del servidor (mostrar).");
      }
    },
    error: function (xhr) {
      const msg = xhr.responseText ? xhr.responseText.substr(0, 500) : 'Sin detalle';
      console.error('Error AJAX op=mostrar:', xhr.status, xhr.responseText);
      bootbox.alert("Error al cargar el ingreso (" + xhr.status + ").\n\n" + msg);
    }
  });

  // 2) Detalle (HTML)
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
        tabla.ajax.reload();
      });
    }
  });
}

// ===============================
// Detalle de compra (formulario)
// ===============================
var impuesto = 18; // porcentaje
var cont = 0;
var detalles = 0;

$("#btnGuardar").hide();

function marcarImpuesto () {
  var tipo = $("#tipo_comprobante option:selected").text();
  if (tipo === 'Factura') { $("#impuesto").val(impuesto); }
  else { $("#impuesto").val("0"); }
}

// Recibe: id, nombre, precio_compra, precio_venta
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
  else {
    $("#btnGuardar").hide();
    cont = 0;
  }
}

function eliminarDetalle (indice) {
  $("#fila" + indice).remove();
  detalles = detalles - 1;
  calcularTotales();
  evaluar();
}

// Exponer funciones a botones inline
window.agregarDetalle = agregarDetalle;
window.mostrar        = mostrar;
window.anular         = anular;

// Boot
init();
