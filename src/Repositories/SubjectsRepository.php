<?php

namespace App\Repositories;

use PDO;

class SubjectsRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    private function buildFilters(string $q, string $car, string $ciclo, string $estado): array
    {
        $whereConds = [];
        $params = [];
        if ($q !== '') {
            $whereConds[] = '(m.nombre LIKE :q OR m.clave LIKE :q)';
            $params[':q'] = '%' . $q . '%';
        }
        if ($car !== '') {
            $whereConds[] = 'EXISTS (SELECT 1 FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id AND c.clave = :car)';
            $params[':car'] = $car;
        }
        if ($ciclo !== '') {
            $whereConds[] = 'EXISTS (SELECT 1 FROM grupos g WHERE g.materia_id = m.id AND g.ciclo = :ciclo)';
            $params[':ciclo'] = $ciclo;
        }
        if ($estado === 'sin_grupos') {
            $whereConds[] = 'NOT EXISTS (SELECT 1 FROM grupos gx WHERE gx.materia_id = m.id' . ($ciclo !== '' ? ' AND gx.ciclo = :ciclo' : '') . ')';
        } elseif ($estado === 'con_grupos') {
            $whereConds[] = 'EXISTS (SELECT 1 FROM grupos gx WHERE gx.materia_id = m.id' . ($ciclo !== '' ? ' AND gx.ciclo = :ciclo' : '') . ')';
        }
        $where = $whereConds ? ('WHERE ' . implode(' AND ', $whereConds)) : '';
        return [$where, $params];
    }

    private function resolveSortColumn(string $sort): string
    {
        return match ($sort) {
            'id' => 'm.id',
            'nombre' => 'm.nombre',
            'clave' => 'm.clave',
            'carreras' => 'carreras',
            'grupos' => 'grupos',
            'promedio' => 'promedio',
            default => 'm.nombre',
        };
    }

    public function paginate(string $q, string $car, string $ciclo, string $estado, string $sort, string $order, int $page, int $perPage): array
    {
        $allowedPer = [10, 25, 50];
        if (!in_array($perPage, $allowedPer, true)) {
            $perPage = 10;
        }
        $limit = $perPage;
        $offset = ($page - 1) * $limit;
        $allowedSorts = ['id', 'nombre', 'clave', 'carreras', 'grupos', 'promedio'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'nombre';
        }
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'ASC';
        }
        [$where, $params] = $this->buildFilters($q, $car, $ciclo, $estado);
        $sortCol = $this->resolveSortColumn($sort);
        $sql = "SELECT m.id, m.nombre, m.clave,
                       (SELECT GROUP_CONCAT(c.clave ORDER BY c.clave SEPARATOR ', ') FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id) AS carreras,
                       (SELECT COUNT(*) FROM grupos g WHERE g.materia_id = m.id" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . ") AS grupos,
                       (SELECT ROUND(AVG(c.final),2) FROM calificaciones c JOIN grupos g2 ON g2.id = c.grupo_id WHERE g2.materia_id = m.id" . ($ciclo !== '' ? " AND g2.ciclo = :ciclo" : '') . " AND c.final IS NOT NULL) AS promedio
                FROM materias m
                $where
                ORDER BY $sortCol $order";
        $sql .= " LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $subjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $countSql = "SELECT COUNT(*) FROM materias m " . ($where !== '' ? $where : '');
        $countStmt = $this->pdo->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)($countStmt->fetchColumn() ?: 0);
        $pages = (int)max(1, ceil($total / $limit));
        $carrerasList = $this->pdo->query('SELECT clave, nombre FROM carreras ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
        $cyclesList = array_map(fn($x) => (string)$x['ciclo'], $this->pdo->query('SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC')->fetchAll(PDO::FETCH_ASSOC));
        $pagination = [
            'page' => $page,
            'pages' => $pages,
            'sort' => $sort,
            'order' => $order,
            'q' => $q,
            'carrera' => $car,
            'ciclo' => $ciclo,
            'estado' => $estado,
            'total' => $total,
            'per_page' => $perPage,
        ];
        return [
            'subjects' => $subjects,
            'pagination' => $pagination,
            'carrerasList' => $carrerasList,
            'cyclesList' => $cyclesList,
        ];
    }

    public function exportSubjects(string $q, string $car, string $ciclo, string $estado): array
    {
        [$where, $params] = $this->buildFilters($q, $car, $ciclo, $estado);
        $sql = "SELECT m.id, m.nombre, m.clave,
                       (SELECT GROUP_CONCAT(c.clave ORDER BY c.clave SEPARATOR ', ') FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id) AS carreras,
                       (SELECT COUNT(*) FROM grupos g WHERE g.materia_id = m.id" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . ") AS grupos,
                       (SELECT ROUND(AVG(c.final),2) FROM calificaciones c JOIN grupos g2 ON g2.id = c.grupo_id WHERE g2.materia_id = m.id" . ($ciclo !== '' ? " AND g2.ciclo = :ciclo" : '') . " AND c.final IS NOT NULL) AS promedio
                FROM materias m $where ORDER BY m.nombre ASC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getPromediosPorCiclo(string $q, string $car, string $ciclo): array
    {
        $rowsProm = [];
        $pProm = [];
        $qWhere = [];
        if ($q !== '') {
            $qWhere[] = '(m.nombre LIKE :q OR m.clave LIKE :q)';
            $pProm[':q'] = '%' . $q . '%';
        }
        if ($car !== '') {
            $qWhere[] = 'EXISTS (SELECT 1 FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id AND c.clave = :car)';
            $pProm[':car'] = $car;
        }
        $wProm = $qWhere ? ('WHERE ' . implode(' AND ', $qWhere)) : '';
        $sqlProm = "SELECT g.ciclo, m.nombre AS materia, ROUND(AVG(c.final),2) AS promedio, COUNT(*) AS registros
                    FROM calificaciones c JOIN grupos g ON g.id = c.grupo_id JOIN materias m ON m.id = g.materia_id
                    $wProm" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . " AND c.final IS NOT NULL
                    GROUP BY g.ciclo, m.id, m.nombre ORDER BY g.ciclo DESC, m.nombre";
        $stmtProm = $this->pdo->prepare($sqlProm);
        if ($ciclo !== '') {
            $pProm[':ciclo'] = $ciclo;
        }
        $stmtProm->execute($pProm);
        $rowsPromDb = $stmtProm->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rowsPromDb as $r) {
            $rowsProm[] = [
                (string)($r['ciclo'] ?? ''),
                (string)($r['materia'] ?? ''),
                (string)($r['promedio'] ?? ''),
                (string)($r['registros'] ?? ''),
            ];
        }
        return $rowsProm;
    }

    public function getSubjectsSinGrupos(string $q, string $car, string $ciclo): array
    {
        $rowsNoGrp = [];
        $pNo = [];
        $wNo = [];
        if ($q !== '') {
            $wNo[] = '(m.nombre LIKE :q OR m.clave LIKE :q)';
            $pNo[':q'] = '%' . $q . '%';
        }
        if ($car !== '') {
            $wNo[] = 'EXISTS (SELECT 1 FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = m.id AND c.clave = :car)';
            $pNo[':car'] = $car;
        }
        $wNoStr = $wNo ? ('WHERE ' . implode(' AND ', $wNo)) : '';
        $sqlNo = "SELECT m.id, m.nombre, m.clave FROM materias m $wNoStr AND NOT EXISTS (SELECT 1 FROM grupos g WHERE g.materia_id = m.id" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . ") ORDER BY m.nombre";
        if ($wNoStr === '') {
            $sqlNo = "SELECT m.id, m.nombre, m.clave FROM materias m WHERE NOT EXISTS (SELECT 1 FROM grupos g WHERE g.materia_id = m.id" . ($ciclo !== '' ? " AND g.ciclo = :ciclo" : '') . ") ORDER BY m.nombre";
        }
        $stmtNo = $this->pdo->prepare($sqlNo);
        if ($ciclo !== '') {
            $pNo[':ciclo'] = $ciclo;
        }
        $stmtNo->execute($pNo);
        $rowsNoDb = $stmtNo->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rowsNoDb as $r) {
            $rowsNoGrp[] = [
                (string)($r['id'] ?? ''),
                (string)($r['nombre'] ?? ''),
                (string)($r['clave'] ?? ''),
            ];
        }
        return $rowsNoGrp;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, nombre, clave FROM materias WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function getCareers(): array
    {
        return $this->pdo->query('SELECT id, clave, nombre FROM carreras ORDER BY nombre')->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCreditsForCareer(int $materiaId, string $careerKey): ?int
    {
        $stCar = $this->pdo->prepare('SELECT id FROM carreras WHERE clave = :cl LIMIT 1');
        $stCar->execute([':cl' => $careerKey]);
        $cid = (int)($stCar->fetchColumn() ?: 0);
        if ($cid <= 0) {
            return null;
        }
        $stCred = $this->pdo->prepare('SELECT creditos FROM materias_carrera WHERE materia_id = :mid AND carrera_id = :cid LIMIT 1');
        $stCred->execute([':mid' => $materiaId, ':cid' => $cid]);
        $val = $stCred->fetchColumn();
        if ($val === false || $val === null) {
            return null;
        }
        return (int)$val;
    }

    public function getGroupsWithStats(int $materiaId): array
    {
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
        $st->execute([':mid' => $materiaId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getCycles(): array
    {
        $cyclesStmt = $this->pdo->query('SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC');
        return array_map(fn($x) => (string)$x['ciclo'], $cyclesStmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function getCareerAssociations(int $materiaId): array
    {
        try {
            $stAss = $this->pdo->prepare('SELECT mc.id, mc.carrera_id, c.clave, c.nombre AS carrera_nombre, mc.semestre, mc.creditos, mc.tipo FROM materias_carrera mc JOIN carreras c ON c.id = mc.carrera_id WHERE mc.materia_id = :mid ORDER BY mc.semestre');
            $stAss->execute([':mid' => $materiaId]);
            return $stAss->fetchAll(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function addToCareer(int $materiaId, int $carreraId, int $semestre, int $creditos, string $tipo): bool
    {
        $ins = $this->pdo->prepare('INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES (:mid,:cid,:sem,:cred,:tipo) ON DUPLICATE KEY UPDATE creditos = VALUES(creditos), tipo = VALUES(tipo)');
        return $ins->execute([':mid' => $materiaId, ':cid' => $carreraId, ':sem' => $semestre, ':cred' => ($creditos ?: 5), ':tipo' => $tipo]);
    }

    public function removeCareerAssociationById(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM materias_carrera WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function removeCareerAssociation(int $materiaId, int $carreraId, int $semestre): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM materias_carrera WHERE materia_id = :mid AND carrera_id = :cid AND semestre = :sem');
        return $stmt->execute([':mid' => $materiaId, ':cid' => $carreraId, ':sem' => $semestre]);
    }

    public function createSubject(string $nombre, string $clave): array
    {
        $sel = $this->pdo->prepare('SELECT 1 FROM materias WHERE clave = :c LIMIT 1');
        $sel->execute([':c' => $clave]);
        if ($sel->fetchColumn()) {
            return ['ok' => false, 'error' => 'La clave ya existe'];
        }
        $stmt = $this->pdo->prepare('INSERT INTO materias (nombre, clave) VALUES (:n, :c)');
        $ok = $stmt->execute([':n' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'), ':c' => $clave]);
        if (!$ok) {
            return ['ok' => false, 'error' => 'Error al crear materia'];
        }
        return ['ok' => true];
    }

    public function deleteSubject(int $id): array
    {
        $chk = $this->pdo->prepare('SELECT 1 FROM grupos WHERE materia_id = :id LIMIT 1');
        $chk->execute([':id' => $id]);
        if ($chk->fetchColumn()) {
            return ['ok' => false, 'error' => 'No se puede eliminar: materia tiene grupos.'];
        }
        $stmt = $this->pdo->prepare('DELETE FROM materias WHERE id = :id');
        $stmt->execute([':id' => $id]);
        return ['ok' => true];
    }

    public function updateSubject(int $id, string $nombre, string $clave): array
    {
        $sel = $this->pdo->prepare('SELECT 1 FROM materias WHERE clave = :c AND id <> :id LIMIT 1');
        $sel->execute([':c' => $clave, ':id' => $id]);
        if ($sel->fetchColumn()) {
            return ['ok' => false, 'error' => 'La clave ya existe para otra materia'];
        }
        $stmt = $this->pdo->prepare('UPDATE materias SET nombre = :n, clave = :c WHERE id = :id');
        $ok = $stmt->execute([':n' => htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8'), ':c' => $clave, ':id' => $id]);
        if (!$ok) {
            return ['ok' => false, 'error' => 'Error al actualizar'];
        }
        return ['ok' => true];
    }
}
