<?php
ob_start();
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

/* ======= Autenticación / Autorización ======= */
if (!isset($_SESSION["nombre"])) {
  header("Location: ../vistas/login.html");
  exit();
}
if (!isset($_SESSION['compras']) || (int)$_SESSION['compras'] !== 1) {
  require 'noacceso.php';
  ob_end_flush();
  exit();
}

/* ======= Modelos ======= */
require_once "../modelos/Ingreso.php";
$ingreso = new Ingreso();

/* ======= Inputs base ======= */
$idingreso         = isset($_POST["idingreso"])         ? limpiarCadena($_POST["idingreso"])         : "";
$idproveedor       = isset($_POST["idproveedor"])       ? limpiarCadena($_POST["idproveedor"])       : "";
$idusuario         = $_SESSION["idusuario"];
$tipo_comprobante  = isset($_POST["tipo_comprobante"])  ? limpiarCadena($_POST["tipo_comprobante"])  : "";
$serie_comprobante = isset($_POST["serie_comprobante"]) ? limpiarCadena($_POST["serie_comprobante"]) : "";
$num_comprobante   = isset($_POST["num_comprobante"])   ? limpiarCadena($_POST["num_comprobante"])   : "";
$fecha_hora        = isset($_POST["fecha_hora"])        ? limpiarCadena($_POST["fecha_hora"])        : "";
$impuesto          = isset($_POST["impuesto"])          ? limpiarCadena($_POST["impuesto"])          : "";
$total_compra      = isset($_POST["total_compra"])      ? limpiarCadena($_POST["total_compra"])      : "";

$op = isset($_GET["op"]) ? $_GET["op"] : '';

