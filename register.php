<?php
// src/register.php
declare(strict_types=1);

require_once __DIR__ . '/includes/db.php';

$error = '';
$success = '';

function validar_dni(string $doc): bool { return (bool)preg_match('/^[0-9]{8}$/', $doc); }
function validar_ruc(string $doc): bool {
  if (!preg_match('/^[0-9]{11}$/', $doc)) return false;
  $factors = [5,4,3,2,7,6,5,4,3,2];
  $sum = 0;
  for ($i=0; $i<10; $i++) { $sum += ((int)$doc[$i]) * $factors[$i]; }
  $resto  = $sum % 11;
  $digito = 11 - $resto;
  if ($digito === 10) $digito = 0;
  elseif ($digito === 11) $digito = 1;
  return $digito === (int)$doc[10];
}
function validar_pasaporte(string $doc): bool { return (bool)preg_match('/^[A-Za-z0-9]{9,12}$/', $doc); }

function validar_patron_email(string $email): ?string {
  $partes = explode('@', $email);
  if (count($partes) !== 2) return 'Formato de email inválido';
  
  $local = strtolower($partes[0]);
  
  if (strlen($local) < 3) {
    return 'El nombre de usuario es demasiado corto (mín. 3 caracteres)';
  }
  
  if (preg_match('/(.)\1{3,}/', $local)) {
    return 'El correo contiene caracteres repetitivos sospechosos';
  }
  
  $patrones_sospechosos = [
    '/^[a-z]{3}\d{3,}$/',
    '/^[a-z]{6,}$/',
    '/^\d{4,}$/',
    '/^(x+|y+|z+|a+)(x+|y+|z+|a+)/',
    '/^test\d*$/',
    '/^user\d*$/',
    '/^admin\d*$/',
    '/^demo\d*$/',
    '/^(abc|xyz|qwe|asd|zxc)\d*$/',
  ];
  
  foreach ($patrones_sospechosos as $patron) {
    if (preg_match($patron, $local)) {
      return 'El correo parece ser generado aleatoriamente o de prueba';
    }
  }
  
  $tiene_numeros = preg_match('/[0-9]/', $local);
  $tiene_simbolos = preg_match('/[._\-]/', $local);
  
  if (!$tiene_numeros && !$tiene_simbolos) {
    $vocales = preg_match_all('/[aeiou]/', $local);
    $total_letras = strlen(preg_replace('/[^a-z]/', '', $local));
    
    if ($total_letras > 8 && $vocales === 0) {
      return 'El correo parece no ser válido (sin vocales)';
    }
    
    $caracteres_unicos = count(array_unique(str_split($local)));
    if (strlen($local) > 6 && $caracteres_unicos <= 3) {
      return 'El correo tiene un patrón demasiado repetitivo';
    }
  }
  
  for ($i = 0; $i < strlen($local) - 3; $i++) {
    $seq = substr($local, $i, 4);
    if (preg_match('/^[a-z]+$/', $seq)) {
      $consecutivos = true;
      for ($j = 1; $j < strlen($seq); $j++) {
        if (ord($seq[$j]) !== ord($seq[$j-1]) + 1) {
          $consecutivos = false;
          break;
        }
      }
      if ($consecutivos) {
        return 'El correo contiene secuencias alfabéticas sospechosas';
      }
    }
    if (preg_match('/^\d+$/', $seq)) {
      $consecutivos = true;
      for ($j = 1; $j < strlen($seq); $j++) {
        if ((int)$seq[$j] !== (int)$seq[$j-1] + 1) {
          $consecutivos = false;
          break;
        }
      }
      if ($consecutivos) {
        return 'El correo contiene secuencias numéricas sospechosas';
      }
    }
  }
  
  $nombres_falsos = [
    'asdasd', 'asdfgh', 'qwerty', 'qwertyui', 'zxcvbn',
    'testtest', 'test123', 'prueba', 'ejemplo',
    'xxxyyy', 'aaabbb', 'noname', 'random', 'fake',
    'temporal', 'temp', 'basura', 'spam'
  ];
  
  foreach ($nombres_falsos as $falso) {
    if (strpos($local, $falso) !== false) {
      return 'El correo parece ser temporal o de prueba';
    }
  }
  
  return null;
}

