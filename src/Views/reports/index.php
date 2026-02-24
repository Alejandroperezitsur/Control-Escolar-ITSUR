<?php
$role = $_SESSION['role'] ?? '';
$csrf = $_SESSION['csrf_token'] ?? '';
$scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$base = $scriptDir;
$p = strpos($scriptDir, '/public');
if ($p !== false) { $base = substr($scriptDir, 0, $p + 7); }
ob_start();
?>
<div class="container py-4">
  <?php $isPublic = (substr($base, -7) === '/public'); $goDashboard = $base . ($isPublic ? '/app.php?r=/dashboard' : '/dashboard'); ?>
  <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center mb-2">
    <div>
      <h3 class="mb-1">Centro de análisis académico</h3>
      <p class="text-muted mb-0">Explora promedios, reprobación y riesgo académico por ciclo, grupo y materia.</p>
    </div>
    <div class="mt-2 mt-md-0">
      <a href="<?php echo $goDashboard; ?>" class="btn btn-outline-secondary">Volver al panel</a>
    </div>
  </div>

  <div class="d-sm-none mb-3">
    <button class="btn btn-outline-primary w-100" type="button" data-bs-toggle="collapse" data-bs-target="#filtersCollapse" aria-expanded="true" aria-controls="filtersCollapse">
      <i class="fa-solid fa-filter me-1" aria-hidden="true"></i> Filtros
    </button>
  </div>

  <div id="filtersCollapse" class="collapse show">
    <div class="card mb-4">
      <div class="card-body">
        <form method="get" action="<?php echo $base; ?>/reports" class="row g-3" id="filtersForm">
          <div class="col-md-3">
            <label class="form-label">Ciclo</label>
            <small class="text-muted d-block">Selecciona el ciclo que quieres analizar.</small>
            <select class="form-select" name="ciclo" id="sel-ciclo" aria-label="Seleccionar ciclo" title="Selecciona el ciclo académico">
              <option value="">Todos</option>
              <?php if (!empty($cyclesList)) { foreach ($cyclesList as $c) { echo '<option value="'.htmlspecialchars($c).'">'.htmlspecialchars($c).'</option>'; } } ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Grupo</label>
            <small class="text-muted d-block">Filtra por grupo específico (opcional).</small>
            <select class="form-select" name="grupo_id" id="sel-grupo" aria-label="Seleccionar grupo" title="Lista filtrada por ciclo y profesor">
              <option value="">Todos</option>
              <?php if (!empty($groupsList)) { foreach ($groupsList as $g) { echo '<option value="'.(int)$g['id'].'">'.htmlspecialchars($g['ciclo']).' — '.htmlspecialchars($g['materia']).' / '.htmlspecialchars($g['nombre']).'</option>'; } } ?>
            </select>
          </div>
          <div class="col-md-3">
            <label class="form-label">Vista rápida</label>
            <small class="text-muted d-block">Aplicar filtros y refrescar indicadores.</small>
            <div class="d-grid gap-2">
              <button class="btn btn-primary" type="submit" aria-label="Aplicar filtros"><i class="fa-solid fa-filter me-1"></i> Aplicar filtros</button>
              <button class="btn btn-outline-secondary" type="button" id="btn-reset" aria-label="Limpiar filtros"><i class="fa-solid fa-rotate-left me-1"></i> Limpiar filtros</button>
            </div>
          </div>
          <div class="col-md-3">
            <label class="form-label d-flex justify-content-between align-items-center">
              <span>Opciones avanzadas</span>
              <button class="btn btn-link btn-sm p-0" type="button" data-bs-toggle="collapse" data-bs-target="#advancedFilters" aria-expanded="false" aria-controls="advancedFilters">
                Ajustar detalle
              </button>
            </label>
            <small class="text-muted d-block mb-2">Refina por materia, profesor, estado y riesgo.</small>
            <div id="advancedFilters" class="collapse">
              <div class="mb-2">
                <label class="form-label small mb-1">Materia</label>
                <select class="form-select form-select-sm" name="materia_id" id="sel-materia" aria-label="Seleccionar materia" title="Filtrar por materia">
                  <option value="">Todas</option>
                </select>
              </div>
              <?php if ($role === 'admin'): ?>
              <div class="mb-2">
                <label class="form-label small mb-1">Profesor</label>
                <select class="form-select form-select-sm" name="profesor_id" id="sel-prof" aria-label="Seleccionar profesor" title="Filtrar por profesor">
                  <option value="">Todos</option>
                  <?php if (!empty($profsList)) { foreach ($profsList as $p) { $label = ($p['email'] ?? '') ? ($p['nombre'].' ('.$p['email'].')') : $p['nombre']; echo '<option value="'.(int)$p['id'].'">'.htmlspecialchars($label).'</option>'; } } ?>
                </select>
              </div>
              <?php endif; ?>
              <div class="mb-2">
                <label class="form-label small mb-1">Estado</label>
                <select class="form-select form-select-sm" name="estado" id="sel-estado" aria-label="Seleccionar estado" title="Con final: usa calificación final; Pendientes: usa promedio de parciales">
                  <option value="">Todos</option>
                  <option value="con_final">Con final</option>
                  <option value="pendientes">Pendientes</option>
                </select>
              </div>
            </div>
            <button class="btn btn-outline-info btn-sm mt-2 w-100" type="button" id="btn-copy-link" aria-label="Copiar enlace con filtros"><i class="fa-solid fa-link me-1"></i> Copiar enlace con filtros</button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <div id="activeFiltersLine" class="mb-2 text-muted"></div>

  <?php 
    $actCsv = $base . ($isPublic ? '/app.php?r=/reports/export/csv' : '/reports/export/csv');
    $actPdf = $base . ($isPublic ? '/app.php?r=/reports/export/pdf' : '/reports/export/pdf');
    $actZip = $base . ($isPublic ? '/app.php?r=/reports/export/zip' : '/reports/export/zip');
    $actXls = $base . ($isPublic ? '/app.php?r=/reports/export/xlsx' : '/reports/export/xlsx');
  ?>
  <div class="d-flex justify-content-end mb-3 gap-2 flex-wrap">
    <form method="post" action="<?php echo $actCsv; ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
      <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
      <input type="hidden" name="materia_id" value="<?= htmlspecialchars($_GET['materia_id'] ?? '') ?>">
      <input type="hidden" name="estado" value="<?= htmlspecialchars($_GET['estado'] ?? '') ?>">
      <input type="hidden" name="riesgo_umbral" value="<?= htmlspecialchars($_GET['riesgo_umbral'] ?? '') ?>">
      <?php if ($role === 'admin'): ?>
        <input type="hidden" name="profesor_id" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
      <?php endif; ?>
      <button class="btn btn-outline-primary"><i class="fa-solid fa-file-csv me-1"></i> Exportar CSV</button>
    </form>
    <form method="post" action="<?php echo $actPdf; ?>" target="_blank">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
      <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
      <input type="hidden" name="materia_id" value="<?= htmlspecialchars($_GET['materia_id'] ?? '') ?>">
      <input type="hidden" name="estado" value="<?= htmlspecialchars($_GET['estado'] ?? '') ?>">
      <input type="hidden" name="riesgo_umbral" value="<?= htmlspecialchars($_GET['riesgo_umbral'] ?? '') ?>">
      <?php if ($role === 'admin'): ?>
        <input type="hidden" name="profesor_id" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
      <?php endif; ?>
      <button class="btn btn-outline-secondary"><i class="fa-solid fa-file-pdf me-1"></i> Exportar PDF</button>
    </form>
    <form method="post" action="<?php echo $actZip; ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
      <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
      <input type="hidden" name="materia_id" value="<?= htmlspecialchars($_GET['materia_id'] ?? '') ?>">
      <input type="hidden" name="estado" value="<?= htmlspecialchars($_GET['estado'] ?? '') ?>">
      <input type="hidden" name="riesgo_umbral" value="<?= htmlspecialchars($_GET['riesgo_umbral'] ?? '') ?>">
      <?php if ($role === 'admin'): ?>
        <input type="hidden" name="profesor_id" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
      <?php endif; ?>
      <button class="btn btn-outline-dark"><i class="fa-solid fa-file-zipper me-1"></i> Exportar ZIP</button>
    </form>
    <form method="post" action="<?php echo $actXls; ?>">
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
      <input type="hidden" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>">
      <input type="hidden" name="grupo_id" value="<?= htmlspecialchars($_GET['grupo_id'] ?? '') ?>">
      <input type="hidden" name="materia_id" value="<?= htmlspecialchars($_GET['materia_id'] ?? '') ?>">
      <input type="hidden" name="estado" value="<?= htmlspecialchars($_GET['estado'] ?? '') ?>">
      <input type="hidden" name="riesgo_umbral" value="<?= htmlspecialchars($_GET['riesgo_umbral'] ?? '') ?>">
      <?php if ($role === 'admin'): ?>
        <input type="hidden" name="profesor_id" value="<?= htmlspecialchars($_GET['profesor_id'] ?? '') ?>">
      <?php endif; ?>
      <button class="btn btn-outline-success"><i class="fa-solid fa-file-excel me-1"></i> Exportar Excel</button>
    </form>
  </div>

  <div class="row g-3">
    <div class="col-md-4">
          <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title d-flex align-items-center justify-content-between">
            <span id="summaryTitle">Resumen</span>
            <a id="btn-view-group" href="#" class="btn btn-sm btn-outline-primary" style="display:none" aria-label="Ver calificaciones del grupo"><i class="fa-solid fa-table me-1"></i> Ver grupo</a>
          </h5>
          <p class="text-muted small mb-2">Este resumen muestra el desempeño general según los filtros seleccionados.</p>
          <div id="summaryBox" class="text-muted">Cargando…</div>
        </div>
      </div>
    </div>
    <div class="col-md-8">
      <div class="card h-100">
        <div class="card-body">
          <h5 class="card-title mb-1"><span id="chartStatsTitle">Estadísticas</span></h5>
          <p class="text-muted small mb-2" id="chartStatsDescription">Cada barra representa el promedio del grupo o ciclo según los filtros actuales.</p>
          <canvas id="chartStats" height="160" aria-label="Gráfica de estadísticas"></canvas>
          <div id="chartStatsLoading" class="text-muted small mt-2" style="display:none">Cargando...</div>
          <div id="chartStatsEmpty" class="text-muted small mt-2" style="display:none">No hay datos para los filtros actuales. Prueba ajustar ciclo o grupo.</div>
          <hr>
          <h6 class="mt-3 mb-1" id="chartFailTitle">Reprobados por Materia (%)</h6>
          <p class="text-muted small mb-2" id="chartFailDescription">Cada barra muestra el porcentaje de alumnos reprobados o pendientes por materia.</p>
          <canvas id="chartFail" height="140" aria-label="Gráfica de reprobados o pendientes"></canvas>
          <div id="chartFailLoading" class="text-muted small mt-2" style="display:none">Cargando...</div>
          <div id="chartFailEmpty" class="text-muted small mt-2" style="display:none">No hay datos para los filtros actuales. Prueba ajustar ciclo o grupo.</div>
          <hr>
          <div class="d-flex justify-content-end mb-2">
            <button class="btn btn-sm btn-outline-primary" type="button" id="btn-export-tops" aria-label="Exportar Tops a CSV"><i class="fa-solid fa-file-csv me-1"></i> Exportar Tops (CSV)</button>
          </div>
          <div class="row g-3 mt-3">
            <div class="col-md-6">
              <h6>Top 5 grupos por promedio</h6>
              <div class="d-flex gap-2 mb-2">
                <button class="btn btn-sm btn-outline-info" type="button" id="btn-dl-chart-stats" aria-label="Descargar imagen de gráfica de estadísticas"><i class="fa-solid fa-download me-1"></i> Descargar gráfica</button>
              </div>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Promedio</th></tr></thead>
                  <tbody id="tbody-top-prom"></tbody>
                </table>
              </div>
            </div>
            <div class="col-md-6">
              <h6>Top 5 grupos por % reprobados</h6>
              <div class="d-flex gap-2 mb-2">
                <button class="btn btn-sm btn-outline-info" type="button" id="btn-dl-chart-fail" aria-label="Descargar imagen de gráfica de reprobados o pendientes"><i class="fa-solid fa-download me-1"></i> Descargar gráfica</button>
              </div>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th id="th-top-fail" class="text-end">% Reprobados</th></tr></thead>
                  <tbody id="tbody-top-fail"></tbody>
                </table>
              </div>
            </div>
          </div>
          <hr>
          <div class="row g-3 mt-3">
            <div class="col-md-6">
              <h6>Top 5 alumnos por promedio</h6>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>Matrícula</th><th>Alumno</th><th class="text-end">Promedio</th></tr></thead>
                  <tbody id="tbody-top-alum"></tbody>
                </table>
              </div>
            </div>
            <div class="col-md-6">
              <div class="d-flex align-items-center justify-content-between">
                <h6 id="riskTitle">Alumnos con riesgo (final < 60)</h6>
                <div class="input-group input-group-sm" style="max-width:200px">
                  <span class="input-group-text">Umbral</span>
                  <select class="form-select" id="sel-risk" aria-label="Umbral de riesgo" title="Se consideran en riesgo alumnos con final por debajo del umbral">
                    <option value="50">50</option>
                    <option value="55">55</option>
                    <option value="60" selected>60</option>
                    <option value="65">65</option>
                    <option value="70">70</option>
                  </select>
                </div>
              </div>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th>Alumno</th><th class="text-end">Final</th></tr></thead>
                  <tbody id="tbody-risk"></tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
