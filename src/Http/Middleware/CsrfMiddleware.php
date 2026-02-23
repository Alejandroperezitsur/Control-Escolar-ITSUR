<?php
namespace App\Http\Middleware;

use App\Http\Request;

class CsrfMiddleware
{
    public static function checkAuthenticatedPost(): bool
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (strtoupper($method) !== 'POST') {
            return true;
        }
        $token = Request::postString('csrf_token', '');
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            http_response_code(419);
            echo 'CSRF inválido';
            return false;
        }
        return true;
    }
}

