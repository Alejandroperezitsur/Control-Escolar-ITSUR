<?php
$config = @include __DIR__ . '/config/config.php';
$debug = false;
$env = 'local';
if (is_array($config) && isset($config['app'])) {
    $debug = (bool)($config['app']['debug'] ?? false);
    $env = (string)($config['app']['env'] ?? 'local');
}
if ($env === 'production' || !$debug) {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
} else {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
}
// Redirigir a la app principal si es necesario
if (file_exists(__DIR__ . '/public/index.php')) {
    require __DIR__ . '/public/index.php';
    exit;
}
// Si no existe, mostrar mensaje de error simple
echo 'Archivo principal no encontrado.';
