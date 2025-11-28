<?php
require_once __DIR__ . '/../config/db.php';

session_start();
$_SESSION['role'] = 'admin';
$_SESSION['user_id'] = 1;

try {
    $pdo = Database::getInstance()->getConnection();
    
    // Test KPI counts
    echo "=== ADMIN DASHBOARD KPI TEST ===\n\n";
    
    $kpis = [];
    
    // Total students
    $stmt = $pdo->query("SELECT COUNT(*) FROM alumnos WHERE activo = 1");
    $kpis['alumnos'] = $stmt->fetchColumn();
    echo "✓ Students: " . $kpis['alumnos'] . "\n";
    
    // Total subjects
    $stmt = $pdo->query("SELECT COUNT(*) FROM materias");
    $kpis['materias'] = $stmt->fetchColumn();
    echo "✓ Subjects: " . $kpis['materias'] . "\n";
    
    // Total careers
    $stmt = $pdo->query("SELECT COUNT(*) FROM carreras");
    $kpis['carreras'] = $stmt->fetchColumn();
    echo "✓ Careers: " . $kpis['carreras'] . "\n";
    
    // Total professors
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND activo = 1");
    $kpis['profesores'] = $stmt->fetchColumn();
    echo "✓ Professors: " . $kpis['profesores'] . "\n";
    
    // Total groups
    $stmt = $pdo->query("SELECT COUNT(*) FROM grupos");
    $kpis['grupos'] = $stmt->fetchColumn();
    echo "✓ Groups: " . $kpis['grupos'] . "\n";
    
    // Average grade
    $stmt = $pdo->query("SELECT ROUND(AVG(promedio), 2) FROM calificaciones WHERE promedio IS NOT NULL");
    $kpis['promedio'] = $stmt->fetchColumn() ?? 0;
    echo "✓ Average: " . $kpis['promedio'] . "\n";
    
    // Pending evaluations
    $stmt = $pdo->query("SELECT COUNT(*) FROM calificaciones WHERE final IS NULL");
    $kpis['pendientes_evaluacion'] = $stmt->fetchColumn();
    echo "✓ Pending evaluations: " . $kpis['pendientes_evaluacion'] . "\n";
    
    echo "\n=== DASHBOARD VIEW TEST ===\n";
    require_once __DIR__ . '/../src/Controllers/DashboardController.php';
    $dashCtl = new \App\Controllers\DashboardController();
    $html = $dashCtl->index();
    
    if (strpos($html, 'Dashboard Administrador') !== false) {
        echo "✓ Admin dashboard view loads\n";
    } else {
        echo "✗ Admin dashboard view failed\n";
    }
    
    if (strpos($html, 'kpi-alumnos') !== false) {
        echo "✓ KPI elements present\n";
    } else {
        echo "✗ KPI elements missing\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    echo "All KPIs calculated successfully.\n";
    
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
    exit(1);
}
