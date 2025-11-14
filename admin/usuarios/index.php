<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

try {
    $stmt = $conexion->query("SELECT * FROM usuarios ORDER BY creado_en DESC");
    $usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Error al obtener usuarios: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Usuarios | Biblioteca</title>
    <link rel="shortcut icon" href="<?= $base_url ?>/img/logo-biblioteca.PNG" type="image/x-icon" />

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

<body class="bg-gray-100">

    <?php include __DIR__ . '/../../includes/header.php'; ?>

    <main class="max-w-6xl mx-auto py-10 px-4">
      <?php if (isset($_SESSION['mensaje'])): ?>
        <div class="p-4 mb-4 text-sm rounded-lg border
        <?= $_SESSION['tipo_mensaje'] === 'success' 
            ? 'text-green-600 bg-green-50 border-green-600 dark:bg-gray-600 dark:text-green-400 dark:border-green-400' 
            : 'text-red-600 bg-red-50 border-red-600 dark:bg-gray-600 dark:text-red-400 dark:border-red-400' ?>" 
        role="alert">
          <span class="font-medium">
            <?= $_SESSION['tipo_mensaje'] === 'success' ? '¡Éxito!' : '¡Error!' ?>
          </span> <?= $_SESSION['mensaje'] ?>
        </div>
        <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
      <?php endif; ?>

        <div class="flex justify-between items-center mb-6">
            <h1 class="text-lg font-bold text-gray-700">Lista de Usuarios</h1>
            <a href="create.php" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded text-sm font-semibold transition">
                Nuevo
            </a>
        </div>

        <div class="max-w-full">
            <table id="tabla-usuarios" class="w-full bg-white rounded shadow overflow-hidden">
                <thead class="bg-gray-200 text-gray-700">
                    <tr>
                        <th class="py-2 px-4 text-left text-sm font-semibold">ID</th>
                        <th class="py-2 px-4 text-left text-sm font-semibold">Nombre</th>
                        <th class="py-2 px-4 text-left text-sm font-semibold">Email</th>
                        <th class="py-2 px-4 text-left text-sm font-semibold">Tipo</th>
                        <th class="py-2 px-4 text-center text-sm font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                        <?php foreach ($usuarios as $usuario): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-2 px-4 text-sm"><?= htmlspecialchars($usuario['id']) ?></td>
                                <td class="py-2 px-4 text-sm"><?= htmlspecialchars($usuario['nombre']) ?></td>
                                <td class="py-2 px-4 text-sm"><?= htmlspecialchars($usuario['email']) ?></td>
                                <td class="py-2 px-4 text-sm capitalize"><?= htmlspecialchars($usuario['tipo_usuario']) ?></td>
                                <td class="py-2 px-4 text-center space-x-2">
                                    <a href="edit.php?id=<?= $usuario['id'] ?>" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1.5 rounded text-sm font-medium transition">
                                        Editar
                                    </a>
                                   
                                    <form action="delete.php" method="POST" onsubmit="return confirm('¿Estás seguro de eliminar este usuario?');" class="inline">
                                    <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
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
    $('#tabla-usuarios').DataTable({
      language: {
        url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json",
        
      },
      pageLength: 5, 
        lengthMenu: [ [5, 10, 25, 50, -1], [5, 10, 25, 50, "Todos"] ],
        responsive: true,
    });
  });
</script>

    <?php include __DIR__ . '/../../includes/footer.php'; ?>

