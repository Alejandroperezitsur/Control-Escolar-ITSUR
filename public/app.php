<?php
// Entry point moderno con router y vistas. Compatible con XAMPP.

// Autoload Composer si existe
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // Autoloader PSR-4 simple para "App\\" → "src/"
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) { return; }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) { require $file; }
    });
}

// Inicializa sesión y configuración defensiva
require_once __DIR__ . '/../config/session.php';
// Reutiliza la conexión existente
require_once __DIR__ . '/../config/db.php';

use App\Kernel;
use App\Http\Router;
use App\Http\Routes;

Kernel::boot();
$pdo = \Database::getInstance()->getConnection();

$router = new Router();

// Registrar rutas
Routes::register($router, $pdo);

// Despachar
$router->dispatch();
