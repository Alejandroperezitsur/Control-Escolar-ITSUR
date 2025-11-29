<?php
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$base = $scriptDir;
$p = strpos($scriptDir, '/public');
if ($p !== false) { $base = substr($scriptDir, 0, $p + 7); }
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Calificaciones del Grupo</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row align-items-center g-3">
        <div class="col-md-3"><div class="text-muted small">Materia</div><div class="h6 mb-0"><?= htmlspecialchars($grp['materia'] ?? '') ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Grupo</div><div class="h6 mb-0"><?= htmlspecialchars($grp['nombre'] ?? '') ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Ciclo</div><div class="h6 mb-0"><?= htmlspecialchars($grp['ciclo'] ?? '') ?></div></div>
        <div class="col-md-3"><div class="text-muted small">Profesor</div><div class="h6 mb-0"><?= htmlspecialchars($grp['profesor'] ?? '') ?></div></div>
      </div>
      <?php 
        $total = 0; $pend = 0; $aprob = 0; $reprob = 0; $suma = 0; $cuenta = 0;
        foreach (($rows ?? []) as $rr) { 
          $total++; 
          $finv = ($rr['final'] ?? '') !== '' ? (float)$rr['final'] : null; 
          if ($finv === null) { $pend++; } else { $suma += $finv; $cuenta++; if ($finv >= 70) { $aprob++; } else { $reprob++; } }
        }
        $promG = $cuenta > 0 ? round($suma / $cuenta, 2) : null;
        $promCls = $promG === null ? 'bg-secondary' : ($promG >= 85 ? 'bg-success' : ($promG >= 70 ? 'bg-warning text-dark' : 'bg-danger'));
      ?>
      <div class="row g-3 mt-3">
        <div class="col-6 col-md-3">
          <div class="card">
            <div class="card-body py-2 d-flex align-items-center justify-content-between">
              <div>
                <div class="small text-muted">Alumnos</div>
                <div class="h6 mb-0"><?= (int)$total ?></div>
              </div>
              <span class="badge bg-light text-dark" data-bs-toggle="tooltip" title="Total inscritos"><?= (int)$total ?></span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card">
            <div class="card-body py-2 d-flex align-items-center justify-content-between">
              <div>
                <div class="small text-muted">Aprobados</div>
                <div class="h6 mb-0"><?= (int)$aprob ?></div>
              </div>
              <span class="badge bg-success" data-bs-toggle="tooltip" title="Final ≥ 70"><?= (int)$aprob ?></span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card">
            <div class="card-body py-2 d-flex align-items-center justify-content-between">
              <div>
                <div class="small text-muted">Reprobados</div>
                <div class="h6 mb-0"><?= (int)$reprob ?></div>
              </div>
              <span class="badge bg-danger" data-bs-toggle="tooltip" title="Final < 70"><?= (int)$reprob ?></span>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="card">
            <div class="card-body py-2 d-flex align-items-center justify-content-between">
              <div>
                <div class="small text-muted">Pendientes</div>
                <div class="h6 mb-0"><?= (int)$pend ?></div>
              </div>
              <span class="badge bg-warning text-dark" data-bs-toggle="tooltip" title="Sin calificación final"><?= (int)$pend ?></span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="h6 mb-0">Pendientes de evaluación</div>
        <div class="text-muted small">Alumnos sin calificación final</div>
      </div>
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm align-middle">
          <thead class="table-light"><tr><th>Matrícula</th><th>Alumno</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <?php if (!empty($rows)): $pendRows = array_filter($rows, fn($r) => ($r['final'] ?? '') === '' || $r['final'] === null); if (!empty($pendRows)): foreach ($pendRows as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['matricula'] ?? '') ?></td>
                <td><?= htmlspecialchars(($r['nombre'] ?? '') . ' ' . ($r['apellido'] ?? '')) ?></td>
                <td class="text-end">
                  <a class="btn btn-sm btn-primary" href="<?php echo $base; ?>/grades?grupo_id=<?= (int)($grp['id'] ?? 0) ?>&alumno_id=<?= (int)($r['id'] ?? 0) ?>" data-bs-toggle="tooltip" title="Evaluar alumno">
                    <i class="fa-solid fa-pen-to-square"></i> Evaluar
                  </a>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="3" class="text-muted">No hay pendientes.</td></tr>
            <?php endif; else: ?>
              <tr><td colspan="3" class="text-muted">Sin alumnos en el grupo.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <div class="d-flex align-items-center gap-3">
            <div class="h6 mb-0">Calificaciones del grupo</div>
            <div class="text-muted small">Mostrando: <span id="stateCount">0</span> · Promedio visible: <span id="stateAvg">—</span></div>
            <select id="avgSel" class="form-select form-select-sm" style="width:auto">
              <option value="final">Final</option>
              <option value="promedio">Promedio</option>
            </select>
            <select id="stateSel" class="form-select form-select-sm" style="width:auto">
              <option value="todos">Todos</option>
              <option value="pendiente">Pendientes</option>
              <option value="aprobado">Aprobados</option>
              <option value="reprobado">Reprobados</option>
            </select>
          </div>
          <div class="d-flex align-items-center gap-2">
            <?php $gid = (int)($grp['id'] ?? 0); ?>
            <a id="csvLink" class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/grades/group/export/csv?grupo_id=<?= $gid ?>" data-bs-toggle="tooltip" title="Exportar a CSV"><i class="fa-solid fa-file-csv me-1"></i> CSV</a>
            <a id="xlsxLink" class="btn btn-sm btn-outline-success" href="<?php echo $base; ?>/grades/group/export/xlsx?grupo_id=<?= $gid ?>" data-bs-toggle="tooltip" title="Exportar a Excel"><i class="fa-solid fa-file-excel me-1"></i> XLSX</a>
            <a class="btn btn-sm btn-outline-warning" href="<?php echo $base; ?>/grades/group/export/pendingcsv?grupo_id=<?= $gid ?>" data-bs-toggle="tooltip" title="Exportar pendientes a CSV"><i class="fa-solid fa-clock me-1"></i> Pendientes CSV</a>
          </div>
        </div>
      <div class="table-responsive">
        <table class="table table-striped table-hover table-bordered table-sm align-middle">
          <thead class="table-light" style="position: sticky; top: 0; z-index: 1;">
            <tr>
              <th>Matrícula</th>
              <th>Alumno</th>
              <th class="text-end">Parcial 1</th>
              <th class="text-end">Parcial 2</th>
              <th class="text-end">Final</th>
              <th class="text-end">Promedio</th>
            </tr>
          </thead>
          <tbody id="grpTableBody">
            <?php if (!empty($rows)): foreach ($rows as $r): ?>
            <?php
              $p1 = isset($r['parcial1']) && $r['parcial1'] !== '' ? (float)$r['parcial1'] : null;
              $p2 = isset($r['parcial2']) && $r['parcial2'] !== '' ? (float)$r['parcial2'] : null;
              $fin = isset($r['final']) && $r['final'] !== '' ? (float)$r['final'] : null;
              $prom = isset($r['promedio']) && $r['promedio'] !== '' ? (float)$r['promedio'] : null;
              $cls = function($v){ if ($v === null) return 'text-muted'; return $v >= 70 ? 'text-success' : 'text-danger'; };
              $state = $fin === null ? 'pendiente' : ($fin >= 70 ? 'aprobado' : 'reprobado');
            ?>
            <tr data-state="<?= $state ?>">
              <td><?= htmlspecialchars($r['matricula'] ?? '') ?></td>
              <td><?= htmlspecialchars(($r['nombre'] ?? '') . ' ' . ($r['apellido'] ?? '')) ?></td>
              <td class="text-end"><span class="<?= $cls($p1) ?>"><?= htmlspecialchars($r['parcial1'] ?? '') ?></span></td>
              <td class="text-end"><span class="<?= $cls($p2) ?>"><?= htmlspecialchars($r['parcial2'] ?? '') ?></span></td>
              <td class="text-end"><span class="<?= $cls($fin) ?>"><?= htmlspecialchars($r['final'] ?? '') ?></span></td>
              <td class="text-end"><span class="<?= $cls($prom) ?>"><?= htmlspecialchars($r['promedio'] ?? '') ?></span></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-muted">No hay calificaciones registradas en este grupo.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <?php 
    $sumP1 = 0; $cntP1 = 0; $sumP2 = 0; $cntP2 = 0; $sumFin = 0; $cntFin = 0;
    $distLabels = ['0-59','60-69','70-79','80-89','90-100'];
    $distP1 = [0,0,0,0,0];
    $distP2 = [0,0,0,0,0];
    $distFin = [0,0,0,0,0];
    $bucket = function (float $v): int { if ($v < 60) return 0; if ($v < 70) return 1; if ($v < 80) return 2; if ($v < 90) return 3; return 4; };
    foreach (($rows ?? []) as $r) {
      if (($r['parcial1'] ?? '') !== '') { $v=(float)$r['parcial1']; $sumP1 += $v; $cntP1++; $distP1[$bucket($v)]++; }
      if (($r['parcial2'] ?? '') !== '') { $v=(float)$r['parcial2']; $sumP2 += $v; $cntP2++; $distP2[$bucket($v)]++; }
      if (($r['final'] ?? '') !== '') { $v=(float)$r['final']; $sumFin += $v; $cntFin++; $distFin[$bucket($v)]++; }
    }
    $avgP1 = $cntP1 > 0 ? round($sumP1 / $cntP1, 2) : 0;
    $avgP2 = $cntP2 > 0 ? round($sumP2 / $cntP2, 2) : 0;
    $avgFin = $cntFin > 0 ? round($sumFin / $cntFin, 2) : 0;
  ?>
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="h6 mb-0">Desempeño del grupo</div>
        <div class="text-muted small">Promedios y distribución</div>
      </div>
      <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item" role="presentation"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-prom" type="button" role="tab">Promedios</button></li>
        <li class="nav-item" role="presentation"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-dist" type="button" role="tab">Distribución</button></li>
      </ul>
      <div class="tab-content">
        <div class="tab-pane fade show active" id="tab-prom" role="tabpanel"><canvas id="grpPerf" style="height:180px"></canvas></div>
        <div class="tab-pane fade" id="tab-dist" role="tabpanel"><canvas id="grpDist" style="height:220px"></canvas></div>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <div class="h6 mb-0">Horarios del grupo</div>
          <div class="text-muted small">Días, horas y salón</div>
        </div>
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editProfModal">Asignar/Cambiar Profesor</button>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm align-middle">
          <thead><tr><th>Día</th><th>Inicio</th><th>Fin</th><th>Salón</th><th class="text-end">Acciones</th></tr></thead>
          <tbody id="sched-body"><tr><td colspan="5" class="text-muted">Cargando...</td></tr></tbody>
        </table>
      </div>
      <?php $role = $_SESSION['role'] ?? ''; if ($role === 'admin'): ?>
      <form id="sched-form" class="row g-2 mt-2" onsubmit="addSchedule(event)">
        <input type="hidden" name="grupo_id" value="<?= (int)($grp['id'] ?? 0) ?>">
        <div class="col-md-3">
          <select class="form-select form-select-sm" name="dia" required>
            <option value="">Día...</option>
            <?php foreach (["Lunes","Martes","Miércoles","Jueves","Viernes","Sábado","Domingo"] as $d): ?>
              <option value="<?= $d ?>"><?= $d ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-2"><input type="time" class="form-control form-control-sm" name="hora_inicio" required></div>
        <div class="col-md-2"><input type="time" class="form-control form-control-sm" name="hora_fin" required></div>
        <div class="col-md-3"><input type="text" class="form-control form-control-sm" name="salon" placeholder="Salón"></div>
        <div class="col-md-2"><button class="btn btn-sm btn-primary w-100" type="submit">Agregar</button></div>
      </form>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php include __DIR__ . '/../groups/edit_professor.php'; ?>
