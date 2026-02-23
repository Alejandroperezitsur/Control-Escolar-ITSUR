<?php
if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/config/db.php';

$db = Database::getInstance()->getConnection();

$db->exec(
    "CREATE TABLE IF NOT EXISTS migrations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        migration VARCHAR(255) NOT NULL UNIQUE,
        executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
);

$db->setAttribute(PDO::MYSQL_ATTR_MULTI_STATEMENTS, true);

$dir = __DIR__ . '/migrations';
if (!is_dir($dir)) {
    fwrite(STDERR, "Directorio de migraciones no encontrado: {$dir}\n");
    exit(1);
}

$files = glob($dir . DIRECTORY_SEPARATOR . '*.sql');
if ($files === false) {
    $files = [];
}

$files = array_filter($files, function ($path) {
    $base = basename($path);
    return preg_match('/^[0-9]{4}_[0-9]{2}_.+\.sql$/', $base) === 1;
});

usort($files, function ($a, $b) {
    return strcmp(basename($a), basename($b));
});

$stmtCheck = $db->prepare('SELECT COUNT(*) FROM migrations WHERE migration = :migration');
$stmtInsert = $db->prepare('INSERT INTO migrations (migration) VALUES (:migration)');

$applied = 0;

foreach ($files as $file) {
    $name = basename($file);

    $stmtCheck->execute([':migration' => $name]);
    $already = (int)$stmtCheck->fetchColumn() > 0;
    if ($already) {
        echo "[SKIP] {$name} (ya aplicada)\n";
        continue;
    }

    $sql = file_get_contents($file);
    if ($sql === false) {
        fwrite(STDERR, "[FAIL] No se pudo leer {$name}\n");
        exit(1);
    }

    echo "[RUN ] {$name}\n";

    try {
        $db->beginTransaction();
        $db->exec($sql);
        $stmtInsert->execute([':migration' => $name]);
        $db->commit();
        $applied++;
        echo "[OK  ] {$name}\n";
    } catch (Throwable $e) {
        $db->rollBack();
        fwrite(STDERR, "[ERROR] {$name}: " . $e->getMessage() . "\n");
        exit(1);
    }
}

if ($applied === 0) {
    echo "No hay nuevas migraciones para aplicar.\n";
} else {
    echo "Migraciones aplicadas: {$applied}\n";
}

exit(0);

