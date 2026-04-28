<?php

namespace App\Middleware;

use PDO;

/**
 * Rate Limiting Middleware - Previene abuso y ataques de fuerza bruta
 * 
 * Límites configurables por endpoint y usuario/IP
 */
class RateLimitMiddleware {
    
    private PDO $db;
    
    // Límites por defecto (requests por ventana)
    private const LIMITS = [
        'login' => ['requests' => 5, 'window_seconds' => 300],      // 5 intentos en 5 min
        'enrollment' => ['requests' => 10, 'window_seconds' => 60], // 10 inscripciones en 1 min
        'grades_update' => ['requests' => 20, 'window_seconds' => 60],
        'default' => ['requests' => 100, 'window_seconds' => 60]
    ];
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Verifica si el usuario/IP ha excedido el límite
     * @throws Exception Si excede el límite
     */
    public function check(string $endpoint, ?int $userId = null, string $ipAddress): void {
        $this->cleanupOldRecords();
        
        $limitConfig = self::LIMITS[$endpoint] ?? self::LIMITS['default'];
        $windowStart = date('Y-m-d H:i:s', time() - $limitConfig['window_seconds']);
        
        // Contar requests recientes
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count 
            FROM rate_limit_log 
            WHERE endpoint = :endpoint 
            AND created_at > :window_start
            AND (user_id = :uid OR ip_address = :ip)
        ");
        
        $stmt->execute([
            ':endpoint' => $endpoint,
            ':window_start' => $windowStart,
            ':uid' => $userId,
            ':ip' => $ipAddress
        ]);
        
        $result = $stmt->fetch();
        $count = $result['count'];
        
        if ($count >= $limitConfig['requests']) {
            $retryAfter = $limitConfig['window_seconds'];
            http_response_code(429);
            header("Retry-After: $retryAfter");
            throw new Exception("Demasiadas solicitudes. Intente nuevamente en {$retryAfter} segundos.", 429);
        }
        
        // Registrar este request
        $this->logRequest($endpoint, $userId, $ipAddress);
    }
    
    /**
     * Registra un request en el log
     */
    private function logRequest(string $endpoint, ?int $userId, string $ipAddress): void {
        $stmt = $this->db->prepare("
            INSERT INTO rate_limit_log (user_id, ip_address, endpoint, created_at)
            VALUES (:uid, :ip, :endpoint, NOW())
        ");
        
        $stmt->execute([
            ':uid' => $userId,
            ':ip' => $ipAddress,
            ':endpoint' => $endpoint
        ]);
    }
    
    /**
     * Limpieza de registros antiguos
     */
    private function cleanupOldRecords(): void {
        $maxWindow = max(array_column(self::LIMITS, 'window_seconds'));
        $this->db->exec("DELETE FROM rate_limit_log WHERE created_at < DATE_SUB(NOW(), INTERVAL {$maxWindow} SECOND)");
    }
    
    /**
     * Resetear límite para un usuario (útil después de login exitoso)
     */
    public function resetForUser(string $endpoint, int $userId): void {
        $stmt = $this->db->prepare("
            DELETE FROM rate_limit_log 
            WHERE endpoint = :endpoint AND user_id = :uid
        ");
        
        $stmt->execute([
            ':endpoint' => $endpoint,
            ':uid' => $userId
        ]);
    }
}
