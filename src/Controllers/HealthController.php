<?php
namespace App\Controllers;

use PDO;

class HealthController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): string
    {
        $remoteIp = $_SERVER['REMOTE_ADDR'] ?? '';
        $role = $_SESSION['role'] ?? '';
        $isLocal = $remoteIp === '127.0.0.1' || $remoteIp === '::1';
        if (!$isLocal && $role !== 'admin') {
            http_response_code(403);
            header('Content-Type: application/json');
            return json_encode(['status' => 'forbidden']);
        }

        $config = @include __DIR__ . '/../../config/config.php';
        $env = 'local';
        if (is_array($config) && isset($config['app']['env'])) {
            $env = (string)$config['app']['env'];
        }

        $dbStatus = 'connected';
        $mysqlVersion = null;
        try {
            $stmt = $this->pdo->query('SELECT 1');
            $stmt->fetchColumn();
            $mysqlVersion = (string)$this->pdo->query('SELECT VERSION()')->fetchColumn();
        } catch (\Throwable $e) {
            $dbStatus = 'error';
        }

        $logsDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logsDir)) {
            @mkdir($logsDir, 0775, true);
        }
        $logsWritable = is_dir($logsDir) && is_writable($logsDir);

        $cacheDir = __DIR__ . '/../../storage/cache';
        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0775, true);
        }
        $cacheWritable = is_dir($cacheDir) && is_writable($cacheDir);

        $basePath = realpath(__DIR__ . '/../../') ?: __DIR__ . '/../../';
        $diskFreeBytes = @disk_free_space($basePath);
        $diskFreeMb = $diskFreeBytes !== false ? (int)floor($diskFreeBytes / (1024 * 1024)) : null;

        $status = 'ok';
        if ($dbStatus !== 'connected' || !$logsWritable || !$cacheWritable) {
            $status = 'error';
        }

        $payload = [
            'status' => $status,
            'environment' => $env,
            'db' => $dbStatus,
            'php_version' => PHP_VERSION,
            'mysql_version' => $mysqlVersion,
            'logs_writable' => $logsWritable,
            'cache_writable' => $cacheWritable,
            'disk_free_mb' => $diskFreeMb,
        ];

        header('Content-Type: application/json');
        if ($status !== 'ok') {
            http_response_code(500);
        } else {
            http_response_code(200);
        }
        return json_encode($payload);
    }
}

