<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Mis Grupos</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-sm btn-outline-secondary">Volver</a>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <?php if (!empty($rows)): foreach ($rows as $x): ?>
            <tr>
              <td><?= htmlspecialchars($x['ciclo'] ?? '') ?></td>
              <td><?= htmlspecialchars($x['materia'] ?? '') ?></td>
              <td><?= htmlspecialchars($x['nombre'] ?? '') ?></td>
              <td class="text-end"><a class="btn btn-outline-success btn-sm" href="<?php echo $base; ?>/grades" data-bs-toggle="tooltip" title="Ir a calificar este grupo"><i class="fa-solid fa-pen"></i> Calificar</a></td>
            </tr>
            <?php endforeach; else: ?>
            <tr><td colspan="4" class="text-muted">No tienes grupos asignados.</td></tr>
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
