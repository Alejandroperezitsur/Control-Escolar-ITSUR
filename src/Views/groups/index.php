<?php
$csrf = $_SESSION['csrf_token'] ?? '';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Grupos</h3>
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="post" action="<?php echo $base; ?>/groups/create" class="row g-2 needs-validation" novalidate id="groupForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-md-3">
          <select class="form-select" name="materia_id" id="materiaSelect" required>
            <option value="">Seleccione materia...</option>
          </select>
          <div class="invalid-feedback">Selecciona una materia.</div>
        </div>
        <div class="col-md-3">
          <select class="form-select" name="profesor_id" id="profesorSelect" required>
            <option value="">Seleccione profesor...</option>
          </select>
          <div class="invalid-feedback">Selecciona un profesor.</div>
        </div>
        <div class="col-md-2">
          <input class="form-control" name="nombre" placeholder="Grupo" required>
          <div class="invalid-feedback">Ingresa el nombre del grupo.</div>
        </div>
        <div class="col-md-2">
          <input class="form-control" name="ciclo" placeholder="Ciclo" required>
          <div class="invalid-feedback">Ingresa el ciclo.</div>
        </div>
        <div class="col-md-2">
          <input class="form-control" type="number" min="1" max="100" name="cupo" placeholder="Cupo" value="30" required>
          <div class="invalid-feedback">Ingresa cupo (1-100).</div>
        </div>
        <div class="col-12"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-1"></i> Crear grupo</button></div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr><th>ID</th><th>Materia</th><th>Profesor</th><th>Grupo</th><th>Ciclo</th><th>Cupo</th><th class="text-end">Acciones</th></tr></thead>
      <tbody>
        <?php foreach (($groups ?? []) as $g): ?>
        <tr>
          <td><?= htmlspecialchars($g['id']) ?></td>
          <td><a href="<?= $base; ?>/subjects/detail?id=<?= (int)($g['materia_id'] ?? 0) ?>" class="text-decoration-none"><?= htmlspecialchars($g['materia'] ?? '') ?></a></td>
          <td><a href="<?= $base; ?>/professors/detail?id=<?= (int)($g['profesor_id'] ?? 0) ?>" class="text-decoration-none"><?= htmlspecialchars($g['profesor'] ?? '') ?></a></td>
          <td><a href="<?= $base; ?>/grades/group?grupo_id=<?= (int)$g['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($g['nombre']) ?></a></td>
          <td><?= htmlspecialchars($g['ciclo']) ?></td>
          <td><?= htmlspecialchars($g['cupo']) ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delGroup<?= (int)$g['id'] ?>"><i class="fa-regular fa-trash-can"></i></button>
            <a class="btn btn-sm btn-outline-primary" href="<?php echo $base; ?>/grades/group?grupo_id=<?= (int)$g['id'] ?>"><i class="fa-solid fa-table"></i></a>
            <div class="modal fade" id="delGroup<?= (int)$g['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Eliminar grupo</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">¿Confirmas eliminar el grupo "<?= htmlspecialchars($g['nombre']) ?>"?</div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="<?php echo $base; ?>/groups/delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                      <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
// Bootstrap validation
(() => {
  const form = document.getElementById('groupForm');
  form.addEventListener('submit', (event) => {
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    form.classList.add('was-validated');
  }, false);
})();

// Dynamic catalogs for selects
async function loadCatalogs() {
  const subjSel = document.getElementById('materiaSelect');
  const profSel = document.getElementById('profesorSelect');
  try {
    const [subjRes, profRes] = await Promise.all([
      fetch('<?php echo $base; ?>/api/catalogs/subjects'),
      fetch('<?php echo $base; ?>/api/catalogs/professors')
    ]);
    if (!subjRes.ok || !profRes.ok) throw new Error('Error cargando catálogos');
    const subjects = await subjRes.json();
    const professors = await profRes.json();
    subjects.forEach(s => {
      const opt = document.createElement('option');
      opt.value = s.id;
      opt.textContent = s.nombre + (s.clave ? ' ('+s.clave+')' : '');
      subjSel.appendChild(opt);
    });
    professors.forEach(p => {
      const opt = document.createElement('option');
      opt.value = p.id;
      opt.textContent = p.nombre + (p.email ? ' ('+p.email+')' : '');
      profSel.appendChild(opt);
    });
  } catch (e) {
    const warn = document.createElement('div');
    warn.className = 'alert alert-warning mt-2';
    warn.textContent = 'No se pudieron cargar los catálogos. Intenta más tarde.';
    document.querySelector('.card .card-body').appendChild(warn);
    document.querySelector('#groupForm button[type="submit"]').disabled = true;
  }
}
document.addEventListener('DOMContentLoaded', loadCatalogs);
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
