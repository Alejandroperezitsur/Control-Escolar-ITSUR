<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<h2 class="mb-4">Dashboard Administrador</h2>

<!-- KPI Cards Row -->
<div class="row g-3 mb-4">
  <!-- Total Alumnos -->
  <div class="col-12 col-sm-6 col-md-4 col-lg-2">
    <div class="card text-bg-primary position-relative h-100" data-bs-toggle="tooltip" title="Ver lista de alumnos">
      <div class="card-body">
        <div class="d-flex flex-column align-items-center text-center">
          <i class="fa-solid fa-users fa-2x mb-2"></i>
          <div class="small">Total Alumnos</div>
          <div class="h4 mb-0" id="kpi-alumnos">—</div>
        </div>
        <a href="<?php echo $base; ?>/alumnos" class="stretched-link"></a>
      </div>
    </div>
  </div>

  <!-- Materias -->
  <div class="col-12 col-sm-6 col-md-4 col-lg-2">
    <div class="card text-bg-success position-relative h-100" data-bs-toggle="tooltip" title="Ver catálogo de materias">
      <div class="card-body">
        <div class="d-flex flex-column align-items-center text-center">
          <i class="fa-solid fa-book fa-2x mb-2"></i>
          <div class="small">Materias</div>
          <div class="h4 mb-0" id="kpi-materias">—</div>
        </div>
        <a href="<?php echo $base; ?>/subjects" class="stretched-link"></a>
      </div>
    </div>
  </div>

  <!-- Carreras -->
  <div class="col-12 col-sm-6 col-md-4 col-lg-2">
    <div class="card text-bg-secondary position-relative h-100" data-bs-toggle="tooltip" title="Ver carreras y planes de estudio">
      <div class="card-body">
        <div class="d-flex flex-column align-items-center text-center">
          <i class="fa-solid fa-graduation-cap fa-2x mb-2"></i>
          <div class="small">Carreras</div>
          <div class="h4 mb-0" id="kpi-carreras">—</div>
        </div>
        <a href="<?php echo $base; ?>/careers" class="stretched-link"></a>
      </div>
    </div>
  </div>

  <!-- Profesores -->
  <div class="col-12 col-sm-6 col-md-4 col-lg-2">
    <div class="card text-bg-info position-relative h-100" data-bs-toggle="tooltip" title="Ver lista de profesores">
      <div class="card-body">
        <div class="d-flex flex-column align-items-center text-center">
          <i class="fa-solid fa-user-tie fa-2x mb-2"></i>
          <div class="small">Profesores</div>
          <div class="h4 mb-0" id="kpi-profesores">—</div>
        </div>
        <a href="<?php echo $base; ?>/professors" class="stretched-link"></a>
      </div>
    </div>
  </div>

  <!-- Grupos Activos -->
  <div class="col-12 col-sm-6 col-md-4 col-lg-2">
    <div class="card text-bg-warning position-relative h-100" data-bs-toggle="tooltip" title="Ver grupos activos">
      <div class="card-body">
        <div class="d-flex flex-column align-items-center text-center">
          <i class="fa-solid fa-people-group fa-2x mb-2"></i>
          <div class="small text-dark">Grupos Activos</div>
          <div class="h4 mb-0 text-dark" id="kpi-grupos">—</div>
        </div>
        <a href="<?php echo $base; ?>/groups" class="stretched-link"></a>
      </div>
    </div>
  </div>

  <!-- Promedio General -->
  <div class="col-12 col-sm-6 col-md-4 col-lg-2">
    <div class="card text-bg-primary position-relative h-100" data-bs-toggle="tooltip" title="Ver reportes generales">
      <div class="card-body">
        <div class="d-flex flex-column align-items-center text-center">
          <i class="fa-solid fa-chart-line fa-2x mb-2"></i>
          <div class="small">Promedio General</div>
          <div class="h4 mb-0" id="kpi-promedio">—</div>
        </div>
        <a href="<?php echo $base; ?>/reports" class="stretched-link"></a>
      </div>
    </div>
  </div>
</div>

<!-- Action Buttons -->
<div class="mb-4">
  <a class="btn btn-outline-secondary me-2" href="<?php echo $base; ?>/reports/export/csv" data-bs-toggle="tooltip" title="Descargar reporte general en CSV"><i class="fa-solid fa-file-csv me-1"></i> Exportar CSV</a>
  <a class="btn btn-outline-primary" href="<?php echo $base; ?>/dashboard" data-bs-toggle="tooltip" title="Recargar datos del dashboard"><i class="fa-solid fa-arrows-rotate me-1"></i> Refrescar</a>
</div>

<!-- Secondary KPI Row -->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-6 col-lg-4">
    <div class="card text-bg-danger position-relative h-100" data-bs-toggle="tooltip" title="Ver alumnos pendientes de evaluación">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-hourglass-half fa-2x me-3"></i>
          <div>
            <div class="small">Pendientes de Evaluación</div>
            <div class="h5 mb-0" id="kpi-pendientes">—</div>
          </div>
        </div>
        <a href="<?php echo $base; ?>/admin/pendientes" class="stretched-link"></a>
      </div>
    </div>
  </div>
</div>


<div class="mt-4">
  <div class="card">
    <div class="card-body">
      <h5 class="card-title mb-3">Promedios por Materia</h5>
      <canvas id="chart-materias" height="120"></canvas>
    </div>
  </div>
</div>

<?php include __DIR__ . '/admin_stats.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Cargar KPIs reales
fetch('<?php echo $base; ?>/api/kpis/admin')
  .then(r=>r.json())
  .then(d=>{
    document.getElementById('kpi-alumnos').textContent = d.alumnos ?? '—';
    document.getElementById('kpi-materias').textContent = d.materias ?? '—';
    document.getElementById('kpi-carreras').textContent = d.carreras ?? '—';
    document.getElementById('kpi-grupos').textContent = d.grupos ?? '—';
    document.getElementById('kpi-promedio').textContent = d.promedio ?? '—';
    document.getElementById('kpi-profesores').textContent = d.profesores ?? '—';
    document.getElementById('kpi-pendientes').textContent = d.pendientes_evaluacion ?? '—';
  });
  
// Chart.js con datos reales
fetch('<?php echo $base; ?>/api/charts/promedios-materias')
  .then(r=>r.json())
  .then(rows=>{
    const labels = rows.map(x=>x.materia);
    const data = rows.map(x=>Number(x.promedio));
    const ctx = document.getElementById('chart-materias').getContext('2d');
    new Chart(ctx, {
      type: 'bar',
      data: { labels, datasets: [{ label: 'Promedio final', data, backgroundColor: '#0d6efd' }] },
      options: { scales: { y: { beginAtZero: true, suggestedMax: 10 } } }
    });
  });
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
