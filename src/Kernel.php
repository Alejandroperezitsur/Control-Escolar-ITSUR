<?php
namespace App;

use App\Http\SecurityHeaders;

class Kernel
{
    public static function boot(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.use_strict_mode', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_httponly', '1');
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
                ini_set('session.cookie_secure', (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? '1' : '0');
                ini_set('session.cookie_samesite', 'Strict');
            }

            session_start();

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
            if (isset($_SESSION['user_id']) && ($now - $last) > $timeout) {
                session_regenerate_id(true);
                $_SESSION = [];
                if (ini_get('session.use_cookies')) {
                    $params = session_get_cookie_params();
                    setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
                }
                session_destroy();
                $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
                $target = $base !== '' ? ($base . '/app.php?r=/login&code=440') : '/public/app.php?r=/login&code=440';
                header('Location: ' . $target);
                exit;
            }
            $_SESSION['last_activity'] = $now;
        }

        if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        SecurityHeaders::apply();
    }
}
