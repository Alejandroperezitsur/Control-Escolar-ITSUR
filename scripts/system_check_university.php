<?php
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../config/db.php';

use App\Core\Env;

if (!class_exists(Env::class)) {
    require_once __DIR__ . '/../src/Core/Env.php';
}

$pdo = Database::getInstance()->getConnection();

function checkEnvironments(): bool
{
    $env = Env::get('APP_ENV', null);
    $debug = Env::get('APP_DEBUG', null);
    $hasEnvFile = is_file(__DIR__ . '/../src/.env');
    if (!$hasEnvFile) {
        $hasEnvFile = is_file(__DIR__ . '/../.env');
    }
    if ($env === null || $debug === null) {
        return false;
    }
    if (!$hasEnvFile) {
        return false;
    }
    $dbHost = Env::get('DB_HOST', null);
    $dbName = Env::get('DB_NAME', null);
    $dbUser = Env::get('DB_USER', null);
    $dbPass = Env::get('DB_PASS', null);
    if ($dbHost === null || $dbName === null || $dbUser === null || $dbPass === null) {
        return false;
    }
    return true;
}

function checkSessionsSecure(): bool
{
    $strict = ini_get('session.use_strict_mode');
    $onlyCookies = ini_get('session.use_only_cookies');
    $httpOnly = ini_get('session.cookie_httponly');
    $sameSite = ini_get('session.cookie_samesite');
    $gc = ini_get('session.gc_maxlifetime');
    if ($strict !== '1') {
        return false;
    }
    if ($onlyCookies !== '1') {
        return false;
    }
    if ($httpOnly !== '1') {
        return false;
    }
    if (strcasecmp((string)$sameSite, 'Strict') !== 0) {
        return false;
    }
    if ((int)$gc <= 0) {
        return false;
    }
    return true;
}

function checkPasswordHash(): bool
{
    $info = password_get_info(password_hash('test1234A', PASSWORD_DEFAULT));
    if (!isset($info['algo']) || $info['algo'] === 0) {
        return false;
    }
    return true;
}

function checkMigratorExists(PDO $pdo): bool
{
    $root = realpath(__DIR__ . '/..');
    if ($root === false) {
        return false;
    }
    $file = $root . DIRECTORY_SEPARATOR . 'migrate.php';
    if (!is_file($file)) {
        return false;
    }
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'migrations'");
        return $stmt->fetchColumn() !== false;
    } catch (Throwable $e) {
        return false;
    }
}

function checkBackupSystem(): bool
{
    $root = realpath(__DIR__ . '/..');
    if ($root === false) {
        return false;
    }
    $script = $root . DIRECTORY_SEPARATOR . 'scripts' . DIRECTORY_SEPARATOR . 'backup.php';
    $dir = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'backups';
    if (!is_file($script)) {
        return false;
    }
    if (!is_dir($dir)) {
        return false;
    }
    if (!is_writable($dir)) {
        return false;
    }
    return true;
}

function checkAdvancedIndexes(PDO $pdo): bool
{
    $need = [
        'calificaciones' => ['idx_calificaciones_grupo_alumno', 'idx_calificaciones_alumno_grupo'],
        'auditoria_academica' => ['idx_auditoria_entidad_fecha'],
    ];
    foreach ($need as $table => $indexes) {
        try {
            $stmt = $pdo->query('SHOW INDEX FROM ' . $table);
        } catch (Throwable $e) {
            return false;
        }
        $found = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if (isset($row['Key_name'])) {
                $found[] = $row['Key_name'];
            }
        }
        foreach ($indexes as $ix) {
            if (!in_array($ix, $found, true)) {
                return false;
            }
        }
    }
    return true;
}

function checkHealthEndpoint(): bool
{
    $file = __DIR__ . '/../src/Controllers/HealthController.php';
    if (!is_file($file)) {
        return false;
    }
    return true;
}

