<?php
namespace App\Controllers;

use PDO;

class CatalogsController
{
    private PDO $pdo;
    private int $ttlSeconds = 300; // cache ligera 5 minutos

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $_SESSION['catalog_cache'] = $_SESSION['catalog_cache'] ?? [];
    }

    private function getCache(string $key): ?array
    {
        $entry = $_SESSION['catalog_cache'][$key] ?? null;
        if (!$entry) { return null; }
        if ((time() - (int)$entry['ts']) > $this->ttlSeconds) { return null; }
        return (array)$entry['data'];
    }

    private function setCache(string $key, array $data): void
    {
        $_SESSION['catalog_cache'][$key] = ['ts' => time(), 'data' => $data];
    }

    private function ensureCompleteData(): void
    {
        $cfg = @include __DIR__ . '/../../config/config.php';
        $seedGroups = (int)($cfg['academic']['seed_min_groups_per_cycle'] ?? 2);
        $seedGradesMin = (int)($cfg['academic']['seed_min_grades_per_group'] ?? 18);
        $seedStudentsPool = (int)($cfg['academic']['seed_students_pool'] ?? 40);
        $createdMaterias = 0;
        $createdGroups = 0;
        $createdAlumnos = 0;
        $createdCalificaciones = 0;
        $changed = false;

        try {
            $chkM = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'materias' AND COLUMN_NAME = 'carrera_id'");
            $chkM->execute();
            if ((int)$chkM->fetchColumn() === 0) { $this->pdo->exec("ALTER TABLE materias ADD COLUMN carrera_id INT NULL"); }
            $chkG = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'grupos' AND COLUMN_NAME = 'carrera_id'");
            $chkG->execute();
            if ((int)$chkG->fetchColumn() === 0) { $this->pdo->exec("ALTER TABLE grupos ADD COLUMN carrera_id INT NULL"); }
            $this->pdo->exec("UPDATE usuarios SET nombre = SUBSTRING_INDEX(email,'@',1) WHERE (nombre IS NULL OR nombre = '') AND email IS NOT NULL AND email <> ''");
            $this->pdo->exec("UPDATE alumnos SET nombre = SUBSTRING_INDEX(email,'@',1) WHERE (nombre IS NULL OR nombre = '') AND email IS NOT NULL AND email <> ''");
        } catch (\Throwable $e) {}

        try {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS carreras (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(120) NOT NULL, clave VARCHAR(20) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        } catch (\Throwable $e) {}
        $carSel = $this->pdo->prepare("SELECT id, nombre FROM carreras WHERE clave = :c");
        $carIns = $this->pdo->prepare("INSERT INTO carreras (nombre, clave) VALUES (:n,:c)");
        $carUpd = $this->pdo->prepare("UPDATE carreras SET nombre = :n WHERE id = :id");
        $carMap = [];
        foreach ([
            ['Contador Público','CP'],
            ['Ingeniería Electrónica','IE'],
            ['Ingeniería en Energías Renovables','IER'],
            ['Ingeniería en Gestión Empresarial','IGE'],
            ['Ingeniería en Sistemas Computacionales','ISC'],
            ['Ingeniería Industrial','II'],
            ['Ingeniería Mecatrónica','IM'],
        ] as $c) {
            $carSel->execute([':c'=>$c[1]]);
            $row = $carSel->fetch(PDO::FETCH_ASSOC);
            $id = (int)($row['id'] ?? 0);
            if ($id === 0) { $carIns->execute([':n'=>$c[0], ':c'=>$c[1]]); $id = (int)$this->pdo->lastInsertId(); $changed = true; }
            elseif ((string)($row['nombre'] ?? '') !== (string)$c[0]) { $carUpd->execute([':n'=>$c[0], ':id'=>$id]); $changed = true; }
            $carMap[$c[1]] = $id;
        }

        $htmlPath = __DIR__ . '/../../public/test_users.html';
        if (is_file($htmlPath)) {
            $alumnos = [];
            $profesores = [];
            try {
                $dom = new \DOMDocument();
                @$dom->loadHTML(file_get_contents($htmlPath) ?: '');
                $tables = $dom->getElementsByTagName('table');
                if ($tables->length >= 2) {
                    $rowsA = $tables->item(0)->getElementsByTagName('tr');
                    foreach ($rowsA as $i => $row) {
                        if ($i === 0) { continue; }
                        $tds = $row->getElementsByTagName('td');
                        if ($tds->length >= 5) {
                            $alumnos[] = [
                                'matricula' => trim($tds->item(0)->nodeValue),
                                'nombre' => trim($tds->item(1)->nodeValue),
                                'apellido' => trim($tds->item(2)->nodeValue),
                                'email' => trim($tds->item(3)->nodeValue),
                                'password' => trim($tds->item(4)->nodeValue),
                            ];
                        }
                    }
                    $rowsP = $tables->item(1)->getElementsByTagName('tr');
                    foreach ($rowsP as $i => $row) {
                        if ($i === 0) { continue; }
                        $tds = $row->getElementsByTagName('td');
                        if ($tds->length >= 3) {
                            $profesores[] = [
                                'matricula' => trim($tds->item(0)->nodeValue),
                                'email' => trim($tds->item(1)->nodeValue),
                                'password' => trim($tds->item(2)->nodeValue),
                            ];
                        }
                    }
                }
            } catch (\Throwable $e) {}
            if ($alumnos) {
                $insA = $this->pdo->prepare('INSERT INTO alumnos (matricula, nombre, apellido, email, password, activo) VALUES (:mat,:nom,:ape,:em,:pw,1)');
                $selA = $this->pdo->prepare('SELECT id FROM alumnos WHERE matricula = :mat');
                $insUA = $this->pdo->prepare("INSERT INTO usuarios (matricula, nombre, email, password, rol, activo) VALUES (:mat,:nom,:em,:pw,'alumno',1)");
                $selUA = $this->pdo->prepare("SELECT id FROM usuarios WHERE matricula = :mat AND rol = 'alumno'");
                foreach ($alumnos as $a) {
                    $selA->execute([':mat' => $a['matricula']]);
                    $alId = (int)($selA->fetchColumn() ?: 0);
                    if ($alId === 0) {
                        $insA->execute([':mat'=>$a['matricula'], ':nom'=>$a['nombre'], ':ape'=>$a['apellido'], ':em'=>$a['email'], ':pw'=>password_hash($a['password'], PASSWORD_DEFAULT)]);
                        $createdAlumnos++;
                        $changed = true;
                    }
                    $selUA->execute([':mat' => $a['matricula']]);
                    if (!(int)($selUA->fetchColumn() ?: 0)) {
                        $nomFull = trim(($a['nombre'] ?? '') . ' ' . ($a['apellido'] ?? ''));
                        $insUA->execute([':mat'=>$a['matricula'], ':nom'=>$nomFull, ':em'=>$a['email'], ':pw'=>password_hash($a['password'], PASSWORD_DEFAULT)]);
                        $changed = true;
                    }
                }
            }
            if ($profesores) {
                $insP = $this->pdo->prepare("INSERT INTO usuarios (matricula, nombre, email, password, rol, activo) VALUES (:mat,:nom,:em,:pw,'profesor',1)");
                $selP = $this->pdo->prepare('SELECT id FROM usuarios WHERE matricula = :mat AND rol = \"profesor\"');
                foreach ($profesores as $p) {
                    $selP->execute([':mat' => $p['matricula']]);
                    if (!(int)($selP->fetchColumn() ?: 0)) {
                        $nom = 'Profesor ' . $p['matricula'];
                        $insP->execute([':mat'=>$p['matricula'], ':nom'=>$nom, ':em'=>$p['email'], ':pw'=>password_hash($p['password'], PASSWORD_DEFAULT)]);
                        $changed = true;
                    }
                }
            }
        }

        $profes = $this->pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchAll(PDO::FETCH_ASSOC);
        if (!$profes) {
            $insP = $this->pdo->prepare("INSERT INTO usuarios (matricula, nombre, email, password, rol, activo) VALUES (:mat, :nom, :em, :pw, 'profesor', 1)");
            $mat = 'PROF' . date('Y') . '01';
            $nom = 'Profesor Demo';
            $em = 'prof.demo@example.test';
            $pw = password_hash('Demo1234', PASSWORD_DEFAULT);
            try { $insP->execute([':mat'=>$mat, ':nom'=>$nom, ':em'=>$em, ':pw'=>$pw]); } catch (\Throwable $e) {}
            $profes = $this->pdo->query("SELECT id, nombre FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchAll(PDO::FETCH_ASSOC);
            if (!$profes) { return; }
        }
        $mats = $this->pdo->query('SELECT id, nombre, clave, carrera_id FROM materias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        if (!$mats) {
            $seed = [
                ['nombre' => 'Fundamentos de Programación', 'clave' => 'ISC-1001'],
                ['nombre' => 'Estructura de Datos', 'clave' => 'ISC-1003'],
                ['nombre' => 'Base de Datos', 'clave' => 'ISC-2001'],
                ['nombre' => 'Álgebra Lineal', 'clave' => 'MAT-1002'],
            ];
            $insM = $this->pdo->prepare('INSERT INTO materias (nombre, clave, carrera_id) VALUES (:n,:c,:car)');
            foreach ($seed as $s) {
                $car = null;
                $cl = (string)$s['clave'];
                if (str_starts_with($cl, 'ISC')) { $car = $carMap['ISC'] ?? null; }
                elseif (str_starts_with($cl, 'II')) { $car = $carMap['II'] ?? null; }
                elseif (str_starts_with($cl, 'IGE')) { $car = $carMap['IGE'] ?? null; }
                elseif (str_starts_with($cl, 'IE')) { $car = $carMap['IE'] ?? null; }
                elseif (str_starts_with($cl, 'IM')) { $car = $carMap['IM'] ?? null; }
                elseif (str_starts_with($cl, 'IER')) { $car = $carMap['IER'] ?? null; }
                elseif (str_starts_with($cl, 'CP')) { $car = $carMap['CP'] ?? null; }
                elseif (str_starts_with($cl, 'ADM')) { $car = $carMap['IGE'] ?? null; }
                elseif (str_starts_with($cl, 'MAT') || str_starts_with($cl, 'GEN')) { $car = $carMap['ISC'] ?? null; }
                $insM->execute([':n'=>$s['nombre'], ':c'=>$s['clave'], ':car'=>$car]);
                $createdMaterias++; $changed = true;
            }
            $mats = $this->pdo->query('SELECT id, nombre, clave, carrera_id FROM materias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
        }
        $matPrim = $mats[0] ?? null; if (!$matPrim) { return; }

        $rowsCycles = $this->pdo->query('SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC')->fetchAll(PDO::FETCH_ASSOC);
        $existingCycles = array_map(fn($x)=>strtoupper((string)$x['ciclo']), $rowsCycles);
        $preferredCycles = (array)($cfg['academic']['cycles'] ?? ['2024A','2024B']);
        $preferredCycles = array_map(fn($c)=>strtoupper((string)$c), $preferredCycles);
        $cycles = array_values(array_unique(array_merge($preferredCycles, $existingCycles)));

        $insG = $this->pdo->prepare('INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo) VALUES (:m,:p,:n,:c)');
        $selCnt = $this->pdo->prepare('SELECT COUNT(*) FROM grupos WHERE profesor_id = :p AND ciclo = :c');
        $selByName = $this->pdo->prepare('SELECT id FROM grupos WHERE profesor_id = :p AND ciclo = :c AND nombre = :n LIMIT 1');
        foreach ($profes as $prof) {
            $pid = (int)$prof['id'];
            foreach ($cycles as $ciclo) {
                $selCnt->execute([':p'=>$pid, ':c'=>$ciclo]);
                $count = (int)($selCnt->fetchColumn() ?: 0);
                $desired = [];
                for ($k=1; $k<=$seedGroups; $k++) {
                    $matIndex = ($k-1) % max(count($mats),1);
                    $matUse = $mats[$matIndex] ?? $matPrim;
                    $desired[] = [ 'name' => ($matUse['clave'] ?? 'MAT') . '-G' . str_pad((string)$k, 2, '0', STR_PAD_LEFT), 'mid' => (int)$matUse['id'] ];
                }
                foreach ($desired as $d) {
                    $selByName->execute([':p'=>$pid, ':c'=>$ciclo, ':n'=>$d['name']]);
                    if (!(int)($selByName->fetchColumn() ?: 0)) {
                        $insG->execute([':m'=>$d['mid'], ':p'=>$pid, ':n'=>$d['name'], ':c'=>$ciclo]);
                        $gidNew = (int)$this->pdo->lastInsertId();
                        $createdGroups++;
                        $changed = true;
                        try { $this->pdo->prepare('UPDATE grupos g JOIN materias m ON m.id = g.materia_id SET g.carrera_id = m.carrera_id WHERE g.id = :id')->execute([':id'=>$gidNew]); } catch (\Throwable $e) {}
                    }
                }
                // Asegurar alumnos y calificaciones para cada grupo del ciclo
                $groups = $this->pdo->prepare('SELECT id FROM grupos WHERE profesor_id = :p AND ciclo = :c');
                $groups->execute([':p'=>$pid, ':c'=>$ciclo]);
                $gids = $groups->fetchAll(PDO::FETCH_ASSOC);
                $alumnos = $this->pdo->query('SELECT id FROM alumnos WHERE activo = 1 ORDER BY id LIMIT '.max($seedStudentsPool,1))->fetchAll(PDO::FETCH_ASSOC);
                if (!$alumnos) {
                    $insA = $this->pdo->prepare('INSERT INTO alumnos (matricula, nombre, apellido, activo) VALUES (:mat,:nom,:ape,1)');
                    $year = (int)date('Y');
                    for ($i=1; $i<=$seedStudentsPool; $i++) {
                        $insA->execute([':mat' => 'A'.str_pad((string)($year*10000+$i), 6, '0', STR_PAD_LEFT), ':nom' => 'Alumno'.$i, ':ape' => 'Demo']);
                    }
                    $alumnos = $this->pdo->query('SELECT id FROM alumnos WHERE activo = 1 ORDER BY id LIMIT '.max($seedStudentsPool,1))->fetchAll(PDO::FETCH_ASSOC);
                    $createdAlumnos += $seedStudentsPool;
                    $changed = true;
                }
                foreach ($gids as $g) {
                    $gid = (int)$g['id'];
                    $countCal = $this->pdo->prepare('SELECT COUNT(*) FROM calificaciones WHERE grupo_id = :g');
                    $countCal->execute([':g'=>$gid]);
                    $existingCount = (int)($countCal->fetchColumn() ?: 0);
                    if ($existingCount < $seedGradesMin) {
                        try {
                            $chkCol = $this->pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'calificaciones' AND COLUMN_NAME = 'promedio'");
                            $chkCol->execute();
                            if ((int)$chkCol->fetchColumn() === 0) {
                                $this->pdo->exec("ALTER TABLE calificaciones ADD COLUMN promedio DECIMAL(5,2) NULL AFTER final");
                            }
                        } catch (\Throwable $e) {}
                        $insC = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final, promedio) VALUES (:a,:g,:p1,:p2,:f,:pr)');
                        $nToAdd = max($seedGradesMin, 1);
                        $i = 0;
                        foreach ($alumnos as $aRow) {
                            if ($i >= $nToAdd) { break; }
                            $aid = (int)$aRow['id'];
                            $exists = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g LIMIT 1');
                            $exists->execute([':a'=>$aid, ':g'=>$gid]);
                            if ($exists->fetchColumn()) { continue; }
                            $p1 = random_int(60, 95); $p2 = random_int(55, 98);
                            $final = (int)round(($p1 + $p2) / 2 + random_int(-5,5));
                            $final = max(50, min(100, $final));
                            $prom = round(($final), 2);
                            $insC->execute([':a'=>$aid, ':g'=>$gid, ':p1'=>$p1, ':p2'=>$p2, ':f'=>$final, ':pr'=>$prom]);
                            $i++;
                            $createdCalificaciones++;
                            $changed = true;
                        }
                    }
                }
            }
        }
        try { $this->pdo->exec("UPDATE calificaciones SET promedio = COALESCE(final, ROUND((IFNULL(parcial1,0)+IFNULL(parcial2,0))/2,2)) WHERE promedio IS NULL"); } catch (\Throwable $e) {}
        if ($createdMaterias + $createdGroups + $createdAlumnos + $createdCalificaciones > 0) {
            $_SESSION['flash'] = 'Siembra automática: ' . ($createdMaterias ? ($createdMaterias . ' materias, ') : '') . ($createdGroups ? ($createdGroups . ' grupos, ') : '') . ($createdAlumnos ? ($createdAlumnos . ' alumnos, ') : '') . ($createdCalificaciones ? ($createdCalificaciones . ' calificaciones, ') : '');
            $_SESSION['flash'] = rtrim($_SESSION['flash'], ', ');
            $_SESSION['flash_type'] = 'success';
        }
        if ($changed) { $_SESSION['catalog_cache'] = []; $_SESSION['charts_cache'] = []; }
    }

    public function ensureSeed(): void
    {
        $this->ensureCompleteData();
    }

    public function subjects(): void
    {
        header('Content-Type: application/json');
        $this->ensureCompleteData();
        $cached = $this->getCache('subjects');
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->query('SELECT id, nombre, clave FROM materias ORDER BY nombre');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('subjects', $rows);
        echo json_encode($rows);
    }

    public function professors(): void
    {
        header('Content-Type: application/json');
        $this->ensureCompleteData();
        $cached = $this->getCache('professors');
        if ($cached !== null) { echo json_encode($cached); return; }
        try { $this->pdo->exec("UPDATE usuarios SET nombre = SUBSTRING_INDEX(email,'@',1) WHERE rol = 'profesor' AND (nombre IS NULL OR nombre = '') AND email IS NOT NULL AND email <> ''"); } catch (\Throwable $e) {}
        $stmt = $this->pdo->query("SELECT id, COALESCE(NULLIF(nombre,''), SUBSTRING_INDEX(email,'@',1)) AS nombre, email FROM usuarios WHERE rol = 'profesor' AND activo = 1 ORDER BY nombre");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('professors', $rows);
        echo json_encode($rows);
    }

    public function students(): void
    {
        header('Content-Type: application/json');
        $this->ensureCompleteData();
        $cached = $this->getCache('students');
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->query('SELECT id, matricula, CONCAT(nombre, " ", apellido) AS nombre FROM alumnos WHERE activo = 1 ORDER BY apellido, nombre');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('students', $rows);
        echo json_encode($rows);
    }

    public function groupsByProfessor(int $profesorId): void
    {
        header('Content-Type: application/json');
        $this->ensureCompleteData();
        $key = 'groups_' . $profesorId;
        $cached = $this->getCache($key);
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->prepare('SELECT g.id, g.nombre, g.ciclo, m.nombre AS materia, m.num_parciales FROM grupos g JOIN materias m ON m.id = g.materia_id WHERE g.profesor_id = :p ORDER BY g.ciclo DESC, m.nombre, g.nombre');
        $stmt->execute([':p' => $profesorId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache($key, $rows);
        echo json_encode($rows);
    }

    public function groupsAll(): void
    {
        header('Content-Type: application/json');
        $this->ensureCompleteData();
        $cached = $this->getCache('groups_all');
        if ($cached !== null) { echo json_encode($cached); return; }
        $stmt = $this->pdo->query('SELECT g.id, g.nombre, g.ciclo, g.profesor_id, m.nombre AS materia, m.num_parciales FROM grupos g JOIN materias m ON m.id = g.materia_id ORDER BY g.ciclo DESC, m.nombre, g.nombre');
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $this->setCache('groups_all', $rows);
        echo json_encode($rows);
    }

    public function cycles(): void
    {
        header('Content-Type: application/json');
        $this->ensureCompleteData();
        $cached = $this->getCache('cycles');
        if ($cached !== null) { echo json_encode($cached); return; }
        $rows = $this->pdo->query('SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC')->fetchAll(PDO::FETCH_ASSOC);
        $cycles = array_map(fn($x) => (string)$x['ciclo'], $rows);
        $this->setCache('cycles', $cycles);
        echo json_encode($cycles);
    }

    public function groupStudents(int $grupoId): void
    {
        header('Content-Type: application/json');
        $this->ensureCompleteData();
        if ($grupoId <= 0) { echo json_encode([]); return; }
        $pending = isset($_GET['pending']) ? (int)$_GET['pending'] : 0;
        $sql = 'SELECT a.id, a.matricula, a.nombre, a.apellido FROM calificaciones c JOIN alumnos a ON a.id = c.alumno_id WHERE c.grupo_id = :g' . ($pending === 1 ? ' AND c.final IS NULL' : '') . ' ORDER BY a.apellido, a.nombre';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':g' => $grupoId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
    }

    public function studentsByProfessor(int $profesorId): void
    {
        header('Content-Type: application/json');
        $this->ensureCompleteData();
        if ($profesorId <= 0) { echo json_encode([]); return; }
        $stmt = $this->pdo->prepare('SELECT DISTINCT a.id, a.matricula, a.nombre, a.apellido FROM calificaciones c JOIN alumnos a ON a.id = c.alumno_id JOIN grupos g ON g.id = c.grupo_id WHERE g.profesor_id = :p ORDER BY a.apellido, a.nombre');
        $stmt->execute([':p' => $profesorId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
    }
}
