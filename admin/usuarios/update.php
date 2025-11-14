<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['mensaje'] = 'Acceso no permitido.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}

$id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
$nombre = trim($_POST['nombre']);
$email = trim($_POST['email']);
$tipo_usuario = $_POST['tipo_usuario'];

if ($id <= 0 || empty($nombre) || empty($email)) {
    $_SESSION['mensaje'] = 'Datos inválidos o incompletos.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}

try {
    // Verificar si el email ya existe en otro usuario
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE email = :email AND id != :id");
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->fetch()) {
        $_SESSION['mensaje'] = 'El correo electrónico ya está en uso por otro usuario.';
        $_SESSION['tipo_mensaje'] = 'error';
        header("Location: edit.php?id=$id");
        exit;
    }

    // Actualizar nombre, email y tipo_usuario
    $stmt = $conexion->prepare("UPDATE usuarios SET nombre = :nombre, email = :email, tipo_usuario = :tipo_usuario WHERE id = :id");
    $stmt->bindValue(':nombre', $nombre, PDO::PARAM_STR);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt->bindValue(':tipo_usuario', $tipo_usuario, PDO::PARAM_STR);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // Si se ingresó una nueva contraseña, actualizarla
    if (!empty($_POST['password'])) {
        $nueva_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $conexion->prepare("UPDATE usuarios SET password = :password WHERE id = :id");
        $stmt->bindValue(':password', $nueva_password, PDO::PARAM_STR);
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
    }

    $_SESSION['mensaje'] = 'Usuario actualizado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al actualizar el usuario.';
    $_SESSION['tipo_mensaje'] = 'error';
    header("Location: edit.php?id=$id");
    exit;
}
