<?php
// Mostrar errores para depuración en hosting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Redirigir a la app principal si es necesario
if (file_exists(__DIR__ . '/public/index.php')) {
    require __DIR__ . '/public/index.php';
    exit;
}
// Si no existe, mostrar mensaje de error simple
echo 'Archivo principal no encontrado.';