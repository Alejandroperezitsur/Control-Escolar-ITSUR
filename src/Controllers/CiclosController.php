<?php
namespace App\Controllers;

use PDO;
use App\Http\Request;

class CiclosController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') {
            http_response_code(403);
            return 'No autorizado';
        }
        $stmt = $this->pdo->query('SELECT id, nombre, fecha_inicio, fecha_fin, activo, created_at, updated_at FROM ciclos_escolares ORDER BY fecha_inicio DESC, nombre DESC');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ob_start();
        echo '<div class="container py-4">';
        echo '<h1 class="h3 mb-3">Ciclos escolares</h1>';
        echo '<a href="/ciclos/create" class="btn btn-sm btn-primary mb-3">Nuevo ciclo</a>';
        if (!$rows) {
            echo '<p>No hay ciclos registrados.</p>';
        } else {
            echo '<table class="table table-sm table-striped"><thead><tr>';
            echo '<th>Nombre</th><th>Inicio</th><th>Fin</th><th>Activo</th><th>Acciones</th>';
            echo '</tr></thead><tbody>';
            foreach ($rows as $c) {
                $id = (int)$c['id'];
                $activo = (int)$c['activo'] === 1;
                echo '<tr>';
                echo '<td>' . htmlspecialchars((string)$c['nombre']) . '</td>';
                echo '<td>' . htmlspecialchars((string)$c['fecha_inicio']) . '</td>';
                echo '<td>' . htmlspecialchars((string)$c['fecha_fin']) . '</td>';
                echo '<td>' . ($activo ? '<span class="badge bg-success">Activo</span>' : '<span class="badge bg-secondary">Inactivo</span>') . '</td>';
                echo '<td>';
                if (!$activo) {
                    echo '<form method="post" action="/ciclos/activar" class="d-inline">';
                    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) . '">';
                    echo '<input type="hidden" name="id" value="' . $id . '">';
                    echo '<button type="submit" class="btn btn-sm btn-outline-success">Activar</button>';
                    echo '</form> ';
                } else {
                    echo '<form method="post" action="/ciclos/cerrar" class="d-inline">';
                    echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) . '">';
                    echo '<input type="hidden" name="id" value="' . $id . '">';
                    echo '<button type="submit" class="btn btn-sm btn-outline-danger">Cerrar</button>';
                    echo '</form> ';
                }
                echo '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</div>';
        return ob_get_clean();
    }

    public function create(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') {
            http_response_code(403);
            return 'No autorizado';
        }
        ob_start();
        echo '<div class="container py-4">';
        echo '<h1 class="h3 mb-3">Nuevo ciclo escolar</h1>';
        echo '<form method="post" action="/ciclos/store" class="row g-3">';
        echo '<input type="hidden" name="csrf_token" value="' . htmlspecialchars((string)($_SESSION['csrf_token'] ?? '')) . '">';
        echo '<div class="col-md-4"><label class="form-label">Nombre</label><input type="text" name="nombre" class="form-control" required></div>';
        echo '<div class="col-md-4"><label class="form-label">Fecha inicio</label><input type="date" name="fecha_inicio" class="form-control" required></div>';
        echo '<div class="col-md-4"><label class="form-label">Fecha fin</label><input type="date" name="fecha_fin" class="form-control" required></div>';
        echo '<div class="col-12"><button type="submit" class="btn btn-primary">Guardar</button> ';
        echo '<a href="/ciclos" class="btn btn-secondary">Cancelar</a></div>';
        echo '</form></div>';
        return ob_get_clean();
    }

    public function store(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') {
            http_response_code(403);
            return 'No autorizado';
        }
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            return 'Método no permitido';
        }
        $nombreRaw = Request::postString('nombre', '');
        $fiRaw = Request::postString('fecha_inicio', '');
        $ffRaw = Request::postString('fecha_fin', '');
        $nombre = trim((string)$nombreRaw);
        $fi = trim((string)$fiRaw);
        $ff = trim((string)$ffRaw);
        if ($nombre === '' || $fi === '' || $ff === '') {
            http_response_code(400);
            return 'Datos incompletos';
        }
        if ($fi > $ff) {
            http_response_code(400);
            return 'Rango de fechas inválido';
        }
        $stmt = $this->pdo->prepare('INSERT INTO ciclos_escolares (nombre, fecha_inicio, fecha_fin, activo) VALUES (:n,:fi,:ff,0)');
        $ok = $stmt->execute([':n' => $nombre, ':fi' => $fi, ':ff' => $ff]);
        if (!$ok) {
            http_response_code(500);
            return 'Error al guardar ciclo';
        }
        header('Location: /ciclos');
        return '';
    }

    public function activar(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') {
            http_response_code(403);
            return 'No autorizado';
        }
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            return 'Método no permitido';
        }
        $id = Request::postInt('id', 0) ?? 0;
        if ($id <= 0) {
            http_response_code(400);
            return 'Ciclo inválido';
        }
        $this->pdo->beginTransaction();
        try {
            $check = $this->pdo->prepare('SELECT id FROM ciclos_escolares WHERE id = :id');
            $check->execute([':id' => $id]);
            if (!$check->fetchColumn()) {
                $this->pdo->rollBack();
                http_response_code(404);
                return 'Ciclo no encontrado';
            }
            $this->pdo->exec('UPDATE ciclos_escolares SET activo = 0');
            $upd = $this->pdo->prepare('UPDATE ciclos_escolares SET activo = 1 WHERE id = :id');
            $upd->execute([':id' => $id]);
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            http_response_code(500);
            return 'Error al activar ciclo';
        }
        header('Location: /ciclos');
        return '';
    }

    public function cerrar(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') {
            http_response_code(403);
            return 'No autorizado';
        }
        if (strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
            http_response_code(405);
            return 'Método no permitido';
        }
        $id = Request::postInt('id', 0) ?? 0;
        if ($id <= 0) {
            http_response_code(400);
            return 'Ciclo inválido';
        }
        $stmt = $this->pdo->prepare('UPDATE ciclos_escolares SET activo = 0 WHERE id = :id');
        $stmt->execute([':id' => $id]);
        header('Location: /ciclos');
        return '';
    }
}

