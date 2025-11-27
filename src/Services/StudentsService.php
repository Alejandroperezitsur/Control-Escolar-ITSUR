<?php
namespace App\Services;

use PDO;
use App\Utils\Logger;

class StudentsService
{
    private PDO $pdo;
    private ?string $lastError = null;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function existsStudentActive(int $id): bool
    {
        if ($id <= 0) { return false; }
        $stmt = $this->pdo->prepare('SELECT 1 FROM alumnos WHERE id = :id AND activo = 1 LIMIT 1');
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    /**
     * Server-side guard: ensure the session user matches requested id
     */
    public function assertSelfAccess(int $sessionUserId, int $requestedId): bool
    {
        if ($sessionUserId <= 0 || $requestedId <= 0 || $sessionUserId !== $requestedId) {
            $this->lastError = 'Acceso inválido: no puedes consultar datos de otro alumno';
            Logger::info('student_access_denied', ['session_user_id' => $sessionUserId, 'requested_id' => $requestedId]);
            return false;
        }
        if (!$this->existsStudentActive($requestedId)) {
            $this->lastError = 'Alumno no existe o no está activo';
            Logger::info('student_validation_failed', ['reason' => 'alumno_missing_or_inactive', 'alumno_id' => $requestedId]);
            return false;
        }
        return true;
    }

    /**
     * Materias/grupos donde el alumno tiene inscripción (calificaciones registradas)
     * Retorna filas con materia, grupo, ciclo y estado/calificación.
     */
    public function getAcademicLoad(int $idAlumno, ?string $ciclo = null): array
    {
        if (!$this->existsStudentActive($idAlumno)) {
            $this->lastError = 'Alumno no existe o no está activo';
            Logger::info('student_load_failed', ['reason' => 'alumno_missing_or_inactive', 'alumno_id' => $idAlumno]);
            return [];
        }
        $params = [':alumno_id' => $idAlumno];
        $whereCiclo = '';
        if ($ciclo) { $whereCiclo = ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        $sql = "SELECT m.nombre AS materia, m.clave, g.id AS grupo_id, g.nombre AS grupo, g.ciclo,
                       c.parcial1, c.parcial2, c.final
                FROM calificaciones c
                JOIN grupos g ON c.grupo_id = g.id
                JOIN materias m ON g.materia_id = m.id
                WHERE c.alumno_id = :alumno_id $whereCiclo
                ORDER BY g.ciclo, m.nombre, g.nombre";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // Derivar estado y calificación visible
        $out = [];
        foreach ($rows as $r) {
            $final = $r['final'] !== null ? (int)$r['final'] : null;
            $estado = 'Pendiente';
            if ($final !== null) { $estado = ($final >= 70) ? 'Aprobado' : 'Reprobado'; }
            $out[] = [
                'materia' => (string)($r['materia'] ?? ''),
                'grupo_id' => (int)($r['grupo_id'] ?? 0),
                'grupo' => (string)($r['grupo'] ?? ''),
                'ciclo' => (string)($r['ciclo'] ?? ''),
                'calificacion' => $final,
                'estado' => $estado,
            ];
        }
        return $out;
    }

    /**
     * Resumen de calificaciones del alumno: promedio general, total de materias y aprobadas/pendientes
     */
    public function getGradesSummary(int $idAlumno, ?string $ciclo = null): array
    {
        if (!$this->existsStudentActive($idAlumno)) {
            $this->lastError = 'Alumno no existe o no está activo';
            Logger::info('student_stats_failed', ['reason' => 'alumno_missing_or_inactive', 'alumno_id' => $idAlumno]);
            return ['promedio' => 0.0, 'total' => 0, 'aprobadas' => 0, 'pendientes' => 0];
        }
        $params = [':alumno_id' => $idAlumno];
        $whereCiclo = '';
        if ($ciclo) { $whereCiclo = ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }

        // Promedio sobre final no nulos
        $stmt = $this->pdo->prepare("SELECT AVG(c.final) AS avg_final
                                      FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id
                                      WHERE c.alumno_id = :alumno_id $whereCiclo AND c.final IS NOT NULL");
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $avg = $stmt->fetchColumn();
        $promedio = $avg !== null ? round((float)$avg, 2) : 0.0;

        // Totales
        $stmt = $this->pdo->prepare("SELECT COUNT(DISTINCT c.grupo_id) FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id WHERE c.alumno_id = :alumno_id $whereCiclo");
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $total = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id WHERE c.alumno_id = :alumno_id $whereCiclo AND c.final IS NOT NULL AND c.final >= 70");
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $aprobadas = (int)$stmt->fetchColumn();

        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id WHERE c.alumno_id = :alumno_id $whereCiclo AND c.final IS NULL");
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $pendientes = (int)$stmt->fetchColumn();

        return [
            'promedio' => $promedio,
            'total' => $total,
            'aprobadas' => $aprobadas,
            'pendientes' => $pendientes,
        ];
    }

    /**
     * Datos para gráfica de rendimiento por ciclo (labels = ciclos, data = promedios finales)
     */
    public function getChartData(int $idAlumno): array
    {
        if (!$this->existsStudentActive($idAlumno)) {
            $this->lastError = 'Alumno no existe o no está activo';
            Logger::info('student_chart_failed', ['reason' => 'alumno_missing_or_inactive', 'alumno_id' => $idAlumno]);
            return ['labels' => [], 'data' => []];
        }
        $stmt = $this->pdo->prepare("SELECT g.ciclo, ROUND(AVG(c.final), 2) AS promedio
                                      FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id
                                      WHERE c.alumno_id = :alumno_id AND c.final IS NOT NULL
                                      GROUP BY g.ciclo ORDER BY g.ciclo");
        $stmt->execute([':alumno_id' => $idAlumno]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $labels = array_map(fn($r) => (string)$r['ciclo'], $rows);
        $data = array_map(fn($r) => (float)$r['promedio'], $rows);
        return ['labels' => $labels, 'data' => $data];
    }
}
