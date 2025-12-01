<?php

namespace App\Controllers;

use PDO;

/**
 * Controller for Academic Load (Carga Académica) functionality
 */
class AcademicLoadController
{
    private PDO $pdo;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    
    /**
     * Display Academic Load page (current semester subjects)
     */
    public function index()
    {
        // Require auth and student role
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'alumno') {
            header('Location: /login');
            exit;
        }
        
        $alumnoId = $_SESSION['user_id'];
        
        // Get student info
        $stmt = $this->pdo->prepare("
            SELECT a.*, c.nombre as carrera_nombre
            FROM alumnos a
            LEFT JOIN carreras c ON c.id = a.carrera_id
            WHERE a.id = ?
        ");
        $stmt->execute([$alumnoId]);
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$alumno) {
            die('Alumno no encontrado');
        }
        
        // Get current academic load (sin vista para InfinityFree)
        $stmt = $this->pdo->prepare("
            SELECT 
                a.id as alumno_id,
                a.matricula,
                a.nombre as alumno_nombre,
                a.apellido as alumno_apellido,
                m.id as materia_id,
                m.nombre as materia_nombre,
                m.clave as materia_clave,
                m.creditos,
                g.id as grupo_id,
                g.nombre as grupo_nombre,
                g.ciclo,
                u.id as profesor_id,
                u.nombre as profesor_nombre,
                GROUP_CONCAT(
                    DISTINCT CONCAT(
                        UPPER(SUBSTRING(h.dia_semana, 1, 1)),
                        LOWER(SUBSTRING(h.dia_semana, 2)),
                        ' ',
                        TIME_FORMAT(h.hora_inicio, '%H:%i'),
                        '-',
                        TIME_FORMAT(h.hora_fin, '%H:%i'),
                        ' ',
                        COALESCE(h.aula, 'Sin asignar')
                    )
                    ORDER BY FIELD(h.dia_semana, 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado')
                    SEPARATOR '; '
                ) as horarios,
                i.semestre_cursado
            FROM alumnos a
            JOIN inscripciones i ON i.alumno_id = a.id AND i.estatus = 'inscrito'
            JOIN grupos g ON g.id = i.grupo_id
            JOIN materias m ON m.id = g.materia_id
            JOIN usuarios u ON u.id = g.profesor_id
            LEFT JOIN horarios h ON h.grupo_id = g.id
            WHERE a.id = ?
            GROUP BY a.id, m.id, g.id, u.id, i.semestre_cursado
            ORDER BY m.nombre
        ");
        $stmt->execute([$alumnoId]);
        $cargaAcademica = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Calculate total credits
        $totalCredits = array_sum(array_column($cargaAcademica, 'creditos'));
        
        // Get schedule grid (organized view)
        $scheduleGrid = $this->getScheduleGrid($alumnoId);
        
        // Pass to view
        $viewData = [
            'alumno' => $alumno,
            'cargaAcademica' => $cargaAcademica,
            'totalCredits' => $totalCredits,
            'scheduleGrid' => $scheduleGrid,
            'pageTitle' => 'Carga Académica - Semestre Actual'
        ];
        
        require __DIR__ . '/../Views/academic_load/index.php';
    }
    
    /**
     * Get schedule organized in a weekly grid
     * 
     * @param int $alumnoId
     * @return array Schedule grid organized by day and time
     */
    private function getScheduleGrid(int $alumnoId): array
    {
        $stmt = $this->pdo->prepare("
            SELECT 
                m.nombre as materia,
                m.clave,
                h.dia_semana,
                h.hora_inicio,
                h.hora_fin,
                h.aula,
                u.nombre as profesor
            FROM inscripciones i
            JOIN grupos g ON g.id = i.grupo_id
            JOIN materias m ON m.id = g.materia_id
            JOIN horarios h ON h.grupo_id = g.id
            JOIN usuarios u ON u.id = g.profesor_id
            WHERE i.alumno_id = ? AND i.estatus = 'inscrito'
            ORDER BY 
                FIELD(h.dia_semana, 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'),
                h.hora_inicio
        ");
        $stmt->execute([$alumnoId]);
        $horarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Organize by day
        $grid = [
            'lunes' => [],
            'martes' => [],
            'miércoles' => [],
            'jueves' => [],
            'viernes' => [],
            'sábado' => []
        ];
        
        foreach ($horarios as $h) {
            $grid[$h['dia_semana']][] = $h;
        }
        
        return $grid;
    }
    
    /**
     * Get academic load as JSON (API)
     */
    public function getJSON()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'alumno') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $alumnoId = $_SESSION['user_id'];
        
        $stmt = $this->pdo->prepare("
            SELECT * FROM view_carga_academica
            WHERE alumno_id = ?
        ");
        $stmt->execute([$alumnoId]);
        $cargaAcademica = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'data' => $cargaAcademica
        ]);
    }
}
