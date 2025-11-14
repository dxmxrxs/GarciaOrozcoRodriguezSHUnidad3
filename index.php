<?php
  include 'config/connexion.php';

  session_start();

  $items_per_page = 5;
  $page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
  if ($page < 1) $page = 1;

  $offset = ($page - 1) * $items_per_page;
  $buscar = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';

  try {
      if ($buscar !== '') {
          $totalStmt = $conexion->prepare("SELECT COUNT(*) FROM libros WHERE estado = 'activo' AND titulo LIKE :buscar");
          $totalStmt->bindValue(':buscar', "%$buscar%", PDO::PARAM_STR);
          $totalStmt->execute();
          $totalItems = (int) $totalStmt->fetchColumn();

          $totalPages = (int) ceil($totalItems / $items_per_page);

          $stmt = $conexion->prepare("SELECT * FROM libros WHERE estado = 'activo' AND titulo LIKE :buscar ORDER BY fecha_registro DESC LIMIT :limit OFFSET :offset");
          $stmt->bindValue(':buscar', "%$buscar%", PDO::PARAM_STR);
      } else {
          $totalStmt = $conexion->query("SELECT COUNT(*) FROM libros WHERE estado = 'activo'");
          $totalItems = (int) $totalStmt->fetchColumn();
          $totalPages = (int) ceil($totalItems / $items_per_page);

          $stmt = $conexion->prepare("SELECT * FROM libros WHERE estado = 'activo' ORDER BY fecha_registro DESC LIMIT :limit OFFSET :offset");
      }

      $stmt->bindValue(':limit', $items_per_page, PDO::PARAM_INT);
      $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
      $stmt->execute();
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
  <title>Biblioteca Escolar</title>
  <link rel="shortcut icon" href="img/Gemini_Generated_Image_8cspsf8cspsf8csp.png" type="image/x-icon">

  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/flowbite/1.6.5/flowbite.min.css" rel="stylesheet" />
  <!-- Añadimos animate.css para animaciones -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css"/>
  <style>
    .book-card {
      transition: all 0.3s ease;
    }
    .book-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
    }
    .pagination-link {
      transition: all 0.2s ease;
    }
    .pagination-link:hover {
      transform: scale(1.1);
    }
    .search-input {
      transition: all 0.3s ease;
    }
    .search-input:focus {
      box-shadow: 0 0 0 3px rgba(74, 222, 128, 0.3);
    }
  </style>
</head>
<body class="bg-gray-50">

<?php include 'includes/header.php'; ?>