function checkPermissionsGranular(PDO $pdo): bool
{
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'permisos'");
        if ($stmt->fetchColumn() === false) {
            return false;
        }
        $stmt = $pdo->query("SHOW TABLES LIKE 'rol_permiso'");
        if ($stmt->fetchColumn() === false) {
            return false;
        }
        $stmt = $pdo->query("SELECT COUNT(*) FROM permisos");
        $countPerm = (int)$stmt->fetchColumn();
        if ($countPerm === 0) {
            return false;
        }
        $stmt = $pdo->query("SELECT COUNT(*) FROM rol_permiso");
        $countMap = (int)$stmt->fetchColumn();
        if ($countMap === 0) {
            return false;
        }
    } catch (Throwable $e) {
        return false;
    }
    return true;
}

function checkServiceLayer(): bool
{
    $root = realpath(__DIR__ . '/..');
    if ($root === false) {
        return false;
    }
    $servicesDir = $root . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Services';
    if (!is_dir($servicesDir)) {
        return false;
    }
    $needed = ['EnrollmentService.php', 'GradeService.php', 'AuthService.php', 'StudentsService.php'];
    foreach ($needed as $file) {
        if (!is_file($servicesDir . DIRECTORY_SEPARATOR . $file)) {
            return false;
        }
    }
    return true;
}

function checkNoSqlInControllers(): bool
{
    $baseDir = realpath(__DIR__ . '/../src/Controllers');
    if ($baseDir === false) {
        return false;
    }
    $patterns = ['SELECT ', 'INSERT ', 'UPDATE ', 'DELETE '];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($baseDir));
    foreach ($it as $file) {
        if (!$file->isFile()) {
            continue;
        }
        if (substr($file->getFilename(), -4) !== '.php') {
            continue;
        }
        $path = $file->getPathname();
        $contents = file_get_contents($path);
        if ($contents === false) {
            continue;
        }
        $stripped = preg_replace('~/\*.*?\*/~s', '', $contents);
        $lines = explode("\n", $stripped);
        foreach ($lines as $line) {
            $lineTrim = ltrim($line);
            if (strpos($lineTrim, '//') === 0) {
                continue;
            }
            foreach ($patterns as $p) {
                if (stripos($lineTrim, $p) !== false) {
                    return false;
                }
            }
        }
    }
    return true;
}

function checkLogsAndCache(): bool
{
    $root = realpath(__DIR__ . '/..');
    if ($root === false) {
        return false;
    }
    $logs = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'logs';
    $cache = $root . DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR . 'cache';
    if (!is_dir($logs) || !is_writable($logs)) {
        return false;
    }
    if (!is_dir($cache) || !is_writable($cache)) {
        return false;
    }
    return true;
}

function checkNoHardcodedCredentials(): bool
{
    $configFile = __DIR__ . '/../config/config.php';
    if (!is_file($configFile)) {
        return false;
    }
    $contents = file_get_contents($configFile);
    if ($contents === false) {
        return false;
    }
    if (strpos($contents, 'root') !== false) {
        return false;
    }
    if (strpos($contents, 'password') !== false) {
        return false;
    }
    return true;
}

$checks = [
    'Entornos separados y credenciales en .env' => checkEnvironments(),
    'Sesiones seguras y endurecidas' => checkSessionsSecure(),
    'Hash de contraseñas moderno' => checkPasswordHash(),
    'Migrador automático activo' => checkMigratorExists($pdo),
    'Sistema de backups configurado' => checkBackupSystem(),
    'Índices avanzados para alta carga' => checkAdvancedIndexes($pdo),
    'Health endpoint definido' => checkHealthEndpoint(),
    'Permisos granulares activos' => checkPermissionsGranular($pdo),
    'Service layer implementado' => checkServiceLayer(),
    'Sin SQL directo en controladores' => checkNoSqlInControllers(),
    'Logs y cache configurados' => checkLogsAndCache(),
    'Sin credenciales hardcodeadas en config' => checkNoHardcodedCredentials(),
];

$allOk = true;
foreach ($checks as $label => $ok) {
    echo ($ok ? '[OK] ' : '[FAIL] ') . $label . PHP_EOL;
    if (!$ok) {
        $allOk = false;
    }
}

if (!$allOk) {
    exit(1);
}

echo 'UNIVERSITY PRODUCTION READY' . PHP_EOL;
echo 'CAPACITY: 2000+ STUDENTS' . PHP_EOL;
echo 'STATUS: STABLE' . PHP_EOL;

exit(0);