<script>
const BASE = '<?php echo $base; ?>';
const GID = <?php echo (int)($grp['id'] ?? 0); ?>;
function loadSchedules(){
  fetch(`${BASE}/api/groups/schedules?grupo_id=${GID}`).then(r=>r.json()).then(j=>{
    const tb = document.getElementById('sched-body');
    if (!j || !j.success) { tb.innerHTML = '<tr><td colspan="5" class="text-danger">Error al cargar horarios</td></tr>'; return; }
    const rows = j.data || [];
    if (rows.length === 0) { tb.innerHTML = '<tr><td colspan="5" class="text-muted">Sin horarios</td></tr>'; return; }
    tb.innerHTML = rows.map(r => `<tr><td>${esc(r.dia)}</td><td>${esc(r.hora_inicio)}</td><td>${esc(r.hora_fin)}</td><td>${esc(r.salon||'')}</td><td class="text-end"><?php echo ($_SESSION['role'] ?? '') === 'admin' ? '<button class="btn btn-sm btn-outline-danger" onclick="delSchedule(${r.id})" data-bs-toggle="tooltip" title="Eliminar horario">Eliminar</button>' : '' ?></td></tr>`).join('');
  }).catch(()=>{
    document.getElementById('sched-body').innerHTML = '<tr><td colspan="5" class="text-danger">Error de red</td></tr>';
  });
}
function esc(s){ s = (s??'').toString(); return s.replace(/[&<>"]/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m])); }
function addSchedule(e){
  e.preventDefault();
  const fd = new FormData(e.target);
  fetch(`${BASE}/groups/schedules/add`, { method:'POST', body: fd }).then(r=>r.json()).then(j=>{ if (j && j.success) { e.target.reset(); loadSchedules(); } else { alert('No se pudo agregar'); } });
}
function delSchedule(id){
  if (!confirm('¿Eliminar horario?')) return;
  const fd = new FormData(); fd.append('id', String(id));
  fetch(`${BASE}/groups/schedules/delete`, { method:'POST', body: fd }).then(r=>r.json()).then(j=>{ if (j && j.success) { loadSchedules(); } else { alert('No se pudo eliminar'); } });
}
document.addEventListener('DOMContentLoaded', loadSchedules);
  document.addEventListener('DOMContentLoaded', () => {
    try {
      const tEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
      tEls.forEach(el => new bootstrap.Tooltip(el));
    } catch(e) {}
  });
  (function(){
    var s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js';
    s.onload = function(){
      var perfEl = document.getElementById('grpPerf');
      var distEl = document.getElementById('grpDist');
      if (perfEl) {
        var perfData = {
          labels: ['Parcial 1','Parcial 2','Final'],
          datasets: [{
            label: 'Promedio',
            data: [<?= json_encode($avgP1) ?>, <?= json_encode($avgP2) ?>, <?= json_encode($avgFin) ?>],
            backgroundColor: ['rgba(13,110,253,.35)','rgba(255,193,7,.35)','rgba(25,135,84,.35)'],
            borderColor: ['#0d6efd','#ffc107','#198754'],
            borderWidth: 1,
          }]
        };
        new Chart(perfEl, { type: 'bar', data: perfData, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true, suggestedMax: 100 } }, plugins: { legend: { display: false } } } });
        try {
          var initSel = document.getElementById('avgSel');
          var initIdx = (initSel && initSel.value === 'promedio') ? 5 : 4;
          updateChart(initIdx);
        } catch(e) {}
      }
      if (distEl) {
        var distLabels = <?= json_encode($distLabels) ?>;
        var distDataP1 = <?= json_encode(array_map('intval', $distP1)) ?>;
        var distDataP2 = <?= json_encode(array_map('intval', $distP2)) ?>;
        var distDataFin = <?= json_encode(array_map('intval', $distFin)) ?>;
        var distData = {
          labels: distLabels,
          datasets: [
            { label: 'Parcial 1', data: distDataP1, backgroundColor: 'rgba(13,110,253,.35)', borderColor: '#0d6efd', borderWidth: 1 },
            { label: 'Parcial 2', data: distDataP2, backgroundColor: 'rgba(255,193,7,.35)', borderColor: '#ffc107', borderWidth: 1 },
            { label: 'Final', data: distDataFin, backgroundColor: 'rgba(25,135,84,.35)', borderColor: '#198754', borderWidth: 1 }
          ]
        };
        new Chart(distEl, { type: 'bar', data: distData, options: { responsive: true, maintainAspectRatio: false, scales: { y: { beginAtZero: true } } } });
      }
    };
    document.body.appendChild(s);
  })();
  function updateChart(colIdx) {
    if (typeof Chart === 'undefined') { return; }
    var perfEl = document.getElementById('grpPerf');
    if (!perfEl) { return; }
    var chart = Chart.getChart(perfEl);
    if (!chart) { return; }
    var tb = document.getElementById('grpTableBody');
    if (!tb) { return; }
    var rows = tb.querySelectorAll('tr');
    var sumP1 = 0, cntP1 = 0;
    var sumP2 = 0, cntP2 = 0;
    var sumSel = 0, cntSel = 0;
    rows.forEach(function (r) {
      if (r.style.display === 'none') { return; }
      var cells = r.querySelectorAll('td');
      if (cells.length < 6) { return; }
      var p1 = cells[2] ? String(cells[2].textContent || '').trim() : '';
      var p2 = cells[3] ? String(cells[3].textContent || '').trim() : '';
      var sel = cells[colIdx] ? String(cells[colIdx].textContent || '').trim() : '';
      if (p1 !== '') { var v1 = Number(p1.replace(',', '.')); if (!isNaN(v1)) { sumP1 += v1; cntP1++; } }
      if (p2 !== '') { var v2 = Number(p2.replace(',', '.')); if (!isNaN(v2)) { sumP2 += v2; cntP2++; } }
      if (sel !== '') { var vs = Number(sel.replace(',', '.')); if (!isNaN(vs)) { sumSel += vs; cntSel++; } }
    });
    var avgP1 = cntP1 > 0 ? Math.round((sumP1 / cntP1) * 100) / 100 : 0;
    var avgP2 = cntP2 > 0 ? Math.round((sumP2 / cntP2) * 100) / 100 : 0;
    var avgSel = cntSel > 0 ? Math.round((sumSel / cntSel) * 100) / 100 : 0;
    chart.data.labels = ['Parcial 1','Parcial 2', (colIdx === 5 ? 'Promedio' : 'Final')];
    chart.data.datasets[0].data = [avgP1, avgP2, avgSel];
    chart.update();
  }
  (function(){
    var sel = document.getElementById('stateSel');
    var tb = document.getElementById('grpTableBody');
    var csv = document.getElementById('csvLink');
    var avgSel = document.getElementById('avgSel');
    if (sel && tb) {
      var key = 'grpStateSel_' + String(GID);
      var initial = localStorage.getItem(key);
      if (initial) { sel.value = initial; }
      var apply = function(){
        var v = sel.value;
        var colIdx = (avgSel && avgSel.value === 'promedio') ? 5 : 4;
        var rows = tb.querySelectorAll('tr');
        var count = 0;
        var sum = 0;
        var cfin = 0;
        rows.forEach(function(r){
          var s = r.getAttribute('data-state') || 'todos';
          var show = (v === 'todos' || v === s);
          r.style.display = show ? '' : 'none';
          if (show) { count++; }
          if (show) {
            var cells = r.querySelectorAll('td');
            var txt = cells && cells[colIdx] ? String(cells[colIdx].textContent || '').trim() : '';
            var val = txt !== '' ? Number(txt.replace(',', '.')) : NaN;
            if (!isNaN(val)) { sum += val; cfin++; }
          }
        });
        if (csv) {
          var href = '<?php echo $base; ?>/grades/group/export/csv?grupo_id=' + String(GID);
          if (v !== 'todos') { href += '&estado=' + encodeURIComponent(v); }
          csv.setAttribute('href', href);
        }
        var xlsx = document.getElementById('xlsxLink');
        if (xlsx) {
          var xhref = '<?php echo $base; ?>/grades/group/export/xlsx?grupo_id=' + String(GID);
          if (v !== 'todos') { xhref += '&estado=' + encodeURIComponent(v); }
          xlsx.setAttribute('href', xhref);
        }
        var cntEl = document.getElementById('stateCount');
        if (cntEl) { cntEl.textContent = String(count); }
        var avgEl = document.getElementById('stateAvg');
        if (avgEl) {
          var avg = cfin > 0 ? Math.round((sum / cfin) * 100) / 100 : null;
          avgEl.textContent = avg === null ? '—' : String(avg);
          avgEl.classList.remove('text-success','text-danger');
          if (avg !== null) { avgEl.classList.add(avg >= 70 ? 'text-success' : 'text-danger'); }
        }
        updateChart(colIdx);
        try { localStorage.setItem(key, v); } catch(e) {}
      };
      sel.addEventListener('change', apply);
      if (avgSel) {
        var avgKey = 'grpAvgSel_' + String(GID);
        var aInit = localStorage.getItem(avgKey);
        if (aInit) { avgSel.value = aInit; }
        avgSel.addEventListener('change', function(){ try { localStorage.setItem(avgKey, avgSel.value); } catch(e) {} apply(); });
      }
      apply();
    }
  })();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
