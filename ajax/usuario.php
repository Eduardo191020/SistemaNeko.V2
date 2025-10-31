<?php
ob_start();
if (strlen(session_id()) < 1){
	session_start();
}
require_once "../modelos/Usuario.php";

$usuario = new Usuario();

$idusuario       = isset($_POST["idusuario"])       ? limpiarCadena($_POST["idusuario"])       : "";
$nombre          = isset($_POST["nombre"])          ? limpiarCadena($_POST["nombre"])          : "";
$tipo_documento  = isset($_POST["tipo_documento"])  ? limpiarCadena($_POST["tipo_documento"])  : "";
$num_documento   = isset($_POST["num_documento"])   ? limpiarCadena($_POST["num_documento"])   : "";
$direccion       = isset($_POST["direccion"])       ? limpiarCadena($_POST["direccion"])       : "";
$telefono        = isset($_POST["telefono"])        ? limpiarCadena($_POST["telefono"])        : "";
$email           = isset($_POST["email"])           ? limpiarCadena($_POST["email"])           : "";
$cargo           = isset($_POST["cargo"])           ? limpiarCadena($_POST["cargo"])           : "";
$clave           = isset($_POST["clave"])           ? limpiarCadena($_POST["clave"])           : "";
$imagen          = isset($_POST["imagen"])          ? limpiarCadena($_POST["imagen"])          : "";

/* ===== NUEVOS CAMPOS PARA ROLES/PERMISOS ===== */
$id_rol          = isset($_POST["id_rol"])          ? limpiarCadena($_POST["id_rol"])          : ""; // value del <select>
$modo_permisos   = isset($_POST["modo_permisos"])   ? limpiarCadena($_POST["modo_permisos"])   : ""; // 'rol' | 'personalizado' | ''
$mantener_clave  = isset($_POST["mantener_clave"])  ? limpiarCadena($_POST["mantener_clave"])  : "0"; // "1" = no cambiar

switch ($_GET["op"]){

/* ============================================================
   GUARDAR / EDITAR
   - Respetamos tu flujo original.
   - Ajuste: si $modo_permisos === 'rol' NO exigimos permiso[].
   - Propagamos $id_rol, $modo_permisos, $mantener_clave al modelo.
   ============================================================ */
case 'guardaryeditar':
  if (!isset($_SESSION["nombre"])) {
    header("Location: ../vistas/login.html");
  } else {
    if ($_SESSION['acceso']==1) {

      // ===== Validaciones previas =====
      if ($usuario->verificarEmailExiste($email, $idusuario)) {
        echo "Error: Este correo electrónico ya está registrado por otro usuario.";
        break;
      }

      if ($usuario->verificarDocumentoExiste($tipo_documento, $num_documento, $idusuario)) {
        echo "Error: Este documento ya está registrado por otro usuario.";
        break;
      }

      if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Error: El formato del correo electrónico no es válido.";
        break;
      }

      // Solo exigimos permisos marcados si NO es modo_permisos='rol'
      if ($modo_permisos !== 'rol') {
        if (!isset($_POST['permiso']) || !is_array($_POST['permiso']) || count($_POST['permiso']) == 0) {
          echo "Error: Debes seleccionar al menos un permiso para el usuario.";
          break;
        }
      }

      // ===== Imagen =====
      if (!file_exists($_FILES['imagen']['tmp_name']) || !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
        $imagen = $_POST["imagenactual"] ?? $imagen;
      } else {
        $ext = explode(".", $_FILES["imagen"]["name"]);
        if (in_array($_FILES['imagen']['type'], ["image/jpg","image/jpeg","image/png"])) {
          $imagen = round(microtime(true)) . '.' . end($ext);
          move_uploaded_file($_FILES["imagen"]["tmp_name"], "../files/usuarios/" . $imagen);
        }
      }

      // ===== Password condicional =====
      $clavehash = null;
      if (empty($idusuario)) {
        // Crear: clave OBLIGATORIA
        if ($clave === "" || strlen($clave) < 10 || strlen($clave) > 64) {
          echo "Error: La contraseña debe tener entre 10 y 64 caracteres.";
          break;
        }
        $clavehash = hash("SHA256", $clave);
      } else {
        // Editar: clave OPCIONAL. Si viene vacía o mantener_clave=1 => conservar hash actual
        if ($mantener_clave === "1" || $clave === "") {
          $fila = $usuario->mostrar($idusuario);
          $hashActual = "";
          if (is_array($fila) && isset($fila['clave']))        { $hashActual = $fila['clave']; }
          elseif (is_object($fila) && isset($fila->clave))      { $hashActual = $fila->clave; }
          $clavehash = $hashActual;
        } else {
          if (strlen($clave) < 10 || strlen($clave) > 64) {
            echo "Error: La contraseña debe tener entre 10 y 64 caracteres.";
            break;
          }
          $clavehash = hash("SHA256", $clave);
        }
      }

      // Normalizamos permisos (si vienen)
      $permisos = isset($_POST['permiso']) && is_array($_POST['permiso']) ? $_POST['permiso'] : array();

      // ===== Insertar / Editar =====
      if (empty($idusuario)) {
        $rspta = $usuario->insertar(
          $nombre, $tipo_documento, $num_documento, $direccion, $telefono, $email,
          $cargo, $clavehash, $imagen, $permisos,
          $id_rol, $modo_permisos   // <<< NUEVOS
        );
        echo $rspta ? "Usuario registrado exitosamente. Puede iniciar sesión con su correo: $email"
                    : "No se pudieron registrar todos los datos del usuario";
      } else {
        $rspta = $usuario->editar(
          $idusuario, $nombre, $tipo_documento, $num_documento, $direccion, $telefono, $email,
          $cargo, $clavehash, $imagen, $permisos,
          $id_rol, $modo_permisos, $mantener_clave === "1"  // <<< NUEVOS
        );
        echo $rspta ? "Usuario actualizado correctamente" : "Usuario no se pudo actualizar";
      }

    } else {
      require 'noacceso.php';
    }
  }