switch ($op) {

  /* =========================
   * Insertar (no editar)
   * ========================= */
  case 'guardaryeditar':
    if (!empty($idingreso)) {
      echo "Este módulo solo inserta (no edita).";
      break;
    }

    // Validación fecha (±2 días, America/Lima)
    $tz     = new DateTimeZone('America/Lima');
    $today  = new DateTime('today', $tz);
    $minDT  = (clone $today)->modify('-2 days');
    $maxDT  = (clone $today)->modify('+2 days');

    $raw = trim($fecha_hora);
    $fh  = DateTime::createFromFormat('Y-m-d', $raw, $tz);
    if (!$fh) { $fh = DateTime::createFromFormat('d/m/Y', $raw, $tz); }

    if (!$fh) {
      http_response_code(400);
      echo "Fecha inválida.";
      break;
    }
    $fh->setTime(0,0,0);
    if ($fh < $minDT || $fh > $maxDT) {
      http_response_code(422);
      echo "La fecha debe estar dentro de ±2 días respecto a hoy.";
      break;
    }
    $fecha_sql = $fh->format('Y-m-d');

    // Inserción
    $rspta = $ingreso->insertar(
      $idproveedor,
      $idusuario,
      $tipo_comprobante,
      $serie_comprobante,
      $num_comprobante,
      $fecha_sql,
      $impuesto,
      $total_compra,
      isset($_POST["idarticulo"])    ? $_POST["idarticulo"]    : [],
      isset($_POST["cantidad"])      ? $_POST["cantidad"]      : [],
      isset($_POST["precio_compra"]) ? $_POST["precio_compra"] : [],
      isset($_POST["precio_venta"])  ? $_POST["precio_venta"]  : []
    );

    echo $rspta ? "Ingreso registrado" : "No se pudieron registrar todos los datos del ingreso";
  break;

  /* =========================
   * Anular
   * ========================= */
  case 'anular':
    $rspta = $ingreso->anular($idingreso);
    echo $rspta ? "Ingreso anulado" : "Ingreso no se puede anular";
  break;

  /* =========================
   * Mostrar por id (para ver)
   * ========================= */
    /* =========================
   * Mostrar por id (para ver)
   * ========================= */
  case 'mostrar':
    // Acepta id por POST (idingreso) o GET (id)
    $id = 0;
    if (isset($_POST['idingreso'])) $id = (int)$_POST['idingreso'];
    elseif (isset($_GET['id']))     $id = (int)$_GET['id'];

    header('Content-Type: application/json; charset=utf-8');

    if ($id <= 0) {
      http_response_code(400);
      echo json_encode(['error' => 'ID de ingreso requerido']);
      exit;
    }

    $raw = $ingreso->mostrar($id);
    if (!$raw) {
      http_response_code(404);
      echo json_encode(['error' => 'Ingreso no encontrado']);
      exit;
    }

    // $raw puede venir como objeto o array y con claves distintas (fecha_hora, etc.)
    // Normalizamos a lo que espera ingreso.js
    if (is_object($raw)) { $raw = (array)$raw; }

    // Obtiene fecha (YYYY-mm-dd) desde 'fecha' o 'fecha_hora'
    $fechaSrc = '';
    if (!empty($raw['fecha']))       $fechaSrc = (string)$raw['fecha'];
    if (!empty($raw['fecha_hora']))  $fechaSrc = (string)$raw['fecha_hora'];
    // recorta a 10 (YYYY-mm-dd)
    $fecha = substr($fechaSrc, 0, 10);

    $out = [
      'idingreso'        => isset($raw['idingreso'])        ? (int)$raw['idingreso']        : $id,
      'idproveedor'      => isset($raw['idproveedor'])      ? (int)$raw['idproveedor']      : 0,
      'tipo_comprobante' => isset($raw['tipo_comprobante']) ? (string)$raw['tipo_comprobante'] : '',
      'serie_comprobante'=> isset($raw['serie_comprobante'])? (string)$raw['serie_comprobante'] : '',
      'num_comprobante'  => isset($raw['num_comprobante'])  ? (string)$raw['num_comprobante']   : '',
      'fecha'            => $fecha,
      'impuesto'         => isset($raw['impuesto'])         ? (string)$raw['impuesto']      : '0',
    ];

    // Limpia cualquier buffer previo que pudiera colar HTML
    while (ob_get_level() > 1) { ob_end_clean(); }
    echo json_encode($out);
    exit;


  /* =========================
   * Detalle HTML (vista lectura)
   * ========================= */
  case 'listarDetalle':
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    if ($id <= 0) {
      echo '<thead><th>Opciones</th><th>Artículo</th><th>Cantidad</th><th>Precio Compra</th><th>Precio Venta</th><th>Subtotal</th></thead>
            <tbody><tr><td colspan="6">ID inválido.</td></tr></tbody>
            <tfoot><th>TOTAL</th><th></th><th></th><th></th><th></th><th><h4 id="total">S/. 0.00</h4></th></tfoot>';
      break;
    }

    $rspta = $ingreso->listarDetalle($id);
    $total = 0;

    echo '<thead style="background-color:#A9D0F5">
            <th>Opciones</th>
            <th>Artículo</th>
            <th>Cantidad</th>
            <th>Precio Compra</th>
            <th>Precio Venta</th>
            <th>Subtotal</th>
          </thead>';

    while ($reg = $rspta->fetch_object()) {
      $pc  = (float)$reg->precio_compra;
      $pv  = (float)$reg->precio_venta;
      $qty = (float)$reg->cantidad;
      $sub = $pc * $qty;

      echo '<tr class="filas">
              <td></td>
              <td>'.htmlspecialchars($reg->nombre,ENT_QUOTES,'UTF-8').'</td>
              <td>'.$qty.'</td>
              <td>'.number_format($pc,2,'.','').'</td>
              <td>'.number_format($pv,2,'.','').'</td>
              <td>'.number_format($sub,2,'.','').'</td>
            </tr>';

      $total += $sub;
    }

    echo '<tfoot>
            <th>TOTAL</th>
            <th></th><th></th><th></th><th></th>
            <th><h4 id="total">S/. '.number_format($total,2,'.','').'</h4>
                <input type="hidden" name="total_compra" id="total_compra"></th>
          </tfoot>';
  break;

  /* =========================
   * Listado principal (DataTables)
   * con filtro opcional por fechas
   * ========================= */
  case 'listar':
    $desde = isset($_GET['desde']) ? trim($_GET['desde']) : '';
    $hasta = isset($_GET['hasta']) ? trim($_GET['hasta']) : '';

    $rspta = $ingreso->listar($desde, $hasta);

    $data = array();
    while ($reg = $rspta->fetch_object()) {
      $btnVer   = '<button class="btn btn-warning" title="Ver" onclick="mostrar('.$reg->idingreso.')"><i class="fa fa-eye"></i></button>';
      $btnAnular= '<button class="btn btn-danger"  title="Anular" onclick="anular('.$reg->idingreso.')"><i class="fa fa-close"></i></button>';
      $btnPdf   = '<a target="_blank" href="../reportes/exIngreso.php?id='.$reg->idingreso.'">
                     <button class="btn btn-info" title="Comprobante"><i class="fa fa-file"></i></button>
                   </a>';

      $ops = ($reg->estado=='Aceptado') ? ($btnVer.' '.$btnAnular.' '.$btnPdf) : ($btnVer.' '.$btnPdf);

      $data[] = array(
        "0" => $ops,
        "1" => $reg->fecha, // Asegúrate que en el modelo sea DATE(i.fecha_hora) AS fecha
        "2" => $reg->proveedor,
        "3" => $reg->usuario,
        "4" => $reg->tipo_comprobante,
        "5" => $reg->serie_comprobante.'-'.$reg->num_comprobante,
        "6" => $reg->total_compra,
        "7" => ($reg->estado=='Aceptado')
                ? '<span class="label bg-green">Aceptado</span>'
                : '<span class="label bg-red">Anulado</span>'
      );
    }

    $results = array(
      "sEcho" => 1,
      "iTotalRecords" => count($data),
      "iTotalDisplayRecords" => count($data),
      "aaData" => $data
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results);
  break;

  /* =========================
   * Select de Proveedor
   * ========================= */
  case 'selectProveedor':
    require_once "../modelos/Persona.php";
    $persona = new Persona();
    $rspta   = $persona->listarP();
    while ($reg = $rspta->fetch_object()) {
      echo '<option value="'.(int)$reg->idpersona.'">'.htmlspecialchars($reg->nombre,ENT_QUOTES,'UTF-8').'</option>';
    }
  break;

  /* =========================
   * Listar artículos (modal)
   * ========================= */
  case 'listarArticulos':
    require_once "../modelos/Articulo.php";
    $articulo = new Articulo();

    $rspta = $articulo->listarActivos();
    $data  = array();

    while ($reg = $rspta->fetch_object()) {
      $btn = '<button class="btn btn-warning" '.
             'onclick="agregarDetalle('.(int)$reg->idarticulo.',\''.
               addslashes($reg->nombre).'\','.
               number_format((float)$reg->precio_compra,2,'.','').','.
               number_format((float)$reg->precio_venta,2,'.','').')">'.
               '<span class="fa fa-plus"></span></button>';

      $img = "<img src=\"../files/articulos/".htmlspecialchars($reg->imagen,ENT_QUOTES,'UTF-8')."\" ".
             "style=\"width:46px;height:46px;object-fit:cover;border-radius:6px;border:1px solid #e5e7eb\">";

      $data[] = array(
        "0" => $btn,
        "1" => htmlspecialchars($reg->nombre,ENT_QUOTES,'UTF-8'),
        "2" => htmlspecialchars($reg->categoria,ENT_QUOTES,'UTF-8'),
        "3" => htmlspecialchars($reg->codigo,ENT_QUOTES,'UTF-8'),
        "4" => (int)$reg->stock,
        "5" => number_format((float)$reg->precio_compra, 2, '.', ''),
        "6" => number_format((float)$reg->precio_venta, 2, '.', ''),
        "7" => $img
      );
    }

    $results = array(
      "sEcho" => 1,
      "iTotalRecords" => count($data),
      "iTotalDisplayRecords" => count($data),
      "aaData" => $data
    );

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($results);
  break;

  default:
    http_response_code(400);
    echo "Operación no válida";
  break;
}

ob_end_flush();
