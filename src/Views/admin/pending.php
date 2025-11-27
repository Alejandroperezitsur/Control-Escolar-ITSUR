<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Pendientes de Evaluación</h3>
    <div class="d-flex align-items-center gap-2">
      <form method="get" action="<?php echo $base; ?>/admin/pendientes" class="d-flex align-items-center">
        <select name="ciclo" class="form-select form-select-sm" style="max-width: 160px" onchange="this.form.submit()">
            <option value="">Todos los ciclos</option>
            <?php if (!empty($cycles)): foreach ($cycles as $c): ?>
                <option value="<?= htmlspecialchars($c) ?>" <?= (isset($_GET['ciclo']) && $_GET['ciclo'] === $c) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($c) ?>
                </option>
            <?php endforeach; endif; ?>
        </select>
        <noscript><button class="btn btn-sm btn-primary ms-2" type="submit">Filtrar</button></noscript>
      </form>
      <a href="<?php echo $base; ?>/dashboard" class="btn btn-sm btn-outline-secondary">Volver</a>
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
              <th>Matrícula</th>
              <th>Alumno</th>
              <th>Profesor</th>
              <th class="text-end">Acciones</th>
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
                <td><?= htmlspecialchars($r['profesor']) ?></td>
                <td class="text-end">
                    <button type="button" 
                            class="btn btn-sm btn-primary"
                            data-bs-toggle="modal" 
                            data-bs-target="#evalModal"
                            data-alumno-id="<?= $r['alumno_id'] ?>"
                            data-grupo-id="<?= $r['grupo_id'] ?>"
                            data-alumno-name="<?= htmlspecialchars($r['alumno']) ?>"
                            data-materia-name="<?= htmlspecialchars($r['materia']) ?>"
                            onclick="setupEvalModal(this)">
                        Evaluar
                    </button>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="7" class="text-muted text-center py-3">No hay pendientes de evaluación.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Modal de Evaluación -->
<div class="modal fade" id="evalModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="<?php echo $base; ?>/grades/create">
        <div class="modal-header">
          <h5 class="modal-title">Evaluar Alumno</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
          <input type="hidden" name="redirect_to" value="/admin/pendientes">
          <input type="hidden" name="alumno_id" id="modalAlumnoId">
          <input type="hidden" name="grupo_id" id="modalGrupoId">
          
          <div class="mb-3">
            <label class="form-label fw-bold">Alumno:</label>
            <span id="modalAlumnoName"></span>
          </div>
          <div class="mb-3">
            <label class="form-label fw-bold">Materia:</label>
            <span id="modalMateriaName"></span>
          </div>
          
          <div class="row g-3">
            <div class="col-4">
              <label for="parcial1" class="form-label">Parcial 1</label>
              <input type="number" class="form-control" name="parcial1" id="parcial1" min="0" max="100">
            </div>
            <div class="col-4">
              <label for="parcial2" class="form-label">Parcial 2</label>
              <input type="number" class="form-control" name="parcial2" id="parcial2" min="0" max="100">
            </div>
            <div class="col-4">
              <label for="final" class="form-label">Final</label>
              <input type="number" class="form-control" name="final" id="final" min="0" max="100">
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar Calificación</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
function setupEvalModal(btn) {
    document.getElementById('modalAlumnoId').value = btn.dataset.alumnoId;
    document.getElementById('modalGrupoId').value = btn.dataset.grupoId;
    document.getElementById('modalAlumnoName').textContent = btn.dataset.alumnoName;
    document.getElementById('modalMateriaName').textContent = btn.dataset.materiaName;
    
    // Limpiar inputs
    document.getElementById('parcial1').value = '';
    document.getElementById('parcial2').value = '';
    document.getElementById('final').value = '';
}

(function(){
  var ids=['parcial1','parcial2','final'];
  function paint(el){ var v = el.value === '' ? null : Number(el.value); el.classList.remove('is-valid','is-invalid','text-success','text-danger'); if (v === null || Number.isNaN(v)) return; if (v >= 70) { el.classList.add('is-valid','text-success'); } else { el.classList.add('is-invalid','text-danger'); } }
  function clamp(el){ var v = el.value; if (v === '') return; var n = Math.round(Number(v)); if (Number.isNaN(n)) { el.value = ''; } else { if (n < 0) n = 0; if (n > 100) n = 100; el.value = String(n); } paint(el); }
  ids.forEach(function(id){ var el = document.getElementById(id); if (!el) return; el.addEventListener('input', function(){ paint(el); }); el.addEventListener('blur', function(){ clamp(el); }); });
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
