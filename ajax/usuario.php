<?php
ob_start();
if (strlen(session_id()) < 1){
	session_start();
}
require_once "../modelos/Usuario.php";

$usuario=new Usuario();

$idusuario=isset($_POST["idusuario"])? limpiarCadena($_POST["idusuario"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$tipo_documento=isset($_POST["tipo_documento"])? limpiarCadena($_POST["tipo_documento"]):"";
$num_documento=isset($_POST["num_documento"])? limpiarCadena($_POST["num_documento"]):"";
$direccion=isset($_POST["direccion"])? limpiarCadena($_POST["direccion"]):"";
$telefono=isset($_POST["telefono"])? limpiarCadena($_POST["telefono"]):"";
$email=isset($_POST["email"])? limpiarCadena($_POST["email"]):"";
$cargo=isset($_POST["cargo"])? limpiarCadena($_POST["cargo"]):"";
$clave=isset($_POST["clave"])? limpiarCadena($_POST["clave"]):"";
$imagen=isset($_POST["imagen"])? limpiarCadena($_POST["imagen"]):"";

switch ($_GET["op"]){
	case 'guardaryeditar':
		if (!isset($_SESSION["nombre"]))
		{
		  header("Location: ../vistas/login.html");
		}
		else
		{
			if ($_SESSION['acceso']==1)
			{
				// VALIDACIONES
				
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
				
				if (strlen($clave) < 10 || strlen($clave) > 64) {
					echo "Error: La contraseña debe tener entre 10 y 64 caracteres.";
					break;
				}
				
				if (!isset($_POST['permiso']) || count($_POST['permiso']) == 0) {
					echo "Error: Debes seleccionar al menos un permiso para el usuario.";
					break;
				}
				
				// Procesar imagen
				if (!file_exists($_FILES['imagen']['tmp_name']) || !is_uploaded_file($_FILES['imagen']['tmp_name']))
				{
					$imagen=$_POST["imagenactual"];
				}
				else 
				{
					$ext = explode(".", $_FILES["imagen"]["name"]);
					if ($_FILES['imagen']['type'] == "image/jpg" || $_FILES['imagen']['type'] == "image/jpeg" || $_FILES['imagen']['type'] == "image/png")
					{
						$imagen = round(microtime(true)) . '.' . end($ext);
						move_uploaded_file($_FILES["imagen"]["tmp_name"], "../files/usuarios/" . $imagen);
					}
				}
				
				$clavehash=hash("SHA256",$clave);

				if (empty($idusuario)){
					$rspta=$usuario->insertar($nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clavehash,$imagen,$_POST['permiso']);
					echo $rspta ? "Usuario registrado exitosamente. Puede iniciar sesión con su correo: $email" : "No se pudieron registrar todos los datos del usuario";
				}
				else {
					$rspta=$usuario->editar($idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clavehash,$imagen,$_POST['permiso']);
					echo $rspta ? "Usuario actualizado correctamente" : "Usuario no se pudo actualizar";
				}
			}
			else
			{
		  	require 'noacceso.php';
			}
		}		
	break;

	case 'desactivar':
		if (!isset($_SESSION["nombre"]))
		{
		  header("Location: ../vistas/login.html");
		}
		else
		{
			if ($_SESSION['acceso']==1)
			{
				$rspta=$usuario->desactivar($idusuario);
 				echo $rspta ? "Usuario Desactivado" : "Usuario no se puede desactivar";
			}
			else
			{
		  	require 'noacceso.php';
			}
		}		
	break;

	case 'activar':
		if (!isset($_SESSION["nombre"]))
		{
		  header("Location: ../vistas/login.html");
		}
		else
		{
			if ($_SESSION['acceso']==1)
			{
				$rspta=$usuario->activar($idusuario);
 				echo $rspta ? "Usuario activado" : "Usuario no se puede activar";
			}
			else
			{
		  	require 'noacceso.php';
			}
		}		
	break;

	case 'mostrar':
		if (!isset($_SESSION["nombre"]))
		{
		  header("Location: ../vistas/login.html");
		}
		else
		{
			if ($_SESSION['acceso']==1)
			{
				$rspta=$usuario->mostrar($idusuario);
		 		echo json_encode($rspta);
			}
			else
			{
		  	require 'noacceso.php';
			}
		}		
	break;

	case 'listar':
		if (!isset($_SESSION["nombre"]))
		{
		  header("Location: ../vistas/login.html");
		}
		else
		{
			if ($_SESSION['acceso']==1)
			{
				$rspta=$usuario->listar();
		 		$data= Array();

		 		while ($reg=$rspta->fetch_object()){
		 			$data[]=array(
		 				"0"=>($reg->condicion)?'<button class="btn btn-warning" onclick="mostrar('.$reg->idusuario.')"><i class="fa fa-pencil"></i></button>'.
		 					' <button class="btn btn-danger" onclick="desactivar('.$reg->idusuario.')"><i class="fa fa-close"></i></button>':
		 					'<button class="btn btn-warning" onclick="mostrar('.$reg->idusuario.')"><i class="fa fa-pencil"></i></button>'.
		 					' <button class="btn btn-primary" onclick="activar('.$reg->idusuario.')"><i class="fa fa-check"></i></button>',
		 				"1"=>$reg->nombre,
		 				"2"=>$reg->tipo_documento,
		 				"3"=>$reg->num_documento,
		 				"4"=>$reg->telefono,
		 				"5"=>$reg->email,
		 				"6"=>$reg->cargo,
		 				"7"=>"<img src='../files/usuarios/".$reg->imagen."' height='50px' width='50px' >",
		 				"8"=>($reg->condicion)?'<span class="label bg-green">Activado</span>':
		 				'<span class="label bg-red">Desactivado</span>'
		 				);
		 		}
		 		$results = array(
		 			"sEcho"=>1,
		 			"iTotalRecords"=>count($data),
		 			"iTotalDisplayRecords"=>count($data),
		 			"aaData"=>$data);
		 		echo json_encode($results);
			}
			else
			{
		  	require 'noacceso.php';
			}
		}
	break;

	case 'permisos':
		require_once "../modelos/Permiso.php";
		$permiso = new Permiso();
		$rspta = $permiso->listar();

		$id=$_GET['id'];
		$marcados = $usuario->listarmarcados($id);
		$valores=array();

		while ($per = $marcados->fetch_object())
			{
				array_push($valores, $per->idpermiso);
			}

		while ($reg = $rspta->fetch_object())
				{
					$sw=in_array($reg->idpermiso,$valores)?'checked':'';
					echo '<li> <input type="checkbox" '.$sw.'  name="permiso[]" value="'.$reg->idpermiso.'"> '.$reg->nombre.'</li>';
				}
	break;

	// ========================================
	// ✅ CASO CRÍTICO: Cargar roles dinámicamente
	// ========================================
	case 'selectRol':
		if (!isset($_SESSION["nombre"])) {
			header("Location: ../vistas/login.html");
			exit;
		}
		
		if ($_SESSION['acceso']==1) {
			require_once "../modelos/Rol.php";
			$rol = new Rol();
			$rspta = $rol->listarActivos();

			echo '<option value="">Seleccione...</option>';
			while ($reg = $rspta->fetch_object()) {
				echo '<option value="'.$reg->id_rol.'">'.$reg->nombre.'</option>';
			}
		} else {
			echo '<option value="">Sin acceso</option>';
		}
	break;

	case 'verificar':
		$logina=$_POST['logina'];
	    $clavea=$_POST['clavea'];

	    $clavehash=hash("SHA256",$clavea);

		$rspta=$usuario->verificar($logina, $clavehash);

		$fetch=$rspta->fetch_object();

		if (isset($fetch))
	    {
	        $_SESSION['idusuario']=$fetch->idusuario;
	        $_SESSION['nombre']=$fetch->nombre;
	        $_SESSION['imagen']=$fetch->imagen;
	        $_SESSION['email']=$fetch->email;

	        $marcados = $usuario->listarmarcados($fetch->idusuario);

			$valores=array();

			while ($per = $marcados->fetch_object())
				{
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