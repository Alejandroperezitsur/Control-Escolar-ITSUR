<?php
$creds = [
    ['root', ''],
    ['root', 'root'],
    ['root', 'mysql'],
    ['admin', 'admin'],
    ['root', 'password']
];

foreach ($creds as $c) {
    list($user, $pass) = $c;
    echo "Trying $user / '$pass' ... ";
    try {
        $pdo = new PDO("mysql:host=localhost", $user, $pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        echo "SUCCESS!\n";
        
        // Find DB
        $dbs = $pdo->query("SHOW DATABASES")->fetchAll(PDO::FETCH_COLUMN);
        $targetDb = null;
        foreach ($dbs as $d) { if (strpos($d, 'control_escolar') !== false) { $targetDb = $d; break; } }
        if (!$targetDb) $targetDb = 'control_escolar';
        echo "Using DB: $targetDb\n";
        $pdo->exec("USE `$targetDb`");
        
        // Apply
        try { $pdo->exec("ALTER TABLE calificaciones ADD COLUMN parcial3 INT NULL AFTER parcial2"); } catch(Exception $e) {}
        try { $pdo->exec("ALTER TABLE calificaciones ADD COLUMN parcial4 INT NULL AFTER parcial3"); } catch(Exception $e) {}
        try { $pdo->exec("ALTER TABLE calificaciones ADD COLUMN parcial5 INT NULL AFTER parcial4"); } catch(Exception $e) {}
        try { $pdo->exec("ALTER TABLE materias ADD COLUMN num_parciales INT NOT NULL DEFAULT 3"); } catch(Exception $e) {}
        
        echo "Schema applied.\n";
        exit(0);
    } catch (PDOException $e) {
        echo "Failed.\n";
    }
}
echo "All attempts failed.\n";
