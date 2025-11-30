<?php
namespace App\Services;

use PDO;
use App\Utils\Logger;

class GroupsService
{
    private PDO $pdo;
    private ?string $lastError = null;
    
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function count(?int $profesorId = null): int
    {
        if ($profesorId) {
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM grupos WHERE profesor_id = :pid');
            $stmt->execute([':pid' => $profesorId]);
            return (int)$stmt->fetchColumn();
        }
        return (int)$this->pdo->query('SELECT COUNT(*) FROM grupos')->fetchColumn();
    }

    public function activeByTeacher(int $profesorId): array
    {
        $sql = "SELECT g.id, m.nombre AS materia, g.nombre AS grupo, g.ciclo,
                       COUNT(DISTINCT c.alumno_id) AS alumnos,
                       ROUND(AVG(c.promedio), 2) AS promedio
                FROM grupos g
                JOIN materias m ON g.materia_id = m.id
                LEFT JOIN calificaciones c ON c.grupo_id = g.id
                WHERE g.profesor_id = :profesorId
                GROUP BY g.id, m.nombre, g.nombre, g.ciclo
                ORDER BY m.nombre, g.nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':profesorId' => $profesorId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function studentsInGroup(int $grupoId): array
    {
        $sql = "SELECT a.id, a.matricula, a.nombre, a.apellido,
                       c.parcial1, c.parcial2, c.parcial3, c.parcial4, c.parcial5, c.final, c.promedio
                FROM calificaciones c
                JOIN alumnos a ON a.id = c.alumno_id
                WHERE c.grupo_id = :grupoId
                ORDER BY a.apellido, a.nombre";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':grupoId' => $grupoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    private function exists(string $table, int $id): bool
    {
        if ($id <= 0) { return false; }
        $stmt = $this->pdo->prepare("SELECT 1 FROM {$table} WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    private function existsProfesorActivo(int $id): bool
    {
        if ($id <= 0) { return false; }
        $stmt = $this->pdo->prepare("SELECT 1 FROM usuarios WHERE id = :id AND rol = 'profesor' AND activo = 1 LIMIT 1");
        $stmt->execute([':id' => $id]);
        return (bool)$stmt->fetchColumn();
    }

    private function existsGroupCombo(array $data, ?int $excludeId = null): bool
    {
        $sql = 'SELECT 1 FROM grupos WHERE materia_id = :mid AND profesor_id = :pid AND nombre = :nombre AND ciclo = :ciclo';
        $params = [
            ':mid' => (int)($data['materia_id'] ?? 0),
            ':pid' => (int)($data['profesor_id'] ?? 0),
            ':nombre' => trim((string)($data['nombre'] ?? '')),
            ':ciclo' => trim((string)($data['ciclo'] ?? '')),
        ];
        if ($excludeId !== null && $excludeId > 0) {
            $sql .= ' AND id <> :id';
            $params[':id'] = $excludeId;
        }
        $stmt = $this->pdo->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return (bool)$stmt->fetchColumn();
    }

    private function validate(array $data, ?int $excludeId = null): bool
    {
        $materiaId = (int)($data['materia_id'] ?? 0);
        $profesorId = (int)($data['profesor_id'] ?? 0);
        $nombre = trim((string)($data['nombre'] ?? ''));
        $ciclo = trim((string)($data['ciclo'] ?? ''));
        $cupo = (int)($data['cupo'] ?? 30);
        $aulaId = (int)($data['aula_id'] ?? 0);

        // cupo 1-100
        if ($cupo < 1 || $cupo > 100) {
            $this->lastError = 'Cupo fuera de rango (1–100)';
            Logger::info('group_validation_failed', ['reason' => 'cupo_range', 'cupo' => $cupo]);
            return false;
        }
        // ciclo regex YYYY-1|2
        if (!preg_match('/^\d{4}-(1|2)$/', $ciclo)) {
            $this->lastError = 'Formato de ciclo inválido (YYYY-1 o YYYY-2)';
            Logger::info('group_validation_failed', ['reason' => 'ciclo_format', 'ciclo' => $ciclo]);
            return false;
        }
        // materia/profesor existen
        if (!$this->exists('materias', $materiaId)) {
            $this->lastError = 'Materia no existe';
            Logger::info('group_validation_failed', ['reason' => 'materia_missing', 'materia_id' => $materiaId]);
            return false;
        }
        if (!$this->existsProfesorActivo($profesorId)) {
            $this->lastError = 'Profesor no existe o no está activo';
            Logger::info('group_validation_failed', ['reason' => 'profesor_missing_or_inactive', 'profesor_id' => $profesorId]);
            return false;
        }
        // aula existe (si se proporciona)
        if ($aulaId > 0 && !$this->exists('aulas', $aulaId)) {
            $this->lastError = 'Aula no existe';
            Logger::info('group_validation_failed', ['reason' => 'aula_missing', 'aula_id' => $aulaId]);
            return false;
        }
        // unicidad
        if ($this->existsGroupCombo($data, $excludeId)) {
            $this->lastError = 'Ya existe un grupo con la misma materia, profesor, nombre y ciclo';
            Logger::info('group_validation_failed', [
                'reason' => 'duplicate_combo',
                'materia_id' => $materiaId,
                'profesor_id' => $profesorId,
                'nombre' => $nombre,
                'ciclo' => $ciclo,
                'exclude_id' => $excludeId,
            ]);
            return false;
        }
        return true;
    }

    public function create(array $data): bool
    {
        if (!$this->validate($data, null)) { return false; }
        $stmt = $this->pdo->prepare('INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo, cupo, aula_id) VALUES (:materia_id, :profesor_id, :nombre, :ciclo, :cupo, :aula_id)');
        return $stmt->execute([
            ':materia_id' => (int)($data['materia_id'] ?? 0),
            ':profesor_id' => (int)($data['profesor_id'] ?? 0),
            ':nombre' => trim((string)($data['nombre'] ?? '')),
            ':ciclo' => trim((string)($data['ciclo'] ?? '')),
            ':cupo' => (int)($data['cupo'] ?? 30),
            ':aula_id' => ($data['aula_id'] ?? 0) > 0 ? (int)$data['aula_id'] : null,
        ]);
    }

    public function update(int $id, array $data): bool
    {
        if ($id <= 0) { 
            $this->lastError = 'ID de grupo inválido'; 
            Logger::info('group_validation_failed', ['reason' => 'invalid_id', 'id' => $id]); 
            return false; 
        }
        if (!$this->validate($data, $id)) { return false; }
        $stmt = $this->pdo->prepare('UPDATE grupos SET materia_id = :materia_id, profesor_id = :profesor_id, nombre = :nombre, ciclo = :ciclo, cupo = :cupo, aula_id = :aula_id WHERE id = :id');
        return $stmt->execute([
            ':id' => $id,
            ':materia_id' => (int)($data['materia_id'] ?? 0),
            ':profesor_id' => (int)($data['profesor_id'] ?? 0),
            ':nombre' => trim((string)($data['nombre'] ?? '')),
            ':ciclo' => trim((string)($data['ciclo'] ?? '')),
            ':cupo' => (int)($data['cupo'] ?? 30),
            ':aula_id' => ($data['aula_id'] ?? 0) > 0 ? (int)$data['aula_id'] : null,
        ]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM grupos WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }
}