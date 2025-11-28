<?php
/**
 * Script de diagnóstico para probar autenticación
 * SUBE ESTE ARCHIVO AL SERVIDOR REMOTO EN: /public_html/scripts/test_login.php
 */

// Configurar para mostrar errores
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html><html><head><meta charset='UTF-8'><title>Diagnóstico Login</title>";
echo "<style>body{font-family:Arial;padding:20px;} h2{color:#333;} h3{color:#666;margin-top:20px;} pre{background:#f4f4f4;padding:10px;border-radius:5px;}</style></head><body>";

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../config/db.php';
    
    $db = Database::getInstance();
    $pdo = $db->getConnection();
    
    echo "<h2>✅ Conexión a Base de Datos: EXITOSA</h2>";
    
    // Test 1: Verificar estructura de tabla usuarios
    echo "<h3>Test 1: Estructura tabla usuarios</h3>";
    $stmt = $pdo->query("DESCRIBE usuarios");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>" . print_r($columns, true) . "</pre>";
    
    // Test 2: Contar usuarios
    echo "<h3>Test 2: Total de usuarios en BD</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total usuarios: <strong>{$count['total']}</strong><br>";
    
    // Test 3: Verificar si el admin existe
    echo "<h3>Test 3: Verificar usuario admin@itsur.edu.mx</h3>";
    $stmt = $pdo->prepare("SELECT id, email, nombre, rol, activo, SUBSTRING(password, 1, 20) as pass_inicio, LENGTH(password) as pass_length FROM usuarios WHERE email = :email LIMIT 1");
    $stmt->execute([':email' => 'admin@itsur.edu.mx']);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Admin encontrado:<br>";
        echo "<pre>" . print_r($admin, true) . "</pre>";
        
        // Obtener password completo
        $stmt = $pdo->prepare("SELECT password FROM usuarios WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => 'admin@itsur.edu.mx']);
        $pass_data = $stmt->fetch(PDO::FETCH_ASSOC);
        $stored_hash = $pass_data['password'];
        
        echo "<h3>Test 4: Verificación de contraseña 'admin123'</h3>";
        echo "Hash almacenado: <code>" . htmlspecialchars($stored_hash) . "</code><br>";
        echo "Longitud del hash: " . strlen($stored_hash) . " caracteres<br>";
        
        $passwords_to_test = ['admin123', 'admin', '123456'];
        echo "<h4>Probando contraseñas:</h4>";
        foreach ($passwords_to_test as $test_pass) {
            $verify_result = password_verify($test_pass, $stored_hash);
            $color = $verify_result ? 'green' : 'red';
            $icon = $verify_result ? '✅' : '❌';
            echo "<span style='color:$color'>$icon Password '<strong>$test_pass</strong>': " . 
                 ($verify_result ? 'VÁLIDA' : 'INVÁLIDA') . "</span><br>";
        }
        
    } else {
        echo "<p style='color:red'>❌ NO se encontró admin@itsur.edu.mx</p>";
        
        // Buscar cualquier admin
        echo "<h4>Buscando cualquier usuario admin...</h4>";
        $stmt = $pdo->prepare("SELECT id, email, nombre, rol FROM usuarios WHERE rol = 'admin' LIMIT 5");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<pre>" . print_r($admins, true) . "</pre>";
    }
    
    // Test 5: Verificar alumnos
    echo "<h3>Test 5: Verificar alumnos</h3>";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM alumnos");
    $count_al = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total alumnos: <strong>{$count_al['total']}</strong><br>";
    
    if ($count_al['total'] > 0) {
        // Buscar primer alumno
        $stmt = $pdo->prepare("SELECT id, matricula, nombre, activo, SUBSTRING(password, 1, 20) as pass_inicio FROM alumnos LIMIT 1");
        $stmt->execute();
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Primer alumno:<br><pre>" . print_r($alumno, true) . "</pre>";
        
        // Probar con S22120001 si existe
        $stmt = $pdo->prepare("SELECT matricula, nombre, password FROM alumnos WHERE matricula = :m LIMIT 1");
        $stmt->execute([':m' => 'S22120001']);
        $test_alumno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_alumno) {
            echo "<h4>Alumno S22120001:</h4>";
            $verify = password_verify('alumno123', $test_alumno['password']);
            $color = $verify ? 'green' : 'red';
            echo "<span style='color:$color'>" . ($verify ? '✅' : '❌') . " Password 'alumno123': " . 
                 ($verify ? 'VÁLIDA' : 'INVÁLIDA') . "</span><br>";
        }
    }
    
    // Test 6: Generar nuevos hashes como referencia
    echo "<h3>Test 6: Generar nuevos hashes (para referencia)</h3>";
    $new_admin_hash = password_hash('admin123', PASSWORD_BCRYPT);
    $new_alumno_hash = password_hash('alumno123', PASSWORD_BCRYPT);
    $new_profesor_hash = password_hash('profesor123', PASSWORD_BCRYPT);
    
    echo "Nuevo hash 'admin123': <code>" . htmlspecialchars($new_admin_hash) . "</code><br>";
    echo "Nuevo hash 'alumno123': <code>" . htmlspecialchars($new_alumno_hash) . "</code><br>";
    echo "Nuevo hash 'profesor123': <code>" . htmlspecialchars($new_profesor_hash) . "</code><br>";
    
    // Test 7: Verificar que los nuevos hashes funcionan
    echo "<h3>Test 7: Verificar que password_verify funciona en este servidor</h3>";
    $test_verify = password_verify('admin123', $new_admin_hash);
    echo "<span style='color:" . ($test_verify ? 'green' : 'red') . "'>" . 
         ($test_verify ? '✅ password_verify FUNCIONA correctamente' : '❌ password_verify NO funciona') . 
         "</span><br>";
    
} catch (Exception $e) {
    echo "<h2 style='color:red'>❌ Error de Conexión</h2>";
    echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
}

echo "</body></html>";
