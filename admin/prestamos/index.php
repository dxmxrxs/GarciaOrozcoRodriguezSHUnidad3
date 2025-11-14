<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

try {
    $stmt = $conexion->query("
        SELECT p.*, 
               u.nombre AS usuario, 
               l.titulo AS libro 
        FROM prestamos p
        INNER JOIN usuarios u ON p.usuario_id = u.id
        INNER JOIN libros l ON p.libro_id = l.id
        ORDER BY p.id DESC
    ");
    $prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener préstamos: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Prestamos | Biblioteca</title>
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
        : 'text-red-600 bg-red-50 border-red-600' ?> "
    >
      <strong><?= $_SESSION['tipo_mensaje'] === 'success' ? '¡Éxito!' : '¡Error!' ?></strong>
      <?= $_SESSION['mensaje'] ?>
    </div>
    <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
  <?php endif; ?>

  <div class="flex justify-between items-center mb-6">
    <h1 class="text-lg font-bold text-gray-700">Lista de Préstamos</h1>
    <a href="create.php" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm font-semibold transition">
      Nuevo
    </a>
  </div>

  <div class="max-w-full">
    <table id="tabla-prestamos" class="display w-full bg-white rounded shadow overflow-hidden">
      <thead class="bg-gray-200 text-gray-700">
        <tr>
          <th class="py-2 px-4 text-left text-sm font-semibold">ID</th>
          <th class="py-2 px-4 text-left text-sm font-semibold">Usuario</th>
          <th class="py-2 px-4 text-left text-sm font-semibold">Libro</th>
          <th class="py-2 px-4 text-left text-sm font-semibold">Fecha Préstamo</th>
          <th class="py-2 px-4 text-left text-sm font-semibold">Fecha Devolución</th>
          <th class="py-2 px-4 text-left text-sm font-semibold">Devolución Real</th>
          <th class="py-2 px-4 text-left text-sm font-semibold">Cantidad</th>
          <th class="py-2 px-4 text-left text-sm font-semibold">Estado</th>
          <th class="py-2 px-4 text-center text-sm font-semibold">Acciones</th>
        </tr>
      </thead>
      <tbody>
          <?php foreach ($prestamos as $prestamo): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="py-2 px-4 text-sm"><?= $prestamo['id'] ?></td>
              <td class="py-2 px-4 text-sm"><?= htmlspecialchars($prestamo['usuario']) ?></td>
              <td class="py-2 px-4 text-sm"><?= htmlspecialchars($prestamo['libro']) ?></td>
              <td class="py-2 px-4 text-sm"><?= date('d/m/Y', strtotime($prestamo['fecha_prestamo'])) ?></td>
              <td class="py-2 px-4 text-sm"><?= date('d/m/Y', strtotime($prestamo['fecha_devolucion'])) ?></td>
              <td class="py-2 px-4 text-sm">
                <?= $prestamo['fecha_devolucion_real'] ? date('d/m/Y', strtotime($prestamo['fecha_devolucion_real'])) : '<span class="text-gray-400 italic">Pendiente</span>' ?>
              </td>
              <td class="py-2 px-4 text-sm"><?= $prestamo['cantidad'] ?></td>
              <td class="py-2 px-4 text-sm">
                <?php
                  $estadoReal = $prestamo['estado'];
                  
                  if ($estadoReal === 'activo' && strtotime($prestamo['fecha_devolucion']) < strtotime(date('Y-m-d'))) {
                    $estadoVista = 'vencido';
                  } else {
                    $estadoVista = $estadoReal;
                  }

                  $estilos = [
                    'activo' => 'bg-blue-100 text-blue-800',
                    'devuelto' => 'bg-green-100 text-green-800',
                    'vencido' => 'bg-red-100 text-red-800',
                    'anulado' => 'bg-gray-100 text-gray-800'
                  ];
                  $clase = $estilos[$estadoVista] ?? 'bg-gray-100 text-gray-800';
                ?>
                <span class="<?= $clase ?> text-xs font-medium px-2.5 py-0.5 rounded">
                  <?= ucfirst($estadoVista) ?>
                </span>
              </td>

              <td class="py-2 px-4 text-center space-x-2">
                <?php if ($prestamo['estado'] === 'activo'): ?>
                  <a href="edit.php?id=<?= $prestamo['id'] ?>" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm font-medium transition">
                    Editar
                  </a>

                  <form action="cancel.php" method="POST" class="inline" onsubmit="return confirm('¿Anular este préstamo? Esta acción no se puede deshacer.');">
                    <input type="hidden" name="id" value="<?= $prestamo['id'] ?>">
                    <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-3 py-1.5 rounded text-sm font-medium transition">
                      Anular
                    </button>
                  </form>
                  
                  <form action="return.php" method="POST" class="inline" onsubmit="return confirm('¿Confirmar devolución del libro?');">
                    <input type="hidden" name="id" value="<?= $prestamo['id'] ?>">
                    <button type="submit" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm font-medium transition">
                      Devolver
                    </button>
                  </form>

                <?php else: ?>
                  <span class="text-sm italic text-gray-500">
                    <?= $prestamo['estado'] === 'anulado' ? 'Préstamo anulado' : 'Préstamo cerrado' ?>
                  </span>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<script>
  $(document).ready(function () {
    $('#tabla-prestamos').DataTable({
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

