<?php
/**
 * ajax/articulo.php – Respuestas JSON limpias para DataTables
 */
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

/* === No ensuciar JSON con warnings/notices === */
error_reporting(E_ALL);
ini_set('display_errors', '0');

/* === Helpers JSON === */
function json_ok($payload, $code = 200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($payload);
  exit;
}
function json_msg($ok, $msg, $code = 200){
  json_ok(["success"=>$ok, "message"=>$msg], $code);
}

/* === Autenticación básica === */
if (!isset($_SESSION["idusuario"])) {
  // Para AJAX siempre responde JSON, no redirijas vistas
  json_msg(false, "No autenticado", 401);
}

/* === Permiso de módulo === */
$hasAlmacen = !empty($_SESSION['almacen']) && (int)$_SESSION['almacen'] === 1;

require_once "../modelos/Articulo.php";
$articulo = new Articulo();

/* ====== Inputs comunes ====== */
$idarticulo     = isset($_POST["idarticulo"])     ? limpiarCadena($_POST["idarticulo"])     : "";
$idcategoria    = isset($_POST["idcategoria"])    ? limpiarCadena($_POST["idcategoria"])    : "";
$codigo         = isset($_POST["codigo"])         ? limpiarCadena($_POST["codigo"])         : "";
$nombre         = isset($_POST["nombre"])         ? limpiarCadena($_POST["nombre"])         : "";
$stock          = isset($_POST["stock"])          ? limpiarCadena($_POST["stock"])          : "0";
$precio_compra  = isset($_POST["precio_compra"])  ? limpiarCadena($_POST["precio_compra"])  : "0";
$precio_venta   = isset($_POST["precio_venta"])   ? limpiarCadena($_POST["precio_venta"])   : "0";
$descripcion    = isset($_POST["descripcion"])    ? limpiarCadena($_POST["descripcion"])    : "";
$imagen         = isset($_POST["imagen"])         ? limpiarCadena($_POST["imagen"])         : "";

$op = $_GET["op"] ?? '';

