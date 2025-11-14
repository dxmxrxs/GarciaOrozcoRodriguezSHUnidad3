<?php
session_start();
include 'config/connexion.php';

define('RECAPTCHA_SITE_KEY', '6Lctk4UrAAAAALd9Qbzt8SIxTo90BeRqyVettjom');
define('RECAPTCHA_SECRET_KEY', '6Lctk4UrAAAAAF8qNpBupThPRaYlBc0xTvMK77EU');

$error = '';
$limite_intentos = 2;
$tiempo_bloqueo = 10;
$ip = $_SERVER['REMOTE_ADDR'];
$ahora = new DateTime();
$ahora_str = $ahora->format('Y-m-d H:i:s');

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

function estaBloqueada($conexion, $ip, $ahora) {
    $stmt = $conexion->prepare("SELECT intentos, bloqueado_hasta FROM intentos_ip WHERE ip = :ip");
    $stmt->execute(['ip' => $ip]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row && $row['bloqueado_hasta'] !== null) {
        $bloqueado_hasta = new DateTime($row['bloqueado_hasta']);
        if ($ahora < $bloqueado_hasta) {
            return $bloqueado_hasta;
        } else {
            $stmt = $conexion->prepare("UPDATE intentos_ip SET intentos = 0, bloqueado_hasta = NULL WHERE ip = :ip");
            $stmt->execute(['ip' => $ip]);
            return false;
        }
    }
    return false;
}

