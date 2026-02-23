<?php
namespace App\Controllers;

use PDO;
use App\Utils\Logger;
use App\Http\Request;

class ChartsController
{
    private PDO $pdo;
    private int $ttlSeconds = 300; // 5 minutos
    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
        $_SESSION['charts_cache'] = $_SESSION['charts_cache'] ?? [];
    }

    private function cacheKey(string $name, array $filters = []): string
    {
        return $name . ':' . md5(json_encode($filters));
    }

    private function getCache(string $key): ?array
    {
        $entry = $_SESSION['charts_cache'][$key] ?? null;
        if (!$entry) { return null; }
        if ((time() - (int)$entry['ts']) > $this->ttlSeconds) { return null; }
        return (array)$entry['data'];
    }

    private function setCache(string $key, array $data): void
    {
        $_SESSION['charts_cache'][$key] = ['ts' => time(), 'data' => $data];
    }

    public function averagesBySubject(): void
    {
        header('Content-Type: application/json');
        $sql = "SELECT m.nombre AS materia, ROUND(AVG(c.final),2) AS promedio
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                WHERE c.final IS NOT NULL
                GROUP BY m.id, m.nombre
                ORDER BY m.nombre";
        $stmt = $this->pdo->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
    }

    // /api/charts/promedios-ciclo
    public function averagesByCycle(): string
    {
        $gid = Request::getInt('grupo_id');
        $ciclo = Request::getString('ciclo');
        $mid = Request::getInt('materia_id');
        $estadoRaw = Request::getString('estado', '');
        $estado = strtolower(trim((string)$estadoRaw));
        $role = $_SESSION['role'] ?? '';
        $pid = (int)($_SESSION['user_id'] ?? 0);
        $filters = ['gid' => $gid, 'ciclo' => $ciclo, 'mid' => $mid, 'estado' => $estado, 'pid' => ($role==='profesor'?$pid:null)];
        $key = $this->cacheKey('promedios_ciclo', $filters);
        $cached = $this->getCache($key);
        if ($cached) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'data' => $cached]);
        }
        $avgExpr = ($estado === 'pendientes') ? 'ROUND(AVG(COALESCE(c.promedio, ROUND((IFNULL(c.parcial1,0)+IFNULL(c.parcial2,0))/2,2))),2) AS promedio' : 'ROUND(AVG(c.final),2) AS promedio';
        $sql = "SELECT g.ciclo, $avgExpr
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                WHERE 1=1";
        $params = [];
        if ($estado === 'pendientes') { $sql .= ' AND c.final IS NULL'; } else { $sql .= ' AND c.final IS NOT NULL'; }
        if ($gid) { $sql .= ' AND g.id = :gid'; $params[':gid'] = $gid; }
        if ($ciclo) { $sql .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        if ($mid) { $sql .= ' AND g.materia_id = :mid'; $params[':mid'] = $mid; }
        if ($role === 'profesor' && $pid > 0) { $sql .= ' AND g.profesor_id = :pid'; $params[':pid'] = $pid; }
        $sql .= ' GROUP BY g.ciclo ORDER BY g.ciclo';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [
            'labels' => array_map(fn($r) => $r['ciclo'], $rows),
            'data' => array_map(fn($r) => (float)$r['promedio'], $rows),
        ];
        $this->setCache($key, $data);
        Logger::info('chart_query', ['type' => 'promedios_ciclo']);
        header('Content-Type: application/json');
        return json_encode(['ok' => true, 'data' => $data]);
    }

    // /api/charts/desempeño-grupo (profesor)
    public function performanceByProfessorGroups(): string
    {
        $role = $_SESSION['role'] ?? '';
        $pid = (int)($_SESSION['user_id'] ?? 0);
        if ($role !== 'profesor' || $pid <= 0) {
            header('Content-Type: application/json');
            http_response_code(403);
            return json_encode(['ok' => false, 'message' => 'No autorizado']);
        }
        $gid = Request::getInt('grupo_id');
        $ciclo = Request::getString('ciclo');
        $mid = Request::getInt('materia_id');
        $estadoRaw = Request::getString('estado', '');
        $estado = strtolower(trim((string)$estadoRaw));
        $key = $this->cacheKey('desempeno_grupo', ['pid' => $pid, 'gid' => $gid, 'ciclo' => $ciclo, 'mid' => $mid, 'estado' => $estado]);
        $cached = $this->getCache($key);
        if ($cached) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'data' => $cached]);
        }
        $avgExpr = ($estado === 'pendientes') ? 'ROUND(AVG(COALESCE(c.promedio, ROUND((IFNULL(c.parcial1,0)+IFNULL(c.parcial2,0))/2,2))),2)' : 'ROUND(AVG(c.final),2)';
        $sql = "SELECT g.nombre AS grupo, $avgExpr AS promedio
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                WHERE g.profesor_id = :pid";
        $params = [':pid' => $pid];
        if ($estado === 'pendientes') { $sql .= ' AND c.final IS NULL'; } else { $sql .= ' AND c.final IS NOT NULL'; }
        if ($gid) { $sql .= ' AND g.id = :gid'; $params[':gid'] = $gid; }
        if ($ciclo) { $sql .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        if ($mid) { $sql .= ' AND g.materia_id = :mid'; $params[':mid'] = $mid; }
        $sql .= ' GROUP BY g.id, g.nombre ORDER BY g.nombre';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [
            'labels' => array_map(fn($r) => $r['grupo'], $rows),
            'data' => array_map(fn($r) => (float)$r['promedio'], $rows),
        ];
        $this->setCache($key, $data);
        Logger::info('chart_query', ['type' => 'desempeno_grupo', 'pid' => $pid]);
        header('Content-Type: application/json');
        return json_encode(['ok' => true, 'data' => $data]);
    }

    // /api/charts/reprobados
    public function failRateBySubject(): string
    {
        $role = $_SESSION['role'] ?? '';
        $pid = (int)($_SESSION['user_id'] ?? 0);
        $ciclo = Request::getString('ciclo');
        $gid = Request::getInt('grupo_id');
        $mid = Request::getInt('materia_id');
        $estadoRaw = Request::getString('estado', '');
        $estado = strtolower(trim((string)$estadoRaw));
        $filters = ['ciclo' => $ciclo, 'gid' => $gid, 'mid' => $mid, 'estado' => $estado, 'pid' => ($role==='profesor'?$pid:null)];
        $key = $this->cacheKey('reprobados_materia', $filters);
        $cached = $this->getCache($key);
        if ($cached) {
            header('Content-Type: application/json');
            return json_encode(['ok' => true, 'data' => $cached]);
        }
        $expr = ($estado === 'pendientes')
            ? 'ROUND(SUM(CASE WHEN c.final IS NULL THEN 1 ELSE 0 END) / NULLIF(COUNT(*),0) * 100, 2)'
            : 'ROUND(SUM(CASE WHEN c.final IS NOT NULL AND c.final < 70 THEN 1 ELSE 0 END) / NULLIF(COUNT(CASE WHEN c.final IS NOT NULL THEN 1 END),0) * 100, 2)';
        $sql = "SELECT m.nombre AS materia, $expr AS porcentaje
                FROM calificaciones c
                JOIN grupos g ON g.id = c.grupo_id
                JOIN materias m ON m.id = g.materia_id
                WHERE 1=1";
        $params = [];
        if ($role === 'profesor' && $pid > 0) { $sql .= ' AND g.profesor_id = :pid'; $params[':pid'] = $pid; }
        if ($ciclo) { $sql .= ' AND g.ciclo = :ciclo'; $params[':ciclo'] = $ciclo; }
        if ($gid) { $sql .= ' AND g.id = :gid'; $params[':gid'] = $gid; }
        if ($mid) { $sql .= ' AND g.materia_id = :mid'; $params[':mid'] = $mid; }
        $sql .= ' GROUP BY m.id, m.nombre ORDER BY m.nombre';
        $stmt = $this->pdo->prepare($sql);
        foreach ($params as $k=>$v) { $stmt->bindValue($k, $v); }
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [
            'labels' => array_map(fn($r) => $r['materia'], $rows),
            'data' => array_map(fn($r) => (float)$r['porcentaje'], $rows),
        ];
        $this->setCache($key, $data);
        Logger::info('chart_query', ['type' => 'reprobados_materia']);
        header('Content-Type: application/json');
        return json_encode(['ok' => true, 'data' => $data]);
    }
}
