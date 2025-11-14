<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

$id = $_POST['id'] ?? null;

if (!$id) {
    $_SESSION['mensaje'] = 'ID de préstamo no válido.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}

try {
    // Obtener préstamo activo para saber qué libro devolver
    $stmt = $conexion->prepare("SELECT libro_id FROM prestamos WHERE id = :id AND estado = 'activo'");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$prestamo) {
        $_SESSION['mensaje'] = 'Préstamo no encontrado o no está activo.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: index.php');
        exit;
    }

    $conexion->beginTransaction();

    // Actualizar el préstamo: marcar como devuelto y guardar la fecha real (hoy)
    $stmtUpdatePrestamo = $conexion->prepare("
        UPDATE prestamos
        SET fecha_devolucion_real = CURDATE(), estado = 'devuelto'
        WHERE id = :id
    ");
    $stmtUpdatePrestamo->bindValue(':id', $id, PDO::PARAM_INT);
    $stmtUpdatePrestamo->execute();

    // Incrementar cantidad disponible del libro
    $stmtUpdateLibro = $conexion->prepare("
        UPDATE libros
        SET cantidad_disponible = cantidad_disponible + 1
        WHERE id = :libro_id
    ");
    $stmtUpdateLibro->bindValue(':libro_id', $prestamo['libro_id'], PDO::PARAM_INT);
    $stmtUpdateLibro->execute();

    $conexion->commit();

    $_SESSION['mensaje'] = 'Préstamo marcado como devuelto y stock actualizado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    $conexion->rollBack();
    $_SESSION['mensaje'] = 'Error al procesar la devolución: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}
