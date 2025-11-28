<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<h2 class="mb-4">Mi Tablero</h2>

<div class="row g-3 mb-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-regular fa-id-card fa-2x me-3 text-primary"></i>
          <div>
            <div class="small">Información Personal</div>
            <div id="perfil-nombre" class="fw-semibold">—</div>
            <div id="perfil-email" class="text-muted small">—</div>
            <div id="perfil-matricula" class="text-muted small">—</div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6"></div>
  </div>

<div class="row g-3">
  <div class="col-md-4">
    <div class="card position-relative" data-bs-toggle="tooltip" title="Ver detalle de calificaciones">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-chart-line fa-2x me-3 text-primary"></i>
          <div>
            <div class="small">Promedio General</div>
            <div class="h4 mb-0" id="stat-promedio">—</div>
          </div>
        </div>
        <div class="progress mt-3" style="height: 20px;">
          <div id="promedio-bar" class="progress-bar" role="progressbar" style="width: 0%">0%</div>
        </div>
        <a href="<?php echo $base; ?>/alumno/calificaciones" class="stretched-link"></a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card position-relative" data-bs-toggle="tooltip" title="Ver carga académica actual">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-book-open fa-2x me-3 text-success"></i>
          <div>
            <div class="small">Materias Cursadas</div>
            <div class="h4 mb-0" id="stat-total">—</div>
          </div>
        </div>
        <a href="<?php echo $base; ?>/alumno/carga" class="stretched-link"></a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card position-relative" data-bs-toggle="tooltip" title="Ver materias pendientes">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-hourglass-half fa-2x me-3 text-warning"></i>
          <div>
            <div class="small">Pendientes</div>
            <div class="h4 mb-0" id="stat-pendientes">—</div>
          </div>
        </div>
        <a href="<?php echo $base; ?>/alumno/pendientes" class="stretched-link"></a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-3">
  <div class="col-md-6 col-lg-3">
    <div class="card position-relative" data-bs-toggle="tooltip" title="Ver mi horario del semestre actual">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-calendar-days fa-2x me-3 text-info"></i>
          <div>
            <div class="small">Mi Horario</div>
            <div class="small text-muted">Semestre Actual</div>
          </div>
        </div>
        <a href="<?php echo $base; ?>/app.php?r=/alumno/horarios" class="stretched-link"></a>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="card position-relative" data-bs-toggle="tooltip" title="Proceso de reinscripción">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-arrow-rotate-right fa-2x me-3 text-secondary"></i>
          <div>
            <div class="small">Reinscripción</div>
            <div class="small text-muted">Inscribe materias</div>
          </div>
        </div>
        <a href="<?php echo $base; ?>/app.php?r=/alumno/reinscripcion" class="stretched-link"></a>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-3">
    <div class="card position-relative" data-bs-toggle="tooltip" title="Ver mi retícula académica">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-diagram-project fa-2x me-3 text-primary"></i>
          <div>
            <div class="small">Mi Retícula</div>
            <div class="small text-muted">Plan de estudios</div>
          </div>
        </div>
        <a href="<?php echo $base; ?>/app.php?r=/alumno/reticula" class="stretched-link"></a>
      </div>
    </div>
  </div>

<div class="row g-3 mt-3">
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h5 class="card-title mb-0">Mi Carga Académica</h5>
          <input type="text" id="carga-filter" class="form-control form-control-sm" style="max-width: 240px" placeholder="Filtrar por materia/grupo" data-bs-toggle="tooltip" title="Escribe para buscar en tu carga">
        </div>
        <div class="table-responsive">
          <table class="table table-sm">
            <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Estado</th><th class="text-end">Calificación</th></tr></thead>
            <tbody id="carga-tbody"><tr><td colspan="5" class="text-muted">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Gráfica de Rendimiento</h5>
        <canvas id="chart-rendimiento" height="120"></canvas>
      </div>
    </div>
  </div>
  <div class="col-12">
    <div id="no-records" class="alert alert-info d-none">No hay registros disponibles.</div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
fetch('<?php echo $base; ?>/api/alumno/perfil').then(r=>r.json()).then(resp=>{
  const d = resp.data || {};
  const nombre = [d.nombre||'', d.apellido||''].filter(Boolean).join(' ');
  document.getElementById('perfil-nombre').textContent = nombre || '—';
  document.getElementById('perfil-email').textContent = d.email || '—';
  document.getElementById('perfil-matricula').textContent = 'Matrícula: ' + (d.matricula || '—');
});
// Cargar estadísticas
fetch('<?php echo $base; ?>/api/alumno/estadisticas').then(r=>r.json()).then(resp=>{
  const d = resp.data || {};
  const prom = Number(d.promedio ?? 0);
  document.getElementById('stat-promedio').textContent = prom.toFixed(2);
  document.getElementById('stat-total').textContent = d.total ?? 0;
  document.getElementById('stat-pendientes').textContent = d.pendientes ?? 0;
  const pct = Math.min(100, Math.max(0, Math.round(prom * 10)));
  const bar = document.getElementById('promedio-bar');
  bar.style.width = pct + '%';
  bar.textContent = pct + '%';
  bar.classList.remove('bg-danger','bg-warning','bg-success');
  bar.classList.add(pct < 60 ? 'bg-danger' : pct < 80 ? 'bg-warning' : 'bg-success');
});

// Cargar tabla Kardex/Carga
let cargaRows = [];
const tbody = document.getElementById('carga-tbody');
const noRec = document.getElementById('no-records');
function renderCarga(){
  const q = (document.getElementById('carga-filter').value || '').toLowerCase();
  const rows = cargaRows.filter(x => (x.materia||'').toLowerCase().includes(q) || (x.grupo||'').toLowerCase().includes(q));
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Sin resultados</td></tr>';
  } else {
    tbody.innerHTML = rows.map(x => `<tr>
      <td>${x.ciclo ?? ''}</td>
      <td>${x.materia ?? ''}</td>
      <td>${x.grupo ?? ''}</td>
      <td class="text-end">${x.estado ?? ''}</td>
      <td class="text-end">${x.calificacion ?? ''}</td>
    </tr>`).join('');
  }
}
fetch('<?php echo $base; ?>/api/alumno/carga').then(r=>r.json()).then(resp=>{
  const data = resp.data || [];
  cargaRows = Array.isArray(data) ? data : [];
  if (cargaRows.length === 0) { noRec.classList.remove('d-none'); }
  renderCarga();
});
document.getElementById('carga-filter').addEventListener('input', renderCarga);

// Gráfica Chart.js
fetch('<?php echo $base; ?>/api/alumno/chart').then(r=>r.json()).then(resp=>{
  const d = resp.data || { labels: [], data: [] };
  const labels = d.labels || [];
  const data = (d.data || []).map(Number);
  const ctx = document.getElementById('chart-rendimiento').getContext('2d');
  new Chart(ctx, {
    type: 'line',
    data: { labels, datasets: [{ label: 'Promedio por ciclo', data, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.2)' }] },
    options: { scales: { y: { beginAtZero: true, suggestedMax: 100 } } }
  });
});
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
