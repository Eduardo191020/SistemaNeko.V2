/* vistas/scripts/cliente.js
 * Gestión de Clientes con autocompletado RENIEC (solo DNI)
 * Requiere: jQuery, DataTables, Buttons, bootbox, selectpicker
 */

var tabla;

/* ======================== Utilidades ======================== */
function debounce(fn, wait){
  let t;
  return function(){
    clearTimeout(t);
    const ctx = this, args = arguments;
    t = setTimeout(function(){ fn.apply(ctx, args); }, wait||350);
  };
}

function setEstado(msg, kind){
  let color = (kind==='ok') ? 'green'
            : (kind==='err' ? '#b91c1c'
            : '#374151'); // slate-700
  $("#estadoDoc").remove();
  $("#num_documento").closest('.input-group, .form-group').after(
    '<small id="estadoDoc" style="display:block;margin-top:6px;color:'+color+';font-weight:600;">'+msg+'</small>'
  );
}

function bloquearCamposFijos(){
  // Nombre y Dirección solo los trae RENIEC
  $("#nombre").prop("readonly", true).addClass("disabled");
  $("#direccion").prop("readonly", true).addClass("disabled");
}
function desbloquearCamposFijos(){
  $("#nombre").prop("readonly", false).removeClass("disabled");
  $("#direccion").prop("readonly", false).removeClass("disabled");
}

/* ======================== Boot ======================== */
function init(){
  mostrarform(false);
  listar();

  // Guardar
  $("#formulario").on("submit", function(e){ guardaryeditar(e); });

  // Menú activo
  $('#mVentas').addClass("treeview active");
  $('#lClientes').addClass("active");

  // Tipo persona fijo = Cliente
  $("#tipo_persona").val("Cliente");

  // Tipo documento visual y espejo hidden (para que siempre viaje en POST)
  $("#tipo_documento_view").val("DNI");
  try { $("#tipo_documento_view").selectpicker('refresh'); } catch(e){}
  $("#tipo_documento_hidden").val("DNI");
  $("#tipo_documento_view").on('change', function(){
    $("#tipo_documento_hidden").val(this.value || 'DNI');
  });

  // Handlers RENIEC
  $("#num_documento")
    .on("input", function(){
      // Solo dígitos y máximo 8
      let v = (this.value||"").replace(/\D/g,'').slice(0,8);
      if (this.value !== v) this.value = v;
    })
    .on("keyup change", debounce(onDniChange, 400));

  // Botón buscar (consulta manual)
  $("#btnBuscarDoc").off("click").on("click", function(){
    const dni = ($("#num_documento").val()||"").replace(/\D/g,'');
    if (dni.length===8) consultarReniec(dni);
    else setEstado("Ingresa 8 dígitos de DNI","err");
  });

  // Estado inicial y bloqueo de campos
  setEstado("Esperando número…","info");
  bloquearCamposFijos();
}

/* ======================== Limpieza/Form ======================== */
function limpiar(){
  $("#idpersona").val("");
  $("#nombre").val("");
  $("#num_documento").val("");
  $("#telefono").val("");
  $("#email").val("");
  $("#direccion").val("");

  $("#tipo_persona").val("Cliente");
  $("#tipo_documento_view").val("DNI");
  $("#tipo_documento_hidden").val("DNI");
  try { $("#tipo_documento_view").selectpicker('refresh'); } catch(e){}

  setEstado("Esperando número…","info");
  bloquearCamposFijos();
}

function mostrarform(flag){
  limpiar();
  if (flag){
    $("#listadoregistros").hide();
    $("#formularioregistros").show();
    $("#btnGuardar").prop("disabled", false);
    $("#btnagregar").hide();
  }else{
    $("#listadoregistros").show();
    $("#formularioregistros").hide();
    $("#btnagregar").show();
  }
}

function cancelarform(){
  limpiar();
  mostrarform(false);
}

/* ======================== Listado ======================== */
function listar(){
  tabla = $('#tbllistado').dataTable({
    lengthMenu: [5, 10, 25, 75, 100],
    aProcessing: true,
    aServerSide: true,
    dom: '<Bl<f>rtip>', // Buttons + length + filter arriba
    buttons: ['copyHtml5','excelHtml5','csvHtml5','pdf'],
    ajax: {
      url: '../ajax/persona.php?op=listarc',
      type: 'GET',
      dataType: 'json',
      error: function(e){ console.log(e.responseText); }
    },
    language: {
      lengthMenu: 'Mostrar : _MENU_ registros',
      buttons: {
        copyTitle: 'Tabla Copiada',
        copySuccess: { _: '%d líneas copiadas', 1: '1 línea copiada' }
      }
    },
    bDestroy: true,
    iDisplayLength: 5,
    order: [[0,'desc']]
  }).DataTable();
}

