<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo "Solo CLI\n";
    exit(1);
}

$root = dirname(__DIR__);

function scan_unsanitized_get(string $root): array
{
    $results = [];
    $dir = new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS);
    $it = new RecursiveIteratorIterator($dir);
    foreach ($it as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        if (str_contains($path, DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) {
            continue;
        }
        if (str_contains($path, DIRECTORY_SEPARATOR . 'storage' . DIRECTORY_SEPARATOR)) {
            continue;
        }
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            continue;
        }
        foreach ($lines as $num => $line) {
            if (strpos($line, '$_GET') !== false && strpos($line, 'filter_input') === false && strpos($line, 'filter_var') === false) {
                $results[] = $path . ':' . ($num + 1) . ' usa $_GET sin filtrado explícito';
            }
        }
    }
    return $results;
}

function scan_create_table_in_src(string $root): array
{
    $results = [];
    $src = $root . DIRECTORY_SEPARATOR . 'src';
    if (!is_dir($src)) {
        return $results;
    }
    $dir = new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS);
    $it = new RecursiveIteratorIterator($dir);
    foreach ($it as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }
        $path = $file->getPathname();
        $lines = @file($path, FILE_IGNORE_NEW_LINES);
        if ($lines === false) {
            continue;
        }
        foreach ($lines as $num => $line) {
            if (stripos($line, 'CREATE TABLE') !== false) {
                $results[] = $path . ':' . ($num + 1) . ' contiene CREATE TABLE';
            }
        }
    }
    return $results;
}

function scan_post_routes_without_csrf(string $root): array
{
    $results = [];
    $app = $root . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'app.php';
    if (!is_file($app)) {
        return $results;
    }
    $lines = @file($app, FILE_IGNORE_NEW_LINES);
    if ($lines === false) {
        return $results;
    }
    $buffer = '';
    $startLine = 0;
    foreach ($lines as $num => $line) {
        if (strpos($line, '->post(') !== false) {
            $buffer = $line;
            $startLine = $num + 1;
            if (strpos($line, ');') !== false) {
                if (strpos($buffer, 'AuthMiddleware::') === false) {
                    $results[] = $app . ':' . $startLine . ' ruta POST sin AuthMiddleware (y por tanto sin CSRF global)';
                }
                $buffer = '';
            }
            continue;
        }
        if ($buffer !== '') {
            $buffer .= ' ' . $line;
            if (strpos($line, 'AuthMiddleware::') !== false) {
                // marcado como protegido
            }
            if (strpos($line, ');') !== false) {
                if (strpos($buffer, 'AuthMiddleware::') === false) {
                    $results[] = $app . ':' . $startLine . ' ruta POST sin AuthMiddleware (y por tanto sin CSRF global)';
                }
                $buffer = '';
            }
        }
    }
    return $results;
}

echo "== Scan: $_GET sin sanitizar ==\n";
$unsanitized = scan_unsanitized_get($root);
if (!$unsanitized) {
    echo "OK: no se detectaron usos directos de \$_GET sin filtrado básico.\n";
} else {
    foreach ($unsanitized as $line) {
        echo "WARN: $line\n";
    }
}

echo "\n== Scan: CREATE TABLE en src/ ==\n";
$ddl = scan_create_table_in_src($root);
if (!$ddl) {
    echo "OK: no se detectaron CREATE TABLE en src/.\n";
} else {
    foreach ($ddl as $line) {
        echo "WARN: $line\n";
    }
}

echo "\n== Scan: rutas POST sin CSRF global ==\n";
$noCsrf = scan_post_routes_without_csrf($root);
if (!$noCsrf) {
    echo "OK: todas las rutas POST del router principal usan AuthMiddleware.\n";
} else {
    foreach ($noCsrf as $line) {
        echo "WARN: $line\n";
    }
}

echo "\nFin de security_scan.\n";
