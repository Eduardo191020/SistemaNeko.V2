// vistas/scripts/articulo.js
var tabla;

/* ======================= Helpers de validación ======================= */

// Nombre: solo letras (con acentos) y espacios, 3..50, sin repeticiones absurdas.
function esNombreValido(nombre) {
  const txt = (nombre || "").trim();
  if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]{3,50}$/.test(txt)) return false;
  if (/([A-Za-z])\1{2,}/.test(txt)) return false;               // repeticiones tipo aaaaa
  if (!/[AEIOUaeiouÁÉÍÓÚáéíóúüÜ]/.test(txt)) return false;       // sin vocales
  const invalidos = ["xxx", "asdf", "test", "prueba", "rol", "role", "wewqeq", "qwe"];
  if (invalidos.some(p => txt.toLowerCase().includes(p))) return false;
  return true;
}

// Precio: ^\d{1,7}(\.\d{1,2})?$
function esPrecioValido(v) {
  return /^\d{1,7}(\.\d{1,2})?$/.test((v || "").trim());
}

// Código de barras: solo dígitos y largo 8..13
function esCodigoValido(v) {
  const txt = (v || "").trim();
  return /^\d{8,13}$/.test(txt);
}

// Aviso sutil en el input (un solo mensaje, nada invasivo)
function setValidity(el, ok, msg) {
  if (!el) return;
  el.setCustomValidity(ok ? "" : msg);
  if (!ok) el.reportValidity();
}

/* ======================= Generación EAN ======================= */

// Dígito de control EAN-13 (recibe 12 dígitos, devuelve 0..9)
function ean13Checksum(d12) {
  const s = String(d12).replace(/\D/g, "").padStart(12, "0").slice(0, 12);
  let sumOdd = 0, sumEven = 0;
  for (let i = 0; i < 12; i++) {
    const n = s.charCodeAt(i) - 48;
    if ((i + 1) % 2 === 0) sumEven += n; // posiciones pares (2,4,...,12)
    else sumOdd += n;                     // posiciones impares (1,3,...,11)
  }
  const total = sumOdd + sumEven * 3;
  return (10 - (total % 10)) % 10;
}

// Genera un EAN-13 válido (13 dígitos)
function generarEAN13() {
  let base = "";
  base += "77"; // prefijo arbitrario (puedes cambiarlo)
  for (let i = base.length; i < 12; i++) base += Math.floor(Math.random() * 10);
  const check = ean13Checksum(base);
  return base + String(check);
}

/* ======================= Sugerencia Precio Venta ======================= */
const IGV = 0.18;                 // Perú
const MARGEN_SUGERIDO = 0.30;     // 30% margen
let precioVentaEditadoManualmente = false;

function f2(n){ return (Math.round(parseFloat(n||0)*100)/100).toFixed(2); }

// Calcula sugerido = compra * (1+IGV) * (1+MARGEN)
function calcularPV(compra){
  const c = parseFloat(String(compra).replace(',', '.'));
  if (isNaN(c) || c <= 0) return "";
  const sugerido = c * (1 + IGV) * (1 + MARGEN_SUGERIDO);
  return f2(sugerido);
}

function actualizarSugerido(){
  const pc = $("#precio_compra").val();
  const sug = calcularPV(pc);
  const hint = document.getElementById('pv_sugerido_hint');
  if (hint) hint.textContent = sug ? `Sugerido: S/ ${sug} (IGV ${IGV*100}%, margen ${MARGEN_SUGERIDO*100}%)` : "Sugerido: —";
  if (sug && !precioVentaEditadoManualmente) {
    $("#precio_venta").val(sug);
  }
}

