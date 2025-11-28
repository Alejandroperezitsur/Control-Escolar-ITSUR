<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$csrf = $_SESSION['csrf_token'] ?? '';
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Materias <span class="badge bg-light text-dark ms-2"><?= (int)($pagination['total'] ?? 0) ?></span><span class="text-muted fs-6 ms-2">Página <?= (int)($pagination['page'] ?? 1) ?> de <?= (int)($pagination['pages'] ?? 1) ?></span></h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>
  <?php $flash = $_SESSION['flash'] ?? null; $flashType = $_SESSION['flash_type'] ?? 'info'; unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
  <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index:1100"></div>

  <div class="card mb-3">
    <div class="card-body">
      <form method="get" action="/public/app.php?r=/subjects" class="d-flex align-items-center">
        <input type="text" name="q" value="<?= htmlspecialchars((string)($pagination['q'] ?? '')) ?>" class="form-control" placeholder="Buscar por nombre/clave" style="max-width:320px" data-bs-toggle="tooltip" title="Buscar por nombre o clave de materia">
        <select name="carrera" class="form-select ms-2" style="max-width:180px" data-bs-toggle="tooltip" title="Filtrar por carrera">
          <?php $selCar = (string)($pagination['carrera'] ?? ''); ?>
          <option value="" <?= $selCar===''?'selected':'' ?>>Todas las carreras</option>
          <?php foreach (($carrerasList ?? []) as $car): $cl = (string)($car['clave'] ?? ''); ?>
            <option value="<?= htmlspecialchars($cl) ?>" <?= $selCar === $cl ? 'selected' : '' ?>><?= htmlspecialchars($car['nombre'] ?? $cl) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="estado" class="form-select ms-2" style="max-width:180px" data-bs-toggle="tooltip" title="Filtrar por estado de oferta">
          <?php $selEstado = (string)($pagination['estado'] ?? ''); ?>
          <option value="" <?= $selEstado===''?'selected':'' ?>>Todos</option>
          <option value="con_grupos" <?= $selEstado==='con_grupos'?'selected':'' ?>>Con grupos</option>
          <option value="sin_grupos" <?= $selEstado==='sin_grupos'?'selected':'' ?>>Sin grupos</option>
        </select>
        <select name="ciclo" class="form-select ms-2" style="max-width:160px" data-bs-toggle="tooltip" title="Filtrar por ciclo escolar">
          <?php $selCiclo = (string)($pagination['ciclo'] ?? ''); ?>
          <option value="" <?= $selCiclo===''?'selected':'' ?>>Todos los ciclos</option>
          <?php foreach (($cyclesList ?? []) as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= $selCiclo === (string)$c ? 'selected' : '' ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="per_page" class="form-select ms-2" style="max-width:120px" data-bs-toggle="tooltip" title="Resultados por página">
          <?php $selPer = (int)($pagination['per_page'] ?? 10); ?>
          <option value="10" <?= $selPer===10?'selected':'' ?>>10</option>
          <option value="25" <?= $selPer===25?'selected':'' ?>>25</option>
          <option value="50" <?= $selPer===50?'selected':'' ?>>50</option>
        </select>
        <button class="btn btn-outline-primary ms-2" type="submit" data-bs-toggle="tooltip" title="Buscar"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
    </div>
  </div>

  <div class="d-flex justify-content-end mb-2">
    <?php $qv = urlencode((string)($pagination['q'] ?? '')); $carv = urlencode((string)($pagination['carrera'] ?? '')); $cicv = urlencode((string)($pagination['ciclo'] ?? '')); $estv = urlencode((string)($pagination['estado'] ?? '')); $ppv = urlencode((string)($pagination['per_page'] ?? '')); ?>
    <a class="btn btn-sm btn-outline-primary me-2" href="<?php echo $base; ?>/subjects/export/csv<?= ($qv!==''||$carv!==''||$cicv!==''||$estv!=='')?('?'.implode('&', array_filter([
      $qv!==''?('q='.$qv):'', $carv!==''?('carrera='.$carv):'', $cicv!==''?('ciclo='.$cicv):'', $estv!==''?('estado='.$estv):'', $ppv!==''?('per_page='.$ppv):''
    ]))):'' ?>" data-bs-toggle="tooltip" title="Exportar a CSV"><i class="bi bi-filetype-csv"></i> Exportar CSV</a>
    <a class="btn btn-sm btn-outline-success me-2" href="<?php echo $base; ?>/subjects/export/xlsx<?= ($qv!==''||$carv!==''||$cicv!==''||$estv!=='')?('?'.implode('&', array_filter([
      $qv!==''?('q='.$qv):'', $carv!==''?('carrera='.$carv):'', $cicv!==''?('ciclo='.$cicv):'', $estv!==''?('estado='.$estv):'', $ppv!==''?('per_page='.$ppv):''
    ]))):'' ?>" data-bs-toggle="tooltip" title="Exportar a Excel"><i class="bi bi-filetype-xlsx"></i> Exportar XLSX</a>
    <a class="btn btn-sm btn-outline-secondary" href="<?php echo $base; ?>/subjects/export/pdf<?= ($qv!==''||$carv!==''||$cicv!==''||$estv!=='')?('?'.implode('&', array_filter([
      $qv!==''?('q='.$qv):'', $carv!==''?('carrera='.$carv):'', $cicv!==''?('ciclo='.$cicv):'', $estv!==''?('estado='.$estv):'', $ppv!==''?('per_page='.$ppv):''
    ]))):'' ?>" data-bs-toggle="tooltip" title="Exportar a PDF"><i class="bi bi-filetype-pdf"></i> Exportar PDF</a>
  </div>

  <?php if ((string)($pagination['estado'] ?? '') === 'sin_grupos'): ?>
    <div class="alert alert-warning py-2 px-3" role="alert">
      <?php $sc = (string)($pagination['ciclo'] ?? ''); ?>
      Mostrando solo materias sin oferta<?= $sc!=='' ? (' en ciclo ' . htmlspecialchars($sc)) : '' ?>.
    </div>
  <?php endif; ?>
  <?php if ((string)($pagination['estado'] ?? '') === 'con_grupos'): ?>
    <div class="alert alert-info py-2 px-3" role="alert">
      <?php $sc = (string)($pagination['ciclo'] ?? ''); ?>
      Mostrando solo materias con oferta<?= $sc!=='' ? (' en ciclo ' . htmlspecialchars($sc)) : '' ?>.
    </div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-body">
      <form method="post" action="/public/app.php?r=/subjects/create" class="row g-2 needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-md-5"><input class="form-control" name="nombre" placeholder="Nombre" required data-bs-toggle="tooltip" title="Nombre de la materia"><div class="invalid-feedback">Ingresa el nombre.</div></div>
        <div class="col-md-3"><input class="form-control" name="clave" placeholder="Clave" required data-bs-toggle="tooltip" title="Clave única de la materia"><div class="invalid-feedback">Ingresa la clave.</div></div>
        <div class="col-md-4"><button class="btn btn-primary" type="submit" data-bs-toggle="tooltip" title="Guardar nueva materia"><i class="fa-solid fa-plus me-1"></i> Agregar materia</button></div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <?php if (empty($subjects)): ?>
      <div class="alert alert-light border py-2 px-3" role="alert">
        No hay resultados con los filtros seleccionados. <a class="alert-link" href="<?php echo $base; ?>/subjects">Limpiar filtros</a>
        <?php 
          $fq = trim((string)($pagination['q'] ?? '')); 
          $fcar = (string)($pagination['carrera'] ?? ''); 
          $fciclo = (string)($pagination['ciclo'] ?? ''); 
          $fest = (string)($pagination['estado'] ?? ''); 
          $hasAny = ($fq!==''||$fcar!==''||$fciclo!==''||$fest!=='');
          $partsQ = [];
          if ($fcar!=='') { $partsQ[] = 'carrera=' . urlencode($fcar); }
          if ($fciclo!=='') { $partsQ[] = 'ciclo=' . urlencode($fciclo); }
          if ($fest!=='') { $partsQ[] = 'estado=' . urlencode($fest); }
          $qsWithoutQ = implode('&', $partsQ);
          $partsCar = [];
          if ($fq!=='') { $partsCar[] = 'q=' . urlencode($fq); }
          if ($fciclo!=='') { $partsCar[] = 'ciclo=' . urlencode($fciclo); }
          if ($fest!=='') { $partsCar[] = 'estado=' . urlencode($fest); }
          $qsWithoutCar = implode('&', $partsCar);
          $partsCiclo = [];
          if ($fq!=='') { $partsCiclo[] = 'q=' . urlencode($fq); }
          if ($fcar!=='') { $partsCiclo[] = 'carrera=' . urlencode($fcar); }
          if ($fest!=='') { $partsCiclo[] = 'estado=' . urlencode($fest); }
          $qsWithoutCiclo = implode('&', $partsCiclo);
          $partsEst = [];
          if ($fq!=='') { $partsEst[] = 'q=' . urlencode($fq); }
          if ($fcar!=='') { $partsEst[] = 'carrera=' . urlencode($fcar); }
          if ($fciclo!=='') { $partsEst[] = 'ciclo=' . urlencode($fciclo); }
          $qsWithoutEst = implode('&', $partsEst);
        ?>
        <?php if ($hasAny): ?>
          <div class="mt-2">
            <span class="text-muted">Filtros activos:</span>
            <?php if ($fq!==''): ?>
              <a class="badge bg-secondary text-decoration-none ms-1" href="<?php echo $base; ?>/subjects<?= $qsWithoutQ!=='' ? ('?' . $qsWithoutQ) : '' ?>" title="Quitar búsqueda">Búsqueda: <?= htmlspecialchars($fq) ?> ✕</a>
            <?php endif; ?>
            <?php if ($fcar!==''): ?>
              <a class="badge bg-secondary text-decoration-none ms-1" href="<?php echo $base; ?>/subjects<?= $qsWithoutCar!=='' ? ('?' . $qsWithoutCar) : '' ?>" title="Quitar carrera">Carrera: <?= htmlspecialchars($fcar) ?> ✕</a>
            <?php endif; ?>
            <?php if ($fciclo!==''): ?>
              <a class="badge bg-secondary text-decoration-none ms-1" href="<?php echo $base; ?>/subjects<?= $qsWithoutCiclo!=='' ? ('?' . $qsWithoutCiclo) : '' ?>" title="Quitar ciclo">Ciclo: <?= htmlspecialchars($fciclo) ?> ✕</a>
            <?php endif; ?>
            <?php if ($fest!==''): ?>
              <a class="badge bg-secondary text-decoration-none ms-1" href="<?php echo $base; ?>/subjects<?= $qsWithoutEst!=='' ? ('?' . $qsWithoutEst) : '' ?>" title="Quitar estado">Estado: <?= htmlspecialchars($fest) ?> ✕</a>
            <?php endif; ?>
          </div>
        <?php endif; ?>
      </div>
    <?php endif; ?>
    <table class="table table-striped table-hover">
      <thead><tr>
        <?php $sort = (string)($pagination['sort'] ?? 'nombre'); $order = (string)($pagination['order'] ?? 'ASC'); $pg = (int)($pagination['page'] ?? 1); $qv = urlencode((string)($pagination['q'] ?? '')); $toggle = $order==='ASC'?'DESC':'ASC'; $ppv = urlencode((string)($pagination['per_page'] ?? '')); ?>
        <th><a href="<?php echo $base; ?>/subjects?page=<?= $pg ?>&sort=id&order=<?= $sort==='id'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?><?= $selCar!==''?"&carrera=$selCar":'' ?><?= $selCiclo!==''?"&ciclo=$selCiclo":'' ?><?= $selEstado!==''?"&estado=$selEstado":'' ?><?= $ppv!==''?"&per_page=$ppv":'' ?>">ID<?= $sort==='id' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th><a href="<?php echo $base; ?>/subjects?page=<?= $pg ?>&sort=nombre&order=<?= $sort==='nombre'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?><?= $selCar!==''?"&carrera=$selCar":'' ?><?= $selCiclo!==''?"&ciclo=$selCiclo":'' ?><?= $selEstado!==''?"&estado=$selEstado":'' ?><?= $ppv!==''?"&per_page=$ppv":'' ?>">Nombre<?= $sort==='nombre' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th><a href="<?php echo $base; ?>/subjects?page=<?= $pg ?>&sort=clave&order=<?= $sort==='clave'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?><?= $selCar!==''?"&carrera=$selCar":'' ?><?= $selCiclo!==''?"&ciclo=$selCiclo":'' ?><?= $selEstado!==''?"&estado=$selEstado":'' ?><?= $ppv!==''?"&per_page=$ppv":'' ?>">Clave<?= $sort==='clave' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th><a href="<?php echo $base; ?>/subjects?page=<?= $pg ?>&sort=carreras&order=<?= $sort==='carreras'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?><?= $selCar!==''?"&carrera=$selCar":'' ?><?= $selCiclo!==''?"&ciclo=$selCiclo":'' ?><?= $selEstado!==''?"&estado=$selEstado":'' ?><?= $ppv!==''?"&per_page=$ppv":'' ?>">Carreras<?= $sort==='carreras' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th><a href="<?php echo $base; ?>/subjects?page=<?= $pg ?>&sort=grupos&order=<?= $sort==='grupos'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?><?= $selCar!==''?"&carrera=$selCar":'' ?><?= $selCiclo!==''?"&ciclo=$selCiclo":'' ?><?= $selEstado!==''?"&estado=$selEstado":'' ?><?= $ppv!==''?"&per_page=$ppv":'' ?>" data-bs-toggle="tooltip" title="Número de grupos activos">Grupos<?= $sort==='grupos' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th><a href="<?php echo $base; ?>/subjects?page=<?= $pg ?>&sort=promedio&order=<?= $sort==='promedio'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?><?= $selCar!==''?"&carrera=$selCar":'' ?><?= $selCiclo!==''?"&ciclo=$selCiclo":'' ?><?= $selEstado!==''?"&estado=$selEstado":'' ?><?= $ppv!==''?"&per_page=$ppv":'' ?>" data-bs-toggle="tooltip" title="Promedio general de la materia">Promedio<?= $sort==='promedio' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th class="text-end">Acciones</th>
      </tr></thead>
      <tbody>
        <?php foreach (($subjects ?? []) as $s): ?>
        <tr>
          <td><?= htmlspecialchars($s['id']) ?></td>
          <td><a href="<?= $base; ?>/subjects/detail?id=<?= (int)$s['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($s['nombre']) ?></a></td>
          <td><?= htmlspecialchars($s['clave'] ?? '') ?></td>
          <td><?= htmlspecialchars($s['carreras'] ?? '—') ?></td>
          <td>
            <?php $g = isset($s['grupos']) ? (int)$s['grupos'] : null; $ttl = ($selCiclo!=='') ? ('Ciclo '.$selCiclo) : 'Todos los ciclos'; ?>
            <?php if ($g === null): ?>
              —
            <?php elseif ($g > 0): ?>
              <span class="badge bg-success" title="<?= htmlspecialchars($ttl) ?>"><?= $g ?></span>
            <?php else: ?>
              <span class="badge bg-warning text-dark" title="<?= htmlspecialchars($ttl) ?>">Sin grupos</span>
            <?php endif; ?>
          </td>
          <td><?= isset($s['promedio']) && $s['promedio'] !== null ? number_format((float)$s['promedio'],2) : '—' ?></td>
          <td class="text-end">
            <a class="btn btn-sm btn-outline-primary me-1" href="<?= $base; ?>/subjects/detail?id=<?= (int)$s['id'] ?>" data-bs-toggle="tooltip" title="Ver detalles"><i class="fa-solid fa-eye"></i></a>
            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editSub<?= (int)$s['id'] ?>" data-bs-toggle="tooltip" title="Editar materia"><i class="fa-solid fa-pen"></i></button>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delSub<?= (int)$s['id'] ?>" data-bs-toggle="tooltip" title="Eliminar materia"><i class="fa-solid fa-trash"></i></button>

            <div class="modal fade" id="delSub<?= (int)$s['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Eliminar materia</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">¿Confirmas eliminar "<?= htmlspecialchars($s['nombre']) ?>"?</div>
                <div class="modal-footer">
                  <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                  <form method="post" action="/public/app.php?r=/subjects/delete">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                  </form>
                </div>
              </div></div>
            </div>

            <div class="modal fade" id="editSub<?= (int)$s['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Editar materia</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="post" action="/public/app.php?r=/subjects/update" class="needs-validation" novalidate>
                  <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int)$s['id'] ?>">
                    <div class="mb-2"><label class="form-label">Nombre</label><input class="form-control" name="nombre" value="<?= htmlspecialchars($s['nombre']) ?>" required><div class="invalid-feedback">Ingresa el nombre.</div></div>
                    <div class="mb-2"><label class="form-label">Clave</label><input class="form-control" name="clave" value="<?= htmlspecialchars($s['clave'] ?? '') ?>" required><div class="invalid-feedback">Ingresa la clave.</div></div>
                  </div>
                  <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-primary">Guardar</button></div>
                </form>
              </div></div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav aria-label="Paginas">
    <ul class="pagination justify-content-center mt-3">
      <?php 
        $pg = (int)($pagination['page'] ?? 1); 
        $pages = (int)($pagination['pages'] ?? 1); 
        $qv = urlencode((string)($pagination['q'] ?? '')); 
        $selCar = urlencode((string)($pagination['carrera'] ?? '')); 
        $selCiclo = urlencode((string)($pagination['ciclo'] ?? '')); 
        $selEstado = urlencode((string)($pagination['estado'] ?? '')); 
        $ppv = urlencode((string)($pagination['per_page'] ?? ''));
        for ($i = 1; $i <= $pages; $i++):
      ?>
        <li class="page-item <?= $i === $pg ? 'active' : '' ?>">
          <a class="page-link" href="<?php echo $base; ?>/subjects?page=<?= $i ?><?= $qv!==''?"&q=$qv":'' ?><?= $selCar!==''?"&carrera=$selCar":'' ?><?= $selCiclo!==''?"&ciclo=$selCiclo":'' ?><?= $selEstado!==''?"&estado=$selEstado":'' ?><?= $ppv!==''?"&per_page=$ppv":'' ?>">
            <?= $i ?>
          </a>
        </li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<script>
