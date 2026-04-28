<?php

namespace App\Services;

use PDO;
use Exception;
use App\Middleware\SecurityMiddleware;

/**
 * CriticalAuditService - Auditoría inmutable con aprobación dual (Two-Man Rule)
 * 
 * Para cambios críticos (editar calificaciones históricas, eliminar registros),
 * se requiere aprobación de un segundo administrador.
 */
class CriticalAuditService {
    
    private PDO $db;
    
    public function __construct(PDO $db) {
        $this->db = $db;
    }
    
    /**
     * Registra un cambio crítico que requiere aprobación
     * El cambio NO se aplica inmediatamente, queda en estado pending
     */
    public function logCriticalChange(
        int $userId,
        string $action,
        string $table,
        int $recordId,
        array $oldValues,
        array $newValues,
        string $motivo
    ): int {
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'CLI';
        
        // Generar hash único para este log
        $logData = [
            'user_id' => $userId,
            'action' => $action,
            'table' => $table,
            'record_id' => $recordId,
            'old' => $oldValues,
            'new' => $newValues,
            'motivo' => $motivo,
            'ip' => $ipAddress,
            'timestamp' => microtime(true)
        ];
        
        $logHash = hash('sha256', json_encode($logData, JSON_SORT_KEYS));
        
        // Obtener hash del último registro para crear cadena inmutable
        $stmt = $this->db->query("SELECT log_hash FROM audit_log_immutable ORDER BY id DESC LIMIT 1");
        $previousHash = $stmt->fetchColumn() ?: null;
        
        // Insertar en log inmutable
        $stmt = $this->db->prepare("
            INSERT INTO audit_log_immutable 
            (log_hash, previous_hash, usuario_id, accion, tabla_afectada, registro_id, 
             valores_anteriores, valores_nuevos, motivo, ip_address, created_at)
            VALUES (:hash, :prev, :uid, :accion, :tabla, :rid, :old, :new, :mot, :ip, NOW())
        ");
        
        $stmt->execute([
            ':hash' => $logHash,
            ':prev' => $previousHash,
            ':uid' => $userId,
            ':accion' => $action,
            ':tabla' => $table,
            ':rid' => $recordId,
            ':old' => json_encode($oldValues),
            ':new' => json_encode($newValues),
            ':mot' => $motivo,
            ':ip' => $ipAddress
        ]);
        
        $auditId = $this->db->lastInsertId();
        
        // También registrar en auditoria_academica con estado pending
        $stmt = $this->db->prepare("
            INSERT INTO auditoria_academica 
            (usuario_id, accion, tabla_afectada, registro_id, valores_anteriores, valores_nuevos, 
             motivo, ip_address, requires_approval, approval_status, is_applied, created_at)
            VALUES (:uid, :accion, :tabla, :rid, :old, :new, :mot, :ip, 1, 'pending', 0, NOW())
        ");
        
        $stmt->execute([
            ':uid' => $userId,
            ':accion' => $action,
            ':tabla' => $table,
            ':rid' => $recordId,
            ':old' => json_encode($oldValues),
            ':new' => json_encode($newValues),
            ':mot' => $motivo,
            ':ip' => $ipAddress
        ]);
        
        return $this->db->lastInsertId();
    }
    
    /**
     * Aprueba un cambio pendiente (requiere rol admin diferente al que solicitó)
     */
    public function approveChange(int $auditId, int $approverUserId, bool $approved, ?string $rejectionReason = null): void {
        $currentUser = SecurityMiddleware::getCurrentUser();
        
        if (!$currentUser || !$currentUser->hasRole('admin')) {
            throw new Exception("Solo administradores pueden aprobar cambios críticos.", 403);
        }
        
        // Verificar que el aprobador no sea el mismo que solicitó
        $stmt = $this->db->prepare("SELECT usuario_id FROM auditoria_academica WHERE id = :aid");
        $stmt->execute([':aid' => $auditId]);
        $requesterId = $stmt->fetchColumn();
        
        if ($requesterId == $approverUserId) {
            throw new Exception("El aprobador debe ser diferente al solicitante (Two-Man Rule).", 400);
        }
        
        $status = $approved ? 'approved' : 'rejected';
        
        $stmt = $this->db->prepare("
            UPDATE auditoria_academica 
            SET approval_status = :status,
                approved_by = :approver,
                approved_at = NOW(),
                rejection_reason = :reason,
                is_applied = :applied
            WHERE id = :aid AND approval_status = 'pending'
        ");
        
        $stmt->execute([
            ':status' => $status,
            ':approver' => $approverUserId,
            ':reason' => $rejectionReason,
            ':applied' => $approved ? 1 : 0,
            ':aid' => $auditId
        ]);
        
        if ($approved && $stmt->rowCount() > 0) {
            // Aplicar el cambio real aquí (esto depende del tipo de cambio)
            $this->applyApprovedChange($auditId);
        }
    }
    
    /**
     * Aplica un cambio aprobado (implementación específica según el tipo de cambio)
     */
    private function applyApprovedChange(int $auditId): void {
        $stmt = $this->db->prepare("
            SELECT accion, tabla_afectada, registro_id, valores_nuevos 
            FROM auditoria_academica 
            WHERE id = :aid AND approval_status = 'approved' AND is_applied = 0
        ");
        
        $stmt->execute([':aid' => $auditId]);
        $change = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$change) {
            return;
        }
        
        // Aquí iría la lógica específica para aplicar cada tipo de cambio
        // Esto es un ejemplo genérico - debería extenderse según necesidades
        
        if ($change['tabla_afectada'] === 'calificaciones') {
            $newValues = json_decode($change['valores_nuevos'], true);
            
            $setClause = [];
            $params = [':id' => $change['registro_id']];
            
            foreach ($newValues as $field => $value) {
                $setClause[] = "$field = :$field";
                $params[":$field"] = $value;
            }
            
            $sql = "UPDATE calificaciones SET " . implode(', ', $setClause) . " WHERE id = :id";
            $updateStmt = $this->db->prepare($sql);
            $updateStmt->execute($params);
        }
        
        // Marcar como aplicado
        $this->db->prepare("UPDATE auditoria_academica SET is_applied = 1 WHERE id = :aid")
            ->execute([':aid' => $auditId]);
    }
    
    /**
     * Verifica la integridad de la cadena de logs inmutables
     * @return bool True si la cadena está intacta
     */
    public function verifyIntegrityChain(): bool {
        $stmt = $this->db->query("
            SELECT id, log_hash, previous_hash 
            FROM audit_log_immutable 
            ORDER BY id ASC
        ");
        
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $previousHash = null;
        
        foreach ($logs as $log) {
            if ($log['previous_hash'] !== $previousHash) {
                return false; // Cadena rota
            }
            
            // Recalcular hash para verificar que no fue modificado
            // (Esto requeriría almacenar todos los datos originales o usar tablas separadas)
            // Por simplicidad, solo verificamos la cadena de hashes
            
            $previousHash = $log['log_hash'];
        }
        
        return true;
    }
}
