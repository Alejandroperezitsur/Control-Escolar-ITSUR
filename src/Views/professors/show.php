<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <h3 class="mb-0">Perfil del Profesor</h3>
      <div class="text-muted small">Información y grupos asignados</div>
    </div>
    <div class="d-flex gap-2">
      <a href="<?php echo $base; ?>/professors" class="btn btn-sm btn-outline-secondary">Volver</a>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-3 align-items-center">
        <div class="col-auto">
          <div class="avatar-initials bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width:56px;height:56px;font-size:1.2rem;">
            <?php echo strtoupper(substr((string)$profesor['nombre'],0,1)); ?>
          </div>
        </div>
        <div class="col">
          <div class="h5 mb-0"><?php echo htmlspecialchars($profesor['nombre'] ?? ''); ?></div>
          <div class="text-muted small">Email: <?php echo htmlspecialchars($profesor['email'] ?? ''); ?></div>
          <div class="text-muted small">Matrícula: <?php echo htmlspecialchars($profesor['matricula'] ?? '—'); ?></div>
        </div>
        <div class="col-auto">
          <span class="badge <?php echo ((int)($profesor['activo'] ?? 0) === 1) ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> rounded-pill"><?php echo ((int)($profesor['activo'] ?? 0) === 1) ? 'Activo' : 'Inactivo'; ?></span>
        </div>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="h6 mb-2">Grupos Asignados</div>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Alumnos</th><th class="text-end">Promedio</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <?php if (!empty($grupos)): foreach ($grupos as $g): ?>
              <tr>
                <td><?php echo htmlspecialchars($g['ciclo'] ?? ''); ?></td>
                <td><a class="text-decoration-none" href="<?php echo $base; ?>/grades/group?grupo_id=<?php echo (int)$g['id']; ?>"><?php echo htmlspecialchars($g['materia'] ?? ''); ?></a></td>
                <td><a class="text-decoration-none" href="<?php echo $base; ?>/grades/group?grupo_id=<?php echo (int)$g['id']; ?>"><?php echo htmlspecialchars($g['grupo'] ?? ''); ?></a></td>
                <td class="text-end"><?php echo isset($g['alumnos']) ? (int)$g['alumnos'] : '—'; ?></td>
                <td class="text-end"><?php echo isset($g['promedio']) ? (float)$g['promedio'] : '—'; ?></td>
                <td class="text-end"><a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/grades/group?grupo_id=<?php echo (int)$g['id']; ?>">Ver</a></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="6" class="text-muted">No tiene grupos asignados.</td></tr>
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
