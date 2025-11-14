<?php
include __DIR__ . '/../../config/config.php';
include __DIR__ . '/../permiso.php';

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

      <h1 class="text-lg font-bold mb-6 text-gray-700 text-center">Crear nuevo usuario</h1>
      <div class="mb-5">
        <label for="nombre" class="block mb-2 text-sm font-medium text-gray-900">Nombre</label>
        <input type="text" name="nombre" id="nombre" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" />
      </div>

      <div class="mb-5">
        <label for="email" class="block mb-2 text-sm font-medium text-gray-900">Correo electrónico</label>
        <input type="email" name="email" id="email" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" />
      </div>

      <div class="mb-5">
        <label for="password" class="block mb-2 text-sm font-medium text-gray-900">Contraseña</label>
        <input type="password" name="password" id="password" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5" />
      </div>

      <div class="mb-6">
        <label for="tipo_usuario" class="block mb-2 text-sm font-medium text-gray-900">Tipo de usuario</label>
        <select name="tipo_usuario" id="tipo_usuario" required
          class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5">
          <option value="estudiante">Estudiante</option>
          <option value="profesor">Profesor</option>
          <option value="administrador">Administrador</option>
        </select>
      </div>

      <button type="submit"
        class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm w-full px-5 py-2.5 text-center">
        Crear Usuario
      </button>
    </form>
  </main>


  <?php include __DIR__ . '/../../includes/footer.php'; ?>