<?php
// src/login.php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/mailer_smtp.php'; // PHPMailer (SMTP)

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $identity = trim($_POST['email'] ?? '');   // puede ser email o num_documento
  $password = (string)($_POST['password'] ?? '');

  if ($identity === '' || $password === '') {
    $error = 'Usuario o contraseña incorrectos';
  } else {
    // Buscar por email o por num_documento (no usamos "login" porque no existe esa columna)

$sql = '
  SELECT 
    u.idusuario       AS id_usuario,
    u.nombre          AS nombre,
    u.email,
    u.clave,          
    u.imagen,
    u.condicion       AS estado_usuario,
    u.id_tipodoc,
    u.num_documento,
    r.id_rol          AS id_rol,
    r.nombre          AS nombre_rol,
    r.estado          AS estado_rol
  FROM usuario u
  INNER JOIN rol_usuarios r ON u.id_rol = r.id_rol
  WHERE u.email = ?
     OR u.num_documento = ?
  LIMIT 1
';


    $stmt = $pdo->prepare($sql);
    $stmt->execute([$identity, $identity]);
    $user = $stmt->fetch();

    // Validaciones de estado y existencia
if (!$user) {
  $error = 'Usuario o contraseña incorrectos';
} 
elseif ((int)$user['estado_usuario'] == 0) {
  $error = 'Usuario inactivo. Contacte al administrador.';
}elseif ((int)$user['estado_usuario'] === 3) {
    $error = 'Tu cuenta está pendiente de aprobación. Espera la activación por parte del administrador.';
}elseif ((int)$user['estado_rol'] == 0) {
  $error = 'Rol desactivado. Contacte al administrador del sistema.';
}
else {
  // continúa con la verificación de contraseña y OTP...

      $hashDb = (string)$user['clave'];
      $userId = (int)$user['id_usuario'];
      $email  = (string)$user['email'];
      $name   = trim((string)$user['nombre']);

      $ok = false;

      // 1) Intentar verificar como bcrypt/password_hash
      $info = password_get_info($hashDb);
      if (!empty($info['algo'])) {
        // Es un hash de password_hash()
        $ok = password_verify($password, $hashDb);

        // Si verifica y necesita rehash (cambiar cost), actualizar
        if ($ok && password_needs_rehash($hashDb, PASSWORD_BCRYPT)) {
          $newHash = password_hash($password, PASSWORD_BCRYPT);
          $upd = $pdo->prepare('UPDATE usuario SET clave = ? WHERE idusuario = ?');
          $upd->execute([$newHash, $userId]);
        }
      } else {
        // 2) Compatibilidad: base antigua con SHA-256 (hex)
        // Compara de forma constante e insensible a mayúsculas/minúsculas de hex
        $inputSha = hash('sha256', $password);
        $ok = hash_equals(strtolower($hashDb), strtolower($inputSha));

        // Si coincide, migramos automáticamente a bcrypt
        if ($ok) {
          $newHash = password_hash($password, PASSWORD_BCRYPT);
          $upd = $pdo->prepare('UPDATE usuario SET clave = ? WHERE idusuario = ?');
          $upd->execute([$newHash, $userId]);
        }
      }

      if ($ok) {
        // === Generar OTP y enviarlo por correo ===
        try {
          $otp      = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
          $otpHash  = hash('sha256', $otp);
          $expires  = (new DateTime('+10 minutes'))->format('Y-m-d H:i:s');

          // Limpiar OTPs previas
          $pdo->prepare('DELETE FROM user_otp WHERE user_id = ?')->execute([$userId]);

          // Guardar OTP
          $ins = $pdo->prepare('INSERT INTO user_otp (user_id, code_hash, expires_at) VALUES (?, ?, ?)');
          $ins->execute([$userId, $otpHash, $expires]);

          // Enviar correo
          $mailOk = sendAuthCode($email, $otp);
          if (!$mailOk) {
            $error = 'No se pudo enviar el correo: revisa tu configuración SMTP en includes/mailer_smtp.php';
          } else {
            $_SESSION['otp_uid']    = $userId;
            $_SESSION['otp_name']   = $name;
            $_SESSION['otp_email']  = $email;
            $_SESSION['otp_sent']   = time();
            $_SESSION['imagen'] = $user['imagen'];
            header('Location: verify.php');
            exit;
          }
        } catch (Throwable $e) {
          $error = 'No se pudo generar/enviar el código de verificación. Inténtalo nuevamente.';
        }
      } else {
        $error = 'Usuario o contraseña incorrectos';
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Login - Neko SAC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="css/estilos.css?v=<?= time() ?>">
</head>
<body class="auth-body">
  <div class="auth-wrapper">
    <section class="auth-card">
      <div class="auth-left">
        <div class="brand-wrap">
          <img src="assets/logo.png" alt="Logo Empresa" class="brand-logo">
          <h1 class="brand-title">Hola, ¡bienvenido!</h1>
          <p class="brand-sub">¿No tienes una cuenta?</p>
          <a class="btn btn-outline" href="register.php">Register</a>
        </div>
      </div>

      <div class="auth-right">
        <h2 class="auth-title">Login</h2>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" class="auth-form" autocomplete="off" novalidate>
          <label class="field">
            <span class="field-label">Email o N° de documento</span>
            <div class="input">
              <input type="text" name="email" placeholder="tucorreo@empresa.com o tu N° de documento" required autocomplete="username">
              <span class="icon">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 12a5 5 0 1 0-5-5 5 5 0 0 0 5 5Zm0 2c-4.08 0-8 2.06-8 5v1h16v-1c0-2.94-3.92-5-8-5Z"/></svg>
              </span>
            </div>
          </label>

          <label class="field">
            <span class="field-label">Contraseña</span>
            <div class="input">
              <input type="password" name="password" placeholder="••••••••" required autocomplete="current-password">
              <span class="icon">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-7-2a2 2 0 0 1 4 0v2H10Zm7 12H7v-8h10Z"/></svg>
              </span>
            </div>
          </label>

          <div class="row-between">
            <a class="link-muted" href="forgot_password.php">¿Olvidaste tu contraseña?</a>
          </div>

          <button type="submit" class="btn btn-primary w-full">Login</button>

          <p class="small text-center m-top">
            ¿No tienes cuenta? <a href="register.php" class="link-strong">Regístrate</a>
          </p>
        </form>
      </div>
    </section>
  </div>
</body>
</html>
