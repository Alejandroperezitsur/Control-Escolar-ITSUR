<?php
namespace App\Controllers;

use App\Services\SubjectsService;
use PDO;

class SubjectsController
{
    private SubjectsService $service;
    public function __construct(PDO $pdo)
    {
        $this->service = new SubjectsService($pdo);
    }

    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $q = trim((string)($_GET['q'] ?? ''));
        $limit = 20;
        $allowedSorts = ['id','nombre','clave'];
        $sort = strtolower(trim((string)($_GET['sort'] ?? 'nombre')));
        $order = strtoupper(trim((string)($_GET['order'] ?? 'ASC')));
        if (!in_array($sort, $allowedSorts, true)) { $sort = 'nombre'; }
        if (!in_array($order, ['ASC','DESC'], true)) { $order = 'ASC'; }
        $total = $this->service->countSearch($q);
        $totalPages = max(1, (int)ceil($total / $limit));
        $page = min(max(1, $page), $totalPages);
        $subjects = $this->service->allSearch($page, $limit, $q, $sort, $order);
        $pagination = ['page' => $page, 'total' => $total, 'pages' => $totalPages, 'limit' => $limit, 'q' => $q, 'sort'=>$sort, 'order'=>$order];
        include __DIR__ . '/../Views/subjects/index.php';
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $ok = $this->service->create($_POST);
            \App\Utils\Logger::info('subject_create', ['nombre' => (string)($_POST['nombre'] ?? ''), 'clave' => (string)($_POST['clave'] ?? '')]);
            $_SESSION['flash'] = $ok ? 'Materia creada' : ($this->service->getLastError() ?: 'Error al crear materia');
            $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
            header('Location: /subjects');
            return;
        }
        include __DIR__ . '/../Views/subjects/create.php';
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $id = (int)($_POST['id'] ?? 0);
            $ok = $this->service->update($id, $_POST);
            \App\Utils\Logger::info('subject_update', ['id' => $id]);
            $_SESSION['flash'] = $ok ? 'Materia actualizada' : ($this->service->getLastError() ?: 'Error al actualizar materia');
            $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
            header('Location: /subjects');
            return;
        }
        header('Location: /subjects');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->assertCsrf();
            $id = (int)($_POST['id'] ?? 0);
            $ok = $this->service->delete($id);
            \App\Utils\Logger::info('subject_delete', ['id' => $id]);
            $_SESSION['flash'] = $ok ? 'Materia eliminada' : 'Error al eliminar materia';
            $_SESSION['flash_type'] = $ok ? 'warning' : 'danger';
            header('Location: /subjects');
            return;
        }
        header('Location: /subjects');
    }

    private function assertCsrf(): void
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            exit('CSRF inválido');
        }
    }
}
