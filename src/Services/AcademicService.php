<?php
namespace App\Services;

use PDO;

class AcademicService
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function assertActiveCycleForGroup(int $grupoId): void
    {
        $stmt = $this->pdo->prepare('SELECT ciclo_id FROM grupos WHERE id = :id');
        $stmt->execute([':id' => $grupoId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            throw new \RuntimeException('Grupo no existe');
        }
        $cicloId = (int)($row['ciclo_id'] ?? 0);
        if ($cicloId <= 0) {
            throw new \RuntimeException('Grupo sin ciclo asignado');
        }
        $stActive = $this->pdo->prepare('SELECT id FROM ciclos_escolares WHERE id = :id AND activo = 1 LIMIT 1');
        $stActive->execute([':id' => $cicloId]);
        if (!$stActive->fetchColumn()) {
            throw new \RuntimeException('Ciclo no activo para el grupo');
        }
    }
}