window.__initialCatalogs = {
  cycles: <?php echo json_encode($cyclesList ?? []); ?>,
  groups: <?php echo json_encode($groupsList ?? []); ?>,
  profs: <?php echo json_encode($profsList ?? []); ?>,
  subjects: <?php echo json_encode($subjectsList ?? []); ?>
};
let chartStatsInst = null;
let chartFailInst = null;
const params = new URLSearchParams(window.location.search);
  const ROOT = '<?php echo $base; ?>';
  const CURRENT_ROLE = '<?php echo $role; ?>';
  // Genera rutas limpias para API y reportes, sin /app.php?r=...
  const api = (p) => ROOT + p;
  async function safeJson(res){ try { return await res.json(); } catch(e){ return { ok:false, message:'Respuesta inválida' }; } }
  function debounce(fn, delay=300){ let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), delay); }; }
  function refreshAll(){ updateSummary(); updateChart(); renderActiveFilters(); syncExportHiddenFields(); updateURLFromFilters(); saveFilters(); updateViewGroupButton(); }
  const refreshAllDebounced = debounce(refreshAll, 300);
  function findSubjectName(id){
    try { id = String(id||'').trim(); } catch(e){}
    const arr = Array.isArray(window.__initialCatalogs?.subjects) ? window.__initialCatalogs.subjects : [];
    const f = arr.find(x => String(x.id) === id);
    return f ? (f.nombre||'') : '';
  }
  function findGroupLabel(id){
    try { id = String(id||'').trim(); } catch(e){}
    const arr = Array.isArray(window.__initialCatalogs?.groups) ? window.__initialCatalogs.groups : [];
    const f = arr.find(x => String(x.id) === id);
    return f ? `${f.ciclo} — ${f.materia} / ${f.nombre}` : '';
  }
  function findProfName(id){
    try { id = String(id||'').trim(); } catch(e){}
    const arr = Array.isArray(window.__initialCatalogs?.profs) ? window.__initialCatalogs.profs : [];
    const f = arr.find(x => String(x.id) === id);
    return f ? `${f.nombre}${f.email?(' ('+f.email+')'):''}` : '';
  }
  function prettyEstado(e){
    if (e === 'pendientes') return 'Pendientes';
    if (e === 'con_final') return 'Con final';
    return 'Todos';
  }
  function getStoredFilters(){
    try {
      const v = localStorage.getItem('reports.filters');
      const obj = v ? JSON.parse(v) : {};
      if (!obj || typeof obj !== 'object') return {};
      if (CURRENT_ROLE !== 'admin') { try { delete obj.profesor_id; } catch(e){} }
      if (obj.role && obj.role !== CURRENT_ROLE) {
        return obj;
      }
      return obj;
    } catch(e){ return {}; }
  }
  function saveFilters(){
    const c = document.getElementById('sel-ciclo')?.value || '';
    const g = document.getElementById('sel-grupo')?.value || '';
    const pEl = document.getElementById('sel-prof');
    const p = pEl ? (pEl.value || '') : '';
    const m = document.getElementById('sel-materia')?.value || '';
    const e = document.getElementById('sel-estado')?.value || '';
    const r = document.getElementById('sel-risk')?.value || '';
    const obj = { ciclo: c, grupo_id: g, profesor_id: p, materia_id: m, estado: e, riesgo_umbral: r, role: CURRENT_ROLE };
    try { localStorage.setItem('reports.filters', JSON.stringify(obj)); } catch(err){}
  }
  function renderActiveFilters(){
    const c = document.getElementById('sel-ciclo')?.value || '';
    const g = document.getElementById('sel-grupo')?.value || '';
    const pEl = document.getElementById('sel-prof');
    const p = pEl ? (pEl.value || '') : '';
    const m = document.getElementById('sel-materia')?.value || '';
    const e = document.getElementById('sel-estado')?.value || '';
    const r = document.getElementById('sel-risk')?.value || '';
    const chips = [];
    if (c) chips.push(`<span class="badge bg-light text-dark border" title="Ciclo seleccionado">Ciclo: ${c}</span>`);
    if (g) { const gl = findGroupLabel(g)||`#${g}`; chips.push(`<span class="badge bg-light text-dark border" title="Grupo ID: ${g}">${gl}</span>`); }
    if (CURRENT_ROLE === 'admin' && p) { const pn = findProfName(p)||`#${p}`; chips.push(`<span class=\"badge bg-light text-dark border\" title=\"Profesor ID: ${p}\">Profesor: ${pn}</span>`); }
    if (m) { const mn = findSubjectName(m)||`#${m}`; chips.push(`<span class="badge bg-light text-dark border" title="Materia ID: ${m}">Materia: ${mn}</span>`); }
    if (e) chips.push(`<span class="badge bg-light text-dark border" title="Estado del filtro">Estado: ${prettyEstado(e)}</span>`);
    if (r) chips.push(`<span class="badge bg-light text-dark border" title="Umbral de riesgo">Umbral: ${r}</span>`);
    const el = document.getElementById('activeFiltersLine');
    if (el) el.innerHTML = chips.length ? chips.join(' ') : '';
  }
  function updateViewGroupButton(){
    const g = document.getElementById('sel-grupo')?.value || '';
    const btn = document.getElementById('btn-view-group');
    if (!btn) return;
    if (g) { btn.href = ROOT + '/grades/group?grupo_id=' + encodeURIComponent(g); btn.style.display = ''; }
    else { btn.style.display = 'none'; btn.href = '#'; }
  }
  function updateURLFromFilters(){
    const qs = new URLSearchParams();
    const c = document.getElementById('sel-ciclo')?.value || '';
    const g = document.getElementById('sel-grupo')?.value || '';
    const pEl = document.getElementById('sel-prof');
    const p = pEl ? (pEl.value || '') : '';
    const m = document.getElementById('sel-materia')?.value || '';
    const e = document.getElementById('sel-estado')?.value || '';
    const r = document.getElementById('sel-risk')?.value || '';
    if (c) qs.set('ciclo', c);
    if (g) qs.set('grupo_id', g);
    if (p) qs.set('profesor_id', p);
    if (m) qs.set('materia_id', m);
    if (e) qs.set('estado', e);
    if (r) qs.set('riesgo_umbral', r);
    const baseUrl = ROOT + (ROOT.endsWith('/public') ? '/app.php?r=/reports' : '/reports');
    const full = baseUrl + (qs.toString() ? ('?' + qs.toString()) : '');
    try { window.history.replaceState({}, '', full); } catch(err) {}
  }
  function syncExportHiddenFields(){
    const c = document.getElementById('sel-ciclo')?.value || '';
    const g = document.getElementById('sel-grupo')?.value || '';
    const pEl = document.getElementById('sel-prof');
    const p = pEl ? (pEl.value || '') : '';
    const m = document.getElementById('sel-materia')?.value || '';
    const e = document.getElementById('sel-estado')?.value || '';
    const r = document.getElementById('sel-risk')?.value || '';
    document.querySelectorAll('div.d-flex form').forEach(f => {
      const sc = f.querySelector('input[name="ciclo"]'); if (sc) sc.value = c;
      const sg = f.querySelector('input[name="grupo_id"]'); if (sg) sg.value = g;
      const sp = f.querySelector('input[name="profesor_id"]'); if (sp) sp.value = p;
      const sm = f.querySelector('input[name="materia_id"]'); if (sm) sm.value = m;
      const se = f.querySelector('input[name="estado"]'); if (se) se.value = e;
      const sr = f.querySelector('input[name="riesgo_umbral"]'); if (sr) sr.value = r;
    });
  }
  function resetFilters(){
    const url = ROOT + (ROOT.endsWith('/public') ? '/app.php?r=/reports' : '/reports');
    try { localStorage.removeItem('reports.filters'); } catch(e){}
    window.location.href = url;
  }
