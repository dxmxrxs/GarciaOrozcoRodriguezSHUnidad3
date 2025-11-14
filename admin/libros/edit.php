<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php'; 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['mensaje'] = 'ID de libro inválido.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}

$id = (int) $_GET['id'];

try {
    $stmt = $conexion->prepare("SELECT * FROM libros WHERE id = :id");
    $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    $stmt->execute();
    $libro = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$libro) {
        $_SESSION['mensaje'] = 'Libro no encontrado.';
        $_SESSION['tipo_mensaje'] = 'error';
        header('Location: index.php');
        exit;
    }
} catch (PDOException $e) {
    $_SESSION['mensaje'] = 'Error al obtener libro: ' . $e->getMessage();
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Editar Libro | Biblioteca</title>
  <link rel="shortcut icon" href="<?= $base_url ?>/img/logo-biblioteca.PNG" type="image/x-icon" />

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet" />
</head>

<body class="bg-gray-100">

  <?php include __DIR__ . '/../../includes/header.php'; ?>

  <main class="max-w-6xl mx-auto py-10 px-4">

    <?php if (isset($_SESSION['mensaje'])): ?>
      <div class="p-4 mb-4 text-sm rounded-lg border
        <?= $_SESSION['tipo_mensaje'] === 'success'
            ? 'text-green-700 bg-green-100 border-green-700'
            : 'text-red-700 bg-red-100 border-red-700' ?>"
        role="alert">
        <span class="font-semibold">
          <?= $_SESSION['tipo_mensaje'] === 'success' ? '¡Éxito!' : '¡Error!' ?>
        </span> <?= htmlspecialchars($_SESSION['mensaje']) ?>
      </div>
      <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <form action="update.php" method="POST" enctype="multipart/form-data" class="max-w-md mx-auto bg-white p-6 rounded shadow">

      <h1 class="text-lg font-bold mb-6 text-gray-700 text-center">Editar libro</h1>

      <input type="hidden" name="id" value="<?= $libro['id'] ?>" />

      <div class="mb-5">
        <label for="titulo" class="block mb-2 text-sm font-medium text-gray-900">Título</label>
        <input type="text" name="titulo" id="titulo" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
          value="<?= htmlspecialchars($libro['titulo']) ?>" />
      </div>

      <div class="mb-5">
        <label for="autor" class="block mb-2 text-sm font-medium text-gray-900">Autor</label>
        <input type="text" name="autor" id="autor" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
          value="<?= htmlspecialchars($libro['autor']) ?>" />
      </div>

      <div class="mb-5">
        <label for="anio" class="block mb-2 text-sm font-medium text-gray-900">Año</label>
        <input type="number" name="anio" id="anio" min="1900" max="<?= date('Y') ?>" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
          value="<?= $libro['anio'] ?>" />
      </div>

      <div class="mb-5">
        <label for="descripcion" class="block mb-2 text-sm font-medium text-gray-900">Descripción (opcional)</label>
        <textarea name="descripcion" id="descripcion" rows="3"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"><?= htmlspecialchars($libro['descripcion']) ?></textarea>
      </div>

      <div class="mb-5">
        <label for="imagen" class="block mb-2 text-sm font-medium text-gray-900">Cambiar imagen (opcional)</label>
        <input type="file" name="imagen" id="imagen" accept="image/*"
          class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:ring-blue-500 focus:border-blue-500" />
        <?php if ($libro['imagen']): ?>
          <img src="<?= $base_url ?>/img/portadas-libros/<?= htmlspecialchars($libro['imagen']) ?>"
               alt="Portada del libro" class="w-32 h-40 object-cover rounded-md mt-3"
               onerror="this.onerror=null;this.src='<?= $base_url ?>/img/portada.svg';" />
        <?php endif; ?>
      </div>

      <div class="mb-5">
        <label for="cantidad_disponible" class="block mb-2 text-sm font-medium text-gray-900">Cantidad disponible</label>
        <input type="number" name="cantidad_disponible" id="cantidad_disponible" min="1" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
          value="<?= $libro['cantidad_disponible'] ?>" />
      </div>

      <div class="mb-6">
        <label for="estado" class="block mb-2 text-sm font-medium text-gray-900">Estado</label>
        <select name="estado" id="estado" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
          <option value="activo" <?= $libro['estado'] === 'activo' ? 'selected' : '' ?>>Activo</option>
          <option value="anulado" <?= $libro['estado'] === 'anulado' ? 'selected' : '' ?>>Anulado</option>
        </select>
      </div>

      <button type="submit"
        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full px-5 py-2.5 text-center">
        Guardar Cambios
      </button>
    </form>
  </main>

  <?php include __DIR__ . '/../../includes/footer.php'; ?>

