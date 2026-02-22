<?php

namespace App\Repositories;

use PDO;

class StudentsRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function paginate(string $search, string $status, string $sort, string $order, int $page, int $limit): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $conditions = [];
        $params = [];
        if ($search !== '') {
            $conditions[] = "(matricula LIKE :s1 OR nombre LIKE :s2 OR apellido LIKE :s3 OR email LIKE :s4)";
            $like = '%' . $search . '%';
            $params[':s1'] = $like;
            $params[':s2'] = $like;
            $params[':s3'] = $like;
            $params[':s4'] = $like;
        }
        if ($status === 'active') {
            $conditions[] = "activo = 1";
        } elseif ($status === 'inactive') {
            $conditions[] = "activo = 0";
        }
        $where = $conditions ? ('WHERE ' . implode(' AND ', $conditions)) : '';
        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM alumnos $where");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();
        $totalPages = $limit > 0 ? (int)ceil($total / $limit) : 1;
        $allowedSorts = ['matricula', 'nombre', 'apellido', 'email', 'activo'];
        if (!in_array($sort, $allowedSorts, true)) {
            $sort = 'apellido';
        }
        if (!in_array($order, ['ASC', 'DESC'], true)) {
            $order = 'ASC';
        }
        $orderBy = $sort . ' ' . $order;
        if ($sort === 'apellido') {
            $orderBy .= ', nombre ASC';
        }
        $sql = "SELECT id, matricula, nombre, apellido, email, activo FROM alumnos $where ORDER BY $orderBy LIMIT :limit OFFSET :offset";
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return [
            'students' => $students,
            'total' => $total,
            'total_pages' => $totalPages,
            'page' => $page,
            'per_page' => $limit,
            'search' => $search,
            'status' => $status,
            'sort' => $sort,
            'order' => $order,
        ];
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, matricula, nombre, apellido, email, activo, fecha_nac, foto FROM alumnos WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }

    public function create(array $data): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM alumnos WHERE matricula = :m');
        $stmt->execute([':m' => $data['matricula']]);
        if ($stmt->fetch()) {
            return ['ok' => false, 'error' => 'La matrícula ya existe'];
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
            ':activo' => isset($data['activo']) ? 1 : 0,
        ]);
        if (!$res) {
            return ['ok' => false, 'error' => 'Error al crear alumno'];
        }
        return ['ok' => true];
    }

    public function update(int $id, array $data): array
    {
        $stmt = $this->pdo->prepare('SELECT id FROM alumnos WHERE matricula = :m AND id != :id');
        $stmt->execute([':m' => $data['matricula'], ':id' => $id]);
        if ($stmt->fetch()) {
            return ['ok' => false, 'error' => 'La matrícula ya existe'];
        }
        $fields = "matricula = :matricula, nombre = :nombre, apellido = :apellido, email = :email, activo = :activo";
        $params = [
            ':matricula' => $data['matricula'],
            ':nombre' => $data['nombre'],
            ':apellido' => $data['apellido'],
            ':email' => $data['email'] ?? null,
            ':activo' => isset($data['activo']) ? 1 : 0,
            ':id' => $id,
        ];
        if (!empty($data['password'])) {
            $fields .= ", password = :password";
            $params[':password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }
        $sql = "UPDATE alumnos SET $fields WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        if (!$stmt->execute($params)) {
            return ['ok' => false, 'error' => 'Error al actualizar alumno'];
        }
        return ['ok' => true];
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare("DELETE FROM alumnos WHERE id = :id");
        return $stmt->execute([':id' => $id]);
    }

    public function getSimpleById(int $id): ?array
    {
        $stmt = $this->pdo->prepare("SELECT id, matricula, nombre, apellido, email, activo FROM alumnos WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row !== false ? $row : null;
    }
}

