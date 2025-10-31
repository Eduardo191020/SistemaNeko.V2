<?php 
require "../config/Conexion.php";

class Usuario
{
	public function __construct(){}

	/* ===========================
	   INSERTAR
	   - $permisos: array de ids. Si viene vacío y $modo_permisos='rol', se cargan del rol.
	   - $id_rol: opcional; si viene, se guarda en usuario.id_rol
	   - $cargo: puedes enviar el nombre del rol (para mostrar en grillas) o dejarlo en blanco y se toma del rol
	   =========================== */
	public function insertar(
		$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clave,$imagen,$permisos,
		$id_rol = null, $modo_permisos = ''
	){
		global $conexion;

		// Si no envías $cargo pero sí $id_rol, tomamos el nombre del rol
		if ((empty($cargo) || $cargo === '0') && !empty($id_rol)) {
			$cargo = $this->obtenerNombreRol($id_rol) ?: '';
		}

		// Construir columnas dinámicamente para compatibilidad con esquemas
		$cols = "nombre,tipo_documento,num_documento,direccion,telefono,email,cargo,clave,imagen,condicion";
		$vals = "'$nombre','$tipo_documento','$num_documento','$direccion','$telefono','$email','$cargo','$clave','$imagen','1'";

		if (!is_null($id_rol) && $id_rol !== '') {
			$id_rol = (int)$id_rol;
			$cols .= ",id_rol";
			$vals .= ",'$id_rol'";
		}

		$sql = "INSERT INTO usuario ($cols) VALUES ($vals)";
		$idusuarionew = ejecutarConsulta_retornarID($sql);

		// Si vienen permisos manuales, se usan; si no y modo rol, tomamos del rol
		if ((!is_array($permisos) || count($permisos) === 0) && $modo_permisos === 'rol' && !empty($id_rol)) {
			$permisos = $this->permisosDeRol($id_rol);
		}

		$sw = $this->setPermisosUsuario($idusuarionew, $permisos);
		return $sw;
	}

	/* ===========================
	   EDITAR
	   - Si $clave viene vacía => NO se cambia clave
	   - Si $imagen viene vacía => se mantiene la actual
	   - Si $modo_permisos='rol' y no recibes $permisos => aplica los del rol
	   - $id_rol opcional (se actualiza si llega)
	   =========================== */
	public function editar(
		$idusuario,$nombre,$tipo_documento,$num_documento,$direccion,$telefono,$email,$cargo,$clave,$imagen,$permisos,
		$id_rol = null, $modo_permisos = '', $mantener_clave = false
	){
		$idusuario = (int)$idusuario;

		// Armar SET dinámico
		$sets = array();
		$sets[] = "nombre='$nombre'";
		$sets[] = "tipo_documento='$tipo_documento'";
		$sets[] = "num_documento='$num_documento'";
		$sets[] = "direccion='$direccion'";
		$sets[] = "telefono='$telefono'";
		$sets[] = "email='$email'";

		// cargo (texto visible en la UI, normalmente el nombre del rol)
		$sets[] = "cargo='$cargo'";

		// clave: actualizar solo si viene y no se indicó mantener
		if (!$mantener_clave && $clave !== null && $clave !== '') {
			$sets[] = "clave='$clave'";
		}

		// imagen: conservar si viene vacía
		if ($imagen !== null && $imagen !== '') {
			$sets[] = "imagen='$imagen'";
		}

		// id_rol si llega
		if (!is_null($id_rol) && $id_rol !== '') {
			$sets[] = "id_rol='".((int)$id_rol)."'";
		}

		$sql = "UPDATE usuario SET ".implode(",", $sets)." WHERE idusuario='$idusuario'";
		ejecutarConsulta($sql);

		/* PERMISOS:
		   - Si llegan $permisos explícitos => se aplican
		   - Si NO llegan y $modo_permisos='rol' y hay $id_rol => usar permisos del rol
		   - Si NO llegan y $modo_permisos está vacío => dejamos permisos como están (no tocamos tabla) */
		$aplicar = false;
		if (is_array($permisos) && count($permisos) > 0) {
			$aplicar = true;
		} elseif ($modo_permisos === 'rol' && !empty($id_rol)) {
			$permisos = $this->permisosDeRol($id_rol);
			$aplicar  = true;
		}

		if ($aplicar) {
			// limpiamos y aplicamos
			$sqldel = "DELETE FROM usuario_permiso WHERE idusuario='$idusuario'";
			ejecutarConsulta($sqldel);
			return $this->setPermisosUsuario($idusuario, $permisos);
		}

		// No se tocaron permisos
		return true;
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
		// Incluimos id_rol para que la UI pueda seleccionar por id
		$sql="SELECT u.*, r.nombre AS nombre_rol
		      FROM usuario u
		      LEFT JOIN rol_usuarios r ON u.id_rol = r.id_rol
		      WHERE u.idusuario='$idusuario'";
		return ejecutarConsultaSimpleFila($sql);
	}

