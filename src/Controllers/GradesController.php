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
        // try { $this->pdo->exec("ALTER TABLE calificaciones ADD COLUMN promedio DECIMAL(5,2) NULL AFTER final"); } catch (\Throwable $e) {}
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

            $prom = ($fin !== null) ? (float)$fin : (($p1 !== null && $p2 !== null) ? round(($p1 + $p2) / 2, 2) : null);
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

        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') {
            http_response_code(403);
            return 'No autorizado';
        }

        $alumnoId = isset($_POST['alumno_id']) ? filter_var($_POST['alumno_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
        $grupoId = isset($_POST['grupo_id']) ? filter_var($_POST['grupo_id'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) : null;
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

        // Validar alumno activo
        $chkAlumno = $this->pdo->prepare('SELECT 1 FROM alumnos WHERE id = :id AND activo = 1');
        $chkAlumno->execute([':id' => $alumnoId]);
        if (!$chkAlumno->fetchColumn()) {
            http_response_code(400);
            $_SESSION['flash'] = 'Alumno no existe o está inactivo';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /grades');
            return '';
        }

        // Validar grupo existente y pertenencia del profesor si aplica
        $grpStmt = $this->pdo->prepare('SELECT profesor_id FROM grupos WHERE id = :id');
        $grpStmt->execute([':id' => $grupoId]);
        $grpRow = $grpStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$grpRow) {
            http_response_code(400);
            $_SESSION['flash'] = 'Grupo inválido';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /grades');
            return '';
        }
        if ($role === 'profesor') {
            $pid = (int)($_SESSION['user_id'] ?? 0);
            if ((int)$grpRow['profesor_id'] !== $pid) {
                http_response_code(403);
                $_SESSION['flash'] = 'No autorizado para este grupo';
                $_SESSION['flash_type'] = 'danger';
                header('Location: /grades');
                return '';
            }
        }

        // Upsert con control de permisos: profesor solo actualiza existentes; admin puede insertar
        // try { $this->pdo->exec("ALTER TABLE calificaciones ADD COLUMN promedio DECIMAL(5,2) NULL AFTER final"); } catch (\Throwable $e) {}
        $stmt = $this->pdo->prepare('SELECT id FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g');
        $stmt->execute([':a' => $alumnoId, ':g' => $grupoId]);
        $existingId = $stmt->fetchColumn();

        $prom = ($fin !== null) ? (float)$fin : (($p1 !== null && $p2 !== null) ? round(($p1 + $p2) / 2, 2) : null);
        $message = 'Calificación registrada correctamente';
        if ($existingId) {
            $prevStmt = $this->pdo->prepare('SELECT parcial1, parcial2, final, promedio FROM calificaciones WHERE id = :id');
            $prevStmt->execute([':id' => (int)$existingId]);
            $prev = $prevStmt->fetch(PDO::FETCH_ASSOC);
            $prevP1 = ($prev['parcial1'] !== null ? (int)$prev['parcial1'] : null);
            $prevP2 = ($prev['parcial2'] !== null ? (int)$prev['parcial2'] : null);
            $prevFin = ($prev['final'] !== null ? (int)$prev['final'] : null);
            $prevProm = ($prev['promedio'] !== null ? (float)$prev['promedio'] : null);
            $noChange = ($prevP1 === $p1) && ($prevP2 === $p2) && ($prevFin === $fin) && ($prevProm === $prom);
            if ($noChange) {
                $message = 'Sin cambios';
            } else {
                $upd = $this->pdo->prepare('UPDATE calificaciones SET parcial1 = :p1, parcial2 = :p2, final = :fin WHERE id = :id');
                $upd->execute([':p1' => $p1, ':p2' => $p2, ':fin' => $fin, ':id' => (int)$existingId]);
            }
        } else {
            if ($role !== 'admin') {
                http_response_code(403);
                $_SESSION['flash'] = 'Solo un administrador puede inscribir alumnos al grupo';
                $_SESSION['flash_type'] = 'danger';
                header('Location: /grades');
                return '';
            }
            $ins = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a, :g, :p1, :p2, :fin)');
            $ins->execute([':a' => $alumnoId, ':g' => $grupoId, ':p1' => $p1, ':p2' => $p2, ':fin' => $fin]);
        }

        try {
            \App\Utils\Logger::info('grade_upsert', ['alumno_id' => $alumnoId, 'grupo_id' => $grupoId]);
        } catch (\Throwable $e) {
            // Logger may not be available in all contexts
        }
        $redirect = $_POST['redirect_to'] ?? '/grades';
        $redirect = is_string($redirect) ? $redirect : '/grades';
        if (!preg_match('#^/[a-zA-Z0-9/_?=&%-]+$#', $redirect)) { $redirect = '/grades'; }
        if ($message === 'Calificación registrada correctamente') {
            if (preg_match('#^/grades\?grupo_id=[^&]+&alumno_id=[^&]+#', $redirect)) { $message = 'Calificación registrada, avanzando al siguiente alumno'; }
            elseif (preg_match('#^/grades/group\?grupo_id=#', $redirect)) { $message = 'Calificación registrada, regresando al grupo'; }
        }
        $_SESSION['flash'] = $message;
        $_SESSION['flash_type'] = ($message === 'Sin cambios') ? 'warning' : 'success';
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
        // Para el modal de asignar/cambiar profesor:
        $profesores = [];
        $grupo = $grp;
        if ($role === 'admin') {
            $stmtProf = $this->pdo->query("SELECT id, COALESCE(NULLIF(nombre,''), SUBSTRING_INDEX(email,'@',1)) AS nombre FROM usuarios WHERE rol = 'profesor' AND activo = 1 ORDER BY nombre");
            $profesores = $stmtProf->fetchAll(PDO::FETCH_ASSOC);
        }
        ob_start();
        include __DIR__ . '/../Views/grades/group.php';
        return ob_get_clean();
    }

    public function exportGroupCsv(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') { http_response_code(403); return 'No autorizado'; }
        $grupoId = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;
        if ($grupoId <= 0) { http_response_code(400); return 'Grupo inválido'; }
        $estado = strtolower(trim((string)($_GET['estado'] ?? '')));
        $stmt = $this->pdo->prepare('SELECT g.id, g.nombre, g.ciclo, m.nombre AS materia, u.nombre AS profesor, g.profesor_id FROM grupos g JOIN materias m ON m.id = g.materia_id JOIN usuarios u ON u.id = g.profesor_id WHERE g.id = :id');
        $stmt->execute([':id' => $grupoId]);
        $grp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grp) { http_response_code(404); return 'Grupo no encontrado'; }
        if ($role === 'profesor') { $pid = (int)($_SESSION['user_id'] ?? 0); if ((int)$grp['profesor_id'] !== $pid) { http_response_code(403); return 'No autorizado para este grupo'; } }
        $rows = (new \App\Services\GroupsService($this->pdo))->studentsInGroup($grupoId);
        if ($estado === 'pendiente') {
            $rows = array_values(array_filter($rows, fn($r) => ($r['final'] ?? null) === null));
        } elseif ($estado === 'aprobado') {
            $rows = array_values(array_filter($rows, fn($r) => ($r['final'] ?? null) !== null && (float)$r['final'] >= 70));
        } elseif ($estado === 'reprobado') {
            $rows = array_values(array_filter($rows, fn($r) => ($r['final'] ?? null) !== null && (float)$r['final'] < 70));
        }
        header('Content-Type: text/csv; charset=UTF-8');
        $fname = 'grupo_' . (int)$grupoId . '_calificaciones.csv';
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, ['Matrícula','Alumno','Parcial 1','Parcial 2','Final','Promedio']);
        foreach ($rows as $r) {
            $al = trim((string)($r['nombre'] ?? '')); $ap = trim((string)($r['apellido'] ?? ''));
            fputcsv($fp, [ (string)($r['matricula'] ?? ''), trim($al . ' ' . $ap), (string)($r['parcial1'] ?? ''), (string)($r['parcial2'] ?? ''), (string)($r['final'] ?? ''), (string)($r['promedio'] ?? '') ]);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return (string)$csv;
    }

    public function exportGroupPendingCsv(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') { http_response_code(403); return 'No autorizado'; }
        $grupoId = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;
        if ($grupoId <= 0) { http_response_code(400); return 'Grupo inválido'; }
        $stmt = $this->pdo->prepare('SELECT g.id, g.nombre, g.ciclo, m.nombre AS materia, u.nombre AS profesor, g.profesor_id FROM grupos g JOIN materias m ON m.id = g.materia_id JOIN usuarios u ON u.id = g.profesor_id WHERE g.id = :id');
        $stmt->execute([':id' => $grupoId]);
        $grp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grp) { http_response_code(404); return 'Grupo no encontrado'; }
        if ($role === 'profesor') { $pid = (int)($_SESSION['user_id'] ?? 0); if ((int)$grp['profesor_id'] !== $pid) { http_response_code(403); return 'No autorizado para este grupo'; } }
        $q = $this->pdo->prepare('SELECT a.matricula, a.nombre, a.apellido FROM calificaciones c JOIN alumnos a ON a.id = c.alumno_id WHERE c.grupo_id = :gid AND c.final IS NULL ORDER BY a.apellido, a.nombre');
        $q->execute([':gid' => $grupoId]);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: text/csv; charset=UTF-8');
        $fname = 'grupo_' . (int)$grupoId . '_pendientes.csv';
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, ['Matrícula','Alumno']);
        foreach ($rows as $r) {
            $al = trim((string)($r['nombre'] ?? '')); $ap = trim((string)($r['apellido'] ?? ''));
            fputcsv($fp, [ (string)($r['matricula'] ?? ''), trim($al . ' ' . $ap) ]);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return (string)$csv;
    }

    public function exportGroupXlsx(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') { http_response_code(403); return 'No autorizado'; }
        $grupoId = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;
        if ($grupoId <= 0) { http_response_code(400); return 'Grupo inválido'; }
        $estado = strtolower(trim((string)($_GET['estado'] ?? '')));
        $stmt = $this->pdo->prepare('SELECT g.id, g.nombre, g.ciclo, m.nombre AS materia, u.nombre AS profesor, g.profesor_id FROM grupos g JOIN materias m ON m.id = g.materia_id JOIN usuarios u ON u.id = g.profesor_id WHERE g.id = :id');
        $stmt->execute([':id' => $grupoId]);
        $grp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grp) { http_response_code(404); return 'Grupo no encontrado'; }
        if ($role === 'profesor') { $pid = (int)($_SESSION['user_id'] ?? 0); if ((int)$grp['profesor_id'] !== $pid) { http_response_code(403); return 'No autorizado para este grupo'; } }
        $rowsDb = (new \App\Services\GroupsService($this->pdo))->studentsInGroup($grupoId);
        if ($estado === 'pendiente') {
            $rowsDb = array_values(array_filter($rowsDb, fn($r) => ($r['final'] ?? null) === null));
        } elseif ($estado === 'aprobado') {
            $rowsDb = array_values(array_filter($rowsDb, fn($r) => ($r['final'] ?? null) !== null && (float)$r['final'] >= 70));
        } elseif ($estado === 'reprobado') {
            $rowsDb = array_values(array_filter($rowsDb, fn($r) => ($r['final'] ?? null) !== null && (float)$r['final'] < 70));
        }
        $headers = ['Matrícula','Alumno','Parcial 1','Parcial 2','Final','Promedio'];
        $rows = [];
        foreach ($rowsDb as $r) { $al = trim((string)($r['nombre'] ?? '')); $ap = trim((string)($r['apellido'] ?? '')); $rows[] = [ (string)($r['matricula'] ?? ''), trim($al . ' ' . $ap), (string)($r['parcial1'] ?? ''), (string)($r['parcial2'] ?? ''), (string)($r['final'] ?? ''), (string)($r['promedio'] ?? '') ]; }
        if (!class_exists('ZipArchive')) { http_response_code(500); return 'ZipArchive no disponible'; }
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">' . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>' . '<Default Extension="xml" ContentType="application/xml"/>' . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>' . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>' . '</Types>';
        $relsRoot = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>' . '</Relationships>';
        $relsWorkbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">' . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>' . '<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>' . '</Relationships>';
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Calificaciones" sheetId="1" r:id="rId1"/></sheets></workbook>';
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">' . '<numFmts count="0"/>' . '<fonts count="2"><font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font><font><b/><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font></fonts>' . '<fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FFEFEFEF"/><bgColor indexed="64"/></patternFill></fill></fills>' . '<borders count="1"><border/></borders>' . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>' . '<cellXfs count="3"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/><xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/><xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/></cellXfs>' . '</styleSheet>';
        $sheetXml = $this->buildSimpleSheetXml($headers, $rows, ['freezeRows' => 1, 'numericCols' => [3,4,5,6]]);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $relsRoot);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relsWorkbook);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheetXml);
        $zip->close();
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $slug = function(string $s): string { $s = iconv('UTF-8','ASCII//TRANSLIT',$s); $s = strtolower(str_replace(' ', '_', $s)); $s = preg_replace('/[^a-z0-9_\-]/', '', $s); return $s ?: 'grupo'; };
        $fname = 'grupo_' . (int)$grupoId . '_' . $slug((string)($grp['materia'] ?? '')) . '_' . $slug((string)($grp['nombre'] ?? '')) . '.xlsx';
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        return '';
    }

    public function gradeRow(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') {
            header('Content-Type: application/json');
            http_response_code(403);
            return json_encode(['ok' => false, 'message' => 'No autorizado']);
        }
        $alumnoId = isset($_GET['alumno_id']) ? (int)$_GET['alumno_id'] : 0;
        $grupoId = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;
        if ($alumnoId <= 0 || $grupoId <= 0) {
            header('Content-Type: application/json');
            http_response_code(400);
            return json_encode(['ok' => false, 'message' => 'Parámetros inválidos']);
        }
        $stmt = $this->pdo->prepare('SELECT profesor_id FROM grupos WHERE id = :id');
        $stmt->execute([':id' => $grupoId]);
        $grp = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$grp) {
            header('Content-Type: application/json');
            http_response_code(404);
            return json_encode(['ok' => false, 'message' => 'Grupo no encontrado']);
        }
        if ($role === 'profesor') {
            $pid = (int)($_SESSION['user_id'] ?? 0);
            if ((int)$grp['profesor_id'] !== $pid) {
                header('Content-Type: application/json');
                http_response_code(403);
                return json_encode(['ok' => false, 'message' => 'No autorizado para este grupo']);
            }
        }
        $q = $this->pdo->prepare('SELECT parcial1, parcial2, final, promedio FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g');
        $q->execute([':a' => $alumnoId, ':g' => $grupoId]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        if (!$row) { return json_encode(['ok' => true, 'data' => null]); }
        return json_encode(['ok' => true, 'data' => [
            'parcial1' => isset($row['parcial1']) ? (int)$row['parcial1'] : null,
            'parcial2' => isset($row['parcial2']) ? (int)$row['parcial2'] : null,
            'final' => isset($row['final']) ? (int)$row['final'] : null,
            'promedio' => isset($row['promedio']) ? (float)$row['promedio'] : null,
        ]]);
    }

    private function buildSimpleSheetXml(array $headers, array $rows, array $options = []): string
    {
        $freezeRows = (int)($options['freezeRows'] ?? 0);
        $numericCols = (array)($options['numericCols'] ?? []);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        if ($freezeRows > 0) { $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$freezeRows.'" topLeftCell="A'.($freezeRows+1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'; }
        $xml .= '<sheetData>';
        $rowNum = 1;
        $colLabel = function(int $index): string { $label = ''; $n = $index; while ($n > 0) { $n -= 1; $label = chr(65 + ($n % 26)) . $label; $n = intdiv($n, 26); } return $label; };
        $makeRow = function(array $cells, ?int $styleOverride = null) use (&$rowNum, $numericCols, $colLabel) { $r = '<row r="'.$rowNum.'">'; for ($i=0; $i<count($cells); $i++) { $col = $colLabel($i+1); $val = (string)$cells[$i]; $normalized = str_replace([','], '', $val); $isNum = $normalized !== '' && is_numeric($normalized); $applyNumFmt = in_array($i+1, $numericCols, true); if ($isNum) { $styleAttr = ''; if ($styleOverride !== null) { $styleAttr = ' s="'.$styleOverride.'"'; } elseif ($applyNumFmt) { $styleAttr = ' s="1"'; } $r .= '<c r="'.$col.$rowNum.'" t="n"'.$styleAttr.'><v>'.htmlspecialchars((string)$normalized, ENT_QUOTES).'</v></c>'; } else { $styleAttr = ($styleOverride !== null) ? (' s="'.$styleOverride.'"') : ''; $r .= '<c r="'.$col.$rowNum.'" t="inlineStr"'.$styleAttr.'><is><t>'.htmlspecialchars($val, ENT_QUOTES).'</t></is></c>'; } } $r .= '</row>'; $rowNum++; return $r; };
        if (!empty($headers)) { $xml .= $makeRow($headers, 2); }
        foreach ($rows as $row) { $xml .= $makeRow($row); }
        $xml .= '</sheetData></worksheet>';
        return $xml;
    }
}
