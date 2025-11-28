<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../src/Repositories/UserRepository.php';
require_once __DIR__ . '/../src/Services/UserService.php';

use App\Repositories\UserRepository;
use App\Services\UserService;

try {
    $pdo = Database::getInstance()->getConnection();
    $repo = new UserRepository($pdo);
    $service = new UserService($repo);

    echo "Testing Admin Auth...\n";
    $adminEmail = 'admin@itsur.edu.mx';
    $passwords = ['admin', 'password', '123456', 'admin123'];
    
    foreach ($passwords as $pass) {
        $user = $service->authenticate($adminEmail, $pass);
        if ($user) {
            echo "SUCCESS: Admin password is '$pass'\n";
            break;
        }
    }
    if (empty($user)) echo "FAILED: Could not find admin password.\n";

    echo "\nTesting Student Auth...\n";
    $matricula = 'S12345678';
    foreach ($passwords as $pass) {
        $user = $service->authenticate($matricula, $pass);
        if ($user) {
            echo "SUCCESS: Student password is '$pass'\n";
            break;
        }
    }
    if (empty($user)) echo "FAILED: Could not find student password.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