/* =========================== Inicialización =========================== */
function init() {
  mostrarform(false);
  listar();

  // submit del formulario
  $("#formulario").on("submit", function (e) { guardaryeditar(e); });

  // cargar categorías
  $.post("../ajax/articulo.php?op=selectCategoria", function (r) {
    $("#idcategoria").html(r);
    $("#idcategoria").selectpicker('refresh');
  });

  // UI menor
  $("#imagenmuestra").hide();
  $("#mAlmacen").addClass("treeview active");
  $("#lArticulos").addClass("active");

  // Validaciones en vivo
  $("#precio_compra").on("input", function(){
    const ok = esPrecioValido(this.value);
    setValidity(this, ok, "Precio inválido. Use solo números y hasta 2 decimales.");
    actualizarSugerido();
  });

  $("#precio_venta").on("input", function(){
    precioVentaEditadoManualmente = true;
    const ok = esPrecioValido(this.value);
    setValidity(this, ok, "Precio inválido. Use solo números y hasta 2 decimales.");
  });

  $("#precio_venta").on("blur", function(){
    // normaliza a 2 decimales si es válido
    if (esPrecioValido(this.value)) this.value = f2(this.value);
  });
  $("#precio_compra").on("blur", function(){
    if (esPrecioValido(this.value)) this.value = f2(this.value);
  });

  $("#stock").on("input", function () {
    this.value = this.value.replace(/[^\d]/g, "").slice(0, 5);
    if (this.value === "" || parseInt(this.value) < 0) {
      this.setCustomValidity("El stock debe ser un número mayor o igual a 0.");
    } else {
      this.setCustomValidity("");
    }
  });

  // Código: fuerza dígitos, máx 13; render si es válido
  $("#codigo").on("input", function () {
    this.value = this.value.replace(/\D+/g, "").slice(0, 13);
    const ok = esCodigoValido(this.value);
    setValidity(this, ok, ok ? "" : "Solo números (8 a 13 dígitos).");
    if (ok) renderBarcode(this.value);
  });

  // ================= Delegación de eventos (botones en la tabla) =================
  // Editar (lápiz)
  $(document).on('click', '#tbllistado .btn-edit', function () {
    const id = $(this).data('id');
    if (!id) return;
    mostrar(id);
  });

  // Desactivar
  $(document).on('click', '#tbllistado .btn-off', function () {
    const id = $(this).data('id');
    bootbox.confirm("¿Está seguro de desactivar el artículo?", function (ok) {
      if (!ok) return;
      $.post("../ajax/articulo.php?op=desactivar", { idarticulo: id }, function (e) {
        bootbox.alert(e);
        tabla.ajax.reload(null, false);
      });
    });
  });

  // Activar
  $(document).on('click', '#tbllistado .btn-on', function () {
    const id = $(this).data('id');
    bootbox.confirm("¿Está seguro de activar el artículo?", function (ok) {
      if (!ok) return;
      $.post("../ajax/articulo.php?op=activar", { idarticulo: id }, function (e) {
        bootbox.alert(e);
        tabla.ajax.reload(null, false);
      });
    });
  });
}

/* ============================== Vistas =============================== */
function limpiar() {
  $("#codigo").val("");
  $("#nombre").val("");
  $("#descripcion").val("");
  $("#stock").val("");
  $("#precio_compra").val("");
  $("#precio_venta").val("");
  $("#imagenmuestra").attr("src", "").hide();
  $("#imagenactual").val("");
  $("#print").hide();
  $("#idarticulo").val("");

  precioVentaEditadoManualmente = false;
  const hint = document.getElementById('pv_sugerido_hint');
  if (hint) hint.textContent = "Sugerido: —";

  ["#precio_compra", "#precio_venta", "#codigo"].forEach(sel => {
    const el = document.querySelector(sel);
    if (el) el.setCustomValidity("");
  });
}

function mostrarform(flag) {
  limpiar();
  if (flag) {
    $("#listadoregistros").hide();
    $("#formularioregistros").show();
    $("#btnGuardar").prop("disabled", false);
    $("#btnagregar").hide();
  } else {
    $("#listadoregistros").show();
    $("#formularioregistros").hide();
    $("#btnagregar").show();
  }
}

function cancelarform() {
  limpiar();
  mostrarform(false);
}

function listar() {
  if ($.fn.DataTable.isDataTable('#tbllistado')) {
    $('#tbllistado').DataTable().destroy();
    $('#tbllistado tbody').empty();
  }

  tabla = $('#tbllistado').DataTable({
    processing: true,
    serverSide: false,     // tolerante con backend clásico
    deferRender: true,
    responsive: true,
    autoWidth: false,
    dom: 'Bfrtip',
    buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdf'],

    ajax: {
      url: '../ajax/articulo.php?op=listar',
      type: 'GET',
      dataType: 'json',
      dataSrc: function (json) {
        // Acepta 'data' o 'aaData'
        if (json && Array.isArray(json.data))   return json.data;
        if (json && Array.isArray(json.aaData)) return json.aaData;
        return [];
      }
    },

    // Mapea las 9 columnas enviadas por el backend
    columns: [
      { data: 0, orderable:false, searchable:false }, // botones (HTML con .btn-edit / .btn-on / .btn-off)
      { data: 1 }, // Nombre
      { data: 2 }, // Categoría
      { data: 3 }, // Código
      { data: 4 }, // Stock
      { data: 5 }, // Precio Compra
      { data: 6 }, // Precio Venta
      { data: 7, orderable:false, searchable:false }, // Imagen (HTML)
      { data: 8, orderable:false, searchable:false }  // Estado (HTML)
    ],

    pageLength: 5,
    order: [[1, "asc"]]
  });
}

