<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php'; 

// Validar que venga un ID válido por POST
if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
    die('ID inválido');
}

$id = (int) $_POST['id'];

try {
    // Verifica que el libro existe antes de eliminar
    $stmt = $conexion->prepare("SELECT * FROM libros WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$libro) {
        $_SESSION['mensaje'] = 'El libro no existe.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: index.php');
        exit;
    }

    // Borra la imagen física del libro al eliminar el registro
    if (!empty($libro['imagen'])) {
        $rutaImagen = __DIR__ . '/../../img/portadas-libros/' . $libro['imagen'];
        if (file_exists($rutaImagen)) {
            unlink($rutaImagen);
        }
    }

    // Eliminar libro
    $stmt = $conexion->prepare("DELETE FROM libros WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    $_SESSION['mensaje'] = 'Libro eliminado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al eliminar el libro: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}
