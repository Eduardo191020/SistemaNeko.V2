<?php
/**
 * ajax/articulo.php – soporte precio_compra + miniaturas + columnas alineadas (0..8)
 */
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

// Bloqueo si no hay sesión
if (!isset($_SESSION["nombre"])) {
  header("Location: ../vistas/login.html");
  exit();
}

// Permiso de módulo
if (!isset($_SESSION['almacen']) || (int)$_SESSION['almacen'] !== 1) {
  require 'noacceso.php';
  ob_end_flush();
  exit();
}

require_once "../modelos/Articulo.php";
$articulo = new Articulo();

// --------- INPUTS ----------
$idarticulo     = isset($_POST["idarticulo"])     ? limpiarCadena($_POST["idarticulo"])     : "";
$idcategoria    = isset($_POST["idcategoria"])    ? limpiarCadena($_POST["idcategoria"])    : "";
$codigo         = isset($_POST["codigo"])         ? limpiarCadena($_POST["codigo"])         : "";
$nombre         = isset($_POST["nombre"])         ? limpiarCadena($_POST["nombre"])         : "";
$stock          = isset($_POST["stock"])          ? limpiarCadena($_POST["stock"])          : "0";
$precio_compra  = isset($_POST["precio_compra"])  ? limpiarCadena($_POST["precio_compra"])  : "0";
$precio_venta   = isset($_POST["precio_venta"])   ? limpiarCadena($_POST["precio_venta"])   : "0";
$descripcion    = isset($_POST["descripcion"])    ? limpiarCadena($_POST["descripcion"])    : "";
$imagen         = isset($_POST["imagen"])         ? limpiarCadena($_POST["imagen"])         : "";

$op = isset($_GET["op"]) ? $_GET["op"] : '';

switch ($op) {

  /* ======================= CREAR / EDITAR ======================= */
  case 'guardaryeditar':

    // Manejo de imagen
    if (!file_exists($_FILES['imagen']['tmp_name']) || !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
      $imagen = $_POST["imagenactual"]; // conservar
    } else {
      // Validar mime real
      $mime = @mime_content_type($_FILES["imagen"]["tmp_name"]);
      $permitidos = ["image/jpg","image/jpeg","image/png"];
      if (in_array($mime, $permitidos, true)) {
        $ext = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
        $imagen = 'art_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.' . $ext;
        move_uploaded_file($_FILES["imagen"]["tmp_name"], "../files/articulos/" . $imagen);
      } else {
        // Tipo no permitido: mantén la actual si existía
        $imagen = $_POST["imagenactual"];
      }
    }

    if (empty($idarticulo)) {
      // insertar
      $rspta = $articulo->insertar($idcategoria, $codigo, $nombre, $stock, $precio_compra, $precio_venta, $descripcion, $imagen);
      if ($rspta === "duplicado") {
        echo "duplicado";
      } else {
        echo $rspta ? "Artículo registrado" : "Artículo no se pudo registrar";
      }
    } else {
      // editar
      $rspta = $articulo->editar($idarticulo, $idcategoria, $codigo, $nombre, $stock, $precio_compra, $precio_venta, $descripcion, $imagen);
      if ($rspta === "duplicado") {
        echo "duplicado";
      } else {
        echo $rspta ? "Artículo actualizado" : "Artículo no se pudo actualizar";
      }
    }
  break;

  /* ======================= CAMBIOS DE ESTADO ======================= */
  case 'desactivar':
    $rspta = $articulo->desactivar($idarticulo);
    echo $rspta ? "Artículo desactivado" : "Artículo no se puede desactivar";
  break;

  case 'activar':
    $rspta = $articulo->activar($idarticulo);
    echo $rspta ? "Artículo activado" : "Artículo no se puede activar";
  break;

  /* ======================= MOSTRAR (por id) ======================= */
  case 'mostrar':
    $rspta = $articulo->mostrar($idarticulo);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($rspta);
  break;

  /* ======================= LISTAR (DataTables) ======================= */
    /* ======================= LISTAR (DataTables) ======================= */
 case 'listar':
  $rspta = $articulo->listar();

  $rows = [];
  $thumbStyle = "width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb";
  $placeholder = "../public/img/no-image.png";

  while ($reg = $rspta->fetch_object()) {
    $img = !empty($reg->imagen) ? "../files/articulos/".$reg->imagen : $placeholder;

    // ✳️ En vez de inline onclick, deja un data-id y una clase (mejor con CSP)
    $btns =
      '<button class="btn btn-warning btn-sm btn-edit" data-id="'.$reg->idarticulo.'" title="Editar"><i class="fa fa-pencil"></i></button> '.
      ($reg->condicion
        ? '<button class="btn btn-danger btn-sm btn-off" data-id="'.$reg->idarticulo.'"><i class="fa fa-trash"></i></button>'
        : '<button class="btn btn-primary btn-sm btn-on" data-id="'.$reg->idarticulo.'"><i class="fa fa-check"></i></button>'
      );

    $rows[] = [
      $btns,
      htmlspecialchars($reg->nombre, ENT_QUOTES, 'UTF-8'),
      htmlspecialchars($reg->categoria, ENT_QUOTES, 'UTF-8'),
      htmlspecialchars($reg->codigo, ENT_QUOTES, 'UTF-8'),
      (string)$reg->stock,
      number_format((float)$reg->precio_compra, 2, '.', ''),
      number_format((float)$reg->precio_venta, 2, '.', ''),
      '<img src="'.$img.'" style="'.$thumbStyle.'">',
      $reg->condicion ? '<span class="label bg-green">Activado</span>' : '<span class="label bg-red">Desactivado</span>'
    ];
  }

  $draw  = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
  $total = count($rows);

  $payload = [
    // moderno
    "draw" => $draw,
    "recordsTotal" => $total,
    "recordsFiltered" => $total,
    "data" => $rows,
    // legacy (si tu front antiguo lo esperaba)
    "sEcho" => $draw,
    "iTotalRecords" => $total,
    "iTotalDisplayRecords" => $total,
    "aaData" => $rows
  ];

  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
break;


  /* ======================= SELECT de categorías ======================= */
  case "selectCategoria":
    require_once "../modelos/Categoria.php";
    $categoria = new Categoria();
    $rspta = $categoria->select();
    while ($reg = $rspta->fetch_object()){
      echo '<option value="'.$reg->idcategoria.'">'.htmlspecialchars($reg->nombre,ENT_QUOTES,'UTF-8').'</option>';
    }
  break;

  default:
    http_response_code(400);
    echo "Operación no válida";
  break;
}

ob_end_flush();
