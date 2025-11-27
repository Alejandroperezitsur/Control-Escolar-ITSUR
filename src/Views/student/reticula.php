<?php $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); ob_start(); ?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Retícula Académica</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="row g-3">
        <?php $i=0; foreach (($cycles ?? []) as $c): $list = $map[$c] ?? []; $i++; ?>
        <div class="col-12 col-md-6 col-lg-4">
          <div class="card h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <div class="h6 mb-0">Ciclo</div>
                <span class="badge bg-light text-dark"><?php echo htmlspecialchars($c); ?></span>
              </div>
              <div class="table-responsive">
                <table class="table table-sm">
                  <thead><tr><th>Materia</th><th>Grupo</th></tr></thead>
                  <tbody>
                  <?php if (!empty($list)): foreach ($list as $r): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['materia'] ?? ''); ?></td>
                      <td><?php echo htmlspecialchars($r['grupo'] ?? ''); ?></td>
                    </tr>
                  <?php endforeach; else: ?>
                    <tr><td colspan="2" class="text-muted">Sin registros</td></tr>
                  <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; if (empty($cycles)): ?>
        <div class="col-12"><div class="alert alert-info">No hay datos disponibles.</div></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>
<?php $content = ob_get_clean(); include __DIR__ . '/../layout.php'; ?>
