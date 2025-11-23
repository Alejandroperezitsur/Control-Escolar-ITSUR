<?php
namespace App\Services;

use PDO;

class SubjectsService
{
    private PDO $pdo;
    private ?string $lastError = null;
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function count(): int
    {
        return (int)$this->pdo->query('SELECT COUNT(*) FROM materias')->fetchColumn();
    }

    public function all(int $page = 1, int $limit = 10, string $sort = 'nombre', string $order = 'ASC'): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $allowedSorts = ['id','nombre','clave'];
        $sort = strtolower(trim($sort));
        $order = strtoupper(trim($order));
        if (!in_array($sort, $allowedSorts, true)) { $sort = 'nombre'; }
        if (!in_array($order, ['ASC','DESC'], true)) { $order = 'ASC'; }
        $stmt = $this->pdo->prepare("SELECT * FROM materias ORDER BY $sort $order LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countSearch(string $q = ''): int
    {
        if ($q === '') { return $this->count(); }
        $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM materias WHERE nombre LIKE :q1 OR clave LIKE :q2');
        $like = '%' . $q . '%';
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->execute();
        return (int)$stmt->fetchColumn();
    }

    public function allSearch(int $page, int $limit, string $q = '', string $sort = 'nombre', string $order = 'ASC'): array
    {
        $offset = max(0, ($page - 1) * $limit);
        $allowedSorts = ['id','nombre','clave'];
        $sort = strtolower(trim($sort));
        $order = strtoupper(trim($order));
        if (!in_array($sort, $allowedSorts, true)) { $sort = 'nombre'; }
        if (!in_array($order, ['ASC','DESC'], true)) { $order = 'ASC'; }
        if ($q === '') { return $this->all($page, $limit, $sort, $order); }
        $stmt = $this->pdo->prepare("SELECT * FROM materias WHERE nombre LIKE :q1 OR clave LIKE :q2 ORDER BY $sort $order LIMIT :limit OFFSET :offset");
        $like = '%' . $q . '%';
        $stmt->bindValue(':q1', $like, PDO::PARAM_STR);
        $stmt->bindValue(':q2', $like, PDO::PARAM_STR);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create(array $data): bool
    {
        $nombre = trim((string)($data['nombre'] ?? ''));
        $clave = trim((string)($data['clave'] ?? ''));
        if ($nombre === '' || $clave === '') { $this->lastError = 'Nombre y clave son obligatorios'; return false; }
        $sel = $this->pdo->prepare('SELECT 1 FROM materias WHERE clave = :clave LIMIT 1');
        $sel->execute([':clave' => $clave]);
        if ($sel->fetchColumn()) { $this->lastError = 'La clave ya existe'; return false; }
        $stmt = $this->pdo->prepare('INSERT INTO materias (nombre, clave) VALUES (:nombre, :clave)');
        return $stmt->execute([':nombre' => $nombre, ':clave' => $clave]);
    }

    public function update(int $id, array $data): bool
    {
        $nombre = trim((string)($data['nombre'] ?? ''));
        $clave = trim((string)($data['clave'] ?? ''));
        if ($id <= 0) { $this->lastError = 'ID inválido'; return false; }
        if ($nombre === '' || $clave === '') { $this->lastError = 'Nombre y clave son obligatorios'; return false; }
        $sel = $this->pdo->prepare('SELECT 1 FROM materias WHERE clave = :clave AND id <> :id LIMIT 1');
        $sel->execute([':clave' => $clave, ':id' => $id]);
        if ($sel->fetchColumn()) { $this->lastError = 'La clave ya existe'; return false; }
        $stmt = $this->pdo->prepare('UPDATE materias SET nombre = :nombre, clave = :clave WHERE id = :id');
        return $stmt->execute([':id' => $id, ':nombre' => $nombre, ':clave' => $clave]);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM materias WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }
}
