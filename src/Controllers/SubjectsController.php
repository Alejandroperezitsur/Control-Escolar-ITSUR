<?php
namespace App\Controllers;

use PDO;
use App\Http\Request;
use App\Repositories\SubjectsRepository;

class SubjectsController
{
    private PDO $pdo;
    private SubjectsRepository $subjectsRepository;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->subjectsRepository = new SubjectsRepository($pdo);
    }

    public function index(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); return 'No autorizado'; }
        $page = Request::getInt('page', 1) ?? 1;
        if ($page < 1) { $page = 1; }
        $per = Request::getInt('per_page', 10) ?? 10;
        $sortRaw = Request::getString('sort', 'nombre') ?? 'nombre';
        $orderRaw = Request::getString('order', 'ASC') ?? 'ASC';
        $sort = strtolower($sortRaw);
        $order = strtoupper($orderRaw);
        $q = Request::getString('q', '') ?? '';
        $carRaw = Request::getString('carrera', '') ?? '';
        $car = strtoupper($carRaw);
        $ciclo = Request::getString('ciclo', '') ?? '';
        $estadoRaw = Request::getString('estado', '') ?? '';
        $estado = strtolower($estadoRaw);
        $result = $this->subjectsRepository->paginate($q, $car, $ciclo, $estado, $sort, $order, $page, $per);
        $subjects = $result['subjects'];
        $pagination = $result['pagination'];
        $carrerasList = $result['carrerasList'];
        $cyclesList = $result['cyclesList'];
        ob_start();
        include __DIR__ . '/../Views/subjects/index.php';
        return ob_get_clean();
    }

    public function exportCsv(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); return 'No autorizado'; }
        $q = Request::getString('q', '') ?? '';
        $carRaw = Request::getString('carrera', '') ?? '';
        $car = strtoupper($carRaw);
        $ciclo = Request::getString('ciclo', '') ?? '';
        $estadoRaw = Request::getString('estado', '') ?? '';
        $estado = strtolower($estadoRaw);
        $rows = $this->subjectsRepository->exportSubjects($q, $car, $ciclo, $estado);
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
        $q = Request::getString('q', '') ?? '';
        $carRaw = Request::getString('carrera', '') ?? '';
        $car = strtoupper($carRaw);
        $ciclo = Request::getString('ciclo', '') ?? '';
        $estadoRaw = Request::getString('estado', '') ?? '';
        $estado = strtolower($estadoRaw);
        $rows = $this->subjectsRepository->exportSubjects($q, $car, $ciclo, $estado);

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
        $q = Request::getString('q', '') ?? '';
        $carRaw = Request::getString('carrera', '') ?? '';
        $car = strtoupper($carRaw);
        $ciclo = Request::getString('ciclo', '') ?? '';
        $estadoRaw = Request::getString('estado', '') ?? '';
        $estado = strtolower($estadoRaw);
        $rowsDb = $this->subjectsRepository->exportSubjects($q, $car, $ciclo, $estado);
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
        $rowsProm = $this->subjectsRepository->getPromediosPorCiclo($q, $car, $ciclo);
        $sheet2Xml = $this->buildSheetXml(['Ciclo','Materia','Promedio','Registros'], $rowsProm, ['freezeRows' => 1, 'numericCols' => [3,4]]);
        $rowsNoGrp = $this->subjectsRepository->getSubjectsSinGrupos($q, $car, $ciclo);
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
        $id = Request::getInt('id', 0) ?? 0;
        if ($id <= 0) { http_response_code(400); return 'ID inválido'; }
        $materia = $this->subjectsRepository->findById($id);
        if ($materia === null) { http_response_code(404); return 'Materia no encontrada'; }

        $carKey = Request::getString('carrera', '') ?? '';
        $creditos = null;
        $carreras = $this->subjectsRepository->getCareers();
        if ($carKey !== '') {
            $creditos = $this->subjectsRepository->getCreditsForCareer($id, $carKey);
        }
        if ($creditos !== null) { $materia['creditos'] = $creditos; }

        $grupos = $this->subjectsRepository->getGroupsWithStats($id);
        $ciclos = $this->subjectsRepository->getCycles();
        $assocs = $this->subjectsRepository->getCareerAssociations($id);

        ob_start();
        $data_carreras = $carreras;
        include __DIR__ . '/../Views/subjects/show.php';
        return ob_get_clean();
    }

    public function addToCareer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $token = Request::postString('csrf_token', '') ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $materia_id = filter_input(INPUT_POST, 'materia_id', FILTER_VALIDATE_INT);
        $carrera_id = filter_input(INPUT_POST, 'carrera_id', FILTER_VALIDATE_INT);
        $semestre = filter_input(INPUT_POST, 'semestre', FILTER_VALIDATE_INT);
        $creditos = filter_input(INPUT_POST, 'creditos', FILTER_VALIDATE_INT);
        $tipoRaw = Request::postString('tipo', 'basica') ?? 'basica';
        $tipo = in_array($tipoRaw, ['basica','especialidad','residencia'], true) ? $tipoRaw : 'basica';
        if (!$materia_id || !$carrera_id || !$semestre) { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $ok = $this->subjectsRepository->addToCareer($materia_id, $carrera_id, $semestre, (int)($creditos ?: 5), $tipo);
        $_SESSION['flash'] = $ok ? 'Asignación guardada' : 'Error al guardar asignación';
        $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
        header('Location: /subjects/detail?id=' . (int)$materia_id);
    }

    public function removeFromCareer(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $token = Request::postString('csrf_token', '') ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        $materia_id = filter_input(INPUT_POST, 'materia_id', FILTER_VALIDATE_INT);
        if ($id) {
            $ok = $this->subjectsRepository->removeCareerAssociationById($id);
        } elseif ($materia_id) {
            $carrera_id = filter_input(INPUT_POST, 'carrera_id', FILTER_VALIDATE_INT);
            $semestre = filter_input(INPUT_POST, 'semestre', FILTER_VALIDATE_INT);
            if (!$carrera_id || !$semestre) { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
            $ok = $this->subjectsRepository->removeCareerAssociation($materia_id, $carrera_id, $semestre);
        } else {
            $_SESSION['flash'] = 'ID inválido'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $_SESSION['flash'] = $ok ? 'Asignación eliminada' : 'Error al eliminar'; $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
        header('Location: /subjects/detail?id=' . (int)($materia_id ?? 0));
    }

    public function create(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $token = Request::postString('csrf_token', '') ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        $nombre = Request::postString('nombre', '') ?? '';
        $claveRaw = Request::postString('clave', '') ?? '';
        $clave = strtoupper($claveRaw);
        if ($nombre === '' || $clave === '') { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $result = $this->subjectsRepository->createSubject($nombre, $clave);
        if (!$result['ok']) {
            $_SESSION['flash'] = $result['error'] ?? 'Error al crear materia';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /subjects');
            return;
        }
        $_SESSION['flash'] = 'Materia creada'; $_SESSION['flash_type'] = 'success'; header('Location: /subjects');
    }

    public function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $token = Request::postString('csrf_token', '') ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) { $_SESSION['flash'] = 'ID inválido'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $result = $this->subjectsRepository->deleteSubject($id);
        if (!$result['ok']) {
            $_SESSION['flash'] = $result['error'] ?? 'Error al eliminar';
            $_SESSION['flash_type'] = 'warning';
            header('Location: /subjects');
            return;
        }
        $_SESSION['flash'] = 'Materia eliminada'; $_SESSION['flash_type'] = 'success'; header('Location: /subjects');
    }

    public function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $token = Request::postString('csrf_token', '') ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) { $_SESSION['flash'] = 'ID inválido'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $nombre = Request::postString('nombre', '') ?? '';
        $claveRaw = Request::postString('clave', '') ?? '';
        $clave = strtoupper($claveRaw);
        if ($nombre === '' || $clave === '') { $_SESSION['flash'] = 'Datos inválidos'; $_SESSION['flash_type'] = 'danger'; header('Location: /subjects'); return; }
        $result = $this->subjectsRepository->updateSubject($id, $nombre, $clave);
        if (!$result['ok']) {
            $_SESSION['flash'] = $result['error'] ?? 'Error al actualizar';
            $_SESSION['flash_type'] = 'danger';
            header('Location: /subjects');
            return;
        }
        $_SESSION['flash'] = 'Materia actualizada';
        $_SESSION['flash_type'] = 'success';
        header('Location: /subjects');
    }
}
