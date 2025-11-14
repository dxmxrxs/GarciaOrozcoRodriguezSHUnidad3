<?php
session_start();

require __DIR__ . '/vendor/autoload.php';
include 'config/connexion.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;

// Cargar variables de entorno
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

// Obtener variables de entorno
$mailHost = $_ENV['SMTP_HOST'] ?? '';
$mailUser = $_ENV['SMTP_USER'] ?? '';
$mailPass = $_ENV['SMTP_PASS'] ?? '';
$mailPort = $_ENV['SMTP_PORT'] ?? 587;

$error = '';
$success = '';

// Token CSRF
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Función para validar contraseña fuerte
function isPasswordStrong($password) {
    return strlen($password) >= 8 &&
           preg_match('/[A-Z]/', $password) &&
           preg_match('/[a-z]/', $password) &&
           preg_match('/[0-9]/', $password) &&
           preg_match('/[\W]/', $password);
}

// Control de intentos fallidos para bloqueo por IP
$max_intentos = 5;
$bloqueo_minutos = 10;
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$ahora = new DateTime();

function registrarIntentoFallido($conexion, $ip, $max_intentos, $bloqueo_minutos) {
    $stmt = $conexion->prepare("SELECT intentos, bloqueado_hasta FROM intentos_ip WHERE ip = :ip");
    $stmt->execute(['ip' => $ip]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $intentos = $row['intentos'] + 1;
        if ($intentos >= $max_intentos) {
            $bloqueado_hasta = (new DateTime())->modify("+{$bloqueo_minutos} minutes")->format('Y-m-d H:i:s');
            $update = $conexion->prepare("UPDATE intentos_ip SET intentos = :intentos, bloqueado_hasta = :bloqueado WHERE ip = :ip");
            $update->execute(['intentos' => $intentos, 'bloqueado' => $bloqueado_hasta, 'ip' => $ip]);
            return ['bloqueado' => true, 'mensaje' => "Demasiados intentos. Intenta de nuevo en {$bloqueo_minutos} minutos."];
        } else {
            $update = $conexion->prepare("UPDATE intentos_ip SET intentos = :intentos WHERE ip = :ip");
            $update->execute(['intentos' => $intentos, 'ip' => $ip]);
            return ['bloqueado' => false, 'mensaje' => "Intentos fallidos: $intentos"];
        }
    } else {
        $insert = $conexion->prepare("INSERT INTO intentos_ip (ip, intentos) VALUES (:ip, 1)");
        $insert->execute(['ip' => $ip]);
        return ['bloqueado' => false, 'mensaje' => "Intentos fallidos: 1"];
    }
}

// Comprobar bloqueo por IP
$stmt = $conexion->prepare("SELECT bloqueado_hasta FROM intentos_ip WHERE ip = :ip");
$stmt->execute(['ip' => $ip]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row && !empty($row['bloqueado_hasta'])) {
    $bloqueado_hasta = new DateTime($row['bloqueado_hasta']);
    if ($ahora < $bloqueado_hasta) {
        die("Tu IP está bloqueada hasta " . $bloqueado_hasta->format('H:i:s') . ". Intenta más tarde.");
    } else {
        $reset = $conexion->prepare("UPDATE intentos_ip SET intentos = 0, bloqueado_hasta = NULL WHERE ip = :ip");
        $reset->execute(['ip' => $ip]);
    }
}

