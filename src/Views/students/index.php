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
        <?php 
        $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH); 
        ?>
        <form class="d-flex" method="get" action="<?= htmlspecialchars($currentPath) ?>">
            <?php if (isset($_GET['r'])): ?>
                <input type="hidden" name="r" value="<?= htmlspecialchars($_GET['r']) ?>">
            <?php endif; ?>
            <input class="form-control form-control-sm me-2" type="search" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($_GET['q'] ?? '') ?>" aria-label="Search" data-bs-toggle="tooltip" title="Buscar por matrícula, nombre o email">
            <select name="status" class="form-select form-select-sm me-2" style="max-width: 120px;" data-bs-toggle="tooltip" title="Filtrar por estado">
                <option value="" <?= (!isset($_GET['status']) || $_GET['status'] === '' ) ? 'selected' : '' ?>>Todos</option>
                <option value="active" <?= (isset($_GET['status']) && $_GET['status'] === 'active') ? 'selected' : '' ?>>Activos</option>
                <option value="inactive" <?= (isset($_GET['status']) && $_GET['status'] === 'inactive') ? 'selected' : '' ?>>Inactivos</option>
            </select>
            <select name="career" class="form-select form-select-sm me-2" style="max-width: 160px;" data-bs-toggle="tooltip" title="Filtrar por carrera">
                <option value="0">Todas las carreras</option>
                <?php foreach (($careers ?? []) as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= ((int)($_GET['career'] ?? 0) === $c['id']) ? 'selected' : '' ?>><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="group" class="form-select form-select-sm me-2" style="max-width: 180px;" data-bs-toggle="tooltip" title="Filtrar por grupo">
                <option value="0">Todos los grupos</option>
                <?php foreach (($groups ?? []) as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= ((int)($_GET['group'] ?? 0) === $g['id']) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($g['nombre']) ?> - <?= htmlspecialchars($g['materia_nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-sm btn-outline-secondary" type="submit" data-bs-toggle="tooltip" title="Buscar"><i class="fa-solid fa-magnifying-glass"></i></button>
        </form>
        <button class="btn btn-sm btn-primary" onclick="openCreateModal()" data-bs-toggle="tooltip" title="Registrar un nuevo alumno">
            <i class="fa-solid fa-plus me-1"></i> Nuevo Alumno
        </button>
        <a href="/public/app.php?r=/dashboard" class="btn btn-sm btn-outline-secondary">Volver</a>
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
            
            function sortLink($col, $label, $currentSort, $currentOrder, $baseParams, $path) {
                $newOrder = ($currentSort === $col && $currentOrder === 'ASC') ? 'DESC' : 'ASC';
                $icon = '';
                if ($currentSort === $col) {
                    $icon = ($currentOrder === 'ASC') ? '<i class="fa-solid fa-sort-up ms-1"></i>' : '<i class="fa-solid fa-sort-down ms-1"></i>';
                } else {
                    $icon = '<i class="fa-solid fa-sort text-muted ms-1" style="opacity:0.3"></i>';
                }
                $params = array_merge($baseParams, ['sort' => $col, 'order' => $newOrder]);
                $url = $path . '?' . http_build_query($params);
                return "<a href=\"$url\" class=\"text-decoration-none text-dark fw-bold\">$label $icon</a>";
            }
            ?>
            <tr>
              <th class="ps-4"><?= sortLink('matricula', 'Matrícula', $currentSort, $currentOrder, $baseParams, $currentPath) ?></th>
              <th><?= sortLink('nombre', 'Nombre Completo', $currentSort, $currentOrder, $baseParams, $currentPath) ?></th>
              <th><?= sortLink('email', 'Email', $currentSort, $currentOrder, $baseParams, $currentPath) ?></th>
              <th>Carrera</th>

              <th><?= sortLink('activo', 'Estado', $currentSort, $currentOrder, $baseParams, $currentPath) ?></th>
              <th class="text-end pe-4">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($students)): ?>
                <tr><td colspan="5" class="text-center py-5 text-muted">No se encontraron alumnos.</td></tr>
            <?php else: foreach ($students as $s): ?>
              <tr>
                <td class="ps-4 fw-medium"><a href="/public/app.php?r=/alumnos/detalle&id=<?= (int)$s['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($s['matricula']) ?></a></td>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-initials bg-primary-subtle text-primary rounded-circle me-2 d-flex align-items-center justify-content-center" style="width: 32px; height: 32px; font-size: 0.85rem;">
                            <?= strtoupper(substr($s['nombre'],0,1).substr($s['apellido'],0,1)) ?>
                        </div>
                        <div>
                            <a href="/public/app.php?r=/alumnos/detalle&id=<?= (int)$s['id'] ?>" class="text-decoration-none">
                                <?= htmlspecialchars($s['nombre'] . ' ' . $s['apellido']) ?>
                            </a>
                        </div>
                    </div>
                </td>
                <td class="text-dark small"><a href="/public/app.php?r=/alumnos/detalle&id=<?= (int)$s['id'] ?>" class="text-decoration-none"><?= htmlspecialchars($s['email'] ?? '—') ?></a></td>
                <td class="small text-muted"><?= htmlspecialchars($s['carrera_nombre'] ?? 'Sin asignar') ?></td>

                <td>
                    <?php
                        $statusLabel = $s['activo'] ? 'Activo' : 'Inactivo';
                        $badgeClass = $s['activo'] ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger';
                        // Build URL preserving current search and toggling status filter
                        $params = $_GET;
                        $params['status'] = $s['activo'] ? 'active' : 'inactive';
                        $url = $currentPath . '?' . http_build_query($params);
                    ?>
                    <a href="<?= $url ?>" class="badge <?= $badgeClass ?> rounded-pill text-decoration-none">
                        <?= $statusLabel ?>
                    </a>
                </td>
                <td class="text-end pe-4">
                    <button class="btn btn-sm btn-link text-decoration-none p-0 me-2" data-bs-toggle="modal" data-bs-target="#studentModal" onclick="openEditModal(<?= $s['id'] ?>)" title="Editar alumno" data-bs-toggle="tooltip">
                        <i class="fa-solid fa-pen-to-square"></i>
                    </button>
                    <button class="btn btn-sm btn-link text-danger text-decoration-none p-0" onclick="deleteStudent(<?= $s['id'] ?>)" title="Eliminar alumno" data-bs-toggle="tooltip">
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
                $prevUrl = $currentPath . '?' . http_build_query($queryParams);
                
                $queryParams['page'] = min($totalPages ?? 1, $page + 1);
                $nextUrl = $currentPath . '?' . http_build_query($queryParams);
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
</div>

<style>
/* Fix modal z-index and interaction issues */
.modal {
    z-index: 1055 !important;
}
.modal-backdrop {
    z-index: 1050 !important;
}
.modal-dialog {
    z-index: 1056 !important;
    pointer-events: auto !important;
}
.modal-content {
    pointer-events: auto !important;
    position: relative;
    z-index: 1057 !important;
}
.modal-body input,
.modal-body select,
.modal-body textarea,
.modal-body button,
.modal-footer button {
    pointer-events: auto !important;
}
</style>

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
            <div class="invalid-feedback">Ingresa la matrícula.</div>
          </div>
          
          <div class="row g-3 mb-3">
            <div class="col-6">
                <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>
                <div class="invalid-feedback">Ingresa el nombre.</div>
            </div>
            <div class="col-6">
                <label for="apellido" class="form-label">Apellido <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="apellido" name="apellido" required>
                <div class="invalid-feedback">Ingresa el apellido.</div>
            </div>
          </div>

          <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" class="form-control" id="email" name="email">
            <div class="invalid-feedback">Ingresa un correo válido.</div>
          </div>

          <div class="mb-3">
            <label for="carrera_id" class="form-label">Carrera</label>
            <select class="form-select" id="carrera_id" name="carrera_id">
                <option value="">Selecciona una carrera...</option>
                <?php foreach (($careers ?? []) as $c): ?>
                    <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre']) ?></option>
                <?php endforeach; ?>
            </select>
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

<div id="toastContainer" class="position-fixed top-0 end-0 p-3" style="z-index:1100"></div>

<script>
const API_BASE_URL = window.location.origin + '/public';
let modalInstance = null;
const careerClaveToId = <?php echo json_encode((function($careers){ $m=[]; foreach(($careers??[]) as $c){ if(isset($c['clave'])) $m[$c['clave']]=$c['id']; } return $m; })($careers)); ?>;
function inferCareerIdFromMatricula(m){ if(!m||typeof m!=='string'||m.length<1) return ''; const p=m.trim().toUpperCase().charAt(0); let clave=''; switch(p){ case 'S': clave='ISC'; break; case 'I': clave='II'; break; case 'A': clave='IGE'; break; case 'E': clave='IE'; break; case 'M': clave='IM'; break; case 'Q': clave='IER'; break; case 'C': clave='CP'; break; default: return ''; } return careerClaveToId[clave]||''; }

function getModal() {
    const el = document.getElementById('studentModal');
    if (!el) { return null; }
    if (typeof bootstrap === 'undefined') {
        return {
            show: function(){ el.classList.add('show'); el.removeAttribute('aria-hidden'); el.setAttribute('aria-modal','true'); },
            hide: function(){ el.classList.remove('show'); el.setAttribute('aria-hidden','true'); el.removeAttribute('aria-modal'); }
        };
    }
    if (!modalInstance) { modalInstance = new bootstrap.Modal(el); }
    return modalInstance;
}

function openCreateModal() {
    try {
        const form = document.getElementById('studentForm');
        if (form) form.reset();
        
        const idEl = document.getElementById('studentId');
        if (idEl) idEl.value = '';
        
        const carEl = document.getElementById('carrera_id');
        if (carEl) carEl.value = '';
        
        const title = document.getElementById('modalTitle');
        if (title) title.textContent = 'Nuevo Alumno';
        
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
        const mat = document.getElementById('matricula');
        const carSel = document.getElementById('carrera_id');
        if(mat && carSel){ mat.addEventListener('input', function(){ const cid = inferCareerIdFromMatricula(mat.value); if(cid){ carSel.value = String(cid); } }); }
    } catch (e) {
        console.error(e);
        alert('Error: ' + e.message);
    }
}

function openEditModal(id) {
    const url = `${API_BASE_URL}/app.php?r=/alumnos/get&id=${id}`;
    console.log('Fetching student from:', url);

    try {
        const m = getModal();
        if (m) m.show();
        const title = document.getElementById('modalTitle');
        if (title) title.textContent = 'Cargando alumno...';
        const btn = document.getElementById('saveBtn');
        if (btn) { btn.disabled = true; btn.textContent = 'Cargando...'; }
    } catch {}

    fetch(url, { credentials: 'same-origin' })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.error('Raw response:', text);
                    throw new Error('El servidor devolvió una respuesta inválida (no es JSON). Ver consola.');
                }
            });
        })
        .then(data => {
            if(data.error) { alert(data.error); return; }
            
            document.getElementById('studentId').value = data.id;
            document.getElementById('matricula').value = data.matricula;
            document.getElementById('nombre').value = data.nombre;
            document.getElementById('apellido').value = data.apellido;
            document.getElementById('email').value = data.email || '';
            document.getElementById('carrera_id').value = data.carrera_id || '';

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
            
            const title = document.getElementById('modalTitle');
            if (title) title.textContent = 'Editar Alumno';
            const btn = document.getElementById('saveBtn');
            if (btn) { btn.disabled = false; btn.textContent = 'Guardar'; }
            showToast('Datos del alumno cargados', 'success');
        })
        .catch(e => {
            console.error('Fetch error:', e);
            const title = document.getElementById('modalTitle');
            if (title) title.textContent = 'Editar Alumno';
            const btn = document.getElementById('saveBtn');
            if (btn) { btn.disabled = false; btn.textContent = 'Guardar'; }
            showToast('Error de conexión al obtener datos', 'danger');
        });
}

