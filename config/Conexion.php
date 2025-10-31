<?php
require_once __DIR__ . "/global.php";

$conexion = @new mysqli(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);

if ($conexion->connect_errno) {
    die("❌ Error de conexión ({$conexion->connect_errno}): {$conexion->connect_error}");
}

/* Charset: prioriza utf8mb4 si existe; si no, usa DB_ENCODE */
$charset = defined('DB_CHARSET') ? DB_CHARSET : DB_ENCODE;
$coll    = defined('DB_COLLATION') ? DB_COLLATION : 'utf8_general_ci';

if (!$conexion->set_charset($charset)) {
    $conexion->query("SET NAMES '$charset' COLLATE '$coll'");
}
$conexion->query("SET collation_connection = '$coll'");
$conexion->query("SET time_zone='-05:00'");

/* ===== Helpers legacy ===== */
if (!function_exists('ejecutarConsulta')) {

    function ejecutarConsulta($sql) {
        global $conexion;
        $res = $conexion->query($sql);
        if ($res === false) throw new Exception("SQL error: {$conexion->error}");
        return $res;
    }

    function ejecutarConsultaSimpleFila($sql) {
        $res = ejecutarConsulta($sql);
        return $res ? $res->fetch_assoc() : null;
    }

    function ejecutarConsulta_retornarID($sql) {
        global $conexion;
        $ok = $conexion->query($sql);
        if ($ok === false) throw new Exception("SQL error: {$conexion->error}");
        return $conexion->insert_id;
    }

    function limpiarCadena($str) {
        global $conexion;
        $str = $conexion->real_escape_string(trim((string)$str));
        return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
    }
}
