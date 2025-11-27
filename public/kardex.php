<?php
header('Location: app.php?r=/dashboard');
exit;
?>
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_server_view'])) {
  $token = $_POST['csrf_token'] ?? '';
  if (!$auth->validateCSRFToken($token)) {
    http_response_code(400);
    echo 'CSRF inválido';
    exit;
  }
  $label = trim((string)($_POST['label'] ?? 'Vista'));
  $data = [
    'f-materia' => (string)($_POST['f-materia'] ?? ''),
    'f-ciclo' => (string)($_POST['f-ciclo'] ?? ''),
    'f-min' => (string)($_POST['f-min'] ?? ''),
    'f-max' => (string)($_POST['f-max'] ?? ''),
    'f-aprobado' => isset($_POST['f-aprobado']) ? '1' : '0',
  ];
  try {
    (new SavedView())->create([
      'user_id' => (int)$user['id'],
      'page_key' => 'kardex:' . (trim((string)($_GET['matricula'] ?? ''))),
      'label' => $label !== '' ? $label : ('Vista ' . date('Y-m-d H:i')),
      'data_json' => json_encode($data, JSON_UNESCAPED_UNICODE),
    ]);
    $message = 'Vista guardada en servidor';
  } catch (Throwable $e) {
    $message = 'No se pudo guardar la vista';
  }
}
// Acceso: Admin puede consultar cualquier matrícula; Alumno solo su propio Kardex
$role = $_SESSION['role'] ?? '';
$isAdmin = ($role === 'admin');
$isAlumno = ($role === 'alumno');
if (!$isAdmin && !$isAlumno) {
  http_response_code(403);
  echo 'Acceso denegado';
  exit;
}