$bloqueo = estaBloqueada($conexion, $ip, $ahora);
if ($bloqueo) {
    die("Tu IP está bloqueada hasta " . $bloqueo->format('H:i:s') . ". Intenta más tarde.");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Error de validación CSRF. Por favor recarga la página y vuelve a intentarlo.');
    }

    $recaptcha_response = $_POST['g-recaptcha-response'] ?? '';

    if (empty($recaptcha_response)) {
        $error = 'Por favor, completa la verificación de seguridad.';
    } else {
        $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify';
        $recaptcha_data = [
            'secret' => RECAPTCHA_SECRET_KEY,
            'response' => $recaptcha_response,
            'remoteip' => $ip
        ];

        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($recaptcha_data)
            ]
        ];

        $context = stream_context_create($options);
        $recaptcha_result = file_get_contents($recaptcha_url, false, $context);
        $recaptcha_json = json_decode($recaptcha_result);

        if (!$recaptcha_json->success) {
            $error = 'Error en la verificación de seguridad. Inténtalo de nuevo.';
        } else {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'Por favor, complete todos los campos.';
            } else {
                $stmt = $conexion->prepare("SELECT id, nombre, email, password, tipo_usuario FROM usuarios WHERE email = :email AND estado = 'activo'");
                $stmt->bindValue(':email', $email);
                $stmt->execute();
                $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($usuario && password_verify($password, $usuario['password'])) {
                    $stmt = $conexion->prepare("DELETE FROM intentos_ip WHERE ip = :ip");
                    $stmt->execute(['ip' => $ip]);

                    session_regenerate_id(true);
                    $_SESSION['usuario'] = [
                        'id' => $usuario['id'],
                        'nombre' => $usuario['nombre'],
                        'email' => $usuario['email'],
                        'tipo_usuario' => $usuario['tipo_usuario']
                    ];

                    $stmt = $conexion->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id");
                    $stmt->bindValue(':id', $usuario['id']);
                    $stmt->execute();

                    header('Location: index.php');
                    exit;
                } else {
                    $stmt = $conexion->prepare("SELECT intentos FROM intentos_ip WHERE ip = :ip");
                    $stmt->execute(['ip' => $ip]);
                    $fila = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($fila) {
                        $intentos = $fila['intentos'] + 1;
                        if ($intentos >= $limite_intentos) {
                            $bloqueado_hasta = (new DateTime())->modify("+{$tiempo_bloqueo} minutes")->format('Y-m-d H:i:s');
                            $stmt = $conexion->prepare("UPDATE intentos_ip SET intentos = :intentos, ultimo_intento = :ahora, bloqueado_hasta = :bloqueado WHERE ip = :ip");
                            $stmt->execute([
                                'intentos' => $intentos,
                                'ahora' => $ahora_str,
                                'bloqueado' => $bloqueado_hasta,
                                'ip' => $ip
                            ]);
                            $error = "Demasiados intentos fallidos. Tu IP está bloqueada por {$tiempo_bloqueo} minutos.";
                        } else {
                            $stmt = $conexion->prepare("UPDATE intentos_ip SET intentos = :intentos, ultimo_intento = :ahora WHERE ip = :ip");
                            $stmt->execute([
                                'intentos' => $intentos,
                                'ahora' => $ahora_str,
                                'ip' => $ip
                            ]);
                            $error = "Credenciales incorrectas. Intentos: $intentos.";
                        }
                    } else {
                        $stmt = $conexion->prepare("INSERT INTO intentos_ip (ip, intentos, ultimo_intento) VALUES (:ip, 1, :ahora)");
                        $stmt->execute(['ip' => $ip, 'ahora' => $ahora_str]);
                        $error = "Credenciales incorrectas. Intentos: 1.";
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
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Biblioteca Escolar - Iniciar Sesión</title>
  <link rel="shortcut icon" href="img/Gemini_Generated_Image_8cspsf8cspsf8csp.png" type="image/x-icon" />
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://www.google.com/recaptcha/api.js?render=<?= RECAPTCHA_SITE_KEY ?>"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />
  <style>
    .login-container {
      background: linear-gradient(135deg, #f0fff4 0%, #ffffff 100%);
    }
    .login-card {
      transition: all 0.3s ease;
      box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
    }
    .login-card:hover {
      box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
    }
    .input-field {
      transition: all 0.3s ease;
    }
    .input-field:focus {
      box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.3);
    }
    .btn-login {
      transition: all 0.3s ease;
      letter-spacing: 0.5px;
    }
    .btn-login:hover {
      transform: translateY(-2px);
      box-shadow: 0 5px 15px rgba(74, 222, 128, 0.4);
    }
    .error-message {
      animation: shake 0.5s;
    }
    @keyframes shake {
      0%, 100% { transform: translateX(0); }
      10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
      20%, 40%, 60%, 80% { transform: translateX(5px); }
    }
  </style>
</head>
<body class="bg-gray-50 min-h-screen flex items-center justify-center p-4">
  <div class="login-container rounded-2xl overflow-hidden animate__animated animate__fadeIn">
    <div class="login-card bg-white p-8 sm:p-10 rounded-2xl w-full max-w-md border border-gray-100">
      <div class="flex justify-center mb-6 animate__animated animate__bounceIn">
        <img src="img/Gemini_Generated_Image_8cspsf8cspsf8csp.png" alt="Logo Biblioteca" class="h-20 transition-transform duration-500 hover:rotate-6" />
      </div>
      <h1 class="text-3xl font-bold mb-2 text-center text-gray-800 animate__animated animate__fadeInDown">
        Iniciar Sesión
        <div class="w-16 h-1 bg-green-500 mx-auto mt-3 rounded-full animate__animated animate__fadeInLeft"></div>
      </h1>
      <p class="text-gray-600 text-center mb-8 animate__animated animate__fadeIn">Accede a tu cuenta de la biblioteca</p>

      <?php if ($error): ?>
        <div class="error-message bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded animate__animated animate__fadeIn">
          <div class="flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
            </svg>
            <?= htmlspecialchars($error) ?>
          </div>
        </div>
      <?php endif; ?>

      <form method="POST" id="loginForm" class="space-y-6" novalidate>
        <div class="space-y-4">
            <div class="animate__animated animate__fadeInLeft" style="animation-delay: 0.2s">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Correo electrónico</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" />
                            <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" />
                        </svg>
                    </div>
                    <input type="email" name="email" id="email" required
                          class="input-field pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500"
                          placeholder="tucorreo@ejemplo.com" />
                </div>
            </div>

            <div class="animate__animated animate__fadeInLeft" style="animation-delay: 0.4s">
                <label for="password" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <input type="password" name="password" id="password" required
                          class="input-field pl-10 w-full border border-gray-300 rounded-lg px-4 py-3 focus:outline-none focus:ring-2 focus:ring-green-500"
                          placeholder="••••••••" />
                </div>
            </div>
        </div>

        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">

        <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response" />

        <div class="animate__animated animate__fadeInUp" style="animation-delay: 0.6s">
            <button type="submit" id="submitBtn"
                    class="btn-login w-full bg-gradient-to-r from-green-500 to-green-600 text-white py-3 px-4 rounded-lg font-semibold text-lg shadow-md hover:from-green-600 hover:to-green-700">
                Ingresar
            </button>
        </div>

        <div class="flex justify-between items-center animate__animated animate__fadeIn" style="animation-delay: 0.8s">
            <a href="index.php" class="inline-flex items-center text-green-600 hover:text-green-800 text-sm font-medium transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Volver al inicio
            </a>

            <a href="register.php" class="text-green-600 hover:text-green-800 text-sm font-medium transition">
                ¿No tienes cuenta? Regístrate
            </a>
        </div>
    </form>
    </div>
  </div>

  <script>
  document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('loginForm');
    const submitBtn = document.getElementById('submitBtn');

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      submitBtn.disabled = true;
      submitBtn.innerHTML = 'Verificando...';

      grecaptcha.ready(function () {
        grecaptcha.execute('<?= RECAPTCHA_SITE_KEY ?>', { action: 'login' })
          .then(function (token) {
            document.getElementById('g-recaptcha-response').value = token;
            form.submit();
          })
          .catch(function (error) {
            alert('Error al verificar reCAPTCHA: ' + error);
            console.error('reCAPTCHA error:', error);
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Ingresar';
          });
      });
    });

    const inputs = document.querySelectorAll('.input-field');
    inputs.forEach(input => {
      input.addEventListener('focus', () => {
        const icon = input.parentElement.querySelector('svg');
        icon.classList.add('text-green-500');
        icon.classList.remove('text-gray-400');
      });
      input.addEventListener('blur', () => {
        const icon = input.parentElement.querySelector('svg');
        icon.classList.remove('text-green-500');
        icon.classList.add('text-gray-400');
      });
    });
  });
  </script>
</body>
</html>
