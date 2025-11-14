<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die('ID inválido');
}

$id = (int) $_POST['id'];
$usuario_actual_id = $_SESSION['usuario']['id']; 

try {
    // Verificar si el usuario a eliminar es administrador
    $stmt = $conexion->prepare("SELECT tipo_usuario FROM usuarios WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario) {
        $_SESSION['mensaje'] = 'El usuario no existe.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: index.php');
        exit;
    }

    // Si es administrador, verificar si es el único
    if ($usuario['tipo_usuario'] === 'administrador') {
        $stmt = $conexion->prepare("SELECT COUNT(*) FROM usuarios WHERE tipo_usuario = 'administrador' AND id != :id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $totalOtrosAdmins = $stmt->fetchColumn();

        if ($totalOtrosAdmins == 0) {
            $_SESSION['mensaje'] = 'No puedes eliminar al único administrador del sistema.';
            $_SESSION['tipo_mensaje'] = 'error';
            header('Location: index.php');
            exit;
        }
    }


    // Eliminar usuario
    $stmt = $conexion->prepare("DELETE FROM usuarios WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    // Verificar si se eliminó a sí mismo
    if ($id === $usuario_actual_id) {
        header("Location: $base_url/logout.php");
        exit;
    }

    $_SESSION['mensaje'] = 'Usuario eliminado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al eliminar el usuario.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}
