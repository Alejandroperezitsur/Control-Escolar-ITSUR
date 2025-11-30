<?php
namespace App\Controllers;

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
            $count = (int)$this->pdo->query('SELECT COUNT(*) FROM carreras')->fetchColumn();
            echo json_encode(['count' => $count]);
        } catch (\PDOException $e) {
            echo json_encode(['count' => 0, 'error' => $e->getMessage()]);
        }
        return '';
    }

    public function getCurriculum(): string
    {
        // Endpoint para obtener el curriculum de una carrera específica desde BD
        $careerClave = strtoupper($_GET['career'] ?? 'ISC');
        
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
                    mc.id as mc_id,
                    mc.semestre,
                    m.id as materia_id,
                    m.nombre as subject_name,
                    m.clave as subject_code,
                    mc.creditos,
                    mc.tipo,
                    m.num_parciales,
                    (SELECT COUNT(*) FROM grupos WHERE materia_id = m.id) as grupos_count
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
                    'mc_id' => (int)$row['mc_id'],
                    'materia_id' => (int)$row['materia_id'],
                    'name' => $row['subject_name'],
                    'code' => $row['subject_code'],
                    'credits' => (int)$row['creditos'],
                    'type' => $row['tipo'],
                    'parciales' => (int)$row['num_parciales'],
                    'grupos_count' => (int)$row['grupos_count']
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

    /**
     * Obtener materias disponibles que NO están en el plan de estudios
     */
    public function getAvailableSubjects(): void
    {
        header('Content-Type: application/json');
        $this->assertCsrf();
        $this->assertAdmin();
        
        $careerClave = strtoupper($_GET['career'] ?? '');
        
        if (!$careerClave) {
            http_response_code(400);
            echo json_encode(['error' => 'Career parameter required']);
            return;
        }
        
        try {
            // Get career ID
            $stmt = $this->pdo->prepare("SELECT id FROM carreras WHERE clave = :clave");
            $stmt->execute([':clave' => $careerClave]);
            $carrera = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            if (!$carrera) {
                http_response_code(404);
                echo json_encode(['error' => 'Career not found']);
                return;
            }
            
            $carreraId = (int)$carrera['id'];
            
            // Get materias NOT in this career's curriculum
            $stmt = $this->pdo->prepare("
                SELECT m.id, m.nombre, m.clave, m.creditos, m.tipo, m.num_parciales
                FROM materias m
                WHERE m.id NOT IN (
                    SELECT materia_id 
                    FROM materias_carrera 
                    WHERE carrera_id = :carrera_id
                )
                ORDER BY m.nombre
            ");
            $stmt->execute([':carrera_id' => $carreraId]);
            $materias = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            echo json_encode($materias);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Agregar una materia al plan de estudios de una carrera
     */
    public function addSubjectToCurriculum(): void
    {
        header('Content-Type: application/json');
        $this->assertCsrf();
        $this->assertAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $carreraId = (int)($data['carrera_id'] ?? 0);
        $materiaId = (int)($data['materia_id'] ?? 0);
        $semestre = (int)($data['semestre'] ?? 1);
        $creditos = (int)($data['creditos'] ?? 5);
        $tipo = $data['tipo'] ?? 'Básica';
        
        if (!$carreraId || !$materiaId || !$semestre) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }
        
        try {
            // Check if already exists
            $check = $this->pdo->prepare("
                SELECT COUNT(*) FROM materias_carrera 
                WHERE carrera_id = :cid AND materia_id = :mid
            ");
            $check->execute([':cid' => $carreraId, ':mid' => $materiaId]);
            
            if ($check->fetchColumn() > 0) {
                http_response_code(409);
                echo json_encode(['error' => 'Subject already in curriculum']);
                return;
            }
            
            // Insert
            $stmt = $this->pdo->prepare("
                INSERT INTO materias_carrera (materia_id, carrera_id, semestre, tipo, creditos)
                VALUES (:mid, :cid, :sem, :tipo, :cred)
            ");
            $stmt->execute([
                ':mid' => $materiaId,
                ':cid' => $carreraId,
                ':sem' => $semestre,
                ':tipo' => $tipo,
                ':cred' => $creditos
            ]);
            
            echo json_encode(['success' => true, 'message' => 'Subject added to curriculum']);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Actualizar una materia en el plan de estudios (semestre, créditos, tipo, parciales)
     */
    public function updateSubjectInCurriculum(): void
    {
        header('Content-Type: application/json');
        $this->assertCsrf();
        $this->assertAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $mcId = (int)($data['mc_id'] ?? 0);  // materias_carrera.id
        $materiaId = (int)($data['materia_id'] ?? 0);
        $semestre = (int)($data['semestre'] ?? 0);
        $creditos = (int)($data['creditos'] ?? 0);
        $tipo = $data['tipo'] ?? '';
        $numParciales = (int)($data['num_parciales'] ?? 2);
        
        if (!$mcId || !$semestre) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required parameters']);
            return;
        }
        
        try {
            $this->pdo->beginTransaction();
            
            // Update materias_carrera (semestre, creditos, tipo)
            $stmt = $this->pdo->prepare("
                UPDATE materias_carrera 
                SET semestre = :sem, creditos = :cred, tipo = :tipo
                WHERE id = :id
            ");
            $stmt->execute([
                ':sem' => $semestre,
                ':cred' => $creditos,
                ':tipo' => $tipo,
                ':id' => $mcId
            ]);
            
            // Update materias (num_parciales) - affects all careers
            if ($materiaId) {
                $stmt2 = $this->pdo->prepare("
                    UPDATE materias
                    SET num_parciales = :parc, creditos = :cred, tipo = :tipo
                    WHERE id = :id
                ");
                $stmt2->execute([
                    ':parc' => $numParciales,
                    ':cred' => $creditos,
                    ':tipo' => $tipo,
                    ':id' => $materiaId
                ]);
            }
            
            $this->pdo->commit();
            echo json_encode(['success' => true, 'message' => 'Subject updated']);
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Eliminar una materia del plan de estudios
     */
    public function removeSubjectFromCurriculum(): void
    {
        header('Content-Type: application/json');
        $this->assertCsrf();
        $this->assertAdmin();
        
        $data = json_decode(file_get_contents('php://input'), true);
        
        $mcId = (int)($data['mc_id'] ?? 0);
        
        if (!$mcId) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing mc_id parameter']);
            return;
        }
        
        try {
            $stmt = $this->pdo->prepare("DELETE FROM materias_carrera WHERE id = :id");
            $stmt->execute([':id' => $mcId]);
            
            echo json_encode(['success' => true, 'message' => 'Subject removed from curriculum']);
        } catch (\PDOException $e) {
            http_response_code(500);
            echo json_encode(['error' => 'Database error', 'message' => $e->getMessage()]);
        }
    }

    private function assertCsrf(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $token = $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? null;
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$token && $input) {
            $token = $input['csrf_token'] ?? null;
        }
        
        if (!$token || !isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            http_response_code(403);
            echo json_encode(['error' => 'Invalid CSRF token']);
            exit;
        }
    }

    private function assertAdmin(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        if (!isset($_SESSION['user']) || ($_SESSION['user']['rol'] ?? '') !== 'admin') {
            http_response_code(403);
            echo json_encode(['error' => 'Admin access required']);
            exit;
        }
    }
}
