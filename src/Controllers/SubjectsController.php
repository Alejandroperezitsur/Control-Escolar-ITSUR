<?php
namespace App\Controllers;

use PDO;

class SubjectsController
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); return 'No autorizado'; }
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $per = isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10;
        $allowedPer = [10,25,50];
        if (!in_array($per, $allowedPer, true)) { $per = 10; }
        $limit = $per;
        $offset = ($page - 1) * $limit;
        $sort = isset($_GET['sort']) ? strtolower(trim((string)$_GET['sort'])) : 'nombre';
        $order = isset($_GET['order']) ? strtoupper(trim((string)$_GET['order'])) : 'ASC';
        $allowedSorts = ['id','nombre','clave','carreras','grupos','promedio'];
        if (!in_array($sort, $allowedSorts, true)) { $sort = 'nombre'; }
        if (!in_array($order, ['ASC','DESC'], true)) { $order = 'ASC'; }
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $car = isset($_GET['carrera']) ? strtoupper(trim((string)$_GET['carrera'])) : '';
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
        $estado = isset($_GET['estado']) ? strtolower(trim((string)$_GET['estado'])) : '';
        $whereConds = [];
        $params = [];
        if ($q !== '') { $whereConds[] = '(m.nombre LIKE :q OR m.clave LIKE :q)'; $params[':q'] = '%'.$q.'%'; }
        if ($car !== '') { $whereConds[] = 'EXISTS (SELECT 1 FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id AND c.clave = :car)'; $params[':car'] = $car; }
        if ($ciclo !== '') { $whereConds[] = 'EXISTS (SELECT 1 FROM grupos g WHERE g.materia_id = m.id AND g.ciclo = :ciclo)'; $params[':ciclo'] = $ciclo; }
        if ($estado === 'sin_grupos') {
            $whereConds[] = 'NOT EXISTS (SELECT 1 FROM grupos gx WHERE gx.materia_id = m.id' . ($ciclo !== '' ? ' AND gx.ciclo = :ciclo' : '') . ')';
        } elseif ($estado === 'con_grupos') {
            $whereConds[] = 'EXISTS (SELECT 1 FROM grupos gx WHERE gx.materia_id = m.id' . ($ciclo !== '' ? ' AND gx.ciclo = :ciclo' : '') . ')';
        }
        $where = $whereConds ? ('WHERE ' . implode(' AND ', $whereConds)) : '';
        $sortCol = match ($sort) {
            'id' => 'm.id',
            'nombre' => 'm.nombre',
            'clave' => 'm.clave',
            'carreras' => 'carreras',
            'grupos' => 'grupos',
            'promedio' => 'promedio',
            default => 'm.nombre',
        };
        $sql = "SELECT m.id, m.nombre, m.clave,
                       (SELECT GROUP_CONCAT(c.clave ORDER BY c.clave SEPARATOR ', ') FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id) AS carreras,
                       (SELECT COUNT(*) FROM grupos g WHERE g.materia_id = m.id" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . ") AS grupos,
                       (SELECT ROUND(AVG(c.final),2) FROM calificaciones c JOIN grupos g2 ON g2.id = c.grupo_id WHERE g2.materia_id = m.id" . ($ciclo !== '' ? " AND g2.ciclo = :ciclo" : '') . " AND c.final IS NOT NULL) AS promedio
                FROM materias m
                $where
            ORDER BY $sortCol $order";
        // MySQL drivers may not support binding LIMIT/OFFSET named params reliably,
        // inject integers directly after casting to int for safety.
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $countSql = "SELECT COUNT(*) FROM materias m " . ($where !== '' ? $where : '');
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)($countStmt->fetchColumn() ?: 0);
        $pages = (int)max(1, ceil($total / $limit));
        $pagination = ['page'=>$page,'pages'=>$pages,'sort'=>$sort,'order'=>$order,'q'=>$q,'carrera'=>$car,'ciclo'=>$ciclo,'estado'=>$estado,'total'=>$total,'per_page'=>$per];
        $carrerasList = $this->pdo->query('SELECT clave, nombre FROM carreras ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
        $cyclesList = array_map(fn($x) => (string)$x['ciclo'], $this->pdo->query('SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC')->fetchAll(PDO::FETCH_ASSOC));
        ob_start();
        include __DIR__ . '/../Views/subjects/index.php';
        return ob_get_clean();
    }

    public function exportCsv(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); return 'No autorizado'; }
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $car = isset($_GET['carrera']) ? strtoupper(trim((string)$_GET['carrera'])) : '';
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
        $estado = isset($_GET['estado']) ? strtolower(trim((string)$_GET['estado'])) : '';
        $whereConds = [];
        $params = [];
        if ($q !== '') { $whereConds[] = '(m.nombre LIKE :q OR m.clave LIKE :q)'; $params[':q'] = '%'.$q.'%'; }
        if ($car !== '') { $whereConds[] = 'EXISTS (SELECT 1 FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id AND c.clave = :car)'; $params[':car'] = $car; }
        if ($ciclo !== '') { $whereConds[] = 'EXISTS (SELECT 1 FROM grupos g WHERE g.materia_id = m.id AND g.ciclo = :ciclo)'; $params[':ciclo'] = $ciclo; }
        if ($estado === 'sin_grupos') {
            $whereConds[] = 'NOT EXISTS (SELECT 1 FROM grupos gx WHERE gx.materia_id = m.id' . ($ciclo !== '' ? ' AND gx.ciclo = :ciclo' : '') . ')';
        } elseif ($estado === 'con_grupos') {
            $whereConds[] = 'EXISTS (SELECT 1 FROM grupos gx WHERE gx.materia_id = m.id' . ($ciclo !== '' ? ' AND gx.ciclo = :ciclo' : '') . ')';
        }
        $where = $whereConds ? ('WHERE ' . implode(' AND ', $whereConds)) : '';
        $sql = "SELECT m.id, m.nombre, m.clave,
                       (SELECT GROUP_CONCAT(c.clave ORDER BY c.clave SEPARATOR ', ') FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id) AS carreras,
                       (SELECT COUNT(*) FROM grupos g WHERE g.materia_id = m.id" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . ") AS grupos,
                       (SELECT ROUND(AVG(c.final),2) FROM calificaciones c JOIN grupos g2 ON g2.id = c.grupo_id WHERE g2.materia_id = m.id" . ($ciclo !== '' ? " AND g2.ciclo = :ciclo" : '') . " AND c.final IS NOT NULL) AS promedio
                FROM materias m $where ORDER BY m.nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="materias.csv"');
        $fp = fopen('php://temp', 'w+');
        fputcsv($fp, ['ID','Nombre','Clave','Carreras','Grupos','Promedio']);
        foreach ($rows as $r) {
            fputcsv($fp, [
                (string)($r['id'] ?? ''), (string)($r['nombre'] ?? ''), (string)($r['clave'] ?? ''),
                (string)($r['carreras'] ?? ''), (string)($r['grupos'] ?? ''), (string)($r['promedio'] ?? '')
            ]);
        }
        rewind($fp);
        $csv = stream_get_contents($fp);
        fclose($fp);
        return (string)$csv;
    }

    public function exportPdf(): void
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $car = isset($_GET['carrera']) ? strtoupper(trim((string)$_GET['carrera'])) : '';
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
        $estado = isset($_GET['estado']) ? strtolower(trim((string)$_GET['estado'])) : '';
        $whereConds = [];
        $params = [];
        if ($q !== '') { $whereConds[] = '(m.nombre LIKE :q OR m.clave LIKE :q)'; $params[':q'] = '%'.$q.'%'; }
        if ($car !== '') { $whereConds[] = 'EXISTS (SELECT 1 FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id AND c.clave = :car)'; $params[':car'] = $car; }
        if ($ciclo !== '') { $whereConds[] = 'EXISTS (SELECT 1 FROM grupos g WHERE g.materia_id = m.id AND g.ciclo = :ciclo)'; $params[':ciclo'] = $ciclo; }
        if ($estado === 'sin_grupos') { $whereConds[] = 'NOT EXISTS (SELECT 1 FROM grupos gx WHERE gx.materia_id = m.id' . ($ciclo !== '' ? ' AND gx.ciclo = :ciclo' : '') . ')'; }
        elseif ($estado === 'con_grupos') { $whereConds[] = 'EXISTS (SELECT 1 FROM grupos gx WHERE gx.materia_id = m.id' . ($ciclo !== '' ? ' AND gx.ciclo = :ciclo' : '') . ')'; }
        $where = $whereConds ? ('WHERE ' . implode(' AND ', $whereConds)) : '';
        $sql = "SELECT m.id, m.nombre, m.clave,
                       (SELECT GROUP_CONCAT(c.clave ORDER BY c.clave SEPARATOR ', ') FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id) AS carreras,
                       (SELECT COUNT(*) FROM grupos g WHERE g.materia_id = m.id" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . ") AS grupos,
                       (SELECT ROUND(AVG(c.final),2) FROM calificaciones c JOIN grupos g2 ON g2.id = c.grupo_id WHERE g2.materia_id = m.id" . ($ciclo !== '' ? " AND g2.ciclo = :ciclo" : '') . " AND c.final IS NOT NULL) AS promedio
                FROM materias m $where ORDER BY m.nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $html = '<h2>Materias</h2>';
        $html .= '<table width="100%" border="1" cellspacing="0" cellpadding="6">';
        $html .= '<thead><tr><th>ID</th><th>Nombre</th><th>Clave</th><th>Carreras</th><th>Grupos' . ($ciclo!==''?' ('.$this->escape($ciclo).')':'') . '</th><th>Promedio' . ($ciclo!==''?' ('.$this->escape($ciclo).')':'') . '</th></tr></thead><tbody>';
        foreach ($rows as $r) {
            $html .= '<tr>'
                .'<td>'.htmlspecialchars((string)($r['id'] ?? '')).'</td>'
                .'<td>'.htmlspecialchars((string)($r['nombre'] ?? '')).'</td>'
                .'<td>'.htmlspecialchars((string)($r['clave'] ?? '')).'</td>'
                .'<td>'.htmlspecialchars((string)($r['carreras'] ?? '')).'</td>'
                .'<td>'.htmlspecialchars((string)($r['grupos'] ?? '')).'</td>'
                .'<td>'.htmlspecialchars((string)($r['promedio'] ?? '')).'</td>'
                .'</tr>';
        }
        $html .= '</tbody></table>';

        if (!class_exists('Dompdf\\Dompdf')) {
            $autoload = __DIR__ . '/../../vendor/autoload.php';
            if (file_exists($autoload)) { require_once $autoload; }
        }
        if (!class_exists('Dompdf\\Dompdf')) {
            http_response_code(500);
            echo 'Dompdf no disponible. Instala con composer require dompdf/dompdf';
            return;
        }
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream('materias.pdf', ['Attachment' => false]);
    }

    private function escape(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

    public function exportXlsx(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); return 'No autorizado'; }
        $q = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
        $car = isset($_GET['carrera']) ? strtoupper(trim((string)$_GET['carrera'])) : '';
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
        $estado = isset($_GET['estado']) ? strtolower(trim((string)$_GET['estado'])) : '';
        $whereConds = [];
        $params = [];
        if ($q !== '') { $whereConds[] = '(m.nombre LIKE :q OR m.clave LIKE :q)'; $params[':q'] = '%'.$q.'%'; }
        if ($car !== '') { $whereConds[] = 'EXISTS (SELECT 1 FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id AND c.clave = :car)'; $params[':car'] = $car; }
        if ($ciclo !== '') { $whereConds[] = 'EXISTS (SELECT 1 FROM grupos g WHERE g.materia_id = m.id AND g.ciclo = :ciclo)'; $params[':ciclo'] = $ciclo; }
        if ($estado === 'sin_grupos') { $whereConds[] = 'NOT EXISTS (SELECT 1 FROM grupos gx WHERE gx.materia_id = m.id' . ($ciclo !== '' ? ' AND gx.ciclo = :ciclo' : '') . ')'; }
        elseif ($estado === 'con_grupos') { $whereConds[] = 'EXISTS (SELECT 1 FROM grupos gx WHERE gx.materia_id = m.id' . ($ciclo !== '' ? ' AND gx.ciclo = :ciclo' : '') . ')'; }
        $where = $whereConds ? ('WHERE ' . implode(' AND ', $whereConds)) : '';
        $sql = "SELECT m.id, m.nombre, m.clave,
                       (SELECT GROUP_CONCAT(c.clave ORDER BY c.clave SEPARATOR ', ') FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id) AS carreras,
                       (SELECT COUNT(*) FROM grupos g WHERE g.materia_id = m.id" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . ") AS grupos,
                       (SELECT ROUND(AVG(c.final),2) FROM calificaciones c JOIN grupos g2 ON g2.id = c.grupo_id WHERE g2.materia_id = m.id" . ($ciclo !== '' ? " AND g2.ciclo = :ciclo" : '') . " AND c.final IS NOT NULL) AS promedio
                FROM materias m $where ORDER BY m.nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $rowsDb = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $headers = ['ID','Nombre','Clave','Carreras','Grupos','Promedio'];
        $rows = array_map(function($r){ return [
            (string)($r['id'] ?? ''), (string)($r['nombre'] ?? ''), (string)($r['clave'] ?? ''),
            (string)($r['carreras'] ?? ''), (string)($r['grupos'] ?? ''), (string)($r['promedio'] ?? '')
        ]; }, $rowsDb);

        if (!class_exists('ZipArchive')) { http_response_code(500); return 'ZipArchive no disponible'; }
        $tmp = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new \ZipArchive();
        $zip->open($tmp, \ZipArchive::OVERWRITE);
        $contentTypes = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet3.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
        $relsRoot = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
        $relsWorkbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
            .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet3.xml"/>'
            .'<Relationship Id="rIdStyles" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
        $workbook = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets>'
            .'<sheet name="Materias" sheetId="1" r:id="rId1"/>'
            .'<sheet name="Promedios por ciclo" sheetId="2" r:id="rId2"/>'
            .'<sheet name="Sin grupos" sheetId="3" r:id="rId3"/>'
            .'</sheets></workbook>';
        $stylesXml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="0"/>'
            .'<fonts count="2">'
              .'<font><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
              .'<font><b/><sz val="11"/><color theme="1"/><name val="Calibri"/><family val="2"/></font>'
            .'</fonts>'
            .'<fills count="2">'
              .'<fill><patternFill patternType="none"/></fill>'
              .'<fill><patternFill patternType="solid"><fgColor rgb="FFEFEFEF"/><bgColor indexed="64"/></patternFill></fill>'
            .'</fills>'
            .'<borders count="1"><border/></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="3">'
              .'<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
              .'<xf numFmtId="4" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
              .'<xf numFmtId="0" fontId="1" fillId="1" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            .'</cellXfs>'
            .'</styleSheet>';
        $sheet1Xml = $this->buildSheetXml($headers, $rows, ['freezeRows' => 1, 'numericCols' => [5,6]]);
        $rowsProm = [];
        $pProm = [];
        $qWhere = [];
        if ($q !== '') { $qWhere[] = '(m.nombre LIKE :q OR m.clave LIKE :q)'; $pProm[':q'] = '%'.$q.'%'; }
        if ($car !== '') { $qWhere[] = 'EXISTS (SELECT 1 FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id AND c.clave = :car)'; $pProm[':car'] = $car; }
        $wProm = $qWhere ? ('WHERE ' . implode(' AND ', $qWhere)) : '';
        $sqlProm = "SELECT g.ciclo, m.nombre AS materia, ROUND(AVG(c.final),2) AS promedio, COUNT(*) AS registros
                    FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                    $wProm" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . " AND c.final IS NOT NULL
                    GROUP BY g.ciclo, m.id, m.nombre ORDER BY g.ciclo DESC, m.nombre";
        $stmtProm = $this->pdo->prepare($sqlProm);
        // Ensure ciclo param is in the array when needed, then execute with array
        if ($ciclo !== '') { $pProm[':ciclo'] = $ciclo; }
        $stmtProm->execute($pProm);
        $rowsPromDb = $stmtProm->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rowsPromDb as $r) { $rowsProm[] = [ (string)($r['ciclo'] ?? ''), (string)($r['materia'] ?? ''), (string)($r['promedio'] ?? ''), (string)($r['registros'] ?? '') ]; }
        $sheet2Xml = $this->buildSheetXml(['Ciclo','Materia','Promedio','Registros'], $rowsProm, ['freezeRows' => 1, 'numericCols' => [3,4]]);
        $rowsNoGrp = [];
        $pNo = [];
        $wNo = [];
        if ($q !== '') { $wNo[] = '(m.nombre LIKE :q OR m.clave LIKE :q)'; $pNo[':q'] = '%'.$q.'%'; }
        if ($car !== '') { $wNo[] = 'EXISTS (SELECT 1 FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id AND c.clave = :car)'; $pNo[':car'] = $car; }
        $wNoStr = $wNo ? ('WHERE ' . implode(' AND ', $wNo)) : '';
        $sqlNo = "SELECT m.id, m.nombre, m.clave FROM materias m $wNoStr AND NOT EXISTS (SELECT 1 FROM grupos g WHERE g.materia_id = m.id" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . ") ORDER BY m.nombre";
        if ($wNoStr === '') { $sqlNo = "SELECT m.id, m.nombre, m.clave FROM materias m WHERE NOT EXISTS (SELECT 1 FROM grupos g WHERE g.materia_id = m.id" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . ") ORDER BY m.nombre"; }
        $stmtNo = $this->pdo->prepare($sqlNo);
        if ($ciclo !== '') { $pNo[':ciclo'] = $ciclo; }
        $stmtNo->execute($pNo);
        $rowsNoDb = $stmtNo->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($rowsNoDb as $r) { $rowsNoGrp[] = [ (string)($r['id'] ?? ''), (string)($r['nombre'] ?? ''), (string)($r['clave'] ?? '') ]; }
        $sheet3Xml = $this->buildSheetXml(['ID','Nombre','Clave'], $rowsNoGrp, ['freezeRows' => 1]);
        $zip->addFromString('[Content_Types].xml', $contentTypes);
        $zip->addFromString('_rels/.rels', $relsRoot);
        $zip->addFromString('xl/_rels/workbook.xml.rels', $relsWorkbook);
        $zip->addFromString('xl/styles.xml', $stylesXml);
        $zip->addFromString('xl/workbook.xml', $workbook);
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet1Xml);
        $zip->addFromString('xl/worksheets/sheet2.xml', $sheet2Xml);
        $zip->addFromString('xl/worksheets/sheet3.xml', $sheet3Xml);
        $zip->close();
        $slug = function(string $s): string { $s = iconv('UTF-8','ASCII//TRANSLIT',$s); $s = strtolower(str_replace(' ', '_', $s)); $s = preg_replace('/[^a-z0-9_\-]/', '', $s); return $s ?: 'todos'; };
        $fnameParts = ['materias'];
        if ($car !== '') { $fnameParts[] = $slug($car); }
        if ($ciclo !== '') { $fnameParts[] = $slug($ciclo); }
        if ($estado !== '') { $fnameParts[] = $slug($estado); }
        $fname = implode('_', $fnameParts) . '.xlsx';
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $fname . '"');
        header('Content-Length: ' . filesize($tmp));
        readfile($tmp);
        @unlink($tmp);
        return '';
    }

    private function buildSheetXml(array $headers, array $rows, array $options = []): string
    {
        $freezeRows = (int)($options['freezeRows'] ?? 0);
        $numericCols = (array)($options['numericCols'] ?? []);
        $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">';
        if ($freezeRows > 0) { $xml .= '<sheetViews><sheetView workbookViewId="0"><pane ySplit="'.$freezeRows.'" topLeftCell="A'.($freezeRows+1).'" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'; }
        $xml .= '<sheetData>';
        $rowNum = 1;
        $colLabel = function(int $index): string {
            $label = '';
            $n = $index;
            while ($n > 0) {
                $n -= 1;
                $label = chr(65 + ($n % 26)) . $label;
                $n = intdiv($n, 26);
            }
            return $label;
        };
        $makeRow = function(array $cells, ?int $styleOverride = null) use (&$rowNum, $numericCols, $colLabel) {
            $r = '<row r="'.$rowNum.'">';
            for ($i=0; $i<count($cells); $i++) {
                $col = $colLabel($i+1);
                $val = (string)$cells[$i];
                $normalized = str_replace([','], '', $val);
                $isNum = $normalized !== '' && is_numeric($normalized);
                $applyNumFmt = in_array($i+1, $numericCols, true);
                if ($isNum) {
                    $styleAttr = '';
                    if ($styleOverride !== null) { $styleAttr = ' s="'.$styleOverride.'"'; }
                    elseif ($applyNumFmt) { $styleAttr = ' s="1"'; }
                    $r .= '<c r="'.$col.$rowNum.'" t="n"'.$styleAttr.'><v>'.htmlspecialchars((string)$normalized, ENT_QUOTES).'</v></c>';
                } else {
                    $styleAttr = ($styleOverride !== null) ? (' s="'.$styleOverride.'"') : '';
                    $r .= '<c r="'.$col.$rowNum.'" t="inlineStr"'.$styleAttr.'><is><t>'.htmlspecialchars($val, ENT_QUOTES).'</t></is></c>';
                }
            }
            $r .= '</row>';
            $rowNum++;
            return $r;
        };
        if (!empty($headers)) { $xml .= $makeRow($headers, 2); }
        foreach ($rows as $row) { $xml .= $makeRow($row); }
        $xml .= '</sheetData></worksheet>';
        return $xml;
    }

    public function show(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); return 'No autorizado'; }
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { http_response_code(400); return 'ID inválido'; }
        $stmt = $this->pdo->prepare('SELECT id, nombre, clave FROM materias WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $materia = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$materia) { http_response_code(404); return 'Materia no encontrada'; }

        $carKey = isset($_GET['carrera']) ? trim((string)$_GET['carrera']) : '';
        $creditos = null;
        $carreras = [];
        try {
            $carreras = $this->pdo->query('SELECT id, clave, nombre FROM carreras ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
            if ($carKey !== '') {
                $stCar = $this->pdo->prepare('SELECT id FROM carreras WHERE clave = :cl LIMIT 1');
                $stCar->execute([':cl' => $carKey]);
                $cid = (int)($stCar->fetchColumn() ?: 0);
                if ($cid > 0) {
                    $stCred = $this->pdo->prepare('SELECT creditos FROM materias_carrera WHERE materia_id = :mid AND carrera_id = :cid LIMIT 1');
                    $stCred->execute([':mid' => $id, ':cid' => $cid]);
                    $val = $stCred->fetchColumn();
                    if ($val !== false && $val !== null) { $creditos = (int)$val; }
                }
            }
        } catch (\Throwable $e) {}
        if ($creditos !== null) { $materia['creditos'] = $creditos; }

        $sql = "SELECT g.id, g.nombre AS grupo, g.ciclo, u.nombre AS profesor, u.id AS profesor_id,
                       COUNT(DISTINCT c.alumno_id) AS alumnos,
                       ROUND(AVG(c.promedio),2) AS promedio
                FROM grupos g
                JOIN usuarios u ON u.id = g.profesor_id
                LEFT JOIN calificaciones c ON c.grupo_id = g.id
                WHERE g.materia_id = :mid
                GROUP BY g.id, g.nombre, g.ciclo, u.nombre
                ORDER BY g.ciclo DESC, g.nombre";
        $st = $this->pdo->prepare($sql);
        $st->execute([':mid' => $id]);
        $grupos = $st->fetchAll(PDO::FETCH_ASSOC);

        $cyclesStmt = $this->pdo->query('SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC');
        $ciclos = array_map(fn($x) => (string)$x['ciclo'], $cyclesStmt->fetchAll(PDO::FETCH_ASSOC));

        // Obtener asociaciones materia <-> carrera
        $assocs = [];
        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS materias_carrera (id INT AUTO_INCREMENT PRIMARY KEY, materia_id INT NOT NULL, carrera_id INT NOT NULL, semestre TINYINT NOT NULL, tipo ENUM('basica','especialidad','residencia') DEFAULT 'basica', creditos INT DEFAULT 5, UNIQUE KEY uk_materia_carrera_semestre (materia_id, carrera_id, semestre)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {}
        try {
            $stAss = $this->pdo->prepare('SELECT mc.id, mc.carrera_id, c.clave, c.nombre AS carrera_nombre, mc.semestre, mc.creditos, mc.tipo FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = :mid ORDER BY mc.semestre');
            $stAss->execute([':mid' => $id]);
            $assocs = $stAss->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) { $assocs = []; }

        ob_start();
        $data_carreras = $carreras;
        include __DIR__ . '/../Views/subjects/show.php';
        return ob_get_clean();
    }

    public function addToCareer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $materia_id = filter_input(INPUT_POST, 'materia_id', FILTER_VALIDATE_INT);
        $carrera_id = filter_input(INPUT_POST, 'carrera_id', FILTER_VALIDATE_INT);
        $semestre = filter_input(INPUT_POST, 'semestre', FILTER_VALIDATE_INT);
        $creditos = filter_input(INPUT_POST, 'creditos', FILTER_VALIDATE_INT);
        $tipo = in_array($_POST['tipo'] ?? 'basica', ['basica','especialidad','residencia'], true) ? $_POST['tipo'] : 'basica';
        if (!$materia_id || !$carrera_id || !$semestre) { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        try { $this->pdo->exec("CREATE TABLE IF NOT EXISTS materias_carrera (id INT AUTO_INCREMENT PRIMARY KEY, materia_id INT NOT NULL, carrera_id INT NOT NULL, semestre TINYINT NOT NULL, tipo ENUM('basica','especialidad','residencia') DEFAULT 'basica', creditos INT DEFAULT 5, UNIQUE KEY uk_materia_carrera_semestre (materia_id, carrera_id, semestre)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"); } catch (\Throwable $e) {}
        // Insert or update
        $ins = $this->pdo->prepare('INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES (:mid,:cid,:sem,:cred,:tipo) ON DUPLICATE KEY UPDATE creditos = VALUES(creditos), tipo = VALUES(tipo)');
        $ok = $ins->execute([':mid'=>$materia_id, ':cid'=>$carrera_id, ':sem'=>$semestre, ':cred'=>($creditos?:5), ':tipo'=>$tipo]);
        $_SESSION['flash'] = $ok ? 'Asignación guardada' : 'Error al guardar asignación';
        $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
        header('Location: /subjects/detail?id=' . (int)$materia_id);
    }

    public function removeFromCareer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $materia_id = filter_input(INPUT_POST, 'materia_id', FILTER_VALIDATE_INT);
        if ($id) {
            $stmt = $this->pdo->prepare('DELETE FROM materias_carrera WHERE id = :id');
            $ok = $stmt->execute([':id'=>$id]);
        } elseif ($materia_id) {
            $carrera_id = filter_input(INPUT_POST, 'carrera_id', FILTER_VALIDATE_INT);
            $semestre = filter_input(INPUT_POST, 'semestre', FILTER_VALIDATE_INT);
            if (!$carrera_id || !$semestre) { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
            $stmt = $this->pdo->prepare('DELETE FROM materias_carrera WHERE materia_id = :mid AND carrera_id = :cid AND semestre = :sem');
            $ok = $stmt->execute([':mid'=>$materia_id, ':cid'=>$carrera_id, ':sem'=>$semestre]);
        } else {
            $_SESSION['flash'] = 'ID inválido'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $_SESSION['flash'] = $ok ? 'Asignación eliminada' : 'Error al eliminar'; $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
        header('Location: /subjects/detail?id=' . (int)($materia_id ?? 0));
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $clave = strtoupper(trim((string)($_POST['clave'] ?? '')));
        if ($nombre === '' || $clave === '') { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $sel = $this->pdo->prepare('SELECT 1 FROM materias WHERE clave = :c LIMIT 1');
        $sel->execute([':c' => $clave]);
        if ($sel->fetchColumn()) { $_SESSION['flash'] = 'La clave ya existe'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $stmt = $this->pdo->prepare('INSERT INTO materias (nombre, clave) VALUES (:n, :c)');
        $stmt->execute([':n' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'), ':c' => $clave]);
        $_SESSION['flash'] = 'Materia creada'; $_SESSION['flash_type'] = 'success'; header('Location: /subjects');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) { $_SESSION['flash'] = 'ID inválido'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $chk = $this->pdo->prepare('SELECT 1 FROM grupos WHERE materia_id = :id LIMIT 1');
        $chk->execute([':id' => $id]);
        if ($chk->fetchColumn()) { $_SESSION['flash'] = 'No se puede eliminar: materia tiene grupos.'; $_SESSION['flash_type'] = 'warning'; header('Location: /subjects'); return; }
        $stmt = $this->pdo->prepare('DELETE FROM materias WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $_SESSION['flash'] = 'Materia eliminada'; $_SESSION['flash_type'] = 'success'; header('Location: /subjects');
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $token = $_POST['csrf_token'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) { $_SESSION['flash'] = 'ID inválido'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $nombre = trim((string)($_POST['nombre'] ?? ''));
        $clave = strtoupper(trim((string)($_POST['clave'] ?? '')));
        if ($nombre === '' || $clave === '') { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $sel = $this->pdo->prepare('SELECT 1 FROM materias WHERE clave = :c AND id <> :id LIMIT 1');
        $sel->execute([':c' => $clave, ':id' => $id]);
        if ($sel->fetchColumn()) { $_SESSION['flash'] = 'La clave ya existe para otra materia'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $stmt = $this->pdo->prepare('UPDATE materias SET nombre = :n, clave = :c WHERE id = :id');
        $ok = $stmt->execute([':n' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'), ':c' => $clave, ':id' => $id]);
        $_SESSION['flash'] = $ok ? 'Materia actualizada' : 'Error al actualizar';
        $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
        header('Location: /subjects');
    }
}
