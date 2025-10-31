<?php
// Arranca sesión si no está activa
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Validar login correcto (nuevo sistema)
if (empty($_SESSION['idusuario'])) {
    header('Location: ../login.php'); // ✅ YA NO login.html
    exit;
}

// Evitar errores por índices faltantes
$_SESSION['nombre'] = $_SESSION['nombre'] ?? 'Usuario';
$_SESSION['imagen'] = $_SESSION['imagen'] ?? 'default.png';

// Activar flags mínimos si aún no se cargaron desde verify.php
foreach (['escritorio','almacen','compras','ventas','acceso','consultac','consultav'] as $flag) {
    if (!isset($_SESSION[$flag])) {
        $_SESSION[$flag] = 0; // simplemente inicializar
    }
}
