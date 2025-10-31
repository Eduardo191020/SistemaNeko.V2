<?php
// src/reset_password.php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$error = '';
$message = '';
$validToken = false;
$userId = null;

// Verificar token
if (isset($_GET['token'])) {
  $token = $_GET['token'];
  $tokenHash = hash('sha256', $token);

  // Buscar token válido y no expirado
  $stmt = $pdo->prepare(
    'SELECT pr.user_id, u.nombre, u.email 
     FROM password_reset pr
     INNER JOIN usuario u ON pr.user_id = u.idusuario
     WHERE pr.token_hash = ? 
       AND pr.expires_at > NOW() 
       AND pr.used = 0
       AND u.condicion = 1
     LIMIT 1'
  );
  $stmt->execute([$tokenHash]);
  $tokenData = $stmt->fetch();

  if ($tokenData) {
    $validToken = true;
    $userId = (int)$tokenData['user_id'];
  } else {
    $error = 'El enlace de recuperación es inválido o ha expirado. Por favor solicita uno nuevo.';
  }
}

// Procesar cambio de contraseña
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['token'])) {
  $token = $_POST['token'];
  $newPassword = $_POST['password'] ?? '';
  $confirmPassword = $_POST['confirm_password'] ?? '';

  if ($newPassword === '' || $confirmPassword === '') {
    $error = 'Por favor completa todos los campos.';
  } elseif ($newPassword !== $confirmPassword) {
    $error = 'Las contraseñas no coinciden.';
  } elseif (strlen($newPassword) < 6) {
    $error = 'La contraseña debe tener al menos 6 caracteres.';
  } else {
    // Verificar token nuevamente
    $tokenHash = hash('sha256', $token);
    $stmt = $pdo->prepare(
      'SELECT user_id 
       FROM password_reset 
       WHERE token_hash = ? 
         AND expires_at > NOW() 
         AND used = 0
       LIMIT 1'
    );
    $stmt->execute([$tokenHash]);
    $tokenData = $stmt->fetch();

    if ($tokenData) {
      $userId = (int)$tokenData['user_id'];
      
      // Actualizar contraseña (sha256 como en tu sistema)
      $newPasswordHash = hash('sha256', $newPassword);
      $updateStmt = $pdo->prepare('UPDATE usuario SET clave = ? WHERE idusuario = ?');
      $updateStmt->execute([$newPasswordHash, $userId]);

      // Marcar token como usado
      $markUsed = $pdo->prepare('UPDATE password_reset SET used = 1 WHERE token_hash = ?');
      $markUsed->execute([$tokenHash]);

      // Eliminar todos los tokens del usuario
      $pdo->prepare('DELETE FROM password_reset WHERE user_id = ?')->execute([$userId]);

      $message = 'Tu contraseña ha sido actualizada correctamente. Ahora puedes iniciar sesión.';
      $validToken = false; // Ocultar formulario
    } else {
      $error = 'El enlace de recuperación es inválido o ha expirado.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Restablecer Contraseña - Neko SAC</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="css/estilos.css?v=<?= time() ?>">
</head>
<body class="auth-body">
  <div class="auth-wrapper">
    <section class="auth-card">
      <div class="auth-left">
        <div class="brand-wrap">
          <img src="assets/logo.png" alt="Logo Empresa" class="brand-logo">
          <h1 class="brand-title">Nueva contraseña</h1>
          <p class="brand-sub">Ingresa tu nueva contraseña segura</p>
          <a class="btn btn-outline" href="login.php">Volver al Login</a>
        </div>
      </div>

      <div class="auth-right">
        <h2 class="auth-title">Restablecer contraseña</h2>

        <?php if ($message): ?>
          <div class="alert alert-success">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
            <br><br>
            <a href="login.php" class="btn btn-primary w-full">Ir al Login</a>
          </div>
        <?php endif; ?>

        <?php if ($error): ?>
          <div class="alert alert-error">
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            <?php if (!$validToken): ?>
              <br><br>
              <a href="forgot_password.php" class="btn btn-primary w-full">Solicitar nuevo enlace</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <?php if ($validToken && !$message): ?>
          <form method="post" action="reset_password.php" class="auth-form" autocomplete="off" novalidate>
            <input type="hidden" name="token" value="<?= htmlspecialchars($_GET['token'], ENT_QUOTES, 'UTF-8') ?>">

            <label class="field">
              <span class="field-label">Nueva contraseña</span>
              <div class="input">
                <input type="password" name="password" placeholder="••••••••" required minlength="6" autocomplete="new-password">
                <span class="icon">
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-7-2a2 2 0 0 1 4 0v2H10Zm7 12H7v-8h10Z"/>
                  </svg>
                </span>
              </div>
              <small class="field-hint">Mínimo 6 caracteres</small>
            </label>

            <label class="field">
              <span class="field-label">Confirmar contraseña</span>
              <div class="input">
                <input type="password" name="confirm_password" placeholder="••••••••" required minlength="6" autocomplete="new-password">
                <span class="icon">
                  <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M17 9h-1V7a4 4 0 0 0-8 0v2H7a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-8a2 2 0 0 0-2-2Zm-7-2a2 2 0 0 1 4 0v2H10Zm7 12H7v-8h10Z"/>
                  </svg>
                </span>
              </div>
            </label>

            <button type="submit" class="btn btn-primary w-full">Restablecer contraseña</button>
          </form>
        <?php endif; ?>
      </div>
    </section>
  </div>
</body>
</html>