	public function listar()
	{
		// Trae nombre del rol y deja compatibilidad con tipo_documento "texto"
		$sql="SELECT 
		        u.idusuario,
		        u.nombre,
		        COALESCE(td.nombre, u.tipo_documento) AS tipo_documento,
		        u.num_documento,
		        u.telefono,
		        u.email,
		        u.cargo,             -- texto visible (normalmente nombre del rol)
		        u.id_rol,            -- id del rol (para acciones)
		        u.imagen,
		        u.condicion,
		        r.nombre AS nombre_rol
		      FROM usuario u
		      LEFT JOIN rol_usuarios r   ON u.id_rol = r.id_rol
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
        $result = ejecutarConsultaSimpleFila($sql);
        if (!$result || !isset($result['total'])) return false;
        return ((int)$result['total'] > 0);
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
        $result = ejecutarConsultaSimpleFila($sql);
        if (!$result || !isset($result['total'])) return false;
        return ((int)$result['total'] > 0);
    }

	/* ===========================
	   NUEVOS HELPERS
	   =========================== */

	// Nombre del rol por id
	private function obtenerNombreRol($id_rol){
		$id_rol = (int)$id_rol;
		$sql = "SELECT nombre FROM rol_usuarios WHERE id_rol='$id_rol' LIMIT 1";
		$row = ejecutarConsultaSimpleFila($sql);
		return $row && isset($row['nombre']) ? $row['nombre'] : null;
	}

	// Permisos (ids) de un rol
	public function permisos_por_rol($id_rol){
		return $this->permisosDeRol($id_rol); // alias público para endpoint
	}

	private function permisosDeRol($id_rol){
		$id_rol = (int)$id_rol;
		$sql = "SELECT idpermiso FROM rol_permiso WHERE id_rol='$id_rol'";
		$rs = ejecutarConsulta($sql);
		$out = array();
		if ($rs) {
			while ($row = $rs->fetch_assoc()) {
				$out[] = (int)$row['idpermiso'];
			}
		}
		return $out;
	}

	// Reemplaza permisos de un usuario por el array dado
	private function setPermisosUsuario($idusuario, $permisos){
		$idusuario = (int)$idusuario;
		if (!is_array($permisos)) $permisos = array();

		$sw = true;
		// Limpia y aplica
		$sqldel="DELETE FROM usuario_permiso WHERE idusuario='$idusuario'";
		ejecutarConsulta($sqldel);

		for ($i=0; $i < count($permisos); $i++) {
			$pid = (int)$permisos[$i];
			$sql_detalle = "INSERT INTO usuario_permiso(idusuario, idpermiso) VALUES('$idusuario', '$pid')";
			ejecutarConsulta($sql_detalle) or $sw=false;
		}
		return $sw;
	}
}
?>
