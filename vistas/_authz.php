<?php
// vistas/_authz.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

/**
 * can('Ventas') o can('ventas')
 * Usa el mapa $_SESSION['perms'] que seteamos en verify.php
 * y además acepta los flags antiguos de compatibilidad.
 */
function can(string $permName): bool {
  $k1 = $permName;
  $k2 = mb_strtolower($k1, 'UTF-8');

  // Nuevo: mapa por nombre
  if (!empty($_SESSION['perms'])) {
    if (!empty($_SESSION['perms'][$k1]) || !empty($_SESSION['perms'][$k2])) {
      return true;
    }
  }

  // Compatibilidad con flags antiguos
  $legacy = [
    'Escritorio'        => 'escritorio',
    'Almacen'           => 'almacen',
    'Compras'           => 'compras',
    'Ventas'            => 'ventas',
    'Acceso'            => 'acceso',
    'Consulta Compras'  => 'consultac',
    'Consulta Ventas'   => 'consultav',
  ];
  if (isset($legacy[$k1])) {
    $flag = $legacy[$k1];
  } elseif (isset($legacy[ucfirst($k2)])) {
    $flag = $legacy[ucfirst($k2)];
  } else {
    $flag = $k2; // último intento
  }
  return !empty($_SESSION[$flag]) && (int)$_SESSION[$flag] === 1;
}
