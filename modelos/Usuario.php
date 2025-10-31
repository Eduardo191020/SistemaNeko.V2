<?php 
require "../config/Conexion.php";

Class Usuario
{
	public function __construct()
	{
	}

	public function insertar($nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clave,$imagen,$permisos)
	{
		$sql="INSERT INTO usuario (nombre,tipo_documento,num_documento,direccion,telefono,email,cargo,clave,imagen,condicion)
		VALUES ('$nombre','$tipo_documento','$num_documento','$direccion','$telefono','$email','$cargo','$clave','$imagen','1')";
		
		$idusuarionew=ejecutarConsulta_retornarID($sql);

		$num_elementos=0;
		$sw=true;

		while ($num_elementos < count($permisos))
		{
			$sql_detalle = "INSERT INTO usuario_permiso(idusuario, idpermiso) VALUES('$idusuarionew', '$permisos[$num_elementos]')";
			ejecutarConsulta($sql_detalle) or $sw = false;
			$num_elementos=$num_elementos + 1;
		}

		return $sw;
	}

	public function editar($idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clave,$imagen,$permisos)
	{
		$sql="UPDATE usuario SET nombre='$nombre',tipo_documento='$tipo_documento',num_documento='$num_documento',direccion='$direccion',telefono='$telefono',email='$email',cargo='$cargo',clave='$clave',imagen='$imagen' WHERE idusuario='$idusuario'";
		ejecutarConsulta($sql);

		$sqldel="DELETE FROM usuario_permiso WHERE idusuario='$idusuario'";
		ejecutarConsulta($sqldel);

		$num_elementos=0;
		$sw=true;

		while ($num_elementos < count($permisos))
		{
			$sql_detalle = "INSERT INTO usuario_permiso(idusuario, idpermiso) VALUES('$idusuario', '$permisos[$num_elementos]')";
			ejecutarConsulta($sql_detalle) or $sw = false;
			$num_elementos=$num_elementos + 1;
		}

		return $sw;
	}

	public function desactivar($idusuario)
	{
		$sql="UPDATE usuario SET condicion='0' WHERE idusuario='$idusuario'";
		return ejecutarConsulta($sql);
	}

	public function activar($idusuario)
	{
		$sql="UPDATE usuario SET condicion='1' WHERE idusuario='$idusuario'";
		return ejecutarConsulta($sql);
	}

	public function mostrar($idusuario)
	{
		$sql="SELECT * FROM usuario WHERE idusuario='$idusuario'";
		return ejecutarConsultaSimpleFila($sql);
	}

	public function listar()
	{
		// ✅ QUERY CORREGIDA: Ahora trae tipo_documento desde tipo_documento tabla
		$sql="SELECT 
		        u.idusuario,
		        u.nombre,
		        COALESCE(td.nombre, u.tipo_documento) as tipo_documento,
		        u.num_documento,
		        u.telefono,
		        u.email,
		        u.cargo,
		        u.imagen,
		        u.condicion,
		        r.nombre as nombre_rol
		      FROM usuario u
		      LEFT JOIN rol_usuarios r ON u.id_rol = r.id_rol
		      LEFT JOIN tipo_documento td ON u.id_tipodoc = td.id_tipodoc
		      ORDER BY u.idusuario DESC";
		return ejecutarConsulta($sql);		
	}
	
	public function listarmarcados($idusuario)
	{
		$sql="SELECT * FROM usuario_permiso WHERE idusuario='$idusuario'";
		return ejecutarConsulta($sql);
	}

	// ✅ SOLO LOGIN CON EMAIL (sin campo login)
	public function verificar($email, $clave)
    {
    	$sql="SELECT idusuario, nombre, tipo_documento, num_documento, telefono, email, cargo, imagen, id_rol
    	      FROM usuario 
    	      WHERE email='$email' 
    	      AND clave='$clave' 
    	      AND condicion='1'"; 
    	return ejecutarConsulta($sql);  
    }
    
    public function verificarEmailExiste($email, $idusuario = 0)
    {
        global $conexion;
        $email_escaped = $conexion->real_escape_string($email);
        
        if ($idusuario > 0) {
            $sql = "SELECT COUNT(*) as total FROM usuario 
                    WHERE email='$email_escaped' 
                    AND idusuario != '$idusuario'";
        } else {
            $sql = "SELECT COUNT(*) as total FROM usuario 
                    WHERE email='$email_escaped'";
        }
        
        try {
            $result = ejecutarConsultaSimpleFila($sql);
            
            if ($result === false || $result === null || !isset($result['total'])) {
                error_log("Error verificando email en Usuario.php: $email");
                return false;
            }
            
            return (int)$result['total'] > 0;
            
        } catch (Exception $e) {
            error_log("Excepción verificando email: " . $e->getMessage());
            return false;
        }
    }
    
    public function verificarDocumentoExiste($tipo_documento, $num_documento, $idusuario = 0)
    {
        global $conexion;
        $tipo_escaped = $conexion->real_escape_string($tipo_documento);
        $num_escaped = $conexion->real_escape_string($num_documento);
        
        if ($idusuario > 0) {
            $sql = "SELECT COUNT(*) as total FROM usuario 
                    WHERE tipo_documento='$tipo_escaped' 
                    AND num_documento='$num_escaped' 
                    AND idusuario != '$idusuario'";
        } else {
            $sql = "SELECT COUNT(*) as total FROM usuario 
                    WHERE tipo_documento='$tipo_escaped' 
                    AND num_documento='$num_escaped'";
        }
        
        try {
            $result = ejecutarConsultaSimpleFila($sql);
            
            if ($result === false || $result === null || !isset($result['total'])) {
                error_log("Error verificando documento en Usuario.php: $tipo_documento $num_documento");
                return false;
            }
            
            return (int)$result['total'] > 0;
            
        } catch (Exception $e) {
            error_log("Excepción verificando documento: " . $e->getMessage());
            return false;
        }
    }
}

?>