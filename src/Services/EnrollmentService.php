<?php
namespace App\Services;

use PDO;
use App\Services\AcademicService;
use App\Utils\Logger;

class EnrollmentService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function adminEnroll(int $alumnoId, int $grupoId): array
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $this->pdo->beginTransaction();
                $chkA = $this->pdo->prepare('SELECT 1 FROM alumnos WHERE id = :id AND activo = 1');
                $chkA->execute([':id' => $alumnoId]);
                if (!$chkA->fetchColumn()) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 400,
                        'payload' => ['success' => false, 'error' => 'Alumno no existe o inactivo'],
                    ];
                }
                $stG = $this->pdo->prepare('SELECT g.id, COALESCE(g.cupo,30) AS cupo FROM grupos g WHERE g.id = :id FOR UPDATE');
                $stG->execute([':id' => $grupoId]);
                $g = $stG->fetch(PDO::FETCH_ASSOC);
                if (!$g) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 400,
                        'payload' => ['success' => false, 'error' => 'Grupo no existe'],
                    ];
                }
                $stCnt = $this->pdo->prepare('SELECT COUNT(*) FROM calificaciones WHERE grupo_id = :g');
                $stCnt->execute([':g' => $grupoId]);
                $ocup = (int)$stCnt->fetchColumn();
                if ($ocup >= (int)($g['cupo'] ?? 30)) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 409,
                        'payload' => ['success' => false, 'error' => 'Cupo lleno'],
                    ];
                }
                $exists = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g');
                $exists->execute([':a' => $alumnoId, ':g' => $grupoId]);
                if ($exists->fetchColumn()) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 409,
                        'payload' => ['success' => false, 'error' => 'Ya inscrito'],
                    ];
                }
                $ins = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a,:g,NULL,NULL,NULL)');
                $ok = $ins->execute([':a' => $alumnoId, ':g' => $grupoId]);
                if (!$ok) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 500,
                        'payload' => ['success' => false, 'error' => 'Error al inscribir'],
                    ];
                }
                $this->pdo->commit();
                Logger::info('enroll_admin', ['alumno_id' => $alumnoId, 'grupo_id' => $grupoId]);
                return [
                    'success' => true,
                    'http_code' => 200,
                    'payload' => ['success' => true],
                ];
            } catch (\PDOException $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                $code = $e->errorInfo[1] ?? null;
                $sqlState = $e->getCode();
                if ($code === 1213 || $code === 1205 || $sqlState === '40001') {
                    if ($attempt < 2) {
                        usleep(50000);
                        continue;
                    }
                    return [
                        'success' => false,
                        'http_code' => 503,
                        'payload' => ['success' => false, 'error' => 'Conflicto de concurrencia, intenta nuevamente'],
                    ];
                }
                throw $e;
            } catch (\Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $e;
            }
        }
        return [
            'success' => false,
            'http_code' => 500,
            'payload' => ['success' => false, 'error' => 'Error interno de inscripción'],
        ];
    }

    public function adminUnenroll(int $alumnoId, int $grupoId): array
    {
        $del = $this->pdo->prepare('DELETE FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g');
        $ok = $del->execute([':a' => $alumnoId, ':g' => $grupoId]);
        if (!$ok) {
            return [
                'success' => false,
                'http_code' => 500,
                'payload' => ['success' => false, 'error' => 'Error al desinscribir'],
            ];
        }
        Logger::info('unenroll_admin', ['alumno_id' => $alumnoId, 'grupo_id' => $grupoId]);
        return [
            'success' => true,
            'http_code' => 200,
            'payload' => ['success' => true],
        ];
    }

    public function studentSelfEnroll(int $alumnoId, int $grupoId): array
    {
        for ($attempt = 0; $attempt < 3; $attempt++) {
            try {
                $this->pdo->beginTransaction();
                $stG = $this->pdo->prepare('SELECT g.id, g.materia_id, g.ciclo, g.ciclo_id, COALESCE(g.cupo,30) AS cupo FROM grupos g WHERE g.id = :id FOR UPDATE');
                $stG->execute([':id' => $grupoId]);
                $g = $stG->fetch(PDO::FETCH_ASSOC);
                if (!$g) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 404,
                        'payload' => ['success' => false, 'error' => 'Grupo no existe'],
                    ];
                }
                (new AcademicService($this->pdo))->assertActiveCycleForGroup($grupoId);
                $stCnt = $this->pdo->prepare('SELECT COUNT(*) FROM calificaciones WHERE grupo_id = :g');
                $stCnt->execute([':g' => $grupoId]);
                $ocup = (int)$stCnt->fetchColumn();
                if ($ocup >= (int)($g['cupo'] ?? 30)) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 409,
                        'payload' => ['success' => false, 'error' => 'Cupo lleno'],
                    ];
                }
                $stDup = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g LIMIT 1');
                $stDup->execute([':a' => $alumnoId, ':g' => $grupoId]);
                if ($stDup->fetchColumn()) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 409,
                        'payload' => ['success' => false, 'error' => 'Ya inscrito en el grupo'],
                    ];
                }
                $stApr = $this->pdo->prepare('SELECT 1 FROM calificaciones c JOIN grupos gx ON gx.id = c.grupo_id WHERE c.alumno_id = :a AND gx.materia_id = :m AND c.final IS NOT NULL AND c.final >= 70 LIMIT 1');
                $stApr->execute([':a' => $alumnoId, ':m' => (int)($g['materia_id'] ?? 0)]);
                if ($stApr->fetchColumn()) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 409,
                        'payload' => ['success' => false, 'error' => 'Materia ya aprobada'],
                    ];
                }
                $stPendSame = $this->pdo->prepare('SELECT 1 FROM calificaciones c JOIN grupos gx ON gx.id = c.grupo_id WHERE c.alumno_id = :a AND c.final IS NULL AND gx.materia_id = :m AND gx.ciclo = :c AND gx.id <> :g LIMIT 1');
                $stPendSame->execute([
                    ':a' => $alumnoId,
                    ':m' => (int)($g['materia_id'] ?? 0),
                    ':c' => (string)($g['ciclo'] ?? ''),
                    ':g' => (int)($g['id'] ?? 0),
                ]);
                if ($stPendSame->fetchColumn()) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 409,
                        'payload' => ['success' => false, 'error' => 'Ya tienes una inscripción pendiente en la misma materia y ciclo'],
                    ];
                }
                $stPre = $this->pdo->prepare('SELECT materia_requisito_id FROM materias_prerrequisitos WHERE materia_id = :mid');
                $stPre->execute([':mid' => (int)($g['materia_id'] ?? 0)]);
                $reqIds = $stPre->fetchAll(PDO::FETCH_COLUMN);
                if ($reqIds) {
                    $reqIds = array_map('intval', $reqIds);
                    $placeholders = implode(',', array_fill(0, count($reqIds), '?'));
                    $sqlReq = 'SELECT COUNT(DISTINCT gx.materia_id) FROM calificaciones c2 JOIN grupos gx ON gx.id = c2.grupo_id WHERE c2.alumno_id = ? AND c2.final IS NOT NULL AND c2.final >= 70 AND gx.materia_id IN ('.$placeholders.')';
                    $params = array_merge([$alumnoId], $reqIds);
                    $stReq = $this->pdo->prepare($sqlReq);
                    $stReq->execute($params);
                    $aprobadasReq = (int)$stReq->fetchColumn();
                    if ($aprobadasReq < count($reqIds)) {
                        $this->pdo->rollBack();
                        return [
                            'success' => false,
                            'http_code' => 409,
                            'payload' => ['success' => false, 'error' => 'No has aprobado las materias prerrequisito para este grupo'],
                        ];
                    }
                }
                $cfg = @include __DIR__ . '/../../config/config.php';
                $maxPerCycle = 7;
                if (is_array($cfg) && isset($cfg['academic']['max_grupos_por_ciclo'])) {
                    $maxPerCycle = (int)$cfg['academic']['max_grupos_por_ciclo'] ?: $maxPerCycle;
                }
                $stCountCycle = $this->pdo->prepare('SELECT COUNT(*) FROM calificaciones c JOIN grupos gx ON gx.id = c.grupo_id WHERE c.alumno_id = :a AND gx.ciclo = :c AND c.final IS NULL');
                $stCountCycle->execute([':a' => $alumnoId, ':c' => (string)($g['ciclo'] ?? '')]);
                $pendingInCycle = (int)$stCountCycle->fetchColumn();
                if ($pendingInCycle >= $maxPerCycle) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 409,
                        'payload' => ['success' => false, 'error' => 'Has alcanzado el límite de grupos pendientes por ciclo'],
                    ];
                }
                $ins = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a,:g,NULL,NULL,NULL)');
                $ok = $ins->execute([':a' => $alumnoId, ':g' => $grupoId]);
                if (!$ok) {
                    $this->pdo->rollBack();
                    return [
                        'success' => false,
                        'http_code' => 500,
                        'payload' => ['success' => false, 'error' => 'Error al inscribir'],
                    ];
                }
                $this->pdo->commit();
                Logger::info('enroll_student', ['alumno_id' => $alumnoId, 'grupo_id' => $grupoId]);
                return [
                    'success' => true,
                    'http_code' => 200,
                    'payload' => ['success' => true],
                ];
            } catch (\PDOException $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                $code = $e->errorInfo[1] ?? null;
                $sqlState = $e->getCode();
                if ($code === 1213 || $code === 1205 || $sqlState === '40001') {
                    if ($attempt < 2) {
                        usleep(50000);
                        continue;
                    }
                    return [
                        'success' => false,
                        'http_code' => 503,
                        'payload' => ['success' => false, 'error' => 'Conflicto de concurrencia, intenta nuevamente'],
                    ];
                }
                throw $e;
            } catch (\Throwable $e) {
                if ($this->pdo->inTransaction()) {
                    $this->pdo->rollBack();
                }
                throw $e;
            }
        }
        return [
            'success' => false,
            'http_code' => 500,
            'payload' => ['success' => false, 'error' => 'Error interno de inscripción'],
        ];
    }

    public function studentSelfUnenroll(int $alumnoId, int $grupoId): array
    {
        $stChk = $this->pdo->prepare('SELECT c.id FROM calificaciones c WHERE c.alumno_id = :a AND c.grupo_id = :g AND c.final IS NULL');
        $stChk->execute([':a' => $alumnoId, ':g' => $grupoId]);
        $row = $stChk->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            return [
                'success' => false,
                'http_code' => 409,
                'payload' => ['success' => false, 'error' => 'No se puede desinscribir (ya evaluado o no inscrito)'],
            ];
        }
        $del = $this->pdo->prepare('DELETE FROM calificaciones WHERE id = :id');
        $ok = $del->execute([':id' => (int)($row['id'] ?? 0)]);
        if (!$ok) {
            return [
                'success' => false,
                'http_code' => 500,
                'payload' => ['success' => false, 'error' => 'Error al desinscribir'],
            ];
        }
        Logger::info('unenroll_student', ['alumno_id' => $alumnoId, 'grupo_id' => $grupoId]);
        return [
            'success' => true,
            'http_code' => 200,
            'payload' => ['success' => true],
        ];
    }
}

