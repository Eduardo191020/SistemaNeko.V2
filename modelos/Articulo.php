<?php 
// modelos/Articulo.php
require "../config/Conexion.php";

class Articulo
{
  public function __construct(){}

  // Helper: ¿existe un artículo con ese nombre?
  private function existeNombre($nombre, $idarticulo = null){
    $nombre = trim($nombre);
    $sql = "SELECT idarticulo FROM articulo 
            WHERE nombre = '$nombre' " . ($idarticulo ? "AND idarticulo <> '$idarticulo'" : "") . "
            LIMIT 1";
    $fila = ejecutarConsultaSimpleFila($sql);
    // ejecutarConsultaSimpleFila suele devolver array asociativo o null
    return is_array($fila) && isset($fila['idarticulo']);
  }

  public function insertar($idcategoria,$codigo,$nombre,$stock,$precio_compra,$precio_venta,$descripcion,$imagen)
  {
    if ($this->existeNombre($nombre)) { return "duplicado"; }

    $sql = "INSERT INTO articulo
            (idcategoria,codigo,nombre,stock,precio_compra,precio_venta,descripcion,imagen,condicion)
            VALUES
            ('$idcategoria','$codigo','$nombre','$stock','$precio_compra','$precio_venta','$descripcion','$imagen','1')";
    return ejecutarConsulta($sql);
  }

  public function editar($idarticulo,$idcategoria,$codigo,$nombre,$stock,$precio_compra,$precio_venta,$descripcion,$imagen)
  {
    if ($this->existeNombre($nombre, $idarticulo)) { return "duplicado"; }

    $sql = "UPDATE articulo SET
              idcategoria='$idcategoria',
              codigo='$codigo',
              nombre='$nombre',
              stock='$stock',
              precio_compra='$precio_compra',
              precio_venta='$precio_venta',
              descripcion='$descripcion',
              imagen='$imagen'
            WHERE idarticulo='$idarticulo'";
    return ejecutarConsulta($sql);
  }

  public function desactivar($idarticulo)
  {
    $sql="UPDATE articulo SET condicion='0' WHERE idarticulo='$idarticulo'";
    return ejecutarConsulta($sql);
  }

  public function activar($idarticulo)
  {
    $sql="UPDATE articulo SET condicion='1' WHERE idarticulo='$idarticulo'";
    return ejecutarConsulta($sql);
  }

  public function mostrar($idarticulo)
  {
    $sql="SELECT * FROM articulo WHERE idarticulo='$idarticulo'";
    return ejecutarConsultaSimpleFila($sql);
  }

  public function listar()
  {
    $sql="SELECT 
            a.idarticulo,
            a.idcategoria,
            c.nombre AS categoria,
            a.codigo,
            a.nombre,
            a.stock,
            a.precio_compra,
            a.precio_venta,
            a.descripcion,
            a.imagen,
            a.condicion
          FROM articulo a
          INNER JOIN categoria c ON a.idcategoria=c.idcategoria";
    return ejecutarConsulta($sql);		
  }

  public function listarActivos()
  {
    $sql="SELECT 
            a.idarticulo,
            a.idcategoria,
            c.nombre AS categoria,
            a.codigo,
            a.nombre,
            a.stock,
            a.precio_compra,
            a.precio_venta,
            a.descripcion,
            a.imagen,
            a.condicion
          FROM articulo a
          INNER JOIN categoria c ON a.idcategoria=c.idcategoria
          WHERE a.condicion='1'";
    return ejecutarConsulta($sql);		
  }

  public function listarActivosVenta()
  {
    $sql="SELECT 
            a.idarticulo,
            a.idcategoria,
            c.nombre AS categoria,
            a.codigo,
            a.nombre,
            a.stock,
            a.precio_compra,
            COALESCE(
              a.precio_venta,
              (SELECT di.precio_venta 
                 FROM detalle_ingreso di 
                WHERE di.idarticulo = a.idarticulo
                ORDER BY di.iddetalle_ingreso DESC
                LIMIT 1)
            ) AS precio_venta,
            a.descripcion,
            a.imagen,
            a.condicion
          FROM articulo a
          INNER JOIN categoria c ON a.idcategoria=c.idcategoria
          WHERE a.condicion='1'";
    return ejecutarConsulta($sql);		
  }

  // ✅ AHORA SÍ dentro de la clase
  public function selectActivosParaHistorial()
  {
    $sql = "SELECT idarticulo, codigo, nombre 
            FROM articulo 
            WHERE condicion = 1 
            ORDER BY nombre ASC";
    return ejecutarConsulta($sql);
  }
}
// (sin etiqueta de cierre PHP para evitar BOM)
