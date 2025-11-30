<?php
namespace App\Controllers;

class AulasController
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): string
    {
        $this->assertAdmin();
        
        $stmt = $this->pdo->query("SELECT * FROM aulas ORDER BY clave");
        $aulas = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();
        include __DIR__ . '/../Views/aulas/index.php';
        return ob_get_clean();
    }

    public function store(): void
    {
        $this->assertAdmin();
        $this->assertCsrf();

        $clave = trim($_POST['clave'] ?? '');
        $capacidad = filter_input(INPUT_POST, 'capacidad', FILTER_VALIDATE_INT);
        $tipo = $_POST['tipo'] ?? 'aula';
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $recursos = trim($_POST['recursos'] ?? ''); // JSON or comma separated, let's assume text for now

        if (!$clave || !$capacidad) {
            $this->jsonResponse(['error' => 'Faltan datos obligatorios'], 400);
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO aulas (clave, capacidad, tipo, ubicacion, recursos_json) VALUES (:clave, :cap, :tipo, :ub, :rec)");
            $stmt->execute([
                ':clave' => $clave,
                ':cap' => $capacidad,
                ':tipo' => $tipo,
                ':ub' => $ubicacion,
                ':rec' => $recursos
            ]);
            $this->jsonResponse(['success' => true, 'message' => 'Aula creada correctamente']);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Error al crear aula: ' . $e->getMessage()], 500);
        }
    }

    public function update(): void
    {
        $this->assertAdmin();
        $this->assertCsrf();

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $clave = trim($_POST['clave'] ?? '');
        $capacidad = filter_input(INPUT_POST, 'capacidad', FILTER_VALIDATE_INT);
        $tipo = $_POST['tipo'] ?? 'aula';
        $ubicacion = trim($_POST['ubicacion'] ?? '');
        $recursos = trim($_POST['recursos'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;

        if (!$id || !$clave || !$capacidad) {
            $this->jsonResponse(['error' => 'Faltan datos obligatorios'], 400);
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE aulas SET clave = :clave, capacidad = :cap, tipo = :tipo, ubicacion = :ub, recursos_json = :rec, activo = :activo WHERE id = :id");
            $stmt->execute([
                ':clave' => $clave,
                ':cap' => $capacidad,
                ':tipo' => $tipo,
                ':ub' => $ubicacion,
                ':rec' => $recursos,
                ':activo' => $activo,
                ':id' => $id
            ]);
            $this->jsonResponse(['success' => true, 'message' => 'Aula actualizada correctamente']);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Error al actualizar aula: ' . $e->getMessage()], 500);
        }
    }

    public function delete(): void
    {
        $this->assertAdmin();
        $this->assertCsrf();

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse(['error' => 'ID inválido'], 400);
        }

        try {
            $stmt = $this->pdo->prepare("DELETE FROM aulas WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $this->jsonResponse(['success' => true, 'message' => 'Aula eliminada correctamente']);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Error al eliminar aula (puede estar en uso)'], 500);
        }
    }

    public function get(): void
    {
        $this->assertAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse(['error' => 'ID inválido'], 400);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM aulas WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $aula = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($aula) {
            $this->jsonResponse($aula);
        } else {
            $this->jsonResponse(['error' => 'Aula no encontrada'], 404);
        }
    }

    private function assertAdmin(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        if (($_SESSION['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo 'Acceso denegado';
            exit;
        }
    }

    private function assertCsrf(): void
    {
        if (session_status() === PHP_SESSION_NONE) session_start();
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            $this->jsonResponse(['error' => 'Token CSRF inválido'], 403);
        }
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
}