function updateSummary() {
  const ciclo = document.getElementById('sel-ciclo')?.value || '';
  const prof = document.getElementById('sel-prof')?.value || '';
  const grupo = document.getElementById('sel-grupo')?.value || '';
  const materia = document.getElementById('sel-materia')?.value || '';
  const estado = document.getElementById('sel-estado')?.value || '';
  const st = document.getElementById('summaryTitle');
  if (st) {
    let t = 'Resumen';
    const mName = materia ? findSubjectName(materia) : '';
    const gLabel = grupo ? findGroupLabel(grupo) : '';
    const eText = estado ? prettyEstado(estado) : '';
    const cText = ciclo ? ciclo : '';
    if (mName) t += ' — ' + mName;
    if (gLabel) t += ' — ' + gLabel;
    if (eText) t += ' — ' + eText;
    if (cText) t += ' — ' + cText;
    st.textContent = t;
  }
  const qs = new URLSearchParams();
  if (ciclo) qs.set('ciclo', ciclo);
  if (prof) qs.set('profesor_id', prof);
  if (grupo) qs.set('grupo_id', grupo);
  if (materia) qs.set('materia_id', materia);
  if (estado) qs.set('estado', estado);
  const resumenUrl = api('/reports/summary') + (qs.toString() ? ('?' + qs.toString()) : '');
  fetch(resumenUrl).then(safeJson).then(j => {
    const box = document.getElementById('summaryBox');
    if (!box) return;
    if (!j.ok) { box.textContent = j.message || 'No hay datos para los filtros actuales. Prueba cambiar el ciclo o grupo.'; return; }
    const prom = Number(j.data.promedio ?? 0);
    const promCls = isNaN(prom) ? 'text-muted' : (prom >= 70 ? 'text-success' : 'text-danger');
    box.innerHTML = `
    <div class="row g-2">
      <div class="col-md-6">
        <div><strong>Promedio:</strong> <span class="${promCls}">${isNaN(prom) ? '—' : prom}</span></div>
        <div><strong>Total con final:</strong> ${j.data.total_con_final ?? 0}</div>
      </div>
      <div class="col-md-6">
        <div><strong>Aprobadas:</strong> ${j.data.aprobadas ?? 0}</div>
        <div><strong>Pendientes:</strong> ${j.data.pendientes ?? 0}</div>
      </div>
      <div class="col-12"><strong>Reprobados:</strong> ${j.data.reprobados ?? 0} (${j.data.porcentaje_reprobados ?? 0}%)</div>
    </div>`;
  }).catch(() => {
    const box = document.getElementById('summaryBox');
    if (box) box.textContent = 'No hay datos para los filtros actuales. Prueba cambiar el ciclo o grupo.';
  });
}

