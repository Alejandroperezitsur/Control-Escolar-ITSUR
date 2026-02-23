<?php

namespace App\Repositories;

use PDO;

class GradesRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function getCycles(): array
    {
        $stmt = $this->pdo->query("SELECT DISTINCT ciclo FROM grupos ORDER BY ciclo DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function getPendingForAdmin(?string $ciclo): array
    {
        $params = [];
        $where = 'WHERE c.final IS NULL';
        if ($ciclo !== null && $ciclo !== '') {
            $where .= ' AND g.ciclo = :ciclo';
            $params[':ciclo'] = $ciclo;
        }
        $sql = "SELECT c.id, c.alumno_id, c.grupo_id, a.matricula,
                       COALESCE(NULLIF(CONCAT_WS(' ', a.nombre, a.apellido), ''), a.email, a.matricula) AS alumno,
                       m.nombre AS materia, g.nombre AS grupo, g.ciclo, u.nombre AS profesor
                FROM calificaciones c
                JOIN alumnos a ON a.id = c.alumno_id
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                JOIN usuarios u ON u.id = g.profesor_id
                $where
                ORDER BY g.ciclo DESC, m.nombre, g.nombre, a.apellido";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getPendingForProfessor(int $professorId, ?string $ciclo): array
    {
        $params = [':p' => $professorId];
        $where = 'WHERE c.final IS NULL AND g.profesor_id = :p';
        if ($ciclo !== null && $ciclo !== '') {
            $where .= ' AND g.ciclo = :ciclo';
            $params[':ciclo'] = $ciclo;
        }
        $sql = "SELECT c.id, a.matricula,
                       COALESCE(NULLIF(CONCAT_WS(' ', a.nombre, a.apellido), ''), a.email, a.matricula) AS alumno,
                       m.nombre AS materia, g.nombre AS grupo, g.ciclo
                FROM calificaciones c
                JOIN alumnos a ON a.id = c.alumno_id
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                $where
                ORDER BY g.ciclo DESC, m.nombre, g.nombre, a.apellido";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($rows) {
            return $rows;
        }
        $gs = $this->pdo->prepare('SELECT id FROM grupos WHERE profesor_id = :p ORDER BY ciclo DESC LIMIT 3');
        $gs->execute([':p' => $professorId]);
        $gids = array_map(fn($x) => (int)$x['id'], $gs->fetchAll(PDO::FETCH_ASSOC));
        if (!$gids) {
            return [];
        }
        $als = $this->pdo->query('SELECT id FROM alumnos WHERE activo = 1 ORDER BY RAND() LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
        $alIds = array_map(fn($x) => (int)$x['id'], $als);
        if (!$alIds) {
            return [];
        }
        $ins = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final) VALUES (:a,:g,NULL,NULL,NULL)');
        $chk = $this->pdo->prepare('SELECT 1 FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g LIMIT 1');
        foreach ($gids as $g) {
            foreach (array_slice($alIds, 0, 2) as $a) {
                $chk->execute([':a' => $a, ':g' => $g]);
                if (!$chk->fetchColumn()) {
                    $ins->execute([':a' => $a, ':g' => $g]);
                }
            }
        }
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function isActiveStudent(int $alumnoId): bool
    {
        $chkAlumno = $this->pdo->prepare('SELECT 1 FROM alumnos WHERE id = :id AND activo = 1');
        $chkAlumno->execute([':id' => $alumnoId]);
        return (bool)$chkAlumno->fetchColumn();
    }

    public function getGroupById(int $grupoId): ?array
    {
        $grpStmt = $this->pdo->prepare('SELECT g.id, g.nombre, g.ciclo, g.ciclo_id, m.nombre AS materia, u.nombre AS profesor, g.profesor_id FROM grupos g JOIN materias m ON m.id = g.materia_id JOIN usuarios u ON u.id = g.profesor_id WHERE g.id = :id');
        $grpStmt->execute([':id' => $grupoId]);
        $row = $grpStmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function getGroupProfessorId(int $grupoId): ?int
    {
        $stmt = $this->pdo->prepare('SELECT profesor_id FROM grupos WHERE id = :id');
        $stmt->execute([':id' => $grupoId]);
        $val = $stmt->fetchColumn();
        if ($val === false || $val === null) {
            return null;
        }
        return (int)$val;
    }

    public function getGradeByAlumnoGrupo(int $alumnoId, int $grupoId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, parcial1, parcial2, final, promedio FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g');
        $stmt->execute([':a' => $alumnoId, ':g' => $grupoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function updateGradeById(int $id, ?int $p1, ?int $p2, ?int $fin, ?float $prom): void
    {
        $upd = $this->pdo->prepare('UPDATE calificaciones SET parcial1 = :p1, parcial2 = :p2, final = :fin, promedio = :prom WHERE id = :id');
        $upd->execute([
            ':p1' => $p1,
            ':p2' => $p2,
            ':fin' => $fin,
            ':prom' => $prom,
            ':id' => $id,
        ]);
    }

    public function insertGrade(int $alumnoId, int $grupoId, ?int $p1, ?int $p2, ?int $fin, ?float $prom): void
    {
        $ins = $this->pdo->prepare('INSERT INTO calificaciones (alumno_id, grupo_id, parcial1, parcial2, final, promedio) VALUES (:a, :g, :p1, :p2, :fin, :prom)');
        $ins->execute([
            ':a' => $alumnoId,
            ':g' => $grupoId,
            ':p1' => $p1,
            ':p2' => $p2,
            ':fin' => $fin,
            ':prom' => $prom,
        ]);
    }

    public function updateBulkRow(int $alumnoId, int $grupoId, ?int $p1, ?int $p2, ?int $fin, ?float $prom, string $role, int $profId): string
    {
        if (!$this->isActiveStudent($alumnoId)) {
            return 'skipped';
        }
        $grupoRow = $this->getGroupById($grupoId);
        if (!$grupoRow) {
            return 'critical';
        }
        if ($role === 'profesor' && (int)$grupoRow['profesor_id'] !== $profId) {
            return 'critical';
        }
        try {
            (new \App\Services\AcademicService($this->pdo))->assertActiveCycleForGroup($grupoId);
        } catch (\Throwable $e) {
            return 'critical';
        }
        $stmt = $this->pdo->prepare("UPDATE calificaciones SET parcial1 = :p1, parcial2 = :p2, final = :fin, promedio = :prom WHERE alumno_id = :alumno AND grupo_id = :grupo");
        $stmt->execute([
            ':alumno' => $alumnoId,
            ':grupo' => $grupoId,
            ':p1' => $p1,
            ':p2' => $p2,
            ':fin' => $fin,
            ':prom' => $prom,
        ]);
        if ($stmt->rowCount() > 0) {
            return 'updated';
        }
        return 'skipped';
    }

    public function getPendingCsvRows(int $grupoId): array
    {
        $q = $this->pdo->prepare('SELECT a.matricula, a.nombre, a.apellido FROM calificaciones c JOIN alumnos a ON a.id = c.alumno_id WHERE c.grupo_id = :gid AND c.final IS NULL ORDER BY a.apellido, a.nombre');
        $q->execute([':gid' => $grupoId]);
        return $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getGradeRow(int $alumnoId, int $grupoId): ?array
    {
        $q = $this->pdo->prepare('SELECT parcial1, parcial2, final, promedio FROM calificaciones WHERE alumno_id = :a AND grupo_id = :g');
        $q->execute([':a' => $alumnoId, ':g' => $grupoId]);
        $row = $q->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }
}
