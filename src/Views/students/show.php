<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
ob_start();
?>
<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>

    <!-- Modal Create/Edit (duplicado aquí para permitir edición desde la vista de perfil) -->
    <div class="modal fade" id="studentModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
    <div class="modal-content">
          <form id="studentForm" onsubmit="saveStudent(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <div class="modal-header">
              <h5 class="modal-title" id="modalTitle">Editar Alumno</h5>
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
                <label for="password" class="form-label">Contraseña</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Dejar en blanco para mantener actual">
                <div class="form-text small text-muted" id="passwordHelp">Dejar en blanco para mantener la contraseña actual.</div>
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

    </div>
    <div class="d-flex gap-2">
      <a href="<?php echo $base; ?>/alumnos" class="btn btn-sm btn-outline-secondary">Volver</a>
      <button class="btn btn-sm btn-primary" onclick="openEditModal(<?php echo (int)$alumno['id']; ?>)"><i class="fa-solid fa-pen-to-square me-1"></i> Editar</button>
    </div>
  </div>

  <div class="row g-3 mb-3">
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-3">
            <div class="avatar-initials bg-primary-subtle text-primary rounded-circle me-3 d-flex align-items-center justify-content-center" style="width: 48px; height: 48px; font-size: 1.1rem;">
              <?php echo strtoupper(substr((string)$alumno['nombre'],0,1).substr((string)$alumno['apellido'],0,1)); ?>
            </div>
            <div>
              <div class="h5 mb-0"><?php echo htmlspecialchars(($alumno['nombre'] ?? '').' '.($alumno['apellido'] ?? '')); ?></div>
              <div class="text-muted small">Matrícula: <?php echo htmlspecialchars($alumno['matricula'] ?? ''); ?></div>
            </div>
          </div>
          <div class="row g-3">
            <div class="col-6">
              <div class="small text-muted">Email</div>
              <div><?php echo htmlspecialchars($alumno['email'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <div class="small text-muted">Estado</div>
              <div>
                <?php $act = (int)($alumno['activo'] ?? 0) === 1; ?>
                <span class="badge <?php echo $act ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'; ?> rounded-pill"><?php echo $act ? 'Activo' : 'Inactivo'; ?></span>
              </div>
            </div>
            <div class="col-6">
              <div class="small text-muted">Fecha de Nacimiento</div>
              <div><?php echo htmlspecialchars($alumno['fecha_nac'] ?? '—'); ?></div>
            </div>
            <div class="col-6">
              <div class="small text-muted">ID</div>
              <div><?php echo (int)$alumno['id']; ?></div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-md-6">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <div>
              <div class="h6 mb-0">Inscribir a Grupo</div>
              <div class="text-muted small">Agregar materia/grupo a la carga académica</div>
            </div>
          </div>
          <form id="enroll-form" onsubmit="enrollSubmit(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            <input type="hidden" name="alumno_id" value="<?php echo (int)$alumno['id']; ?>">
            <div class="row g-2">
              <div class="col-6">
                <label class="form-label small">Grupo</label>
                <select class="form-select form-select-sm" name="grupo_id" id="grupo_id">
                  <?php foreach (($allGroups ?? []) as $g): ?>
                    <option value="<?php echo (int)$g['id']; ?>"><?php echo htmlspecialchars(($g['ciclo'] ?? '').' · '.($g['materia'] ?? '').' · '.($g['nombre'] ?? '')); ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="col-6 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-primary w-100">Inscribir</button>
              </div>
            </div>
          </form>
          <div class="small text-muted mt-2">Evita duplicados: el sistema valida si ya está inscrito.</div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div>
          <div class="h6 mb-0">Carga Académica</div>
          <div class="text-muted small">Materias y grupos donde está inscrito</div>
        </div>
        <div>
          <select class="form-select form-select-sm" id="flt-ciclo" onchange="changeCycle(this.value)">
            <option value="">Todos los ciclos</option>
            <?php foreach (($ciclos ?? []) as $c): ?>
              <option value="<?php echo htmlspecialchars($c); ?>" <?php echo (isset($_GET['ciclo']) && $_GET['ciclo'] === $c) ? 'selected' : ''; ?>><?php echo htmlspecialchars($c); ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="table-responsive">
        <table class="table table-sm align-middle">
          <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Estado</th><th class="text-end">Calificación</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
            <?php if (!empty($carga)): foreach ($carga as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['ciclo'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['materia'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['grupo'] ?? ''); ?></td>
                <td class="text-end"><span class="badge <?php echo ($r['estado'] === 'Aprobado') ? 'bg-success-subtle text-success' : (($r['estado'] === 'Reprobado') ? 'bg-danger-subtle text-danger' : 'bg-warning-subtle text-warning'); ?> rounded-pill"><?php echo htmlspecialchars($r['estado'] ?? ''); ?></span></td>
                <td class="text-end"><?php echo $r['calificacion'] !== null ? (int)$r['calificacion'] : '—'; ?></td>
                <td class="text-end">
                  <?php
                    $gid = null;
                    $r_materia = (string)($r['materia'] ?? '');
                    $r_grupo = (string)($r['grupo'] ?? '');
                    $r_ciclo = (string)($r['ciclo'] ?? '');
                    foreach (($grupos ?? []) as $g) {
                      $g_materia = (string)($g['materia'] ?? '');
                      // grupos pueden usar la clave 'nombre' o 'grupo' para el nombre del grupo
                      $g_grupo = (string)($g['nombre'] ?? ($g['grupo'] ?? ''));
                      $g_ciclo = (string)($g['ciclo'] ?? '');
                      if ($g_materia === $r_materia && $g_grupo === $r_grupo && $g_ciclo === $r_ciclo) {
                        $gid = (int)($g['id'] ?? 0);
                        break;
                      }
                    }
                  ?>
                  <?php if ($gid): ?>
                    <button class="btn btn-sm btn-outline-danger" onclick="unenroll(<?php echo (int)$alumno['id']; ?>, <?php echo (int)$gid; ?>)">Quitar</button>
                  <?php else: ?>
                    <span class="text-muted small">—</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="6" class="text-muted">Sin registros</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card">
    <div class="card-body">
      <div class="h6 mb-2">Calificaciones</div>
      <div class="table-responsive">
        <table class="table table-striped table-hover table-sm">
          <thead><tr><th>Ciclo</th><th>Materia</th><th>Grupo</th><th class="text-end">Parcial 1</th><th class="text-end">Parcial 2</th><th class="text-end">Final</th><th class="text-end">Promedio</th></tr></thead>
          <tbody>
            <?php if (!empty($calificaciones)): foreach ($calificaciones as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['ciclo'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['materia'] ?? ''); ?></td>
                <td><?php echo htmlspecialchars($r['grupo'] ?? ''); ?></td>
                <td class="text-end"><?php echo $r['parcial1'] !== null ? (float)$r['parcial1'] : '—'; ?></td>
                <td class="text-end"><?php echo $r['parcial2'] !== null ? (float)$r['parcial2'] : '—'; ?></td>
                <td class="text-end"><?php echo $r['final'] !== null ? (float)$r['final'] : '—'; ?></td>
                <td class="text-end"><?php echo $r['promedio'] !== null ? (float)$r['promedio'] : '—'; ?></td>
              </tr>
            <?php endforeach; else: ?>
              <tr><td colspan="7" class="text-muted">No hay calificaciones registradas.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<script>
function changeCycle(c){
  const params = new URLSearchParams(window.location.search);
  if (c) params.set('ciclo', c); else params.delete('ciclo');
  window.location.search = params.toString();
}
function enrollSubmit(e){
  e.preventDefault();
  const form = e.target;
  const data = new FormData(form);
  fetch('<?php echo $base; ?>/alumnos/enroll', { method: 'POST', body: data })
    .then(r => r.json())
    .then(j => { if (j && j.success) { location.reload(); } else { alert('Error al inscribir'); } })
    .catch(()=> alert('Error de red'));
}
function unenroll(aid, gid){
  if (!confirm('¿Quitar este grupo de la carga académica?')) return;
  const data = new FormData(); data.append('alumno_id', String(aid)); data.append('grupo_id', String(gid));
  data.append('csrf_token', '<?php echo $_SESSION['csrf_token'] ?? '' ?>');
  fetch('<?php echo $base; ?>/alumnos/unenroll', { method: 'POST', body: data })
    .then(r => r.json())
    .then(j => { if (j && j.success) { location.reload(); } else { alert('Error al desinscribir'); } })
    .catch(()=> alert('Error de red'));
}

// Modal helpers y CRUD via AJAX (permitir editar desde perfil)
const BASE_URL = '<?php echo $base; ?>';
let modalInstance = null;
function getModal() {
  if (typeof bootstrap === 'undefined') { console.error('Bootstrap not loaded'); alert('Error: Componentes de interfaz no cargados. Recargue la página.'); return null; }
  if (!modalInstance) {
    const el = document.getElementById('studentModal');
    if (!el) { console.error('Modal element missing'); return null; }
    modalInstance = new bootstrap.Modal(el);
  }
  return modalInstance;
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
      const act = document.getElementById('activo'); if(act) act.checked = data.activo == 1;
      document.getElementById('modalTitle').textContent = 'Editar Alumno';
      const pwd = document.getElementById('password'); if(pwd) { pwd.placeholder = 'Dejar en blanco para mantener actual'; pwd.required = false; }
      const m = getModal(); if(m) m.show();
      showToast('Datos del alumno cargados', 'success');
    })
    .catch(e => { console.error(e); alert('Error de conexión al obtener datos'); });
}

function saveStudent(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);
  const id = formData.get('id');
  const url = id ? `${BASE_URL}/alumnos/update` : `${BASE_URL}/alumnos/store`;
  const btn = document.getElementById('saveBtn');
  const originalText = btn.textContent;
  btn.disabled = true; btn.textContent = 'Guardando...';
  fetch(url, { method: 'POST', body: formData })
  .then(r => r.json())
  .then(data => { if(data.success) { location.reload(); } else { alert(data.error || 'Error desconocido'); } })
  .catch(err => { console.error(err); showToast('Error de red', 'danger'); })
  .finally(() => { btn.disabled = false; btn.textContent = originalText; });
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
