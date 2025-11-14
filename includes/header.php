<?php
include __DIR__ . '/../config/config.php'; 
?>
<header>
  <nav class="bg-white border border-gray-200">
    <div class="max-w-screen-xl flex flex-wrap items-center justify-between mx-auto p-4">
      <a href="<?= $base_url ?>/index.php" class="flex items-center space-x-3">
        <img src="<?= $base_url ?>/img/Gemini_Generated_Image_8cspsf8cspsf8csp.png" class="h-8" alt="Logo" />
        <span class="self-center text-2xl font-semibold whitespace-nowrap text-gray-900">Biblioteca Escolar</span>
      </a>

      <button data-collapse-toggle="navbar-default" type="button"
        class="inline-flex items-center p-2 w-10 h-10 justify-center text-sm text-gray-600 rounded-lg md:hidden hover:bg-gray-100 focus:outline-none focus:ring-2 focus:ring-gray-300"
        aria-controls="navbar-default" aria-expanded="false">
        <span class="sr-only">Abrir menú</span>
        <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none"
          viewBox="0 0 17 14" stroke="currentColor" stroke-width="2"
          stroke-linecap="round" stroke-linejoin="round">
          <path d="M1 1h15M1 7h15M1 13h15" />
        </svg>
      </button>

      <div class="hidden w-full md:block md:w-auto" id="navbar-default">
        <ul class="font-medium flex flex-col p-4 md:p-0 mt-4 border border-gray-100 rounded-lg bg-gray-50 md:flex-row md:space-x-8 md:mt-0 md:border-0 md:bg-white">

          <li><a href="<?= $base_url ?>/index.php" class="block py-2 px-3 text-green-700 hover:text-green-800">Inicio</a></li>

          <?php if (isset($_SESSION['usuario'])): ?>
            <?php if ($_SESSION['usuario']['tipo_usuario'] === 'administrador'): ?>
              <li><a href="<?= $base_url ?>/admin/libros/index.php" class="block py-2 px-3 text-green-700 hover:text-green-800">Libros</a></li>
              <li><a href="<?= $base_url ?>/admin/prestamos/index.php" class="block py-2 px-3 text-green-700 hover:text-green-800">Préstamos</a></li>
              <li><a href="<?= $base_url ?>/admin/usuarios/index.php" class="block py-2 px-3 text-green-700 hover:text-green-800">Usuarios</a></li>
              
            <?php elseif ($_SESSION['usuario']['tipo_usuario'] === 'estudiante' || $_SESSION['usuario']['tipo_usuario'] === 'profesor'): ?>
              <li><a href="<?= $base_url ?>/mis-prestamos.php" class="block py-2 px-3 text-green-700 hover:text-green-800">Mis Préstamos</a></li>
            <?php endif; ?>
          <?php endif; ?>

          <li>
            <?php if (isset($_SESSION['usuario'])): ?>
              <a href="<?= $base_url ?>/logout.php" class="block py-2 px-4 bg-red-600 text-white rounded hover:bg-red-700 transition">Cerrar sesión</a>
            <?php else: ?>
              <a href="<?= $base_url ?>/login.php" class="block py-2 px-4 bg-gray-600 text-white rounded hover:bg-gray-700 transition">Inicio</a>
            <?php endif; ?>
          </li>
        </ul>
      </div>
    </div>
  </nav>
</header>
