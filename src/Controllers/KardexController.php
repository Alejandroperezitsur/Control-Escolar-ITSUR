<?php

namespace App\Controllers;

use PDO;
use App\Services\KardexService;

/**
 * Controller for Kardex (Academic History) functionality
 */
class KardexController
{
    private PDO $pdo;
    private KardexService $kardexService;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->kardexService = new KardexService($pdo);
    }
    
    /**
     * Display Kardex page
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
            SELECT a.*, c.nombre as carrera_nombre, c.creditos_totales
            FROM alumnos a
            LEFT JOIN carreras c ON c.id = a.carrera_id
            WHERE a.id = ?
        ");
        $stmt->execute([$alumnoId]);
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$alumno) {
            die('Alumno no encontrado');
        }
        
        // Get complete history
        $history = $this->kardexService->getCompleteHistory($alumnoId);
        
        // Get statistics
        $stats = $this->kardexService->getStatistics($alumnoId);
        
        // Get current semester credits
        $currentCredits = $this->kardexService->getCurrentSemesterCredits($alumnoId);
        
        // Group by semester
        $historySemester = $this->kardexService->getHistoryBySemester($alumnoId);
        
        // Pass to view
        $viewData = [
            'alumno' => $alumno,
            'history' => $history,
            'historySemester' => $historySemester,
            'stats' => $stats,
            'currentCredits' => $currentCredits,
            'pageTitle' => 'Kardex - Historial Académico'
        ];
        
        require __DIR__ . '/../Views/kardex/index.php';
    }
    
    /**
     * Export Kardex as PDF
     */
    public function exportPDF()
    {
        // TODO: Implement PDF generation using dompdf
        // For now, just redirect back
        header('Location: /kardex');
        exit;
    }
    
    /**
     * Get Kardex data as JSON (API)
     */
    public function getJSON()
    {
        if (!isset($_SESSION['user_id']) || $_SESSION['rol'] !== 'alumno') {
            http_response_code(403);
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $alumnoId = $_SESSION['user_id'];
        
        $history = $this->kardexService->getCompleteHistory($alumnoId);
        $stats = $this->kardexService->getStatistics($alumnoId);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'history' => $history,
            'statistics' => $stats
        ]);
    }
}
