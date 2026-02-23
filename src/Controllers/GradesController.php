<?php
namespace App\Controllers;

use App\Http\Request;
use App\Repositories\GradesRepository;
use App\Services\AcademicService;
use PDO;

class GradesController
{
    private PDO $pdo;
    private GradesRepository $gradesRepository;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->gradesRepository = new GradesRepository($pdo);
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
        $cycles = $this->gradesRepository->getCycles();
        $ciclo = Request::getString('ciclo');
        $rows = $this->gradesRepository->getPendingForAdmin($ciclo);

        ob_start();
        include __DIR__ . '/../Views/admin/pending.php';
        return ob_get_clean();
    }

    public function pendingForProfessor(): string
    {
        $pid = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = Request::getString('ciclo');
        $rows = $this->gradesRepository->getPendingForProfessor($pid, $ciclo);
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
        $token = Request::postString('csrf_token', '') ?? '';
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
        if (!isset($_FILES['csv']['size']) || (int)$_FILES['csv']['size'] > (5 * 1024 * 1024)) {
            http_response_code(400);
            return 'CSV demasiado grande (límite 5MB)';
        }
        set_time_limit(120);
        $fp = fopen($_FILES['csv']['tmp_name'], 'r');
        $headers = fgetcsv($fp);
        $count = 0;
        $skipped = 0;
        $processed = 0;
        $criticalError = false;
        try {
            $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $this->pdo->beginTransaction();
            while (($row = fgetcsv($fp)) !== false) {
                if ($processed >= 5000) {
                    $criticalError = true;
                    break;
                }
                [$alumnoId, $grupoId, $p1, $p2, $fin] = $row;

                $alumnoId = filter_var($alumnoId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $grupoId = filter_var($grupoId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
                $p1Val = ($p1 !== '' ? filter_var($p1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null);
                $p2Val = ($p2 !== '' ? filter_var($p2, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null);
                $finVal = ($fin !== '' ? filter_var($fin, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null);

                if (!$alumnoId || !$grupoId) { $skipped++; $processed++; continue; }
                if (($p1 !== '' && $p1Val === false) || ($p2 !== '' && $p2Val === false) || ($fin !== '' && $finVal === false)) { $skipped++; $processed++; continue; }

                try {
                    (new AcademicService($this->pdo))->assertActiveCycleForGroup($grupoId);
                } catch (\Throwable $e) {
                    $criticalError = true;
                    break;
                }

                $prom = ($finVal !== null) ? (float)$finVal : (($p1Val !== null && $p2Val !== null) ? round(($p1Val + $p2Val) / 2, 2) : null);
                $result = $this->gradesRepository->updateBulkRow($alumnoId, $grupoId, $p1Val, $p2Val, $finVal, $prom, $role, $profId);
                if ($result === 'updated') {
                    $count++;
                } elseif ($result === 'critical') {
                    $criticalError = true;
                    break;
                } else {
                    $skipped++;
                }
                $processed++;
            }
            fclose($fp);
            if ($criticalError) {
                $this->pdo->rollBack();
                http_response_code(409);
                return 'Error crítico en carga masiva (grupo o profesor inválido/ciclo no activo), no se aplicaron cambios';
            }
            $invalidRatio = $processed > 0 ? ($skipped / $processed) : 0;
            if ($invalidRatio > 0.2) {
                $this->pdo->rollBack();
                http_response_code(400);
                return 'Más del 20% de filas inválidas, se deshicieron todos los cambios';
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            fclose($fp);
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }
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
        $token = Request::postString('csrf_token', '') ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            return 'CSRF inválido';
        }

        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin' && $role !== 'profesor') {
            http_response_code(403);
            return 'No autorizado';
        }

        $alumnoId = filter_input(INPUT_POST, 'alumno_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $grupoId = filter_input(INPUT_POST, 'grupo_id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $p1Raw = Request::postString('parcial1', '') ?? '';
        $p2Raw = Request::postString('parcial2', '') ?? '';
        $finRaw = Request::postString('final', '') ?? '';
        $p1 = $p1Raw !== '' ? filter_var($p1Raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null;
        $p2 = $p2Raw !== '' ? filter_var($p2Raw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null;
        $fin = $finRaw !== '' ? filter_var($finRaw, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 100]]) : null;
        if (($p1Raw !== '' && $p1 === false) || ($p2Raw !== '' && $p2 === false) || ($finRaw !== '' && $fin === false)) {
            http_response_code(400);
            $_SESSION['flash'] = 'Las calificaciones deben estar entre 0 y 100';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /grades');
            return '';
        }

        if (!$alumnoId || !$grupoId) {
            http_response_code(400);
            $_SESSION['flash'] = 'Datos inválidos: IDs requeridos.';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /grades');
            return '';
        }

        if (!$this->gradesRepository->isActiveStudent($alumnoId)) {
            http_response_code(400);
            $_SESSION['flash'] = 'Alumno no existe o está inactivo';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /grades');
            return '';
        }

        $grpRow = $this->gradesRepository->getGroupById($grupoId);
        if (!$grpRow) {
            http_response_code(400);
            $_SESSION['flash'] = 'Grupo inválido';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /grades');
            return '';
        }
        try {
            (new AcademicService($this->pdo))->assertActiveCycleForGroup($grupoId);
        } catch (\Throwable $e) {
            http_response_code(409);
            $_SESSION['flash'] = 'No se pueden capturar calificaciones porque el ciclo no está activo para este grupo';
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

        $existing = $this->gradesRepository->getGradeByAlumnoGrupo($alumnoId, $grupoId);
        $prom = ($fin !== null) ? (float)$fin : (($p1 !== null && $p2 !== null) ? round(($p1 + $p2) / 2, 2) : null);
        $message = 'Calificación registrada correctamente';
        $oldData = null;
        $existingId = null;
        if ($existing) {
            $existingId = (int)$existing['id'];
            $oldData = [
                'parcial1' => $existing['parcial1'],
                'parcial2' => $existing['parcial2'],
                'final' => $existing['final'],
                'promedio' => $existing['promedio'],
            ];
        }
        try {
            $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
            $this->pdo->beginTransaction();
            if ($existing && $existingId !== null) {
                $prevP1 = ($existing['parcial1'] !== null ? (int)$existing['parcial1'] : null);
                $prevP2 = ($existing['parcial2'] !== null ? (int)$existing['parcial2'] : null);
                $prevFin = ($existing['final'] !== null ? (int)$existing['final'] : null);
                $prevProm = ($existing['promedio'] !== null ? (float)$existing['promedio'] : null);
                $noChange = ($prevP1 === $p1) && ($prevP2 === $p2) && ($prevFin === $fin) && ($prevProm === $prom);
                if ($noChange) {
                    $message = 'Sin cambios';
                } else {
                    $this->gradesRepository->updateGradeById($existingId, $p1, $p2, $fin, $prom);
                    $newData = [
                        'parcial1' => $p1,
                        'parcial2' => $p2,
                        'final' => $fin,
                        'promedio' => $prom,
                    ];
                    $aud = $this->pdo->prepare('INSERT INTO auditoria_academica (usuario_id, accion, entidad, entidad_id, datos_anteriores, datos_nuevos, ip) VALUES (:uid,:acc,:ent,:eid,:old,:new,:ip)');
                    $audOk = $aud->execute([
                        ':uid' => (int)($_SESSION['user_id'] ?? 0) ?: null,
                        ':acc' => 'update_grade',
                        ':ent' => 'calificacion',
                        ':eid' => $existingId,
                        ':old' => $oldData !== null ? json_encode($oldData) : null,
                        ':new' => json_encode($newData),
                        ':ip' => (string)($_SERVER['REMOTE_ADDR'] ?? ''),
                    ]);
                    if (!$audOk) {
                        $this->pdo->rollBack();
                        http_response_code(500);
                        $_SESSION['flash'] = 'Error de auditoría, no se guardó la calificación';
                        $_SESSION['flash_type'] = 'danger';
                        header('Location: /grades');
                        return '';
                    }
                    $message = 'Calificación actualizada correctamente';
                }
            } else {
                if ($role !== 'admin') {
                    $this->pdo->rollBack();
                    http_response_code(403);
                    $_SESSION['flash'] = 'Solo un administrador puede inscribir alumnos al grupo';
                    $_SESSION['flash_type'] = 'danger';
                    header('Location: /grades');
                    return '';
                }
                $this->gradesRepository->insertGrade($alumnoId, $grupoId, $p1, $p2, $fin, $prom);
            }
            $this->pdo->commit();
        } catch (\Throwable $e) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }
            throw $e;
        }

        \App\Utils\Logger::info('grade_upsert', ['alumno_id' => $alumnoId, 'grupo_id' => $grupoId]);
        $redirect = Request::postString('redirect_to', '/grades') ?? '/grades';
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
        $grupoId = Request::getInt('grupo_id', 0) ?? 0;
        if ($grupoId <= 0) { http_response_code(400); return 'Grupo inválido'; }
        $grp = $this->gradesRepository->getGroupById($grupoId);
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
        $grupoId = Request::getInt('grupo_id', 0) ?? 0;
        if ($grupoId <= 0) { http_response_code(400); return 'Grupo inválido'; }
        $estadoRaw = Request::getString('estado', '') ?? '';
        $estado = strtolower($estadoRaw);
        $grp = $this->gradesRepository->getGroupById($grupoId);
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
        $grupoId = Request::getInt('grupo_id', 0) ?? 0;
        if ($grupoId <= 0) { http_response_code(400); return 'Grupo inválido'; }
        $grp = $this->gradesRepository->getGroupById($grupoId);
        if (!$grp) { http_response_code(404); return 'Grupo no encontrado'; }
        if ($role === 'profesor') { $pid = (int)($_SESSION['user_id'] ?? 0); if ((int)$grp['profesor_id'] !== $pid) { http_response_code(403); return 'No autorizado para este grupo'; } }
        $rows = $this->gradesRepository->getPendingCsvRows($grupoId);
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
        $grupoId = Request::getInt('grupo_id', 0) ?? 0;
        if ($grupoId <= 0) { http_response_code(400); return 'Grupo inválido'; }
        $estadoRaw = Request::getString('estado', '') ?? '';
        $estado = strtolower($estadoRaw);
        $grp = $this->gradesRepository->getGroupById($grupoId);
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
        $alumnoId = Request::getInt('alumno_id', 0) ?? 0;
        $grupoId = Request::getInt('grupo_id', 0) ?? 0;
        if ($alumnoId <= 0 || $grupoId <= 0) {
            header('Content-Type: application/json');
            http_response_code(400);
            return json_encode(['ok' => false, 'message' => 'Parámetros inválidos']);
        }
        $grp = $this->gradesRepository->getGroupById($grupoId);
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
        $row = $this->gradesRepository->getGradeRow($alumnoId, $grupoId);
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
