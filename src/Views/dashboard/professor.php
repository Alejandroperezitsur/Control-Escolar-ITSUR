<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-3">
  <div>
    <h2 class="mb-1">Mi actividad como profesor</h2>
    <p class="text-muted mb-0">Revisa tus grupos, pendientes de evaluación y alumnos a tu cargo.</p>
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
          <i class="fa-solid fa-people-group fa-2x me-3 text-primary"></i>
          <div>
            <div class="small">Grupos Activos</div>
            <div class="h5 mb-0" id="kpi-grupos">—</div>
          </div>
        </div>
        <a href="<?php echo $base; ?>/profesor/grupos" class="stretched-link"></a>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-4">
    <div class="card position-relative">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-user-graduate fa-2x me-3 text-success"></i>
          <div>
            <div class="small">Total Alumnos</div>
            <div class="h5 mb-0" id="kpi-alumnos">—</div>
          </div>
        </div>
        <a href="<?php echo $base; ?>/profesor/alumnos" class="stretched-link"></a>
      </div>
    </div>
  </div>
  <div class="col-md-4">
    <div class="card position-relative">
      <div class="card-body">
        <div class="d-flex align-items-center">
          <i class="fa-solid fa-clipboard-check fa-2x me-3 text-warning"></i>
          <div>
            <div class="small">Evaluaciones pendientes</div>
            <div class="h5 mb-0" id="kpi-pendientes">—</div>
          </div>
        </div>
        <a href="<?php echo $base; ?>/profesor/pendientes" class="stretched-link"></a>
      </div>
    </div>
  </div>
</div>

<div class="mt-4 d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
  <div>
    <h5 class="mb-1">¿Qué tienes pendiente hoy?</h5>
    <p class="text-muted small mb-0">Accede rápido a tus grupos y tareas de evaluación.</p>
  </div>
  <div class="d-flex flex-wrap gap-2">
    <a class="btn btn-outline-primary" href="<?php echo $base; ?>/profesor/pendientes"><i class="fa-solid fa-clock me-1"></i> Ver pendientes de evaluación</a>
    <a class="btn btn-outline-secondary" href="<?php echo $base; ?>/grades/bulk"><i class="fa-solid fa-file-import me-1"></i> Carga masiva de calificaciones</a>
  </div>
</div>

<script>
fetch('<?php echo $base; ?>/api/profesor/perfil').then(r=>r.json()).then(resp=>{
  const d = resp.data || {};
  const fallback = <?php echo json_encode((string)($_SESSION['name'] ?? '')); ?>;
  document.getElementById('perfil-nombre').textContent = (d.nombre && d.nombre.trim()) ? d.nombre : (fallback || '—');
  document.getElementById('perfil-email').textContent = d.email || '—';
  document.getElementById('perfil-matricula').textContent = 'Matrícula: ' + (d.matricula || '—');
});
fetch('<?php echo $base; ?>/api/kpis/profesor').then(r=>r.json()).then(d=>{
  document.getElementById('kpi-grupos').textContent = d.grupos_activos ?? '—';
  document.getElementById('kpi-alumnos').textContent = d.alumnos ?? '—';
  document.getElementById('kpi-pendientes').textContent = d.pendientes ?? '—';
});
</script>
<div class="mt-4">
  <div class="card">
    <div class="card-body">
      <div class="d-flex flex-column flex-sm-row justify-content-between align-items-sm-center mb-2">
        <div>
          <h5 class="card-title mb-0">Mis grupos</h5>
          <p class="text-muted small mb-0">Selecciona un grupo para capturar o revisar calificaciones.</p>
        </div>
        <div class="mt-2 mt-sm-0" style="max-width: 260px;">
          <input type="text" id="grp-filter" class="form-control form-control-sm" placeholder="Buscar por materia o grupo">
        </div>
      </div>
      <div id="grp-list" class="d-md-none"></div>
      <div class="table-responsive d-none d-md-block">
        <table class="table table-sm align-middle">
          <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Acciones</th></tr></thead>
          <tbody id="grp-tbody"></tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
fetch('<?php echo $base; ?>/api/kpis/profesor').then(r=>r.json()).then(d=>{
  const rows = (d.grupos || []).filter(x => Number(x.alumnos || 0) > 0);
  const tbody = document.getElementById('grp-tbody');
  const list = document.getElementById('grp-list');
  const formatAvg = v => (v !== null && v !== undefined && !isNaN(Number(v))) ? Number(v).toFixed(2) : '';
  const render = () => {
    const q = document.getElementById('grp-filter').value.toLowerCase();
    const filtered = rows.filter(x => (x.materia||'').toLowerCase().includes(q) || (x.grupo||'').toLowerCase().includes(q));
    if (filtered.length === 0) {
      tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted">No hay grupos con alumnos.</td></tr>`;
      if (list) list.innerHTML = `<div class="text-muted small">No hay grupos con alumnos.</div>`;
      return;
    }
    tbody.innerHTML = filtered
      .map(x => `<tr>
        <td>${x.ciclo ?? ''}</td>
        <td>${x.materia ?? ''}</td>
        <td>
          ${x.grupo ?? ''}
          <span class="badge bg-secondary ms-2">${Number(x.alumnos||0)} alumnos</span>
          ${x.promedio ? `<span class="badge bg-info ms-1">${formatAvg(x.promedio)}</span>` : ''}
        </td>
        <td class="text-end">
          <a class="btn btn-outline-success btn-sm" href="<?php echo $base; ?>/grades?grupo_id=${x.id}"><i class="fa-solid fa-pen"></i> Calificar</a>
          <a class="btn btn-outline-primary btn-sm ms-1" href="<?php echo $base; ?>/grades/group?grupo_id=${x.id}"><i class="fa-solid fa-table"></i> Ver calificaciones</a>
        </td>
      </tr>`).join('');
    if (list) {
      list.innerHTML = filtered
        .map(x => `
          <div class="card mb-2">
            <div class="card-body py-2">
              <div class="d-flex justify-content-between">
                <div>
                  <div class="small text-muted">${x.ciclo ?? ''}</div>
                  <div class="fw-semibold">${x.materia ?? ''}</div>
                  <div class="text-muted small">${x.grupo ?? ''}</div>
                </div>
                <div class="text-end">
                  <div class="badge bg-secondary mb-1">${Number(x.alumnos||0)} alumnos</div>
                  ${x.promedio ? `<div class="badge bg-info">Promedio ${formatAvg(x.promedio)}</div>` : ''}
                </div>
              </div>
              <div class="mt-2 d-flex justify-content-end gap-2">
                <a class="btn btn-sm btn-outline-success" href="<?php echo $base; ?>/grades?grupo_id=${x.id}"><i class="fa-solid fa-pen"></i> Calificar</a>
                <a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/grades/group?grupo_id=${x.id}"><i class="fa-solid fa-table"></i> Ver</a>
              </div>
            </div>
          </div>
        `).join('');
    }
  };
  render();
  document.getElementById('grp-filter').addEventListener('input', render);
});
</script>
<?php include __DIR__ . '/prof_stats.php'; ?>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