function validar_email_real(string $email): ?string {
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    return 'El formato del correo no es válido.';
  }

  $error_patron = validar_patron_email($email);
  if ($error_patron !== null) {
    return $error_patron;
  }

  $parts = explode('@', $email);
  if (count($parts) !== 2) return 'El correo no tiene un formato válido.';
  $domain = $parts[1];

  if (!checkdnsrr($domain, 'MX') && !checkdnsrr($domain, 'A')) {
    return 'El dominio del correo no existe o no puede recibir emails.';
  }

  $disposable = [
    'tempmail.com', 'guerrillamail.com', '10minutemail.com', 'throwaway.email',
    'mailinator.com', 'trashmail.com', 'yopmail.com', 'maildrop.cc',
    'temp-mail.org', 'fakeinbox.com', 'sharklasers.com', 'guerrillamailblock.com',
    'pokemail.net', 'spam4.me', 'grr.la', 'dispostable.com',
    'tempinbox.com', 'minuteinbox.com', 'emailondeck.com',
    'mytemp.email', 'mohmal.com', 'moakt.com'
  ];
  if (in_array(strtolower($domain), $disposable, true)) {
    return 'No se permiten correos temporales o desechables.';
  }

  return null;
}

function validar_password_robusta(
  string $pwd,
  string $email = '',
  string $nombres = '',
  string $apellidos = ''
): ?string {
  if (strlen($pwd) < 10 || strlen($pwd) > 64) return 'La contraseña debe tener entre 10 y 64 caracteres.';
  if (preg_match('/\s/', $pwd)) return 'La contraseña no debe contener espacios.';
  if (!preg_match('/[A-Z]/', $pwd)) return 'Debe incluir al menos una letra mayúscula (A-Z).';
  if (!preg_match('/[a-z]/', $pwd)) return 'Debe incluir al menos una letra minúscula (a-z).';
  if (!preg_match('/[0-9]/', $pwd)) return 'Debe incluir al menos un dígito (0-9).';
  if (!preg_match('/[!@#$%^&*()_\+\=\-\[\]{};:,.?]/', $pwd)) return 'Debe incluir al menos un caracter especial: !@#$%^&*()_+=-[]{};:,.?';

  $lowerPwd = mb_strtolower($pwd, 'UTF-8');
  $prohibidos = [];
  
  if ($email) { 
    $local = mb_strtolower((string)strtok($email, '@'), 'UTF-8'); 
    if ($local) $prohibidos[] = $local; 
  }
  
  foreach (preg_split('/\s+/', trim($nombres . ' ' . $apellidos)) as $pieza) {
    $pieza = mb_strtolower($pieza, 'UTF-8');
    if (mb_strlen($pieza, 'UTF-8') >= 4) $prohibidos[] = $pieza;
  }
  
  foreach ($prohibidos as $p) {
    if ($p !== '' && mb_strpos($lowerPwd, $p, 0, 'UTF-8') !== false) {
      return 'No debe contener partes de tu correo, nombres o apellidos.';
    }
  }

  $comunes = ['123456','123456789','12345678','12345','qwerty','password','111111','abc123','123123','iloveyou','admin','welcome','monkey','dragon','qwertyuiop','000000'];
  if (in_array(mb_strtolower($pwd, 'UTF-8'), $comunes, true)) return 'La contraseña es demasiado común. Elige otra.';
  return null;
}

// Catálogos
$tiposDoc = $pdo->query('SELECT id_tipodoc, nombre FROM tipo_documento ORDER BY id_tipodoc')->fetchAll();
$roles = $pdo->query('SELECT id_rol, nombre FROM rol_usuarios WHERE estado = 1 AND id_rol != 1 ORDER BY id_rol')->fetchAll();