function saveStudent(e) {
    e.preventDefault();
    const form = e.target;
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    const formData = new FormData(form);
    const id = formData.get('id');
    const url = id ? `${API_BASE_URL}/app.php?r=/alumnos/update` : `${API_BASE_URL}/app.php?r=/alumnos/store`;
    console.log('Saving to:', url);
    
    const btn = document.getElementById('saveBtn');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    fetch(url, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => {
        return response.text().then(text => {
            try {
                return JSON.parse(text);
            } catch (e) {
                console.error('Raw response:', text);
                throw new Error('El servidor devolvió una respuesta inválida (no es JSON). Ver consola.');
            }
        });
    })
    .then(data => {
        console.log('Response:', data);
        if(data.success) {
            showToast(id ? 'Alumno actualizado' : 'Alumno creado', 'success');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.error || 'Error desconocido', 'danger');
        }
    })
    .catch(err => {
        console.error(err);
        showToast('Error de red', 'danger');
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
    
    fetch(`${API_BASE_URL}/app.php?r=/alumnos/delete`, {
        method: 'POST',
        body: formData,
        credentials: 'same-origin'
    })
    .then(response => response.text().then(text => {
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('Raw response:', text);
            throw new Error('Respuesta inválida del servidor');
        }
    }))
    .then(data => {
        if(data.success) {
            showToast('Alumno eliminado', 'warning');
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(data.error || 'Error al eliminar', 'danger');
        }
    })
    .catch(e => {
        console.error(e);
        showToast('Error de red', 'danger');
    });
}

function showToast(message, type = 'success') {
    try {
        const container = document.getElementById('toastContainer');
        const bg = type === 'success' ? 'bg-success text-white' : type === 'warning' ? 'bg-warning text-dark' : 'bg-danger text-white';
        const id = 't' + String(Date.now());
        const html = `
        <div id="${id}" class="toast align-items-center ${bg}" role="alert" aria-live="assertive" aria-atomic="true">
          <div class="d-flex">
            <div class="toast-body">${message}</div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
        </div>`;
        container.insertAdjacentHTML('beforeend', html);
        const el = document.getElementById(id);
        const t = new bootstrap.Toast(el, { delay: 2500 });
        t.show();
        el.addEventListener('hidden.bs.toast', () => { el.remove(); });
    } catch (e) {}
}
</script>

<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
