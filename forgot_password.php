<?php
// src/forgot_password.php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer_smtp.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');

  if ($email !== '') {
    // Verificar si el usuario existe y está activo
    $stmt = $pdo->prepare(
      'SELECT idusuario, nombre, email 
       FROM usuario 
       WHERE email = ? AND condicion = 1
       LIMIT 1'
    );
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if ($user) {
      $userId = (int)$user['idusuario'];
      $userName = trim((string)$user['nombre']);
      
      // Generar token único
      $token = bin2hex(random_bytes(32));
      $tokenHash = hash('sha256', $token);
      $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

      // Guardar token en la base de datos
      // Primero eliminar tokens antiguos del usuario
      $pdo->prepare('DELETE FROM password_reset WHERE user_id = ?')->execute([$userId]);
      
      // Insertar nuevo token
      $ins = $pdo->prepare(
        'INSERT INTO password_reset (user_id, token_hash, expires_at) 
         VALUES (?, ?, ?)'
      );
      $ins->execute([$userId, $tokenHash, $expires]);

      // Construir URL de reset
      $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
      $host = $_SERVER['HTTP_HOST'];
      $resetUrl = "{$protocol}://{$host}" . dirname($_SERVER['PHP_SELF']) . "/reset_password.php?token={$token}";

      // Enviar correo
      $mailOk = sendPasswordResetEmail($email, $userName, $resetUrl);

      if ($mailOk) {
        $message = 'Se ha enviado un enlace de recuperación a tu correo electrónico. Por favor revisa tu bandeja de entrada.';
      } else {
        $error = 'Error al enviar el correo. Por favor contacta al administrador.';
      }
    } else {
      // Por seguridad, mostramos el mismo mensaje aunque el usuario no exista
      $message = 'Se ha enviado un enlace de recuperación a tu correo electrónico. Por favor revisa tu bandeja de entrada.';
    }
  } else {
    $error = 'Por favor ingresa tu correo electrónico.';
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Recuperar Contraseña - Neko SAC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="css/estilos.css?v=<?= time() ?>">
</head>
<body class="auth-body">
  <div class="auth-wrapper">
    <section class="auth-card">
      <div class="auth-left">
        <div class="brand-wrap">
          <img src="assets/logo.png" alt="Logo Empresa" class="brand-logo">
          <h1 class="brand-title">Recupera tu cuenta</h1>
          <p class="brand-sub">Te enviaremos un enlace para restablecer tu contraseña</p>
          <a class="btn btn-outline" href="login.php">Volver al Login</a>
        </div>
      </div>

      <div class="auth-right">
        <h2 class="auth-title">¿Olvidaste tu contraseña?</h2>
        <p class="auth-subtitle">Ingresa tu correo electrónico y te enviaremos un enlace para recuperar tu cuenta.</p>

        <?php if ($message): ?>
          <div class="alert alert-success"><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="forgot_password.php" class="auth-form" autocomplete="off" novalidate>
          <label class="field">
            <span class="field-label">Correo electrónico</span>
            <div class="input">
              <input type="email" name="email" placeholder="tucorreo@empresa.com" required autocomplete="email">
              <span class="icon">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                  <path d="M20 4H4c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                </svg>
              </span>
            </div>
          </label>

          <button type="submit" class="btn btn-primary w-full">Enviar enlace de recuperación</button>

          <p class="small text-center m-top">
            ¿Recordaste tu contraseña? <a href="login.php" class="link-strong">Inicia sesión</a>
          </p>
        </form>
      </div>
    </section>
  </div>
</body>
</html>