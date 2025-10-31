<?php 
// Incluímos inicialmente la conexión a la base de datos
require "../config/Conexion.php";

class Articulo
{
  // Implementamos nuestro constructor
  public function __construct(){}

  // Helper: ¿existe un artículo con ese nombre?
  private function existeNombre($nombre, $idarticulo = null){
    $nombre = trim($nombre);
    $sql = "SELECT idarticulo FROM articulo 
            WHERE nombre = '$nombre' " . ($idarticulo ? "AND idarticulo <> '$idarticulo'" : "") . "
            LIMIT 1";
    $fila = ejecutarConsultaSimpleFila($sql);
    return isset($fila['idarticulo']); // true si existe
  }

  /**
   * Insertar artículo
   * Ahora acepta precio_compra además de precio_venta
   */
  public function insertar($idcategoria,$codigo,$nombre,$stock,$precio_compra,$precio_venta,$descripcion,$imagen)
  {
    // PRE-CHEQUEO: evita pegarle al UNIQUE
    if ($this->existeNombre($nombre)) {
      return "duplicado";
    }

    $sql = "INSERT INTO articulo
            (idcategoria,codigo,nombre,stock,precio_compra,precio_venta,descripcion,imagen,condicion)
            VALUES
            ('$idcategoria','$codigo','$nombre','$stock','$precio_compra','$precio_venta','$descripcion','$imagen','1')";
    return ejecutarConsulta($sql);
  }

  /**
   * Editar artículo
   * Ahora acepta precio_compra además de precio_venta
   */
  public function editar($idarticulo,$idcategoria,$codigo,$nombre,$stock,$precio_compra,$precio_venta,$descripcion,$imagen)
  {
    // PRE-CHEQUEO: mismo nombre en otro id
    if ($this->existeNombre($nombre, $idarticulo)) {
      return "duplicado";
    }

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

  // Implementamos un método para desactivar registros
  public function desactivar($idarticulo)
  {
    $sql="UPDATE articulo SET condicion='0' WHERE idarticulo='$idarticulo'";
    return ejecutarConsulta($sql);
  }

  // Implementamos un método para activar registros
  public function activar($idarticulo)
  {
    $sql="UPDATE articulo SET condicion='1' WHERE idarticulo='$idarticulo'";
    return ejecutarConsulta($sql);
  }

  // Implementar un método para mostrar los datos de un registro a modificar
  public function mostrar($idarticulo)
  {
    $sql="SELECT * FROM articulo WHERE idarticulo='$idarticulo'";
    return ejecutarConsultaSimpleFila($sql);
  }

  // Implementar un método para listar los registros (incluye precio_compra)
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

  // Implementar un método para listar los registros activos (incluye precio_compra)
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

  /**
   * Listar activos para venta.
   * Conserva la lógica histórica: usa el último precio del detalle_ingreso
   * pero si el artículo ya tiene precio_venta definido, se prioriza ese valor.
   * Además expone precio_compra para cálculos en front.
   */
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
            /* Precio de venta: el guardado en artículo o el último de detalle_ingreso */
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
}
?>
