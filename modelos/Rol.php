<?php
// Incluimos inicialmente la conexión a la base de datos
require "../config/Conexion.php";

class Rol
{
  // Implementamos nuestro constructor
  public function __construct(){}

  // Insertar registro
  public function insertar($nombre)
  {
    $sql = "INSERT INTO rol_usuarios (nombre, estado, creado_en)
            VALUES ('$nombre','1', NOW())";
    return ejecutarConsulta($sql);
  }

  // Editar registro
  public function editar($idrol, $nombre)
  {
    $sql = "UPDATE rol_usuarios SET nombre='$nombre'
            WHERE id_rol='$idrol'";
    return ejecutarConsulta($sql);
  }

  // Desactivar (eliminación lógica)
  public function desactivar($idrol)
  {
    $sql = "UPDATE rol_usuarios SET estado='0'
            WHERE id_rol='$idrol'";
    return ejecutarConsulta($sql);
  }

  // Activar
  public function activar($idrol)
  {
    $sql = "UPDATE rol_usuarios SET estado='1'
            WHERE id_rol='$idrol'";
    return ejecutarConsulta($sql);
  }

  // Mostrar un registro
  public function mostrar($idrol)
  {
    $sql = "SELECT * FROM rol_usuarios WHERE id_rol='$idrol'";
    return ejecutarConsultaSimpleFila($sql);
  }

  // Listar todos
  public function listar()
  {
    $sql = "SELECT id_rol, nombre, estado, creado_en
            FROM rol_usuarios
            ORDER BY id_rol";
    return ejecutarConsulta($sql);
  }

  // Listar solo activos (para combos)
  public function listarActivos()
  {
    $sql = "SELECT id_rol, nombre
            FROM rol_usuarios
            WHERE estado='1'
            ORDER BY nombre";
    return ejecutarConsulta($sql);
  }
}
?>
