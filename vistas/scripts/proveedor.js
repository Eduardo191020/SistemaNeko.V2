/* vistas/scripts/proveedor.js
 * Gestión de Proveedores (UI + CRUD + Autocompletado SUNAT/RENIEC)
 * Requiere: jQuery, Bootstrap, DataTables, bootbox, selectpicker
 */

var tabla;

/* ========== Utilidad: debounce para limitar requests ========== */
function debounce(fn, wait) {
  let t;
  return function () {
    clearTimeout(t);
    const args = arguments, ctx = this;
    t = setTimeout(function () { fn.apply(ctx, args); }, wait || 400);
  };
}

/* ========== Helpers Chip de estado (azul/verde/rojo/ámbar) ========== */
function chipInfo(msg)  { const el = document.getElementById('docStatus'); if (!el) return; el.className = 'chip info'; el.innerHTML = '<i class="fa fa-info-circle"></i> ' + msg; }
function chipBusy(msg)  { const el = document.getElementById('docStatus'); if (!el) return; el.className = 'chip info'; el.innerHTML = '<i class="fa fa-hourglass-half"></i> ' + msg; }
function chipOK(msg)    { const el = document.getElementById('docStatus'); if (!el) return; el.className = 'chip ok';  el.innerHTML = '<i class="fa fa-check"></i> ' + msg; }
function chipErr(msg)   { const el = document.getElementById('docStatus'); if (!el) return; el.className = 'chip err'; el.innerHTML = '<i class="fa fa-times-circle"></i> ' + msg; }
function chipWarn(msg)  { const el = document.getElementById('docStatus'); if (!el) return; el.className = 'chip warn'; el.innerHTML = '<i class="fa fa-exclamation-triangle"></i> ' + msg; }

/* ========== Máscaras + placeholders según tipo documento ========== */
function setupDocMask() {
  const tipo   = document.getElementById('tipo_documento').value;
  const ndoc   = document.getElementById('num_documento');
  const ayuda  = document.getElementById('ayuda_doc');
  const nombre = document.getElementById('nombre');
  const dir    = document.getElementById('direccion');

  if (tipo === 'DNI') {
    ndoc.maxLength = 8;
    ndoc.setAttribute('pattern', '\\d{8}');
    ndoc.placeholder = 'DNI (8 dígitos)';
    if (ayuda) ayuda.textContent = 'DNI: 8 dígitos';
    nombre.readOnly = true;  nombre.classList.add('readonly');
    dir.readOnly = true;     dir.classList.add('readonly');
  } else if (tipo === 'RUC') {
    ndoc.maxLength = 11;
    ndoc.setAttribute('pattern', '\\d{11}');
    ndoc.placeholder = 'RUC (11 dígitos)';
    if (ayuda) ayuda.textContent = 'RUC: 11 dígitos';
    nombre.readOnly = true;  nombre.classList.add('readonly');
    dir.readOnly = true;     dir.classList.add('readonly');
  } else {
    ndoc.removeAttribute('pattern');
    ndoc.maxLength = 20;
    ndoc.placeholder = 'Documento';
    if (ayuda) ayuda.textContent = 'Documento';
    nombre.readOnly = false; nombre.classList.remove('readonly');
    dir.readOnly = false;    dir.classList.remove('readonly');
  }
}

/* ========== Consulta RENIEC / SUNAT con feedback visual ========== */
const consultaDoc = debounce(async function () {
  const tipo   = document.getElementById('tipo_documento').value;
  const ndocEl = document.getElementById('num_documento');
  const nombre = document.getElementById('nombre');
  const dir    = document.getElementById('direccion');

  // Normaliza: solo dígitos en el campo número
  ndocEl.value = (ndocEl.value || '').replace(/\D+/g, '');

  // Reset de estado si no alcanza longitud mínima
  if ((tipo === 'RUC' && ndocEl.value.length < 11) || (tipo === 'DNI' && ndocEl.value.length < 8)) {
    chipInfo('Esperando número…');
    return;
  }

  // ====== DNI - RENIEC ======
  if (tipo === 'DNI' && /^\d{8}$/.test(ndocEl.value)) {
    chipBusy('Consultando RENIEC…');
    nombre.value = 'Consultando RENIEC…';
    dir.value = '';
    try {
      const url = `../ajax/reniec.php?dni=${encodeURIComponent(ndocEl.value)}`;
      const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' }, cache: 'no-store' });
      const data = await res.json();
      if (!res.ok || data.success === false) throw new Error(data.message || 'RENIEC respondió con error');
      const nom = [data.nombres, data.apellidos].filter(Boolean).join(' ').trim();
      nombre.value = nom || '(sin nombre)';
      chipOK('RENIEC verificado');
    } catch (e) {
      console.error('RENIEC fail:', e);
      nombre.value = 'No se pudo consultar RENIEC';
      chipErr('Error RENIEC');
    }
    return;
  }

  // ====== RUC - SUNAT ======
  if (tipo === 'RUC' && /^\d{11}$/.test(ndocEl.value)) {
    chipBusy('Consultando SUNAT…');
    nombre.value = 'Consultando SUNAT…';
    dir.value = '';
    try {
      const url = `../ajax/sunat.php?ruc=${encodeURIComponent(ndocEl.value)}`;
      const res = await fetch(url, { headers: { 'X-Requested-With': 'fetch' }, cache: 'no-store' });
      // Si la respuesta no es JSON válido, .json() arroja excepción => caerá al catch (chip rojo)
      const data = await res.json();

      // Algunos proveedores pueden no enviar "success". Forzamos OK si trajo razón social o dirección.
      const okFlag = (data && (data.success === true || data.razon_social || data.nombre_o_razon_social));

      if (!res.ok || !okFlag) throw new Error(data && data.message ? data.message : 'SUNAT respondió con error');

      const razon = (data.razon_social || data.nombre_o_razon_social || '').toString().trim();
      const direccion = (data.direccion || data.domicilio_fiscal || '').toString().trim();

      if (razon)  nombre.value = razon; else nombre.value = '(sin razón social)';
      if (direccion) dir.value = direccion; else dir.value = '';

      chipOK('SUNAT verificado');
    } catch (e) {
      console.error('SUNAT fail:', e);
      nombre.value = 'No se pudo consultar SUNAT';
      dir.value = '';
      chipErr('Error SUNAT');
    }
  }
}, 450);

