<?php
include __DIR__ . '/config/config.php';
session_start();

// Solo permitir estudiantes o profesores
if (!in_array($_SESSION['usuario']['tipo_usuario'], ['estudiante', 'profesor'])) {
    header("Location: $base_url");
    exit;
}

$usuario_id = $_SESSION['usuario']['id'];

try {
    $stmt = $conexion->prepare("
        SELECT p.*, l.titulo, l.imagen 
        FROM prestamos p
        INNER JOIN libros l ON p.libro_id = l.id
        WHERE p.usuario_id = :usuario_id
        ORDER BY p.fecha_prestamo DESC
    ");
    $stmt->execute(['usuario_id' => $usuario_id]);
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
  <title>Mis Préstamos | Biblioteca</title>
  <link rel="shortcut icon" href="<?= $base_url ?>/img/logo-biblioteca.PNG" type="image/x-icon" />
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet" />
</head>

<body class="bg-gray-100">
<?php include __DIR__ . '/includes/header.php'; ?>

<main class="max-w-6xl mx-auto py-10 px-4">
  <h1 class="text-xl font-bold text-gray-700 mb-6">Mis Préstamos</h1>

  <?php if (count($prestamos) === 0): ?>
    <div class="text-gray-600 text-center">No has realizado ningún préstamo todavía.</div>
  <?php else: ?>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
      <?php foreach ($prestamos as $prestamo): ?>
        <div class="bg-white shadow rounded overflow-hidden flex">
          <!-- Imagen a la izquierda (fija) -->
          <div class="w-1/3">
            <img 
              src="<?= $base_url ?>/img/portadas-libros/<?= htmlspecialchars($prestamo['imagen']) ?>" 
              alt="<?= htmlspecialchars($prestamo['titulo']) ?>" 
              class="w-full h-full object-cover"
              onerror="this.onerror=null;this.src='<?= $base_url ?>/img/portada.svg';"
            >
          </div>

          <!-- Info a la derecha -->
          <div class="w-2/3 p-3 flex flex-col justify-between">
            <div>
              <h2 class="font-bold text-sm text-gray-800 mb-1"><?= htmlspecialchars($prestamo['titulo']) ?></h2>
              <p class="text-xs text-gray-600 mb-0.5">
                <span class="font-medium">Préstamo:</span> <?= date("d/m/Y", strtotime($prestamo['fecha_prestamo'])) ?>
              </p>
              <p class="text-xs text-gray-600 mb-0.5">
                <span class="font-medium">Devolver hasta:</span> <?= date("d/m/Y", strtotime($prestamo['fecha_devolucion'])) ?>
              </p>
              <p class="text-xs text-gray-600 mb-0.5">
                <span class="font-medium">Cantidad:</span> <?= htmlspecialchars($prestamo['cantidad']) ?>
              </p>
            </div>
            <?php
  $estadoReal = $prestamo['estado'];
  $esVencido = $estadoReal === 'activo' && strtotime($prestamo['fecha_devolucion']) < strtotime(date('Y-m-d'));
  $estadoVista = $esVencido ? 'vencido' : $estadoReal;

  $estilos = [
    'activo' => 'bg-blue-100 text-blue-800',
    'devuelto' => 'bg-green-100 text-green-800',
    'vencido' => 'bg-red-100 text-red-800',
    'anulado' => 'bg-gray-100 text-gray-800'
  ];
  $clase = $estilos[$estadoVista] ?? 'bg-gray-100 text-gray-800';
?>

<p class="text-xs mt-2">
  <span class="<?= $clase ?> text-xs font-medium px-2.5 py-0.5 rounded">
    <?= ucfirst($estadoVista) ?>
  </span>

  <?php if ($esVencido): ?>
    <span class="block mt-1 text-red-600 text-[11px] font-medium">
      ⚠️Recuerde que este libro ya debería haber sido devuelto. ⏰
    </span>
  <?php endif; ?>
</p>

          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>


<?php include __DIR__ . '/includes/footer.php'; ?>

