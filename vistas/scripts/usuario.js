/* vistas/scripts/usuario.js */
var tabla; 

// ====================== INICIO ======================
function init(){
  mostrarform(false);
  listar();

  $("#formulario").on("submit", function(e){ guardaryeditar(e); });

  $("#imagenmuestra").hide();
  $("#pwd-strength").hide();
  
  // Mostrar permisos (alta/edición)
  $.post("../ajax/usuario.php?op=permisos&id=", function(r){
    $("#permisos").html(r);
  });
  
  $('#mAcceso').addClass("treeview active");
  $('#lUsuarios').addClass("active");

  // Inicializaciones diferidas
  setTimeout(function(){
    setupDocumentValidation();
    setupPasswordValidation();
    setupEmailValidation();
    setupPhoneValidation();
    togglePasswordVisibility();
  }, 300);
}

// ========== VALIDACIÓN DE TELÉFONO ==========
function setupPhoneValidation() {
  const telefonoInput = document.getElementById('telefono');
  if (telefonoInput) {
    $(telefonoInput).off('input keypress');
    $(telefonoInput).on('input', function(){
      this.value = this.value.replace(/[^0-9\s\-+]/g, '');
    });
    $(telefonoInput).on('keypress', function(e){
      const char = String.fromCharCode(e.which);
      if (!/[0-9\s\-+]/.test(char)) { e.preventDefault(); return false; }
    });
  }
}

