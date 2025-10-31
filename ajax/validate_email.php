<?php
// ajax/validate_email.php - CON API EXTERNA
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

// DEBUG
error_log('=== VALIDATE EMAIL API ===');

$email = trim($_GET['email'] ?? '');

if (empty($email)) {
    echo json_encode(['success' => false, 'valid' => false, 'message' => 'Email vacío']);
    exit;
}

// 1. Validación de formato básico
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'valid' => false,
        'message' => 'El formato del correo no es válido'
    ]);
    exit;
}

error_log("Validando email: $email");

// 2. OPCIÓN A: AbstractAPI (100 gratis/mes)
// Regístrate en: https://app.abstractapi.com/users/signup
// Obtén tu API key gratis
$ABSTRACT_API_KEY = 'd14097ba1a3e48d585e0f0a395deab55'; // ⭐ REEMPLAZA ESTO

// 3. OPCIÓN B: Validación local mejorada (si no tienes API key)
$usar_api = !empty($ABSTRACT_API_KEY) && $ABSTRACT_API_KEY !== 'd14097ba1a3e48d585e0f0a395deab55';

if ($usar_api) {
    // ========== VALIDACIÓN CON API EXTERNA ==========
    $api_url = "https://emailvalidation.abstractapi.com/v1/?api_key={$ABSTRACT_API_KEY}&email=" . urlencode($email);
    
    $ch = curl_init($api_url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code === 200 && $response) {
        $data = json_decode($response, true);
        
        error_log("Respuesta API: " . print_r($data, true));
        
        // Analizar respuesta de AbstractAPI
        $is_valid_format = $data['is_valid_format']['value'] ?? false;
        $is_mx_found = $data['is_mx_found']['value'] ?? false;
        $is_smtp_valid = $data['is_smtp_valid']['value'] ?? false;
        $is_disposable = $data['is_disposable_email']['value'] ?? false;
        $is_free_email = $data['is_free_email']['value'] ?? false;
        $quality_score = $data['quality_score'] ?? 0;
        
        // Validaciones
        if (!$is_valid_format) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'El formato del correo no es válido'
            ]);
            exit;
        }
        
        if ($is_disposable) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'No se permiten correos temporales o desechables'
            ]);
            exit;
        }
        
        if (!$is_mx_found) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'El dominio del correo no existe o no puede recibir emails'
            ]);
            exit;
        }
        
        // Si el quality_score es muy bajo, rechazar
        if ($quality_score < 0.5) {
            echo json_encode([
                'success' => false,
                'valid' => false,
                'message' => 'El correo no parece ser válido o confiable'
            ]);
            exit;
        }
        
        // ✅ Email válido
        echo json_encode([
            'success' => true,
            'valid' => true,
            'verified' => $is_smtp_valid,
            'is_free' => $is_free_email,
            'quality_score' => $quality_score,
            'message' => 'Correo verificado como válido'
        ]);
        exit;
        
    } else {
        error_log("Error API: HTTP $http_code - $response");
        // Si falla la API, usar validación local
    }
}

// ========== VALIDACIÓN LOCAL (FALLBACK O SI NO HAY API) ==========

// Extraer dominio
$parts = explode('@', $email);
if (count($parts) !== 2) {
    echo json_encode([
        'success' => false,
        'valid' => false,
        'message' => 'El correo no tiene un formato válido'
    ]);
    exit;
}

$local = strtolower($parts[0]);
$domain = strtolower($parts[1]);

// Lista de dominios desechables
$disposable_domains = [
    'tempmail.com', 'guerrillamail.com', '10minutemail.com', 
    'throwaway.email', 'mailinator.com', 'trashmail.com', 
    'yopmail.com', 'maildrop.cc', 'temp-mail.org',
    'fakeinbox.com', 'sharklasers.com'
];

if (in_array($domain, $disposable_domains)) {
    echo json_encode([
        'success' => false,
        'valid' => false,
        'message' => 'No se permiten correos temporales o desechables'
    ]);
    exit;
}

// Verificar DNS (con bypass para dominios conocidos)
$dominios_conocidos = ['gmail.com', 'hotmail.com', 'outlook.com', 'yahoo.com', 'icloud.com', 'live.com', 'msn.com'];
$es_dominio_conocido = in_array($domain, $dominios_conocidos);

if (!$es_dominio_conocido) {
    $has_mx = @checkdnsrr($domain, 'MX');
    $has_a = @checkdnsrr($domain, 'A');
    
    if (!$has_mx && !$has_a) {
        echo json_encode([
            'success' => false,
            'valid' => false,
            'message' => 'El dominio del correo no existe o no puede recibir emails'
        ]);
        exit;
    }
}

// Validaciones simples de patrón (solo casos muy obvios)
$patrones_invalidos = [
    '/^test\d*$/i',      // test, test123
    '/^user\d*$/i',      // user123
    '/^admin\d*$/i',     // admin123
    '/^demo\d*$/i',      // demo123
    '/^spam\d*$/i',      // spam123
    '/^(xxx|yyy|zzz){2,}/', // xxxyyy, yyyzzz
];

foreach ($patrones_invalidos as $patron) {
    if (preg_match($patron, $local)) {
        echo json_encode([
            'success' => false,
            'valid' => false,
            'message' => 'El correo parece ser de prueba o no válido'
        ]);
        exit;
    }
}

// ✅ Email válido (validación local)
echo json_encode([
    'success' => true,
    'valid' => true,
    'verified' => $es_dominio_conocido,
    'trusted' => $es_dominio_conocido,
    'message' => $es_dominio_conocido 
        ? 'Correo verificado como válido' 
        : 'Correo válido (dominio verificado)'
]);