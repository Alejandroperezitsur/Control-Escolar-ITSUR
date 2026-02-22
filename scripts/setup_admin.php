<?php

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$config = require __DIR__ . '/../config/config.php';
$env = (string)($config['app']['env'] ?? getenv('APP_ENV') ?: 'local');
if ($env === 'production') {
    fwrite(STDERR, "Setup de admin deshabilitado en producción\n");
    exit(1);
}

try {
    $pdo = Database::getInstance()->getConnection();
} catch (Throwable $e) {
    fwrite(STDERR, 'Error de conexión a la base de datos: ' . $e->getMessage() . "\n");
    exit(1);
}

$newEmail = $argv[1] ?? 'admin@itsur.edu.mx';
$newPasswordPlain = $argv[2] ?? 'admin123';

try {
    $pdo->beginTransaction();
    $stmtDel = $pdo->prepare("DELETE FROM usuarios WHERE rol = 'admin' AND email = :email");
    $stmtDel->execute([':email' => 'admin@local']);
    $deleted = (int)$stmtDel->rowCount();
    $stmtSel = $pdo->query("SELECT id, email FROM usuarios WHERE rol = 'admin' LIMIT 1");
    $admin = $stmtSel->fetch(PDO::FETCH_ASSOC);
    $hash = password_hash($newPasswordPlain, PASSWORD_DEFAULT);
    if ($admin && isset($admin['id'])) {
        $stmtUpd = $pdo->prepare("UPDATE usuarios SET email = :email, password = :pw, activo = 1 WHERE id = :id");
        $stmtUpd->execute([':email' => $newEmail, ':pw' => $hash, ':id' => (int)$admin['id']]);
        echo "Admin actualizado: ID=" . (int)$admin['id'] . ", email=" . $admin['email'] . " -> " . $newEmail . "\n";
    } else {
        $stmtIns = $pdo->prepare("INSERT INTO usuarios (email, password, rol, activo) VALUES (:email, :pw, 'admin', 1)");
        $stmtIns->execute([':email' => $newEmail, ':pw' => $hash]);
        $newId = (int)$pdo->lastInsertId();
        echo "Admin creado: ID=" . $newId . ", email=" . $newEmail . "\n";
    }
    if ($deleted > 0) {
        echo "Registros eliminados de admin@local: {$deleted}\n";
    }
    echo "Credenciales: {$newEmail} / {$newPasswordPlain}\n";
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, 'Error: ' . $e->getMessage() . "\n");
    exit(1);
}

