<?php

namespace App\Services;

use PDO;

/**
 * Service for Kardex (Academic History) Calculations and Queries
 */
class KardexService
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Get complete academic history for a student
     * 
     * @param int $alumnoId Student ID
     * @return array Complete history with all subjects
     */
    public function getCompleteHistory(int $alumnoId): array
    {
        // Query directa (sin vista) para compatibilidad con InfinityFree
        $stmt = $this->pdo->prepare("
            SELECT 
                a.id as alumno_id,
                a.matricula,
                a.nombre,
                a.apellido,
                a.carrera_id,
                c.nombre as carrera_nombre,
                i.id as inscripcion_id,
                m.id as materia_id,
                m.nombre as materia_nombre,
                m.clave as materia_clave,
                m.creditos,
                g.nombre as grupo,
                g.ciclo,
                COALESCE(mc.semestre, i.semestre_cursado, 1) as semestre,
                cf.promedio_unidades,
                cf.calificacion_final,
                cf.promedio_general,
                cf.estatus,
                cf.tipo_acreditacion,
                cf.periodo_acreditacion,
                CASE 
                    WHEN cf.promedio_general IS NULL THEN 'Sin Calificar'
                    WHEN cf.promedio_general >= 90 THEN 'Excelente'
                    WHEN cf.promedio_general >= 80 THEN 'Notable'
                    WHEN cf.promedio_general >= 70 THEN 'Bueno'
                    WHEN cf.promedio_general >= 60 THEN 'Suficiente'
                    ELSE 'No Acreditado'
                END as nivel_desempeno,
                i.fecha_inscripcion,
                i.estatus as estatus_inscripcion
            FROM alumnos a
            JOIN inscripciones i ON i.alumno_id = a.id
            JOIN grupos g ON g.id = i.grupo_id
            JOIN materias m ON m.id = g.materia_id
            LEFT JOIN carreras c ON c.id = a.carrera_id
            LEFT JOIN materias_carrera mc ON mc.materia_id = m.id AND mc.carrera_id = a.carrera_id
            LEFT JOIN calificaciones_finales cf ON cf.inscripcion_id = i.id
            WHERE a.id = ?
            ORDER BY semestre, i.fecha_inscripcion
        ");
        $stmt->execute([$alumnoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get statistics summary for a student
     * 
     * @param int $alumnoId Student ID
     * @return array Statistics (credits, avg, etc.)
     */
    public function getStatistics(int $alumnoId): array
    {
        // Query directa (sin vista) para compatibilidad con InfinityFree
        $stmt = $this->pdo->prepare("
            SELECT 
                a.id as alumno_id,
                a.matricula,
                a.nombre,
                a.apellido,
                a.carrera_id,
                c.nombre as carrera_nombre,
                c.creditos_totales as creditos_requeridos,
                COUNT(DISTINCT i.id) as total_materias_cursadas,
                SUM(CASE WHEN cf.estatus = 'aprobado' THEN 1 ELSE 0 END) as materias_aprobadas,
                SUM(CASE WHEN cf.estatus = 'reprobado' THEN 1 ELSE 0 END) as materias_reprobadas,
                SUM(CASE WHEN cf.estatus IN ('aprobado') THEN m.creditos ELSE 0 END) as creditos_completados,
                ROUND(
                    (SUM(CASE WHEN cf.estatus = 'aprobado' THEN m.creditos ELSE 0 END) / c.creditos_totales) * 100,
                    2
                ) as porcentaje_avance,
                ROUND(
                    AVG(CASE WHEN cf.promedio_general IS NOT NULL AND cf.estatus IN ('aprobado', 'reprobado') 
                        THEN cf.promedio_general 
                        ELSE NULL 
                    END),
                    2
                ) as promedio_general,
                MAX(COALESCE(i.semestre_cursado, mc.semestre, 1)) as semestre_actual
            FROM alumnos a
            LEFT JOIN carreras c ON c.id = a.carrera_id
            LEFT JOIN inscripciones i ON i.alumno_id = a.id
            LEFT JOIN grupos g ON g.id = i.grupo_id
            LEFT JOIN materias m ON m.id = g.materia_id
            LEFT JOIN materias_carrera mc ON mc.materia_id = m.id AND mc.carrera_id = a.carrera_id
            LEFT JOIN calificaciones_finales cf ON cf.inscripcion_id = i.id
            WHERE a.id = ?
            GROUP BY a.id, c.id
        ");
        $stmt->execute([$alumnoId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stats) {
            return [
                'alumno_id' => $alumnoId,
                'creditos_requeridos' => 240,
                'creditos_completados' => 0,
                'porcentaje_avance' => 0,
                'promedio_general' => 0,
                'total_materias_cursadas' => 0,
                'materias_aprobadas' => 0,
                'materias_reprobadas' => 0,
                'semestre_actual' => 1
            ];
        }
        
        return $stats;
    }
    
    /**
     * Calculate total credits completed
     * 
     * @param int $alumnoId Student ID
     * @return int Total credits
     */
    public function calculateTotalCredits(int $alumnoId): int
    {
        $stats = $this->getStatistics($alumnoId);
        return (int)($stats['creditos_completados'] ?? 0);
    }
    
    /**
     * Calculate completion percentage
     * 
     * @param int $alumnoId Student ID
     * @return float Percentage (0-100)
     */
    public function calculateCompletionPercentage(int $alumnoId): float
    {
        $stats = $this->getStatistics($alumnoId);
        return (float)($stats['porcentaje_avance'] ?? 0);
    }
    
    /**
     * Calculate general average (GPA)
     * 
     * @param int $alumnoId Student ID
     * @return float Average grade
     */
    public function calculateGeneralAverage(int $alumnoId): float
    {
        $stats = $this->getStatistics($alumnoId);
        return (float)($stats['promedio_general'] ?? 0);
    }
    
    /**
     * Get count of approved subjects
     * 
     * @param int $alumnoId Student ID
     * @return int Count
     */
    public function getApprovedCount(int $alumnoId): int
    {
        $stats = $this->getStatistics($alumnoId);
        return (int)($stats['materias_aprobadas'] ?? 0);
    }
    
    /**
     * Get count of failed subjects
     * 
     * @param int $alumnoId Student ID
     * @return int Count
     */
    public function getFailedCount(int $alumnoId): int
    {
        $stats = $this->getStatistics($alumnoId);
        return (int)($stats['materias_reprobadas'] ?? 0);
    }
    
    /**
     * Get current credits being taken this semester
     * 
     * @param int $alumnoId Student ID
     * @return int Credits
     */
    public function getCurrentSemesterCredits(int $alumnoId): int
    {
        $stmt = $this->pdo->prepare("
            SELECT SUM(m.creditos) as total
            FROM inscripciones i
            JOIN grupos g ON g.id = i.grupo_id
            JOIN materias m ON m.id = g.materia_id
            WHERE i.alumno_id = ? AND i.estatus = 'inscrito'
        ");
        $stmt->execute([$alumnoId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }
    
    /**
     * Get Kardex data grouped by semester
     * 
     * @param int $alumnoId Student ID
     * @return array Array of semesters with subjects
     */
    public function getHistoryBySemester(int $alumnoId): array
    {
        $history = $this->getCompleteHistory($alumnoId);
        $bySemester = [];
        
        foreach ($history as $record) {
            $sem = $record['semestre'];
            if (!isset($bySemester[$sem])) {
                $bySemester[$sem] = [];
            }
            $bySemester[$sem][] = $record;
        }
        
        ksort($bySemester);
        return $bySemester;
    }
}