/* ========================= Guardar / Editar ========================== */
function guardaryeditar(e) {
  e.preventDefault();
  $("#btnGuardar").prop("disabled", true);

  const nombre = $("#nombre").val();
  if (!esNombreValido(nombre)) {
    bootbox.alert("⚠️ Ingrese un nombre válido (solo letras y sin repeticiones).");
    $("#btnGuardar").prop("disabled", false);
    return;
  }

  const precioCompraEl = document.querySelector("#precio_compra");
  if (!esPrecioValido(precioCompraEl.value)) {
    setValidity(precioCompraEl, false, "Precio inválido. Use solo números y hasta 2 decimales.");
    $("#btnGuardar").prop("disabled", false);
    return;
  } else {
    setValidity(precioCompraEl, true, "");
  }

  const precioVentaEl = document.querySelector("#precio_venta");
  if (!esPrecioValido(precioVentaEl.value)) {
    setValidity(precioVentaEl, false, "Precio inválido. Use solo números y hasta 2 decimales.");
    $("#btnGuardar").prop("disabled", false);
    return;
  } else {
    setValidity(precioVentaEl, true, "");
  }

  const stockEl = document.querySelector("#stock");
  const stockVal = parseInt(stockEl.value);
  if (isNaN(stockVal) || stockVal < 0) {
    stockEl.setCustomValidity("El stock debe ser 0 o mayor.");
    stockEl.reportValidity();
    $("#btnGuardar").prop("disabled", false);
    return;
  } else {
    stockEl.setCustomValidity("");
  }

  var formData = new FormData($("#formulario")[0]);

  $.ajax({
    url: "../ajax/articulo.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      const msg = String(datos || '').replace(/\uFEFF/g, '').trim();
      if (/duplicado/i.test(msg)) {
        bootbox.alert("⚠️ El nombre ya existe. No se permiten duplicados.");
        mostrarform(false);
        tabla.ajax.reload();
        $("#btnGuardar").prop("disabled", false);
        return;
      }
      bootbox.alert(msg);
      mostrarform(false);
      tabla.ajax.reload();
    }
  });

  limpiar();
}

/* ============================= Mostrar =============================== */
function mostrar(idarticulo) {
  $.post("../ajax/articulo.php?op=mostrar",
    { idarticulo: idarticulo },
    function (data, status) {
      data = (typeof data === 'string') ? JSON.parse(data) : data;
      mostrarform(true);

      $("#idcategoria").val(data.idcategoria);
      $('#idcategoria').selectpicker('refresh');

      $("#codigo").val(data.codigo || "");
      $("#nombre").val(data.nombre || "");
      $("#stock").val(data.stock || "");
      $("#precio_compra").val(data.precio_compra || "");
      $("#precio_venta").val(data.precio_venta || "");
      $("#descripcion").val(data.descripcion || "");

      if (data.imagen) {
        $("#imagenmuestra").attr("src", "../files/articulos/" + data.imagen).show();
      } else {
        $("#imagenmuestra").attr("src", "").hide();
      }
      $("#imagenactual").val(data.imagen || "");
      $("#idarticulo").val(data.idarticulo);

      // Sugerido / barcode
      precioVentaEditadoManualmente = false;
      actualizarSugerido();

      if (data.codigo && /^\d{8,13}$/.test(String(data.codigo))) {
        renderBarcode(String(data.codigo));
      } else {
        $("#print").hide();
      }
    }
  );
}

/* =================== Código de barras (Generar/Imprimir) =================== */

// Renderiza el código (elige EAN-8 o EAN-13 según longitud)
function renderBarcode(code) {
  const clean = String(code).replace(/\D/g, "");
  const fmt = clean.length === 8 ? "EAN8" : "EAN13"; // 8 -> EAN8, 12/13 -> EAN13
  JsBarcode("#barcode", clean, {
    format: fmt,
    displayValue: true,
    fontSize: 18,
    textMargin: 6,
    width: 2,
    height: 110,
    margin: 0
  });
  $("#print").show();
}

// Genera automáticamente un EAN-13 válido si el campo está vacío o inválido.
// Si ingresas 12 dígitos, completa el dígito de control y renderiza.
// Si ingresas 8 dígitos válidos, renderiza EAN-8.
function generarbarcode() {
  let code = $("#codigo").val().replace(/\D/g, "");

  if (/^\d{12}$/.test(code)) {
    code = code + ean13Checksum(code);
  } else if (!/^\d{8}$/.test(code) && !/^\d{13}$/.test(code)) {
    code = generarEAN13();
  }
  $("#codigo").val(code);
  renderBarcode(code);
}

// Imprime el área de código
function imprimir() {
  $("#print").printArea();
}

init();
