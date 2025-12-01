<?php
/**
 * Kardex View - Complete Academic History
 * Shows all subjects ever taken with grades, credits, and statistics
 */

// Ensure we have the data from controller
$alumno = $viewData['alumno'] ?? [];
$stats = $viewData['stats'] ?? [];
$history = $viewData['history'] ?? [];
$historySemester = $viewData['historySemester'] ?? [];
$currentCredits = $viewData['currentCredits'] ?? 0;
$pageTitle = $viewData['pageTitle'] ?? 'Kardex';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Control Escolar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .stats-card {
            border-left: 4px solid #0d6efd;
            transition: all 0.3s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        .aprobado { background-color: #d4edda; }
        .reprobado { background-color: #f8d7da; }
        .cursando { background-color: #d1ecf1; }
        .semestre-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            margin-top: 30px;
            margin-bottom: 15px;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <h1 class="display-5 mb-0">
                <i class="bi bi-journal-text"></i> Kardex - Historial Académico
            </h1>
            <p class="text-muted">
               <?= htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']) ?> - 
                <?= htmlspecialchars($alumno['matricula']) ?>
            </p>
        </div>
        <div class="col-auto">
            <a href="/dashboard" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Regresar
            </a>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-trophy-fill text-warning fs-2"></i>
                    <h3 class="mt-2 mb-0"><?= number_format($stats['promedio_general'] ?? 0, 2) ?></h3>
                    <small class="text-muted">Promedio General</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-award-fill text-info fs-2"></i>
                    <h3 class="mt-2 mb-0"><?= $stats['creditos_completados'] ?? 0 ?> / <?= $stats['creditos_requeridos'] ?? 240 ?></h3>
                    <small class="text-muted">Créditos Completados</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-percent text-success fs-2"></i>
                    <h3 class="mt-2 mb-0"><?= number_format($stats['porcentaje_avance'] ?? 0, 1) ?>%</h3>
                    <small class="text-muted">Avance de Carrera</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card stats-card h-100">
                <div class="card-body text-center">
                    <i class="bi bi-calendar3 text-primary fs-2"></i>
                    <h3 class="mt-2 mb-0"><?= $stats['semestre_actual'] ?? 1 ?>°</h3>
                    <small class="text-muted">Semestre Actual</small>
                </div>
            </div>
        </div>
    </div>

    <!-- Summary Row -->
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Materias Cursadas</small>
                            <h4 class="mb-0"><?= $stats['total_materias_cursadas'] ?? 0 ?></h4>
                        </div>
                        <i class="bi bi-book-fill text-primary fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Materias Aprobadas</small>
                            <h4 class="mb-0 text-success"><?= $stats['materias_aprobadas'] ?? 0 ?></h4>
                        </div>
                        <i class="bi bi-check-circle-fill text-success fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <small class="text-muted">Créditos Actuales</small>
                            <h4 class="mb-0 text-info"><?= $currentCredits ?></h4>
                        </div>
                        <i class="bi bi-clipboard-check-fill text-info fs-1"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Academic History by Semester -->
    <?php foreach ($historySemester as $semestre => $subjects): ?>
        <div class="semestre-header">
            <h4 class="mb-0">
                <i class="bi bi-bookmark-fill"></i> Semestre <?= $semestre ?>
            </h4>
        </div>
        
        <div class="card mb-4">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Clave</th>
                                <th>Materia</th>
                                <th>Créditos</th>
                                <th>Período</th>
                                <th class="text-center">Promedio</th>
                                <th class="text-center">Final</th>
                                <th class="text-center">General</th>
                                <th>Nivel</th>
                                <th>Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subjects as $subject): ?>
                                <?php
                                    $statusClass = '';
                                    if ($subject['estatus'] == 'aprobado') $statusClass = 'aprobado';
                                    elseif ($subject['estatus'] == 'reprobado') $statusClass = 'reprobado';
                                    elseif ($subject['estatus'] == 'cursando') $statusClass = 'cursando';
                                ?>
                                <tr class="<?= $statusClass ?>">
                                    <td><code><?= htmlspecialchars($subject['materia_clave']) ?></code></td>
                                    <td><strong><?= htmlspecialchars($subject['materia_nombre']) ?></strong></td>
                                    <td><?= $subject['creditos'] ?></td>
                                    <td><small><?= htmlspecialchars($subject['periodo_acreditacion'] ?? $subject['ciclo']) ?></small></td>
                                    <td class="text-center">
                                        <?= $subject['promedio_unidades'] ? number_format($subject['promedio_unidades'], 1) : '-' ?>
                                    </td>
                                    <td class="text-center">
                                        <?= $subject['calificacion_final'] ? number_format($subject['calificacion_final'], 1) : '-' ?>
                                    </td>
                                    <td class="text-center">
                                        <strong><?= $subject['promedio_general'] ? number_format($subject['promedio_general'], 1) : '-' ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary"><?= htmlspecialchars($subject['nivel_desempeno']) ?></span>
                                    </td>
                                    <td>
                                        <?php if ($subject['estatus'] == 'aprobado'): ?>
                                            <span class="badge bg-success">Aprobado</span>
                                        <?php elseif ($subject['estatus'] == 'reprobado'): ?>
                                            <span class="badge bg-danger">Reprobado</span>
                                        <?php elseif ($subject['estatus'] == 'cursando'): ?>
                                            <span class="badge bg-info">Cursando</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($subject['estatus']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php if (empty($history)): ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No hay registros académicos disponibles.
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
