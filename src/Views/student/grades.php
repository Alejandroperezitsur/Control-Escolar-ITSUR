<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Mis Calificaciones</h3>
    <div class="d-flex align-items-center gap-2">
      <form method="get" action="<?php echo $base; ?>/alumno/calificaciones" class="d-flex align-items-center">
        <select name="materia" class="form-select form-select-sm" style="max-width: 220px" data-bs-toggle="tooltip" title="Filtrar por materia">
          <option value="">Todas las materias</option>
          <?php foreach (($materias ?? []) as $m): $sel = isset($_GET['materia']) && $_GET['materia'] === $m ? 'selected' : ''; ?>
            <option value="<?= htmlspecialchars($m) ?>" <?= $sel ?>><?= htmlspecialchars($m) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="ciclo" class="form-select form-select-sm ms-2" style="max-width: 160px" data-bs-toggle="tooltip" title="Filtrar por ciclo escolar">
          <option value="">Todos los ciclos</option>
          <?php foreach (($ciclos ?? []) as $c): $sel = isset($_GET['ciclo']) && $_GET['ciclo'] === $c ? 'selected' : ''; ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $sel ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-primary ms-2" type="submit" data-bs-toggle="tooltip" title="Aplicar filtros">Filtrar</button>
      </form>
      <a href="<?php echo $base; ?>/alumno/calificaciones/export<?= isset($_GET['materia'])||isset($_GET['ciclo']) ? ('?'.http_build_query(['materia'=>($_GET['materia'] ?? ''),'ciclo'=>($_GET['ciclo'] ?? '')])) : '' ?>" class="btn btn-sm btn-outline-secondary" data-bs-toggle="tooltip" title="Exportar calificaciones a CSV"><i class="fa-solid fa-file-csv me-1"></i> Exportar CSV</a>
      <a href="<?php echo $base; ?>/dashboard" class="btn btn-sm btn-outline-secondary">Volver</a>
    </div>
</div>
  <div class="card">
    <div class="card-body">
      <div class="row g-3 mb-2">
        <div class="col-md-4">
          <div class="card text-bg-primary">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <i class="fa-solid fa-chart-line fa-2x me-3"></i>
                <div>
                  <div class="small">Promedio (filtros)</div>
                  <div class="h5 mb-0" id="flt-prom">—</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-bg-success">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <i class="fa-solid fa-book-open fa-2x me-3"></i>
                <div>
                  <div class="small">Materias (filtradas)</div>
                  <div class="h5 mb-0" id="flt-total">—</div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-md-4">
          <div class="card text-bg-warning">
            <div class="card-body">
              <div class="d-flex align-items-center">
                <i class="fa-solid fa-hourglass-half fa-2x me-3"></i>
                <div>
                  <div class="small">Pendientes</div>
                  <div class="h5 mb-0" id="flt-pend">—</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
          <thead>
            <tr>
              <th>Ciclo</th>
              <th>Materia</th>
              <th>Grupo</th>
              <th class="text-end">Parcial 1</th>
              <th class="text-end">Parcial 2</th>
              <th class="text-end">Final</th>
              <th class="text-end">Promedio</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($rows)): foreach ($rows as $r): ?>
            <?php
              $p1 = isset($r['parcial1']) && $r['parcial1'] !== '' ? (float)$r['parcial1'] : null;
              $p2 = isset($r['parcial2']) && $r['parcial2'] !== '' ? (float)$r['parcial2'] : null;
              $fin = isset($r['final']) && $r['final'] !== '' ? (float)$r['final'] : null;
              $prom = isset($r['promedio']) && $r['promedio'] !== '' ? (float)$r['promedio'] : null;
              $cls = function($v){ if ($v === null) return 'text-muted'; return $v >= 70 ? 'text-success' : 'text-danger'; };
            ?>
            <tr>
              <td><?= htmlspecialchars($r['ciclo']) ?></td>
              <td><?= htmlspecialchars($r['materia']) ?></td>
              <td><?= htmlspecialchars($r['grupo']) ?></td>
              <td class="text-end"><span class="<?= $cls($p1) ?>"><?= htmlspecialchars($r['parcial1'] ?? '') ?></span></td>
              <td class="text-end"><span class="<?= $cls($p2) ?>"><?= htmlspecialchars($r['parcial2'] ?? '') ?></span></td>
              <td class="text-end"><span class="<?= $cls($fin) ?>"><?= htmlspecialchars($r['final'] ?? '') ?></span></td>
              <td class="text-end"><span class="<?= $cls($prom) ?>"><?= htmlspecialchars($r['promedio'] ?? '') ?></span></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="7" class="text-muted">No hay calificaciones registradas.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="row g-3 mb-2">
    <div class="col-md-6">
      <div class="card text-bg-success">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <i class="fa-solid fa-check fa-2x me-3"></i>
            <div>
              <div class="small">Aprobadas</div>
              <div class="h5 mb-0" id="flt-aprob">—</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card text-bg-danger">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <i class="fa-solid fa-xmark fa-2x me-3"></i>
            <div>
              <div class="small">Reprobadas</div>
              <div class="h5 mb-0" id="flt-reprob">—</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
(() => {
  const form = document.querySelector('form[action$="/alumno/calificaciones"]');
  const matSel = form.querySelector('select[name="materia"]');
  const cicloSel = form.querySelector('select[name="ciclo"]');
  const base = '<?php echo $base; ?>';
  const updateMaterias = async () => {
    const params = new URLSearchParams();
    const c = cicloSel.value.trim();
    if (c) params.set('ciclo', c);
    const res = await fetch(`${base}/api/alumno/materias${params.toString() ? ('?'+params.toString()) : ''}`);
    const list = await res.json();
    const selVal = matSel.value;
    matSel.innerHTML = '<option value="">Todas las materias</option>' + (list||[]).map(m => `<option value="${m}">${m}</option>`).join('');
    const opt = Array.from(matSel.options).find(o => o.value === selVal);
    if (opt) opt.selected = true;
  };
  const updateSummary = async () => {
    const params = new URLSearchParams();
    const c = cicloSel.value.trim(); const m = matSel.value.trim();
    if (c) params.set('ciclo', c);
    if (m) params.set('materia', m);
    const res = await fetch(`${base}/api/alumno/calificaciones/resumen${params.toString() ? ('?'+params.toString()) : ''}`);
    const j = await res.json();
    document.getElementById('flt-prom').textContent = (j.promedio ?? 0).toFixed(2);
    document.getElementById('flt-total').textContent = j.total ?? 0;
    document.getElementById('flt-pend').textContent = j.pendientes ?? 0;
    document.getElementById('flt-aprob').textContent = j.aprobadas ?? 0;
    document.getElementById('flt-reprob').textContent = j.reprobadas ?? 0;
  };
  cicloSel.addEventListener('change', () => { updateMaterias(); updateSummary(); });
  matSel.addEventListener('change', updateSummary);
  // Inicial
  updateSummary();
})();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
