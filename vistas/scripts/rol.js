var tabla; 
// Permitir solo letras (con acentos) y espacios; compacta espacios
function soloLetras(el){
  el.value = el.value
    .replace(/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/g, "")
    .replace(/\s{2,}/g, " ")
    .replace(/^\s+/, "");
}

// Validar nombre con criterio avanzado
function esNombreValido(txt){
  txt = txt.trim();
  if (!/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ ]{3,50}$/.test(txt)) return false;
  if (/^(.)\1{2,}$/.test(txt)) return false;
  if (!/[AEIOUÁÉÍÓÚaeiouáéíóú]/.test(txt)) return false;
  const invalidos = ["xxx", "wewqeq", "asdf", "qwe", "test", "rol", "role", "prueba"];
  if (invalidos.some(p => txt.toLowerCase().includes(p))) return false;
  return true;
}

//Función que se ejecuta al inicio
function init(){
  mostrarform(false);
  listar();

  $("#formulario").on("submit", function(e){
    guardaryeditar(e);
  });

  $("#mAcceso").addClass("treeview active");
  $("#lRoles").addClass("active");
}

//Función limpiar
function limpiar(){
  $("#idrol").val("");
  $("#nombre").val("");
}

//Función mostrar formulario
function mostrarform(flag){
  limpiar();
  if(flag){
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

//Función cancelar formulario
function cancelarform(){
  limpiar();
  mostrarform(false);
}

// ======= FUNCIÓN LISTAR (CORREGIDA) =======
function listar(){
  tabla = $('#tbllistado').dataTable({
    "aProcessing": true,
    "aServerSide": true,
    dom: 'Bfrtip',
    buttons: ['copyHtml5','excelHtml5','csvHtml5','pdf'],
    "ajax": {
      url: '../ajax/rol.php?op=listar',
      type: "get",
      dataType: "json",
      error: function(e){ console.log(e.responseText); }
    },
    // Mapea las 4 columnas visibles a los índices correctos del backend
    "columns": [
      { "data": 0 }, // Opciones
      { "data": 2 }, // Nombre (saltamos el 1, que es el ID)
      { "data": 3 }, // Estado
      { "data": 4 }  // Creado
    ],
    // Opciones no ordenable/buscable
    "columnDefs": [
      { "targets": 0, "orderable": false, "searchable": false }
    ],
    "bDestroy": true,
    "iDisplayLength": 10,
    "order": [[3, "asc"]]   // Ordenar por Nombre (la 2da visible)
  }).DataTable();
}
// =========================================

function guardaryeditar(e){
  e.preventDefault();
  $("#btnGuardar").prop("disabled", true);

  const nom = $("#nombre").val().trim();
  if (!esNombreValido(nom)){
    bootbox.alert("⚠️ Elija un nombre válido. Evite letras repetidas");
    $("#nombre").focus();
    $("#btnGuardar").prop("disabled", false);
    return;
  }

  var formData = new FormData($("#formulario")[0]);

  $.ajax({
    url: "../ajax/rol.php?op=guardaryeditar",
    type: "POST",
    data: formData,
    contentType: false,
    processData: false,
    success: function(datos){
      bootbox.alert(datos);
      mostrarform(false);
      tabla.ajax.reload();
    }
  });

  limpiar();
}

//Función para mostrar los datos de un registro
function mostrar(idrol){
  $.post("../ajax/rol.php?op=mostrar", {idrol: idrol}, function(data, status){
    data = JSON.parse(data);
    mostrarform(true);
    $("#idrol").val(data.id_rol); // oculto, interno
    $("#nombre").val(data.nombre);
  });
}

//Función para desactivar registros
function desactivar(idrol){
  bootbox.confirm("¿Está seguro de desactivar el rol?", function(result){
    if(result){
      $.post("../ajax/rol.php?op=desactivar", {idrol: idrol}, function(e){
        bootbox.alert(e);
        tabla.ajax.reload();
      });
    }
  });
}

//Función para activar registros
function activar(idrol){
  bootbox.confirm("¿Está seguro de activar el rol?", function(result){
    if(result){
      $.post("../ajax/rol.php?op=activar", {idrol: idrol}, function(e){
        bootbox.alert(e);
        tabla.ajax.reload();
      });
    }
  });
}

init();
