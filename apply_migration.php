<?php
$host = 'localhost';
$db   = 'control_escolar';
$user = 'root';
$pass = 'root';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    
    // Create DB if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `$db`");
    $pdo->exec("USE `$db`");
    
    echo "Connected to local database '$db'.\n";
    
    $sqlFile = __DIR__ . '/migrations/post_mvp_schema.sql';
    if (!file_exists($sqlFile)) {
        die("Migration file not found: $sqlFile\n");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Split by semicolon, but be careful with triggers/procedures if any (none in this simple schema)
    // For simple schema, basic split is usually okay, or just exec the whole thing if driver supports multiple queries
    // PDO might not support multiple queries in one go depending on config, but let's try.
    // Actually, let's split by command to be safer and see progress.
    
    $commands = explode(';', $sql);
    
    foreach ($commands as $cmd) {
        $cmd = trim($cmd);
        if (empty($cmd)) continue;
        try {
            $pdo->exec($cmd);
            echo "Executed command: " . substr($cmd, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // Ignore "Duplicate column" or "Table exists" if we want idempotency, 
            // but the script uses IF NOT EXISTS so it should be fine.
            // However, ALTER TABLE might fail if column exists.
            echo "Warning/Error executing: " . substr($cmd, 0, 50) . "... -> " . $e->getMessage() . "\n";
        }
    }
    
    echo "Migration completed.\n";

} catch (\PDOException $e) {
    echo "Database Error: " . $e->getMessage() . "\n";
    exit(1);
}
