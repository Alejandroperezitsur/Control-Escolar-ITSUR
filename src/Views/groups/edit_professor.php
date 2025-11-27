<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$csrf = $_SESSION['csrf_token'] ?? '';
$profesores = $profesores ?? [];
$grupo = $grupo ?? [];
?>
<div class="modal fade" id="editProfModal" tabindex="-1" aria-labelledby="editProfModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="post" action="/public/app.php?r=/groups/update_professor" class="needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title" id="editProfModalLabel">Asignar/Cambiar Profesor</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
          <input type="hidden" name="grupo_id" value="<?= (int)($grupo['id'] ?? 0) ?>">
          <div class="mb-3">
            <label for="profesor_id" class="form-label">Profesor</label>
            <select class="form-select" name="profesor_id" id="profesor_id" required>
              <option value="">Selecciona un profesor...</option>
              <?php foreach ($profesores as $prof): ?>
                <option value="<?= (int)$prof['id'] ?>" <?= isset($grupo['profesor_id']) && $grupo['profesor_id'] == $prof['id'] ? 'selected' : '' ?>><?= htmlspecialchars($prof['nombre']) ?></option>
              <?php endforeach; ?>
            </select>
            <div class="invalid-feedback">Selecciona un profesor.</div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>
