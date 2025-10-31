var tabla;

/* ==========================
   Helpers de validación
   ========================== */

// Normaliza espacios
function norm(txt) {
  return (txt || "").replace(/\s+/g, " ").trim();
}

// Mensaje nativo (tooltip)
function setValidity(el, ok, msg) {
  if (!el) return;
  el.setCustomValidity(ok ? "" : msg);
  if (!ok) el.reportValidity();
}

// Nombre: solo letras (con acentos) y espacios, 3..50, sin repeticiones absurdas
function esNombreCategoriaValido(v) {
  const txt = norm(v);

  // solo letras y espacios, largo 3..50
  if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]{3,50}$/.test(txt)) return false;

  // evita "aa", "qqqq", etc (2+ repeticiones seguidas)
  if (/([A-Za-zÁÉÍÓÚÜÑáéíóúüñ])\1{2,}/.test(txt)) return false;

  // evita basura conocida
  const basura = ["xxx", "asdf", "qwe", "test", "prueba", "wewe", "qweqwe", "demo"];
  if (basura.some(p => txt.toLowerCase().includes(p))) return false;

  return true;
}

// Descripción: opcional, máx 120, sin cadenas basura repetitivas
function esDescripcionValida(v) {
  const txt = norm(v);

  if (txt.length === 0) return true;          // opcional
  if (txt.length > 120) return false;

  // No permitir puras letras repetidas ("xxxx", "zzzz")
  if (/^([A-Za-zÁÉÍÓÚÜÑáéíóúüñ])\1{3,}$/.test(txt)) return false;

  // Debe contener al menos UNA letra (evitamos solo números)
  if (!/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ0-9]/.test(txt)) return false; // algo alfanumérico razonable
  if (!/[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]/.test(txt)) return false;

  // Evitar basura conocida
  const basura = ["asdf", "qwe", "zzz", "lorem", "prueba", "test"];
  if (basura.some(p => txt.toLowerCase().includes(p))) return false;

  return true;
}

/* ==========================
   Función que se ejecuta al inicio
   ========================== */
function init() {
  mostrarform(false);
  listar();

  $("#formulario").on("submit", function (e) {
    guardaryeditar(e);
  });

  $("#mAlmacen").addClass("treeview active");
  $("#lCategorias").addClass("active");

  // === Validación en vivo ===

  // Nombre: solo letras y espacios
  $("#nombre").on("input", function () {
    this.value = this.value
      .replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/g, "") // quita no letras
      .replace(/\s{2,}/g, " ");                   // sin doble espacio

    const ok = esNombreCategoriaValido(this.value);
    setValidity(this, ok, ok ? "" : "Solo letras y espacios (3 a 50), sin repeticiones.");
  });

  // Descripción: opcional, pero debe ser razonable
  $("#descripcion").on("input", function () {
    const ok = esDescripcionValida(this.value);
    setValidity(this, ok, ok ? "" : "Descripción inválida (máx. 120).");
  });
}

/* ==========================
   Función limpiar
   ========================== */
function limpiar() {
  $("#idcategoria").val("");
  $("#nombre").val("");
  $("#descripcion").val("");

  // limpiar mensajes de validación nativos
  ["#nombre", "#descripcion"].forEach(sel => {
    const el = document.querySelector(sel);
    if (el) el.setCustomValidity("");
  });
}

/* ==========================
   Mostrar/ocultar formulario
   ========================== */
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

/* ==========================
   Listado (DataTable)
   ========================== */
function listar() {
  tabla = $("#tbllistado").dataTable({
    "lengthMenu": [5, 10, 25, 75, 100],
    "aProcessing": true,        // Activamos el procesamiento del datatables
    "aServerSide": true,        // Paginación y filtrado realizados por el servidor
    dom: '<Bl<f>rtip>',         // Elementos del control de tabla
    buttons: [
      'copyHtml5',
      'excelHtml5',
      'csvHtml5',
      'pdf'
    ],
    "ajax": {
      url: '../ajax/categoria.php?op=listar',
      type: 'get',
      dataType: 'json',
      error: function (e) {
        console.log(e.responseText);
      }
    },
    "language": {
      "lengthMenu": "Mostrar : _MENU_ registros",
      "buttons": {
        "copyTitle": "Tabla Copiada",
        "copySuccess": {
          "_": "%d líneas copiadas",
          "1": "1 línea copiada"
        }
      }
    },
    "bDestroy": true,
    "iDisplayLength": 5,
    "order": [[0, "desc"]] // Ordenar (columna,orden)
  }).DataTable();
}

/* ==========================
   Guardar o editar
   ========================== */
function guardaryeditar(e) {
  e.preventDefault();
  $("#btnGuardar").prop("disabled", true);

  // Validar nombre antes de enviar
  const nombreEl = document.querySelector("#nombre");
  if (!esNombreCategoriaValido(nombreEl.value)) {
    setValidity(nombreEl, false, "Solo letras y espacios (3 a 50), sin repeticiones.");
    $("#btnGuardar").prop("disabled", false);
    return;
  } else {
    setValidity(nombreEl, true, "");
  }

  // Validar descripción antes de enviar
  const descEl = document.querySelector("#descripcion");
  if (!esDescripcionValida(descEl.value)) {
    setValidity(descEl, false, "Descripción inválida (máx. 120, sin cadenas basura).");
    $("#btnGuardar").prop("disabled", false);
    return;
  } else {
    setValidity(descEl, true, "");
  }

  var formData = new FormData($("#formulario")[0]);

  $.ajax({
    url: "../ajax/categoria.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,

    success: function (datos) {
      const res = (datos || "").toString().trim().toLowerCase();

      if (res === "duplicado") {
        bootbox.alert("El nombre de la categoría ya existe. No se permiten duplicados.");
        $("#btnGuardar").prop("disabled", false);
        return;
      }

      if (res === "error") {
        bootbox.alert("Ocurrió un error al registrar/actualizar la categoría.");
        $("#btnGuardar").prop("disabled", false);
        return;
      }

      // Mensaje normal que ya retornaba tu backend ("Categoría registrada", etc.)
      bootbox.alert(datos);
      mostrarform(false);
      tabla.ajax.reload();
    },

    error: function () {
      bootbox.alert("No se pudo completar la operación. Verifica tu conexión.");
      $("#btnGuardar").prop("disabled", false);
    }
  });

  limpiar();
}

/* ==========================
   Mostrar / activar / desactivar
   ========================== */
function mostrar(idcategoria) {
  $.post("../ajax/categoria.php?op=mostrar", { idcategoria: idcategoria }, function (data, status) {
    data = JSON.parse(data);
    mostrarform(true);

    $("#nombre").val(data.nombre);
    $("#descripcion").val(data.descripcion);
    $("#idcategoria").val(data.idcategoria);
  });
}

function desactivar(idcategoria) {
  bootbox.confirm("¿Está Seguro de desactivar la Categoría?", function (result) {
    if (result) {
      $.post("../ajax/categoria.php?op=desactivar", { idcategoria: idcategoria }, function (e) {
        bootbox.alert(e);
        tabla.ajax.reload();
      });
    }
  });
}

function activar(idcategoria) {
  bootbox.confirm("¿Está Seguro de activar la Categoría?", function (result) {
    if (result) {
      $.post("../ajax/categoria.php?op=activar", { idcategoria: idcategoria }, function (e) {
        bootbox.alert(e);
        tabla.ajax.reload();
      });
    }
  });
}

init();
