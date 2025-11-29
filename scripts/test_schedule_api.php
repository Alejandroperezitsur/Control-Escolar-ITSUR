<?php
// Script to test the schedule API
require_once __DIR__ . '/../app/init.php';
require_once __DIR__ . '/../config/db.php';

// Mock session for admin
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = 1;

// Mock GET request
$_GET['grupo_id'] = 51; // Use a valid group ID from seed data

// Instantiate controller
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=control_escolar;charset=utf8mb4', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
$controller = new \App\Controllers\GroupsController($pdo);

// Capture output
ob_start();
$response = $controller->schedules();
ob_end_clean();

echo "Response:\n" . $response . "\n";

$data = json_decode($response, true);
if ($data && isset($data['success']) && $data['success'] === true) {
    echo "SUCCESS: API returned valid JSON with success=true.\n";
    if (isset($data['data']) && is_array($data['data'])) {
        echo "Data count: " . count($data['data']) . "\n";
        if (count($data['data']) > 0) {
            echo "First item keys: " . implode(', ', array_keys($data['data'][0])) . "\n";
        }
    }
} else {
    echo "FAILURE: API returned error or invalid JSON.\n";
}
