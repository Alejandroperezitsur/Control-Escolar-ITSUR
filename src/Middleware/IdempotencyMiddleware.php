<?php

namespace App\Middleware;

use PDO;
use Exception;

/**
 * Middleware de Idempotencia - Previene doble submit y ejecución duplicada
 * 
 * USO: Cada request crítico debe enviar un X-Idempotency-Key único
 * Si el mismo key se envía twice, retorna el resultado cacheado sin reejecutar
 */
class IdempotencyMiddleware {
    
    private PDO $db;
    private const KEY_TTL_HOURS = 24;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Verifica si el request ya fue procesado
     * @return array|null Retorna respuesta cacheada si existe, null si es nuevo
     */
    public function handle(string $requestKey, int $userId, string $endpoint): ?array {
        // Limpiar keys expiradas
        $this->cleanupExpired();
        
        // Buscar request existente
        $stmt = $this->db->prepare("
            SELECT response_data, status_code, created_at 
            FROM idempotency_keys 
            WHERE request_key = :key 
            AND user_id = :uid 
            AND expires_at > NOW()
        ");
        
        $stmt->execute([
            ':key' => $requestKey,
            ':uid' => $userId
        ]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            // Request ya procesado - retornar resultado cacheado
            http_response_code($result['status_code'] ?? 200);
            header('X-Idempotency-Replayed: true');
            return json_decode($result['response_data'], true);
        }
        
        return null;
    }
    
    /**
     * Guarda el resultado de un request para futura idempotencia
     */
    public function storeResult(
        string $requestKey, 
        int $userId, 
        string $endpoint, 
        array $responseData, 
        int $statusCode
    ): void {
        $expiresAt = date('Y-m-d H:i:s', strtotime('+' . self::KEY_TTL_HOURS . ' hours'));
        
        $stmt = $this->db->prepare("
            INSERT INTO idempotency_keys 
            (request_key, user_id, endpoint, response_data, status_code, expires_at)
            VALUES (:key, :uid, :endpoint, :response, :status, :expires)
            ON DUPLICATE KEY UPDATE 
                response_data = VALUES(response_data),
                status_code = VALUES(status_code),
                expires_at = VALUES(expires_at)
        ");
        
        $stmt->execute([
            ':key' => $requestKey,
            ':uid' => $userId,
            ':endpoint' => $endpoint,
            ':response' => json_encode($responseData),
            ':status' => $statusCode,
            ':expires' => $expiresAt
        ]);
    }
    
    /**
     * Genera un key único para un request
     */
    public static function generateKey(array $requestData, string $endpoint): string {
        $normalized = [
            'endpoint' => $endpoint,
            'data' => $requestData
        ];
        
        return hash('sha256', json_encode($normalized, JSON_SORT_KEYS));
    }
    
    /**
     * Limpieza de keys expiradas
     */
    private function cleanupExpired(): void {
        $this->db->exec("DELETE FROM idempotency_keys WHERE expires_at < NOW()");
    }
}
