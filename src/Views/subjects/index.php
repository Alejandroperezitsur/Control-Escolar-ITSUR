<?php
// Expect $subjects
$csrf = $_SESSION['csrf_token'] ?? '';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3>Materias</h3>
    <div class="d-flex align-items-center gap-2">
      <form method="get" action="<?php echo $base; ?>/subjects" class="d-flex align-items-center">
        <input type="text" name="q" value="<?= htmlspecialchars((string)($pagination['q'] ?? '')) ?>" class="form-control" placeholder="Buscar por nombre/clave" style="max-width:260px">
        <button class="btn btn-outline-primary ms-2" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
      </form>
      <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">Volver</a>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-body">
      <form method="post" action="<?php echo $base; ?>/subjects/create" class="row g-2 needs-validation" novalidate id="subjectForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <div class="col-md-4">
          <input class="form-control" name="nombre" placeholder="Nombre" required>
          <div class="invalid-feedback">Ingresa el nombre de la materia.</div>
        </div>
        <div class="col-md-3">
          <input class="form-control" name="clave" placeholder="Clave" required>
          <div class="invalid-feedback">Ingresa la clave.</div>
        </div>
        <div class="col-md-3">
          <button class="btn btn-primary" type="submit"><i class="fa-solid fa-plus me-1"></i> Agregar</button>
        </div>
      </form>
    </div>
  </div>

  <div class="table-responsive">
    <table class="table table-striped">
      <thead><tr>
        <?php $sort = (string)($pagination['sort'] ?? 'nombre'); $order = (string)($pagination['order'] ?? 'ASC'); $qv = urlencode((string)($pagination['q'] ?? '')); $pg = (int)($pagination['page'] ?? 1); $toggle = $order==='ASC'?'DESC':'ASC'; ?>
        <th><a href="<?php echo $base; ?>/subjects?page=<?= $pg ?>&sort=id&order=<?= $sort==='id'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?>">ID<?= $sort==='id' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th><a href="<?php echo $base; ?>/subjects?page=<?= $pg ?>&sort=nombre&order=<?= $sort==='nombre'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?>">Nombre<?= $sort==='nombre' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th><a href="<?php echo $base; ?>/subjects?page=<?= $pg ?>&sort=clave&order=<?= $sort==='clave'?$toggle:'ASC' ?><?= $qv!==''?"&q=$qv":'' ?>">Clave<?= $sort==='clave' ? ($order==='ASC'?' ▲':' ▼') : '' ?></a></th>
        <th class="text-end">Acciones</th>
      </tr></thead>
      <tbody>
        <?php foreach ($subjects as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['id']) ?></td>
          <td><?= htmlspecialchars($m['nombre']) ?></td>
          <td><?= htmlspecialchars($m['clave']) ?></td>
          <td class="text-end">
            <button class="btn btn-sm btn-outline-primary me-1" data-bs-toggle="modal" data-bs-target="#editSubj<?= (int)$m['id'] ?>">
              <i class="fa-solid fa-pen"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#delSubj<?= (int)$m['id'] ?>">
              <i class="fa-regular fa-trash-can"></i>
            </button>
            <div class="modal fade" id="delSubj<?= (int)$m['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Eliminar materia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">¿Confirmas eliminar "<?= htmlspecialchars($m['nombre']) ?>"?</div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="post" action="<?php echo $base; ?>/subjects/delete">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                      <button type="submit" class="btn btn-danger">Eliminar</button>
                    </form>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal fade" id="editSubj<?= (int)$m['id'] ?>" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog">
                <div class="modal-content">
                  <div class="modal-header"><h5 class="modal-title">Editar materia</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <form method="post" action="<?php echo $base; ?>/subjects/update" class="needs-validation" novalidate>
                    <div class="modal-body">
                      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                      <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                      <div class="mb-2">
                        <label class="form-label">Nombre</label>
                        <input class="form-control" name="nombre" value="<?= htmlspecialchars($m['nombre']) ?>" required>
                        <div class="invalid-feedback">Ingresa el nombre.</div>
                      </div>
                      <div class="mb-2">
                        <label class="form-label">Clave</label>
                        <input class="form-control" name="clave" value="<?= htmlspecialchars($m['clave']) ?>" required>
                        <div class="invalid-feedback">Ingresa la clave.</div>
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
      <?php $pg = (int)($pagination['page'] ?? 1); $pages = (int)($pagination['pages'] ?? 1); $qv = urlencode((string)($pagination['q'] ?? '')); ?>
      <?php for ($i=1; $i<=$pages; $i++): ?>
        <li class="page-item <?= $i === $pg ? 'active' : '' ?>"><a class="page-link" href="<?php echo $base; ?>/subjects?page=<?= $i ?><?= $qv!==''?"&q=$qv":'' ?>"><?= $i ?></a></li>
      <?php endfor; ?>
    </ul>
  </nav>
</div>

<script>
// Bootstrap client-side validation
(() => {
  const form = document.getElementById('subjectForm');
  form.addEventListener('submit', (event) => {
    if (!form.checkValidity()) {
      event.preventDefault();
      event.stopPropagation();
    }
    form.classList.add('was-validated');
  }, false);
})();
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
