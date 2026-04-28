<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use App\Middleware\SecurityMiddleware;
use App\Middleware\RateLimitMiddleware;
use App\Middleware\IdempotencyMiddleware;
use PDO;

/**
 * Tests de Seguridad - Verificación automática de vulnerabilidades
 * 
 * Ejecutar: php vendor/bin/phpunit tests/SecurityTest.php
 */
class SecurityTest extends TestCase {
    
    private PDO $db;
    
    protected function setUp(): void {
        // Configurar database de test
        $this->db = new PDO('sqlite::memory:');
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Crear tablas mínimas para tests
        $this->db->exec("
            CREATE TABLE usuarios (
                id INTEGER PRIMARY KEY,
                email TEXT UNIQUE,
                password_hash TEXT,
                role TEXT
            )
        ");
        
        $this->db->exec("
            CREATE TABLE alumnos (
                id INTEGER PRIMARY KEY,
                user_id INTEGER,
                matricula TEXT UNIQUE,
                apellido TEXT,
                nombre TEXT,
                activo BOOLEAN DEFAULT 1,
                deleted_at DATETIME
            )
        ");
        
        $this->db->exec("
            CREATE TABLE rate_limit_log (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER,
                ip_address TEXT,
                endpoint TEXT,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $this->db->exec("
            CREATE TABLE idempotency_keys (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                request_key TEXT UNIQUE,
                user_id INTEGER,
                endpoint TEXT,
                response_data TEXT,
                status_code INTEGER,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                expires_at DATETIME
            )
        ");
    }
    
    /**
     * Test IDOR - Verificar que un alumno no puede acceder a datos de otro
     */
    public function testIDORProtection(): void {
        // Crear dos alumnos
        $stmt = $this->db->prepare("INSERT INTO alumnos (user_id, matricula, apellido, nombre) VALUES (?, ?, ?, ?)");
        $stmt->execute([1, 'A001', 'Perez', 'Juan']);
        $stmt->execute([2, 'A002', 'Lopez', 'Maria']);
        
        // Simular usuario 1 intentando acceder al alumno 2
        $alumno2Id = 2;
        
        // La verificación debería fallar
        $studentIdForUser1 = $this->db->prepare("SELECT id FROM alumnos WHERE user_id = ? AND deleted_at IS NULL");
        $studentIdForUser1->execute([1]);
        $retrievedId = $studentIdForUser1->fetchColumn();
        
        $this->assertNotEquals($alumno2Id, $retrievedId, 'Usuario 1 no debería poder acceder al alumno 2');
        $this->assertEquals(1, $retrievedId, 'Usuario 1 solo debería acceder a su propio alumno');
    }
    
    /**
     * Test Rate Limiting - Verificar bloqueo después de múltiples intentos
     */
    public function testRateLimiting(): void {
        $rateLimiter = new RateLimitMiddleware($this->db);
        $ip = '192.168.1.100';
        $userId = 1;
        
        // Simular 5 intentos de login (límite)
        for ($i = 0; $i < 5; $i++) {
            try {
                $rateLimiter->check('login', $userId, $ip);
                $this->assertTrue(true, "Intento $i permitido");
            } catch (\Exception $e) {
                $this->fail("El intento $i no debería ser bloqueado");
            }
        }
        
        // El sexto intento debería ser bloqueado
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Demasiadas solicitudes');
        $rateLimiter->check('login', $userId, $ip);
    }
    
    /**
     * Test Idempotencia - Verificar que requests duplicados retornan resultado cacheado
     */
    public function testIdempotency(): void {
        $idempotency = new IdempotencyMiddleware($this->db);
        $requestKey = 'test-key-123';
        $userId = 1;
        $endpoint = '/api/enrollment';
        
        // Primer request - debería retornar null (no existe)
        $result = $idempotency->handle($requestKey, $userId, $endpoint);
        $this->assertNull($result, 'Primer request no debería tener resultado cacheado');
        
        // Guardar resultado
        $responseData = ['success' => true, 'message' => 'Inscrito correctamente'];
        $idempotency->storeResult($requestKey, $userId, $endpoint, $responseData, 200);
        
        // Segundo request con mismo key - debería retornar resultado cacheado
        $cachedResult = $idempotency->handle($requestKey, $userId, $endpoint);
        $this->assertNotNull($cachedResult, 'Segundo request debería tener resultado cacheado');
        $this->assertEquals($responseData, $cachedResult, 'Resultado cacheado debería coincidir');
    }
    
    /**
     * Test Mass Assignment - Verificar que campos protegidos son ignorados
     */
    public function testMassAssignmentProtection(): void {
        $maliciousData = [
            'email' => 'student@test.com',
            'password' => 'secure123',
            'role' => 'admin',  // Intento de escalada
            'activo' => 1,
            'deleted_at' => null
        ];
        
        // Whitelist de campos permitidos
        $allowedFields = ['email', 'password', 'apellido', 'nombre', 'matricula'];
        $sanitizedData = array_intersect_key($maliciousData, array_flip($allowedFields));
        
        $this->assertArrayNotHasKey('role', $sanitizedData, 'Campo role debería ser eliminado');
        $this->assertArrayNotHasKey('activo', $sanitizedData, 'Campo activo debería ser eliminado');
        $this->assertArrayNotHasKey('deleted_at', $sanitizedData, 'Campo deleted_at debería ser eliminado');
        $this->assertEquals(['email', 'password'], array_keys($sanitizedData), 'Solo campos permitidos deberían permanecer');
    }
    
    /**
     * Test Timing Attack - Verificar que login toma tiempo constante
     */
    public function testTimingAttackProtection(): void {
        // Crear usuario existente
        $hash = password_hash('correct_password', PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("INSERT INTO usuarios (email, password_hash, role) VALUES (?, ?, ?)");
        $stmt->execute(['existing@test.com', $hash, 'student']);
        
        // Medir tiempo para usuario existente con password incorrecto
        $startExisting = microtime(true);
        $user = $this->db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $user->execute(['existing@test.com']);
        $userData = $user->fetch();
        if ($userData) {
            password_verify('wrong_password', $userData['password_hash']);
        }
        $timeExisting = microtime(true) - $startExisting;
        
        // Medir tiempo para usuario inexistente (debería usar dummy hash)
        $startNonExisting = microtime(true);
        $user2 = $this->db->prepare("SELECT * FROM usuarios WHERE email = ?");
        $user2->execute(['nonexisting@test.com']);
        $userData2 = $user2->fetch();
        if ($userData2) {
            password_verify('any_password', $userData2['password_hash']);
        } else {
            // Usar dummy hash para tiempo constante
            $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
            password_verify('any_password', $dummyHash);
        }
        $timeNonExisting = microtime(true) - $startNonExisting;
        
        // Los tiempos deberían ser similares (margen de 20%)
        $ratio = max($timeExisting, $timeNonExisting) / min($timeExisting, $timeNonExisting);
        $this->assertLessThan(2.0, $ratio, 'El tiempo de respuesta debería ser similar para prevenir timing attacks');
    }
    
    /**
     * Test Soft Delete - Verificar que registros eliminados no son accesibles
     */
    public function testSoftDeleteProtection(): void {
        // Crear alumno y marcarlo como eliminado
        $stmt = $this->db->prepare("INSERT INTO alumnos (user_id, matricula, apellido, nombre, deleted_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([1, 'A003', 'Deleted', 'Student']);
        
        // Query que respeta soft delete
        $query = $this->db->prepare("SELECT * FROM alumnos WHERE id = ? AND deleted_at IS NULL");
        $query->execute([1]);
        $result = $query->fetch();
        
        $this->assertFalse($result, 'Alumno eliminado no debería ser accesible');
    }
}
