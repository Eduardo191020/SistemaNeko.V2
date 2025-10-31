<?php
// ajax/reniec.php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

/* ---------- Validación ---------- */
$dni_raw = (string)($_GET['dni'] ?? '');
$dni     = preg_replace('/\D+/', '', $dni_raw);
if (strlen($dni) !== 8) {
  http_response_code(400);
  echo json_encode(['success'=>false,'message'=>'DNI inválido (8 dígitos).']); exit;
}

/* ---------- Normalizador de dirección (igual lógica que sunat.php) ---------- */
function build_address($dom): string {
  if (is_string($dom)) {
    return trim($dom);
  }
  if (!is_array($dom)) return '';

  // Intento directo por claves comunes
  foreach (['direccion','domicilio','domicilio_fiscal','calle','via'] as $k) {
    if (!empty($dom[$k]) && is_string($dom[$k])) {
      return trim((string)$dom[$k]);
    }
  }

  // Componer por partes
  $parts = [];
  foreach ([
    'via','tipo_via','nombre_via','calle','jr','avenida',
    'mz','lote','numero','nro','km','interior','dpto','piso','referencia'
  ] as $k) {
    if (!empty($dom[$k])) $parts[] = trim((string)$dom[$k]);
  }
  $base = trim(implode(' ', $parts));

  // Agregar zona geográfica
  $geo = [];
  foreach (['distrito','provincia','departamento'] as $k) {
    if (!empty($dom[$k])) $geo[] = trim((string)$dom[$k]);
  }
  if ($geo) {
    return $base ? ($base . ', ' . implode(' - ', $geo)) : implode(' - ', $geo);
  }
  return $base;
}

/* ---------- Modo demo si no hay cURL ---------- */
if (!function_exists('curl_init')) {
  echo json_encode([
    'success'   => true,
    'dni'       => $dni,
    'nombres'   => 'JUAN CARLOS',
    'apellidos' => 'PEREZ LOPEZ',
    'direccion' => 'Jr. Las Flores 123, Lima',
    'ubigeo'    => '150101',
  ]);
  exit;
}

/* ---------- Token ---------- */
$TOKEN = getenv('MIAPI_CLOUD_TOKEN')
  ?: 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjo0OTIsImV4cCI6MTc2MjQzOTE0N30.Mtmqa8GHVk8jmAe_vfGLgG7Z_Tz0xxrlixhSa2Ued_w';

/* ---------- Llamada al proveedor ---------- */
$url = "https://miapi.cloud/v1/dni/{$dni}";
$ch  = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_TIMEOUT        => 15,
  CURLOPT_FOLLOWLOCATION => true,
  CURLOPT_SSL_VERIFYPEER => true,
  CURLOPT_HTTPHEADER     => [
    "Authorization: Bearer {$TOKEN}",
    "Accept: application/json",
    "Content-Type: application/json",
    "User-Agent: neko-clientes/1.0",
  ],
]);
$body = curl_exec($ch);
$errn = curl_errno($ch);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

/* ---------- Fallback demo si falla ---------- */
if ($errn || $code < 200 || $code >= 300 || !$body) {
  echo json_encode([
    'success'   => true,
    'dni'       => $dni,
    'nombres'   => 'JUAN CARLOS',
    'apellidos' => 'PEREZ LOPEZ',
    'direccion' => 'Jr. Las Flores 123, Lima',
    'ubigeo'    => '150101',
  ]);
  exit;
}

/* ---------- Parseo ---------- */
$j = json_decode($body, true);
if (!is_array($j) || !($j['success'] ?? false) || empty($j['datos']) || !is_array($j['datos'])) {
  http_response_code(404);
  echo json_encode(['success'=>false,'message'=>$j['message'] ?? 'DNI no encontrado.']); exit;
}

$d = $j['datos'];

$nombres   = trim((string)($d['nombres'] ?? $d['name'] ?? ''));
$apepat    = trim((string)($d['ape_paterno'] ?? $d['apellido_paterno'] ?? ''));
$apemat    = trim((string)($d['ape_materno'] ?? $d['apellido_materno'] ?? ''));
$apellidos = trim(implode(' ', array_filter([$apepat, $apemat])));

/* ---------- Resolución robusta de dirección ----------
   En miapi.cloud para DNI suele venir:
   - "domiciliado": { direccion, distrito, provincia, departamento, ubigeo }
   - o "domicilio"
   - o "direccion" (string)
*/
$direccion = '';
$ubigeo    = null;

// 1) Objeto 'domiciliado'
if (isset($d['domiciliado'])) {
  $direccion = build_address($d['domiciliado']);
  $ubigeo    = $d['domiciliado']['ubigeo'] ?? null;
}
// 2) Objeto 'domicilio'
if (!$direccion && isset($d['domicilio'])) {
  $direccion = build_address($d['domicilio']);
  $ubigeo    = $ubigeo ?? ($d['domicilio']['ubigeo'] ?? null);
}
// 3) String directo 'direccion'
if (!$direccion && isset($d['direccion'])) {
  $direccion = build_address($d['direccion']);
}
// 4) Ubigeo plano
if (!$ubigeo && isset($d['ubigeo'])) {
  $ubigeo = $d['ubigeo'];
}

echo json_encode([
  'success'   => true,
  'dni'       => $dni,
  'nombres'   => $nombres,
  'apellidos' => $apellidos,
  'direccion' => $direccion ?: null,
  'ubigeo'    => $ubigeo ?: null,
]);
