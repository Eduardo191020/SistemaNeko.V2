<?php 
// modelos/Ingreso.php
// Incluímos inicialmente la conexión a la base de datos
require "../config/Conexion.php";

class Ingreso
{
  public function __construct(){}

  /* ============================================================
   * Insertar con transacción
   * - Usa el idusuario que llega, pero valida que sea > 0.
   *   (Mi consejo: pásalo siempre desde la sesión en ajax/ingreso.php)
   * ============================================================ */
  public function insertar(
      $idproveedor, $idusuario, $tipo_comprobante, $serie_comprobante, $num_comprobante,
      $fecha_hora, $impuesto, $total_compra,
      $idarticulo, $cantidad, $precio_compra, $precio_venta
  ){
    // Validaciones mínimas (defensivas)
    if (empty($idproveedor) || empty($idusuario)) return false;
    if (!is_array($idarticulo) || !is_array($cantidad) || !is_array($precio_compra) || !is_array($precio_venta)) return false;
    if (count($idarticulo) !== count($cantidad) || count($idarticulo) !== count($precio_compra) || count($idarticulo) !== count($precio_venta)) return false;

    // Iniciar transacción
    ejecutarConsulta("START TRANSACTION");

    $sql = "INSERT INTO ingreso
            (idproveedor,idusuario,tipo_comprobante,serie_comprobante,num_comprobante,fecha_hora,impuesto,total_compra,estado)
            VALUES
            ('$idproveedor','$idusuario','$tipo_comprobante','$serie_comprobante','$num_comprobante','$fecha_hora','$impuesto','$total_compra','Aceptado')";
    $idingresonew = ejecutarConsulta_retornarID($sql);
    if (!$idingresonew) {
      ejecutarConsulta("ROLLBACK");
      return false;
    }

    $sw = true;
    for ($i=0; $i<count($idarticulo); $i++) {
      $ida = (int)$idarticulo[$i];
      $cant = (float)$cantidad[$i];
      $pc   = (float)$precio_compra[$i];
      $pv   = (float)$precio_venta[$i];

      // No permitir negativos/cero
      if ($ida <= 0 || $cant <= 0 || $pc < 0 || $pv < 0) { $sw = false; break; }

      $sql_detalle = "INSERT INTO detalle_ingreso
                      (idingreso, idarticulo, cantidad, precio_compra, precio_venta)
                      VALUES
                      ('$idingresonew', '$ida', '$cant', '$pc', '$pv')";
      if (!ejecutarConsulta($sql_detalle)) { $sw = false; break; }

      // (Opcional) Actualizar stock aquí si tu diseño lo hace en el alta
      // ejecutarConsulta("UPDATE articulo SET stock = stock + $cant WHERE idarticulo = $ida");
    }

    if ($sw) {
      ejecutarConsulta("COMMIT");
      return true;
    } else {
      ejecutarConsulta("ROLLBACK");
      return false;
    }
  }

  // Anular ingreso
  public function anular($idingreso){
    $sql="UPDATE ingreso SET estado='Anulado' WHERE idingreso='$idingreso'";
    return ejecutarConsulta($sql);
  }

  // Mostrar cabecera de un ingreso
  public function mostrar($idingreso){
    $sql="SELECT i.idingreso,
                 DATE(i.fecha_hora) as fecha,
                 i.idproveedor, p.nombre as proveedor,
                 u.idusuario, u.nombre as usuario,
                 i.tipo_comprobante,i.serie_comprobante,i.num_comprobante,
                 i.total_compra,i.impuesto,i.estado
          FROM ingreso i
          INNER JOIN persona p ON i.idproveedor=p.idpersona
          INNER JOIN usuario u ON i.idusuario=u.idusuario
          WHERE i.idingreso='$idingreso'";
    return ejecutarConsultaSimpleFila($sql);
  }

  // Detalle de ingreso
  public function listarDetalle($idingreso){
    $sql="SELECT di.idingreso,di.idarticulo,a.nombre,
                 di.cantidad,di.precio_compra,di.precio_venta
          FROM detalle_ingreso di
          INNER JOIN articulo a ON di.idarticulo=a.idarticulo
          WHERE di.idingreso='$idingreso'";
    return ejecutarConsulta($sql);
  }

  // Listado con filtros de fecha (opcional)
  public function listar($desde = '', $hasta = ''){
    $where = "1=1";
    if ($desde !== '') $where .= " AND DATE(i.fecha_hora) >= '$desde'";
    if ($hasta !== '') $where .= " AND DATE(i.fecha_hora) <= '$hasta'";

    $sql = "SELECT i.idingreso,
                   DATE(i.fecha_hora) AS fecha,
                   p.nombre AS proveedor,
                   u.nombre AS usuario,
                   i.tipo_comprobante,i.serie_comprobante,i.num_comprobante,
                   i.total_compra,i.estado
            FROM ingreso i
            INNER JOIN persona p ON i.idproveedor = p.idpersona
            INNER JOIN usuario u ON i.idusuario  = u.idusuario
            WHERE $where
            ORDER BY i.idingreso DESC";
    return ejecutarConsulta($sql);
  }

  // Reporte cabecera
  public function ingresocabecera($idingreso){
    $sql="SELECT i.idingreso,i.idproveedor,p.nombre as proveedor,p.direccion,p.tipo_documento,
                 p.num_documento,p.email,p.telefono,i.idusuario,u.nombre as usuario,
                 i.tipo_comprobante,i.serie_comprobante,i.num_comprobante,DATE(i.fecha_hora) as fecha,
                 i.impuesto,i.total_compra
          FROM ingreso i
          INNER JOIN persona p ON i.idproveedor=p.idpersona
          INNER JOIN usuario u ON i.idusuario=u.idusuario
          WHERE i.idingreso='$idingreso'";
    return ejecutarConsulta($sql);
  }

  // Reporte detalle
  public function ingresodetalle($idingreso){
    $sql="SELECT a.nombre as articulo,a.codigo,d.cantidad,d.precio_compra,d.precio_venta,
                 (d.cantidad*d.precio_compra) as subtotal
          FROM detalle_ingreso d
          INNER JOIN articulo a ON d.idarticulo=a.idarticulo
          WHERE d.idingreso='$idingreso'";
    return ejecutarConsulta($sql);
  }
}
?>
