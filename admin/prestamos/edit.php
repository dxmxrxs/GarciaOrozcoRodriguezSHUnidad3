<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

$id = $_GET['id'] ?? null;

if (!$id) {
  $_SESSION['mensaje'] = 'ID de préstamo no proporcionado.';
  $_SESSION['tipo_mensaje'] = 'error';
  header('Location: index.php');
  exit;
}

try {
  $stmt = $conexion->prepare("SELECT p.*, u.nombre AS usuario_nombre, l.titulo AS libro_titulo 
                              FROM prestamos p 
                              JOIN usuarios u ON p.usuario_id = u.id 
                              JOIN libros l ON p.libro_id = l.id 
                              WHERE p.id = :id");
  $stmt->execute([':id' => $id]);
  $prestamo = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$prestamo) {
    $_SESSION['mensaje'] = 'Préstamo no encontrado.';
    $_SESSION['tipo_mensaje'] = 'error';
    header('Location: index.php');
    exit;
  }

} catch (PDOException $e) {
  die("Error al obtener préstamo: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Editar Préstamo | Biblioteca</title>
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
            : 'text-red-700 bg-red-100 border-red-700' ?>">

        <span class="font-semibold">
          <?= $_SESSION['tipo_mensaje'] === 'success' ? '¡Éxito!' : '¡Error!' ?>
        </span> <?= htmlspecialchars($_SESSION['mensaje']) ?>
      </div>
      <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
    <?php endif; ?>

    <form action="update.php" method="POST" class="max-w-md mx-auto bg-white p-6 rounded shadow">
      <h1 class="text-lg font-bold mb-6 text-gray-700 text-center">Editar Préstamo</h1>

      <input type="hidden" name="id" value="<?= $prestamo['id'] ?>">

      <div class="mb-5">
        <label class="block mb-2 text-sm font-medium text-gray-900">Usuario</label>
        <input type="text" disabled value="<?= htmlspecialchars($prestamo['usuario_nombre']) ?>"
          class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed" />
      </div>

      <div class="mb-5">
        <label class="block mb-2 text-sm font-medium text-gray-900">Libro</label>
        <input type="text" disabled value="<?= htmlspecialchars($prestamo['libro_titulo']) ?>"
          class="bg-gray-100 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5 cursor-not-allowed" />
      </div>

      <div class="mb-5">
        <label for="fecha_devolucion" class="block mb-2 text-sm font-medium text-gray-900">
          Fecha de devolución (Extender plazo)
        </label>
        <input type="date" name="fecha_devolucion" id="fecha_devolucion"
          min="<?= date('Y-m-d') ?>" required
          value="<?= htmlspecialchars($prestamo['fecha_devolucion']) ?>"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5" />
      </div>

      <div class="mb-6">
        <label for="observaciones" class="block mb-2 text-sm font-medium text-gray-900">Observaciones</label>
        <textarea name="observaciones" id="observaciones" rows="3"
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg block w-full p-2.5"><?= htmlspecialchars($prestamo['observaciones']) ?></textarea>
      </div>

      <button type="submit"
        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full px-5 py-2.5 text-center">
        Guardar cambios
      </button>
    </form>
  </main>

  <?php include __DIR__ . '/../../includes/footer.php'; ?>