// Valores del form
$id_tipodoc    = (int)($_POST['id_tipodoc'] ?? 0);
$id_rol        = (int)($_POST['id_rol'] ?? 0);
$nro_documento = trim($_POST['nro_documento'] ?? '');
$nombres       = trim($_POST['nombres'] ?? '');
$apellidos     = trim($_POST['apellidos'] ?? '');
$empresa       = trim($_POST['empresa'] ?? '');
$email         = trim($_POST['email'] ?? '');
$telefono      = trim($_POST['telefono'] ?? '');
$direccion     = trim($_POST['direccion'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $password = $_POST['password'] ?? '';
  $confirm  = $_POST['confirm'] ?? '';

  if (!$id_tipodoc || !$id_rol || !$nro_documento || !$email || !$password || !$confirm) {
    $error = 'Todos los campos obligatorios deben ser completados.';
  } 
  elseif ($id_rol === 1) {
    $error = 'Acceso denegado. No puedes registrarte con ese rol.';
  }
  else {
    $checkRol = $pdo->prepare('SELECT 1 FROM rol_usuarios WHERE id_rol = ? AND estado = 1 AND id_rol != 1 LIMIT 1');
    $checkRol->execute([$id_rol]);
    if (!$checkRol->fetch()) {
      $error = 'El rol seleccionado no es válido o no está disponible para registro público.';
    }
  }

  if ($error === '') {
    $emailError = validar_email_real($email);
    if ($emailError !== null) {
      $error = $emailError;
    } elseif ($password !== $confirm) {
      $error = 'Las contraseñas no coinciden.';
    } else {
      $okDoc = false;
      if     ($id_tipodoc === 1) $okDoc = validar_dni($nro_documento);
      elseif ($id_tipodoc === 2) $okDoc = validar_ruc($nro_documento);
      elseif ($id_tipodoc === 3) $okDoc = validar_pasaporte($nro_documento);

      if (!$okDoc) {
        $error = 'Número de documento inválido para el tipo seleccionado.';
      } else {
        if ($id_tipodoc === 2) {
          if ($empresa === '') {
            $error = 'La razón social no fue completada. Usa el autocompletado por SUNAT.';
          } else {
            $nombres   = $empresa;
            $apellidos = '';
          }
        } else {
          if ($nombres === '' || $apellidos === '') {
            $error = 'Nombres y apellidos son obligatorios (usa el autocompletado).';
          }
        }

        if ($error === '') {
          if ($telefono !== '' && !preg_match('/^[0-9+\-\s]{6,20}$/', $telefono)) {
            $error = 'Teléfono no válido.';
          } elseif ($direccion !== '' && mb_strlen($direccion, 'UTF-8') > 70) {
            $error = 'Dirección demasiado larga (máx 70).';
          }
        }

        if ($error === '') {
          $errPwd = validar_password_robusta($password, $email, $nombres, $apellidos);
          if ($errPwd !== null) {
            $error = $errPwd;
          }
        }

        if ($error === '') {
          $dup = $pdo->prepare('
            SELECT 
              CASE 
                WHEN email = ? THEN "email"
                WHEN id_tipodoc = ? AND num_documento = ? THEN "documento"
                ELSE "otro"
              END as tipo_duplicado
            FROM usuario
            WHERE email = ?
               OR (id_tipodoc = ? AND num_documento = ?)
            LIMIT 1
          ');
          $dup->execute([$email, $id_tipodoc, $nro_documento, $email, $id_tipodoc, $nro_documento]);
          $duplicado = $dup->fetch();
          
          if ($duplicado) {
            if ($duplicado['tipo_duplicado'] === 'email') {
              $error = 'Este correo electrónico ya está registrado. ¿Olvidaste tu contraseña?';
            } elseif ($duplicado['tipo_duplicado'] === 'documento') {
              $error = 'Este documento ya está registrado. Una persona no puede registrarse dos veces.';
            } else {
              $error = 'Ya existe una cuenta con estos datos.';
            }
          } else {
            $hash = hash('sha256', $password);
            $nombreFinal = ($id_tipodoc === 2) ? $empresa : trim($nombres . ' ' . $apellidos);

            // Asignar cargo automático según el rol
            $cargo = '';
            switch ($id_rol) {
              case 2: $cargo = 'Vendedor'; break;
              case 3: $cargo = 'Almacenero'; break;
              default: $cargo = 'Empleado'; break;
            }

            // ====== ASIGNACIÓN DE IMAGEN SEGÚN ROL ======
            $rolImages = [
              2 => 'vendedor.png',    // Vendedor
              3 => 'almacenero.jpg',  // Almacenero
            ];
            $defaultImage = 'default.png';
            $imagen = $rolImages[$id_rol] ?? $defaultImage;

            // === OBTENER NOMBRE DEL TIPO DE DOCUMENTO (para denormalizar en usuario.tipo_documento) ===
            $tipo_documento_nombre = null;
            try {
              $qTipo = $pdo->prepare('SELECT nombre FROM tipo_documento WHERE id_tipodoc = ? LIMIT 1');
              $qTipo->execute([$id_tipodoc]);
              $rowTipo = $qTipo->fetch(PDO::FETCH_ASSOC);
              if ($rowTipo && isset($rowTipo['nombre'])) {
                $tipo_documento_nombre = $rowTipo['nombre'];
              }
            } catch (Exception $e) {
              $tipo_documento_nombre = null; // no romper registro si falla
            }

            try {
              $pdo->beginTransaction();

              // ✅ INSERTAR CON IMAGEN Y NOMBRE DE TIPO DE DOCUMENTO
              $ins = $pdo->prepare('
                INSERT INTO usuario
                  (id_tipodoc, tipo_documento, num_documento, id_rol, nombre, email, clave, telefono, direccion, cargo, imagen, condicion)
                VALUES
                  (?,           ?,               ?,            ?,      ?,      ?,     ?,     ?,        ?,         ?,     ?,      1)
              ');
              $ins->execute([
                $id_tipodoc,
                $tipo_documento_nombre,   // NUEVO: nombre legible ("DNI","RUC","PASAPORTE")
                $nro_documento,
                $id_rol,
                $nombreFinal,
                $email,
                $hash,
                $telefono,
                $direccion,
                $cargo,
                $imagen
              ]);
              
              $newUserId = $pdo->lastInsertId();

              // Asignar permisos automáticos según el rol
              $permisos = $pdo->prepare('SELECT idpermiso FROM rol_permiso WHERE id_rol = ?');
              $permisos->execute([$id_rol]);
              $permisosRol = $permisos->fetchAll(PDO::FETCH_COLUMN);

              // Insertar permisos del usuario
              $insPermiso = $pdo->prepare('INSERT INTO usuario_permiso (idusuario, idpermiso) VALUES (?, ?)');
              foreach ($permisosRol as $idpermiso) {
                $insPermiso->execute([$newUserId, $idpermiso]);
              }

              $pdo->commit();

              $success = 'Registro exitoso. Ahora puedes iniciar sesión con tu correo electrónico.';
              $id_tipodoc = $id_rol = 0;
              $nro_documento = $nombres = $apellidos = $empresa = $email = $telefono = $direccion = '';
              
            } catch (Exception $e) {
              $pdo->rollBack();
              $error = 'Error al registrar: ' . $e->getMessage();
            }
          }
        }
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Registro - Sistema Neko</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="stylesheet" href="css/estilos.css?v=<?= time() ?>">
  <style>
    .req { display:flex; align-items:center; gap:8px; font-size:.85rem; margin:4px 0; }
    .req i{ width:16px; text-align:center; font-style:normal; }
    .req.bad{ color:#ef4444; }
    .req.bad i::before { content:'✗'; }
    .req.ok{ color:#10b981; }
    .req.ok i::before { content:'✓'; }
    .input-eye { position:absolute; right:12px; top:50%; transform:translateY(-50%); cursor:pointer; opacity:.7; user-select:none; font-size:1.2rem; }
    .input-eye:hover { opacity:1; }
    .input-wrap { position:relative; }
    .hidden { display:none !important; }
    .auth-form { max-height: 70vh; overflow-y: auto; padding-right: 10px; }
    .auth-form::-webkit-scrollbar { width: 6px; }
    .auth-form::-webkit-scrollbar-thumb { background: #4a5568; border-radius: 3px; }
    .field { margin-bottom: 1rem; }
    .role-selector { display: flex; gap: 10px; flex-wrap: wrap; }
    .role-btn { 
      padding: 10px 20px; 
      border: 2px solid #4a5568; 
      border-radius: 8px; 
      cursor: pointer; 
      transition: all 0.3s;
      background: #2d3748;
      color: #e2e8f0;
    }
    input[type="radio"]:checked + .role-btn {
      background: #4299e1;
      border-color: #4299e1;
      color: white;
    }
    .alert { padding: 12px; margin-bottom: 16px; border-radius: 8px; }
    .alert-error { background: #fed7d7; color: #742a2a; border-left: 4px solid #f56565; }
    .alert-success { background: #c6f6d5; color: #22543d; border-left: 4px solid #48bb78; }
  </style>
</head>
<body class="auth-body">
  <div class="auth-wrapper">
    <section class="auth-card">
      <div class="auth-left">
        <div class="brand-wrap">
          <img src="assets/logo.png" alt="Logo Neko" class="brand-logo">
          <h1 class="brand-title">Registro</h1>
          <p class="brand-sub">¿Ya tienes cuenta?</p>
          <a class="btn btn-outline" href="login.php">Iniciar Sesión</a>
        </div>
      </div>

      <div class="auth-right">
        <h2 class="auth-title">Crear cuenta</h2>

        <?php if ($error): ?>
          <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
          <div class="alert alert-success">
            <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
            <br><br>
            <a href="login.php" class="btn btn-primary w-full">Ir al Login</a>
          </div>
        <?php endif; ?>

        <?php if (!$success): ?>
        <form method="post" action="register.php" class="auth-form" autocomplete="off" novalidate>
          <!-- Tipo documento -->
          <label class="field">
            <span class="field-label">Tipo de documento *</span>
            <select id="tipodoc" name="id_tipodoc" required>
              <option value="">Seleccione…</option>
              <?php foreach ($tiposDoc as $td): ?>
                <option value="<?= (int)$td['id_tipodoc'] ?>" <?= ((int)$td['id_tipodoc']===$id_tipodoc?'selected':'') ?>>
                  <?= htmlspecialchars($td['nombre']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </label>

          <label class="field">
            <span class="field-label">Nro. de documento *</span>
            <input id="nrodoc" type="text" name="nro_documento" value="<?= htmlspecialchars($nro_documento) ?>" required>
            <small id="hintdoc" class="hint"></small>
          </label>

          <!-- Empresa (solo RUC) -->
          <label class="field <?= $id_tipodoc===2 ? '' : 'hidden' ?>" id="wrap-empresa">
            <span class="field-label">Razón social / Nombre de la empresa *</span>
            <input id="empresa" type="text" name="empresa" value="<?= htmlspecialchars($empresa) ?>" placeholder="Autocompletado por SUNAT" readonly>
          </label>

          <!-- Persona (DNI/Pasaporte) -->
          <label class="field <?= $id_tipodoc===2 ? 'hidden' : '' ?>" id="wrap-nombres">
            <span class="field-label">Nombres *</span>
            <input id="nombres" type="text" name="nombres" value="<?= htmlspecialchars($nombres) ?>" placeholder="Autocompletado por RENIEC" readonly>
          </label>

          <label class="field <?= $id_tipodoc===2 ? 'hidden' : '' ?>" id="wrap-apellidos">
            <span class="field-label">Apellidos *</span>
            <input id="apellidos" type="text" name="apellidos" value="<?= htmlspecialchars($apellidos) ?>" placeholder="Autocompletado por RENIEC" readonly>
          </label>

          <!-- EMAIL -->
          <label class="field">
            <span class="field-label">Correo electrónico *</span>
            <div style="position:relative;">
              <input id="email" type="email" name="email" value="<?= htmlspecialchars($email) ?>" style="width:100%;" required>
              <span id="email-status" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);font-size:1.2rem;"></span>
            </div>
            <small id="email-hint" class="hint">Usarás este correo para iniciar sesión</small>
          </label>

          <label class="field">
            <span class="field-label">Teléfono</span>
            <input type="text" name="telefono" value="<?= htmlspecialchars($telefono) ?>" placeholder="Opcional (6–20 caracteres)">
          </label>

          <label class="field">
            <span class="field-label">Dirección</span>
            <input type="text" name="direccion" value="<?= htmlspecialchars($direccion) ?>" placeholder="Opcional (máx. 70)">
          </label>

          <!-- Rol -->
          <label class="field">
            <span class="field-label">Selecciona tu rol *</span>
            <div class="role-selector">
              <?php foreach ($roles as $rol): ?>
                <input type="radio" id="rol<?= (int)$rol['id_rol'] ?>" name="id_rol" value="<?= (int)$rol['id_rol'] ?>" <?= ((int)$rol['id_rol']===$id_rol?'checked':'') ?> required style="display:none;">
                <label for="rol<?= (int)$rol['id_rol'] ?>" class="role-btn"><?= htmlspecialchars($rol['nombre']) ?></label>
              <?php endforeach; ?>
            </div>
          </label>

          <label class="field">
            <span class="field-label">Contraseña *</span>
            <div class="input-wrap">
              <input id="pwd" type="password" name="password" required aria-describedby="pwdHelp" style="width:90%;">
              <span class="input-eye" id="togglePwd" title="Ver/Ocultar">👁️</span>
            </div>
            <small id="pwdHelp" class="hint">Debe cumplir todos los requisitos:</small>
            <div id="rules" style="margin-top:8px;">
              <div class="req bad" id="r-len"><i></i> 10–64 caracteres</div>
              <div class="req bad" id="r-up"><i></i> Al menos 1 mayúscula (A-Z)</div>
              <div class="req bad" id="r-low"><i></i> Al menos 1 minúscula (a-z)</div>
              <div class="req bad" id="r-num"><i></i> Al menos 1 número (0-9)</div>
              <div class="req bad" id="r-spe"><i></i> Al menos 1 especial (!@#$%^&*)</div>
              <div class="req bad" id="r-spc"><i></i> Sin espacios</div>
              <div class="req bad" id="r-pii"><i></i> No contiene correo/nombres</div>
              <div class="req bad" id="r-common"><i></i> No es contraseña común</div>
            </div>
          </label>

          <label class="field">
            <span class="field-label">Confirmar contraseña *</span>
            <div class="input-wrap">
              <input id="pwd2" type="password" name="confirm" style="width:90%;" required>
              <span class="input-eye" id="togglePwd2" title="Ver/Ocultar">👁️</span>
            </div>
          </label>

          <button type="submit" class="btn btn-primary w-full">Crear cuenta</button>
          <p class="small text-center m-top">¿Ya tienes cuenta? <a href="login.php" class="link-strong">Inicia sesión</a></p>
        </form>
        <?php endif; ?>
      </div>
    </section>
  </div>

<script>
// Cambiar máscara según tipo documento
const tipodoc=document.getElementById('tipodoc');
const nrodoc=document.getElementById('nrodoc');
const hint=document.getElementById('hintdoc');
const wrapEmp = document.getElementById('wrap-empresa');
const wrapNom = document.getElementById('wrap-nombres');
const wrapApe = document.getElementById('wrap-apellidos');
const nombres = document.getElementById('nombres');
const apellidos = document.getElementById('apellidos');
const empresa = document.getElementById('empresa');

function setupDocMask(){
  let t=parseInt(tipodoc.value||'0',10);
  nrodoc.removeAttribute('pattern');nrodoc.removeAttribute('maxlength');
  if(t===1){ // DNI
    nrodoc.setAttribute('pattern','^[0-9]{8}$');nrodoc.maxLength=8;hint.textContent='DNI: 8 dígitos';
    wrapEmp.classList.add('hidden'); wrapNom.classList.remove('hidden'); wrapApe.classList.remove('hidden');
  }
  else if(t===2){ // RUC
    nrodoc.setAttribute('pattern','^[0-9]{11}$');nrodoc.maxLength=11;hint.textContent='RUC: 11 dígitos';
    wrapEmp.classList.remove('hidden'); wrapNom.classList.add('hidden'); wrapApe.classList.add('hidden');
  }
  else if(t===3){ // Pasaporte
    nrodoc.setAttribute('pattern','^[A-Za-z0-9]{9,12}$');nrodoc.maxLength=12;hint.textContent='Pasaporte: 9-12 caracteres';
    wrapEmp.classList.add('hidden'); wrapNom.classList.remove('hidden'); wrapApe.classList.remove('hidden');
  } else {
    hint.textContent='';
    wrapEmp.classList.add('hidden'); wrapNom.classList.remove('hidden'); wrapApe.classList.remove('hidden');
  }
}
tipodoc.addEventListener('change',setupDocMask);
document.addEventListener('DOMContentLoaded',setupDocMask);

// Ver/ocultar contraseñas
function togglePass(id, btnId){
  const input = document.getElementById(id);
  const btn = document.getElementById(btnId);
  btn.addEventListener('click', ()=>{ input.type = (input.type==='password'?'text':'password'); });
}
togglePass('pwd','togglePwd');
togglePass('pwd2','togglePwd2');

// Validación en vivo de contraseña
(function(){
  const pwd = document.getElementById('pwd');
  const pwd2 = document.getElementById('pwd2');
  const email = document.getElementById('email');

  const common = new Set(['123456','123456789','12345678','12345','qwerty','password','111111','abc123','123123','iloveyou','admin','welcome','monkey','dragon','qwertyuiop','000000']);

  function mark(id, ok){ 
    const el=document.getElementById(id); 
    el.classList.toggle('ok', ok); 
    el.classList.toggle('bad', !ok); 
  }

  function strongCheck(v){
    const len = v.length>=10 && v.length<=64;
    const up  = /[A-Z]/.test(v);
    const low = /[a-z]/.test(v);
    const num = /[0-9]/.test(v);
    const spe = /[!@#$%^&*()_\+\=\-\[\]{};:,.?]/.test(v);
    const spc = !/\s/.test(v);

    const lowers = v.toLowerCase();
    let pii = true;
    const pieces = [];
    if (email && email.value) pieces.push((email.value.split('@')[0]||'').toLowerCase());
    (nombres.value+' '+apellidos.value).split(/\s+/).forEach(p=>{ p=p.toLowerCase(); if(p.length>=4) pieces.push(p); });
    for (const p of pieces){ if(p && lowers.includes(p)){ pii=false; break; } }

    const notCommon = !common.has(lowers);

    mark('r-len', len); mark('r-up', up); mark('r-low', low);
    mark('r-num', num); mark('r-spe', spe); mark('r-spc', spc);
    mark('r-pii', pii); mark('r-common', notCommon);

    return len && up && low && num && spe && spc && pii && notCommon;
  }

  function syncValidity(){
    strongCheck(pwd.value);
    if (!strongCheck(pwd.value)) { pwd.setCustomValidity('La contraseña no cumple los requisitos mínimos.'); }
    else { pwd.setCustomValidity(''); }
    if (pwd2.value && pwd2.value !== pwd.value) { pwd2.setCustomValidity('Las contraseñas no coinciden.'); }
    else { pwd2.setCustomValidity(''); }
  }
  pwd.addEventListener('input', syncValidity);
  pwd2.addEventListener('input', syncValidity);
  if(email) email.addEventListener('input', syncValidity);
})();

// RENIEC (DNI)
(function(){
  const tip = document.getElementById('tipodoc');
  let t; let inflight; let lastQueried = '';

  function ready(){
    return parseInt(tip.value||'0',10)===1 && /^\d{8}$/.test(nrodoc.value);
  }

  async function consulta(){
    if(!ready()) return;
    if (nrodoc.value === lastQueried) return;
    if (inflight) inflight.abort();
    inflight = new AbortController();

    const prevN=nombres.value, prevA=apellidos.value;
    nombres.value='Consultando RENIEC...'; apellidos.value='Consultando RENIEC...';

    try{
      const res = await fetch(`ajax/reniec.php?dni=${encodeURIComponent(nrodoc.value)}`, {
        headers:{'X-Requested-With':'fetch'}, cache:'no-store', signal: inflight.signal
      });
      const data = await res.json();
      if(!res.ok || data.success===false) throw new Error(data.message || 'Error al consultar');
      nombres.value = data.nombres || '';
      apellidos.value = data.apellidos || '';
      lastQueried = nrodoc.value;
    }catch(e){
      if (e.name === 'AbortError') return;
      nombres.value = prevN; apellidos.value = prevA;
      alert(e.message || 'Error al consultar RENIEC');
      lastQueried = '';
    }
  }

  function debounce(){ clearTimeout(t); t=setTimeout(()=>{ if(ready()) consulta(); }, 450); }
  tip.addEventListener('change', debounce);
  nrodoc.addEventListener('input', debounce);
  nrodoc.addEventListener('blur', ()=>{ if(ready()) consulta(); });
})();

// SUNAT (RUC)
(function(){
  const tip = document.getElementById('tipodoc');
  let t; let inflight; let lastRuc='';

  function ready(){
    return parseInt(tip.value||'0',10)===2 && /^\d{11}$/.test(nrodoc.value);
  }

  async function consulta(){
    if(!ready()) return;
    if (nrodoc.value === lastRuc) return;
    if (inflight) inflight.abort();
    inflight = new AbortController();

    const prev = empresa.value;
    empresa.value = 'Consultando SUNAT...';
    try{
      const res = await fetch(`ajax/sunat.php?ruc=${encodeURIComponent(nrodoc.value)}`, {
        headers:{'X-Requested-With':'fetch'}, cache:'no-store', signal: inflight.signal
      });
      const data = await res.json();
      if(!res.ok || data.success===false) throw new Error(data.message || 'Error al consultar');
      empresa.value = data.razon_social || data.nombre_o_razon_social || '';
      lastRuc = nrodoc.value;
    }catch(e){
      if (e.name === 'AbortError') return;
      empresa.value = prev;
      alert(e.message || 'Error al consultar SUNAT');
      lastRuc='';
    }
  }

  function debounce(){ clearTimeout(t); t=setTimeout(()=>{ if(ready()) consulta(); }, 450); }
  tip.addEventListener('change', debounce);
  nrodoc.addEventListener('input', debounce);
  nrodoc.addEventListener('blur', ()=>{ if(ready()) consulta(); });
})();

// VALIDACIÓN DE EMAIL EN TIEMPO REAL
(function(){
  const emailInput = document.getElementById('email');
  const emailHint = document.getElementById('email-hint');
  const emailStatus = document.getElementById('email-status');
  let timer;
  let inflight;
  let lastChecked = '';

  function isValidFormat(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
  }

  async function validateEmail() {
    const email = emailInput.value.trim();
    
    if (!email) {
      emailStatus.textContent = '';
      emailHint.textContent = 'Usarás este correo para iniciar sesión';
      emailHint.style.color = '';
      emailInput.setCustomValidity('');
      return;
    }

    if (email === lastChecked) return;

    if (!isValidFormat(email)) {
      emailStatus.textContent = '❌';
      emailHint.textContent = 'Formato de correo inválido';
      emailHint.style.color = '#ef4444';
      emailInput.setCustomValidity('Formato inválido');
      return;
    }

    if (inflight) inflight.abort();
    inflight = new AbortController();

    emailStatus.textContent = '⏳';
    emailHint.textContent = 'Verificando correo...';
    emailHint.style.color = '#3b82f6';

    try {
      const res = await fetch(`ajax/validate_email.php?email=${encodeURIComponent(email)}`, {
        headers: {'X-Requested-With': 'fetch'},
        cache: 'no-store',
        signal: inflight.signal
      });

      const data = await res.json();

      if (data.success && data.valid) {
        emailStatus.textContent = '✅';
        emailHint.textContent = data.verified 
          ? 'Correo verificado y válido' 
          : 'Correo válido (dominio verificado)';
        emailHint.style.color = '#10b981';
        emailInput.setCustomValidity('');
        lastChecked = email;
      } else {
        emailStatus.textContent = '❌';
        emailHint.textContent = data.message || 'Este correo no es válido';
        emailHint.style.color = '#ef4444';
        emailInput.setCustomValidity(data.message || 'Email inválido');
      }
    } catch (e) {
      if (e.name === 'AbortError') return;
      
      emailStatus.textContent = '⚠️';
      emailHint.textContent = 'No se pudo verificar. Asegúrate que sea un correo real.';
      emailHint.style.color = '#f59e0b';
      emailInput.setCustomValidity('');
    }
  }

  function debounce() {
    clearTimeout(timer);
    timer = setTimeout(validateEmail, 800);
  }

  emailInput.addEventListener('input', debounce);
  emailInput.addEventListener('blur', validateEmail);
})();
</script>
</body>
</html>
