<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;

    if (!$id) {
        $_SESSION['mensaje'] = 'ID de préstamo no válido.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: index.php');
        exit;
    }

    try {
        $stmt = $conexion->prepare("UPDATE prestamos SET estado = 'anulado' WHERE id = ?");
        $stmt->execute([$id]);

        $_SESSION['mensaje'] = 'El préstamo fue anulado correctamente.';
        $_SESSION['tipo_mensaje'] = 'success';
    } catch (PDOException $e) {
        $_SESSION['mensaje'] = 'Error al anular el préstamo: ' . $e->getMessage();
        $_SESSION['tipo_mensaje'] = 'error';
    }

    header('Location: index.php');
    exit;
}
?>
