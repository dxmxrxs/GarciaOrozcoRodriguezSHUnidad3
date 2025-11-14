<?php
  include __DIR__ . '/../../config/config.php';
  include __DIR__ . '/../permiso.php'; 

  try {
      $stmt = $conexion->query("SELECT * FROM libros ORDER BY fecha_registro DESC");
      $libros = $stmt->fetchAll(PDO::FETCH_ASSOC);
  } catch (PDOException $e) {
      die("Error al obtener libros: " . $e->getMessage());
  }
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Libros | Biblioteca</title>
  <link rel="shortcut icon" href="<?= $base_url ?>/img/logo-biblioteca.PNG" type="image/x-icon">

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet" />

  <!-- DataTables CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
  <!-- Responsive extension CSS -->
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.dataTables.min.css">
  <!-- jQuery -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <!-- DataTables core JS -->
  <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
  <!-- Responsive extension JS -->
  <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>

</head>
<body>

<?php include '../../includes/header.php'; ?>


<main class="max-w-6xl mx-auto py-10 px-4">

  <?php if (isset($_SESSION['mensaje'])): ?>
    <div class="p-4 mb-4 text-sm rounded-lg border
      <?= $_SESSION['tipo_mensaje'] === 'success'
        ? 'text-green-600 bg-green-50 border-green-600'
        : 'text-red-600 bg-red-50 border-red-600' ?>"
    >
      <strong><?= $_SESSION['tipo_mensaje'] === 'success' ? '¡Éxito!' : '¡Error!' ?></strong>
      <?= $_SESSION['mensaje'] ?>
    </div>
    <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
  <?php endif; ?>

  <div class="flex justify-between items-center mb-6">
    <h1 class="text-lg font-bold text-gray-700">Lista de Libros</h1>
    <a href="create.php" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm font-semibold transition">
      Nuevo
    </a>
  </div>

  <div class="max-w-full">
  <table id="tabla-libros" class="w-full bg-white rounded shadow overflow-hidden">
    <thead class="bg-gray-200 text-gray-700">
      <tr>
        <th class="py-2 px-4 text-left text-sm font-semibold">ID</th>
        <th class="py-2 px-4 text-left text-sm font-semibold">Título</th>
        <th class="py-2 px-4 text-left text-sm font-semibold">Portada</th>
        <th class="py-2 px-4 text-left text-sm font-semibold">Autor</th>
        <th class="py-2 px-4 text-left text-sm font-semibold">Año</th>
        <th class="py-2 px-4 text-left text-sm font-semibold">Cantidad</th>
        <th class="py-2 px-4 text-left text-sm font-semibold">Estado</th>
        <th class="py-2 px-4 text-center text-sm font-semibold">Acciones</th>
      </tr>
    </thead>
    <tbody>
        <?php foreach ($libros as $libro): ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="py-2 px-4 text-sm"><?= $libro['id'] ?></td>
            <td class="py-2 px-4 text-sm"><?= htmlspecialchars($libro['titulo']) ?></td>
            <td class="py-2 px-4 text-sm">
            <img src="<?= $base_url ?>/img/portadas-libros/<?= htmlspecialchars($libro['imagen']) ?>" 
                alt="Portada" 
                class="w-12 h-16 object-cover rounded"
                onerror="this.onerror=null;this.src='<?= $base_url ?>/img/portada.svg';">
          </td>

            <td class="py-2 px-4 text-sm"><?= htmlspecialchars($libro['autor']) ?></td>
            <td class="py-2 px-4 text-sm"><?= $libro['anio'] ?></td>
            <td class="py-2 px-4 text-sm"><?= $libro['cantidad_disponible'] ?></td>
            <td class="py-2 px-4 text-sm">
              <?php if ($libro['estado'] === 'activo'): ?>
                <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded">Activo</span>
              <?php elseif ($libro['estado'] === 'anulado'): ?>
                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded">Anulado</span>
              <?php else: ?>
                <span class="bg-gray-100 text-gray-800 text-xs font-medium px-2.5 py-0.5 rounded"><?= ucfirst($libro['estado']) ?></span>
              <?php endif; ?>
            </td>

            <td class="py-2 px-4 text-center space-x-2">
              <a href="edit.php?id=<?= $libro['id'] ?>" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm font-medium transition">
                Editar
              </a>
              <form action="delete.php" method="POST" class="inline" onsubmit="return confirm('¿Eliminar este libro?');">
                <input type="hidden" name="id" value="<?= $libro['id'] ?>">
                <button type="submit" class="bg-red-600 text-white px-3 py-1.5 rounded hover:bg-red-700 text-sm font-medium transition">
                  Eliminar
                </button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
    </tbody>
  </table>
  </div>
</main>

<script>
  $(document).ready(function () {
    $('#tabla-libros').DataTable({
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
        
      },
      pageLength: 5, 
      lengthMenu: [ [5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"] ],
      responsive: true,
    });
  });
</script>

<?php include '../../includes/footer.php'; ?>

