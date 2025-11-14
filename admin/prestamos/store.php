<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

// Obtener y limpiar datos
$usuario_id = $_POST['usuario_id'] ?? '';
$libro_id = $_POST['libro_id'] ?? '';
$cantidad = $_POST['cantidad'] ?? '';
$fecha_devolucion = $_POST['fecha_devolucion'] ?? '';
$observaciones = trim($_POST['observaciones'] ?? '');

// Validación básica
if (empty($usuario_id) || empty($libro_id) || empty($fecha_devolucion) || empty($cantidad)) {
    $_SESSION['mensaje'] = 'Por favor, completa todos los campos obligatorios.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}

// Validar que la fecha de devolución no sea menor que hoy
$hoy = date('Y-m-d');
if (strtotime($fecha_devolucion) < strtotime($hoy)) {
    $_SESSION['mensaje'] = 'La fecha de devolución no puede ser anterior a hoy.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}

if (!is_numeric($cantidad) || $cantidad < 1) {
    $_SESSION['mensaje'] = 'La cantidad debe ser un número mayor a 0.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}

try {
    // Verificar que el libro exista, esté activo y tenga stock suficiente
    $stmtLibro = $conexion->prepare("SELECT cantidad_disponible FROM libros WHERE id = :libro_id AND estado = 'activo'");
    $stmtLibro->bindValue(':libro_id', $libro_id, PDO::PARAM_INT);
    $stmtLibro->execute();
    $libro = $stmtLibro->fetch(PDO::FETCH_ASSOC);

    if (!$libro || $libro['cantidad_disponible'] < $cantidad) {
        $_SESSION['mensaje'] = 'No hay suficientes unidades disponibles del libro.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: create.php');
        exit;
    }

    // Insertar préstamo
    $stmt = $conexion->prepare("
        INSERT INTO prestamos (usuario_id, libro_id, cantidad, fecha_prestamo, fecha_devolucion, observaciones, estado)
        VALUES (:usuario_id, :libro_id, :cantidad, NOW(), :fecha_devolucion, :observaciones, 'activo')
    ");
    $stmt->bindValue(':usuario_id', $usuario_id);
    $stmt->bindValue(':libro_id', $libro_id);
    $stmt->bindValue(':cantidad', $cantidad);
    $stmt->bindValue(':fecha_devolucion', $fecha_devolucion);
    $stmt->bindValue(':observaciones', $observaciones);
    $stmt->execute();

    // Actualizar stock del libro
    $stmtUpdate = $conexion->prepare("UPDATE libros SET cantidad_disponible = cantidad_disponible - :cantidad WHERE id = :libro_id");
    $stmtUpdate->bindValue(':cantidad', $cantidad, PDO::PARAM_INT);
    $stmtUpdate->bindValue(':libro_id', $libro_id, PDO::PARAM_INT);
    $stmtUpdate->execute();

    $_SESSION['mensaje'] = 'Préstamo registrado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al registrar el préstamo: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}
