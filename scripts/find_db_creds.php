<?php
$host = '127.0.0.1';
$db   = 'control_escolar';
$user = 'root';
$passwords = ['', 'root', 'admin', 'password', '123456'];

foreach ($passwords as $pass) {
    try {
        $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        echo "SUCCESS: Password is '$pass'\n";
        exit(0);
    } catch (\PDOException $e) {
        // echo "Failed with '$pass': " . $e->getMessage() . "\n";
    }
}

// Try without dbname in case db doesn't exist
foreach ($passwords as $pass) {
    try {
        $dsn = "mysql:host=$host;charset=utf8mb4";
        $pdo = new PDO($dsn, $user, $pass);
        echo "SUCCESS (No DB): Password is '$pass'\n";
        exit(0);
    } catch (\PDOException $e) {
        // echo "Failed with '$pass': " . $e->getMessage() . "\n";
    }
}

echo "FAILURE: Could not connect with common passwords.\n";
