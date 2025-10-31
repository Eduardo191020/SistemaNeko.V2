<?php 
ob_start();
if (strlen(session_id()) < 1){
	session_start();//Validamos si existe o no la sesión
}
if (!isset($_SESSION["nombre"]))
{
  header("Location: ../vistas/login.html");//Validamos el acceso solo a los usuarios logueados al sistema.
}
else
{
//Validamos el acceso solo al usuario logueado y autorizado.
if ($_SESSION['ventas']==1 || $_SESSION['compras']==1)
{
require_once "../modelos/Persona.php";

$persona=new Persona();

$idpersona=isset($_POST["idpersona"])? limpiarCadena($_POST["idpersona"]):"";
$tipo_persona=isset($_POST["tipo_persona"])? limpiarCadena($_POST["tipo_persona"]):"";
$nombre=isset($_POST["nombre"])? limpiarCadena($_POST["nombre"]):"";
$tipo_documento=isset($_POST["tipo_documento"])? limpiarCadena($_POST["tipo_documento"]):"";
$num_documento=isset($_POST["num_documento"])? limpiarCadena($_POST["num_documento"]):"";
$direccion=isset($_POST["direccion"])? limpiarCadena($_POST["direccion"]):"";
$telefono=isset($_POST["telefono"])? limpiarCadena($_POST["telefono"]):"";
$email=isset($_POST["email"])? limpiarCadena($_POST["email"]):"";

switch ($_GET["op"]){
	case 'consultaDoc':
  // Entrada: POST { tipo: 'DNI'|'RUC', numero: '...' }
  header('Content-Type: application/json; charset=utf-8');

  $tipo   = isset($_POST['tipo'])   ? $_POST['tipo']   : '';
  $numero = isset($_POST['numero']) ? preg_replace('/\D/','', $_POST['numero']) : '';

  if ($tipo !== 'DNI' && $tipo !== 'RUC') {
    echo json_encode(['ok'=>false, 'msg'=>'Tipo inválido']); break;
  }
  if (($tipo==='DNI' && strlen($numero)!==8) || ($tipo==='RUC' && strlen($numero)!==11)) {
    echo json_encode(['ok'=>false, 'msg'=>'Longitud inválida']); break;
  }

  // ==== Lógica de consulta ====
  // Reutiliza tus funciones existentes (las mismas que usas en Proveedores).
  // Aquí muestro un ejemplo genérico. Ajusta a tu implementación real:

  try {
    require_once "../modelos/Persona.php";
    $p = new Persona();

    if ($tipo === 'DNI') {
      // Debe devolver al menos 'nombre' desde RENIEC
      $info = $p->consultaRENIEC($numero); // <-- implementado en tu modelo o servicio
      if ($info && !empty($info['nombre'])) {
        echo json_encode(['ok'=>true, 'nombre'=>$info['nombre'], 'direccion'=> isset($info['direccion'])?$info['direccion']:'' ]);
      } else {
        echo json_encode(['ok'=>false, 'msg'=>'DNI no encontrado']);
      }
    } else { // RUC
      // Debe devolver 'razon_social' y 'direccion'
      $info = $p->consultaSUNAT($numero); // <-- igual que en proveedores
      if ($info && !empty($info['razon_social'])) {
        echo json_encode(['ok'=>true, 'nombre'=>$info['razon_social'], 'direccion'=> isset($info['direccion'])?$info['direccion']:'' ]);
      } else {
        echo json_encode(['ok'=>false, 'msg'=>'RUC no encontrado']);
      }
    }
  } catch (Exception $ex) {
    echo json_encode(['ok'=>false, 'msg'=>'Error en servicio']);
  }
break;

	case 'guardaryeditar':
		if (empty($idpersona)){
			$rspta=$persona->insertar($tipo_persona,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email);
			echo $rspta ? "Persona registrada" : "Persona no se pudo registrar";
		}
		else {
			$rspta=$persona->editar($idpersona,$tipo_persona,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email);
			echo $rspta ? "Persona actualizada" : "Persona no se pudo actualizar";
		}
	break;

	case 'eliminar':
		$rspta=$persona->eliminar($idpersona);
 		echo $rspta ? "Persona eliminada" : "Persona no se puede eliminar";
	break;

	case 'mostrar':
		$rspta=$persona->mostrar($idpersona);
 		//Codificar el resultado utilizando json
 		echo json_encode($rspta);
	break;

	case 'listarp':
		$rspta=$persona->listarp();
 		//Vamos a declarar un array
 		$data= Array();

 		while ($reg=$rspta->fetch_object()){
 			$data[]=array(
 				"0"=>'<button class="btn btn-warning" onclick="mostrar('.$reg->idpersona.')"><i class="fa fa-pencil"></i></button>'.
 					' <button class="btn btn-danger" onclick="eliminar('.$reg->idpersona.')"><i class="fa fa-trash"></i></button>',
 				"1"=>$reg->nombre,
 				"2"=>$reg->tipo_documento,
 				"3"=>$reg->num_documento,
 				"4"=>$reg->telefono,
 				"5"=>$reg->email
 				);
 		}
 		$results = array(
 			"sEcho"=>1, //Información para el datatables
 			"iTotalRecords"=>count($data), //enviamos el total registros al datatable
 			"iTotalDisplayRecords"=>count($data), //enviamos el total registros a visualizar
 			"aaData"=>$data);
 		echo json_encode($results);

	break;

	case 'listarc':
		$rspta=$persona->listarc();
 		//Vamos a declarar un array
 		$data= Array();

 		while ($reg=$rspta->fetch_object()){
 			$data[]=array(
 				"0"=>'<button class="btn btn-warning" onclick="mostrar('.$reg->idpersona.')"><i class="fa fa-pencil"></i></button>'.
 					' <button class="btn btn-danger" onclick="eliminar('.$reg->idpersona.')"><i class="fa fa-trash"></i></button>',
 				"1"=>$reg->nombre,
 				"2"=>$reg->tipo_documento,
 				"3"=>$reg->num_documento,
 				"4"=>$reg->telefono,
 				"5"=>$reg->email
 				);
 		}
 		$results = array(
 			"sEcho"=>1, //Información para el datatables
 			"iTotalRecords"=>count($data), //enviamos el total registros al datatable
 			"iTotalDisplayRecords"=>count($data), //enviamos el total registros a visualizar
 			"aaData"=>$data);
 		echo json_encode($results);

	break;


}
//Fin de las validaciones de acceso
}
else
{
  require 'noacceso.php';
}
}
ob_end_flush();
?>