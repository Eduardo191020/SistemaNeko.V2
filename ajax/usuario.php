<?php
ob_start();
if (strlen(session_id()) < 1){ session_start(); }

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
$mantener_clave  = isset($_POST["mantener_clave"])  ? $_POST["mantener_clave"]                  : "0"; // "1" = no cambiar

/* === NUEVOS CAMPOS PARA ROLES === */
$id_rol          = isset($_POST["id_rol"])          ? limpiarCadena($_POST["id_rol"])          : "";  // select rol
$modo_permisos   = isset($_POST["modo_permisos"])   ? limpiarCadena($_POST["modo_permisos"])   : "";  // "rol" | "personalizado" | ""

/* Helper: obtiene permisos desde el rol si está disponible en el modelo.
   Debes tener en BD: tabla rol_permiso(id_rol, idpermiso).
   Implementación esperada en Usuario.php:

   public function permisosPorRol($id_rol){
     $sql = "SELECT idpermiso FROM rol_permiso WHERE id_rol = '$id_rol'";
     return ejecutarConsulta($sql); // retorna resultset
   }
*/
function _obtenerPermisosDesdeRol(Usuario $usuarioModel, $idRol){
  $ids = array();
  if (empty($idRol)) return $ids;

  if (method_exists($usuarioModel, 'permisosPorRol')) {
    $rs = $usuarioModel->permisosPorRol($idRol);
    if ($rs) {
      while ($row = $rs->fetch_object()) {
        $ids[] = (int)$row->idpermiso;
      }
    }
  }
  return $ids;
}