// ========== VALIDACIÓN DE DOCUMENTOS ==========
function setupDocumentValidation() {
  const tipo_documento = document.getElementById("tipo_documento");
  const num_documento  = document.getElementById("num_documento");
  const nombre         = document.getElementById("nombre");
  const hint_tipo      = document.getElementById("hint_tipo");
  const hint_numero    = document.getElementById("hint_numero");
  if (!tipo_documento || !num_documento || !nombre) return;

  let timer, inflight, lastQueried='';

  $(num_documento).off('input keypress blur');
  $(tipo_documento).off('change');

  $(num_documento).on('input', function(){
    const tipoDoc = $(tipo_documento).val();
    if (tipoDoc==='DNI' || tipoDoc==='RUC') { this.value = this.value.replace(/\D/g,''); }
    debounceConsulta();
  });

  $(num_documento).on('keypress', function(e){
    const tipoDoc = $(tipo_documento).val();
    if (tipoDoc==='DNI' || tipoDoc==='RUC') {
      const code = e.which ? e.which : e.keyCode;
      if (code>31 && (code<48 || code>57)) { e.preventDefault(); return false; }
    }
  });

  $(tipo_documento).on('change', function(){
    $(num_documento).val('');
    $(nombre).val('').attr('readonly','readonly');
    lastQueried='';
    const t = $(this).val();
    if (t==="DNI"){
      $(num_documento).attr({"maxlength":"8","pattern":"[0-9]{8}"});
      $(hint_numero).text("DNI: 8 dígitos").removeClass().addClass("text-muted");
      $(hint_tipo).html('<i class="fa fa-check text-success"></i> Se consultará RENIEC automáticamente');
    } else if (t==="RUC"){
      $(num_documento).attr({"maxlength":"11","pattern":"[0-9]{11}"});
      $(hint_numero).text("RUC: 11 dígitos").removeClass().addClass("text-muted");
      $(hint_tipo).html('<i class="fa fa-check text-success"></i> Se consultará SUNAT automáticamente');
    } else if (t==="Carnet de Extranjería"){
      $(num_documento).attr("maxlength","12").removeAttr("pattern");
      $(hint_numero).text("Carnet: 9-12 caracteres").removeClass().addClass("text-muted");
      $(hint_tipo).html('<i class="fa fa-info-circle text-info"></i> Deberás ingresar el nombre manualmente');
      $(nombre).removeAttr('readonly');
    } else {
      $(num_documento).attr("maxlength","20").removeAttr("pattern");
      $(hint_numero).text("Ingresa el número de documento").removeClass().addClass("text-muted");
      $(hint_tipo).text("Selecciona el tipo de documento");
    }
  });

  function consultarRENIEC(){
    const tipoDoc = $(tipo_documento).val();
    const numDoc  = $(num_documento).val();
    if (tipoDoc!=='DNI' || !/^\d{8}$/.test(numDoc)) return;
    if (numDoc===lastQueried) return;

    if (inflight) inflight.abort();
    inflight = new AbortController();
    const prevNombre = $(nombre).val();
    $(nombre).val('Consultando RENIEC...').css('background','#ffffcc');
    $(hint_numero).html('<i class="fa fa-spinner fa-spin text-info"></i> Consultando...').removeClass().addClass('text-info');

    $.ajax({
      url:'../ajax/reniec.php', type:'GET', data:{dni:numDoc}, dataType:'json', timeout:10000,
      success: function(data){
        if (data.success===true){
          const nom = ((data.nombres||'')+' '+(data.apellidos||'')).trim();
          $(nombre).val(nom).css('background','#d4edda');
          $(hint_numero).html('<i class="fa fa-check text-success"></i> Datos verificados por RENIEC').removeClass().addClass('text-success');
          lastQueried=numDoc;
          setTimeout(function(){ $(nombre).css('background',''); $(hint_numero).removeClass().addClass('text-muted'); },3000);
        } else { throw new Error(data.message||'Error al consultar RENIEC'); }
      },
      error: function(xhr, status, error){
        $(nombre).val(prevNombre).css('background','#f8d7da');
        $(hint_numero).html('<i class="fa fa-times text-danger"></i> '+(error||'Error al consultar RENIEC')).removeClass().addClass('text-danger');
        setTimeout(function(){ $(nombre).css('background',''); $(hint_numero).text('DNI: 8 dígitos').removeClass().addClass('text-muted'); },4000);
      }
    });
  }

  function consultarSUNAT(){
    const tipoDoc = $(tipo_documento).val();
    const numDoc  = $(num_documento).val();
    if (tipoDoc!=='RUC' || !/^\d{11}$/.test(numDoc)) return;
    if (numDoc===lastQueried) return;

    if (inflight) inflight.abort();
    inflight = new AbortController();
    const prevNombre = $(nombre).val();
    $(nombre).val('Consultando SUNAT...').css('background','#ffffcc');
    $(hint_numero).html('<i class="fa fa-spinner fa-spin text-info"></i> Consultando...').removeClass().addClass('text-info');

    $.ajax({
      url:'../ajax/sunat.php', type:'GET', data:{ruc:numDoc}, dataType:'json', timeout:10000,
      success: function(data){
        if (data.success===true){
          $(nombre).val(data.razon_social||'').css('background','#d4edda');
          $(hint_numero).html('<i class="fa fa-check text-success"></i> Datos verificados por SUNAT').removeClass().addClass('text-success');
          lastQueried=numDoc;
          setTimeout(function(){ $(nombre).css('background',''); $(hint_numero).removeClass().addClass('text-muted'); },3000);
        } else { throw new Error(data.message||'Error al consultar SUNAT'); }
      },
      error: function(xhr, status, error){
        $(nombre).val(prevNombre).css('background','#f8d7da');
        $(hint_numero).html('<i class="fa fa-times text-danger"></i> '+(error||'Error al consultar SUNAT')).removeClass().addClass('text-danger');
        setTimeout(function(){ $(nombre).css('background',''); $(hint_numero).text('RUC: 11 dígitos').removeClass().addClass('text-muted'); },4000);
      }
    });
  }

  function debounceConsulta(){ clearTimeout(timer); timer = setTimeout(function(){ 
    const t=$(tipo_documento).val(); if(t==='DNI') consultarRENIEC(); else if(t==='RUC') consultarSUNAT(); 
  },1000); }

  $(num_documento).on('blur', function(){
    const t=$(tipo_documento).val(); if(t==='DNI') consultarRENIEC(); else if(t==='RUC') consultarSUNAT();
  });
}

