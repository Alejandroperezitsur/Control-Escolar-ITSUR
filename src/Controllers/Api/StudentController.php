<?php
namespace App\Controllers\Api;

use PDO;
use App\Services\StudentsService;
use App\Http\Request;

class StudentController
{
    private StudentsService $service;
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->service = new StudentsService($pdo);
    }

    private function json(array $payload, int $code = 200): string
    {
        http_response_code($code);
        header('Content-Type: application/json');
        return json_encode($payload);
    }

    /**
     * GET /api/alumno/carga
     * Optional query: ciclo=YYYY-1|YYYY-2
     */
    public function carga(): string
    {
        $alumnoId = (int)($_SESSION['user_id'] ?? 0);
        $cicloRaw = Request::getString('ciclo');
        $ciclo = $cicloRaw !== null ? trim((string)$cicloRaw) : null;
        if ($ciclo !== null && $ciclo !== '' && !preg_match('/^\d{4}-(1|2)$/', $ciclo)) {
            return $this->json(['success' => false, 'error' => 'Ciclo inválido'], 400);
        }
        $rows = $this->service->getAcademicLoad($alumnoId, $ciclo ?: null);
        if ($rows === [] && $this->service->getLastError()) {
            return $this->json(['success' => false, 'error' => $this->service->getLastError()], 400);
        }
        return $this->json(['success' => true, 'data' => $rows]);
    }

    /**
     * GET /api/alumno/estadisticas
     * Optional query: ciclo=YYYY-1|YYYY-2
     */
    public function estadisticas(): string
    {
        $alumnoId = (int)($_SESSION['user_id'] ?? 0);
        $cicloRaw = Request::getString('ciclo');
        $ciclo = $cicloRaw !== null ? trim((string)$cicloRaw) : null;
        if ($ciclo !== null && $ciclo !== '' && !preg_match('/^\d{4}-(1|2)$/', $ciclo)) {
            return $this->json(['success' => false, 'error' => 'Ciclo inválido'], 400);
        }
        $res = $this->service->getGradesSummary($alumnoId, $ciclo ?: null);
        return $this->json(['success' => true, 'data' => $res]);
    }

    /**
     * GET /api/alumno/chart
     */
    public function chart(): string
    {
        $alumnoId = (int)($_SESSION['user_id'] ?? 0);
        $res = $this->service->getChartData($alumnoId);
        if ($res['labels'] === [] && $res['data'] === [] && $this->service->getLastError()) {
            return $this->json(['success' => false, 'error' => $this->service->getLastError()], 400);
        }
        return $this->json(['success' => true, 'data' => $res]);
    }

    public function perfil(): string
    {
        $id = (int)($_SESSION['user_id'] ?? 0);
        $stmt = $this->pdo->prepare('SELECT id, matricula, nombre, apellido, email FROM alumnos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
        return $this->json(['success' => true, 'data' => $row]);
    }
}
