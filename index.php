<?php
// Redirigir a la app principal si es necesario
if (file_exists(__DIR__ . '/public/index.php')) {
    require __DIR__ . '/public/index.php';
    exit;
}
// Si no existe, mostrar mensaje de error simple
echo 'Archivo principal no encontrado.';