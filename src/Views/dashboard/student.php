<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
  <div>
    <h2 class="mb-1">Mi situación académica</h2>
    <p class="text-muted mb-0">Revisa tu estado actual, materias en riesgo y próximos pasos.</p>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6 col-lg-4">
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
  <div class="col-md-6 col-lg-4">
    <div class="card position-relative">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-chart-line fa-2x me-3 text-primary"></i>
          <div>
            <div class="small">Semáforo académico</div>
            <div class="h4 mb-0" id="stat-promedio">—</div>
          </div>
        </div>
        <div class="mt-3">
          <div class="progress" style="height: 20px;">
            <div id="promedio-bar" class="progress-bar" role="progressbar" style="width: 0%">0%</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mt-2 small text-muted">
            <span id="promedio-label">Sin datos suficientes</span>
            <a href="<?php echo $base; ?>/alumno/calificaciones" class="link-primary text-decoration-none">Ver historial</a>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-4">
    <div class="card position-relative">
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
    <div class="card position-relative">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-hourglass-half fa-2x me-3 text-warning"></i>
          <div>
            <div class="small">Materias en riesgo</div>
            <div class="h4 mb-0" id="stat-pendientes">—</div>
          </div>
        </div>
        <p class="text-muted small mt-3 mb-0">Revisa estas materias primero para evitar reprobar.</p>
        <a href="<?php echo $base; ?>/alumno/pendientes" class="stretched-link"></a>
      </div>
    </div>
  </div>
</div>

<div class="row g-3 mt-4">
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-body">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-2">
          <div>
            <h5 class="card-title mb-0">Mis materias actuales</h5>
            <p class="text-muted small mb-0">Revisa tu carga por ciclo, estado y calificación.</p>
          </div>
          <div class="mt-2 mt-sm-0" style="max-width: 260px;">
            <input type="text" id="carga-filter" class="form-control form-control-sm" placeholder="Buscar por materia o grupo">
          </div>
        </div>
        <div id="carga-list" class="d-md-none"></div>
        <div class="table-responsive d-none d-md-block">
          <table class="table table-sm align-middle">
            <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Estado</th><th class="text-end">Calificación</th></tr></thead>
            <tbody id="carga-tbody"><tr><td colspan="5" class="text-muted">Cargando...</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  <div class="col-12 col-lg-6">
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-1">Evolución de tu rendimiento</h5>
        <p class="text-muted small mb-3">Observa cómo ha cambiado tu promedio a lo largo de los ciclos.</p>
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
  const list = document.getElementById('carga-list');
const noRec = document.getElementById('no-records');
function renderCarga(){
  const q = (document.getElementById('carga-filter').value || '').toLowerCase();
  const rows = cargaRows.filter(x => (x.materia||'').toLowerCase().includes(q) || (x.grupo||'').toLowerCase().includes(q));
  if (!rows.length) {
    tbody.innerHTML = '<tr><td colspan="5" class="text-muted">Sin materias que coincidan con tu búsqueda.</td></tr>';
    if (list) list.innerHTML = '<div class="text-muted small">No hay materias que coincidan con tu búsqueda.</div>';
    return;
  }
  tbody.innerHTML = rows.map(x => `<tr>
    <td>${x.ciclo ?? ''}</td>
    <td>${x.materia ?? ''}</td>
    <td>${x.grupo ?? ''}</td>
    <td class="text-end">${x.estado ?? ''}</td>
    <td class="text-end">${x.calificacion ?? ''}</td>
  </tr>`).join('');
  if (list) {
    list.innerHTML = rows.map(x => `
      <div class="card mb-2">
        <div class="card-body py-2">
          <div class="d-flex justify-content-between">
            <div>
              <div class="small text-muted">${x.ciclo ?? ''}</div>
              <div class="fw-semibold">${x.materia ?? ''}</div>
              <div class="text-muted small">${x.grupo ?? ''}</div>
            </div>
            <div class="text-end">
              <div class="small">${x.estado ?? ''}</div>
              <div class="fw-semibold">${x.calificacion ?? ''}</div>
            </div>
          </div>
        </div>
      </div>
    `).join('');
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
