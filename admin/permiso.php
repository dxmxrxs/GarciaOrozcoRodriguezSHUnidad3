<?php
include __DIR__ . '/../config/config.php';
session_start();

// Verifica si hay sesión
if (!isset($_SESSION['usuario'])) {
    header("Location: $base_url/login.php");
    exit;
}

// Verifica si es administrador
if ($_SESSION['usuario']['tipo_usuario'] !== 'administrador') {
    header("Location: $base_url/index.php");
    exit;
}