switch ($_GET["op"]) {

  /* ============================================================
   * GUARDAR / EDITAR
   * ============================================================ */
  case 'guardaryeditar':
    if (!isset($_SESSION["nombre"])) {
      header("Location: ../vistas/login.html");
    } else {
      if ($_SESSION['acceso'] == 1) {

        // -------- Validaciones de negocio previas --------
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

        // Validación de contraseña
        if (empty($idusuario)) {
          if ($clave === "" || strlen($clave) < 10 || strlen($clave) > 64) {
            echo "Error: La contraseña debe tener entre 10 y 64 caracteres.";
            break;
          }
        } else {
          if ($mantener_clave !== "1" && $clave !== "") {
            if (strlen($clave) < 10 || strlen($clave) > 64) {
              echo "Error: La contraseña debe tener entre 10 y 64 caracteres.";
              break;
            }
          }
        }

        /* ============== LÓGICA DE PERMISOS ==============
           prioridad:
           1) Si $modo_permisos == 'rol'  -> permisos vienen del rol
           2) Si $modo_permisos == 'personalizado' -> usar $_POST['permiso']
           3) Si $modo_permisos vacío:
              - si hay $_POST['permiso'] => personalizado
              - si no hay y hay $id_rol  => del rol
        */
        $permisosAUsar = array();
        $permPost      = isset($_POST['permiso']) ? $_POST['permiso'] : array();

        if ($modo_permisos === 'rol') {
          $permisosAUsar = _obtenerPermisosDesdeRol($usuario, $id_rol);
        } elseif ($modo_permisos === 'personalizado') {
          $permisosAUsar = $permPost;
        } else {
          if (!empty($permPost)) {
            $permisosAUsar = $permPost;
          } else {
            $permisosAUsar = _obtenerPermisosDesdeRol($usuario, $id_rol);
          }
        }

        if (!is_array($permisosAUsar) || count($permisosAUsar) === 0) {
          echo "Error: Debes seleccionar al menos un permiso (o define permisos en el Rol).";
          break;
        }

        // Si no envías cargo, puedes inferir por permisos (opcional)
        if (empty($cargo)) {
          if (in_array(5, $permisosAUsar)) {
            $cargo = 'Administrador';
          } elseif (in_array(2, $permisosAUsar) && in_array(3, $permisosAUsar)) {
            $cargo = 'Almacenero';
          } elseif (in_array(4, $permisosAUsar)) {
            $cargo = 'Vendedor';
          } else {
            $cargo = 'Empleado';
          }
        }

        // --------- Imagen ---------
        if (!file_exists($_FILES['imagen']['tmp_name']) || !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
          $imagen = $_POST["imagenactual"] ?? $imagen;
        } else {
          $ext = explode(".", $_FILES["imagen"]["name"]);
          if ($_FILES['imagen']['type'] == "image/jpg" || $_FILES['imagen']['type'] == "image/jpeg" || $_FILES['imagen']['type'] == "image/png") {
            $imagen = round(microtime(true)) . '.' . end($ext);
            move_uploaded_file($_FILES["imagen"]["tmp_name"], "../files/usuarios/" . $imagen);
          }
        }

        // --------- Insertar o Editar ---------
        if (empty($idusuario)) {
          $clavehash = hash("SHA256", $clave);

          // Si tu modelo Usuario->insertar no contempla id_rol aún,
          // crea una versión insertarConRol o actualiza el método existente.
          if (method_exists($usuario, 'insertarConRol')) {
            $rspta = $usuario->insertarConRol(
              $nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,
              $cargo,$clavehash,$imagen,$permisosAUsar,$id_rol
            );
          } else {
            $rspta = $usuario->insertar(
              $nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,
              $cargo,$clavehash,$imagen,$permisosAUsar
            );
            // y si tienes una columna id_rol en usuario, haz un update aparte:
            if (!empty($id_rol) && method_exists($usuario,'actualizarRol')){
              $usuario->actualizarRol($rspta ? $rspta : 0, $id_rol);
            }
          }

          echo $rspta ? "Usuario registrado exitosamente." : "No se pudieron registrar todos los datos del usuario";

        } else {
          // EDIT
          if ($mantener_clave === "1" || $clave === "") {
            if (method_exists($usuario, 'editarSinClaveConRol')) {
              $rspta = $usuario->editarSinClaveConRol($idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$imagen,$permisosAUsar,$id_rol);
            } elseif (method_exists($usuario, 'editarSinClave')) {
              $rspta = $usuario->editarSinClave($idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$imagen,$permisosAUsar);
              if (!empty($id_rol) && method_exists($usuario,'actualizarRol')){
                $usuario->actualizarRol($idusuario, $id_rol);
              }
            } else {
              // Fallback: obtener hash actual y llamar editar(...)
              $hashActual = "";
              if (method_exists($usuario, 'obtenerHash')) {
                $hashActual = $usuario->obtenerHash($idusuario);
              }
              if (!$hashActual) {
                $fila = $usuario->mostrar($idusuario);
                if (is_array($fila) && isset($fila['clave'])) { $hashActual = $fila['clave']; }
                elseif (is_object($fila) && isset($fila->clave)) { $hashActual = $fila->clave; }
              }
              $rspta = $usuario->editar($idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$hashActual,$imagen,$permisosAUsar);
              if (!empty($id_rol) && method_exists($usuario,'actualizarRol')){
                $usuario->actualizarRol($idusuario, $id_rol);
              }
            }

            echo $rspta ? "Usuario actualizado correctamente" : "Usuario no se pudo actualizar";

          } else {
            // Cambiar clave
            $clavehash = hash("SHA256", $clave);
            if (method_exists($usuario, 'editarConRol')) {
              $rspta = $usuario->editarConRol($idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clavehash,$imagen,$permisosAUsar,$id_rol);
            } else {
              $rspta = $usuario->editar($idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clavehash,$imagen,$permisosAUsar);
              if (!empty($id_rol) && method_exists($usuario,'actualizarRol')){
                $usuario->actualizarRol($idusuario, $id_rol);
              }
            }
            echo $rspta ? "Usuario actualizado correctamente" : "Usuario no se pudo actualizar";
          }
        }

      } else {
        require 'noacceso.php';
      }
    }
  break;

  /* ============================================================
   * DESACTIVAR
   * ============================================================ */
  case 'desactivar':
    if (!isset($_SESSION["nombre"])) {
      header("Location: ../vistas/login.html");
    } else {
      if ($_SESSION['acceso']==1) {
        $rspta=$usuario->desactivar($idusuario);
        echo $rspta ? "Usuario Desactivado" : "Usuario no se puede desactivar";
      } else { require 'noacceso.php'; }
    }
  break;

  /* ============================================================
   * ACTIVAR
   * ============================================================ */
  case 'activar':
    if (!isset($_SESSION["nombre"])) {
      header("Location: ../vistas/login.html");
    } else {
      if ($_SESSION['acceso']==1) {
        $rspta=$usuario->activar($idusuario);
        echo $rspta ? "Usuario activado" : "Usuario no se puede activar";
      } else { require 'noacceso.php'; }
    }
  break;

  /* ============================================================
   * MOSTRAR (para edición)
   * ============================================================ */
  case 'mostrar':
    if (!isset($_SESSION["nombre"])) {
      header("Location: ../vistas/login.html");
    } else {
      if ($_SESSION['acceso']==1) {
        $rspta=$usuario->mostrar($idusuario);
        echo json_encode($rspta);
      } else { require 'noacceso.php'; }
    }
  break;

  /* ============================================================
   * LISTAR
   * ============================================================ */
  case 'listar':
    if (!isset($_SESSION["nombre"])) {
      header("Location: ../vistas/login.html");
    } else {
      if ($_SESSION['acceso']==1) {
        $rspta=$usuario->listar();
        $data= Array();
        while ($reg=$rspta->fetch_object()){
          $data[] = array(
            "0" => ($reg->condicion)
                    ? '<button class="btn btn-warning" onclick="mostrar('.$reg->idusuario.')"><i class="fa fa-pencil"></i></button>'
                      .' <button class="btn btn-danger" onclick="desactivar('.$reg->idusuario.')"><i class="fa fa-close"></i></button>'
                    : '<button class="btn btn-warning" onclick="mostrar('.$reg->idusuario.')"><i class="fa fa-pencil"></i></button>'
                      .' <button class="btn btn-primary" onclick="activar('.$reg->idusuario.')"><i class="fa fa-check"></i></button>',
            "1" => $reg->nombre,
            "2" => $reg->tipo_documento,
            "3" => $reg->num_documento,
            "4" => $reg->telefono,
            "5" => $reg->email,
            "6" => $reg->cargo,
            "7" => "<img src='../files/usuarios/".$reg->imagen."' height='50' width='50'>",
            "8" => ($reg->condicion) ? '<span class="label bg-green">Activado</span>'
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
      } else { require 'noacceso.php'; }
    }
  break;

  /* ============================================================
   * PERMISOS (checkboxes - modo personalizado)
   * ============================================================ */
  case 'permisos':
    require_once "../modelos/Permiso.php";
    $permiso = new Permiso();
    $rspta   = $permiso->listar();

    $id = $_GET['id'];
    $marcados = $usuario->listarmarcados($id);
    $valores = array();
    while ($per = $marcados->fetch_object()){ array_push($valores, (int)$per->idpermiso); }

    while ($reg = $rspta->fetch_object()){
      $sw = in_array((int)$reg->idpermiso, $valores) ? 'checked' : '';
      echo '<li><label style="font-weight:normal">'
          . '<input type="checkbox" '.$sw.' name="permiso[]" value="'.$reg->idpermiso.'"> '
          . htmlspecialchars($reg->nombre, ENT_QUOTES, 'UTF-8')
          . '</label></li>';
    }
  break;
case 'roles':
  require_once "../modelos/Rol.php";
  $rol = new Rol();
  $rspta = $rol->listarActivos(); // usa tu método existente

  echo '<option value="">Seleccione...</option>';
  while ($reg = $rspta->fetch_object()) {
    echo '<option value="'.$reg->id_rol.'">'.$reg->nombre.'</option>';
  }
break;

  /* ============================================================
   * NUEVO: PERMISOS POR ROL (para auto-check en el front)
   * GET: op=permisos_por_rol&id_rol=#
   * ============================================================ */
  case 'permisos_por_rol':
    $idRol = isset($_GET['id_rol']) ? $_GET['id_rol'] : '';
    $ids = _obtenerPermisosDesdeRol($usuario, $idRol);
    echo json_encode($ids);
  break;

  /* ============================================================
   * VERIFICAR LOGIN (legacy)
   * ============================================================ */
  case 'verificar':
    $logina = $_POST['logina'];
    $clavea = $_POST['clavea'];
    $clavehash = hash("SHA256", $clavea);

    $rspta = $usuario->verificar($logina, $clavehash);
    $fetch = $rspta->fetch_object();

    if (isset($fetch)) {
      $_SESSION['idusuario'] = $fetch->idusuario;
      $_SESSION['nombre']    = $fetch->nombre;
      $_SESSION['imagen']    = $fetch->imagen;
      $_SESSION['email']     = $fetch->email;

      $marcados = $usuario->listarmarcados($fetch->idusuario);
      $valores = array();
      while ($per = $marcados->fetch_object()){ array_push($valores, (int)$per->idpermiso); }

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
   * SALIR
   * ============================================================ */
  case 'salir':
    $_SESSION = array();
    if (ini_get("session.use_cookies")) {
      $params = session_get_cookie_params();
      setcookie(session_name(), '', time() - 42000, $params["path"], $params["domain"], $params["secure"], $params["httponly"]);
    }
    session_destroy();
    header("Location: ../index.php");
    exit();
  break;
}

ob_end_flush();
?>
