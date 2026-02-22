<?php

if (php_sapi_name() !== 'cli') {
    exit("CLI only\n");
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';

$config = require __DIR__ . '/../config/config.php';
$env = (string)($config['app']['env'] ?? getenv('APP_ENV') ?: 'local');
if ($env === 'production') {
    fwrite(STDERR, "Semilla deshabilitada en producción\n");
    exit(1);
}

function db(): PDO
{
    return Database::getInstance()->getConnection();
}

function ensureSchemaCarreras(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS carreras (id INT AUTO_INCREMENT PRIMARY KEY, nombre VARCHAR(120) NOT NULL, clave VARCHAR(20) NOT NULL UNIQUE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    $hasCarClave = $pdo->query("SHOW COLUMNS FROM carreras LIKE 'clave'")->fetchColumn();
    if (!$hasCarClave) {
        $pdo->exec("ALTER TABLE carreras ADD COLUMN clave VARCHAR(20) NOT NULL UNIQUE");
    }
    $hasClaveMat = $pdo->query("SHOW COLUMNS FROM materias LIKE 'clave'")->fetchColumn();
    if (!$hasClaveMat) {
        $pdo->exec("ALTER TABLE materias ADD COLUMN clave VARCHAR(40) NULL");
    }
    $hasCarMat = $pdo->query("SHOW COLUMNS FROM materias LIKE 'carrera_id'")->fetchColumn();
    if (!$hasCarMat) {
        $pdo->exec("ALTER TABLE materias ADD COLUMN carrera_id INT NULL");
    }
    $hasCarGrp = $pdo->query("SHOW COLUMNS FROM grupos LIKE 'carrera_id'")->fetchColumn();
    if (!$hasCarGrp) {
        $pdo->exec("ALTER TABLE grupos ADD COLUMN carrera_id INT NULL");
    }
}

function ensureCarreras(PDO $pdo): array
{
    ensureSchemaCarreras($pdo);
    $list = [
        ['nombre' => 'Ingeniería en Sistemas Computacionales', 'clave' => 'ICS'],
        ['nombre' => 'Ingeniería Industrial', 'clave' => 'IIN'],
        ['nombre' => 'Ingeniería Química', 'clave' => 'IQ'],
        ['nombre' => 'Ingeniería Mecánica', 'clave' => 'IM'],
        ['nombre' => 'Ingeniería Eléctrica', 'clave' => 'IE'],
        ['nombre' => 'Ingeniería Civil', 'clave' => 'IC'],
        ['nombre' => 'Ingeniería en Gestión Empresarial', 'clave' => 'IGE'],
    ];
    $sel = $pdo->prepare("SELECT id FROM carreras WHERE clave = :c");
    $ins = $pdo->prepare("INSERT INTO carreras (nombre, clave) VALUES (:n,:c)");
    $res = [];
    foreach ($list as $c) {
        $sel->execute([':c' => $c['clave']]);
        $id = $sel->fetchColumn();
        if (!$id) {
            $ins->execute([':n' => $c['nombre'], ':c' => $c['clave']]);
            $id = (int)$pdo->lastInsertId();
        }
        $res[$c['clave']] = (int)$id;
    }
    return $res;
}

function parseTestUsers(string $htmlPath): array
{
    $html = file_get_contents($htmlPath);
    if ($html === false) {
        throw new RuntimeException("No se pudo leer $htmlPath");
    }
    $dom = new DOMDocument();
    @$dom->loadHTML($html);
    $tables = $dom->getElementsByTagName('table');
    if ($tables->length < 2) {
        throw new RuntimeException('Formato de test_users.html inválido: se esperan 2 tablas');
    }
    $alumnos = [];
    $rows = $tables->item(0)->getElementsByTagName('tr');
    foreach ($rows as $i => $row) {
        if ($i === 0) {
            continue;
        }
        $cols = $row->getElementsByTagName('td');
        if ($cols->length < 5) {
            continue;
        }
        $alumnos[] = [
            'matricula' => trim($cols->item(0)->nodeValue),
            'nombre' => trim($cols->item(1)->nodeValue),
            'apellido' => trim($cols->item(2)->nodeValue),
            'email' => trim($cols->item(3)->nodeValue),
            'password' => trim($cols->item(4)->nodeValue),
        ];
    }
    $profesores = [];
    $rows = $tables->item(1)->getElementsByTagName('tr');
    foreach ($rows as $i => $row) {
        if ($i === 0) {
            continue;
        }
        $cols = $row->getElementsByTagName('td');
        if ($cols->length < 3) {
            continue;
        }
        $profesores[] = [
            'matricula' => trim($cols->item(0)->nodeValue),
            'email' => trim($cols->item(1)->nodeValue),
            'password' => trim($cols->item(2)->nodeValue),
        ];
    }
    return [$alumnos, $profesores];
}

function ensureUsers(PDO $pdo, array $alumnos, array $profesores): array
{
    $insAlumno = $pdo->prepare("INSERT INTO alumnos (matricula, nombre, apellido, email, password) VALUES (:mat, :nom, :ape, :email, :pw)");
    $selAlumno = $pdo->prepare("SELECT id FROM alumnos WHERE matricula = :mat");
    $insProf = $pdo->prepare("INSERT INTO usuarios (matricula, email, password, rol, activo) VALUES (:mat, :email, :pw, 'profesor', 1)");
    $selProf = $pdo->prepare("SELECT id FROM usuarios WHERE matricula = :mat");
    $addedA = 0;
    $addedP = 0;
    $skippedA = 0;
    $skippedP = 0;
    foreach ($alumnos as $a) {
        $selAlumno->execute([':mat' => $a['matricula']]);
        $id = $selAlumno->fetchColumn();
        if ($id) {
            $skippedA++;
            continue;
        }
        $insAlumno->execute([
            ':mat' => $a['matricula'],
            ':nom' => $a['nombre'],
            ':ape' => $a['apellido'],
            ':email' => $a['email'],
            ':pw' => password_hash($a['password'], PASSWORD_DEFAULT),
        ]);
        $addedA++;
    }
    foreach ($profesores as $p) {
        $selProf->execute([':mat' => $p['matricula']]);
        $id = $selProf->fetchColumn();
        if ($id) {
            $skippedP++;
            continue;
        }
        $insProf->execute([
            ':mat' => $p['matricula'],
            ':email' => $p['email'],
            ':pw' => password_hash($p['password'], PASSWORD_DEFAULT),
        ]);
        $addedP++;
    }
    return compact('addedA', 'addedP', 'skippedA', 'skippedP');
}

function ensureMaterias(PDO $pdo, array $carreras): array
{
    $materias = [
        ['nombre' => 'Programación I', 'clave' => 'INF101'],
        ['nombre' => 'Programación II', 'clave' => 'INF102'],
        ['nombre' => 'Estructuras de Datos', 'clave' => 'INF201'],
        ['nombre' => 'Bases de Datos', 'clave' => 'INF202'],
        ['nombre' => 'Ingeniería de Software', 'clave' => 'INF301'],
        ['nombre' => 'Redes de Computadoras', 'clave' => 'INF302'],
        ['nombre' => 'Arquitectura de Computadoras', 'clave' => 'INF303'],
        ['nombre' => 'Álgebra Lineal', 'clave' => 'MAT102'],
        ['nombre' => 'Cálculo Diferencial', 'clave' => 'MAT201'],
        ['nombre' => 'Cálculo Integral', 'clave' => 'MAT202'],
        ['nombre' => 'Probabilidad y Estadística', 'clave' => 'MAT301'],
        ['nombre' => 'Termodinámica', 'clave' => 'IND101'],
        ['nombre' => 'Procesos de Manufactura', 'clave' => 'IND201'],
        ['nombre' => 'Investigación de Operaciones', 'clave' => 'IND301'],
        ['nombre' => 'Control de Calidad', 'clave' => 'IND302'],
        ['nombre' => 'Química General', 'clave' => 'QUI101'],
        ['nombre' => 'Química Orgánica', 'clave' => 'QUI201'],
        ['nombre' => 'Química Analítica', 'clave' => 'QUI202'],
        ['nombre' => 'Bioquímica', 'clave' => 'QUI301'],
        ['nombre' => 'Contabilidad I', 'clave' => 'ADM101'],
        ['nombre' => 'Contabilidad II', 'clave' => 'ADM102'],
        ['nombre' => 'Finanzas Corporativas', 'clave' => 'ADM201'],
        ['nombre' => 'Mercadotecnia', 'clave' => 'ADM202'],
        ['nombre' => 'Recursos Humanos', 'clave' => 'ADM301'],
        ['nombre' => 'Administración de Operaciones', 'clave' => 'ADM302'],
        ['nombre' => 'Derecho Empresarial', 'clave' => 'ADM303'],
        ['nombre' => 'Análisis Numérico', 'clave' => 'MAT303'],
        ['nombre' => 'Compiladores', 'clave' => 'INF401'],
        ['nombre' => 'Inteligencia Artificial', 'clave' => 'INF402'],
        ['nombre' => 'Ética Profesional', 'clave' => 'GEN101'],
        ['nombre' => 'Metodología de la Investigación', 'clave' => 'GEN102'],
    ];
    $hasClave = (bool)$pdo->query("SHOW COLUMNS FROM materias LIKE 'clave'")->fetchColumn();
    $sel = $pdo->prepare($hasClave ? "SELECT id FROM materias WHERE clave = :c" : "SELECT id FROM materias WHERE nombre = :n");
    $ins = $pdo->prepare($hasClave ? "INSERT INTO materias (nombre, clave, carrera_id) VALUES (:n, :c, :car)" : "INSERT INTO materias (nombre, carrera_id) VALUES (:n, :car)");
    $result = [];
    foreach ($materias as $m) {
        if ($hasClave) {
            $sel->execute([':c' => $m['clave']]);
        } else {
            $sel->execute([':n' => $m['nombre']]);
        }
        $id = $sel->fetchColumn();
        $car = null;
        if (str_starts_with($m['clave'], 'INF')) {
            $car = $carreras['ICS'] ?? null;
        } elseif (str_starts_with($m['clave'], 'IND')) {
            $car = $carreras['IIN'] ?? null;
        } elseif (str_starts_with($m['clave'], 'QUI')) {
            $car = $carreras['IQ'] ?? null;
        } elseif (str_starts_with($m['clave'], 'ADM')) {
            $car = $carreras['IGE'] ?? null;
        } elseif (str_starts_with($m['clave'], 'MAT') || str_starts_with($m['clave'], 'GEN')) {
            $car = $carreras['ICS'] ?? null;
        }
        if (!$id) {
            $params = [':n' => $m['nombre'], ':car' => $car];
            if ($hasClave) {
                $params[':c'] = $m['clave'];
            }
            $ins->execute($params);
            $id = (int)$pdo->lastInsertId();
        } else {
            $pdo->prepare('UPDATE materias SET carrera_id = :car WHERE id = :id')->execute([':car' => $car, ':id' => (int)$id]);
        }
        $result[] = ['id' => (int)$id, 'nombre' => $m['nombre'], 'clave' => $m['clave'], 'carrera_id' => $car];
    }
    $extra = [
        ['n' => 'Mecánica de Materiales', 'c' => 'MEC101', 'car' => $carreras['IM'] ?? null],
        ['n' => 'Diseño de Máquinas', 'c' => 'MEC201', 'car' => $carreras['IM'] ?? null],
        ['n' => 'Circuitos Eléctricos I', 'c' => 'ELE101', 'car' => $carreras['IE'] ?? null],
        ['n' => 'Electrónica I', 'c' => 'ELE201', 'car' => $carreras['IE'] ?? null],
        ['n' => 'Análisis Estructural', 'c' => 'CIV101', 'car' => $carreras['IC'] ?? null],
        ['n' => 'Diseño de Concreto', 'c' => 'CIV201', 'car' => $carreras['IC'] ?? null],
    ];
    foreach ($extra as $e) {
        if ($hasClave) {
            $sel->execute([':c' => $e['c']]);
        } else {
            $sel->execute([':n' => $e['n']]);
        }
        $id = $sel->fetchColumn();
        if (!$id) {
            $params = [':n' => $e['n'], ':car' => $e['car']];
            if ($hasClave) {
                $params[':c'] = $e['c'];
            }
            $ins->execute($params);
            $id = (int)$pdo->lastInsertId();
        }
        $result[] = ['id' => (int)$id, 'nombre' => $e['n'], 'clave' => $e['c'], 'carrera_id' => $e['car']];
    }
    return $result;
}

function crearGruposPorProfesor(PDO $pdo, array $materias, array $ciclos, int $porProfesor = 6, int $maxPorProfesor = 8, int $maxTotal = 50): array
{
    $profesores = $pdo->query("SELECT id, matricula FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchAll(PDO::FETCH_ASSOC);
    if (!$profesores) {
        throw new RuntimeException('No hay profesores activos para crear grupos');
    }
    $sel = $pdo->prepare("SELECT id FROM grupos WHERE materia_id = :m AND profesor_id = :p AND nombre = :nom AND ciclo <=> :c");
    $ins = $pdo->prepare("INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo) VALUES (:m, :p, :nom, :c)");
    $created = [];
    $total = (int)$pdo->query('SELECT COUNT(*) FROM grupos')->fetchColumn();
    foreach ($profesores as $prof) {
        $profId = (int)$prof['id'];
        $cur = (int)$pdo->query("SELECT COUNT(*) FROM grupos WHERE profesor_id = $profId")->fetchColumn();
        if ($cur >= $maxPorProfesor) {
            continue;
        }
        if ($total >= $maxTotal) {
            break;
        }
        $indices = array_rand($materias, min($porProfesor, count($materias)));
        $indices = is_array($indices) ? $indices : [$indices];
        $k = 1;
        foreach ($indices as $idx) {
            $m = $materias[$idx];
            $ciclo = $ciclos[($k - 1) % count($ciclos)];
            $nombre = $m['clave'] . '-G' . str_pad((string)$k, 2, '0', STR_PAD_LEFT);
            $sel->execute([':m' => (int)$m['id'], ':p' => $profId, ':nom' => $nombre, ':c' => $ciclo]);
            $gid = $sel->fetchColumn();
            if (!$gid) {
                $ins->execute([':m' => (int)$m['id'], ':p' => $profId, ':nom' => $nombre, ':c' => $ciclo]);
                $gid = (int)$pdo->lastInsertId();
            }
            $pdo->prepare('UPDATE grupos g JOIN materias m ON m.id = g.materia_id SET g.carrera_id = m.carrera_id WHERE g.id = :id')->execute([':id' => (int)$gid]);
            $created[] = ['id' => (int)$gid, 'materia_id' => (int)$m['id'], 'nombre' => $nombre, 'ciclo' => $ciclo, 'profesor_id' => $profId];
            $k++;
            $cur++;
            $total++;
            if ($k > $porProfesor || $cur >= $maxPorProfesor || $total >= $maxTotal) {
                break;
            }
        }
    }
    return $created;
}

function pruneGrupos(PDO $pdo, int $maxTotal = 50): void
{
    $ids = array_map(fn($r) => (int)$r['id'], $pdo->query("SELECT id FROM grupos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC));
    if (count($ids) > $maxTotal) {
        $surplus = array_slice($ids, $maxTotal);
        if ($surplus) {
            $in = implode(',', array_map('intval', $surplus));
            $pdo->exec("DELETE FROM calificaciones WHERE grupo_id IN ($in)");
            $pdo->exec("DELETE FROM grupos WHERE id IN ($in)");
        }
    }
}

function calificacionExiste(PDO $pdo, int $alumnoId, int $grupoId): bool
{
    $stmt = $pdo->prepare("SELECT id FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g");
    $stmt->execute([':a' => $alumnoId, ':g' => $grupoId]);
    return (bool)$stmt->fetchColumn();
}

function crearCalificacion(PDO $pdo, int $alumnoId, int $grupoId): void
{
    $p1 = random_int(60, 100);
    $p2 = random_int(60, 100);
    $fin = random_int(60, 100);
    $stmt = $pdo->prepare("INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a, :g, :p1, :p2, :f)");
    $stmt->execute([':a' => $alumnoId, ':g' => $grupoId, ':p1' => $p1, ':p2' => $p2, ':f' => $fin]);
}

function enrollAll(PDO $pdo, int $minGroups = 5, int $maxGroups = 8): int
{
    $alumnos = $pdo->query("SELECT id FROM alumnos")->fetchAll();
    $grupos = $pdo->query("SELECT id FROM grupos")->fetchAll();
    if (!$alumnos || !$grupos) {
        return 0;
    }
    $inscritos = 0;
    foreach ($alumnos as $al) {
        $k = random_int($minGroups, $maxGroups);
        $indices = array_rand($grupos, min($k, count($grupos)));
        $indices = is_array($indices) ? $indices : [$indices];
        foreach ($indices as $idx) {
            $gid = (int)$grupos[$idx]['id'];
            $aid = (int)$al['id'];
            if (!calificacionExiste($pdo, $aid, $gid)) {
                crearCalificacion($pdo, $aid, $gid);
                $inscritos++;
            }
        }
    }
    return $inscritos;
}

function mainAdminSeed(): void
{
    $pdo = db();
    $carreras = ensureCarreras($pdo);
    $testUsersPath = __DIR__ . '/../public/test_users.html';
    if (!is_file($testUsersPath)) {
        fwrite(STDERR, "No se encontró test_users.html en public/\n");
        exit(1);
    }
    [$alumnos, $profesores] = parseTestUsers($testUsersPath);
    $usersResult = ensureUsers($pdo, $alumnos, $profesores);
    $materias = ensureMaterias($pdo, $carreras);
    $ciclos = ['2024-1', '2024-2'];
    $grupos = crearGruposPorProfesor($pdo, $materias, $ciclos);
    pruneGrupos($pdo);
    $inscritos = enrollAll($pdo);
    echo "Alumnos agregados: {$usersResult['addedA']}, omitidos: {$usersResult['skippedA']}\n";
    echo "Profesores agregados: {$usersResult['addedP']}, omitidos: {$usersResult['skippedP']}\n";
    echo "Grupos creados o asegurados: " . count($grupos) . "\n";
    echo "Calificaciones creadas: {$inscritos}\n";
}

mainAdminSeed();

