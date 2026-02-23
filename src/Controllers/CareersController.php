<?php
namespace App\Controllers;

use App\Http\Request;

class CareersController
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): string
    {
        return include __DIR__ . '/../Views/careers/index.php';
    }

    public function getCareersCount(): string
    {
        header('Content-Type: application/json');
        try {
            $count = (int)$this->pdo->query('SELECT COUNT(*) FROM careers')->fetchColumn();
            echo json_encode(['count' => $count]);
        } catch (\PDOException $e) {
            echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
        }
        return '';
    }

    public function getCurriculum(): string
    {
        $careerRaw = Request::getString('career', 'ISC');
        $careerClave = strtoupper((string)$careerRaw);
        
        header('Content-Type: application/json');
        
        try {
            // Get career ID
            $stmt = $this->pdo->prepare("SELECT id FROM carreras WHERE clave = :clave LIMIT 1");
            $stmt->execute([':clave' => $careerClave]);
            $carrera = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$carrera) {
                echo json_encode(['error' => 'Career not found']);
                return '';
            }
            
            $carreraId = (int)$carrera['id'];
            
            // Get curriculum organized by semester
            $stmt = $this->pdo->prepare("
                SELECT 
                    mc.semestre,
                    m.nombre as subject_name,
                    m.clave as subject_code,
                    mc.creditos,
                    mc.tipo
                FROM materias_carrera mc
                JOIN materias m ON mc.materia_id = m.id
                WHERE mc.carrera_id = :carrera_id
                ORDER BY mc.semestre, m.nombre
            ");
            $stmt->execute([':carrera_id' => $carreraId]);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            // Organize by semester
            $curriculum = [];
            foreach ($results as $row) {
                $semestre = (int)$row['semestre'];
                if (!isset($curriculum[$semestre])) {
                    $curriculum[$semestre] = [
                        'semester' => $semestre,
                        'subjects' => []
                    ];
                }
                $curriculum[$semestre]['subjects'][] = [
                    'name' => $row['subject_name'],
                    'code' => $row['subject_code'],
                    'credits' => (int)$row['creditos'],
                    'type' => $row['tipo']
                ];
            }
            
            // Convert to indexed array and sort by semester
            $curriculum = array_values($curriculum);
            
            echo json_encode($curriculum);
        } catch (\PDOException $e) {
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        }
        
        return '';
    }
}