// Procesar formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = 'Token CSRF inválido. Recarga la página e intenta nuevamente.';
    } else {
        $nombre = filter_var(trim($_POST['nombre'] ?? ''), FILTER_SANITIZE_STRING);
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $tipo_usuario = 'estudiante';

        if (empty($nombre) || empty($email) || empty($password) || empty($confirm_password)) {
            $error = 'Todos los campos son obligatorios.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Correo electrónico no válido.';
        } elseif ($password !== $confirm_password) {
            $error = 'Las contraseñas no coinciden.';
        } elseif (!isPasswordStrong($password)) {
            $error = 'La contraseña debe tener al menos 8 caracteres, incluir mayúsculas, minúsculas, números y símbolos.';
        } else {
            try {
                $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = :email");
                $stmt->execute(['email' => $email]);

                if ($stmt->fetch()) {
                    $error = 'Este correo electrónico ya está registrado.';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, tipo_usuario) VALUES (:nombre, :email, :password, :tipo_usuario)");
                    $stmt->execute([
                        'nombre' => $nombre,
                        'email' => $email,
                        'password' => $password_hash,
                        'tipo_usuario' => $tipo_usuario
                    ]);

                    if ($stmt->rowCount() > 0) {
                        unset($_SESSION['csrf_token']);
                        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                        $csrf_token = $_SESSION['csrf_token'];

                        // Enviar correo de bienvenida
                        try {
                            $mail = new PHPMailer(true);
                            $mail->SMTPDebug = 0; 
                            $mail->isSMTP();
                            $mail->Host = $mailHost;
                            $mail->SMTPAuth = true;
                            $mail->Username = $mailUser;
                            $mail->Password = $mailPass;
                            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                            $mail->Port = $mailPort;
                            $mail->CharSet = 'UTF-8';

                            $mail->setFrom($mailUser, 'Biblioteca UTC');
                            $mail->addAddress($email, $nombre);
                            $mail->isHTML(true);
                            $mail->Subject = 'Bienvenido a la Biblioteca UTC';
                            $mail->Body = "
                                Bienvenido (a) <b>" . htmlspecialchars($nombre) . "</b>,<br><br>
                                Has sido registrado exitosamente en la plataforma.<br><br>
                                Ya puedes iniciar sesión con tu cuenta.<br><br>
                                ¡Gracias por unirte a nuestra comunidad!
                            ";

                            $mail->send();
                            $success = 'Registro exitoso. Revisa tu correo para más información.';
                        } catch (Exception $e) {
                            error_log("Error al enviar correo a $email: " . $mail->ErrorInfo);
                            $error = 'Usuario creado, pero ocurrió un error al enviar el correo: ' . $mail->ErrorInfo;
                        }
                    } else {
                        $resultadoIntento = registrarIntentoFallido($conexion, $ip, $max_intentos, $bloqueo_minutos);
                        $error = $resultadoIntento['bloqueado'] ? $resultadoIntento['mensaje'] : 'Error al registrar el usuario.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Error en la base de datos: " . $e->getMessage());
                $error = 'Error interno. Por favor intenta más tarde.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Biblioteca Escolar - Registro</title>
  <link rel="shortcut icon" href="img/Gemini_Generated_Image_8cspsf8cspsf8csp.png" type="image/x-icon">
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <style>
    .register-container {
      background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
    }
    .register-card {
      transition: all 0.3s ease;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }
    .register-card:hover {
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    .input-field {
      transition: all 0.3s ease;
    }
    .input-field:focus {
      box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.3);
    }
    .btn-register {
      transition: all 0.3s ease;
      letter-spacing: 0.5px;
    }
    .btn-register:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(74, 222, 128, 0.4);
    }
    .error-message {
      animation: shake 0.5s;
    }
    .success-message {
      animation: fadeIn 0.5s;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
    .password-strength {
      height: 4px;
      transition: all 0.3s ease;
    }
    .password-weak {
      background-color: #ef4444;
      width: 25%;
    }
    .password-medium {
      background-color: #f59e0b;
      width: 50%;
    }
    .password-strong {
      background-color: #10b981;
      width: 100%;
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
  <div class="register-container rounded-2xl overflow-hidden animate__animated animate__fadeIn">
    <div class="register-card bg-white p-8 sm:p-10 rounded-2xl w-full max-w-md border border-gray-100">
      <div class="flex justify-center mb-6 animate__animated animate__bounceIn">
        <img src="img/Gemini_Generated_Image_8cspsf8cspsf8csp.png" alt="Logo Biblioteca" 
             class="h-20 transition-transform duration-500 hover:rotate-6">
      </div>
      <h1 class="text-3xl font-bold mb-2 text-center text-gray-800 animate__animated animate__fadeInDown">
        Crear Cuenta
        <div class="w-16 h-1 bg-green-500 mx-auto mt-3 rounded-full animate__animated animate__fadeInLeft"></div>
      </h1>
      <p class="text-gray-600 text-center mb-8 animate__animated animate__fadeIn">Únete a nuestra biblioteca</p>

      <?php if (!empty($error)): ?>
        <div class="error-message bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <?= htmlspecialchars($error) ?>
          </div>
        </div>
      <?php endif; ?>

      <?php if (!empty($success)): ?>
        <div class="success-message bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
            </svg>
            <?= htmlspecialchars($success) ?>
          </div>
        </div>
      <?php endif; ?>

      <form method="POST" class="space-y-6" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <div class="space-y-4">
          <div class="animate__animated animate__fadeInLeft" style="animation-delay: 0.2s">
            <label for="nombre" class="block text-sm font-medium text-gray-700 mb-1">Nombre Completo</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                </svg>
              </div>
              <input type="text" name="nombre" id="nombre" required
                     value="<?= htmlspecialchars($nombre ?? '') ?>"
                     class="input-field pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500"
                     placeholder="Tu nombre completo" autocomplete="name" autofocus>
            </div>
          </div>

          <div class="animate__animated animate__fadeInLeft" style="animation-delay: 0.3s">
            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                  <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                </svg>
              </div>
              <input type="email" name="email" id="email" required
                     value="<?= htmlspecialchars($email ?? '') ?>"
                     class="input-field pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500"
                     placeholder="tucorreo@ejemplo.com" autocomplete="email">
            </div>
          </div>

          <div class="animate__animated animate__fadeInLeft" style="animation-delay: 0.4s">
            <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                </svg>
              </div>
              <input type="password" name="password" id="password" required
                     class="input-field pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500"
                     placeholder="••••••••" autocomplete="new-password">
              <div id="password-strength" class="password-strength mt-1 rounded"></div>
              <div id="password-hints" class="text-xs text-gray-500 mt-2 hidden" aria-live="polite">
                <p class="flex items-center"><span class="hint-icon mr-1">✗</span> Mínimo 8 caracteres</p>
                <p class="flex items-center"><span class="hint-icon mr-1">✗</span> Mayúsculas y minúsculas</p>
                <p class="flex items-center"><span class="hint-icon mr-1">✗</span> Al menos un número</p>
                <p class="flex items-center"><span class="hint-icon mr-1">✗</span> Carácter especial</p>
              </div>
            </div>
          </div>

          <div class="animate__animated animate__fadeInLeft" style="animation-delay: 0.5s">
            <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-1">Confirmar Contraseña</label>
            <div class="relative">
              <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                  <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                </svg>
              </div>
              <input type="password" name="confirm_password" id="confirm_password" required
                     class="input-field pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500"
                     placeholder="••••••••" autocomplete="new-password">
              <div id="password-match" class="text-xs mt-1 hidden" aria-live="polite"></div>
            </div>
          </div>
        </div>

        <div class="animate__animated animate__fadeInUp" style="animation-delay: 0.6s">
          <button type="submit" id="register-btn"
                  class="btn-register w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-3 px-4 rounded-lg font-semibold text-lg shadow-md hover:from-green-600 hover:to-green-700 disabled:opacity-50 disabled:cursor-not-allowed">
            Registrarse
          </button>
        </div>

        <div class="text-center animate__animated animate__fadeIn" style="animation-delay: 0.8s">
          <p class="text-gray-600 text-sm">¿Ya tienes una cuenta? 
            <a href="login.php" class="text-green-600 hover:text-green-800 font-medium transition">Inicia sesión</a>
          </p>
        </div>
      </form>
    </div>
</div>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const passwordInput = document.getElementById('password');
      const confirmPasswordInput = document.getElementById('confirm_password');
      const passwordStrength = document.getElementById('password-strength');
      const passwordHints = document.getElementById('password-hints');
      const passwordMatch = document.getElementById('password-match');
      const registerBtn = document.getElementById('register-btn');
      const hints = passwordHints.querySelectorAll('p');

      passwordInput.addEventListener('input', function() {
        const password = this.value;
        passwordHints.classList.remove('hidden');
        
        const hasLength = password.length >= 8;
        const hasUpper = /[A-Z]/.test(password);
        const hasLower = /[a-z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[\W]/.test(password);
        
        hints[0].querySelector('.hint-icon').textContent = hasLength ? '✓' : '✗';
        hints[0].classList.toggle('text-green-500', hasLength);
        hints[1].querySelector('.hint-icon').textContent = (hasUpper && hasLower) ? '✓' : '✗';
        hints[1].classList.toggle('text-green-500', (hasUpper && hasLower));
        hints[2].querySelector('.hint-icon').textContent = hasNumber ? '✓' : '✗';
        hints[2].classList.toggle('text-green-500', hasNumber);
        hints[3].querySelector('.hint-icon').textContent = hasSpecial ? '✓' : '✗';
        hints[3].classList.toggle('text-green-500', hasSpecial);
        
        const strength = [hasLength, hasUpper, hasLower, hasNumber, hasSpecial].filter(Boolean).length;
        
        passwordStrength.className = 'password-strength mt-1 rounded';
        if (strength <= 2) {
          passwordStrength.classList.add('password-weak');
        } else if (strength <= 4) {
          passwordStrength.classList.add('password-medium');
        } else {
          passwordStrength.classList.add('password-strong');
        }
      });
      
      confirmPasswordInput.addEventListener('input', function() {
        const password = passwordInput.value;
        const confirmPassword = this.value;
        
        if (confirmPassword.length > 0) {
          passwordMatch.classList.remove('hidden');
          if (password === confirmPassword) {
            passwordMatch.textContent = 'Las contraseñas coinciden';
            passwordMatch.className = 'text-xs mt-1 text-green-500';
          } else {
            passwordMatch.textContent = 'Las contraseñas no coinciden';
            passwordMatch.className = 'text-xs mt-1 text-red-500';
          }
        } else {
          passwordMatch.classList.add('hidden');
        }
      });
      
      const form = document.querySelector('form');
      form.addEventListener('submit', function(e) {
        const password = passwordInput.value;
        const confirmPassword = confirmPasswordInput.value;
        
        if (password !== confirmPassword) {
          e.preventDefault();
          passwordMatch.textContent = 'Las contraseñas no coinciden';
          passwordMatch.className = 'text-xs mt-1 text-red-500';
          passwordMatch.classList.remove('hidden');
          confirmPasswordInput.focus();
        }
      });

      const inputs = document.querySelectorAll('.input-field');
      inputs.forEach(input => {
        input.addEventListener('focus', () => {
          input.parentElement.querySelector('svg').classList.add('text-green-500');
          input.parentElement.querySelector('svg').classList.remove('text-gray-400');
        });
        input.addEventListener('blur', () => {
          input.parentElement.querySelector('svg').classList.remove('text-green-500');
          input.parentElement.querySelector('svg').classList.add('text-gray-400');
        });
      });
    });
  </script>
</body>
</html>