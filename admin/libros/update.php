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
$titulo = trim($_POST['titulo'] ?? '');
$autor = trim($_POST['autor'] ?? '');
$anio = trim($_POST['anio'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$cantidad_disponible = trim($_POST['cantidad_disponible'] ?? '');
$estado = $_POST['estado'] ?? 'activo';

// Validaciones
if ($id <= 0 || empty($titulo) || empty($autor) || empty($anio) || $cantidad_disponible === '') {
    $_SESSION['mensaje'] = 'Por favor, completa todos los campos obligatorios.';
    $_SESSION['tipo_mensaje'] = 'error';
    header("Location: edit.php?id=$id");
    exit;
}

if (!is_numeric($anio) || $anio < 1900 || $anio > (int)date('Y')) {
    $_SESSION['mensaje'] = 'El año no es válido.';
    $_SESSION['tipo_mensaje'] = 'error';
    header("Location: edit.php?id=$id");
    exit;
}

if (!is_numeric($cantidad_disponible) || $cantidad_disponible < 1) {
    $_SESSION['mensaje'] = 'La cantidad disponible debe ser al menos 1.';
    $_SESSION['tipo_mensaje'] = 'error';
    header("Location: edit.php?id=$id");
    exit;
}

try {
    // Obtener la imagen actual para eliminarla si se sube una nueva
    $stmt = $conexion->prepare("SELECT imagen FROM libros WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$libro) {
        $_SESSION['mensaje'] = 'Libro no encontrado.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: index.php');
        exit;
    }

    $nombre_imagen = $libro['imagen']; // Imagen actual

    // Procesar nueva imagen (opcional)
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $archivo = $_FILES['imagen'];
        $ext_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
        $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

        if (!in_array($ext, $ext_permitidas)) {
            $_SESSION['mensaje'] = 'Tipo de imagen no permitido. Usa jpg, jpeg, png o gif.';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: edit.php?id=$id");
            exit;
        }

        $carpeta_portadas = __DIR__ . '/../../img/portadas-libros/';
        // Eliminar la imagen antigua si existe
        if ($nombre_imagen && file_exists($carpeta_portadas . $nombre_imagen)) {
            unlink($carpeta_portadas . $nombre_imagen);
        }

        // Generar nombre único para la nueva imagen
        $nombre_imagen = uniqid('libro_', true) . '.' . $ext;
        $ruta_destino = $carpeta_portadas . $nombre_imagen;

        if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
            $_SESSION['mensaje'] = 'Error al subir la nueva imagen.';
            $_SESSION['tipo_mensaje'] = 'error';
            header("Location: edit.php?id=$id");
            exit;
        }
    }

    // Actualizar libro
    $stmt = $conexion->prepare("UPDATE libros SET 
        titulo = :titulo, 
        autor = :autor, 
        anio = :anio, 
        descripcion = :descripcion, 
        imagen = :imagen, 
        cantidad_disponible = :cantidad_disponible, 
        estado = :estado
        WHERE id = :id");

    $stmt->bindValue(':titulo', $titulo);
    $stmt->bindValue(':autor', $autor);
    $stmt->bindValue(':anio', $anio);
    $stmt->bindValue(':descripcion', $descripcion);
    $stmt->bindValue(':imagen', $nombre_imagen);
    $stmt->bindValue(':cantidad_disponible', $cantidad_disponible);
    $stmt->bindValue(':estado', $estado);
    $stmt->bindValue(':id', $id);

    $stmt->execute();

    $_SESSION['mensaje'] = 'Libro actualizado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al actualizar el libro: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';
    header("Location: edit.php?id=$id");
    exit;
}
