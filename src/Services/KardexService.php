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
        $stmt = $this->pdo->prepare("
            SELECT * FROM view_kardex
            WHERE alumno_id = ?
            ORDER BY semestre, fecha_inscripcion
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
        $stmt = $this->pdo->prepare("
            SELECT * FROM view_estadisticas_alumno
            WHERE alumno_id = ?
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
