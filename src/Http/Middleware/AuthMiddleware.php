<?php
namespace App\Http\Middleware;

class AuthMiddleware
{
    public static function requireAuth(): callable
    {
        return function () {
            if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
                http_response_code(302);
                $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
                header('Location: ' . $base . '/app.php?r=/login');
                return false;
            }
            if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                if (!CsrfMiddleware::checkAuthenticatedPost()) {
                    return false;
                }
            }
            return true;
        };
    }

    public static function requireRole(string $role): callable
    {
        return function () use ($role) {
            if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
                http_response_code(302);
                $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
                header('Location: ' . $base . '/app.php?r=/login');
                return false;
            }
            if ($_SESSION['role'] !== $role) {
                http_response_code(403);
                echo 'Acceso denegado: rol requerido ' . htmlspecialchars($role);
                return false;
            }
            if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                if (!CsrfMiddleware::checkAuthenticatedPost()) {
                    return false;
                }
            }
            return true;
        };
    }

    /**
     * Permite uno de múltiples roles (e.g. ['admin','profesor']).
     */
    public static function requireAnyRole(array $roles): callable
    {
        return function () use ($roles) {
            if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
                http_response_code(302);
                $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
                header('Location: ' . $base . '/app.php?r=/login');
                return false;
            }
            $current = (string)($_SESSION['role'] ?? '');
            if (!in_array($current, $roles, true)) {
                http_response_code(403);
                echo 'Acceso denegado: roles permitidos ' . htmlspecialchars(implode(',', $roles));
                return false;
            }
            if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
                if (!CsrfMiddleware::checkAuthenticatedPost()) {
                    return false;
                }
            }
            return true;
        };
    }
}
