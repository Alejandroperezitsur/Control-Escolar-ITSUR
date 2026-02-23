<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Kárdex académico</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-sm btn-outline-secondary">Volver</a>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card text-bg-primary">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <i class="fa-solid fa-chart-line fa-2x me-3"></i>
            <div>
              <div class="small">Promedio general</div>
              <div class="h4 mb-0">
                <?php echo number_format((float)($kardexPromedio ?? 0), 2); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card text-bg-success">
        <div class="card-body">
          <div class="d-flex align-items-center">
            <i class="fa-solid fa-graduation-cap fa-2x me-3"></i>
            <div>
              <div class="small">Créditos acumulados</div>
              <div class="h4 mb-0">
                <?php echo (int)($kardexCreditos ?? 0); ?>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <div class="card">
    <div class="card-body">
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm align-middle">
          <thead>
            <tr>
              <th>Ciclo</th>
              <th>Materia</th>
              <th>Grupo</th>
              <th class="text-end">Créditos</th>
              <th class="text-end">Final</th>
              <th class="text-center">Estado</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!empty($kardexRows)): ?>
              <?php foreach ($kardexRows as $r): ?>
                <?php
                  $final = $r['final'] !== null && $r['final'] !== '' ? (float)$r['final'] : null;
                  $estado = (string)($r['estado'] ?? '');
                  $clsEstado = 'badge bg-secondary';
                  if ($estado === 'Aprobada') { $clsEstado = 'badge bg-success'; }
                  elseif ($estado === 'Reprobada') { $clsEstado = 'badge bg-danger'; }
                  elseif ($estado === 'En curso') { $clsEstado = 'badge bg-warning text-dark'; }
                ?>
                <tr>
                  <td><?php echo htmlspecialchars((string)$r['ciclo']); ?></td>
                  <td>
                    <?php echo htmlspecialchars((string)$r['materia']); ?>
                    <?php if (!empty($r['materia_clave'])): ?>
                      <div class="small text-muted text-uppercase"><?php echo htmlspecialchars((string)$r['materia_clave']); ?></div>
                    <?php endif; ?>
                  </td>
                  <td><?php echo htmlspecialchars((string)$r['grupo']); ?></td>
                  <td class="text-end"><?php echo (int)($r['creditos'] ?? 0); ?></td>
                  <td class="text-end">
                    <?php echo $final !== null ? htmlspecialchars((string)$r['final']) : '—'; ?>
                  </td>
                  <td class="text-center">
                    <span class="<?php echo $clsEstado; ?>"><?php echo htmlspecialchars($estado); ?></span>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-muted">No hay materias cursadas registradas.</td>
              </tr>
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