$alumnoModel = new Alumno();
$savedViewModel = new SavedView();
$matricula = trim((string)($_GET['matricula'] ?? ''));
if ($isAlumno) {
  // Forzar a la matrícula del alumno si no se especifica
  $matricula = $matricula !== '' ? $matricula : trim((string)($user['matricula'] ?? ''));
}
$alumno = null;
$historial = [];
if ($matricula !== '') {
  $alumno = $alumnoModel->findByMatricula($matricula);
  if ($alumno) {
    $profFilter = ($role === 'profesor') ? (int)($user['id'] ?? 0) : null;
    $historial = $alumnoModel->getWithCalificaciones((int)$alumno['id'], $profFilter);
  }
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Kardex</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <link href="assets/css/desktop-fixes.css" rel="stylesheet">
  <style>
  @media print {
    body * { visibility: hidden; }
    .print-list, .print-list * { visibility: visible; }
    .print-list { position: absolute; left:0; top:0; width:100%; }
    .btn, .form-control, .form-select, nav, header, .app-sidebar { display:none !important; }
  }
  </style>
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<div class="app-shell">
  <!-- Sidebar eliminado: accesos centralizados en dashboard -->
  <main class="app-content">
    <?php $pageTitle = 'Kardex'; ?>
    <div class="d-flex flex-column mb-3">
      <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
      <?php $breadcrumbs = [ ['label' => 'Inicio', 'url' => 'dashboard.php'], ['label' => $pageTitle, 'url' => null] ]; ?>
      <?php require __DIR__ . '/partials/breadcrumb.php'; ?>
    </div>
    <p class="text-muted">Consulta y gestión del historial académico del alumno.</p>

    <div class="card">
      <div class="card-body">
        <?php if (!$isAlumno): ?>
        <form class="row g-3" method="get">
          <div class="col-md-4">
            <label class="form-label">Matrícula del alumno</label>
            <input type="text" name="matricula" class="form-control" placeholder="Ej. S12345678" value="<?= htmlspecialchars($matricula) ?>">
          </div>
          <div class="col-md-3 align-self-end">
            <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i> Buscar</button>
          </div>
        </form>
        <?php endif; ?>
      </div>
    </div>

    <?php if ($alumno): ?>
    <div class="card mt-4">
      <div class="card-header d-flex justify-content-between align-items-center">
        <div>
          Historial de <?= htmlspecialchars($alumno['nombre'] ?? '') . ' ' . htmlspecialchars($alumno['apellido'] ?? '') ?> (<?= htmlspecialchars($alumno['matricula'] ?? '') ?>)
        </div>
        <div class="d-flex gap-2">
           <button class="btn btn-outline-primary btn-sm" data-export="csv" data-target="#tabla-kardex" data-filename="kardex_<?= htmlspecialchars($alumno['matricula'] ?? 'alumno') ?>.csv" data-timestamp="true"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
           <button class="btn btn-outline-secondary btn-sm" data-export="pdf" data-target="#tabla-kardex"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
          <a class="btn btn-outline-dark" target="_blank" href="kardex_formato.php?matricula=<?= urlencode($alumno['matricula'] ?? '') ?>"><i class="bi bi-file-earmark-text"></i> Formato oficial</a>
        </div>
      </div>
      <div class="card-body">
        <?php if ($message): ?><div class="alert alert-info" role="alert"><?= htmlspecialchars($message) ?></div><?php endif; ?>
        <div class="row g-3 mb-3">
          <div class="col-md-3">
            <label class="form-label">Materia</label>
            <input list="dl-materias" class="form-control" id="f-materia" placeholder="Nombre o clave">
            <datalist id="dl-materias">
              <?php
              $materiasOpts = [];
              if (!empty($historial)) {
                foreach ($historial as $h) {
                  $nombre = (string)($h['materia'] ?? '');
                  $clave = (string)($h['materia_clave'] ?? '');
                  $label = trim($nombre . ($clave !== '' ? " ($clave)" : ''));
                  if ($label !== '') { $materiasOpts[$label] = true; }
                }
              }
              foreach (array_keys($materiasOpts) as $opt) {
                echo '<option value="' . htmlspecialchars($opt) . '"></option>';
              }
              ?>
            </datalist>
          </div>
          <div class="col-md-3">
            <label class="form-label">Ciclo</label>
            <select class="form-select" id="f-ciclo">
              <option value="">Todos</option>
              <?php
              $ciclosOpts = [];
              if (!empty($historial)) {
                foreach ($historial as $h) {
                  $ciclo = trim((string)($h['grupo_ciclo'] ?? ''));
                  if ($ciclo !== '') { $ciclosOpts[$ciclo] = true; }
                }
              }
              foreach (array_keys($ciclosOpts) as $c) {
                echo '<option value="' . htmlspecialchars($c) . '">' . htmlspecialchars($c) . '</option>';
              }
              ?>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label">Final min</label>
            <input type="number" class="form-control" id="f-min" min="0" max="100" step="0.01">
          </div>
          <div class="col-md-2">
            <label class="form-label">Final max</label>
            <input type="number" class="form-control" id="f-max" min="0" max="100" step="0.01">
          </div>
          <div class="col-md-2 d-flex align-items-end">
            <div class="form-check">
              <input class="form-check-input" type="checkbox" id="f-aprobado">
              <label class="form-check-label" for="f-aprobado">Solo aprobados (≥ 70)</label>
            </div>
          </div>
        </div>
        <div class="row g-3 mb-3">
          <div class="col-md-4">
            <input type="text" class="form-control" id="f-nombre-vista" placeholder="Nombre de vista (opcional)">
          </div>
          <div class="col-md-2">
            <button class="btn btn-outline-primary w-100" id="f-guardar-vista"><i class="bi bi-save"></i> Guardar filtros</button>
          </div>
          <div class="col-md-3">
            <select class="form-select" id="f-vistas"><option value="">Cargar vista...</option></select>
          </div>
          <div class="col-md-3 d-flex gap-2">
            <button class="btn btn-outline-secondary flex-fill" id="f-limpiar"><i class="bi bi-x-circle"></i> Limpiar</button>
            <button class="btn btn-outline-success flex-fill" data-bs-toggle="collapse" data-bs-target="#server-views">Vistas servidor</button>
          </div>
        </div>
        <?php $serverViews = $savedViewModel->getByUserAndPage((int)$user['id'], 'kardex:' . ($alumno['matricula'] ?? '')); ?>
        <div class="collapse" id="server-views">
          <div class="card card-body">
            <form id="server-views-form" method="post" class="row g-3" novalidate>
              <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
              <input type="hidden" name="save_server_view" value="1">
              <div class="col-md-4">
                <label class="form-label">Nombre</label>
                <input type="text" class="form-control" name="label" placeholder="Ej. Aprobadas ciclo 2023">
              </div>
              <div class="col-md-4">
                <label class="form-label">Vistas guardadas</label>
                <select class="form-select" id="f-vistas-srv">
                  <option value="">Selecciona...</option>
                  <?php foreach ($serverViews as $sv): ?>
                    <option value='<?= htmlspecialchars($sv['data_json'] ?? '{}') ?>'><?= htmlspecialchars($sv['label'] ?? '') ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-success"><i class="bi bi-cloud-check"></i> Guardar vista en servidor</button>
                <button type="button" class="btn btn-outline-primary" id="btn-cargar-srv"><i class="bi bi-cloud-download"></i> Cargar vista</button>
              </div>
            </form>
          </div>
        </div>

        <div class="alert alert-secondary" id="totales" role="alert">
          <strong>Totales:</strong>
          Materias: <span id="t-count">0</span> · Promedio final: <span id="t-prom">0</span> · Aprobadas: <span id="t-aprob">0</span> · Reprobadas: <span id="t-reprob">0</span>
        </div>
        <div class="table-responsive print-list">
          <table id="tabla-kardex" class="table table-striped table-hover">
            <thead>
              <tr>
                <th>Grupo</th>
                <th>Materia</th>
                <th>Ciclo</th>
                <th>Parcial 1</th>
                <th>Parcial 2</th>
                <th>Final</th>
                <th>Promedio</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($historial)): foreach ($historial as $h): ?>
              <tr>
                <td><?= htmlspecialchars($h['grupo_nombre'] ?? ($h['grupo_id'] ?? '')) ?></td>
                <td><?= htmlspecialchars(($h['materia'] ?? '') . (isset($h['materia_clave']) ? ' (' . $h['materia_clave'] . ')' : '')) ?></td>
                <td><?= htmlspecialchars($h['grupo_ciclo'] ?? '') ?></td>
                <td><?= htmlspecialchars((string)($h['parcial1'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($h['parcial2'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($h['final'] ?? '')) ?></td>
                <td><?= htmlspecialchars((string)($h['promedio'] ?? '')) ?></td>
              </tr>
              <?php endforeach; else: ?>
              <tr><td colspan="6">Sin calificaciones registradas.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
        <div class="row mt-3">
          <div class="col-md-6">
            <h6>Promedio por ciclo</h6>
            <ul id="sum-por-ciclo" class="list-group list-group-flush"></ul>
          </div>
          <div class="col-md-6">
            <h6>Aprobadas/Reprobadas</h6>
            <canvas id="chart-aprob" height="120"></canvas>
            <h6 class="mt-3">Promedio por ciclo (gráfica)</h6>
            <canvas id="chart-ciclo" height="120"></canvas>
          </div>
        </div>
      </div>
    </div>
    <?php elseif ($matricula !== ''): ?>
      <div class="alert alert-warning mt-4">No se encontró alumno con matrícula <?= htmlspecialchars($matricula) ?>.</div>
    <?php endif; ?>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
  (function(){
    const tabla = document.getElementById('tabla-kardex');
    const inputs = {
      materia: document.getElementById('f-materia'),
      ciclo: document.getElementById('f-ciclo'),
      min: document.getElementById('f-min'),
      max: document.getElementById('f-max'),
      aprobado: document.getElementById('f-aprobado')
    };
    const vistasSel = document.getElementById('f-vistas');
    const nombreVista = document.getElementById('f-nombre-vista');
    const storageKey = 'kardex_filters_' + <?= json_encode($alumno['matricula'] ?? 'alumno') ?>;
    // Carga de vista desde servidor
    document.getElementById('btn-cargar-srv')?.addEventListener('click', ()=>{
      const sel = document.getElementById('f-vistas-srv');
      try {
        const json = sel?.value || '{}';
        const data = JSON.parse(json);
        TableUtils.applyFilters(Object.values(inputs), { data });
      } catch {}
    });
    // Inyecta filtros actuales al formulario de guardado antes de enviar
    document.getElementById('server-views-form')?.addEventListener('submit', (e)=>{
      const form = e.target;
      const ensure = (name, value)=>{
        let input = form.querySelector(`[name="${name}"]`);
        if (!input) { input = document.createElement('input'); input.type = 'hidden'; input.name = name; form.appendChild(input); }
        input.value = value;
      };
      ensure('f-materia', inputs.materia?.value || '');
      ensure('f-ciclo', inputs.ciclo?.value || '');
      ensure('f-min', inputs.min?.value || '');
      ensure('f-max', inputs.max?.value || '');
      ensure('f-aprobado', inputs.aprobado?.checked ? '1' : '0');
    });

    function filtrar() {
      let count = 0, sumFinal = 0, aprob = 0, reprob = 0;
      const materiaQ = (inputs.materia.value || '').toLowerCase();
      const cicloQ = (inputs.ciclo.value || '').toLowerCase();
      const min = inputs.min.value !== '' ? parseFloat(inputs.min.value) : -Infinity;
      const max = inputs.max.value !== '' ? parseFloat(inputs.max.value) : Infinity;
      const onlyAprob = inputs.aprobado.checked;

      const rows = Array.from(tabla.querySelectorAll('tbody tr'));
      const sumPorCiclo = {};
      rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        const grupo = (cells[0]?.textContent || '').toLowerCase();
        const materia = (cells[1]?.textContent || '').toLowerCase();
        const ciclo = (cells[2]?.textContent || '').toLowerCase();
        const finalStr = (cells[5]?.textContent || '').trim();
        const final = finalStr === '' ? NaN : parseFloat(finalStr);

        let visible = true;
        if (materiaQ && !materia.includes(materiaQ)) visible = false;
        if (cicloQ && !ciclo.includes(cicloQ)) visible = false;
        if (!isNaN(final)) {
          if (final < min || final > max) visible = false;
          if (onlyAprob && final < 70) visible = false;
        } else {
          if (!isFinite(min) || !isFinite(max) || onlyAprob) { /* sin final no cumple filtros */
            visible = false;
          }
        }

        row.style.display = visible ? '' : 'none';
        if (visible && !isNaN(final)) {
          count++; sumFinal += final; aprob += (final >= 70) ? 1 : 0; reprob += (final < 70) ? 1 : 0;
          const cicloKey = (cells[2]?.textContent || '').trim();
          if (!sumPorCiclo[cicloKey]) sumPorCiclo[cicloKey] = {count:0,sum:0};
          sumPorCiclo[cicloKey].count++; sumPorCiclo[cicloKey].sum += final;
        }
      });

      document.getElementById('t-count').textContent = String(count);
      document.getElementById('t-prom').textContent = count ? (sumFinal / count).toFixed(2) : '0';
      document.getElementById('t-aprob').textContent = String(aprob);
      document.getElementById('t-reprob').textContent = String(reprob);

      // resumen por ciclo
      const list = document.getElementById('sum-por-ciclo');
      list.innerHTML = '';
      Object.entries(sumPorCiclo).sort((a,b)=>a[0].localeCompare(b[0])).forEach(([ciclo,val])=>{
        const li = document.createElement('li');
        li.className = 'list-group-item';
        const prom = (val.count ? (val.sum / val.count).toFixed(2) : '0');
        li.textContent = `${ciclo}: ${val.count} materias · Promedio ${prom}`;
        list.appendChild(li);
      });
      // chart aprob/reprob
      updateChart(aprob, reprob);
      updateCicloBar(sumPorCiclo);
    }

    Object.values(inputs).forEach(el => el && el.addEventListener('input', filtrar));
    // poblar selects/datalist
    const materias = TableUtils.collectUniqueColumnValues(tabla, 1);
    const dl = document.getElementById('dl-materias');
    materias.forEach(m => { const opt = document.createElement('option'); opt.value = m; dl.appendChild(opt); });
    const ciclos = TableUtils.collectUniqueColumnValues(tabla, 2);
    const cicloSel = document.getElementById('f-ciclo');
    ciclos.forEach(c => { const opt = document.createElement('option'); opt.value = c; opt.textContent = c; cicloSel.appendChild(opt); });

    // ordenar por encabezado
    TableUtils.enableTableSort(tabla);

    // guardado de filtros
    document.getElementById('f-guardar-vista').addEventListener('click', (e)=>{
      e.preventDefault();
      TableUtils.saveFilters(Object.values(inputs), storageKey, nombreVista.value.trim());
      refreshVistas();
    });
    document.getElementById('f-limpiar').addEventListener('click', (e)=>{
      e.preventDefault(); Object.values(inputs).forEach(el=>{ if(el.type==='checkbox') el.checked=false; else el.value=''; }); filtrar();
    });
    vistasSel.addEventListener('change', ()=>{
      const list = TableUtils.loadFilterList(storageKey);
      const idx = parseInt(vistasSel.value);
      if (!isNaN(idx) && list[idx]) { TableUtils.applyFilters(Object.values(inputs), list[idx]); }
    });
    function refreshVistas(){
      const list = TableUtils.loadFilterList(storageKey);
      vistasSel.innerHTML = '<option value="">Cargar vista...</option>';
      list.forEach((rec, i) => {
        const opt = document.createElement('option'); opt.value = String(i); opt.textContent = rec.label || ('Vista ' + (i+1)); vistasSel.appendChild(opt);
      });
    }
    let chartInstance = null;
    let cicloBarInstance = null;
    function updateChart(aprob, reprob){
      const ctx = document.getElementById('chart-aprob');
      if (!ctx) return;
      const data = { labels:['Aprobadas','Reprobadas'], datasets:[{ data:[aprob,reprob], backgroundColor:['#28a745','#dc3545'] }] };
      if (chartInstance) { chartInstance.data = data; chartInstance.update(); }
      else { chartInstance = new Chart(ctx, { type:'doughnut', data }); }
    }

    function updateCicloBar(sumPorCiclo){
      const ctx = document.getElementById('chart-ciclo');
      if (!ctx) return;
      const labels = Object.keys(sumPorCiclo);
      const dataVals = labels.map(k => {
        const rec = sumPorCiclo[k];
        return rec.count ? (rec.sum / rec.count) : 0;
      });
      const data = { labels, datasets:[{ label:'Promedio por ciclo', data:dataVals, backgroundColor:'#0d6efd' }] };
      const options = { scales:{ y:{ beginAtZero:true, max:100 } } };
      if (cicloBarInstance) { cicloBarInstance.data = data; cicloBarInstance.update(); }
      else { cicloBarInstance = new Chart(ctx, { type:'bar', data, options }); }
    }

    refreshVistas();
    filtrar();
  })();
</script>
</body>
</html>
