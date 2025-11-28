<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Evaluaciones Pendientes</h3>
    <div class="d-flex align-items-center gap-2">
      <form method="get" action="<?php echo $base; ?>/profesor/pendientes" class="d-flex align-items-center">
        <input type="text" name="ciclo" value="<?= htmlspecialchars($_GET['ciclo'] ?? '') ?>" class="form-control form-control-sm" placeholder="Ciclo" style="max-width: 160px" data-bs-toggle="tooltip" title="Filtrar por ciclo escolar">
        <button class="btn btn-sm btn-primary ms-2" type="submit" data-bs-toggle="tooltip" title="Aplicar filtro">Filtrar</button>
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
              <th>Ciclo</th>
              <th>Materia</th>
              <th>Grupo</th>
              <th>Matrícula</th>
              <th>Alumno</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($rows)): foreach ($rows as $r): ?>
              <tr>
                <td><?= htmlspecialchars($r['ciclo']) ?></td>
                <td><?= htmlspecialchars($r['materia']) ?></td>
                <td><?= htmlspecialchars($r['grupo']) ?></td>
                <td><?= htmlspecialchars($r['matricula']) ?></td>
                <td><?= htmlspecialchars($r['alumno']) ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="5" class="text-muted">No hay evaluaciones pendientes.</td></tr>
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