/* ======================== Guardar/Editar ======================== */
function guardaryeditar(e){
  e.preventDefault();
  $("#btnGuardar").prop("disabled", true);

  // Sincroniza espejos: estos SI viajan en POST
  $("#tipo_persona").val("Cliente");
  $("#tipo_documento_hidden").val($("#tipo_documento_view").val() || 'DNI');

  bloquearCamposFijos(); // por si acaso

  var formData = new FormData($("#formulario")[0]);
  $.ajax({
    url: "../ajax/persona.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function(datos){
      bootbox.alert(datos);
      mostrarform(false);
      if (tabla && tabla.ajax && typeof tabla.ajax.reload==='function') tabla.ajax.reload();
    },
    error: function(xhr){
      console.error('Error guardando cliente', xhr.status, xhr.responseText);
      bootbox.alert("Ocurrió un error al guardar.");
    },
    complete: function(){
      $("#btnGuardar").prop("disabled", false);
    }
  });
}

/* ======================== Mostrar/Eliminar ======================== */
function mostrar(idpersona){
  $.post("../ajax/persona.php?op=mostrar", { idpersona: idpersona }, function(data){
    data = JSON.parse(data);
    mostrarform(true);

    $("#idpersona").val(data.idpersona);
    $("#nombre").val(data.nombre);

    $("#tipo_persona").val("Cliente");
    $("#tipo_documento_view").val("DNI");
    $("#tipo_documento_hidden").val("DNI");
    try { $("#tipo_documento_view").selectpicker('refresh'); } catch(e){}

    $("#num_documento").val((data.num_documento||'').replace(/\D/g,'').slice(0,8));
    $("#telefono").val(data.telefono);
    $("#email").val(data.email);
    $("#direccion").val(data.direccion||'');

    bloquearCamposFijos();
    setEstado("Datos cargados (edición)","info");
  });
}

function eliminar(idpersona){
  bootbox.confirm("¿Está Seguro de eliminar el cliente?", function(result){
    if(result){
      $.post("../ajax/persona.php?op=eliminar", { idpersona: idpersona }, function(e){
        bootbox.alert(e);
        if (tabla && tabla.ajax && typeof tabla.ajax.reload==='function') tabla.ajax.reload();
      });
    }
  });
}

/* ======================== RENIEC ======================== */
function onDniChange(){
  const dni = ($("#num_documento").val()||"").replace(/\D/g,'');
  if (dni.length===8){
    consultarReniec(dni);
  }else{
    $("#nombre").val("");
    $("#direccion").val("");
    setEstado("Esperando número…","info");
    bloquearCamposFijos();
  }
}

function consultarReniec(dni){
  // Spinner en botón si existe
  const $btn = $("#btnBuscarDoc");
  const hadBtn = $btn.length>0;
  let oldHtml;
  if (hadBtn){
    oldHtml = $btn.html();
    $btn.prop("disabled", true).html('<i class="fa fa-spinner fa-spin"></i>');
  }

  setEstado("Consultando RENIEC…","info");

  $.ajax({
    url: "../ajax/reniec.php",
    type: "GET",
    dataType: "json",
    cache: false,
    data: { dni: dni, _: Date.now() },
    success: function(resp){
      if (resp && resp.success){
        const nombre = [resp.nombres||'', resp.apellidos||''].join(' ').trim();
        $("#nombre").val(nombre);
        $("#direccion").val(resp.direccion || '');
        bloquearCamposFijos();
        setEstado("Datos encontrados (RENIEC)","ok");
      }else{
        $("#nombre").val('');
        $("#direccion").val('');
        bloquearCamposFijos();
        setEstado((resp && (resp.message||resp.msg)) ? (resp.message||resp.msg) : "DNI no encontrado","err");
      }
    },
    error: function(xhr){
      console.error("RENIEC error", xhr.status, xhr.responseText);
      $("#nombre").val('');
      $("#direccion").val('');
      bloquearCamposFijos();
      setEstado("Error consultando servicio","err");
    },
    complete: function(){
      if (hadBtn){
        $btn.prop("disabled", false).html(oldHtml);
      }
    }
  });
}

/* ======================== Run ======================== */
init();
