<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php'; 

// Recibir datos del formulario
$titulo = trim($_POST['titulo'] ?? '');
$autor = trim($_POST['autor'] ?? '');
$anio = trim($_POST['anio'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$cantidad_disponible = trim($_POST['cantidad_disponible'] ?? '');
$estado = $_POST['estado'] ?? 'activo';

// Validaciones básicas
if (empty($titulo) || empty($autor) || empty($anio) || $cantidad_disponible === '') {
    $_SESSION['mensaje'] = 'Por favor, completa todos los campos obligatorios.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}

if (!is_numeric($anio) || $anio < 1900 || $anio > (int)date('Y')) {
    $_SESSION['mensaje'] = 'El año no es válido.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}

if (!is_numeric($cantidad_disponible) || $cantidad_disponible < 1) {
    $_SESSION['mensaje'] = 'La cantidad disponible debe ser mínimo 1.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}

// Procesar imagen (opcional)
$nombre_imagen = null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $archivo = $_FILES['imagen'];
    $ext_permitidas = ['jpg', 'jpeg', 'png', 'gif'];
    $ext = strtolower(pathinfo($archivo['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $ext_permitidas)) {
        $_SESSION['mensaje'] = 'Tipo de imagen no permitido. Usa jpg, jpeg, png o gif.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: create.php');
        exit;
    }

    $carpeta_portadas = __DIR__ . '/../../img/portadas-libros/';

    // Generar nombre único para la imagen
    $nombre_imagen = uniqid('libro_', true) . '.' . $ext;
    $ruta_destino = $carpeta_portadas . $nombre_imagen;

    if (!move_uploaded_file($archivo['tmp_name'], $ruta_destino)) {
        $_SESSION['mensaje'] = 'Error al subir la imagen.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: create.php');
        exit;
    }
}

try {
    $stmt = $conexion->prepare("INSERT INTO libros (titulo, autor, anio, descripcion, imagen, cantidad_disponible, estado) VALUES (:titulo, :autor, :anio, :descripcion, :imagen, :cantidad_disponible, :estado)");
    $stmt->bindValue(':titulo', $titulo);
    $stmt->bindValue(':autor', $autor);
    $stmt->bindValue(':anio', $anio);
    $stmt->bindValue(':descripcion', $descripcion);
    $stmt->bindValue(':imagen', $nombre_imagen);
    $stmt->bindValue(':cantidad_disponible', $cantidad_disponible);
    $stmt->bindValue(':estado', $estado);
    $stmt->execute();

    $_SESSION['mensaje'] = 'Libro agregado correctamente.';
    $_SESSION['tipo_mensaje'] = 'success';
    header('Location: index.php');
    exit;

} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al agregar el libro: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: create.php');
    exit;
}