/* ========== Teléfono: solo dígitos y 9 máximo ========== */
function setupTelefonoRules() {
  const tel = document.getElementById('telefono');
  if (!tel) return;
  tel.addEventListener('input', function () {
    this.value = this.value.replace(/\D+/g, '').slice(0, 9);
  });
}

/* ================== CRUD y UI ================== */
function init() {
  mostrarform(false);
  listar();

  $("#formulario").on("submit", function (e) {
    e.preventDefault();

    // Validaciones básicas
    const tel = (document.getElementById('telefono').value || '').trim();
    if (!/^\d{9}$/.test(tel)) { bootbox.alert('El teléfono debe tener exactamente 9 dígitos.'); return; }

    const tipo = document.getElementById('tipo_documento').value;
    const ndoc = (document.getElementById('num_documento').value || '').trim();
    if (tipo === 'DNI' && !/^\d{8}$/.test(ndoc)) { bootbox.alert('DNI inválido. Debe tener 8 dígitos.'); return; }
    if (tipo === 'RUC' && !/^\d{11}$/.test(ndoc)) { bootbox.alert('RUC inválido. Debe tener 11 dígitos.'); return; }

    guardaryeditar();
  });

  // Menú activo
  $('#mCompras').addClass("treeview active");
  $('#lProveedores').addClass("active");

  // Máscaras y reglas
  setupDocMask();
  setupTelefonoRules();

  // Eventos de autocompletado
  const tipoEl = document.getElementById('tipo_documento');
  const numEl  = document.getElementById('num_documento');

  if (tipoEl) {
    tipoEl.addEventListener('change', function () {
      setupDocMask();
      chipInfo('Esperando número…');  // reset chip al cambiar tipo
      consultaDoc();                  // dispara consulta si ya hay número válido
      try { $('#tipo_documento').selectpicker('refresh'); } catch (_) {}
    });
  }
  if (numEl) {
    // Dispara consulta en cada edición y al salir del campo
    ['input','keyup','change','blur'].forEach(ev => numEl.addEventListener(ev, consultaDoc));
  }
}

/* --- Helpers UI --- */
function limpiar() {
  $("#nombre").val("");
  $("#num_documento").val("");
  $("#direccion").val("");
  $("#telefono").val("");
  $("#email").val("");
  $("#idpersona").val("");
  chipInfo('Esperando número…');
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

function cancelarform() { limpiar(); mostrarform(false); }

/* --- DataTable listado --- */
function listar() {
  tabla = $('#tbllistado').dataTable({
    "lengthMenu": [5, 10, 25, 75, 100],
    "aProcessing": true,
    "aServerSide": true,
    dom: '<Bl<f>rtip>',
    buttons: ['copyHtml5', 'excelHtml5', 'csvHtml5', 'pdf'],
    "ajax": {
      url: '../ajax/persona.php?op=listarp',
      type: "get",
      dataType: "json",
      error: function (e) { console.log(e.responseText); }
    },
    "language": {
      "lengthMenu": "Mostrar : _MENU_ registros",
      "buttons": {
        "copyTitle": "Tabla Copiada",
        "copySuccess": { _: '%d líneas copiadas', 1: '1 línea copiada' }
      }
    },
    "bDestroy": true,
    "iDisplayLength": 5,
    "order": [[0, "desc"]]
  }).DataTable();
}

/* --- Guardar / Editar --- */
function guardaryeditar() {
  $("#btnGuardar").prop("disabled", true);
  var formData = new FormData($("#formulario")[0]);

  $.ajax({
    url: "../ajax/persona.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function (datos) {
      bootbox.alert(datos);
      mostrarform(false);
      tabla.ajax.reload();
    },
    complete: function () { $("#btnGuardar").prop("disabled", false); }
  });
  limpiar();
}

/* --- Mostrar para editar --- */
function mostrar(idpersona) {
  $.post("../ajax/persona.php?op=mostrar", { idpersona: idpersona }, function (data, status) {
    data = JSON.parse(data);
    mostrarform(true);

    $("#nombre").val(data.nombre);
    $("#tipo_documento").val(data.tipo_documento);
    try { $("#tipo_documento").selectpicker('refresh'); } catch (_) {}
    $("#num_documento").val(data.num_documento);
    $("#direccion").val(data.direccion);
    $("#telefono").val(data.telefono);
    $("#email").val(data.email);
    $("#idpersona").val(data.idpersona);

    setupDocMask();
    chipWarn('Editando proveedor'); // estado ámbar cuando estás editando
  });
}

/* --- Eliminar --- */
function eliminar(idpersona) {
  bootbox.confirm("¿Está Seguro de eliminar el proveedor?", function (result) {
    if (result) {
      $.post("../ajax/persona.php?op=eliminar", { idpersona: idpersona }, function (e) {
        bootbox.alert(e);
        tabla.ajax.reload();
      });
    }
  });
}

/* ========== Arranque ========== */
init();
