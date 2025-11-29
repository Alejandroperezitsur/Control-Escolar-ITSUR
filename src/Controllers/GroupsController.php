<?php
namespace App\Controllers;

use App\Services\GroupsService;

class GroupsController
{
    private GroupsService $service;
    private \PDO $pdo;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->service = new GroupsService($pdo);
    }

    // Lista de grupos (index)
    public function index(): void
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $groups = $this->service->count() ? $this->listAll() : [];
        include __DIR__ . '/../Views/groups/index.php';
    }

    // Asignar, quitar o cambiar profesor de un grupo
    public function updateProfessor(): void
    {
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $this->assertCsrf();
        $grupo_id = filter_input(INPUT_POST, 'grupo_id', FILTER_VALIDATE_INT);
        $profesor_id = filter_input(INPUT_POST, 'profesor_id', FILTER_VALIDATE_INT);
        if (!$grupo_id) { $_SESSION['flash'] = 'Grupo inválido'; $_SESSION['flash_type'] = 'danger'; header('Location: /groups'); return; }
        // Permitir quitar profesor (profesor_id puede ser null o 0)
        $sql = 'UPDATE grupos SET profesor_id = :pid WHERE id = :gid';
        $stmt = $this->pdo->prepare($sql);
        $ok = $stmt->execute([':pid' => $profesor_id ?: null, ':gid' => $grupo_id]);
        $_SESSION['flash'] = $ok ? 'Profesor actualizado en el grupo' : 'Error al actualizar profesor';
        $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
        header('Location: /groups');
    }

    public function mine(): string
    {
        $pid = (int)($_SESSION['user_id'] ?? 0);
        $stmt = $this->pdo->prepare("SELECT g.ciclo, m.nombre AS materia, g.nombre FROM grupos g JOIN materias m ON m.id = g.materia_id WHERE g.profesor_id = :p ORDER BY g.ciclo DESC, m.nombre, g.nombre");
        $stmt->execute([':p' => $pid]);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        ob_start();
        include __DIR__ . '/../Views/professor/groups.php';
        return ob_get_clean();
    }

    public function seedDemo(): string
    {
        $cycles = ['2024A','2024B'];
        $profs = $this->pdo->query("SELECT id, matricula FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchAll(\PDO::FETCH_ASSOC);
        $mats = $this->pdo->query("SELECT id, clave FROM materias")->fetchAll(\PDO::FETCH_ASSOC);
        if (!$profs || !$mats) { header('Content-Type: application/json'); return json_encode(['ok'=>false,'message'=>'Faltan profesores o materias']); }
        $sel = $this->pdo->prepare('SELECT id FROM grupos WHERE materia_id = :m AND profesor_id = :p AND nombre = :n AND ciclo <=> :c');
        $ins = $this->pdo->prepare('INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo) VALUES (:m,:p,:n,:c)');
        $created = 0;
        foreach ($profs as $prof) {
            $count = 7;
            $indices = array_rand($mats, min($count, count($mats)));
            $indices = is_array($indices) ? $indices : [$indices];
            $k = 1;
            foreach ($indices as $idx) {
                $m = $mats[$idx];
                $c = $cycles[($k - 1) % count($cycles)];
                $name = $m['clave'] . '-G' . str_pad((string)$k, 2, '0', STR_PAD_LEFT);
                $sel->execute([':m' => (int)$m['id'], ':p' => (int)$prof['id'], ':n' => $name, ':c' => $c]);
                $gid = $sel->fetchColumn();
                if (!$gid) { $ins->execute([':m' => (int)$m['id'], ':p' => (int)$prof['id'], ':n' => $name, ':c' => $c]); $created++; }
                $k++; if ($k > $count) { break; }
            }
        }
        header('Content-Type: application/json');
        return json_encode(['ok'=>true,'created'=>$created]);
    }

    private function listAll(): array
    {
        $sql = "SELECT g.id, g.nombre, g.ciclo, g.cupo, m.nombre AS materia, m.id AS materia_id, u.nombre AS profesor, u.id AS profesor_id
                FROM grupos g
                JOIN materias m ON m.id = g.materia_id
                LEFT JOIN usuarios u ON u.id = g.profesor_id
                ORDER BY g.ciclo DESC, m.nombre, g.nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
            $this->assertCsrf();
            $ok = $this->service->create($_POST);
            \App\Utils\Logger::info('group_create', [
                'materia_id' => (int)($_POST['materia_id'] ?? 0),
                'profesor_id' => (int)($_POST['profesor_id'] ?? 0),
                'nombre' => (string)($_POST['nombre'] ?? ''),
                'ciclo' => (string)($_POST['ciclo'] ?? ''),
            ]);
            $_SESSION['flash'] = $ok ? 'Grupo creado' : ('Error al crear grupo: ' . ($this->service->getLastError() ?? 'validación fallida'));
            $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
            header('Location: /groups');
            return;
        }
        include __DIR__ . '/../Views/groups/create.php';
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
            $this->assertCsrf();
            $id = (int)($_POST['id'] ?? 0);
            $ok = $this->service->update($id, $_POST);
            \App\Utils\Logger::info('group_update', ['id' => $id]);
            $_SESSION['flash'] = $ok ? 'Grupo actualizado' : ('Error al actualizar grupo: ' . ($this->service->getLastError() ?? 'validación fallida'));
            $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
            header('Location: /groups');
            return;
        }
        header('Location: /groups');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
            $this->assertCsrf();
            $id = (int)($_POST['id'] ?? 0);
            $ok = $this->service->delete($id);
            \App\Utils\Logger::info('group_delete', ['id' => $id]);
            $_SESSION['flash'] = $ok ? 'Grupo eliminado' : 'Error al eliminar grupo';
            $_SESSION['flash_type'] = $ok ? 'warning' : 'danger';
            http_response_code(403);
            exit('CSRF inválido');
        }
    }
}
