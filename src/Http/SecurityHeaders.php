<?php
namespace App\Http;

class SecurityHeaders
{
    public static function apply(): void
    {
        header('X-Frame-Options: DENY');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
        // CSP actualizada para Bootstrap, Chart.js y FontAwesome
        header("Content-Security-Policy: "
            . "default-src 'self'; "
            . "img-src 'self' data: https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; "
            . "style-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com 'unsafe-inline'; "
            . "script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com 'unsafe-inline'; "
            . "font-src 'self' https://cdnjs.cloudflare.com https://fonts.gstatic.com; "
            . "connect-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com;"
        );
    }
}
