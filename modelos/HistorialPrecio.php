<?php
require "../config/Conexion.php";

class HistorialPrecios {

  public function __construct(){}

  /* Insertar movimiento en historial_precios */
  public function insertar($idarticulo, $precio_anterior, $precio_nuevo, $motivo, $fuente='manual', $id_origen=null, $idusuario=null){
    $idarticulo      = (int)$idarticulo;
    $precio_anterior = (float)$precio_anterior;
    $precio_nuevo    = (float)$precio_nuevo;
    $motivo          = limpiarCadena($motivo);
    $fuente          = ($fuente==='ingreso' ? 'ingreso' : 'manual');
    $id_origen_sql   = is_null($id_origen) ? "NULL" : (int)$id_origen;
    $idusuario_sql   = is_null($idusuario) ? "NULL" : (int)$idusuario;

    $sql = "INSERT INTO historial_precios
              (idarticulo, precio_anterior, precio_nuevo, motivo, fuente, id_origen, idusuario, fecha)
            VALUES
              ($idarticulo, $precio_anterior, $precio_nuevo,
               '".mysqli_real_escape_string($GLOBALS['conexion'],$motivo)."',
               '$fuente', $id_origen_sql, $idusuario_sql, NOW())";
    return ejecutarConsulta($sql);
  }

  /* Lista movimientos (opcional por artículo) */
  public function listarMovimientos($idarticulo = 0){
    $idarticulo = (int)$idarticulo;
    $where = $idarticulo>0 ? "WHERE hp.idarticulo = $idarticulo" : "";
    $sql = "SELECT
              hp.id_historial,
              a.nombre  AS articulo,
              a.codigo  AS codigo,
              hp.precio_anterior,
              hp.precio_nuevo,
              hp.motivo,
              hp.fuente,
              u.nombre  AS usuario,
              DATE_FORMAT(hp.fecha, '%Y-%m-%d %H:%i:%s') AS fecha
            FROM historial_precios hp
            LEFT JOIN articulo a ON a.idarticulo = hp.idarticulo
            LEFT JOIN usuario  u ON u.idusuario  = hp.idusuario
            $where
            ORDER BY hp.fecha DESC, hp.id_historial DESC";
    return ejecutarConsulta($sql);
  }

  /* Lee precios vigentes desde la vista v_precios_actuales */
  public function listarVigentes($idarticulo = 0){
    $idarticulo = (int)$idarticulo;
    $where = $idarticulo>0 ? "WHERE v.idarticulo = $idarticulo" : "";
    $sql = "SELECT v.idarticulo, v.nombre, v.precio_venta, v.precio_compra, v.stock
            FROM v_precios_actuales v
            $where
            ORDER BY v.nombre ASC";
    return ejecutarConsulta($sql);
  }

  /* Precio actual (para el modal) */
  public function precioActual($idarticulo){
    $idarticulo = (int)$idarticulo;
    $sql = "SELECT precio_venta FROM articulo WHERE idarticulo = $idarticulo LIMIT 1";
    return ejecutarConsultaSimpleFila($sql);
  }

  /* Actualiza el precio vigente en artículo */
  public function actualizarPrecioArticulo($idarticulo, $precio_nuevo){
    $idarticulo   = (int)$idarticulo;
    $precio_nuevo = (float)$precio_nuevo;
    $sql = "UPDATE articulo SET precio_venta = $precio_nuevo WHERE idarticulo = $idarticulo";
    return ejecutarConsulta($sql);
  }
}