(() => {
  // Añade validación a todos los formularios con la clase needs-validation
  const needs = document.querySelectorAll('form.needs-validation');
  needs.forEach(f => {
    f.addEventListener('submit', (ev) => {
      if (!f.checkValidity()) {
        ev.preventDefault();
        ev.stopPropagation();
        f.classList.add('was-validated');
        return;
      }
      // evitar doble envío simple
      const btn = f.querySelector('button[type="submit"]');
      if (btn) { btn.disabled = true; }
    }, false);
    // resetear validación al cerrar modal (si está dentro de un modal)
    const modalEl = f.closest('.modal');
    if (modalEl) {
      modalEl.addEventListener('hidden.bs.modal', () => {
        f.classList.remove('was-validated');
      });
    }
  });

  // Confirmación para formularios de eliminación (dentro de modales)
  document.querySelectorAll('form[action="<?php echo $base; ?>/subjects/delete"]').forEach(df => {
    df.addEventListener('submit', (e) => {
      if (!confirm('¿Confirmas eliminar esta materia?')) { e.preventDefault(); }
    });
  });

  // Validación para los formularios de edición dentro de modales
  document.querySelectorAll('form[action="<?php echo $base; ?>/subjects/update"]').forEach(ef => {
    ef.addEventListener('submit', (e) => {
      if (!ef.checkValidity()) { e.preventDefault(); e.stopPropagation(); ef.classList.add('was-validated'); }
    });
  });

  // En el filtro superior, enviar al cambiar per_page o ciclo para mejorar UX
  const filterForm = document.querySelector('form[action="<?php echo $base; ?>/subjects"]');
  if (filterForm) {
    const perSel = filterForm.querySelector('select[name="per_page"]');
    if (perSel) { perSel.addEventListener('change', () => { try { localStorage.setItem('subjects_last_per_page', String(perSel.value||'')); } catch(e){} filterForm.submit(); }); }
    const cicloSel = filterForm.querySelector('select[name="ciclo"]');
    if (cicloSel) { cicloSel.addEventListener('change', () => filterForm.submit()); try { localStorage.setItem('subjects_last_ciclo', String(cicloSel.value||'')); } catch(e){} }
    const carSel = filterForm.querySelector('select[name="carrera"]'); if (carSel) { carSel.addEventListener('change', () => filterForm.submit()); }
    const estadoSel = filterForm.querySelector('select[name="estado"]'); if (estadoSel) { estadoSel.addEventListener('change', () => filterForm.submit()); }
  }

  // Restaurar último ciclo seleccionado en localStorage (si aplica)
  try {
    const last = localStorage.getItem('subjects_last_ciclo');
    if (last) {
      const fs = document.querySelector('form[action="<?php echo $base; ?>/subjects"] select[name="ciclo"]');
      if (fs && fs.value === '') { fs.value = last; }
    }
  } catch (e) {}

  try {
    const lastPer = localStorage.getItem('subjects_last_per_page');
    const hasPer = new URLSearchParams(location.search).has('per_page');
    if (lastPer && !hasPer) {
      const ps = document.querySelector('form[action="<?php echo $base; ?>/subjects"] select[name="per_page"]');
      if (ps && ps.value !== lastPer) {
        ps.value = lastPer;
        const f = document.querySelector('form[action="<?php echo $base; ?>/subjects"]');
        if (f) { f.submit(); }
      }
    }
  } catch (e) {}

  function showToast(message, type) {
    const container = document.getElementById('toastContainer');
    if (!container || !message) return;
    const bg = type === 'success' ? 'bg-success text-white' : type === 'warning' ? 'bg-warning text-dark' : type === 'danger' ? 'bg-danger text-white' : 'bg-primary text-white';
    const id = 't' + String(Date.now());
    const html = `<div id="${id}" class="toast align-items-center ${bg}" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`;
    container.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById(id);
    const t = new bootstrap.Toast(el, { delay: 2500 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => { el.remove(); });
  }
  <?php if ($flash): ?>
  showToast('<?= htmlspecialchars($flash) ?>', '<?= htmlspecialchars($flashType) ?>');
  <?php endif; ?>
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
