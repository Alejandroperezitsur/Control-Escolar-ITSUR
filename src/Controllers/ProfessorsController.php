<?php
namespace App\Controllers;

use PDO;
use App\Http\Request;

class ProfessorsController
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $page = Request::getInt('page', 1) ?? 1;
        $limit = 20;
        $q = Request::getString('q', '') ?? '';
        $status = Request::getString('status', '') ?? '';
        $conds = ["rol = 'profesor'"]; $params = [];
        if ($q !== '') { $conds[] = '(nombre LIKE :q1 OR email LIKE :q2)'; $params[':q1'] = '%' . $q . '%'; $params[':q2'] = '%' . $q . '%'; }
        if ($status === 'active') { $conds[] = 'activo = 1'; }
        elseif ($status === 'inactive') { $conds[] = 'activo = 0'; }
        $where = $conds ? ('WHERE ' . implode(' AND ', $conds)) : '';
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuarios $where");
        foreach ($params as $k=>$v) { $countStmt->bindValue($k, $v); }
        $countStmt->execute();
        $total = (int)($countStmt->fetchColumn() ?: 0);
        $totalPages = max(1, (int)ceil($total / $limit));
        $page = min(max(1, $page), $totalPages);
        $offset = max(0, ($page - 1) * $limit);
        $allowedSorts = ['id','nombre','email','activo'];
        $sortRaw = Request::getString('sort', 'nombre') ?? 'nombre';
        $orderRaw = Request::getString('order', 'ASC') ?? 'ASC';
        $sort = strtolower($sortRaw);
        $order = strtoupper($orderRaw);
        if (!in_array($sort, $allowedSorts, true)) { $sort = 'nombre'; }
        if (!in_array($order, ['ASC','DESC'], true)) { $order = 'ASC'; }
        $sql = "SELECT id, nombre, email, activo FROM usuarios $where ORDER BY $sort $order LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $professors = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $pagination = ['page'=>$page,'pages'=>$totalPages,'total'=>$total,'limit'=>$limit,'q'=>$q,'status'=>$status,'sort'=>$sort,'order'=>$order];
        include __DIR__ . '/../Views/professors/index.php';
    }

    public function show(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); return 'No autorizado'; }
        $id = Request::getInt('id', 0) ?? 0;
        if ($id <= 0) { http_response_code(400); return 'ID inválido'; }
        $stmt = $this->pdo->prepare('SELECT id, nombre, email, matricula, activo FROM usuarios WHERE id = :id AND rol = "profesor"');
        $stmt->execute([':id' => $id]);
        $profesor = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$profesor) { http_response_code(404); return 'Profesor no encontrado'; }
        $svc = new \App\Services\GroupsService($this->pdo);
        $grupos = $svc->activeByTeacher($id);
        $cyclesStmt = $this->pdo->query('SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC');
        $ciclos = array_map(fn($x) => (string)$x['ciclo'], $cyclesStmt->fetchAll(PDO::FETCH_ASSOC));
        ob_start();
        include __DIR__ . '/../Views/professors/show.php';
        return ob_get_clean();
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $token = Request::postString('csrf_token', '') ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }

        $nombre = Request::postString('nombre', '') ?? '';
        $emailRaw = Request::postString('email', '') ?? '';
        $email = $emailRaw !== '' ? filter_var($emailRaw, FILTER_VALIDATE_EMAIL) : false;
        if ($nombre === '' || !$email) { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /professors'); return; }

        // Validación de unicidad de email
        $sel = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE rol = 'profesor' AND email = :e LIMIT 1");
        $sel->execute([':e' => $email]);
        if ($sel->fetchColumn()) {
            $_SESSION['flash'] = 'El email ya está registrado para otro profesor';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /professors');
            return;
        }

        // Crear profesor con contraseña temporal segura
        $password = bin2hex(random_bytes(8));
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (nombre, email, password, rol, activo) VALUES (:n, :e, :p, 'profesor', 1)");
        $stmt->execute([':n' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'), ':e' => $email, ':p' => $hash]);
        \App\Utils\Logger::info('professor_create', ['email' => $email]);
        $_SESSION['flash'] = 'Profesor creado. Contraseña temporal enviada al administrador.';
        $_SESSION['flash_type'] = 'success';
        header('Location: /professors');
        return;
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $token = Request::postString('csrf_token', '') ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) { $_SESSION['flash'] = 'ID inválido'; $_SESSION['flash_type'] = 'danger'; header('Location: /professors'); return; }
        $stmt = $this->pdo->prepare("DELETE FROM usuarios WHERE id = :id AND rol = 'profesor'");
        $stmt->execute([':id' => $id]);
        \App\Utils\Logger::info('professor_delete', ['id' => $id]);
        $_SESSION['flash'] = 'Profesor eliminado';
        $_SESSION['flash_type'] = 'warning';
        header('Location: /professors');
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $token = Request::postString('csrf_token', '') ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) { $_SESSION['flash'] = 'ID inválido'; $_SESSION['flash_type'] = 'danger'; header('Location: /professors'); return; }
        $nombre = Request::postString('nombre', '') ?? '';
        $emailRaw = Request::postString('email', '') ?? '';
        $email = $emailRaw !== '' ? filter_var($emailRaw, FILTER_VALIDATE_EMAIL) : false;
        $activo = Request::hasPost('activo') ? 1 : 0;
        if ($nombre === '' || !$email) { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /professors'); return; }
        $sel = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE rol = 'profesor' AND email = :e AND id <> :id LIMIT 1");
        $sel->execute([':e' => $email, ':id' => $id]);
        if ($sel->fetchColumn()) {
            $_SESSION['flash'] = 'El email ya está registrado para otro profesor';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /professors');
            return;
        }
        $stmt = $this->pdo->prepare("UPDATE usuarios SET nombre = :n, email = :e, activo = :a WHERE id = :id AND rol = 'profesor'");
        $ok = $stmt->execute([':n' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'), ':e' => $email, ':a' => $activo, ':id' => $id]);
        \App\Utils\Logger::info('professor_update', ['id' => $id]);
        $_SESSION['flash'] = $ok ? 'Profesor actualizado' : 'Error al actualizar';
        $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
        header('Location: /professors');
    }
}