function updateChart() {
  const ls = document.getElementById('chartStatsLoading'); if (ls) ls.style.display = '';
  const es = document.getElementById('chartStatsEmpty'); if (es) es.style.display = 'none';
  let chartUrl = api('/api/charts/promedios-ciclo');
  <?php if ($role === 'profesor'): ?>chartUrl = api('/api/charts/desempeño-grupo');<?php endif; ?>
  const qs = new URLSearchParams();
  const c = document.getElementById('sel-ciclo')?.value || '';
  const g = document.getElementById('sel-grupo')?.value || '';
  const m = document.getElementById('sel-materia')?.value || '';
  const e = document.getElementById('sel-estado')?.value || '';
  if (c) qs.set('ciclo', c);
  if (g) qs.set('grupo_id', g);
  if (m) qs.set('materia_id', m);
  if (e) qs.set('estado', e);
  chartUrl += (qs.toString() ? ('?' + qs.toString()) : '');
  fetch(chartUrl).then(safeJson).then(j => {
    const ctx = document.getElementById('chartStats');
    const emptyEl = document.getElementById('chartStatsEmpty');
    const loadEl = document.getElementById('chartStatsLoading');
    if (!j.ok) {
      if (emptyEl) emptyEl.style.display = '';
      if (loadEl) loadEl.style.display = 'none';
      if (typeof window.showToast === 'function') {
        window.showToast('error', j.message || 'No se pudieron cargar las estadísticas.');
      }
      return;
    }
    const isLine = (chartUrl.includes('promedios-ciclo'));
    const vals = (j.data.data || []).map(Number);
    const bgColors = vals.map(v => (isNaN(v) ? 'rgba(108,117,125,0.3)' : (v >= 70 ? 'rgba(25,135,84,0.4)' : 'rgba(220,53,69,0.4)')));
    const borderColors = vals.map(v => (isNaN(v) ? '#6c757d' : (v >= 70 ? '#198754' : '#dc3545')));
    const subjName = m ? findSubjectName(m) : '';
    const grpLabel = g ? findGroupLabel(g) : '';
    let titleBase = isLine ? 'Promedios por ciclo' : 'Promedios por grupo';
    if (subjName) titleBase += ' — ' + subjName;
    if (grpLabel) titleBase += ' — ' + grpLabel;
    if (e === 'pendientes') titleBase += ' — Pendientes'; else if (e === 'con_final') titleBase += ' — Con final';
    if (c) titleBase += ' — ' + c;
    const titleEl = document.getElementById('chartStatsTitle');
    if (titleEl) titleEl.textContent = titleBase;
    const labelBase = isLine ? (e==='pendientes' ? 'Promedio pendientes por ciclo' : 'Promedio final por ciclo') : (e==='pendientes' ? 'Promedio pendientes por grupo' : 'Promedio final por grupo');
    let labelText = labelBase;
    if (subjName) labelText += ' — ' + subjName;
    if (grpLabel) labelText += ' — ' + grpLabel;
    if (c) labelText += ' — ' + c;
    const config = {
      type: isLine ? 'line' : 'bar',
      data: {
        labels: j.data.labels,
        datasets: [{
          label: labelText,
          data: vals,
          borderColor: isLine ? borderColors : borderColors,
          backgroundColor: isLine ? bgColors : bgColors,
          pointBackgroundColor: isLine ? borderColors : undefined
        }]
      },
      options: { responsive: true, plugins: { legend: { display: true }, tooltip: { enabled: true } } }
    };
    if (chartStatsInst) { try { chartStatsInst.destroy(); } catch(e){} }
    if ((j.data.labels||[]).length === 0) { if (emptyEl) emptyEl.style.display = ''; } else { if (emptyEl) emptyEl.style.display = 'none'; chartStatsInst = new Chart(ctx, config); }
    if (loadEl) loadEl.style.display = 'none';
  }).catch(() => {
    const loadEl = document.getElementById('chartStatsLoading');
    const emptyEl = document.getElementById('chartStatsEmpty');
    if (loadEl) loadEl.style.display = 'none';
    if (emptyEl) emptyEl.style.display = '';
    if (typeof window.showToast === 'function') {
      window.showToast('error', 'Error de comunicación al cargar las estadísticas.');
    }
  });

  // Fail chart
  const lf = document.getElementById('chartFailLoading'); if (lf) lf.style.display = '';
  const ef = document.getElementById('chartFailEmpty'); if (ef) ef.style.display = 'none';
  let failUrl = api('/api/charts/reprobados');
  failUrl += (qs.toString() ? ('?' + qs.toString()) : '');
  fetch(failUrl).then(safeJson).then(j => {
    const ctxF = document.getElementById('chartFail');
    const emptyElF = document.getElementById('chartFailEmpty');
    const loadElF = document.getElementById('chartFailLoading');
    if (!j.ok) {
      if (emptyElF) emptyElF.style.display = '';
      if (loadElF) loadElF.style.display = 'none';
      if (typeof window.showToast === 'function') {
        window.showToast('error', j.message || 'No se pudo cargar la gráfica de reprobados.');
      }
      return;
    }
    const vals = (j.data.data || []).map(Number);
    const bg = vals.map(v => (isNaN(v) ? 'rgba(108,117,125,0.3)' : (v >= 30 ? 'rgba(220,53,69,0.4)' : 'rgba(25,135,84,0.4)')));
    const border = vals.map(v => (isNaN(v) ? '#6c757d' : (v >= 30 ? '#dc3545' : '#198754')));
    const failLabel = (e === 'pendientes') ? '% Pendientes' : '% Reprobados';
    const titleEl = document.getElementById('chartFailTitle');
    if (titleEl) {
      let t = (e === 'pendientes') ? 'Pendientes por Materia (%)' : 'Reprobados por Materia (%)';
      const subjName = m ? findSubjectName(m) : '';
      const grpLabel = g ? findGroupLabel(g) : '';
      if (subjName) t += ' — ' + subjName;
      if (grpLabel) t += ' — ' + grpLabel;
      if (c) t += ' — ' + c;
      titleEl.textContent = t;
    }
    if (chartFailInst) { try { chartFailInst.destroy(); } catch(e){} }
    const labelFailBase = failLabel;
    let labelFailText = labelFailBase;
    if (subjName) labelFailText += ' — ' + subjName;
    if (grpLabel) labelFailText += ' — ' + grpLabel;
    if (c) labelFailText += ' — ' + c;
    const thFail = document.getElementById('th-top-fail');
    if (thFail) { thFail.textContent = (e === 'pendientes') ? '% Pendientes' : '% Reprobados'; }
    const cfg = {
      type: 'bar',
      data: { labels: j.data.labels, datasets: [{ label: labelFailText, data: vals, backgroundColor: bg, borderColor: border, borderWidth: 1 }] },
      options: { responsive: true, plugins: { legend: { display: true } } }
    };
    if (chartFailInst) { try { chartFailInst.destroy(); } catch(e){} }
    if ((j.data.labels||[]).length === 0) { if (emptyElF) emptyElF.style.display = ''; } else { if (emptyElF) emptyElF.style.display = 'none'; chartFailInst = new Chart(ctxF, cfg); }
    if (loadElF) loadElF.style.display = 'none';
  }).catch(() => {
    const emptyElF = document.getElementById('chartFailEmpty');
    const loadElF = document.getElementById('chartFailLoading');
    if (loadElF) loadElF.style.display = 'none';
    if (emptyElF) emptyElF.style.display = '';
    if (typeof window.showToast === 'function') {
      window.showToast('error', 'Error de comunicación al cargar la gráfica de reprobados.');
    }
  });

  // Tops
  const qs2 = new URLSearchParams();
  if (c) qs2.set('ciclo', c);
  const pEl = document.getElementById('sel-prof');
  const p = pEl ? (pEl.value || '') : '';
  if (p) qs2.set('profesor_id', p);
  if (g) qs2.set('grupo_id', g);
  
  const r = document.getElementById('sel-risk')?.value || '60';
  if (m) qs2.set('materia_id', m);
  if (e) qs2.set('estado', e);
  if (r) qs2.set('riesgo_umbral', r);
  const rt = document.getElementById('riskTitle');
  if (rt) rt.textContent = `Alumnos con riesgo (final < ${r})`;
  fetch(api('/reports/tops') + (qs2.toString() ? ('?' + qs2.toString()) : ''))
    .then(safeJson).then(j => {
      const tp = document.getElementById('tbody-top-prom');
      const tf = document.getElementById('tbody-top-fail');
      const ta = document.getElementById('tbody-top-alum');
      const tr = document.getElementById('tbody-risk');
      if (!j.ok) {
        if (tp) tp.innerHTML = '<tr><td colspan="4" class="text-muted">Sin datos</td></tr>';
        if (tf) tf.innerHTML = '<tr><td colspan="4" class="text-muted">Sin datos</td></tr>';
        if (ta) ta.innerHTML = '<tr><td colspan="3" class="text-muted">Sin datos</td></tr>';
        if (tr) tr.innerHTML = '<tr><td colspan="5" class="text-muted">Sin datos</td></tr>';
        return;
      }
      const prom = j.data.top_promedios || [];
      const fail = j.data.top_reprobados || [];
      const talum = j.data.top_alumnos || [];
      const riesgo = j.data.alumnos_riesgo || [];
      if (tp) tp.innerHTML = prom.length ? prom.map(x => `<tr><td>${x.ciclo}</td><td>${x.materia}</td><td><a href="#" data-gid="${x.id||''}">${x.grupo}</a></td><td class="text-end">${Number(x.promedio||0).toFixed(2)}</td></tr>`).join('') : '<tr><td colspan="4" class="text-muted">Sin datos</td></tr>';
      if (tf) tf.innerHTML = fail.length ? fail.map(x => `<tr><td>${x.ciclo}</td><td>${x.materia}</td><td><a href="#" data-gid="${x.id||''}">${x.grupo}</a></td><td class="text-end">${Number(x.porcentaje||0).toFixed(2)}%</td></tr>`).join('') : '<tr><td colspan="4" class="text-muted">Sin datos</td></tr>';
      if (ta) ta.innerHTML = talum.length ? talum.map(x => `<tr><td>${x.matricula}</td><td>${x.alumno}</td><td class="text-end">${Number(x.promedio||0).toFixed(2)}</td></tr>`).join('') : '<tr><td colspan="3" class="text-muted">Sin datos</td></tr>';
      if (tr) tr.innerHTML = riesgo.length ? riesgo.map(x => `<tr><td>${x.ciclo}</td><td>${x.materia}</td><td>${x.grupo}</td><td>${x.alumno}</td><td class="text-end">${Number(x.final||0).toFixed(2)}</td></tr>`).join('') : '<tr><td colspan="5" class="text-muted">Sin datos</td></tr>';
    }).catch(() => {
      const tp = document.getElementById('tbody-top-prom');
      const tf = document.getElementById('tbody-top-fail');
      const ta = document.getElementById('tbody-top-alum');
      const tr = document.getElementById('tbody-risk');
      if (tp) tp.innerHTML = '<tr><td colspan="4" class="text-muted">Sin datos</td></tr>';
      if (tf) tf.innerHTML = '<tr><td colspan="4" class="text-muted">Sin datos</td></tr>';
      if (ta) ta.innerHTML = '<tr><td colspan="3" class="text-muted">Sin datos</td></tr>';
      if (tr) tr.innerHTML = '<tr><td colspan="5" class="text-muted">Sin datos</td></tr>';
    });
}

