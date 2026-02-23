<?php
namespace App\Http\Middleware;

use PDO;

class PermissionMiddleware
{
    private static ?PDO $pdo = null;

    public static function boot(PDO $pdo): void
    {
        self::$pdo = $pdo;
    }

    public static function requirePermission(string $perm): callable
    {
        return function () use ($perm) {
            if (!isset($_SESSION['user_id'], $_SESSION['role'])) {
                http_response_code(302);
                $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
                header('Location: ' . $base . '/app.php?r=/login');
                return false;
            }
            if (!self::$pdo) {
                http_response_code(500);
                echo 'Permisos no inicializados';
                return false;
            }
            $role = (string)($_SESSION['role'] ?? '');
            $stmt = self::$pdo->prepare('SELECT 1 FROM rol_permiso rp JOIN permisos p ON p.id = rp.permiso_id WHERE rp.rol = :rol AND p.clave = :perm LIMIT 1');
            $stmt->execute([':rol' => $role, ':perm' => $perm]);
            if (!$stmt->fetchColumn()) {
                http_response_code(403);
                echo 'Acceso denegado: permiso requerido ' . htmlspecialchars($perm);
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

