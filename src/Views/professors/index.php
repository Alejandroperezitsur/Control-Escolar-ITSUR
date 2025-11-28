<?php
$csrf = $_SESSION['csrf_token'] ?? '';
// Expect $professors
?>
<?php $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/'); ob_start(); ?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Profesores</h3>
    <div class="d-flex align-items-center gap-2">
      <form method="get" action="<?php echo $base; ?>/professors" class="d-flex align-items-center">
        <input type="text" name="q" value="<?= htmlspecialchars((string)($pagination['q'] ?? '')) ?>" class="form-control" placeholder="Buscar por nombre/email" style="max-width:260px" data-bs-toggle="tooltip" title="Buscar por nombre o email">
        <select name="status" class="form-select ms-2" style="max-width:180px" data-bs-toggle="tooltip" title="Filtrar por estado">
          <?php $status = (string)($pagination['status'] ?? ''); ?>
          <option value="" <?= $status===''?'selected':'' ?>>Todos</option>
          <option value="active" <?= $status==='active'?'selected':'' ?>>Activos</option>
          <option value="inactive" <?= $status==='inactive'?'selected':'' ?>>Inactivos</option>
        </select>
        <select name="career" class="form-select ms-2" style="max-width:180px" data-bs-toggle="tooltip" title="Filtrar por carrera">
            <option value="0">Todas las carreras</option>
            <?php foreach (($careers ?? []) as $c): ?>
                <option value="<?= $c['id'] ?>" <?= ((int)($_GET['career'] ?? 0) === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
            <?php endforeach; ?>
        </select>
        <button class="btn btn-outline-primary ms-2" type="submit" data-bs-toggle="tooltip" title="Buscar"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
      <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
    </div>
  </div>
  <?php $flash = $_SESSION['flash'] ?? null; $flashType = $_SESSION['flash_type'] ?? 'info'; unset($_SESSION['flash'], $_SESSION['flash_type']); ?>
  <div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index:1100"></div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="post" action="/public/app.php?r=/professors/create" class="row g-2 needs-validation" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-md-4">
          <input class="form-control" name="nombre" placeholder="Nombre" required>
          <div class="invalid-feedback">Ingresa el nombre del profesor.</div>
        </div>
        <div class="col-md-4">
          <input class="form-control" type="email" name="email" placeholder="Email" required>
          <div class="invalid-feedback">Ingresa un correo válido.</div>
        </div>
        <div class="col-md-4">
            <select class="form-select" name="carrera_id">
                <option value="">Carrera (Opcional)</option>
                <?php foreach (($careers ?? []) as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-12 text-end">
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-user-plus me-1"></i> Agregar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr>
        <?php $sort = (string)($pagination['sort'] ?? 'nombre'); $order = (string)($pagination['order'] ?? 'ASC'); $pg = (int)($pagination['page'] ?? 1); $qv = urlencode((string)($pagination['q'] ?? '')); $sv = urlencode((string)($pagination['status'] ?? '')); $toggle = $order==='ASC'?'DESC':'ASC'; ?>
        <th><a href="<?php echo $base; ?>/professors?page=<?= $pg ?>&sort=id&order=<?= $sort==='id'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?><?= $sv!==''?"&status=$sv":'' ?>">ID<?= $sort==='id' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th><a href="<?php echo $base; ?>/professors?page=<?= $pg ?>&sort=nombre&order=<?= $sort==='nombre'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?><?= $sv!==''?"&status=$sv":'' ?>">Nombre<?= $sort==='nombre' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th><a href="<?php echo $base; ?>/professors?page=<?= $pg ?>&sort=email&order=<?= $sort==='email'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?><?= $sv!==''?"&status=$sv":'' ?>">Email<?= $sort==='email' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th>Carrera</th>
        <th><a href="<?php echo $base; ?>/professors?page=<?= $pg ?>&sort=activo&order=<?= $sort==='activo'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?><?= $sv!==''?"&status=$sv":'' ?>">Activo<?= $sort==='activo' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th class="text-end">Acciones</th>
      </tr></thead>
      <tbody>
        <?php foreach ($professors as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['id']) ?></td>
          <td><a href="<?= $base; ?>/professors/detail?id=<?= (int)$p['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($p['nombre']) ?></a></td>
          <td><a href="<?= $base; ?>/professors/detail?id=<?= (int)$p['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($p['email']) ?></a></td>
          <td class="small text-muted"><?= htmlspecialchars($p['carrera_nombre'] ?? '—') ?></td>
          <td><?= (int)$p['activo'] === 1 ? 'Sí' : 'No' ?></td>
          <td class="text-end">
            <button class="btn btn-outline-primary btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editProf<?= (int)$p['id'] ?>" title="Editar profesor" data-bs-toggle="tooltip">
              <i class="fa-solid fa-pen"></i>
            </button>
            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#delModal<?= (int)$p['id'] ?>" title="Eliminar profesor" data-bs-toggle="tooltip">
              <i class="fa-solid fa-trash"></i>
            </button>
            <div class="modal fade" id="delModal<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Eliminar profesor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">¿Confirmas eliminar a <?= htmlspecialchars($p['nombre']) ?>?</div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="/public/app.php?r=/professors/delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                      <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal fade" id="editProf<?= (int)$p['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Editar profesor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="post" action="/public/app.php?r=/professors/update" class="needs-validation" novalidate>
                    <div class="modal-body">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                      <div class="mb-2">
                        <label class="form-label">Nombre</label>
                        <input class="form-control" name="nombre" value="<?= htmlspecialchars($p['nombre']) ?>" required>
                        <div class="invalid-feedback">Ingresa el nombre.</div>
                      </div>
                      <div class="mb-2">
                        <label class="form-label">Email</label>
                        <input class="form-control" type="email" name="email" value="<?= htmlspecialchars($p['email']) ?>" required>
                        <div class="invalid-feedback">Ingresa un correo válido.</div>
                      </div>
                      <div class="mb-2">
                        <label class="form-label">Carrera</label>
                        <select class="form-select" name="carrera_id">
                            <option value="">Sin asignar</option>
                            <?php foreach (($careers ?? []) as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= ((int)($p['carrera_id'] ?? 0) === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                      </div>
                      <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" name="activo" id="chkActivo<?= (int)$p['id'] ?>" <?= ((int)$p['activo'] === 1) ? 'checked' : '' ?>>
                        <label class="form-check-label" for="chkActivo<?= (int)$p['id'] ?>">Activo</label>
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
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <nav aria-label="Paginas">
    <ul class="pagination justify-content-center mt-3">
      <?php $pg = (int)($pagination['page'] ?? 1); $pages = (int)($pagination['pages'] ?? 1); $qv = urlencode((string)($pagination['q'] ?? '')); $sv = urlencode((string)($pagination['status'] ?? '')); ?>
      <?php for ($i=1; $i<=$pages; $i++): ?>
        <li class="page-item <?= $i === $pg ? 'active' : '' ?>"><a class="page-link" href="<?php echo $base; ?>/professors?page=<?= $i ?><?= $qv!==''?"&q=$qv":'' ?><?= $sv!==''?"&status=$sv":'' ?>"><?= $i ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<script>
// Bootstrap client-side validation
(function() {
  const form = document.querySelector('form[action="/public/app.php?r=/professors/create"]');
  form.addEventListener('submit', (event) => {
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    form.classList.add('was-validated');
  }, false);
})();

// Validación para formularios de edición en modales
(function() {
  const editForms = Array.from(document.querySelectorAll('form.needs-validation'));
  editForms.forEach(f => {
    f.addEventListener('submit', (event) => {
      if (!f.checkValidity()) {
        event.preventDefault();
        event.stopPropagation();
      }
      f.classList.add('was-validated');
    }, false);
  });
})();

(function(){
  const container = document.getElementById('toastContainer');
  function showToast(message, type) {
    if (!message) return;
    const bg = type === 'success' ? 'bg-success text-white' : type === 'warning' ? 'bg-warning text-dark' : type === 'danger' ? 'bg-danger text-white' : 'bg-primary text-white';
    const id = 't' + String(Date.now());
    const html = `<div id="${id}" class="toast align-items-center ${bg}" role="alert" aria-live="assertive" aria-atomic="true"><div class="d-flex"><div class="toast-body">${message}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>`;
    container.insertAdjacentHTML('beforeend', html);
    const el = document.getElementById(id);
    const t = new bootstrap.Toast(el, { delay: 2500 });
    t.show();
    el.addEventListener('hidden.bs.toast', () => { el.remove(); });
  }
  <?php if ($flash): ?>
  showToast('<?= htmlspecialchars($flash) ?>', '<?= htmlspecialchars($flashType) ?>');
  <?php endif; ?>
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
