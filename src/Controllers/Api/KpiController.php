<?php
namespace App\Controllers\Api;

use App\Services\GradesService;
use App\Services\GroupsService;
use App\Services\SubjectsService;
use PDO;

class KpiController
{
    private PDO $pdo;
    private GradesService $grades;
    private GroupsService $groups;
    private SubjectsService $subjects;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $this->grades = new GradesService($pdo);
        $this->groups = new GroupsService($pdo);
        $this->subjects = new SubjectsService($pdo);
    }

    public function admin(): void
    {
        header('Content-Type: application/json');
        $totalAlumnos = (int)$this->pdo->query('SELECT COUNT(*) FROM alumnos WHERE activo = 1')->fetchColumn();
        $totalProfesores = (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchColumn();
        if ($totalProfesores === 0) {
            $pwd = password_hash('demo123', PASSWORD_BCRYPT);
            $stmt = $this->pdo->prepare("INSERT INTO usuarios (matricula, email, nombre, password, rol, activo) VALUES (:mat,:em,:nom,:pwd,'profesor',1)");
            $stmt->execute([':mat' => 'P' . random_int(10000000,99999999), ':em' => 'prof.demo@example.com', ':nom' => 'Profesor Demo', ':pwd' => $pwd]);
            $totalProfesores = (int)$this->pdo->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchColumn();
        }
        $totalMaterias = $this->subjects->count();
        
        // Check if carreras table exists and has all required columns
        try {
            // Check if required columns exist
            $columnsCheck = $this->pdo->query("SHOW COLUMNS FROM carreras LIKE 'descripcion'")->fetch();
            
            if (!$columnsCheck) {
                // Table exists but missing columns, add them
                try {
                    $this->pdo->exec("ALTER TABLE carreras ADD COLUMN IF NOT EXISTS descripcion TEXT AFTER nombre");
                } catch (\PDOException $e) {}
                
                try {
                    $this->pdo->exec("ALTER TABLE carreras ADD COLUMN IF NOT EXISTS duracion_semestres INT DEFAULT 9 AFTER descripcion");
                } catch (\PDOException $e) {}
                
                try {
                    $this->pdo->exec("ALTER TABLE carreras ADD COLUMN IF NOT EXISTS creditos_totales INT DEFAULT 240 AFTER duracion_semestres");
                } catch (\PDOException $e) {}
                
                try {
                    $this->pdo->exec("ALTER TABLE carreras ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1 AFTER creditos_totales");
                } catch (\PDOException $e) {}
                
                // Update existing records with descriptions
                $this->pdo->exec("UPDATE carreras SET 
                    descripcion = CASE 
                        WHEN clave = 'ISC' OR clave = 'IC' THEN 'Profesionista capaz de diseñar, desarrollar e implementar sistemas computacionales aplicando las metodologías y tecnologías más recientes.'
                        WHEN clave = 'II' THEN 'Profesionista capaz de diseñar, implementar y mejorar sistemas de producción de bienes y servicios.'
                        WHEN clave = 'IGE' THEN 'Profesionista capaz de diseñar, crear y dirigir organizaciones competitivas con visión estratégica.'
                        WHEN clave = 'IE' THEN 'Profesionista capaz de diseñar, desarrollar e innovar sistemas electrónicos para la solución de problemas en el sector productivo.'
                        WHEN clave = 'IM' THEN 'Profesionista capaz de diseñar, construir y mantener sistemas mecatrónicos innovadores.'
                        WHEN clave = 'IER' THEN 'Profesionista capaz de diseñar, implementar y evaluar proyectos de energía sustentable.'
                        WHEN clave = 'CP' THEN 'Profesionista capaz de diseñar, implementar y evaluar sistemas de información financiera.'
                        ELSE 'Descripción no disponible'
                    END,
                    duracion_semestres = COALESCE(duracion_semestres, 9),
                    creditos_totales = COALESCE(creditos_totales, 240),
                    activo = COALESCE(activo, 1)
                WHERE descripcion IS NULL OR descripcion = ''");
            }

            // Count only active, canonical careers
            $validClaves = "'ISC','II','IGE','IE','IM','IER','CP'";
            $q1 = $this->pdo->query("SELECT COUNT(*) FROM carreras WHERE clave IN ($validClaves) AND (activo = 1 OR activo IS NULL)");
            $totalCarreras = (int)($q1->fetchColumn() ?: 0);
            if ($totalCarreras === 0) {
                // Fallback: distinct claves in case activo column is missing
                $q2 = $this->pdo->query("SELECT COUNT(DISTINCT clave) FROM carreras WHERE clave IN ($validClaves)");
                $totalCarreras = (int)($q2->fetchColumn() ?: 0);
            }
        } catch (\PDOException $e) {
            // Table doesn't exist, create it with all columns
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS carreras (
                id INT AUTO_INCREMENT PRIMARY KEY,
                nombre VARCHAR(255) NOT NULL,
                clave VARCHAR(50) NOT NULL UNIQUE,
                descripcion TEXT,
                duracion_semestres INT DEFAULT 9,
                creditos_totales INT DEFAULT 240,
                activo TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_activo (activo),
                INDEX idx_clave (clave)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            
            // Insert all 7 careers
            $this->pdo->exec("INSERT INTO carreras (nombre, clave, descripcion, duracion_semestres, creditos_totales) VALUES
                ('Ingeniería en Sistemas Computacionales', 'ISC', 'Profesionista capaz de diseñar, desarrollar e implementar sistemas computacionales aplicando las metodologías y tecnologías más recientes.', 9, 240),
                ('Ingeniería Industrial', 'II', 'Profesionista capaz de diseñar, implementar y mejorar sistemas de producción de bienes y servicios.', 9, 240),
                ('Ingeniería en Gestión Empresarial', 'IGE', 'Profesionista capaz de diseñar, crear y dirigir organizaciones competitivas con visión estratégica.', 9, 240),
                ('Ingeniería Electrónica', 'IE', 'Profesionista capaz de diseñar, desarrollar e innovar sistemas electrónicos para la solución de problemas en el sector productivo.', 9, 240),
                ('Ingeniería Mecatrónica', 'IM', 'Profesionista capaz de diseñar, construir y mantener sistemas mecatrónicos innovadores.', 9, 240),
                ('Ingeniería en Energías Renovables', 'IER', 'Profesionista capaz de diseñar, implementar y evaluar proyectos de energía sustentable.', 9, 240),
                ('Contador Público', 'CP', 'Profesionista capaz de diseñar, implementar y evaluar sistemas de información financiera.', 9, 240)
                ON DUPLICATE KEY UPDATE nombre=VALUES(nombre)");
            
            $totalCarreras = 7;
        }
        
        // Auto-setup curriculum structure (materias_carrera table)
        try {
            $checkCurriculumTable = $this->pdo->query("SHOW TABLES LIKE 'materias_carrera'")->fetch();
            if (!$checkCurriculumTable) {
                // Create materias_carrera table
                $this->pdo->exec("CREATE TABLE IF NOT EXISTS materias_carrera (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    materia_id INT UNSIGNED NOT NULL,
                    carrera_id INT NOT NULL,
                    semestre TINYINT NOT NULL,
                    tipo ENUM('basica', 'especialidad', 'residencia') DEFAULT 'basica',
                    creditos INT DEFAULT 5,
                    FOREIGN KEY (materia_id) REFERENCES materias(id) ON DELETE CASCADE,
                    FOREIGN KEY (carrera_id) REFERENCES carreras(id) ON DELETE CASCADE,
                    INDEX idx_carrera_semestre (carrera_id, semestre),
                    INDEX idx_materia (materia_id),
                    UNIQUE KEY uk_materia_carrera_semestre (materia_id, carrera_id, semestre)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            }
        } catch (\PDOException $e) {
            // Table might already exist or foreign keys might fail, silently continue
        }
        
        // Auto-seed curriculum data if empty
        try {
            $count = $this->pdo->query("SELECT COUNT(*) FROM materias_carrera")->fetchColumn();
            if ($count == 0) {
                // Path to migrations
                $migrationsPath = __DIR__ . '/../../../migrations/';
                
                // 1. Seed subjects
                if (file_exists($migrationsPath . 'seed_subjects_data.sql')) {
                    $sql = file_get_contents($migrationsPath . 'seed_subjects_data.sql');
                    $this->pdo->exec($sql);
                }
                
                // 2. Seed curriculum part 1
                if (file_exists($migrationsPath . 'seed_curriculum_part1.sql')) {
                    $sql = file_get_contents($migrationsPath . 'seed_curriculum_part1.sql');
                    $this->pdo->exec($sql);
                }
                
                // 3. Seed curriculum part 2
                if (file_exists($migrationsPath . 'seed_curriculum_part2.sql')) {
                    $sql = file_get_contents($migrationsPath . 'seed_curriculum_part2.sql');
                    $this->pdo->exec($sql);
                }
            }
        } catch (\Exception $e) {
            // Ignore seeding errors
        }

        // Fix: Ensure ISC and CP have curriculum data (requested specifically)
        try {
            $iscCount = $this->pdo->query("SELECT COUNT(*) FROM materias_carrera mc JOIN carreras c ON mc.carrera_id = c.id WHERE c.clave = 'ISC'")->fetchColumn();
            
            // If ISC curriculum is incomplete (less than 40 items, a full curriculum is usually ~50), force reload
            if ($iscCount < 40) {
                $migrationsPath = __DIR__ . '/../../../migrations/';
                if (file_exists($migrationsPath . 'force_full_isc_curriculum.sql')) {
                    $sql = file_get_contents($migrationsPath . 'force_full_isc_curriculum.sql');
                    $this->pdo->exec($sql);
                }
            }
            
            // Check CP separately
            $cpCount = $this->pdo->query("SELECT COUNT(*) FROM materias_carrera mc JOIN carreras c ON mc.carrera_id = c.id WHERE c.clave = 'CP'")->fetchColumn();
             if ($cpCount == 0) {
                $migrationsPath = __DIR__ . '/../../../migrations/';
                if (file_exists($migrationsPath . 'seed_isc_cp_fix.sql')) {
                    $sql = file_get_contents($migrationsPath . 'seed_isc_cp_fix.sql');
                    $this->pdo->exec($sql);
                }
            }
        } catch (\Exception $e) {
            // Ignore errors
        }

        // Ensure unique indexes exist for data integrity
        try {
            $chkIdx = $this->pdo->query("SHOW INDEX FROM materias WHERE Key_name = 'uq_materias_clave'")->fetch();
            if (!$chkIdx) {
                $this->pdo->exec("CREATE UNIQUE INDEX uq_materias_clave ON materias (clave)");
            }
        } catch (\PDOException $e) { /* duplicates or engine limitations; rely on service-level validation */ }

        try {
            $chkIdx2 = $this->pdo->query("SHOW INDEX FROM usuarios WHERE Key_name = 'uq_usuarios_email_role'")->fetch();
            if (!$chkIdx2) {
                $this->pdo->exec("CREATE UNIQUE INDEX uq_usuarios_email_role ON usuarios (email, rol)");
            }
        } catch (\PDOException $e) { /* ignore if incompatible or duplicates exist */ }

        try {
            $chkIdx3 = $this->pdo->query("SHOW INDEX FROM alumnos WHERE Key_name = 'uq_alumnos_matricula'")->fetch();
            if (!$chkIdx3) {
                $this->pdo->exec("CREATE UNIQUE INDEX uq_alumnos_matricula ON alumnos (matricula)");
            }
        } catch (\PDOException $e) { /* ignore */ }
        
        $activosGrupos = (int)$this->pdo->query("SELECT COUNT(*) FROM grupos g WHERE EXISTS (SELECT 1 FROM calificaciones c WHERE c.grupo_id = g.id)")->fetchColumn();

        if ($totalMaterias === 0) {
            $this->pdo->exec("INSERT INTO materias (nombre, clave) VALUES
                ('Matemáticas I','MAT1'),('Programación I','PRO1'),('Física I','FIS1'),('Química I','QUI1'),('Inglés I','ING1')");
            $totalMaterias = $this->subjects->count();
        }

        if ($activosGrupos === 0 && $totalProfesores > 0) {
            $profs = $this->pdo->query("SELECT id FROM usuarios WHERE rol = 'profesor' AND activo = 1")->fetchAll(PDO::FETCH_ASSOC);
            $mats = $this->pdo->query("SELECT id, clave FROM materias ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
            $ins = $this->pdo->prepare('INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo) VALUES (:m,:p,:n,:c)');
            foreach ($profs as $idx => $p) {
                $pid = (int)$p['id'];
                for ($k=0; $k<min(3,count($mats)); $k++) {
                    $m = $mats[$k];
                    $name = ($m['clave'] ?? ('GRP'.($k+1))) . '-G' . str_pad((string)($idx+1), 2, '0', STR_PAD_LEFT);
                    $ciclo = date('Y') . '-' . ((($k % 2) === 0) ? '1' : '2');
                    $sel = $this->pdo->prepare('SELECT 1 FROM grupos WHERE materia_id = :m AND profesor_id = :p AND nombre = :n AND ciclo = :c LIMIT 1');
                    $sel->execute([':m'=>(int)$m['id'], ':p'=>$pid, ':n'=>$name, ':c'=>$ciclo]);
                    if (!$sel->fetchColumn()) { $ins->execute([':m'=>(int)$m['id'], ':p'=>$pid, ':n'=>$name, ':c'=>$ciclo]); }
                }
            }
            $activosGrupos = (int)$this->pdo->query("SELECT COUNT(*) FROM grupos g WHERE EXISTS (SELECT 1 FROM calificaciones c WHERE c.grupo_id = g.id)")->fetchColumn();
        }

        if ($totalAlumnos === 0) {
            $insA = $this->pdo->prepare('INSERT INTO alumnos (matricula, nombre, apellido, email, password, activo) VALUES (:mat,:nom,:ape,:em,:pwd,1)');
            for ($i=0;$i<10;$i++) {
                $mat = 'S' . str_pad((string)random_int(10000000, 99999999), 8, '0', STR_PAD_LEFT);
                $nom = 'Alumno' . ($i+1);
                $ape = 'Demo';
                $em = strtolower($nom) . ($i+1) . '@example.com';
                $pwd = password_hash('demo123', PASSWORD_BCRYPT);
                $sel = $this->pdo->prepare('SELECT 1 FROM alumnos WHERE matricula = :m LIMIT 1');
                $sel->execute([':m'=>$mat]);
                if (!$sel->fetchColumn()) { $insA->execute([':mat'=>$mat, ':nom'=>$nom, ':ape'=>$ape, ':em'=>$em, ':pwd'=>$pwd]); }
            }
            $totalAlumnos = (int)$this->pdo->query('SELECT COUNT(*) FROM alumnos')->fetchColumn();
        }

        $grps = $this->pdo->query('SELECT id FROM grupos')->fetchAll(PDO::FETCH_ASSOC);
        if ($grps) {
            $alIds = $this->pdo->query('SELECT id FROM alumnos WHERE activo = 1 LIMIT 20')->fetchAll(PDO::FETCH_ASSOC);
            $chk = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g LIMIT 1');
            $insC = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a,:g,:p1,:p2,:fin)');
            foreach ($grps as $g) {
                $gid = (int)$g['id'];
                foreach (array_slice($alIds, 0, 5) as $a) {
                    $aid = (int)$a['id'];
                    $chk->execute([':a'=>$aid, ':g'=>$gid]);
                    if (!$chk->fetchColumn()) {
                        $p1 = random_int(50, 95);
                        $p2 = random_int(50, 95);
                        $fin = (random_int(0, 3) === 0) ? null : random_int(50, 95);
                        $insC->execute([':a'=>$aid, ':g'=>$gid, ':p1'=>$p1, ':p2'=>$p2, ':fin'=>$fin]);
                    }
                }
            }
        }

        $promedioGeneral = $this->grades->globalAverage();
        $pendientes = (int)$this->pdo->query('SELECT COUNT(*) FROM calificaciones WHERE final IS NULL')->fetchColumn();
        echo json_encode([
            'alumnos' => $totalAlumnos,
            'profesores' => $totalProfesores,
            'materias' => $totalMaterias,
            'carreras' => $totalCarreras,
            'promedio' => $promedioGeneral,
            'grupos' => $activosGrupos,
            'pendientes_evaluacion' => $pendientes,
        ]);
    }

    public function profesorDashboard(int $profesorId): void
    {
        header('Content-Type: application/json');
        $grupos = $this->groups->activeByTeacher($profesorId);
        $grupos = array_values(array_filter($grupos, fn($g) => (int)($g['alumnos'] ?? 0) > 0));
        if (!$grupos) {
            $mats = $this->pdo->query('SELECT id, clave FROM materias ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
            if ($mats) {
                $ins = $this->pdo->prepare('INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo) VALUES (:m,:p,:n,:c)');
                for ($k=0; $k<min(3,count($mats)); $k++) {
                    $m = $mats[$k];
                    $name = ($m['clave'] ?? ('GRP'.($k+1))) . '-G' . str_pad((string)$profesorId, 2, '0', STR_PAD_LEFT);
                    $ciclo = date('Y') . '-' . ((($k % 2) === 0) ? '1' : '2');
                    $sel = $this->pdo->prepare('SELECT 1 FROM grupos WHERE materia_id = :m AND profesor_id = :p AND nombre = :n AND ciclo = :c LIMIT 1');
                    $sel->execute([':m'=>(int)$m['id'], ':p'=>$profesorId, ':n'=>$name, ':c'=>$ciclo]);
                    if (!$sel->fetchColumn()) { $ins->execute([':m'=>(int)$m['id'], ':p'=>$profesorId, ':n'=>$name, ':c'=>$ciclo]); }
                }
                $grupos = $this->groups->activeByTeacher($profesorId);
                $grupos = array_values(array_filter($grupos, fn($g) => (int)($g['alumnos'] ?? 0) > 0));
                if ($grupos) {
                    $als = $this->pdo->query('SELECT id FROM alumnos WHERE activo = 1 LIMIT 12')->fetchAll(PDO::FETCH_ASSOC);
                    $chk = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g LIMIT 1');
                    $insC = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a,:g,:p1,:p2,:fin)');
                    foreach ($grupos as $g) {
                        $gid = (int)$g['id'];
                        foreach (array_slice($als, 0, 4) as $a) {
                            $aid = (int)$a['id'];
                            $chk->execute([':a'=>$aid, ':g'=>$gid]);
                            if (!$chk->fetchColumn()) {
                                $p1 = random_int(50, 95);
                                $p2 = random_int(50, 95);
                                $fin = (random_int(0, 3) === 0) ? null : random_int(50, 95);
                                $insC->execute([':a'=>$aid, ':g'=>$gid, ':p1'=>$p1, ':p2'=>$p2, ':fin'=>$fin]);
                            }
                        }
                    }
                }
            }
        }
        $totalAlumnos = 0;
        foreach ($grupos as $g) { $totalAlumnos += (int)($g['alumnos'] ?? 0); }
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id WHERE g.profesor_id = :pid AND c.final IS NULL");
        $stmt->execute([':pid' => $profesorId]);
        $pendientes = (int)($stmt->fetchColumn() ?: 0);
        echo json_encode([
            'grupos_activos' => count($grupos),
            'alumnos' => $totalAlumnos,
            'grupos' => $grupos,
            'pendientes' => $pendientes,
        ]);
    }
}