// ========== VALIDACIÓN DE EMAIL ==========
function setupEmailValidation(){
  const emailInput  = document.getElementById('email');
  const emailHint   = document.getElementById('email-hint');
  const emailStatus = document.getElementById('email-status');
  if (!emailInput) return;

  let timer, lastChecked='';
  $(emailInput).off('input blur');

  function isValidFormat(e){ return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(e); }

  function validateEmail(){
    const email = $(emailInput).val().trim();
    if (!email){ $(emailStatus).text(''); $(emailHint).text('Se usará como usuario de acceso al sistema').removeClass().addClass('text-muted'); emailInput.setCustomValidity(''); return; }
    if (email===lastChecked) return;
    if (!isValidFormat(email)){ $(emailStatus).text('❌'); $(emailHint).text('Formato de correo inválido').removeClass().addClass('text-danger'); emailInput.setCustomValidity('Formato inválido'); return; }
    $(emailStatus).text('⏳'); $(emailHint).text('Verificando correo...').removeClass().addClass('text-info');
    $.ajax({
      url:'../ajax/validate_email.php', type:'GET', data:{email:email}, dataType:'json', timeout:10000,
      success:function(data){
        if (data.success && data.valid){ $(emailStatus).text('✅'); $(emailHint).text('Correo válido y verificado').removeClass().addClass('text-success'); emailInput.setCustomValidity(''); lastChecked=email; }
        else { $(emailStatus).text('❌'); $(emailHint).text(data.message||'Este correo no es válido').removeClass().addClass('text-danger'); emailInput.setCustomValidity(data.message||'Email inválido'); }
      },
      error:function(){ $(emailStatus).text('⚠️'); $(emailHint).text('No se pudo verificar. Asegúrate que sea un correo real.').removeClass().addClass('text-warning'); emailInput.setCustomValidity(''); }
    });
  }

  function debounce(){ clearTimeout(timer); timer = setTimeout(validateEmail,1200); }
  $(emailInput).on('input', debounce);
  $(emailInput).on('blur', validateEmail);
}

