<?php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

require_once "../config/Conexion.php";
require_once "../modelos/HistorialPrecio.php";

if (!function_exists('limpiarCadena')) {
  function limpiarCadena($str){ return htmlspecialchars(trim((string)$str), ENT_QUOTES, 'UTF-8'); }
}
function j($ok,$p=[],$code=200){
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(array_merge(['success'=>$ok],$p), JSON_UNESCAPED_UNICODE);
  exit;
}

$hist = new HistorialPrecios();
$op   = $_GET["op"] ?? '';

switch ($op) {

  /* === MOVIMIENTOS (historial) === */
  case 'listar_movimientos':
    $id = isset($_GET['idarticulo']) ? (int)$_GET['idarticulo'] : 0;
    $rs = $hist->listarMovimientos($id);
    $data = [];
    while ($r = $rs->fetch_object()) {
      $data[] = [
        0 => $r->id_historial,
        1 => $r->articulo,
        2 => $r->codigo,
        3 => number_format((float)$r->precio_anterior,2,'.',''),
        4 => number_format((float)$r->precio_nuevo,2,'.',''),
        5 => $r->motivo,
        6 => $r->fuente,
        7 => $r->usuario ?: '—',
        8 => $r->fecha
      ];
    }
    echo json_encode(["data"=>$data]);
    break;

  /* === VIGENTES (desde la vista v_precios_actuales) === */
  case 'listar_vigentes':
    $id = isset($_GET['idarticulo']) ? (int)$_GET['idarticulo'] : 0;
    $rs = $hist->listarVigentes($id);
    $data = [];
    while ($r = $rs->fetch_object()) {
      $data[] = [
        0 => $r->idarticulo,
        1 => $r->nombre,
        2 => number_format((float)$r->precio_venta,2,'.',''),
        3 => number_format((float)$r->precio_compra,2,'.',''),
        4 => (int)$r->stock
      ];
    }
    echo json_encode(["data"=>$data]);
    break;

  /* === Precio actual para el modal === */
  case 'ultimo':
    $id = (int)($_GET['idarticulo'] ?? 0);
    if ($id<=0) j(false,['message'=>'Artículo inválido'],400);
    $fila = $hist->precioActual($id);
    $precio = $fila ? (float)$fila['precio_venta'] : 0;
    j(true, ['precio_venta'=>$precio]);
    break;

  /* === Actualización de precio (registra movimiento) === */
  case 'actualizar_precio':
    if (empty($_SESSION['idusuario'])) j(false,['message'=>'No autenticado'],401);
    $idusuario   = (int)$_SESSION['idusuario'];
    $idarticulo  = (int)($_POST['idarticulo'] ?? 0);
    $nuevo       = (float)($_POST['precio_nuevo'] ?? 0);
    $motivo      = limpiarCadena($_POST['motivo'] ?? 'Ajuste manual');

    if ($idarticulo<=0) j(false,['message'=>'Artículo inválido'],400);
    if ($nuevo<0)       j(false,['message'=>'Precio inválido'],400);

    $fila = $hist->precioActual($idarticulo);
    if (!$fila) j(false,['message'=>'Artículo no encontrado'],404);
    $actual = (float)$fila['precio_venta'];

    if ($actual == $nuevo) j(true, ['message'=>'Sin cambios']);

    try{
      ejecutarConsulta("START TRANSACTION");
      if (!$hist->actualizarPrecioArticulo($idarticulo,$nuevo)) throw new Exception('No se pudo actualizar el artículo');
      if (!$hist->insertar($idarticulo,$actual,$nuevo,$motivo,'manual',null,$idusuario)) throw new Exception('No se pudo registrar el historial');
      ejecutarConsulta("COMMIT");
      j(true, ['message'=>'Precio actualizado correctamente']);
    }catch(Throwable $e){
      ejecutarConsulta("ROLLBACK");
      j(false, ['message'=>'Error: '.$e->getMessage()], 500);
    }
    break;

  default:
    j(false, ['message'=>'Operación no válida'], 400);
}
