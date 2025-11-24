<?php
namespace App;

use App\Http\SecurityHeaders;

class Kernel
{
    public static function boot(): void
    {
        // Solo aplicar headers de seguridad y CSRF si la sesión ya está iniciada
        if (session_status() === PHP_SESSION_ACTIVE && !isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        SecurityHeaders::apply();
    }
}