// ========== VALIDACIÓN DE CONTRASEÑA ==========
function setupPasswordValidation(){
  const pwd = document.getElementById('clave');
  const strengthDiv = document.getElementById('pwd-strength');
  if (!pwd || !strengthDiv) return;

  $(pwd).off('input focus');

  function mark(id, ok){
    const el = document.getElementById(id); if (!el) return;
    const icon=$(el).find('i');
    if (ok){ icon.removeClass('fa-times text-danger').addClass('fa-check text-success'); }
    else   { icon.removeClass('fa-check text-success').addClass('fa-times text-danger'); }
  }

  function checkStrength(v){
    if (!v){ $(strengthDiv).hide(); return false; }
    $(strengthDiv).show();
    const len = v.length>=10 && v.length<=64;
    const up  = /[A-Z]/.test(v);
    const low = /[a-z]/.test(v);
    const num = /[0-9]/.test(v);
    const spe = /[!@#$%^&*()_\+\=\-\[\]{};:,.?]/.test(v);
    mark('r-len',len); mark('r-up',up); mark('r-low',low); mark('r-num',num); mark('r-spe',spe);
    return len && up && low && num && spe;
  }

  $(pwd).on('input', function(){ checkStrength($(this).val()); });
  $(pwd).on('focus', function(){ if ($(this).val()) $(strengthDiv).show(); });
}

// ========== VER/OCULTAR CONTRASEÑA ==========
function togglePasswordVisibility(){
  const toggleBtn = document.getElementById('toggleClave');
  const pwdInput  = document.getElementById('clave');
  if (!toggleBtn || !pwdInput) return;

  $(toggleBtn).off('click');
  $(toggleBtn).on('click', function(){
    if ($(pwdInput).attr('type')==='password'){ $(pwdInput).attr('type','text'); $(this).text('🙈'); }
    else{ $(pwdInput).attr('type','password'); $(this).text('👁️'); }
  });
}

// =================== FUNCIONES ORIGINALES ===================

function limpiar(){
  $("#nombre").val("");
  $("#tipo_documento").val("");
  $("#num_documento").val("");
  $("#direccion").val("");
  $("#telefono").val("");
  $("#email").val("");
  $("#cargo").val("");
  $("#clave").val("");
  $("#imagenmuestra").attr("src","");
  $("#imagenactual").val("");
  $("#idusuario").val("");
  $("#imagenmuestra").hide();
  $("#pwd-strength").hide();
  $("#email-status").text("");
  $("#email-hint").text("Se usará como usuario de acceso al sistema").removeClass().addClass("text-muted");
  $("#hint_numero").text("Ingresa el número de documento").removeClass().addClass("text-muted");
  $("#hint_tipo").text("Selecciona el tipo de documento").removeClass().addClass("text-muted");
  if (document.getElementById('email')) { document.getElementById('email').setCustomValidity(''); }
  $("#nombre").attr('readonly','readonly');
}

function mostrarform(flag){
  limpiar();
  if (flag){
    $("#listadoregistros").hide();
    $("#formularioregistros").show();
    $("#btnGuardar").prop("disabled",false);
    $("#btnagregar").hide();
  } else {
    $("#listadoregistros").show();
    $("#formularioregistros").hide();
    $("#btnagregar").show();
  }
}

function cancelarform(){ limpiar(); mostrarform(false); }

function listar(){
  tabla = $('#tbllistado').dataTable({
    "lengthMenu":[5,10,25,75,100],
    "aProcessing":true,
    "aServerSide":true,
    dom:'<Bl<f>rtip>',
    buttons:['copyHtml5','excelHtml5','csvHtml5','pdf'],
    "ajax":{
      url:'../ajax/usuario.php?op=listar',
      type:"get",
      dataType:"json",
      error:function(e){ console.log(e.responseText); }
    },
    "language":{
      "lengthMenu":"Mostrar : _MENU_ registros",
      "buttons":{
        "copyTitle":"Tabla Copiada",
        "copySuccess":{ _: '%d líneas copiadas', 1:'1 línea copiada' }
      }
    },
    "bDestroy":true,
    "iDisplayLength":5,
    "order":[[0,"desc"]]
  }).DataTable();
}

// =============== GUARDAR / EDITAR =================
function guardaryeditar(e){
  e.preventDefault();

  // Al menos un permiso
  var permisosChecked = $("input[name='permiso[]']:checked").length;
  if (permisosChecked===0){ bootbox.alert("Debes seleccionar al menos un permiso para el usuario."); return; }

  $("#btnGuardar").prop("disabled",true);

  var formData = new FormData($("#formulario")[0]);

  // REGLA CRÍTICA: si la clave está vacía, NO la enviamos; marcamos mantener_clave=1
  var claveVal = ($("#clave").val() || "").trim();
  if (!claveVal){
    formData.delete('clave');                 // evita que el backend piense que quieres cambiarla
    formData.append('mantener_clave','1');    // bandera para el backend
  } else {
    formData.append('mantener_clave','0');
  }

  $.ajax({
    url:"../ajax/usuario.php?op=guardaryeditar",
    type:"POST",
    data:formData,
    contentType:false,
    processData:false,
    success:function(datos){
      bootbox.alert(datos);
      mostrarform(false);
      tabla.ajax.reload();
    },
    error:function(xhr,status,error){
      bootbox.alert("Error al guardar: "+error);
      $("#btnGuardar").prop("disabled",false);
    }
  });

  limpiar();
}

// =============== MOSTRAR (EDICIÓN) =================
function mostrar(idusuario){
  $.post("../ajax/usuario.php?op=mostrar",{idusuario:idusuario}, function(data,status){
    data = JSON.parse(data);
    mostrarform(true);

    $("#tipo_documento").val(data.tipo_documento).selectpicker('refresh').trigger('change');
    $("#num_documento").val(data.num_documento);
    $("#nombre").val(data.nombre).removeAttr('readonly');

    $("#direccion").val(data.direccion);
    $("#telefono").val(data.telefono);
    $("#email").val(data.email);
    $("#cargo").val(data.cargo);

    // *** IMPORTANTE ***
    // No cargamos el hash en el input de clave: queda vacío
    $("#clave").val("").attr("placeholder","Dejar en blanco para mantener la contraseña");

    // Reiniciar estado del toggle a password
    $("#toggleClave").text('👁️');
    $("#clave").attr('type','password');

    $("#imagenmuestra").show().attr("src","../files/usuarios/"+data.imagen);
    $("#imagenactual").val(data.imagen);
    $("#idusuario").val(data.idusuario);
  });

  $.post("../ajax/usuario.php?op=permisos&id="+idusuario, function(r){
    $("#permisos").html(r);
  });
}

// =============== ACTIVAR / DESACTIVAR ===============
function desactivar(idusuario){
  bootbox.confirm("¿Está Seguro de desactivar el usuario?", function(result){
    if(result){
      $.post("../ajax/usuario.php?op=desactivar",{idusuario:idusuario}, function(e){
        bootbox.alert(e);
        tabla.ajax.reload();
      });
    }
  });
}

function activar(idusuario){
  bootbox.confirm("¿Está Seguro de activar el Usuario?", function(result){
    if(result){
      $.post("../ajax/usuario.php?op=activar",{idusuario:idusuario}, function(e){
        bootbox.alert(e);
        tabla.ajax.reload();
      });
    }
  });
}

init();
