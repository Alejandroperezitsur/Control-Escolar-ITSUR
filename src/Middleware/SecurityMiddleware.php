<?php

namespace App\Middleware;

use PDO;
use App\Utils\Logger;

/**
 * SecurityMiddleware - SEGURIDAD DE PRODUCCIÓN REAL
 * 
 * Maneja:
 * - Verificación de propiedad (ownership) CORRECTA
 * - Regeneración de sesión cada 15 minutos
 * - Invalidación total de sesión en logout
 * - CSRF token management
 */
class SecurityMiddleware
{
    /**
     * Verifica ownership usando relaciones REALES de la base de datos
     * 
     * IMPORTANTE: Usa las columnas correctas del schema nuevo:
     * - grupos.profesor_id (NO docente_id)
     * - alumnos.id directo (NO user_id para validación)
     * 
     * @param PDO $pdo Conexión a base de datos
     * @param int $resourceId ID del recurso a validar
     * @param string $resourceType Tipo de recurso: 'alumno', 'grupo', 'calificacion'
     * @param int $userId ID del usuario autenticado
     * @param string $userRole Rol del usuario: 'admin', 'profesor', 'alumno'
     * @throws \Exception Si no tiene permiso de acceso
     */
    public static function verifyOwnership(PDO $pdo, int $resourceId, string $resourceType, int $userId, string $userRole): void
    {
        // Admin tiene acceso total
        if ($userRole === 'admin') {
            return;
        }

        switch ($resourceType) {
            case 'alumno':
                // Validar que el alumno existe y pertenece al usuario
                // NOTA: Usamos alumnos.id directo, NO user_id
                $stmt = $pdo->prepare("
                    SELECT a.id 
                    FROM alumnos a
                    WHERE a.id = :id 
                      AND a.user_id = :uid 
                      AND a.deleted_at IS NULL
                      AND a.activo = 1
                ");
                $stmt->execute([
                    ':id' => $resourceId,
                    ':uid' => $userId
                ]);
                
                if (!$stmt->fetchColumn()) {
                    Logger::warning('ownership_denied', [
                        'resource_type' => 'alumno',
                        'resource_id' => $resourceId,
                        'user_id' => $userId,
                        'reason' => 'alumno_no_pertenece_a_usuario'
                    ]);
                    throw new \Exception("Acceso denegado: No puedes ver datos de otro alumno.", 403);
                }
                break;
            
            case 'grupo':
                if ($userRole !== 'profesor') {
                    Logger::warning('ownership_denied', [
                        'resource_type' => 'grupo',
                        'resource_id' => $resourceId,
                        'user_id' => $userId,
                        'user_role' => $userRole,
                        'reason' => 'rol_no_autorizado'
                    ]);
                    throw new \Exception("Acceso denegado: Rol no autorizado.", 403);
                }
                
                // CRÍTICO: Usar profesor_id (NO docente_id que no existe)
                $stmt = $pdo->prepare("
                    SELECT g.id 
                    FROM grupos g
                    WHERE g.id = :gid 
                      AND g.profesor_id = :tid 
                      AND g.deleted_at IS NULL
                      AND g.activo = 1
                ");
                $stmt->execute([
                    ':gid' => $resourceId,
                    ':tid' => $userId
                ]);
                
                if (!$stmt->fetchColumn()) {
                    Logger::warning('ownership_denied', [
                        'resource_type' => 'grupo',
                        'resource_id' => $resourceId,
                        'user_id' => $userId,
                        'reason' => 'grupo_no_pertenece_a_profesor'
                    ]);
                    throw new \Exception("Acceso denegado: Este grupo no te pertenece.", 403);
                }
                break;
                
            case 'calificacion':
                if ($userRole === 'profesor') {
                    // Profesor solo puede modificar calificaciones de sus grupos
                    // CRÍTICO: Usar profesor_id (NO docente_id)
                    $stmt = $pdo->prepare("
                        SELECT c.id 
                        FROM calificaciones c 
                        JOIN grupos g ON c.grupo_id = g.id 
                        WHERE c.id = :cid 
                          AND g.profesor_id = :tid 
                          AND c.deleted_at IS NULL
                    ");
                    $stmt->execute([
                        ':cid' => $resourceId,
                        ':tid' => $userId
                    ]);
                    
                    if (!$stmt->fetchColumn()) {
                        Logger::warning('ownership_denied', [
                            'resource_type' => 'calificacion',
                            'resource_id' => $resourceId,
                            'user_id' => $userId,
                            'reason' => 'profesor_no_pertenece_grupo'
                        ]);
                        throw new \Exception("Acceso denegado: No puedes modificar esta calificacion.", 403);
                    }
                } elseif ($userRole === 'alumno') {
                    // Alumno solo puede ver sus propias calificaciones
                    $stmt = $pdo->prepare("
                        SELECT c.id 
                        FROM calificaciones c 
                        JOIN alumnos a ON c.alumno_id = a.id 
                        WHERE c.id = :cid 
                          AND a.user_id = :uid 
                          AND c.deleted_at IS NULL
                    ");
                    $stmt->execute([
                        ':cid' => $resourceId,
                        ':uid' => $userId
                    ]);
                    
                    if (!$stmt->fetchColumn()) {
                        Logger::warning('ownership_denied', [
                            'resource_type' => 'calificacion',
                            'resource_id' => $resourceId,
                            'user_id' => $userId,
                            'reason' => 'calificacion_no_pertenece_a_alumno'
                        ]);
                        throw new \Exception("Acceso denegado: Esta calificacion no te pertenece.", 403);
                    }
                } else {
                    Logger::warning('ownership_denied', [
                        'resource_type' => 'calificacion',
                        'resource_id' => $resourceId,
                        'user_id' => $userId,
                        'user_role' => $userRole,
                        'reason' => 'rol_no_autorizado_calificacion'
                    ]);
                    throw new \Exception("Acceso denegado: Rol no autorizado.", 403);
                }
                break;
                
            default:
                Logger::error('ownership_unknown_resource', [
                    'resource_type' => $resourceType,
                    'resource_id' => $resourceId
                ]);
                throw new \Exception("Tipo de recurso desconocido.", 500);
        }
    }

    /**
     * Regenera el ID de sesión por seguridad
     * Se debe llamar cada 15 minutos o después de eventos críticos
     * 
     * @param string $reason Motivo de la regeneración
     */
    public static function regenerateSessionContext(string $reason = 'security_event'): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            // Guardar datos importantes antes de regenerar
            $oldData = $_SESSION ?? [];
            
            // Regenerar ID de sesión (previene session fixation)
            session_regenerate_id(true);
            
            // Restaurar datos esenciales
            $_SESSION = [
                'user_id' => $oldData['user_id'] ?? null,
                'role' => $oldData['role'] ?? null,
                'email' => $oldData['email'] ?? null,
                'csrf_token' => self::generateCsrfToken(),
                'last_regeneration' => time(),
                'regeneration_reason' => $reason,
                'login_time' => $oldData['login_time'] ?? time()
            ];
            
            Logger::info('session_regenerated', ['reason' => $reason]);
        }
    }

    /**
     * Verifica si es necesario regenerar la sesión (cada 15 minutos)
     * 
     * @return bool True si se regeneró, False si no era necesario
     */
    public static function checkSessionRegeneration(): bool
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        $lastRegen = $_SESSION['last_regeneration'] ?? 0;
        $timeSinceRegen = time() - $lastRegen;
        
        // Regenerar cada 15 minutos (900 segundos)
        if ($timeSinceRegen > 900) {
            self::regenerateSessionContext('periodic_security');
            return true;
        }
        
        return false;
    }

    /**
     * Logout COMPLETO con invalidación total de sesión
     * - Invalida CSRF token
     * - Elimina cookies de sesión
     * - Registra en auditoría
     * - Destruye sesión completamente
     */
    public static function completeLogout(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $userId = $_SESSION['user_id'] ?? null;
            $userRole = $_SESSION['role'] ?? null;
            
            // Invalidar CSRF token ANTES de destruir sesión
            $_SESSION['csrf_token'] = null;
            
            // Registrar en auditoría
            Logger::info('user_logout', [
                'user_id' => $userId,
                'role' => $userRole,
                'session_duration' => time() - ($_SESSION['login_time'] ?? time())
            ]);
            
            // Limpiar array de sesión
            $_SESSION = [];
            
            // Eliminar cookie de sesión si existe
            if (isset($_COOKIE[session_name()])) {
                setcookie(
                    session_name(),
                    '',
                    time() - 3600,
                    '/',
                    '',
                    true, // secure
                    true  // httponly
                );
            }
            
            // Destruir sesión completamente
            session_destroy();
        }
    }

