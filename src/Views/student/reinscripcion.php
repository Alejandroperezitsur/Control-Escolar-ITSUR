<?php $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); $csrf = $_SESSION['csrf_token'] ?? ''; ob_start(); ?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Reinscripción</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>
  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="h6 mb-0">Mi carga actual</div>
        <form method="get" class="d-flex align-items-center">
          <input type="text" name="ciclo" value="<?php echo htmlspecialchars($_GET['ciclo'] ?? ''); ?>" class="form-control form-control-sm" placeholder="Filtrar por ciclo" style="max-width: 200px" data-bs-toggle="tooltip" title="Filtrar por ciclo">
          <input type="text" name="career" value="<?php echo htmlspecialchars($_GET['career'] ?? ''); ?>" class="form-control form-control-sm ms-2" placeholder="Carrera (p.ej. ISC)" style="max-width: 160px" data-bs-toggle="tooltip" title="Filtrar por carrera">
          <button class="btn btn-sm btn-outline-primary ms-2" type="submit" data-bs-toggle="tooltip" title="Aplicar filtros">Aplicar</button>
        </form>
      </div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Estado</th><th class="text-end">Calificación</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
          <?php $rows = is_array($load) ? $load : []; if (!empty($rows)): foreach ($rows as $x): ?>
            <tr>
              <td><?php echo htmlspecialchars($x['ciclo'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($x['materia'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($x['grupo'] ?? ''); ?></td>
              <td class="text-end"><?php echo htmlspecialchars($x['estado'] ?? ''); ?></td>
              <td class="text-end"><?php echo htmlspecialchars($x['calificacion'] ?? ''); ?></td>
              <td class="text-end">
                <?php if (($x['estado'] ?? '') === 'Pendiente'): ?>
                  <form method="post" action="<?php echo $base; ?>/alumno/unenroll" onsubmit="return confirm('¿Desinscribirte de este grupo?');" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                    <input type="hidden" name="grupo_id" value="<?php echo htmlspecialchars($x['grupo_id'] ?? ''); ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger" data-bs-toggle="tooltip" title="Darse de baja de este grupo">Desinscribir</button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="5" class="text-muted">Sin registros.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="h6 mb-0">Oferta disponible</div>
        <span class="text-muted small">Filtrada por ciclo y carrera</span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm">
          <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th>Ocupados</th><th>Cupo</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
          <?php $rowsO = is_array($offer ?? null) ? $offer : []; if (!empty($rowsO)): foreach ($rowsO as $r): ?>
            <tr>
              <td><?php echo htmlspecialchars($r['ciclo'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['materia'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($r['grupo'] ?? ''); ?></td>
              <td><?php echo (int)($r['ocupados'] ?? 0); ?></td>
              <td><?php echo (int)($r['cupo'] ?? 30); ?></td>
              <td class="text-end">
                <form method="post" action="<?php echo $base; ?>/alumno/enroll" class="d-inline">
                  <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
                  <input type="hidden" name="grupo_id" value="<?php echo (int)($r['id'] ?? 0); ?>">
                  <button type="submit" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Inscribirse en este grupo">Inscribir</button>
                </form>
              </td>
            </tr>
          <?php endforeach; else: ?>
            <tr><td colspan="6" class="text-muted">Sin oferta disponible.</td></tr>
          <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php'; ?>