break;

/* ============================================================
   Activar / Desactivar
   ============================================================ */
case 'desactivar':
	if (!isset($_SESSION["nombre"])) {
	  header("Location: ../vistas/login.html");
	} else {
		if ($_SESSION['acceso']==1) {
			$rspta = $usuario->desactivar($idusuario);
 			echo $rspta ? "Usuario Desactivado" : "Usuario no se puede desactivar";
		} else {
	  	require 'noacceso.php';
		}
	}
break;

case 'activar':
	if (!isset($_SESSION["nombre"])) {
	  header("Location: ../vistas/login.html");
	} else {
		if ($_SESSION['acceso']==1) {
			$rspta = $usuario->activar($idusuario);
 			echo $rspta ? "Usuario activado" : "Usuario no se puede activar";
		} else {
	  	require 'noacceso.php';
		}
	}
break;

/* ============================================================
   Mostrar un usuario (incluye id_rol desde el modelo)
   ============================================================ */
case 'mostrar':
	if (!isset($_SESSION["nombre"])) {
	  header("Location: ../vistas/login.html");
	} else {
		if ($_SESSION['acceso']==1) {
			$rspta = $usuario->mostrar($idusuario);
	 		echo json_encode($rspta);
		} else {
	  	require 'noacceso.php';
		}
	}
break;

/* ============================================================
   Listar usuarios (mantengo tus columnas originales)
   ============================================================ */
case 'listar':
	if (!isset($_SESSION["nombre"])) {
	  header("Location: ../vistas/login.html");
	} else {
		if ($_SESSION['acceso']==1) {
			$rspta = $usuario->listar();
	 		$data = array();

	 		while ($reg = $rspta->fetch_object()){
	 			$data[] = array(
	 				"0" => ($reg->condicion)
	 						? '<button class="btn btn-warning" onclick="mostrar('.$reg->idusuario.')"><i class="fa fa-pencil"></i></button>'.
	 						  ' <button class="btn btn-danger" onclick="desactivar('.$reg->idusuario.')"><i class="fa fa-close"></i></button>'
	 						: '<button class="btn btn-warning" onclick="mostrar('.$reg->idusuario.')"><i class="fa fa-pencil"></i></button>'.
	 						  ' <button class="btn btn-primary" onclick="activar('.$reg->idusuario.')"><i class="fa fa-check"></i></button>',
	 				"1" => $reg->nombre,
	 				"2" => $reg->tipo_documento,
	 				"3" => $reg->num_documento,
	 				"4" => $reg->telefono,
	 				"5" => $reg->email,
	 				// Mantenemos tu 'cargo' (texto mostrado). Si prefieres el nombre del rol del JOIN, usa $reg->nombre_rol
	 				"6" => $reg->cargo,
	 				"7" => "<img src='../files/usuarios/".$reg->imagen."' height='50px' width='50px' >",
	 				"8" => ($reg->condicion)
	 						? '<span class="label bg-green">Activado</span>'
	 						: '<span class="label bg-red">Desactivado</span>'
	 			);
	 		}
	 		$results = array(
	 			"sEcho"                => 1,
	 			"iTotalRecords"        => count($data),
	 			"iTotalDisplayRecords" => count($data),
	 			"aaData"               => $data
	 		);
	 		echo json_encode($results);
		} else {
	  	require 'noacceso.php';
		}
	}