    /**
     * Genera un token CSRF seguro
     * 
     * @return string Token CSRF
     */
    public static function generateCsrfToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Valida token CSRF
     * 
     * @param string|null $token Token a validar
     * @return bool True si es válido
     */
    public static function validateCsrfToken(?string $token): bool
    {
        if ($token === null || empty($token)) {
            return false;
        }
        
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Verificación segura de password con timing attack prevention
     * 
     * @param string $password Password proporcionado
     * @param string|null $hash Hash almacenado en BD
     * @return bool True si coincide
     */
    public static function safePasswordVerify(string $password, ?string $hash): bool
    {
        // Hash dummy para prevenir timing attacks cuando no hay hash
        $dummyHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
        
        if ($hash === null || $hash === '') {
            // Ejecutar password_verify con hash dummy para mantener tiempo constante
            password_verify($password, $dummyHash);
            return false;
        }
        
        return password_verify($password, $hash);
    }

    /**
     * Filtra datos de entrada usando whitelist
     * Solo permite campos explícitamente autorizados
     * 
     * @param array $input Datos de entrada
     * @param array $allowedFields Campos permitidos
     * @return array Datos filtrados
     */
    public static function whitelistData(array $input, array $allowedFields): array
    {
        $output = [];
        foreach ($allowedFields as $field) {
            if (array_key_exists($field, $input)) {
                $output[$field] = $input[$field];
            }
        }
        return $output;
    }

    /**
     * Valida método HTTP permitido para una ruta
     * 
     * @param string $method Método HTTP recibido
     * @param array $allowedMethods Métodos permitidos
     * @throws \Exception Si el método no está permitido
     */
    public static function validateHttpMethod(string $method, array $allowedMethods): void
    {
        $method = strtoupper($method);
        
        if (!in_array($method, $allowedMethods)) {
            Logger::warning('http_method_not_allowed', [
                'method' => $method,
                'allowed' => $allowedMethods
            ]);
            
            http_response_code(405);
            header('Content-Type: application/json');
            header('Allow: ' . implode(', ', $allowedMethods));
            echo json_encode([
                'success' => false,
                'error' => 'Método no permitido',
                'allowed_methods' => $allowedMethods
            ]);
            exit;
        }
    }
}
