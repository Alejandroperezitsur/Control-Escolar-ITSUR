<?php
namespace App\Controllers;

use PDO;

class StudentsController
{
    private PDO $pdo;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): void
    {
        $page = (int)($_GET['page'] ?? 1);
        $limit = 20;
        $offset = max(0, ($page - 1) * $limit);
        $search = $_GET['q'] ?? '';
        $status = $_GET['status'] ?? '';
        
        $where = '';
        $params = [];
        $conditions = [];
        if ($search) {
            $conditions[] = "(matricula LIKE :s1 OR nombre LIKE :s2 OR apellido LIKE :s3 OR email LIKE :s4)";
            $params[':s1'] = "%$search%";
            $params[':s2'] = "%$search%";
            $params[':s3'] = "%$search%";
            $params[':s4'] = "%$search%";
        }
        if ($status === 'active') {
            $conditions[] = "activo = 1";
        } elseif ($status === 'inactive') {
            $conditions[] = "activo = 0";
        }
        if ($conditions) {
            $where = 'WHERE ' . implode(' AND ', $conditions);
        }
        
        // Count total for pagination
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM alumnos $where");
        if (!empty($params)) {
            $countStmt->execute($params);
        } else {
            $countStmt->execute();
        }
        $total = $countStmt->fetchColumn();
        $totalPages = ceil($total / $limit);

        // Fetch students
        // Sorting
        $sort = $_GET['sort'] ?? 'apellido';
        $order = strtoupper($_GET['order'] ?? 'ASC');
        $allowedSorts = ['matricula', 'nombre', 'apellido', 'email', 'activo'];
        if (!in_array($sort, $allowedSorts)) { $sort = 'apellido'; }
        if (!in_array($order, ['ASC', 'DESC'])) { $order = 'ASC'; }
        
        // Secondary sort for name consistency
        $orderBy = "$sort $order";
        if ($sort === 'apellido') { $orderBy .= ", nombre ASC"; }
        
        // Fetch students
        $sql = "SELECT id, matricula, nombre, apellido, email, activo FROM alumnos $where ORDER BY $orderBy LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) { $stmt->bindValue($k, $v); }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        include __DIR__ . '/../Views/students/index.php';
    }

    public function show(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'admin') { http_response_code(403); return 'No autorizado'; }
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        if ($id <= 0) { http_response_code(400); return 'ID inválido'; }

        $stmt = $this->pdo->prepare('SELECT id, matricula, nombre, apellido, email, activo, fecha_nac, foto FROM alumnos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $alumno = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$alumno) { http_response_code(404); return 'Alumno no encontrado'; }

        $svc = new \App\Services\StudentsService($this->pdo);
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
        $carga = $svc->getAcademicLoad($id, $ciclo !== '' ? $ciclo : null);

        $sql = "SELECT m.nombre AS materia, g.nombre AS grupo, g.ciclo, c.parcial1, c.parcial2, c.final,
                       ROUND(IFNULL(c.final, (IFNULL(c.parcial1,0)+IFNULL(c.parcial2,0))/2),2) AS promedio
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                WHERE c.alumno_id = :a
                ORDER BY g.ciclo DESC, m.nombre, g.nombre";
        $stCal = $this->pdo->prepare($sql);
        $stCal->execute([':a' => $id]);
        $calificaciones = $stCal->fetchAll(PDO::FETCH_ASSOC);

        $stGr = $this->pdo->prepare('SELECT DISTINCT g.id, g.nombre, g.ciclo, m.nombre AS materia FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id WHERE c.alumno_id = :a ORDER BY g.ciclo DESC, m.nombre, g.nombre');
        $stGr->execute([':a' => $id]);
        $grupos = $stGr->fetchAll(PDO::FETCH_ASSOC);

        $cyclesStmt = $this->pdo->query('SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC');
        $ciclos = array_map(fn($x) => (string)$x['ciclo'], $cyclesStmt->fetchAll(PDO::FETCH_ASSOC));

        $allGroups = $this->pdo->query('SELECT g.id, g.nombre, g.ciclo, m.nombre AS materia FROM grupos g JOIN materias m ON m.id = g.materia_id ORDER BY g.ciclo DESC, m.nombre, g.nombre')->fetchAll(PDO::FETCH_ASSOC);

        ob_start();
        include __DIR__ . '/../Views/students/show.php';
        return ob_get_clean();
    }

    public function enroll(): void
    {
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $alumnoId = filter_input(INPUT_POST, 'alumno_id', FILTER_VALIDATE_INT);
        $grupoId = filter_input(INPUT_POST, 'grupo_id', FILTER_VALIDATE_INT);
        if (!$alumnoId || !$grupoId) { http_response_code(400); echo 'Parámetros inválidos'; return; }
        $chkA = $this->pdo->prepare('SELECT 1 FROM alumnos WHERE id = :id AND activo = 1');
        $chkA->execute([':id' => $alumnoId]);
        if (!$chkA->fetchColumn()) { http_response_code(400); echo 'Alumno no existe o inactivo'; return; }
        $chkG = $this->pdo->prepare('SELECT 1 FROM grupos WHERE id = :id');
        $chkG->execute([':id' => $grupoId]);
        if (!$chkG->fetchColumn()) { http_response_code(400); echo 'Grupo no existe'; return; }
        $exists = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g');
        $exists->execute([':a' => $alumnoId, ':g' => $grupoId]);
        if ($exists->fetchColumn()) { http_response_code(409); echo 'Ya inscrito'; return; }
        $ins = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a,:g,NULL,NULL,NULL)');
        $ok = $ins->execute([':a' => $alumnoId, ':g' => $grupoId]);
        if ($ok) { header('Content-Type: application/json'); echo json_encode(['success' => true]); } else { http_response_code(500); echo 'Error al inscribir'; }
    }

    public function unenroll(): void
    {
        if (($_SESSION['role'] ?? '') !== 'admin') { http_response_code(403); echo 'No autorizado'; return; }
        $alumnoId = filter_input(INPUT_POST, 'alumno_id', FILTER_VALIDATE_INT);
        $grupoId = filter_input(INPUT_POST, 'grupo_id', FILTER_VALIDATE_INT);
        if (!$alumnoId || !$grupoId) { http_response_code(400); echo 'Parámetros inválidos'; return; }
        $del = $this->pdo->prepare('DELETE FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g');
        $ok = $del->execute([':a' => $alumnoId, ':g' => $grupoId]);
        if ($ok) { header('Content-Type: application/json'); echo json_encode(['success' => true]); } else { http_response_code(500); echo 'Error al desinscribir'; }
    }

    public function store(): void
    {
        $this->checkAdmin();
        $data = $_POST;
        
        // Validation
        if (empty($data['matricula']) || empty($data['nombre']) || empty($data['apellido'])) {
            $this->jsonResponse(['error' => 'Campos obligatorios faltantes'], 400);
            return;
        }

        // Check duplicate matricula
        $stmt = $this->pdo->prepare('SELECT id FROM alumnos WHERE matricula = :m');
        $stmt->execute([':m' => $data['matricula']]);
        if ($stmt->fetch()) {
            $this->jsonResponse(['error' => 'La matrícula ya existe'], 400);
            return;
        }

        $password = !empty($data['password']) ? password_hash($data['password'], PASSWORD_DEFAULT) : null;
        
        $sql = "INSERT INTO alumnos (matricula, nombre, apellido, email, password, activo) 
                VALUES (:matricula, :nombre, :apellido, :email, :password, :activo)";
        
        $stmt = $this->pdo->prepare($sql);
        $res = $stmt->execute([
            ':matricula' => $data['matricula'],
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':email' => $data['email'] ?? null,
            ':password' => $password,
            ':activo' => isset($data['activo']) ? 1 : 0
        ]);

        if ($res) {
            $this->jsonResponse(['success' => true, 'message' => 'Alumno creado correctamente']);
        } else {
            $this->jsonResponse(['error' => 'Error al crear alumno'], 500);
        }
    }

    public function update(): void
    {
        $this->checkAdmin();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse(['error' => 'ID inválido'], 400);
            return;
        }

        $data = $_POST;
        
        // Check duplicate matricula (excluding current user)
        $stmt = $this->pdo->prepare('SELECT id FROM alumnos WHERE matricula = :m AND id != :id');
        $stmt->execute([':m' => $data['matricula'], ':id' => $id]);
        if ($stmt->fetch()) {
            $this->jsonResponse(['error' => 'La matrícula ya existe'], 400);
            return;
        }

        $fields = "matricula = :matricula, nombre = :nombre, apellido = :apellido, email = :email, activo = :activo";
        $params = [
            ':matricula' => $data['matricula'],
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':email' => $data['email'] ?? null,
            ':activo' => isset($data['activo']) ? 1 : 0,
            ':id' => $id
        ];



        if (!empty($data['password'])) {
            $fields .= ", password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        $sql = "UPDATE alumnos SET $fields WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        
        if ($stmt->execute($params)) {
            $this->jsonResponse(['success' => true, 'message' => 'Alumno actualizado correctamente']);
        } else {
            $this->jsonResponse(['error' => 'Error al actualizar alumno'], 500);
        }
    }

    public function delete(): void
    {
        $this->checkAdmin();
        $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse(['error' => 'ID inválido'], 400);
            return;
        }

        $stmt = $this->pdo->prepare("DELETE FROM alumnos WHERE id = :id");
        if ($stmt->execute([':id' => $id])) {
            $this->jsonResponse(['success' => true, 'message' => 'Alumno eliminado correctamente']);
        } else {
            $this->jsonResponse(['error' => 'Error al eliminar alumno'], 500);
        }
    }

    public function get(): void
    {
        $this->checkAdmin();
        $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
        if (!$id) {
            $this->jsonResponse(['error' => 'ID inválido'], 400);
            return;
        }

        $stmt = $this->pdo->prepare("SELECT id, matricula, nombre, apellido, email, activo FROM alumnos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $student = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            $this->jsonResponse($student);
        } else {
            $this->jsonResponse(['error' => 'Alumno no encontrado'], 404);
        }
    }

    private function checkAdmin(): void
    {
        if (($_SESSION['role'] ?? '') !== 'admin') {
            $this->jsonResponse(['error' => 'No autorizado'], 403);
            exit;
        }
    }

    private function jsonResponse(array $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public function byProfessor(): string
    {
        $pid = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
        $grupoId = isset($_GET['grupo_id']) ? (int)$_GET['grupo_id'] : 0;

        $params = [':p' => $pid];
        $where = 'WHERE g.profesor_id = :p';
        if ($ciclo !== '') { $where .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        if ($grupoId > 0) { $where .= ' AND g.id = :gid'; $params[':gid'] = $grupoId; }

        $sql = "SELECT a.matricula, a.nombre, a.apellido,
                       m.nombre AS materia, g.nombre AS grupo, g.ciclo,
                       c.parcial1, c.parcial2, c.final,
                       ROUND(IFNULL(c.final, (IFNULL(c.parcial1,0)+IFNULL(c.parcial2,0))/2),2) AS promedio
                FROM calificaciones c
                JOIN alumnos a ON a.id = c.alumno_id
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                $where
                ORDER BY a.apellido, a.nombre, g.ciclo DESC, m.nombre, g.nombre";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $grStmt = $this->pdo->prepare('SELECT g.id, g.nombre, g.ciclo, m.nombre AS materia FROM grupos g JOIN materias m ON m.id = g.materia_id WHERE g.profesor_id = :p ORDER BY g.ciclo DESC, m.nombre, g.nombre');
        $grStmt->execute([':p' => $pid]);
        $grupos = $grStmt->fetchAll(PDO::FETCH_ASSOC);

        $ciStmt = $this->pdo->prepare('SELECT DISTINCT g.ciclo FROM grupos g WHERE g.profesor_id = :p ORDER BY g.ciclo DESC');
        $ciStmt->execute([':p' => $pid]);
        $ciclos = array_map(fn($x) => (string)$x['ciclo'], $ciStmt->fetchAll(PDO::FETCH_ASSOC));

        ob_start();
        include __DIR__ . '/../Views/professor/students.php';
        return ob_get_clean();
    }

    public function myGrades(): string
    {
        $aid = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
        $materia = isset($_GET['materia']) ? trim((string)$_GET['materia']) : '';
        $params = [':a' => $aid];
        $where = 'WHERE c.alumno_id = :a';
        if ($ciclo !== '') { $where .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        if ($materia !== '') { $where .= ' AND m.nombre = :materia'; $params[':materia'] = $materia; }
        $sql = "SELECT m.nombre AS materia, g.nombre AS grupo, g.ciclo,
                       c.parcial1, c.parcial2, c.final,
                       ROUND(IFNULL(c.final, (IFNULL(c.parcial1,0)+IFNULL(c.parcial2,0))/2),2) AS promedio
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                $where
                ORDER BY g.ciclo DESC, m.nombre, g.nombre";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $mst = $this->pdo->prepare("SELECT DISTINCT m.nombre FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id WHERE c.alumno_id = :a ORDER BY m.nombre");
        $mst->execute([':a' => $aid]);
        $materias = array_map(fn($x) => $x['nombre'], $mst->fetchAll(PDO::FETCH_ASSOC));
        $cst = $this->pdo->prepare("SELECT DISTINCT g.ciclo FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id WHERE c.alumno_id = :a ORDER BY g.ciclo DESC");
        $cst->execute([':a' => $aid]);
        $ciclos = array_map(fn($x) => $x['ciclo'], $cst->fetchAll(PDO::FETCH_ASSOC));
        ob_start();
        include __DIR__ . '/../Views/student/grades.php';
        return ob_get_clean();
    }

    public function myLoad(): string
    {
        ob_start();
        include __DIR__ . '/../Views/student/load.php';
        return ob_get_clean();
    }

    public function myPending(): string
    {
        $aid = (int)($_SESSION['user_id'] ?? 0);
        $sql = "SELECT m.nombre AS materia, g.nombre AS grupo, g.ciclo
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                WHERE c.alumno_id = :a AND c.final IS NULL
                ORDER BY g.ciclo DESC, m.nombre, g.nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':a' => $aid]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ob_start();
        include __DIR__ . '/../Views/student/pending.php';
        return ob_get_clean();
    }

    public function mySubjects(): string
    {
        $aid = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
        $params = [':a' => $aid];
        $where = '';
        if ($ciclo !== '') { $where = ' AND g.ciclo = :c'; $params[':c'] = $ciclo; }
        $sql = "SELECT DISTINCT m.nombre AS materia
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                WHERE c.alumno_id = :a $where
                ORDER BY m.nombre";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        header('Content-Type: application/json');
        return json_encode(array_map(fn($x) => (string)$x['materia'], $rows));
    }

    private function ascii(?string $s): string
    {
        if ($s === null) { return ''; }
        $t = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        if ($t !== false) { $s = $t ?: $s; }
        $s = str_replace(["'", "`"], '', (string)$s);
        $s = preg_replace('/[^\x20-\x7E]/', '', (string)$s);
        return (string)$s;
    }

    public function exportMyGradesCsv(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'alumno') { http_response_code(403); return ''; }
        $aid = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
        $materia = isset($_GET['materia']) ? trim((string)$_GET['materia']) : '';
        $params = [':a' => $aid];
        $where = 'WHERE c.alumno_id = :a';
        if ($ciclo !== '') { $where .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        if ($materia !== '') { $where .= ' AND m.nombre = :materia'; $params[':materia'] = $materia; }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="mis_calificaciones.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Alumno','Materia','Grupo','Ciclo','Parcial1','Parcial2','Final','Promedio']);
        $sql = "SELECT CONCAT(a.nombre,' ',a.apellido) AS alumno, m.nombre AS materia, g.nombre AS grupo, g.ciclo,
                       c.parcial1, c.parcial2, c.final,
                       ROUND(IFNULL(c.final, (IFNULL(c.parcial1,0)+IFNULL(c.parcial2,0))/2),2) AS promedio
                FROM calificaciones c
                JOIN alumnos a ON a.id = c.alumno_id
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                $where
                ORDER BY g.ciclo DESC, m.nombre, g.nombre";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            fputcsv($out, [
                $this->ascii($row['alumno'] ?? ''),
                $this->ascii($row['materia'] ?? ''),
                $this->ascii($row['grupo'] ?? ''),
                $this->ascii($row['ciclo'] ?? ''),
                $row['parcial1'], $row['parcial2'], $row['final'], $row['promedio']
            ]);
        }
        fclose($out);
        return '';
    }

    public function myGradesSummary(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'alumno') { http_response_code(403); return ''; }
        $aid = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
        $materia = isset($_GET['materia']) ? trim((string)$_GET['materia']) : '';
        $params = [':a' => $aid];
        $where = 'WHERE c.alumno_id = :a';
        if ($ciclo !== '') { $where .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        if ($materia !== '') { $where .= ' AND m.nombre = :materia'; $params[':materia'] = $materia; }
        $sql = "SELECT ROUND(AVG(IFNULL(c.final,(IFNULL(c.parcial1,0)+IFNULL(c.parcial2,0))/2)),2) AS promedio,
                        COUNT(*) AS total,
                        SUM(CASE WHEN c.final IS NULL THEN 1 ELSE 0 END) AS pendientes,
                        SUM(CASE WHEN c.final IS NOT NULL AND c.final >= 70 THEN 1 ELSE 0 END) AS aprobadas,
                        SUM(CASE WHEN c.final IS NOT NULL AND c.final < 70 THEN 1 ELSE 0 END) AS reprobadas
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                $where";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: ['promedio'=>0,'total'=>0,'pendientes'=>0,'aprobadas'=>0,'reprobadas'=>0];
        header('Content-Type: application/json');
        return json_encode([
            'promedio' => (float)($row['promedio'] ?? 0),
            'total' => (int)($row['total'] ?? 0),
            'pendientes' => (int)($row['pendientes'] ?? 0),
            'aprobadas' => (int)($row['aprobadas'] ?? 0),
            'reprobadas' => (int)($row['reprobadas'] ?? 0)
        ]);
    }
}
