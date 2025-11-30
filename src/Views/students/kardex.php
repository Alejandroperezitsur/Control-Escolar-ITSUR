<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kardex - <?= htmlspecialchars($alumno['nombre'] ?? '') ?> <?= htmlspecialchars($alumno['apellido'] ?? '') ?></title>
    <link rel="stylesheet" href="/public/assets/css/styles.css">
    <style>
        .kardex-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .stats-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stats-card h3 {
            font-size: 2rem;
            margin: 0;
            color: #667eea;
        }
        .stats-card p {
            margin: 0.5rem 0 0;
            color: #666;
        }
        .materia-aprobada {
            background-color: #d4edda;
        }
        .materia-reprobada {
            background-color: #f8d7da;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layout.php'; ?>
    
    <div class="container mt-4">
        <div class="kardex-header">
            <div class="row">
                <div class="col-md-8">
                    <h2><i class="fas fa-graduation-cap"></i> Kardex Académico</h2>
                    <h4><?= htmlspecialchars($alumno['nombre'] ?? '') ?> <?= htmlspecialchars($alumno['apellido'] ?? '') ?></h4>
                    <p class="mb-0">
                        <strong>Matrícula:</strong> <?= htmlspecialchars($alumno['matricula'] ?? '') ?> &nbsp;|&nbsp;
                        <strong>Carrera:</strong> <?= htmlspecialchars($alumno['carrera_nombre'] ?? 'N/A') ?>
                    </p>
                </div>
                <div class="col-md-4 text-right">
                    <button class="btn btn-light" onclick="window.print()">
                        <i class="fas fa-print"></i> Imprimir
                    </button>
                    <button class="btn btn-light" onclick="window.history.back()">
                        <i class="fas fa-arrow-left"></i> Regresar
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Estadísticas -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?= number_format($promedioAcumulado, 2) ?></h3>
                    <p>Promedio General</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?= $creditosAcumulados ?></h3>
                    <p>Créditos Acumulados</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stats-card">
                    <h3><?= $totalMaterias ?></h3>
                    <p>Materias Aprobadas</p>
                </div>
            </div>
        </div>
        
        <!-- Historial Académico -->
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="fas fa-history"></i> Historial Académico</h5>
            </div>
            <div class="card-body">
                <?php if (empty($entries)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> No hay registros en el kardex. Las calificaciones finales se sincronizarán automáticamente.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm">
                            <thead class="thead-light">
                                <tr>
                                    <th>Periodo</th>
                                    <th>Clave</th>
                                    <th>Materia</th>
                                    <th>Créditos</th>
                                    <th>Calificación</th>
                                    <th>Estatus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $currentPeriodo = null;
                                foreach ($entries as $entry): 
                                    $rowClass = ($entry['estatus'] === 'Aprobada') ? 'materia-aprobada' : 'materia-reprobada';
                                    
                                    // Encabezado de periodo
                                    if ($currentPeriodo !== $entry['periodo']): 
                                        $currentPeriodo = $entry['periodo'];
                                ?>
                                    <tr class="table-secondary">
                                        <td colspan="6"><strong><?= htmlspecialchars($currentPeriodo) ?></strong></td>
                                    </tr>
                                <?php endif; ?>
                                
                                <tr class="<?= $rowClass ?>">
                                    <td><?= htmlspecialchars($entry['periodo']) ?></td>
                                    <td><?= htmlspecialchars($entry['materia_clave']) ?></td>
                                    <td><?= htmlspecialchars($entry['materia_nombre']) ?></td>
                                    <td><?= htmlspecialchars($entry['creditos']) ?></td>
                                    <td class="text-center"><strong><?= number_format($entry['calificacion'], 0) ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?= ($entry['estatus'] === 'Aprobada') ? 'success' : 'danger' ?>">
                                            <?= htmlspecialchars($entry['estatus']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="mt-3 text-muted">
            <small>
                <i class="fas fa-info-circle"></i> 
                El kardex se actualiza automáticamente cuando el profesor captura la calificación final.
            </small>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
