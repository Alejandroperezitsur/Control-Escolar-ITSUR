<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
 $csrf = $_SESSION['csrf_token'] ?? '';
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Detalle de Materia</h3>
      <div class="text-muted small">Información y grupos activos</div>
    </div>
    <a href="<?php echo $base; ?>/reports" class="btn btn-sm btn-outline-secondary">Reportes</a>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3">
        <div class="col-md-3"><div class="small text-muted">Nombre</div><div class="h5 mb-0"><?php echo htmlspecialchars($materia['nombre'] ?? ''); ?></div></div>
        <div class="col-md-3"><div class="small text-muted">Clave</div><div><?php echo htmlspecialchars($materia['clave'] ?? '—'); ?></div></div>
        <div class="col-md-3"><div class="small text-muted">Créditos</div><div><?php echo isset($materia['creditos']) ? (int)$materia['creditos'] : '—'; ?></div></div>
        <div class="col-md-3">
          <div class="small text-muted">Ciclo</div>
          <select class="form-select form-select-sm" id="flt-ciclo" onchange="changeCycle(this.value)">
            <option value="">Todos</option>
            <?php foreach (($ciclos ?? []) as $c): ?>
              <option value="<?php echo htmlspecialchars($c); ?>" <?php echo (isset($_GET['ciclo']) && $_GET['ciclo'] === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-3">
          <div class="small text-muted">Carrera</div>
          <select class="form-select form-select-sm" id="flt-carrera" onchange="changeCareer(this.value)">
            <option value="">Todas</option>
            <?php $carSel = isset($_GET['carrera']) ? (string)$_GET['carrera'] : ''; foreach (($data_carreras ?? []) as $car): $cl = (string)($car['clave'] ?? ''); ?>
              <option value="<?php echo htmlspecialchars($cl); ?>" <?php echo ($carSel !== '' && $carSel === $cl) ? 'selected' : ''; ?>><?php echo htmlspecialchars($car['nombre'] ?? $cl); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="h6 mb-2">Grupos de la materia</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Ciclo</th><th>Grupo</th><th>Profesor</th><th class="text-end">Alumnos</th><th class="text-end">Promedio</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <?php
              $fCiclo = isset($_GET['ciclo']) ? trim((string)$_GET['ciclo']) : '';
              $rows = $grupos ?? [];
              if ($fCiclo !== '') { $rows = array_values(array_filter($rows, fn($r) => (string)($r['ciclo'] ?? '') === $fCiclo)); }
            ?>
            <?php if (!empty($rows)): foreach ($rows as $g): ?>
              <tr>
                <td><?php echo htmlspecialchars($g['ciclo'] ?? ''); ?></td>
                <td><a href="<?php echo $base; ?>/grades/group?grupo_id=<?php echo (int)$g['id']; ?>" class="text-decoration-none"><?php echo htmlspecialchars($g['grupo'] ?? ''); ?></a></td>
                <td><a href="<?php echo $base; ?>/professors/detail?id=<?php echo (int)($g['profesor_id'] ?? 0); ?>" class="text-decoration-none"><?php echo htmlspecialchars($g['profesor'] ?? ''); ?></a></td>
                <td class="text-end"><?php echo isset($g['alumnos']) ? (int)$g['alumnos'] : '—'; ?></td>
                <td class="text-end"><?php echo isset($g['promedio']) ? (float)$g['promedio'] : '—'; ?></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/grades/group?grupo_id=<?php echo (int)$g['id']; ?>">Ver</a></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="6" class="text-muted">Sin grupos para esta materia.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card mt-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="h6 mb-0">Planes de estudio (asociaciones)</div>
        <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
          <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#assoc-form">Agregar a carrera</button>
        <?php endif; ?>
      </div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th>Carrera</th><th>Clave</th><th>Semestre</th><th>Créditos</th><th>Tipo</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <?php if (!empty($assocs)): foreach ($assocs as $a): ?>
              <tr>
                <td><?= htmlspecialchars($a['carrera_nombre'] ?? $a['clave']) ?></td>
                <td><?= htmlspecialchars($a['clave'] ?? '') ?></td>
                <td><?= (int)($a['semestre'] ?? 0) ?></td>
                <td><?= (int)($a['creditos'] ?? 0) ?></td>
                <td><?= htmlspecialchars($a['tipo'] ?? '') ?></td>
                <td class="text-end">
                  <?php if (($_SESSION['role'] ?? '') === 'admin'): ?>
                  <form method="post" action="<?= $base ?>/subjects/remove_carrera" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                    <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                    <input type="hidden" name="materia_id" value="<?= (int)$materia['id'] ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">Quitar</button>
                  </form>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="6" class="text-muted">No hay asociaciones registradas para esta materia.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <div class="collapse mt-3" id="assoc-form">
        <form method="post" action="<?= $base ?>/subjects/add_carrera" class="row g-2 needs-validation" novalidate>
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="materia_id" value="<?= (int)$materia['id'] ?>">
          <div class="col-md-4">
            <select name="carrera_id" class="form-select" required>
              <option value="">Selecciona carrera...</option>
              <?php foreach (($data_carreras ?? []) as $c): ?>
                <option value="<?= (int)$c['id'] ?>"><?= htmlspecialchars($c['nombre'] ?? $c['clave']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Selecciona una carrera.</div>
          </div>
          <div class="col-md-2"><input type="number" name="semestre" class="form-control" min="1" max="12" placeholder="Semestre" required></div>
          <div class="col-md-2"><input type="number" name="creditos" class="form-control" min="1" max="12" placeholder="Créditos"></div>
          <div class="col-md-2">
            <select name="tipo" class="form-select">
              <option value="basica">Básica</option>
              <option value="especialidad">Especialidad</option>
              <option value="residencia">Residencia</option>
            </select>
          </div>
          <div class="col-md-2"><button class="btn btn-primary w-100" type="submit">Agregar</button></div>
        </form>
      </div>
    </div>
  </div>
</div>
<script>
function changeCycle(c){
  const params = new URLSearchParams(window.location.search);
  if (c) params.set('ciclo', c); else params.delete('ciclo');
  window.location.search = params.toString();
}
function changeCareer(c){
  const params = new URLSearchParams(window.location.search);
  if (c) params.set('carrera', c); else params.delete('carrera');
  window.location.search = params.toString();
}
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
