<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

// Recibir datos del formulario
$nombre = trim($_POST['nombre'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$tipo_usuario = $_POST['tipo_usuario'];

// Validaciones si los campos estan vacios
if (empty($nombre) || empty($email) || empty($password)) {
    $_SESSION['mensaje'] = 'Por favor, completa todos los campos obligatorios.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['mensaje'] = 'El correo electrónico no es válido.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}

try {
    // Verificar si ya existe un usuario con ese email
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = :email");
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->execute();

    if ($stmt->fetch()) {
        $_SESSION['mensaje'] = 'Ya existe un usuario registrado con ese correo electrónico.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: create.php');
        exit;
    }

    // Hashear la contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar nuevo usuario
    $stmt = $conexion->prepare("INSERT INTO usuarios (nombre, email, password, tipo_usuario) VALUES (:nombre, :email, :password, :tipo_usuario)");
    $stmt->bindValue(':nombre', $nombre);
    $stmt->bindValue(':email', $email);
    $stmt->bindValue(':password', $password_hash);
    $stmt->bindValue(':tipo_usuario', $tipo_usuario);
    $stmt->execute();

    $_SESSION['mensaje'] = 'Usuario creado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al crear el usuario: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}
