<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Mis Alumnos</h3>
    <div class="d-flex align-items-center gap-2">
      <form method="get" action="<?php echo $base; ?>/profesor/alumnos" class="d-flex align-items-center gap-2">
        <select name="ciclo" class="form-select form-select-sm" style="max-width: 180px" data-bs-toggle="tooltip" title="Filtrar por ciclo">
          <option value="">Todos los ciclos</option>
          <?php foreach (($ciclos ?? []) as $c): ?>
            <option value="<?= htmlspecialchars($c) ?>" <?= (($c === ($_GET['ciclo'] ?? '')) ? 'selected' : '') ?>><?= htmlspecialchars($c) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="grupo_id" class="form-select form-select-sm" style="max-width: 260px" data-bs-toggle="tooltip" title="Filtrar por grupo">
          <option value="0">Todos los grupos</option>
          <?php foreach (($grupos ?? []) as $g): ?>
            <option value="<?= (int)$g['id'] ?>" <?= (((int)($g['id'])) === (int)($_GET['grupo_id'] ?? 0) ? 'selected' : '') ?>><?= htmlspecialchars($g['ciclo'].' · '.$g['materia'].' · '.$g['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
        <button class="btn btn-sm btn-primary" type="submit" data-bs-toggle="tooltip" title="Aplicar filtros">Filtrar</button>
      </form>
      <a href="<?php echo $base; ?>/dashboard" class="btn btn-sm btn-outline-secondary">Volver</a>
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
          <thead>
            <tr>
              <th>Matrícula</th>
              <th>Alumno</th>
              <th>Materia</th>
              <th>Grupo</th>
              <th>Ciclo</th>
              <th class="text-end">Parcial 1</th>
              <th class="text-end">Parcial 2</th>
              <th class="text-end">Final</th>
              <th class="text-end">Promedio</th>
              <th class="text-end">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($rows)): foreach ($rows as $r): ?>
              <?php $f = $r['final']; $estado = ($f === null || $f === '' ? 'Pendiente' : ((float)$f >= 70 ? 'Aprobada' : 'Reprobada')); ?>
              <tr>
                <td><?= htmlspecialchars($r['matricula']) ?></td>
                <td><?= htmlspecialchars(($r['nombre'] ?? '').' '.($r['apellido'] ?? '')) ?></td>
                <td><?= htmlspecialchars($r['materia']) ?></td>
                <td><?= htmlspecialchars($r['grupo']) ?></td>
                <td><?= htmlspecialchars($r['ciclo']) ?></td>
                <td class="text-end"><?= ($r['parcial1'] !== null && $r['parcial1'] !== '' ? (int)$r['parcial1'] : '') ?></td>
                <td class="text-end"><?= ($r['parcial2'] !== null && $r['parcial2'] !== '' ? (int)$r['parcial2'] : '') ?></td>
                <td class="text-end"><?= ($r['final'] !== null && $r['final'] !== '' ? (int)$r['final'] : '') ?></td>
                <td class="text-end"><?= number_format((float)($r['promedio'] ?? 0), 2) ?></td>
                <td class="text-end <?= ($estado === 'Aprobada' ? 'text-success' : ($estado === 'Reprobada' ? 'text-danger' : 'text-muted')) ?>"><?= $estado ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="10" class="text-muted">No hay registros de calificaciones para tus filtros.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
