<?php

namespace App\Services;

use PDO;
use App\Middleware\SecurityMiddleware;

class GradesService {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Actualiza calificacion con validacion de periodo bloqueado y auditoria
     */
    public function updateGrade(int $calificacionId, array $data, int $userId, string $userRole, ?string $motivo = null): array {
        try {
            $this->pdo->beginTransaction();

            // Verificar propiedad del recurso (IDOR protection)
            SecurityMiddleware::verifyOwnership($this->pdo, $calificacionId, 'calificacion', $userId, $userRole);

            // Obtener calificacion y ciclo asociado
            $stmt = $this->pdo->prepare("
                SELECT c.*, g.ciclo_id 
                FROM calificaciones c
                JOIN grupos g ON c.grupo_id = g.id
                WHERE c.id = :cid AND c.deleted_at IS NULL
                FOR UPDATE
            ");
            $stmt->execute(['cid' => $calificacionId]);
            $registro = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$registro) {
                $this->pdo->rollBack();
                return ['success' => false, 'error' => 'Calificacion no encontrada', 'http_code' => 404];
            }

            // Verificar si el periodo esta bloqueado
            $stmtCiclo = $this->pdo->prepare("SELECT calificaciones_bloqueadas FROM ciclos_escolares WHERE id = :cid");
            $stmtCiclo->execute(['cid' => $registro['ciclo_id']]);
            $bloqueado = $stmtCiclo->fetchColumn();

            if ($bloqueado && $userRole !== 'admin') {
                $this->pdo->rollBack();
                return ['success' => false, 'error' => 'El periodo esta cerrado. No se pueden modificar calificaciones.', 'http_code' => 403];
            }

            // Whitelist de campos permitidos (Mass Assignment protection)
            $allowedFields = ['parcial1', 'parcial2', 'parcial3', 'final'];
            $newValues = SecurityMiddleware::whitelistData($data, $allowedFields);
            $oldValues = [];

            // Validar y filtrar valores
            foreach ($allowedFields as $field) {
                if (isset($newValues[$field])) {
                    $val = filter_var($newValues[$field], FILTER_VALIDATE_INT);
                    if ($val === false || $val < 0 || $val > 100) {
                        $this->pdo->rollBack();
                        return ['success' => false, 'error' => "Valor invalido para {$field}. Debe ser 0-100.", 'http_code' => 400];
                    }
                    $newValues[$field] = $val;
                    $oldValues[$field] = $registro[$field];
                }
            }

            if (empty($newValues)) {
                $this->pdo->rollBack();
                return ['success' => false, 'error' => 'No se proporcionaron datos validos para actualizar', 'http_code' => 400];
            }

            // Calcular promedio final correctamente (ignorar NULLs)
            $promedioFinal = null;
            if (isset($newValues['final']) && $newValues['final'] !== null) {
                $promedioFinal = $newValues['final'];
            } else {
                $parciales = [
                    $newValues['parcial1'] ?? $oldValues['parcial1'] ?? null,
                    $newValues['parcial2'] ?? $oldValues['parcial2'] ?? null,
                    $newValues['parcial3'] ?? $oldValues['parcial3'] ?? null
                ];
                $parcialesValidos = array_filter($parciales, fn($v) => $v !== null);
                if (!empty($parcialesValidos)) {
                    $promedioFinal = array_sum($parcialesValidos) / count($parcialesValidos);
                }
            }

            // Construir UPDATE dinamicamente
            $setClause = [];
            $params = [];
            foreach ($newValues as $k => $v) {
                $setClause[] = "{$k} = :{$k}";
                $params[":{$k}"] = $v;
            }
            $setClause[] = "promedio_final = :prom";
            $params[':prom'] = $promedioFinal;
            $params[':cid'] = $calificacionId;

            $sql = "UPDATE calificaciones SET " . implode(', ', $setClause) . " WHERE id = :cid";
            $stmtUp = $this->pdo->prepare($sql);
            $stmtUp->execute($params);

            // Registrar en auditoria
            $stmtAud = $this->pdo->prepare("
                INSERT INTO auditoria_academica 
                (usuario_id, accion, tabla_afectada, registro_id, valores_anteriores, valores_nuevos, motivo, ip_address)
                VALUES (:uid, 'EDIT_NOTA', 'calificaciones', :rid, :old, :new, :mot, :ip)
            ");
            
            $stmtAud->execute([
                ':uid' => $userId,
                ':rid' => $calificacionId,
                ':old' => json_encode($oldValues),
                ':new' => json_encode($newValues),
                ':mot' => $motivo ?? 'Sin motivo especificado',
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI'
            ]);

            $this->pdo->commit();
            return ['success' => true, 'http_code' => 200];

        } catch (\Exception $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            return ['success' => false, 'error' => $e->getMessage(), 'http_code' => 500];
        }
    }

    /**
     * Obtiene calificaciones de un alumno con proteccion IDOR
     */
    public function getStudentGrades(int $alumnoId, int $userId, string $userRole): array {
        // Si es alumno, solo puede ver sus propias calificaciones
        if ($userRole === 'alumno') {
            $stmt = $this->pdo->prepare("SELECT id FROM alumnos WHERE id = :id AND user_id = :uid AND deleted_at IS NULL");
            $stmt->execute(['id' => $alumnoId, 'uid' => $userId]);
            if (!$stmt->fetchColumn()) {
                return ['success' => false, 'error' => 'Acceso denegado', 'http_code' => 403];
            }
        }

        $stmt = $this->pdo->prepare("
            SELECT c.*, g.ciclo, m.nombre as materia, p.nombre as profesor
            FROM calificaciones c
            JOIN grupos g ON c.grupo_id = g.id
            JOIN materias m ON g.materia_id = m.id
            JOIN docentes d ON g.docente_id = d.id
            JOIN personas p ON d.persona_id = p.id
            WHERE c.alumno_id = :aid AND c.deleted_at IS NULL
            ORDER BY g.ciclo DESC, m.nombre
        ");
        $stmt->execute(['aid' => $alumnoId]);
        
        return ['success' => true, 'data' => $stmt->fetchAll(\PDO::FETCH_ASSOC), 'http_code' => 200];
    }
}
