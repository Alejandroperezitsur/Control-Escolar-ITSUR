<?php
namespace App\Http\Middleware;

class RateLimitMiddleware
{
    public static function limit(string $key, int $maxAttempts = 100, int $windowSeconds = 300): callable
    {
        return function () use ($key, $maxAttempts, $windowSeconds) {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $dir = __DIR__ . '/../../../storage/ratelimit';
            if (!is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }
            $max = $maxAttempts > 0 ? $maxAttempts : 100;
            $window = $windowSeconds > 0 ? $windowSeconds : 300;
            $file = $dir . '/' . md5($ip . '|' . $key) . '.log';
            $now = time();
            $entries = [];
            if (is_file($file)) {
                $lines = @file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
                foreach ($lines as $line) {
                    $ts = (int)$line;
                    if ($ts > 0 && ($now - $ts) < $window) {
                        $entries[] = $ts;
                    }
                }
            }
            if (count($entries) >= $max) {
                http_response_code(429);
                echo 'Demasiados intentos desde esta IP. Intenta de nuevo más tarde.';
                return false;
            }
            $entries[] = $now;
            @file_put_contents($file, implode("\n", $entries) . "\n", LOCK_EX);
            return true;
        };
    }
}
