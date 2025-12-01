<?php
/**
 * Academic Load View (Carga Académica) - Current Semester Subjects
 * Shows enrolled subjects with schedules, professors, and classrooms
 */

$alumno = $viewData['alumno'] ?? [];
$cargaAcademica = $viewData['cargaAcademica'] ?? [];
$totalCredits = $viewData['totalCredits'] ?? 0;
$scheduleGrid = $viewData['scheduleGrid'] ?? [];
$pageTitle = $viewData['pageTitle'] ?? 'Carga Académica';
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
        .subject-card {
            border-left: 5px solid #0d6efd;
            transition: all 0.3s;
        }
        .subject-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }
        .schedule-item {
            background-color: #f8f9fa;
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 5px;
            border-left: 3px solid #0d6efd;
        }
        .day-column {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body class="bg-light">

<div class="container py-5">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col">
            <h1 class="display-5 mb-0">
                <i class="bi bi-calendar-week"></i> Carga Académica
            </h1>
            <p class="text-muted">
                <?= htmlspecialchars($alumno['nombre'] . ' ' . $alumno['apellido']) ?> - 
                Semestre Actual
            </p>
        </div>
        <div class="col-auto">
            <a href="/dashboard" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Regresar
            </a>
        </div>
    </div>

    <!-- Total Credits -->
    <div class="alert alert-info mb-4">
        <i class="bi bi-award-fill"></i> 
        <strong>Créditos del Semestre:</strong> <?= $totalCredits ?> créditos totales
    </div>

    <!-- Subjects List -->
    <h3 class="mb-3">Materias Inscritas</h3>
    <div class="row g-3 mb-5">
        <?php foreach ($cargaAcademica as $subject): ?>
            <div class="col-md-6">
                <div class="card subject-card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="card-title mb-1"><?= htmlspecialchars($subject['materia_nombre']) ?></h5>
                                <p class="text-muted mb-0">
                                    <code><?= htmlspecialchars($subject['materia_clave']) ?></code>
                                </p>
                            </div>
                            <span class="badge bg-primary fs-6"><?= $subject['creditos'] ?> créditos</span>
                        </div>
                        
                        <hr>
                        
                        <div class="mb-2">
                            <i class="bi bi-person-badge text-primary"></i>
                            <strong>Profesor:</strong> <?= htmlspecialchars($subject['profesor_nombre']) ?>
                        </div>
                        
                        <div class="mb-2">
                            <i class="bi bi-people-fill text-success"></i>
                            <strong>Grupo:</strong> <?= htmlspecialchars($subject['grupo_nombre']) ?>
                        </div>
                        
                        <div class="mb-2">
                            <i class="bi bi-calendar3 text-warning"></i>
                            <strong>Ciclo:</strong> <?= htmlspecialchars($subject['ciclo']) ?>
                        </div>
                        
                        <?php if ($subject['horarios']): ?>
                            <div class="mt-3">
                                <div class="fw-bold mb-2">
                                    <i class="bi bi-clock-fill text-info"></i> Horarios:
                                </div>
                                <?php
                                    $horarios = explode('; ', $subject['horarios']);
                                    foreach ($horarios as $horario):
                                ?>
                                    <div class="schedule-item">
                                        <small><?= htmlspecialchars($horario) ?></small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($cargaAcademica)): ?>
        <div class="alert alert-warning">
            <i class="bi bi-exclamation-triangle"></i> No tienes materias inscritas en este semestre.
        </div>
    <?php else: ?>
        <!-- Weekly Schedule Grid -->
        <h3 class="mb-3">Horario Semanal</h3>
        <div class="card">
            <div class="card-body">
                <div class="row g-3">
                    <?php
                        $dias_espanol = [
                            'lunes' => 'Lunes',
                            'martes' => 'Martes',
                            'miércoles' => 'Miércoles',
                            'jueves' => 'Jueves',
                            'viernes' => 'Viernes',
                            'sábado' => 'Sábado'
                        ];
                        
                        foreach ($scheduleGrid as $dia => $horarios):
                            if (empty($horarios)) continue;
                    ?>
                        <div class="col-md-4">
                            <div class="day-column mb-2">
                                <?= $dias_espanol[$dia] ?? ucfirst($dia) ?>
                            </div>
                            <?php foreach ($horarios as $h): ?>
                                <div class="card mb-2">
                                    <div class="card-body p-2">
                                        <div class="fw-bold"><?= htmlspecialchars($h['materia']) ?></div>
                                        <small class="text-muted">
                                            <i class="bi bi-clock"></i> 
                                            <?= date('H:i', strtotime($h['hora_inicio'])) ?> - 
                                            <?= date('H:i', strtotime($h['hora_fin'])) ?>
                                        </small><br>
                                        <small class="text-muted">
                                            <i class="bi bi-geo-alt"></i> <?= htmlspecialchars($h['aula']) ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
