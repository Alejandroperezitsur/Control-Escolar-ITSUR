<?php
namespace App\Controllers;

class PlanesController
{
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): string
    {
        $this->assertAdmin();
        
        // Fetch planes with career info
        $stmt = $this->pdo->query("
            SELECT p.*, c.nombre as carrera_nombre, c.clave as carrera_clave
            FROM planes p
            JOIN carreras c ON p.carrera_id = c.id
            ORDER BY c.nombre, p.anio DESC
        ");
        $planes = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        
        // Fetch careers for the modal/form
        $carreras = $this->pdo->query("SELECT id, nombre FROM carreras WHERE activo = 1 ORDER BY nombre")->fetchAll(\PDO::FETCH_ASSOC);

        ob_start();
        include __DIR__ . '/../Views/planes/index.php';
        return ob_get_clean();
    }

    public function store(): void
    {
        $this->assertAdmin();
        $this->assertCsrf();

        $carreraId = filter_input(INPUT_POST, 'carrera_id', FILTER_VALIDATE_INT);
        $anio = filter_input(INPUT_POST, 'anio', FILTER_VALIDATE_INT);
        $clave = trim($_POST['clave'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');

        if (!$carreraId || !$anio || !$clave) {
            $this->jsonResponse(['error' => 'Faltan datos obligatorios'], 400);
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO planes (carrera_id, anio, clave, descripcion) VALUES (:cid, :anio, :clave, :desc)");
            $stmt->execute([
                ':cid' => $carreraId,
                ':anio' => $anio,
                ':clave' => $clave,
                ':desc' => $descripcion
            ]);
            $this->jsonResponse(['success' => true, 'message' => 'Plan creado correctamente']);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Error al crear plan: ' . $e->getMessage()], 500);
        }
    }

    public function update(): void
    {
        $this->assertAdmin();
        $this->assertCsrf();

        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $carreraId = filter_input(INPUT_POST, 'carrera_id', FILTER_VALIDATE_INT);
        $anio = filter_input(INPUT_POST, 'anio', FILTER_VALIDATE_INT);
        $clave = trim($_POST['clave'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;

        if (!$id || !$carreraId || !$anio || !$clave) {
            $this->jsonResponse(['error' => 'Faltan datos obligatorios'], 400);
        }

        try {
            $stmt = $this->pdo->prepare("UPDATE planes SET carrera_id = :cid, anio = :anio, clave = :clave, descripcion = :desc, activo = :activo WHERE id = :id");
            $stmt->execute([
                ':cid' => $carreraId,
                ':anio' => $anio,
                ':clave' => $clave,
                ':desc' => $descripcion,
                ':activo' => $activo,
                ':id' => $id
            ]);
            $this->jsonResponse(['success' => true, 'message' => 'Plan actualizado correctamente']);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Error al actualizar plan: ' . $e->getMessage()], 500);
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
            $stmt = $this->pdo->prepare("DELETE FROM planes WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $this->jsonResponse(['success' => true, 'message' => 'Plan eliminado correctamente']);
        } catch (\PDOException $e) {
            $this->jsonResponse(['error' => 'Error al eliminar plan (puede tener materias asociadas)'], 500);
        }
    }

    public function get(): void
    {
        $this->assertAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse(['error' => 'ID inválido'], 400);
        }

        $stmt = $this->pdo->prepare("SELECT * FROM planes WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $plan = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($plan) {
            $this->jsonResponse($plan);
        } else {
            $this->jsonResponse(['error' => 'Plan no encontrado'], 404);
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
