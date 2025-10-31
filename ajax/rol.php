<?php
ob_start();
if (strlen(session_id()) < 1){ session_start(); }

// ¿Usuario logueado?
if (!isset($_SESSION["nombre"])) {
  header("Location: ../vistas/login.html");
} else {

  // Permiso para acceder a este módulo (ajusta el índice según tu sistema)
  if ($_SESSION['acceso']==1){

    require_once "../modelos/Rol.php";
    $rol = new Rol();

    $idrol  = isset($_POST["idrol"])  ? limpiarCadena($_POST["idrol"])  : "";
    $nombre = isset($_POST["nombre"]) ? limpiarCadena($_POST["nombre"]) : "";

    switch ($_GET["op"]) {

      case 'guardaryeditar':
        if (empty($idrol)) {
          $rspta = $rol->insertar($nombre);
          echo $rspta ? "Rol registrado" : "No se pudo registrar (¿nombre duplicado?)";
        } else {
          $rspta = $rol->editar($idrol,$nombre);
          echo $rspta ? "Rol actualizado" : "No se pudo actualizar (¿nombre duplicado?)";
        }
      break;

      case 'desactivar':
        $rspta = $rol->desactivar($idrol);
        echo $rspta ? "Rol desactivado" : "No se pudo desactivar";
      break;

      case 'activar':
        $rspta = $rol->activar($idrol);
        echo $rspta ? "Rol activado" : "No se pudo activar";
      break;

      case 'mostrar':
        $rspta = $rol->mostrar($idrol);
        echo json_encode($rspta);
      break;

      case 'listar':
        $rspta = $rol->listar();
        $data = Array();

        while ($reg = $rspta->fetch_object()){

          $btns = ($reg->estado)
            ? '<button class="btn btn-warning btn-sm" onclick="mostrar('.$reg->id_rol.')"><i class="fa fa-pencil"></i></button>
               <button class="btn btn-danger btn-sm" onclick="desactivar('.$reg->id_rol.')"><i class="fa fa-close"></i></button>'
            : '<button class="btn btn-warning btn-sm" onclick="mostrar('.$reg->id_rol.')"><i class="fa fa-pencil"></i></button>
               <button class="btn btn-success btn-sm" onclick="activar('.$reg->id_rol.')"><i class="fa fa-check"></i></button>';

          $estado = $reg->estado
            ? '<span class="label bg-green">Activo</span>'
            : '<span class="label bg-red">Inactivo</span>';

          $data[] = array(
            "0"=>$btns,
            "1"=>$reg->id_rol,
            "2"=>$reg->nombre,
            "3"=>$estado,
            "4"=>$reg->creado_en
          );
        }

        $results = array(
          "sEcho"=>1,
          "iTotalRecords"=>count($data),
          "iTotalDisplayRecords"=>count($data),
          "aaData"=>$data
        );
        echo json_encode($results);
      break;

      // Para llenar combos (solo roles activos)
      case 'selectRol':
        $rspta = $rol->listarActivos();
        while ($reg = $rspta->fetch_object()){
          echo '<option value="'.$reg->id_rol.'">'.$reg->nombre.'</option>';
        }
      break;
    }

  } else {
    require 'noacceso.php';
  }
}
ob_end_flush();
?>