try {

  switch ($op) {

    /* ======================= CREAR / EDITAR ======================= */
    case 'guardaryeditar':
      if (!$hasAlmacen) json_msg(false, "Sin permiso (almacen)", 403);

      // Manejo de imagen
      if (!file_exists($_FILES['imagen']['tmp_name']) || !is_uploaded_file($_FILES['imagen']['tmp_name'])) {
        $imagen = $_POST["imagenactual"] ?? ""; // conservar
      } else {
        $mime = @mime_content_type($_FILES["imagen"]["tmp_name"]);
        $permitidos = ["image/jpg","image/jpeg","image/png"];
        if (in_array($mime, $permitidos, true)) {
          $ext = strtolower(pathinfo($_FILES["imagen"]["name"], PATHINFO_EXTENSION));
          $imagen = 'art_' . date('Ymd_His') . '_' . mt_rand(1000,9999) . '.' . $ext;
          @move_uploaded_file($_FILES["imagen"]["tmp_name"], "../files/articulos/" . $imagen);
        } else {
          $imagen = $_POST["imagenactual"] ?? "";
        }
      }

      if (empty($idarticulo)) {
        $rspta = $articulo->insertar($idcategoria, $codigo, $nombre, $stock, $precio_compra, $precio_venta, $descripcion, $imagen);
        if ($rspta === "duplicado") json_msg(false, "duplicado", 409);
        json_msg((bool)$rspta, $rspta ? "Artículo registrado" : "Artículo no se pudo registrar");
      } else {
        $rspta = $articulo->editar($idarticulo, $idcategoria, $codigo, $nombre, $stock, $precio_compra, $precio_venta, $descripcion, $imagen);
        if ($rspta === "duplicado") json_msg(false, "duplicado", 409);
        json_msg((bool)$rspta, $rspta ? "Artículo actualizado" : "Artículo no se pudo actualizar");
      }
    break;

    /* ======================= CAMBIOS DE ESTADO ======================= */
    case 'desactivar':
      if (!$hasAlmacen) json_msg(false, "Sin permiso (almacen)", 403);
      $rspta = $articulo->desactivar($idarticulo);
      json_msg((bool)$rspta, $rspta ? "Artículo desactivado" : "Artículo no se puede desactivar");
    break;

    case 'activar':
      if (!$hasAlmacen) json_msg(false, "Sin permiso (almacen)", 403);
      $rspta = $articulo->activar($idarticulo);
      json_msg((bool)$rspta, $rspta ? "Artículo activado" : "Artículo no se puede activar");
    break;

    /* ======================= MOSTRAR (por id) ======================= */
    case 'mostrar':
      if (!$hasAlmacen) json_msg(false, "Sin permiso (almacen)", 403);
      $rspta = $articulo->mostrar($idarticulo);
      json_ok($rspta ?: []);
    break;

    /* ======================= LISTAR (DataTables) ======================= */
    case 'listar':
      if (!$hasAlmacen) json_msg(false, "Sin permiso (almacen)", 403);

      $rspta = $articulo->listar();
      $rows = [];
      $thumbStyle   = "width:48px;height:48px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb";
      $placeholder  = "../public/img/no-image.png";

      while ($reg = $rspta->fetch_object()) {
        $img = !empty($reg->imagen) ? "../files/articulos/".$reg->imagen : $placeholder;

        $btns =
          '<button class="btn btn-warning btn-sm btn-edit" data-id="'.(int)$reg->idarticulo.'" title="Editar"><i class="fa fa-pencil"></i></button> '.
          ($reg->condicion
            ? '<button class="btn btn-danger btn-sm btn-off" data-id="'.(int)$reg->idarticulo.'"><i class="fa fa-trash"></i></button>'
            : '<button class="btn btn-primary btn-sm btn-on" data-id="'.(int)$reg->idarticulo.'"><i class="fa fa-check"></i></button>'
          );

        $rows[] = [
          $btns,
          htmlspecialchars($reg->nombre ?? '', ENT_QUOTES, 'UTF-8'),
          htmlspecialchars($reg->categoria ?? '', ENT_QUOTES, 'UTF-8'),
          htmlspecialchars($reg->codigo ?? '', ENT_QUOTES, 'UTF-8'),
          (string)(int)($reg->stock ?? 0),
          number_format((float)($reg->precio_compra ?? 0), 2, '.', ''),
          number_format((float)($reg->precio_venta ?? 0), 2, '.', ''),
          '<img src="'.$img.'" style="'.$thumbStyle.'">',
          ($reg->condicion ? '<span class="label bg-green">Activado</span>' : '<span class="label bg-red">Desactivado</span>')
        ];
      }

      $draw  = isset($_GET['draw']) ? (int)$_GET['draw'] : 1;
      $total = count($rows);

      // Devuelve solo el formato moderno (DataTables acepta "data")
      json_ok([
        "draw" => $draw,
        "recordsTotal" => $total,
        "recordsFiltered" => $total,
        "data" => $rows
      ]);
    break;

    /* ======================= SELECT de categorías ======================= */
    case "selectCategoria":
      require_once "../modelos/Categoria.php";
      $categoria = new Categoria();
      $rspta = $categoria->select();
      header('Content-Type: text/html; charset=utf-8');
      while ($reg = $rspta->fetch_object()){
        echo '<option value="'.$reg->idcategoria.'">'.htmlspecialchars($reg->nombre,ENT_QUOTES,'UTF-8').'</option>';
      }
      exit;

    /* ======================= SELECT de artículos activos (para historial) ======================= */
    case 'selectActivos':
      $rspta = $articulo->selectActivosParaHistorial();
      header('Content-Type: text/html; charset=utf-8');
      echo '<option value="">Seleccione artículo</option>';
      while ($reg = $rspta->fetch_object()) {
        $text = htmlspecialchars(($reg->nombre ?? '') . ' — ' . ($reg->codigo ?? ''), ENT_QUOTES, 'UTF-8');
        echo '<option value="'.$reg->idarticulo.'">'.$text.'</option>';
      }
      exit;

    default:
      json_msg(false, "Operación no válida", 400);
  }

} catch (Throwable $e) {
  json_msg(false, "Error: ".$e->getMessage(), 500);
}

ob_end_flush();