break;

/* ============================================================
   Pintar permisos con los del usuario (tu lógica intacta)
   ============================================================ */
case 'permisos':
	require_once "../modelos/Permiso.php";
	$permiso = new Permiso();
	$rspta = $permiso->listar();

	$id = $_GET['id'];
	$marcados = $usuario->listarmarcados($id);
	$valores = array();

	while ($per = $marcados->fetch_object()){
		array_push($valores, $per->idpermiso);
	}

	while ($reg = $rspta->fetch_object()){
		$sw = in_array($reg->idpermiso, $valores) ? 'checked' : '';
		echo '<li><label style="font-weight:normal;"><input type="checkbox" '.$sw.' name="permiso[]" value="'.$reg->idpermiso.'"> '.$reg->nombre.'</label></li>';
	}
break;

/* ============================================================
   ✅ NUEVO: Cargar roles dinámicamente para el <select>
   ============================================================ */
case 'selectRol':
	if (!isset($_SESSION["nombre"])) {
		header("Location: ../vistas/login.html");
		exit;
	}
	if ($_SESSION['acceso']==1) {
		require_once "../modelos/Rol.php";
		$rol = new Rol();
		// Usa listarActivos() si existe; si no, cambia por listar()
		$rspta = method_exists($rol, 'listarActivos') ? $rol->listarActivos() : $rol->listar();

		echo '<option value="">Seleccione...</option>';
		while ($reg = $rspta->fetch_object()) {
			echo '<option value="'.$reg->id_rol.'">'.$reg->nombre.'</option>';
		}
	} else {
		echo '<option value="">Sin acceso</option>';
	}
break;

/* ============================================================
   ✅ NUEVO: Permisos por rol (para auto-tildar en el front)
   Respuesta: JSON array [idpermiso,...]
   ============================================================ */
case 'permisos_por_rol':
	if (!isset($_SESSION["nombre"])) {
		header("Location: ../vistas/login.html");
		exit;
	}
	if ($_SESSION['acceso']==1) {
		$id_rol_q = isset($_GET['id_rol']) ? intval($_GET['id_rol']) : 0;
		$ids = $usuario->permisos_por_rol($id_rol_q);
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode($ids);
	} else {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode([]);
	}
break;

/* ============================================================
   Login (tu flujo intacto). Nota: el modelo verifica por email.
   ============================================================ */
case 'verificar':
	$logina = $_POST['logina']; // aquí recibes email
	$clavea = $_POST['clavea'];

	$clavehash = hash("SHA256",$clavea);

	$rspta = $usuario->verificar($logina, $clavehash);
	$fetch = $rspta->fetch_object();

	if (isset($fetch))
	{
		$_SESSION['idusuario'] = $fetch->idusuario;
		$_SESSION['nombre']    = $fetch->nombre;
		$_SESSION['imagen']    = $fetch->imagen;
		$_SESSION['email']     = $fetch->email;

		// Permisos del usuario
		$marcados = $usuario->listarmarcados($fetch->idusuario);
		$valores  = array();
		while ($per = $marcados->fetch_object()){
			array_push($valores, $per->idpermiso);
		}

		in_array(1,$valores)?$_SESSION['escritorio']=1:$_SESSION['escritorio']=0;
		in_array(2,$valores)?$_SESSION['almacen']=1:$_SESSION['almacen']=0;
		in_array(3,$valores)?$_SESSION['compras']=1:$_SESSION['compras']=0;
		in_array(4,$valores)?$_SESSION['ventas']=1:$_SESSION['ventas']=0;
		in_array(5,$valores)?$_SESSION['acceso']=1:$_SESSION['acceso']=0;
		in_array(6,$valores)?$_SESSION['consultac']=1:$_SESSION['consultac']=0;
		in_array(7,$valores)?$_SESSION['consultav']=1:$_SESSION['consultav']=0;
	}
	echo json_encode($fetch);
break;

/* ============================================================
   Salir
   ============================================================ */
case 'salir':
	$_SESSION = array();
	if (ini_get("session.use_cookies")) {
		$params = session_get_cookie_params();
		setcookie(session_name(), '', time() - 42000,
			$params["path"], $params["domain"],
			$params["secure"], $params["httponly"]
		);
	}
	session_destroy();
	header("Location: ../index.php");
	exit();
break;
}
ob_end_flush();
?>
