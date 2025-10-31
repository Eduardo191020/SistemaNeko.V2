<?php
// ✅ Asegurar que cargue las constantes antes de usar PDO
require_once __DIR__ . '/../config/global.php';

$dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
$options = [
  PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
  $pdo = new PDO($dsn, DB_USERNAME, DB_PASSWORD, $options);
  $pdo->exec("SET NAMES " . DB_CHARSET . " COLLATE " . DB_COLLATION);
  $pdo->exec("SET time_zone = '-05:00'");
} catch (PDOException $e) {
  error_log('PDO connect error: '.$e->getMessage()); // log interno
  http_response_code(500);
  exit('⚠ No se pudo conectar a la base de datos.');
}
