<?php
namespace App\Controllers;

use PDO;

class GradesController
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): string
    {
        ob_start();
        $csrf = $_SESSION['csrf_token'] ?? '';
        include __DIR__ . '/../Views/grades/index.php';
        return ob_get_clean();
    }

    public function pending(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); return 'No autorizado'; }
        
        // Fetch cycles for the filter dropdown
        $cycles = $this->pdo->query("SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC")->fetchAll(PDO::FETCH_COLUMN);
        
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : null;
        $params = [];
        $where = 'WHERE c.final IS NULL';
        if ($ciclo) { $where .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        
        $sql = "SELECT c.id, c.alumno_id, c.grupo_id, a.matricula, COALESCE(NULLIF(CONCAT_WS(' ', a.nombre, a.apellido), ''), a.email, a.matricula) AS alumno, m.nombre AS materia, g.nombre AS grupo, g.ciclo, u.nombre AS profesor
                FROM calificaciones c
                JOIN alumnos a ON a.id = c.alumno_id
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                JOIN usuarios u ON u.id = g.profesor_id
                $where
                ORDER BY g.ciclo DESC, m.nombre, g.nombre, a.apellido";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        ob_start();
        include __DIR__ . '/../Views/admin/pending.php';
        return ob_get_clean();
    }

    public function pendingForProfessor(): string
    {
        $pid = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : null;
        $params = [':p' => $pid];
        $where = 'WHERE c.final IS NULL AND g.profesor_id = :p';
        if ($ciclo) { $where .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        $sql = "SELECT c.id, a.matricula, COALESCE(NULLIF(CONCAT_WS(' ', a.nombre, a.apellido), ''), a.email, a.matricula) AS alumno, m.nombre AS materia, g.nombre AS grupo, g.ciclo
                FROM calificaciones c
                JOIN alumnos a ON a.id = c.alumno_id
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                $where
                ORDER BY g.ciclo DESC, m.nombre, g.nombre, a.apellido";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) {
            $gs = $this->pdo->prepare('SELECT id FROM grupos WHERE profesor_id = :p ORDER BY ciclo DESC LIMIT 3');
            $gs->execute([':p' => $pid]);
            $gids = array_map(fn($x) => (int)$x['id'], $gs->fetchAll(PDO::FETCH_ASSOC));
            if ($gids) {
                $als = $this->pdo->query('SELECT id FROM alumnos WHERE activo = 1 ORDER BY RAND() LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
                $alIds = array_map(fn($x) => (int)$x['id'], $als);
                $ins = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a,:g,NULL,NULL,NULL)');
                $chk = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g LIMIT 1');
                foreach ($gids as $g) {
                    foreach (array_slice($alIds, 0, 2) as $a) {
                        $chk->execute([':a' => $a, ':g' => $g]);
                        if (!$chk->fetchColumn()) { $ins->execute([':a' => $a, ':g' => $g]); }
                    }
                }
                $stmt->execute();
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            }
        }
        ob_start();
        include __DIR__ . '/../Views/professor/pending.php';
        return ob_get_clean();
    }

    public function showBulkForm(): string
    {
        ob_start();
        include __DIR__ . '/../Views/grades/bulk_upload.php';
        return ob_get_clean();
    }

    public function processBulkUpload(): string
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            return 'CSRF inválido';
        }
        $role = $_SESSION['role'] ?? '';
        $profId = (int)($_SESSION['user_id'] ?? 0);
        if (!isset($_FILES['csv']) || $_FILES['csv']['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400);
            return 'Archivo CSV inválido';
        }
        $fp = fopen($_FILES['csv']['tmp_name'], 'r');
        $headers = fgetcsv($fp);
        $stmt = $this->pdo->prepare("UPDATE calificaciones SET parcial1 = :p1, parcial2 = :p2, final = :fin WHERE alumno_id = :alumno AND grupo_id = :grupo");
        $count = 0;
        $skipped = 0;
        $processed = 0;
        while (($row = fgetcsv($fp)) !== false) {
            // Espera columnas: alumno_id, grupo_id, parcial1, parcial2, final
            [$alumnoId, $grupoId, $p1, $p2, $fin] = $row;

            $alumnoId = filter_var($alumnoId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $grupoId = filter_var($grupoId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            $p1 = ($p1 !== '' ? filter_var($p1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null);
            $p2 = ($p2 !== '' ? filter_var($p2, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null);
            $fin = ($fin !== '' ? filter_var($fin, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null);

            if (!$alumnoId || !$grupoId) { $skipped++; $processed++; continue; }

            // Validar alumno activo
            $chkAlumno = $this->pdo->prepare('SELECT 1 FROM alumnos WHERE id = :id AND activo = 1');
            $chkAlumno->execute([':id' => $alumnoId]);
            if (!$chkAlumno->fetchColumn()) { $skipped++; $processed++; continue; }

            // Validar grupo existente y pertenencia del profesor (si aplica)
            $chkGrupo = $this->pdo->prepare('SELECT profesor_id FROM grupos WHERE id = :id');
            $chkGrupo->execute([':id' => $grupoId]);
            $grupoRow = $chkGrupo->fetch(PDO::FETCH_ASSOC);
            if (!$grupoRow) { $skipped++; $processed++; continue; }
            if ($role === 'profesor' && (int)$grupoRow['profesor_id'] !== $profId) { $skipped++; $processed++; continue; }

            $stmt->execute([
                ':alumno' => $alumnoId,
                ':grupo' => $grupoId,
                ':p1' => $p1,
                ':p2' => $p2,
                ':fin' => $fin,
            ]);
            $count += $stmt->rowCount();
            $processed++;
        }
        fclose($fp);
        \App\Utils\Logger::info('grades_bulk_update', ['updated' => $count, 'skipped' => $skipped, 'processed' => $processed]);
        $_SESSION['bulk_last_summary'] = ['updated' => $count, 'skipped' => $skipped, 'processed' => $processed, 'ts' => time()];
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (strpos($accept, 'application/json') !== false) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'updated' => $count, 'skipped' => $skipped, 'processed' => $processed]);
        }
        return 'Registros actualizados: ' . $count . ($skipped ? "; Filas omitidas: $skipped" : '') . "; Procesadas: $processed";
    }

    public function create(): string
    {
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            return 'CSRF inválido';
        }

        $alumnoId = filter_input(INPUT_POST, 'alumno_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $grupoId = filter_input(INPUT_POST, 'grupo_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $p1 = ($_POST['parcial1'] ?? '') !== '' ? filter_var($_POST['parcial1'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null;
        $p2 = ($_POST['parcial2'] ?? '') !== '' ? filter_var($_POST['parcial2'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null;
        $fin = ($_POST['final'] ?? '') !== '' ? filter_var($_POST['final'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null;

        if (!$alumnoId || !$grupoId) {
            http_response_code(400);
            $_SESSION['flash'] = 'Datos inválidos: IDs requeridos.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /grades');
            return '';
        }

        // Upsert
        $stmt = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g');
        $stmt->execute([':a' => $alumnoId, ':g' => $grupoId]);
        if ($stmt->fetchColumn()) {
            $upd = $this->pdo->prepare('UPDATE calificaciones SET parcial1 = :p1, parcial2 = :p2, final = :fin WHERE alumno_id = :a AND grupo_id = :g');
            $upd->execute([':p1' => $p1, ':p2' => $p2, ':fin' => $fin, ':a' => $alumnoId, ':g' => $grupoId]);
        } else {
            $ins = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a, :g, :p1, :p2, :fin)');
            $ins->execute([':a' => $alumnoId, ':g' => $grupoId, ':p1' => $p1, ':p2' => $p2, ':fin' => $fin]);
        }

        \App\Utils\Logger::info('grade_upsert', ['alumno_id' => $alumnoId, 'grupo_id' => $grupoId]);
        $_SESSION['flash'] = 'Calificación registrada correctamente';
        $_SESSION['flash_type'] = 'success';
        
        $redirect = $_POST['redirect_to'] ?? '/grades';
        header('Location: ' . $redirect);
        return '';
    }

    public function downloadBulkLog(): string
    {
        $sum = $_SESSION['bulk_last_summary'] ?? null;
        if (!$sum) { http_response_code(404); return 'No hay log disponible'; }
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="bulk_upload_log_'.date('Ymd_His', $sum['ts']).'.json' .'"');
        return json_encode($sum);
    }

    public function groupGrades(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') { http_response_code(403); return 'No autorizado'; }
        $grupoId = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;
        if ($grupoId <= 0) { http_response_code(400); return 'Grupo inválido'; }
        $stmt = $this->pdo->prepare('SELECT g.id, g.nombre, g.ciclo, m.nombre AS materia, u.nombre AS profesor, g.profesor_id FROM grupos g JOIN materias m ON m.id = g.materia_id JOIN usuarios u ON u.id = g.profesor_id WHERE g.id = :id');
        $stmt->execute([':id' => $grupoId]);
        $grp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grp) { http_response_code(404); return 'Grupo no encontrado'; }
        if ($role === 'profesor') {
            $pid = (int)($_SESSION['user_id'] ?? 0);
            if ((int)$grp['profesor_id'] !== $pid) { http_response_code(403); return 'No autorizado para este grupo'; }
        }
        $svc = new \App\Services\GroupsService($this->pdo);
        $rows = $svc->studentsInGroup($grupoId);
        ob_start();
        include __DIR__ . '/../Views/grades/group.php';
        return ob_get_clean();
    }
}
