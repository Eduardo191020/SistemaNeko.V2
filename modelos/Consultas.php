<?php 
require "../config/Conexion.php";

class Consultas
{
    public function __construct(){}

    /* Compras entre fechas (ok) */
    public function comprasfecha($fecha_inicio,$fecha_fin){
        $sql="SELECT DATE(i.fecha_hora) as fecha,u.nombre as usuario, p.nombre as proveedor,
                     i.tipo_comprobante,i.serie_comprobante,i.num_comprobante,i.total_compra,
                     i.impuesto,i.estado
              FROM ingreso i
              INNER JOIN persona p ON i.idproveedor=p.idpersona
              INNER JOIN usuario u ON i.idusuario=u.idusuario
              WHERE DATE(i.fecha_hora)>='$fecha_inicio' 
                AND DATE(i.fecha_hora)<='$fecha_fin'";
        return ejecutarConsulta($sql);
    }

    /* Ventas por fecha y cliente (ok) */
    public function ventasfechacliente($fecha_inicio,$fecha_fin,$idcliente){
        $sql="SELECT DATE(v.fecha_hora) as fecha,u.nombre as usuario, p.nombre as cliente,
                     v.tipo_comprobante,v.serie_comprobante,v.num_comprobante,v.total_venta,
                     v.impuesto,v.estado
              FROM venta v
              INNER JOIN persona p ON v.idcliente=p.idpersona
              INNER JOIN usuario u ON v.idusuario=u.idusuario
              WHERE DATE(v.fecha_hora)>='$fecha_inicio' 
                AND DATE(v.fecha_hora)<='$fecha_fin' 
                AND v.idcliente='$idcliente'";
        return ejecutarConsulta($sql);
    }

    public function totalcomprahoy(){
        $sql="SELECT IFNULL(SUM(total_compra),0) as total_compra 
              FROM ingreso 
              WHERE DATE(fecha_hora)=CURDATE()";
        return ejecutarConsulta($sql);
    }

    public function totalventahoy(){
        $sql="SELECT IFNULL(SUM(total_venta),0) as total_venta 
              FROM venta 
              WHERE DATE(fecha_hora)=CURDATE()";
        return ejecutarConsulta($sql);
    }

    /* ---------- FIX 1: últimos 10 días (agrupar por DÍA y ordenar asc) ---------- */
    public function comprasultimos_10dias(){
        $sql="SELECT DATE_FORMAT(DATE(i.fecha_hora),'%d-%m') AS fecha,
                     SUM(i.total_compra) AS total
              FROM ingreso i
              WHERE DATE(i.fecha_hora) >= DATE_SUB(CURDATE(), INTERVAL 9 DAY)
              GROUP BY DATE(i.fecha_hora)
              ORDER BY DATE(i.fecha_hora) ASC";
        return ejecutarConsulta($sql);
    }

    /* ---------- FIX 2: últimos 12 meses (año+mes, orden asc, 12 filas) ---------- */
    public function ventasultimos_12meses(){
        // Opción A: respetando el locale de MySQL (recomendada)
        $sql="SELECT DATE_FORMAT(v.fecha_hora,'%M') AS fecha,
                     SUM(v.total_venta) AS total
              FROM venta v
              WHERE v.fecha_hora >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
              GROUP BY YEAR(v.fecha_hora), MONTH(v.fecha_hora)
              ORDER BY YEAR(v.fecha_hora), MONTH(v.fecha_hora) ASC
              LIMIT 12";
        return ejecutarConsulta($sql); 

		    }
}
?>
