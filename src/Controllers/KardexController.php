<?php
namespace App\Controllers;

use PDO;

class KardexController
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function index(): string
    {
        $role = $_SESSION['role'] ?? '';
        if ($role !== 'alumno') {
            http_response_code(403);
            return 'No autorizado';
        }
        $alumnoId = (int)($_SESSION['user_id'] ?? 0);
        if ($alumnoId <= 0) {
            http_response_code(403);
            return 'No autorizado';
        }
        $sqlDetalle = "SELECT g.ciclo,
                              g.nombre AS grupo,
                              m.nombre AS materia,
                              m.clave AS materia_clave,
                              c.parcial1,
                              c.parcial2,
                              c.final,
                              CASE
                                  WHEN c.final IS NULL THEN 'En curso'
                                  WHEN c.final >= 70 THEN 'Aprobada'
                                  ELSE 'Reprobada'
                              END AS estado,
                              COALESCE(cred.creditos, 0) AS creditos
                       FROM calificaciones c
                       JOIN grupos g ON g.id = c.grupo_id
                       JOIN materias m ON m.id = g.materia_id
                       LEFT JOIN (
                           SELECT materia_id, MAX(creditos) AS creditos
                           FROM materias_carrera
                           GROUP BY materia_id
                       ) cred ON cred.materia_id = m.id
                       WHERE c.alumno_id = :alumno
                       ORDER BY g.ciclo ASC, m.nombre ASC, g.nombre ASC";
        $stmtDet = $this->pdo->prepare($sqlDetalle);
        $stmtDet->execute([':alumno' => $alumnoId]);
        $rows = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        $sqlAgg = "SELECT
                      ROUND(AVG(c.final), 2) AS promedio_general,
                      SUM(CASE WHEN c.final IS NOT NULL AND c.final >= 70 THEN COALESCE(cred.creditos, 0) ELSE 0 END) AS creditos_aprobados
                   FROM calificaciones c
                   JOIN grupos g ON g.id = c.grupo_id
                   JOIN materias m ON m.id = g.materia_id
                   LEFT JOIN (
                       SELECT materia_id, MAX(creditos) AS creditos
                       FROM materias_carrera
                       GROUP BY materia_id
                   ) cred ON cred.materia_id = m.id
                   WHERE c.alumno_id = :alumno AND c.final IS NOT NULL";
        $stmtAgg = $this->pdo->prepare($sqlAgg);
        $stmtAgg->execute([':alumno' => $alumnoId]);
        $agg = $stmtAgg->fetch(PDO::FETCH_ASSOC) ?: [];
        $promedioGeneral = $agg['promedio_general'] !== null ? (float)$agg['promedio_general'] : 0.0;
        $creditosAprobados = $agg['creditos_aprobados'] !== null ? (int)$agg['creditos_aprobados'] : 0;

        ob_start();
        $kardexRows = $rows;
        $kardexPromedio = $promedioGeneral;
        $kardexCreditos = $creditosAprobados;
        include __DIR__ . '/../Views/student/kardex.php';
        return ob_get_clean();
    }
}

