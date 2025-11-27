<?php
namespace App\Http;

class Router
{
    private array $routes = [];

    public function get(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('GET', $path, $handler, $middlewares);
    }

    public function post(string $path, callable $handler, array $middlewares = []): void
    {
        $this->add('POST', $path, $handler, $middlewares);
    }

    private function add(string $method, string $path, callable $handler, array $middlewares): void
    {
        $this->routes[$method][$path] = ['handler' => $handler, 'middlewares' => $middlewares];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        // Soporte de querystring r=/ruta para entornos sin PATH_INFO o reescrituras
        $queryRoute = isset($_GET['r']) ? (string)$_GET['r'] : null;
        // Preferir PATH_INFO si está disponible (ej. /app.php/login → PATH_INFO=/login)
        $pathInfo = isset($_SERVER['PATH_INFO']) ? (string)$_SERVER['PATH_INFO'] : null;
        $uri = $queryRoute !== null && $queryRoute !== ''
            ? $queryRoute
            : (($pathInfo !== null && $pathInfo !== '')
                ? $pathInfo
                : parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH));

        // Normalizar quitando el directorio del script (subcarpetas en XAMPP)
        $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        if ($scriptDir && $scriptDir !== '/') {
            $uri = preg_replace('#^' . preg_quote($scriptDir, '#') . '#', '', $uri);
            if ($uri === '') { $uri = '/'; }
        }
        // Si la ruta incluye el nombre del script (ej. /app.php/login), eliminarlo
        $scriptBase = '/' . basename($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptBase !== '/' && strpos($uri, $scriptBase) === 0) {
            $uri = substr($uri, strlen($scriptBase)) ?: '/';
        }

        $route = $this->routes[$method][$uri] ?? null;
        if (!$route) {
            http_response_code(404);
            echo 'Ruta no encontrada: ' . htmlspecialchars($method . ' ' . $uri);
            return;
        }

        foreach ($route['middlewares'] as $mw) {
            $result = $mw();
            if ($result === false) {
                return; // Middleware ya emitió respuesta
            }
        }

        $result = $route['handler']();
        if (is_string($result)) {
            echo $result;
        }
    }
}