<main class="max-w-6xl mx-auto py-10 px-4 animate__animated animate__fadeIn">
  <!-- Formulario de búsqueda con animación -->
  <form method="GET" action="" class="max-w-md mx-auto mb-8 animate__animated animate__fadeInDown">
    <div class="relative flex items-center">
      <input
        type="text"
        name="buscar"
        placeholder="Buscar libro por título..."
        value="<?= isset($_GET['buscar']) ? htmlspecialchars($_GET['buscar']) : '' ?>"
        class="search-input w-full px-5 py-3 pr-12 rounded-full border border-gray-300 focus:outline-none focus:border-green-500"
      />
      <button
        type="submit"
        class="absolute right-2 bg-green-600 text-white p-2 rounded-full hover:bg-green-700 transition-colors duration-300"
      >
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
        </svg>
      </button>
    </div>
  </form>

  <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-8 text-center animate__animated animate__fadeIn">
    Listado de Libros
    <span class="block w-20 h-1 bg-green-500 mx-auto mt-2 rounded-full"></span>
  </h1>

  <?php if (count($libros) === 0): ?>
    <div class="text-center py-10 animate__animated animate__fadeIn">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mx-auto text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
      </svg>
      <p class="text-gray-600 mt-4 text-lg">No hay libros disponibles.</p>
      <p class="text-gray-500 mt-2">Intenta con otra búsqueda o revisa más tarde.</p>
    </div>
  <?php else: ?>
    <div class="grid gap-8">
      <?php foreach ($libros as $index => $libro): ?>
        <div class="book-card bg-white rounded-xl shadow-md overflow-hidden hover:shadow-lg transition-all duration-300 animate__animated animate__fadeInUp" style="animation-delay: <?= $index * 0.1 ?>s">
          <div class="flex flex-col md:flex-row">
            <div class="md:w-1/4 p-4 flex justify-center">
              <img 
                src="img/portadas-libros/<?= htmlspecialchars($libro['imagen']) ?>" 
                alt="Portada del libro" 
                class="w-full h-48 md:h-56 object-contain rounded-lg shadow-sm transition-all duration-300 hover:scale-105"
                onerror="this.onerror=null;this.src='img/portada.svg';"
              />
            </div>
            <div class="md:w-3/4 p-6 flex flex-col justify-between">
              <div>
                <div class="flex justify-between items-start">
                  <h2 class="text-2xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($libro['titulo']) ?></h2>
                  <span class="inline-block bg-green-100 text-green-800 text-xs px-2 py-1 rounded-full">
                    <?= $libro['cantidad_disponible'] ?> disponibles
                  </span>
                </div>
                <p class="text-gray-600 mb-4">
                  <span class="font-semibold">Autor:</span> <?= htmlspecialchars($libro['autor']) ?> 
                  | <span class="font-semibold">Año:</span> <?= $libro['anio'] ?>
                </p>
                <?php if (!empty($libro['descripcion'])): ?>
                  <p class="text-gray-700 mb-4 line-clamp-3"><?= htmlspecialchars($libro['descripcion']) ?></p>
                <?php endif; ?>
              </div>
              <div class="flex justify-end">
                <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors duration-300 flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M7 9a2 2 0 012-2h6a2 2 0 012 2v6a2 2 0 01-2 2H9a2 2 0 01-2-2V9z" />
                    <path d="M5 3a2 2 0 00-2 2v6a2 2 0 002 2V5h8a2 2 0 00-2-2H5z" />
                  </svg>
                  Reservar
                </button>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Paginación mejorada -->
    <?php if ($totalPages > 1): ?>
      <div class="mt-12 flex justify-center animate__animated animate__fadeIn">
        <nav class="flex items-center space-x-1">
          <?php if ($page > 1): ?>
            <a 
              href="?page=<?= $page - 1 ?>&buscar=<?= urlencode($buscar) ?>" 
              class="pagination-link px-4 py-2 border rounded-l-lg bg-white text-gray-700 hover:bg-gray-50 transition-all"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
              </svg>
            </a>
          <?php endif; ?>

          <?php 
            // Mostrar solo algunas páginas alrededor de la actual
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);
            
            if ($start > 1) {
              echo '<a href="?page=1&buscar='.urlencode($buscar).'" class="pagination-link px-4 py-2 border bg-white text-gray-700 hover:bg-gray-50">1</a>';
              if ($start > 2) echo '<span class="px-4 py-2 border bg-white text-gray-700">...</span>';
            }
            
            for ($i = $start; $i <= $end; $i++): 
          ?>
            <a 
              href="?page=<?= $i ?>&buscar=<?= urlencode($buscar) ?>" 
              class="pagination-link px-4 py-2 border <?= $i === $page ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> transition-colors"
            >
              <?= $i ?>
            </a>
          <?php endfor; ?>

          <?php 
            if ($end < $totalPages) {
              if ($end < $totalPages - 1) echo '<span class="px-4 py-2 border bg-white text-gray-700">...</span>';
              echo '<a href="?page='.$totalPages.'&buscar='.urlencode($buscar).'" class="pagination-link px-4 py-2 border bg-white text-gray-700 hover:bg-gray-50">'.$totalPages.'</a>';
            }
          ?>

          <?php if ($page < $totalPages): ?>
            <a 
              href="?page=<?= $page + 1 ?>&buscar=<?= urlencode($buscar) ?>" 
              class="pagination-link px-4 py-2 border rounded-r-lg bg-white text-gray-700 hover:bg-gray-50 transition-all"
            >
              <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd" />
              </svg>
            </a>
          <?php endif; ?>
        </nav>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</main>

<?php include 'includes/footer.php'; ?>

<!-- Script para animaciones adicionales -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Animación al hacer hover en las tarjetas de libro
    const bookCards = document.querySelectorAll('.book-card');
    bookCards.forEach(card => {
      card.addEventListener('mouseenter', () => {
        card.classList.add('shadow-lg');
      });
      card.addEventListener('mouseleave', () => {
        card.classList.remove('shadow-lg');
      });
    });
    
    // Efecto al hacer clic en los botones de paginación
    const paginationLinks = document.querySelectorAll('.pagination-link');
    paginationLinks.forEach(link => {
      link.addEventListener('click', (e) => {
        e.preventDefault();
        // Agregar animación de clic
        link.classList.add('animate__animated', 'animate__pulse');
        setTimeout(() => {
          window.location.href = link.href;
        }, 300);
      });
    });
  });
</script>
</body>
</html>