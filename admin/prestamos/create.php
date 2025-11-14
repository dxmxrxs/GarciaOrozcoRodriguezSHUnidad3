<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

try {
    // Obtener usuarios
    $stmtUsuarios = $conexion->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC");
    $usuarios = $stmtUsuarios->fetchAll(PDO::FETCH_ASSOC);

    // Obtener libros activos con al menos 1 ejemplar
    $stmtLibros = $conexion->query("SELECT id, titulo FROM libros WHERE estado = 'activo' AND cantidad_disponible > 0 ORDER BY titulo ASC");
    $libros = $stmtLibros->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener datos: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Nuevo Préstamo | Biblioteca</title>
  <link rel="shortcut icon" href="<?= $base_url ?>/img/logo-biblioteca.PNG" type="image/x-icon" />

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet" />

  <!-- Select2 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

  <!-- jQuery (Select2 depende de jQuery) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <!-- Select2 JS -->
  <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

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

    <form action="store.php" method="POST" class="max-w-md mx-auto bg-white p-6 rounded shadow">
      <h1 class="text-lg font-bold mb-6 text-gray-700 text-center">Registrar nuevo préstamo</h1>

      <div class="mb-5">
        <label for="usuario_id" class="block mb-2 text-sm font-medium text-gray-900">Usuario</label>
        <select name="usuario_id" id="usuario_id" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
          <option value="">Seleccione un usuario</option>
          <?php foreach ($usuarios as $usuario): ?>
            <?php if ($usuario['tipo_usuario'] === 'estudiante'): ?>
              <option value="<?= $usuario['id'] ?>"><?= htmlspecialchars($usuario['nombre']) ?></option>
            <?php endif; ?>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-5">
        <label for="libro_id" class="block mb-2 text-sm font-medium text-gray-900">Libro</label>
        <select name="libro_id" id="libro_id" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
          <option value="">Seleccione un libro</option>
          <?php foreach ($libros as $libro): ?>
            <option value="<?= $libro['id'] ?>"><?= htmlspecialchars($libro['titulo']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-5">
        <label for="fecha_devolucion" class="block mb-2 text-sm font-medium text-gray-900">Fecha de devolución</label>
        <input type="date" name="fecha_devolucion" id="fecha_devolucion" min="<?= date('Y-m-d') ?>" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" />
      </div>

      <div class="mb-5">
        <label for="cantidad" class="block mb-2 text-sm font-medium text-gray-900">Cantidad</label>
        <input type="number" name="cantidad" id="cantidad" min="1" value="" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" />
      </div>

      <div class="mb-6">
        <label for="observaciones" class="block mb-2 text-sm font-medium text-gray-900">Observaciones</label>
        <textarea name="observaciones" id="observaciones" rows="3"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5"
          ></textarea>
      </div>

      <button type="submit"
        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full px-5 py-2.5 text-center">
        Registrar Préstamo
      </button>
    </form>
  </main>

  <script>
  $(document).ready(function() {
      $('#usuario_id').select2({
        placeholder: "Seleccione un usuario",
        width: '100%'
      });
      $('#libro_id').select2({
        placeholder: "Seleccione un libro",
        width: '100%'
      });
    });
  </script>

  <?php include __DIR__ . '/../../includes/footer.php'; ?>