// Cargar combos
document.addEventListener('DOMContentLoaded', async () => {
  const selCiclo = document.getElementById('sel-ciclo');
  const selGrupo = document.getElementById('sel-grupo');
  const selProf = document.getElementById('sel-prof');
  const selMateria = document.getElementById('sel-materia');
  const selEstado = document.getElementById('sel-estado');
  const btnReset = document.getElementById('btn-reset');
  const btnCopy = document.getElementById('btn-copy-link');
  const btnDlStats = document.getElementById('btn-dl-chart-stats');
  const btnDlFail = document.getElementById('btn-dl-chart-fail');
    const btnExportTops = document.getElementById('btn-export-tops');
  const selRisk = document.getElementById('sel-risk');
  try {
    let cycles = Array.isArray(window.__initialCatalogs?.cycles) ? window.__initialCatalogs.cycles : [];
    let groups = Array.isArray(window.__initialCatalogs?.groups) ? window.__initialCatalogs.groups : [];
    let profs = Array.isArray(window.__initialCatalogs?.profs) ? window.__initialCatalogs.profs : [];
    let subjects = Array.isArray(window.__initialCatalogs?.subjects) ? window.__initialCatalogs.subjects : [];
    if (!cycles.length || !groups.length || (<?php echo json_encode($role==='admin'); ?> && !profs.length) || !subjects.length) {
      const [cyclesRes, groupsRes, profsRes] = await Promise.all([
        fetch(api('/api/catalogs/cycles')),
        fetch(<?php echo json_encode($role==='admin'); ?> ? api('/api/catalogs/groups_all') : api('/api/catalogs/groups')),
        <?php if ($role === 'admin'): ?>fetch(api('/api/catalogs/professors'))<?php else: ?>Promise.resolve({ok:true,json:async()=>[]})<?php endif; ?>
      ]);
      cycles = cyclesRes.ok ? await safeJson(cyclesRes) : cycles;
      groups = groupsRes.ok ? await safeJson(groupsRes) : groups;
      profs = profsRes.ok ? await safeJson(profsRes) : profs;
      try { const sRes = await fetch(api('/api/catalogs/subjects')); subjects = sRes.ok ? await safeJson(sRes) : subjects; } catch(e){}
    }
    if (!Array.isArray(cycles) || cycles.length === 0) { cycles = ['2024A','2024B']; }

    const stored = getStoredFilters();
    let selectedCiclo = params.get('ciclo') || stored.ciclo || '';
    const selectedGrupo = params.get('grupo_id') || stored.grupo_id || '';
    const selectedProf = params.get('profesor_id') || stored.profesor_id || '';
    const selectedMateria = params.get('materia_id') || stored.materia_id || '';
    const selectedEstado = params.get('estado') || stored.estado || '';
    const selectedRisk = params.get('riesgo_umbral') || stored.riesgo_umbral || '60';

    selCiclo.innerHTML = '<option value="">Todos</option>' + cycles.map(c => `<option value="${c}">${c}</option>`).join('');
    if (selectedCiclo) selCiclo.value = selectedCiclo; else if (cycles.length) { selCiclo.value = cycles[0]; selectedCiclo = cycles[0]; }

    function refreshGroupsOptions() {
      const c = selCiclo.value || '';
      const p = selProf ? (selProf.value || '') : '';
      const filtered = groups.filter(g => {
        const okC = c ? (String(g.ciclo) === c) : true;
        const okP = p ? (String(g.profesor_id || '') === String(p)) : true;
        return okC && okP;
      });
      selGrupo.innerHTML = '<option value="">Todos</option>' + filtered.map(g => `<option value="${g.id}">${g.ciclo} — ${g.materia} / ${g.nombre}</option>`).join('');
    }
    refreshGroupsOptions();
    if (selectedGrupo) selGrupo.value = selectedGrupo;

    if (selProf) {
      selProf.innerHTML = '<option value="">Todos</option>' + profs.map(p => `<option value="${p.id}">${p.nombre}${p.email?(' ('+p.email+')'):''}</option>`).join('');
      if (selectedProf) selProf.value = selectedProf;
    }

    selMateria.innerHTML = '<option value="">Todas</option>' + subjects.map(s => `<option value="${s.id}">${s.nombre}</option>`).join('');
    if (selectedMateria) selMateria.value = selectedMateria;
    if (selEstado && selectedEstado) selEstado.value = selectedEstado;
    if (selRisk && selectedRisk) selRisk.value = selectedRisk;

    selCiclo.addEventListener('change', () => { refreshGroupsOptions(); refreshAllDebounced(); });
    if (selProf) selProf.addEventListener('change', () => { refreshGroupsOptions(); refreshAllDebounced(); });
    selGrupo.addEventListener('change', () => { refreshAllDebounced(); });
    selMateria.addEventListener('change', () => { refreshAllDebounced(); });
    selEstado.addEventListener('change', () => { refreshAllDebounced(); });
    if (selRisk) selRisk.addEventListener('change', () => { refreshAllDebounced(); });
    document.addEventListener('click', (ev) => {
      const t = ev.target;
      if (t && t.matches('a[data-gid]')) {
        ev.preventDefault();
        const gid = t.getAttribute('data-gid') || '';
        if (gid) { selGrupo.value = gid; updateSummary(); updateChart(); renderActiveFilters(); syncExportHiddenFields(); updateURLFromFilters(); saveFilters(); }
      }
    });
    if (btnReset) btnReset.addEventListener('click', resetFilters);
    if (btnCopy) btnCopy.addEventListener('click', async () => { updateURLFromFilters(); try { await navigator.clipboard.writeText(window.location.href); const old = btnCopy.innerHTML; btnCopy.innerHTML = '<i class="fa-solid fa-check me-1"></i> Copiado'; setTimeout(() => { btnCopy.innerHTML = old; }, 1500); } catch(e){} });
    if (btnDlStats) btnDlStats.addEventListener('click', () => { try { const url = chartStatsInst?.toBase64Image(); if (url) { const a = document.createElement('a'); a.href = url; a.download = 'grafica_estadisticas.png'; a.click(); } } catch(e){} });
    if (btnDlFail) btnDlFail.addEventListener('click', () => { try { const url = chartFailInst?.toBase64Image(); if (url) { const a = document.createElement('a'); a.href = url; a.download = 'grafica_reprobados.png'; a.click(); } } catch(e){} });
    if (btnExportTops) btnExportTops.addEventListener('click', async () => {
      const c = document.getElementById('sel-ciclo')?.value || '';
      const g = document.getElementById('sel-grupo')?.value || '';
      const pEl = document.getElementById('sel-prof');
      const p = pEl ? (pEl.value || '') : '';
      const m = document.getElementById('sel-materia')?.value || '';
      const e = document.getElementById('sel-estado')?.value || '';
      const qs = new URLSearchParams();
      if (c) qs.set('ciclo', c);
      if (g) qs.set('grupo_id', g);
      if (p) qs.set('profesor_id', p);
      if (m) qs.set('materia_id', m);
      if (e) qs.set('estado', e);
      const url = api('/reports/tops') + (qs.toString() ? ('?' + qs.toString()) : '');
      const j = await fetch(url).then(safeJson);
      if (!j.ok) return;
      function esc(x){ const s = String(x ?? ''); return '"' + s.replaceAll('"','""') + '"'; }
      const lines = [];
      lines.push('"Top 5 grupos por promedio"');
      lines.push('Ciclo,Materia,Grupo,Promedio');
      (j.data.top_promedios||[]).forEach(r => { lines.push([esc(r.ciclo), esc(r.materia), esc(r.grupo), String(Number(r.promedio||0).toFixed(2))].join(',')); });
      lines.push('');
      lines.push('"Top 5 grupos por % ' + (e === 'pendientes' ? 'pendientes' : 'reprobados') + '"');
      lines.push('Ciclo,Materia,Grupo,% ' + (e === 'pendientes' ? 'Pendientes' : 'Reprobados'));
      (j.data.top_reprobados||[]).forEach(r => { lines.push([esc(r.ciclo), esc(r.materia), esc(r.grupo), String(Number(r.porcentaje||0).toFixed(2))].join(',')); });
      lines.push('');
      lines.push('"Top 5 alumnos por promedio"');
      lines.push('Matrícula,Alumno,Promedio');
      (j.data.top_alumnos||[]).forEach(r => { lines.push([esc(r.matricula), esc(r.alumno), String(Number(r.promedio||0).toFixed(2))].join(',')); });
      lines.push('');
      const riskSel = document.getElementById('sel-risk');
      const riskVal = riskSel ? (riskSel.value || '60') : '60';
      lines.push('"Alumnos con riesgo (final < '+riskVal+')"');
      lines.push('Ciclo,Materia,Grupo,Alumno,Final');
      (j.data.alumnos_riesgo||[]).forEach(r => { lines.push([esc(r.ciclo), esc(r.materia), esc(r.grupo), esc(r.alumno), String(Number(r.final||0).toFixed(2))].join(',')); });
      const blob = new Blob([lines.join('\r\n')], { type: 'text/csv;charset=utf-8;' });
      const a = document.createElement('a');
      a.href = URL.createObjectURL(blob);
      a.download = 'tops.csv';
      document.body.appendChild(a);
      a.click();
      URL.revokeObjectURL(a.href);
      a.remove();
    });
    updateSummary();
    updateChart();
    renderActiveFilters();
    syncExportHiddenFields();
    saveFilters();
    updateURLFromFilters();
    updateViewGroupButton();
  } catch (e) {
    console.warn('Error cargando combos', e);
    document.getElementById('summaryBox').textContent = 'Error al cargar catálogos';
  }
});

// Export respetando selects actuales
document.querySelectorAll('form[action$="/reports/export/csv"], form[action$="/reports/export/pdf"], form[action$="/reports/export/zip"], form[action$="/reports/export/xlsx"]').forEach(f => {
  f.addEventListener('submit', (ev) => {
    const c = document.getElementById('sel-ciclo')?.value || '';
    const g = document.getElementById('sel-grupo')?.value || '';
    const pEl = document.getElementById('sel-prof');
    const p = pEl ? (pEl.value || '') : '';
    const m = document.getElementById('sel-materia')?.value || '';
    const e = document.getElementById('sel-estado')?.value || '';
    const r = document.getElementById('sel-risk')?.value || '';
    const hc = f.querySelector('input[name="ciclo"]'); if (hc) hc.value = c;
    const hg = f.querySelector('input[name="grupo_id"]'); if (hg) hg.value = g;
    const hp = f.querySelector('input[name="profesor_id"]'); if (hp) hp.value = p;
    const hm = f.querySelector('input[name="materia_id"]'); if (hm) hm.value = m;
    const he = f.querySelector('input[name="estado"]'); if (he) he.value = e;
    const hr = f.querySelector('input[name="riesgo_umbral"]'); if (hr) hr.value = r;
  });
});
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
