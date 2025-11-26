<?php
header('Location: app.php?r=/dashboard');
exit;
// KPIs globales (solo admin)
$estadisticas = $controlCal->obtenerAgregadosGlobales();
$promediosPorCiclo = $controlCal->obtenerPromediosPorCiclo();
// Catálogo de ciclos y filtros
$cicloSeleccionado = trim((string)($_GET['ciclo'] ?? ''));
if ($cicloSeleccionado !== '') {
    // Si hay ciclo seleccionado, recalculamos KPIs para ese ciclo
    $estadisticasCiclo = $controlCal->obtenerAgregadosPorCiclo($cicloSeleccionado);
    if ($estadisticasCiclo) {
        $estadisticas = $estadisticasCiclo;
    }
    // Limitar gráfica de promedio por ciclo al ciclo seleccionado
    $promediosFiltrados = array_filter($promediosPorCiclo, function($pc) use ($cicloSeleccionado) {
        return (string)($pc['ciclo'] ?? '') === $cicloSeleccionado;
    });
    $promediosPorCiclo = $promediosFiltrados ?: [];
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
    <title>SICEnet · ITSUR — Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
          rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" 
          rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <link href="assets/css/desktop-fixes.css" rel="stylesheet">
    <style>
    /* impresión: solo listado/tabla en páginas con clase .print-list */
    @media print {
      body * { visibility: hidden; }
      .print-list, .print-list * { visibility: visible; }
      .print-list { position: absolute; left: 0; top: 0; width: 100%; }
      .btn, .form-control, .form-select, nav, header, .app-sidebar { display: none !important; }
    }
    </style>
</head>
<body>
    <?php require __DIR__ . '/partials/header.php'; ?>

    <div class="app-shell">
        <!-- Sidebar removed: todas las operaciones están ahora disponibles como tarjetas en el Dashboard -->
        <main class="app-content">
                    <?php if ($esAdmin): ?>
        <div class="card mb-4 border-0 shadow-sm">
          <div class="card-body">
            <div class="row align-items-center">
              <div class="col-md-8">
                <h6 class="card-title mb-2">
                  <i class="bi bi-calendar3 me-2"></i>Filtro de Ciclo Académico
                </h6>
                <p class="text-muted small mb-0">
                  Selecciona un ciclo específico para analizar el rendimiento académico de ese período
                </p>
              </div>
              <div class="col-md-4">
                <form method="get" action="dashboard.php" class="d-flex gap-2">
                  <select name="ciclo" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">📊 Todos los ciclos</option>
                    <?php foreach ($ciclosCatalog as $c): $val = trim((string)($c['ciclo'] ?? $c)); ?>
                      <option value="<?= htmlspecialchars($val) ?>" <?= $val === $cicloSeleccionado ? 'selected' : '' ?>>
                        📅 <?= htmlspecialchars($val) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                                    <?php if ($cicloSeleccionado): ?>
                    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm" title="Limpiar filtro">
                      <i class="bi bi-x-circle"></i>
                    </a>
                  <?php endif; ?>
                </form>
              </div>
            </div>
            <?php if ($cicloSeleccionado): ?>
              <div class="alert alert-info mt-3 mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Mostrando datos del ciclo: <strong><?= htmlspecialchars($cicloSeleccionado) ?></strong>
              </div>
            <?php endif; ?>
          </div>
        </div>
        <?php endif; ?>
        <!-- Bloque de navegación rápida: tarjetas por rol (reemplaza el antiguo sidebar) -->
        <div class="row g-4 mb-3 dashboard-grid">
            <?php if ($esAlumno): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-journal-text text-primary"></i> Kardex</h5>
                        <p class="card-text text-muted">Consulta tu historial académico y calificaciones.</p>
                        <div class="mt-auto"><a href="kardex.php" class="btn btn-sm btn-primary">Abrir Kardex</a></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-list-check text-success"></i> Mi Carga Académica</h5>
                        <p class="card-text text-muted">Ver materias y horarios asignados.</p>
                        <div class="mt-auto"><a href="mi_carga.php" class="btn btn-sm btn-success">Ver Carga</a></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <?php if ($esProfesor): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-collection text-primary"></i> Mis Grupos</h5>
                        <p class="card-text text-muted">Gestiona tus grupos y alumnos inscritos.</p>
                        <div class="mt-auto"><a href="profesor_grupos.php" class="btn btn-sm btn-primary">Abrir Mis Grupos</a></div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-card-checklist text-danger"></i> Calificaciones</h5>
                        <p class="card-text text-muted">Registra y actualiza calificaciones de tus grupos.</p>
                        <div class="mt-auto"><a href="calificaciones.php" class="btn btn-sm btn-danger">Calificar</a></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-diagram-3 text-info"></i> Retícula</h5>
                        <p class="card-text text-muted">Consulta la retícula y requisitos por carrera.</p>
                        <div class="mt-auto"><a href="reticula.php" class="btn btn-sm btn-info">Abrir Retícula</a></div>
                    </div>
                </div>
            </div>
            <?php if ($esAlumno): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-arrow-repeat text-warning"></i> Reinscripción</h5>
                        <p class="card-text text-muted">Proceso de reinscripción y trámites académicos.</p>
                        <div class="mt-auto"><a href="reinscripcion.php" class="btn btn-sm btn-warning">Ir a Reinscripción</a></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Gestión: visible solo para admin -->
            <?php if ($esAdmin): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-people text-primary"></i> Alumnos</h5>
                        <p class="card-text text-muted">Gestiona el registro de alumnos.</p>
                        <div class="mt-auto">
                            <a href="alumnos.php" class="btn btn-sm btn-primary">Administrar</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-person-badge text-success"></i> Profesores</h5>
                        <p class="card-text text-muted">Administra la plantilla docente.</p>
                        <div class="mt-auto">
                            <a href="profesores.php" class="btn btn-sm btn-success">Administrar</a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card h-100">
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><i class="bi bi-book text-info"></i> Materias</h5>
                        <p class="card-text text-muted">Gestiona el catálogo de materias.</p>
                        <div class="mt-auto">
                            <a href="materias.php" class="btn btn-sm btn-info">Administrar</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>

        <div class="row g-4 dashboard-grid">
            <div class="col-md-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">
                            <i class="bi bi-grid-3x3 text-warning"></i> Grupos
                        </h5>
                        <p class="card-text">
                            <?= $esAdmin ? 'Administra los grupos y horarios.' : 
                                         'Ver tus grupos asignados.' ?>
                        </p>
                        <a href="grupos.php" class="btn btn-warning">
                            <?= $esAdmin ? 'Administrar' : 'Ver Grupos' ?>
                        </a>
                    </div>
                </div>
            </div>

            <?php if ($esProfesor): ?>
            <!-- KPIs específicos para profesor -->
            <div class="col-12">
                <div class="card">
                    <div class="card-header">Mis Estadísticas</div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Grupos Activos</div>
                                    <div class="fs-4"><?= isset($usuarioActual['id']) ? $controlGrupos->contarGruposProfesor($usuarioActual['id']) : 0 ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Alumnos Totales</div>
                                    <div class="fs-4"><?= isset($usuarioActual['id']) ? $controlGrupos->contarAlumnosProfesor($usuarioActual['id']) : 0 ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Evaluaciones Pendientes</div>
                                    <div class="fs-4"><?= $controlCal->contarEvaluacionesPendientes($usuarioActual['id']) ?></div>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Promedio General</div>
                                    <div class="fs-4"><?= number_format($controlCal->obtenerPromedioProfesor($usuarioActual['id']), 2) ?></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="table-responsive mt-4">
                            <h6 class="mb-3">Mis Grupos Activos</h6>
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Materia</th>
                                        <th>Grupo</th>
                                        <th>Alumnos</th>
                                        <th>Promedio</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $gruposActivos = isset($usuarioActual['id']) ? $controlGrupos->obtenerGruposActivosProfesor($usuarioActual['id']) : [];
                                    foreach($gruposActivos as $grupo): 
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($grupo['materia'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($grupo['grupo'] ?? '') ?></td>
                                        <td><?= (int)($grupo['alumnos'] ?? 0) ?></td>
                                        <td><?= number_format((float)($grupo['promedio'] ?? 0), 2) ?></td>
                                        <td>
                                            <a href="calificaciones.php?grupo=<?= urlencode($grupo['id']) ?>" 
                                               class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil-square"></i> Calificar
                                            </a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($esAdmin): ?>
            <div class="col-12">
                <div class="card">
                    <div class="card-header">KPIs Globales</div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Calificaciones</div>
                                    <div class="fs-4"><?= (int)$estadisticas['total'] ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Promedio final</div>
                                    <div class="fs-4"><?= number_format((float)$estadisticas['promedio'], 2) ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Aprobadas</div>
                                    <div class="fs-4"><?= (int)$estadisticas['aprobados'] ?></div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="p-3 border rounded">
                                    <div class="text-muted">Reprobadas</div>
                                    <div class="fs-4"><?= (int)$estadisticas['reprobados'] ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6>Aprobadas/Reprobadas</h6>
                                <canvas id="kpi-donut" height="160"></canvas>
                            </div>
                            <div class="col-md-6">
                                <h6>Promedio por ciclo</h6>
                                <canvas id="kpi-ciclo" height="160"></canvas>
                            </div>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                          <h6 class="mb-0">Top 5 materias por promedio <?= $cicloSeleccionado !== '' ? '(' . htmlspecialchars($cicloSeleccionado) . ')' : '' ?></h6>
                          <div class="d-flex gap-2">
                             <button class="btn btn-outline-primary btn-sm" data-export="csv" data-target="#tabla-top-materias" data-filename="top_materias.csv" data-timestamp="true"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
                             <button class="btn btn-outline-secondary btn-sm" data-export="pdf" data-target="#tabla-top-materias"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
                          </div>
                        </div>
                        <div class="table-responsive print-list">
                          <table id="tabla-top-materias" class="table table-striped table-hover">
                            <thead>
                              <tr>
                                <th>Materia</th>
                                <th>Clave</th>
                                <th>Promedio</th>
                                <th>Registros</th>
                              </tr>
                            </thead>
                            <tbody>
                              <?php
                                $topMaterias = $cicloSeleccionado !== '' ? $controlCal->promediosPorMateria($cicloSeleccionado) : $controlCal->promediosPorMateria(null);
                                usort($topMaterias, function($a,$b){ return ($b['promedio'] <=> $a['promedio']); });
                                $topMaterias = array_slice($topMaterias, 0, 5);
                                foreach ($topMaterias as $tm):
                              ?>
                                <tr>
                                  <td><?= htmlspecialchars($tm['nombre'] ?? '') ?></td>
                                  <td><?= htmlspecialchars($tm['clave'] ?? '') ?></td>
                                  <td><?= htmlspecialchars(number_format((float)($tm['promedio'] ?? 0), 2)) ?></td>
                                  <td><?= htmlspecialchars((string)($tm['count'] ?? 0)) ?></td>
                                </tr>
                              <?php endforeach; ?>
                            </tbody>
                          </table>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        </main>
    </div>

    <!-- Modal Centro de Ayuda -->
    <div class="modal fade" id="helpModal" tabindex="-1" aria-labelledby="helpModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="helpModalLabel">
              <i class="bi bi-question-circle me-2"></i>Centro de Ayuda - Dashboard
            </h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <div class="row">
              <div class="col-md-6">
                <h6><i class="bi bi-speedometer2 me-2"></i>Indicadores Clave</h6>
                <ul class="list-unstyled">
                  <li><strong>Total de Calificaciones:</strong> Número total de evaluaciones registradas</li>
                  <li><strong>Promedio General:</strong> Promedio de todas las calificaciones</li>
                  <li><strong>Aprobados/Reprobados:</strong> Distribución de resultados académicos</li>
                </ul>
                
                <h6 class="mt-3"><i class="bi bi-graph-up me-2"></i>Gráficas</h6>
                <ul class="list-unstyled">
                  <li><strong>Gráfica Circular:</strong> Proporción de aprobados vs reprobados</li>
                  <li><strong>Promedio por Ciclo:</strong> Evolución del rendimiento académico</li>
                </ul>
              </div>
              <div class="col-md-6">
                <h6><i class="bi bi-funnel me-2"></i>Filtros</h6>
                <ul class="list-unstyled">
                  <li><strong>Ciclo Académico:</strong> Filtra datos por período específico</li>
                  <li><strong>Exportar:</strong> Descarga reportes en Excel</li>
                </ul>
                
                <h6 class="mt-3"><i class="bi bi-lightning me-2"></i>Acciones Rápidas</h6>
                <ul class="list-unstyled">
                  <li><strong>Inscribir Materias:</strong> Proceso de inscripción de asignaturas</li>
                  <li><strong>Ver Kardex:</strong> Historial académico completo</li>
                  <li><strong>Mi Perfil:</strong> Información personal y académica</li>
                </ul>
              </div>
            </div>
            
            <div class="alert alert-info mt-3">
              <i class="bi bi-info-circle me-2"></i>
              <strong>Tip:</strong> Usa el selector de ciclo para analizar el rendimiento de períodos específicos. 
              Los datos se actualizan automáticamente al cambiar el filtro.
            </div>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
      // Fallback: bloquear accesos si por alguna razón aparece un enlace no permitido
      (function(){
        const role = '<?= htmlspecialchars($role) ?>';
        const adminOnly = ['alumnos.php','profesores.php','materias.php','admin_dashboard.php','verify_seed.php','admin_seed.php'];
        document.querySelectorAll('a[href]').forEach(a => {
          const href = a.getAttribute('href') || '';
          if (adminOnly.includes(href) && role !== 'admin') {
            a.addEventListener('click', (ev) => {
              ev.preventDefault();
              const m = document.createElement('div');
              m.className = 'modal fade'; m.tabIndex = -1; m.innerHTML = `
                <div class="modal-dialog"><div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Acceso denegado</h5>
                  <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                  <div class="modal-body"><p>No tienes permisos para acceder a esta sección. Si necesitas acceso, contacta al administrador.</p></div>
                  <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
                </div></div>`;
              document.body.appendChild(m);
              const modal = new bootstrap.Modal(m); modal.show();
              m.addEventListener('hidden.bs.modal', ()=> m.remove());
            }, { once:true });
          }
        });
      })();
    </script>
    <?php if ($esAdmin): ?>
    <script>
      (function(){
        const donutCtx = document.getElementById('kpi-donut');
        const cicloCtx = document.getElementById('kpi-ciclo');
        if (donutCtx) {
          const data = { labels:['Aprobadas','Reprobadas'], datasets:[{ data:[<?= (int)$estadisticas['aprobados'] ?>, <?= (int)$estadisticas['reprobados'] ?>], backgroundColor:['#28a745','#dc3545'] }] };
          new Chart(donutCtx, { type:'doughnut', data });
        }
        if (cicloCtx) {
          const src = <?= json_encode($promediosPorCiclo, JSON_UNESCAPED_UNICODE) ?>;
          const labels = src.map(r => r.ciclo);
          const vals = src.map(r => r.promedio);
          const data = { labels, datasets:[{ label:'Promedio por ciclo', data:vals, backgroundColor:'#0d6efd' }] };
          const options = { scales:{ y:{ beginAtZero:true, max:100 } } };
          new Chart(cicloCtx, { type:'bar', data, options });
        }
      })();
    </script>
    <?php endif; ?>
</body>
</html>
