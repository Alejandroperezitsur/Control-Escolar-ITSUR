<?php
// Endurecimiento de sesión: cookies seguras, SameSite y modo estricto
if (session_status() === PHP_SESSION_NONE) {
    // Configuración defensiva antes de iniciar la sesión
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.cookie_httponly', '1');
    // Samesite para mitigar CSRF básico en navegaciones
    // Para PHP >= 7.3 se puede pasar como opción; por compatibilidad, también seteamos ini.
    if (PHP_VERSION_ID >= 70300) {
        $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https'
        );
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        ini_set('session.cookie_samesite', 'Strict');
    } else {
        // Fallback para versiones antiguas
        ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');
        ini_set('session.cookie_samesite', 'Strict');
    }

    session_start();

    try {
        $config = @include __DIR__ . '/../config/config.php';
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
        $timeout = 3600;
        if (is_array($config) && isset($config['security']['session_timeout'])) {
            $timeout = (int)$config['security']['session_timeout'] ?: $timeout;
        }
        $now = time();
        $last = isset($_SESSION['last_activity']) ? (int)$_SESSION['last_activity'] : $now;
        // Si hay usuario autenticado y excede el tiempo, destruir sesión
        if (isset($_SESSION['user_id']) && ($now - $last) > $timeout) {
            // Regenerar ID y limpiar datos de sesión
            session_regenerate_id(true);
            $_SESSION = [];
            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
            session_destroy();
            // Redirigir a login con código de expiración
            header('Location: /index.php?code=440');
            exit;
        }
        // Actualizar marca de tiempo de actividad
        $_SESSION['last_activity'] = $now;
    } catch (Throwable $e) {
        // No bloquear la app por errores en lectura de config
        $_SESSION['last_activity'] = time();
    }
}
?>
