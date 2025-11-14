<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

$id = $_POST['id'] ?? null;
$fecha_devolucion = trim($_POST['fecha_devolucion'] ?? '');
$observaciones = trim($_POST['observaciones'] ?? '');

// Validación básica
if (!$id || !$fecha_devolucion) {
    $_SESSION['mensaje'] = 'Todos los campos requeridos deben estar completos.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: edit.php?id=' . $id);
    exit;
}

$fecha_actual = date('Y-m-d');
if ($fecha_devolucion < $fecha_actual) {
    $_SESSION['mensaje'] = 'La fecha de devolución no puede ser anterior a hoy.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: edit.php?id=' . $id);
    exit;
}

try {
    $stmt = $conexion->prepare("UPDATE prestamos 
                                SET fecha_devolucion = :fecha_devolucion, 
                                    observaciones = :observaciones 
                                WHERE id = :id");

    $stmt->bindValue(':fecha_devolucion', $fecha_devolucion);
    $stmt->bindValue(':observaciones', $observaciones);
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['mensaje'] = 'Préstamo actualizado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al actualizar el préstamo: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: edit.php?id=' . $id);
    exit;
}
