<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2 class="mb-1">Alumnos <span class="badge bg-primary rounded-pill fs-6 align-middle ms-2">Total: <?= $total ?? 0 ?></span></h2>
        <p class="text-muted small mb-0">Gestión de estudiantes registrados</p>
    </div>
    <div class="d-flex gap-2">
        <form class="d-flex" method="get">
            <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" aria-label="Search">
            <select name="status" class="form-select form-select-sm me-2" style="max-width: 120px;">
                <option value="" <?= (!isset($_GET['status']) || $_GET['status'] === '' ) ? 'selected' : '' ?>>Todos</option>
                <option value="active" <?= (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : '' ?>>Activos</option>
                <option value="inactive" <?= (isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'selected' : '' ?>>Inactivos</option>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
        <button class="btn btn-sm btn-primary" onclick="openCreateModal()">
            <i class="fa-solid fa-plus me-1"></i> Nuevo Alumno
        </button>
        <a href="<?php echo $base; ?>/dashboard" class="btn btn-sm btn-outline-secondary">Volver</a>
    </div>
  </div>

  <div class="card shadow-sm border-0">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="bg-light">
            <?php
            $currentSort = $_GET['sort'] ?? 'apellido';
            $currentOrder = strtoupper($_GET['order'] ?? 'ASC');
            $baseParams = $_GET;
            unset($baseParams['sort'], $baseParams['order']);
            
            function sortLink($col, $label, $currentSort, $currentOrder, $baseParams) {
                $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
                $icon = '';
                if ($currentSort === $col) {
                    $icon = ($currentOrder === 'ASC') ? '<i class="fa-solid fa-sort-up ms-1"></i>' : '<i class="fa-solid fa-sort-down ms-1"></i>';
                } else {
                    $icon = '<i class="fa-solid fa-sort text-muted ms-1" style="opacity:0.3"></i>';
                }
                $params = array_merge($baseParams, ['sort' => $col, 'order' => $newOrder]);
                $url = '?' . http_build_query($params);
                return "<a href=\"$url\" class=\"text-decoration-none text-dark fw-bold\">$label $icon</a>";
            }
            ?>
            <tr>
              <th class="ps-4"><?= sortLink('matricula', 'Matrícula', $currentSort, $currentOrder, $baseParams) ?></th>
              <th><?= sortLink('nombre', 'Nombre Completo', $currentSort, $currentOrder, $baseParams) ?></th>
              <th><?= sortLink('email', 'Email', $currentSort, $currentOrder, $baseParams) ?></th>

              <th><?= sortLink('activo', 'Estado', $currentSort, $currentOrder, $baseParams) ?></th>
              <th class="text-end pe-4">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
                <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron alumnos.</td></tr>
            <?php else: foreach ($students as $s): ?>
              <tr>
                <td class="ps-4 fw-medium"><a href="<?= $base; ?>/alumnos/detalle?id=<?= (int)$s['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($s['matricula']) ?></a></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-initials bg-primary-subtle text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                            <?= strtoupper(substr($s['nombre'],0,1).substr($s['apellido'],0,1)) ?>
                        </div>
                        <div>
                            <a href="<?= $base; ?>/alumnos/detalle?id=<?= (int)$s['id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($s['nombre'] . ' ' . $s['apellido']) ?>
                            </a>
                        </div>
                    </div>
                </td>
                <td class="text-dark small"><a href="<?= $base; ?>/alumnos/detalle?id=<?= (int)$s['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($s['email'] ?? '—') ?></a></td>

                <td>
                    <?php
                        $statusLabel = $s['activo'] ? 'Activo' : 'Inactivo';
                        $badgeClass = $s['activo'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                        // Build URL preserving current search and toggling status filter
                        $url = $base . '/alumnos?';
                        $params = [];
                        if (!empty($_GET['q'])) $params['q'] = $_GET['q'];
                        $params['status'] = $s['activo'] ? 'active' : 'inactive';
                        $url .= http_build_query($params);
                    ?>
                    <a href="<?= $url ?>" class="badge <?= $badgeClass ?> rounded-pill text-decoration-none">
                        <?= $statusLabel ?>
                    </a>
                </td>
                <td class="text-end pe-4">
                    <button class="btn btn-sm btn-link text-decoration-none p-0 me-2" onclick="openEditModal(<?= $s['id'] ?>)" title="Editar">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn btn-sm btn-link text-danger text-decoration-none p-0" onclick="deleteStudent(<?= $s['id'] ?>)" title="Eliminar">
                        <i class="fa-solid fa-trash"></i>
                    </button>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
    <div class="card-footer bg-white border-top-0 py-3">
        <nav aria-label="Page navigation">
            <ul class="pagination justify-content-center mb-0">
                <?php
                $queryParams = $_GET;
                $queryParams['page'] = max(1, $page - 1);
                $prevUrl = '?' . http_build_query($queryParams);
                
                $queryParams['page'] = min($totalPages ?? 1, $page + 1);
                $nextUrl = '?' . http_build_query($queryParams);
                ?>
                <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($prevUrl) ?>">Anterior</a>
                </li>
                <li class="page-item disabled"><span class="page-link">Página <?= $page ?> de <?= $totalPages ?? 1 ?></span></li>
                <li class="page-item <?= ($page >= ($totalPages ?? 1)) ? 'disabled' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars($nextUrl) ?>">Siguiente</a>
                </li>
            </ul>
        </nav>
    </div>
  </div>
</div>

<!-- Modal Create/Edit -->
<div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
            <form id="studentForm" onsubmit="saveStudent(event)">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
        <div class="modal-header">
          <h5 class="modal-title" id="modalTitle">Nuevo Alumno</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" id="studentId" name="id">
          
          <div class="mb-3">
            <label for="matricula" class="form-label">Matrícula <span class="text-danger">*</span></label>
            <input type="text" class="form-control" id="matricula" name="matricula" required>
          </div>
          
          <div class="row g-3 mb-3">
            <div class="col-6">
                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>
            <div class="col-6">
                <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="apellido" name="apellido" required>
            </div>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email">
          </div>



          <div class="mb-3">
            <label for="password" class="form-label">Contraseña</label>
            <input type="password" class="form-control" id="password" name="password" placeholder="Dejar en blanco para mantener actual">
            <div class="form-text small text-muted" id="passwordHelp">Requerida para nuevos alumnos.</div>
          </div>

          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
            <label class="form-check-label" for="activo">Alumno Activo</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary" id="saveBtn">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const BASE_URL = '<?php echo $base; ?>';
let modalInstance = null;

function getModal() {
    if (typeof bootstrap === 'undefined') {
        console.error('Bootstrap not loaded');
        alert('Error: Componentes de interfaz no cargados. Recargue la página.');
        return null;
    }
    if (!modalInstance) {
        const el = document.getElementById('studentModal');
        if (!el) { console.error('Modal element missing'); return null; }
        modalInstance = new bootstrap.Modal(el);
    }
    return modalInstance;
}

function openCreateModal() {
    try {
        document.getElementById('studentForm').reset();
        document.getElementById('studentId').value = '';
        document.getElementById('modalTitle').textContent = 'Nuevo Alumno';
        
        const pwd = document.getElementById('password');
        if(pwd) {
            pwd.placeholder = 'Contraseña';
            pwd.required = true;
        }
        
        const help = document.getElementById('passwordHelp');
        if(help) help.style.display = 'none';
        
        const act = document.getElementById('activo');
        if(act) act.checked = true;
        
        const m = getModal();
        if(m) m.show();
    } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
    }
}

function openEditModal(id) {
    fetch(`${BASE_URL}/alumnos/get?id=${id}`)
        .then(r => r.json())
        .then(data => {
            if(data.error) { alert(data.error); return; }
            
            document.getElementById('studentId').value = data.id;
            document.getElementById('matricula').value = data.matricula;
            document.getElementById('nombre').value = data.nombre;
            document.getElementById('apellido').value = data.apellido;
            document.getElementById('email').value = data.email || '';

            const act = document.getElementById('activo');
            if(act) act.checked = data.activo == 1;
            
            document.getElementById('modalTitle').textContent = 'Editar Alumno';
            
            const pwd = document.getElementById('password');
            if(pwd) {
                pwd.placeholder = 'Dejar en blanco para mantener actual';
                pwd.required = false;
            }
            
            const help = document.getElementById('passwordHelp');
            if(help) help.style.display = 'block';
            
            const m = getModal();
            if(m) m.show();
        })
        .catch(e => {
            console.error(e);
            alert('Error de conexión al obtener datos');
        });
}

function saveStudent(e) {
    e.preventDefault();
    console.log('Saving student...');
    const form = e.target;
    const formData = new FormData(form);
    const id = formData.get('id');
    const url = id ? `${BASE_URL}/alumnos/update` : `${BASE_URL}/alumnos/store`;
    console.log('URL:', url);
    
    const btn = document.getElementById('saveBtn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        console.log('Response:', data);
        if(data.success) {
            location.reload();
        } else {
            alert(data.error || 'Error desconocido');
        }
    })
    .catch(err => {
        console.error(err);
        alert('Error de red');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}

function deleteStudent(id) {
    if(!confirm('¿Estás seguro de eliminar este alumno? Esta acción no se puede deshacer.')) return;
    
    const formData = new FormData();
    formData.append('id', id);
    formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?? '' ?>');
    
    fetch(`${BASE_URL}/alumnos/delete`, {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if(data.success) {
            location.reload();
        } else {
            alert(data.error || 'Error al eliminar');
        }
    });
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
