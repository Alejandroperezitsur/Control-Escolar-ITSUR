<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');

// Obtener carreras de la base de datos
$pdo = \Database::getInstance()->getConnection();
// Intentar con activo, si falla usar sin filtro
try {
    // Filter only valid careers to avoid duplicates/garbage data
    $validKeys = "'ISC', 'II', 'IGE', 'IE', 'IM', 'IER', 'CP'";
    $stmt = $pdo->query("SELECT * FROM carreras WHERE clave IN ($validKeys) ORDER BY nombre");
} catch (PDOException $e) {
    // Fallback
    $stmt = $pdo->query("SELECT * FROM carreras ORDER BY nombre");
}
$carreras = $stmt->fetchAll(PDO::FETCH_ASSOC);

ob_start();
?>
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
      <i class="fa-solid fa-graduation-cap me-2"></i>
      Carreras y Planes de Estudio
    </h2>
    <div>
      <?php if (isset($_SESSION['user']) && $_SESSION['user']['rol'] === 'admin'): ?>
        <button class="btn btn-primary me-2" id="toggleEditMode">
          <i class="fa-solid fa-edit me-1"></i> Editar Plan
        </button>
      <?php endif; ?>
      <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">
        <i class="fa-solid fa-arrow-left me-1"></i> Volver al Dashboard
      </a>
    </div>
  </div>

  <?php if (empty($carreras)): ?>
    <div class="alert alert-warning">
      <i class="fa-solid fa-exclamation-triangle me-2"></i>
      No hay carreras registradas en el sistema.
    </div>
  <?php else: ?>
    <!-- Tabs para diferentes carreras -->
    <ul class="nav nav-tabs mb-4" id="careerTabs" role="tablist">
      <?php foreach ($carreras as $index => $carrera): ?>
        <li class="nav-item" role="presentation">
          <button 
            class="nav-link <?php echo $index === 0 ? 'active' : ''; ?>" 
            id="career-<?php echo $carrera['id']; ?>-tab" 
            data-bs-toggle="tooltip" 
            title="Ver plan de estudios de <?php echo htmlspecialchars($carrera['nombre']); ?>"
            data-bs-target="#career-<?php echo $carrera['id']; ?>" 
            type="button" 
            role="tab">
            <i class="fa-solid fa-book-open me-2"></i><?php echo htmlspecialchars($carrera['nombre']); ?>
          </button>
        </li>
      <?php endforeach; ?>
    </ul>

    <!-- Contenido de las tabs -->
    <div class="tab-content" id="careerTabsContent">
      <?php foreach ($carreras as $index => $carrera): ?>
        <div class="tab-pane fade <?php echo $index === 0 ? 'show active' : ''; ?>" id="career-<?php echo $carrera['id']; ?>" role="tabpanel">
          <!-- Información de la carrera -->
          <div class="row mb-4">
            <div class="col-md-12">
              <div class="card border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                  <h5 class="mb-0">
                    <i class="fa-solid fa-info-circle me-2"></i>
                    Información de la Carrera
                  </h5>
                </div>
                <div class="card-body">
                  <div class="row">
                    <div class="col-md-4">
                      <p class="mb-2"><strong>Clave:</strong> <?php echo htmlspecialchars($carrera['clave'] ?? 'N/A'); ?></p>
                      <?php if (isset($carrera['duracion_semestres'])): ?>
                        <p class="mb-2"><strong>Duración:</strong> <?php echo $carrera['duracion_semestres']; ?> semestres</p>
                      <?php endif; ?>
                      <?php if (isset($carrera['creditos_totales'])): ?>
                        <p class="mb-0"><strong>Créditos totales:</strong> <?php echo $carrera['creditos_totales']; ?></p>
                      <?php endif; ?>
                    </div>
                    <div class="col-md-8">
                      <?php if (isset($carrera['descripcion']) && !empty($carrera['descripcion'])): ?>
                        <p class="mb-0"><strong>Perfil del egresado:</strong> <?php echo htmlspecialchars($carrera['descripcion']); ?></p>
                      <?php else: ?>
                        <p class="mb-0 text-muted"><em>Descripción no disponible</em></p>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Diagrama de materias -->
          <div class="row g-3" id="curriculum-<?php echo $carrera['id']; ?>" data-career-clave="<?php echo htmlspecialchars($carrera['clave']); ?>">
            <div class="col-12">
              <div class="d-flex justify-content-center align-items-center py-5">
                <div class="spinner-border text-primary me-3" role="status">
                  <span class="visually-hidden">Cargando...</span>
                </div>
                <span class="text-muted">Cargando plan de estudios...</span>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<!-- Modal: Agregar Materia -->
<div class="modal fade" id="addSubjectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fa-solid fa-plus me-2"></i>Agregar Materia al Plan</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="addSubjectForm">
          <input type="hidden" id="add_carrera_id" name="carrera_id">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
          
          <div class="mb-3">
            <label class="form-label">Materia</label>
            <select class="form-select" id="add_materia_id" name="materia_id" required>
              <option value="">Cargando...</option>
            </select>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Semestre</label>
            <select class="form-select" name="semestre" required>
              <?php for($i=1; $i<=8; $i++): ?>
                <option value="<?php echo $i; ?>">Semestre <?php echo $i; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          
          <div class="row">
            <div class="col-md-6 mb-3">
              <label class="form-label">Créditos</label>
              <input type="number" class="form-control" name="creditos" min="1" max="10" value="5" required>
            </div>
            <div class="col-md-6 mb-3">
              <label class="form-label">Tipo</label>
              <select class="form-select" name="tipo" required>
                <option value="Básica">Básica</option>
                <option value="Especialidad">Especialidad</option>
              </select>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary" onclick="saveAddSubject()">
          <i class="fa-solid fa-save me-1"></i>Agregar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: Editar Materia -->
<div class="modal fade" id="editSubjectModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fa-solid fa-edit me-2"></i>Editar Materia</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <form id="editSubjectForm">
          <input type="hidden" id="edit_mc_id" name="mc_id">
          <input type="hidden" id="edit_materia_id" name="materia_id">
          <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token'] ?? ''; ?>">
          
          <div class="mb-3">
            <label class="form-label">Materia</label>
            <input type="text" class="form-control" id="edit_materia_nombre" readonly>
          </div>
          
          <div class="mb-3">
            <label class="form-label">Semestre</label>
            <select class="form-select" id="edit_semestre" name="semestre" required>
              <?php for($i=1; $i<=8; $i++): ?>
                <option value="<?php echo $i; ?>">Semestre <?php echo $i; ?></option>
              <?php endfor; ?>
            </select>
          </div>
          
          <div class="row">
            <div class="col-md-4 mb-3">
              <label class="form-label">Créditos</label>
              <input type="number" class="form-control" id="edit_creditos" name="creditos" min="1" max="10" required>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Tipo</label>
              <select class="form-select" id="edit_tipo" name="tipo" required>
                <option value="Básica">Básica</option>
                <option value="Especialidad">Especialidad</option>
              </select>
            </div>
            <div class="col-md-4 mb-3">
              <label class="form-label">Parciales</label>
              <input type="number" class="form-control" id="edit_parciales" name="num_parciales" min="2" max="5" required>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" onclick="saveEditSubject()">
          <i class="fa-solid fa-save me-1"></i>Guardar Cambios
        </button>
      </div>
    </div>
  </div>
</div>

<style>
/* Mejorar contraste de texto - NEGRO para mejor visibilidad */
.nav-tabs .nav-link {
  color: #000000 !important;  /* Negro para mejor contraste */
  background-color: #f8f9fa;
  border: 1px solid #dee2e6;
  font-weight: 500;
}

.nav-tabs .nav-link:hover {
  color: #0d6efd;
  background-color: #e9ecef;
  border-color: #dee2e6;
}

.nav-tabs .nav-link.active {
  color: #fff;
  background-color: #0d6efd;
  border-color: #0d6efd;
}

.subject-card {
  transition: all 0.3s ease;
  cursor: pointer;
  border-left: 4px solid transparent;
  background: #ffffff;
  border: 1px solid #dee2e6;
}

.subject-card:hover {
  transform: translateY(-5px);
  box-shadow: 0 8px 16px rgba(0,0,0,0.15);
  border-color: #0d6efd;
}

.subject-card.specialty {
  border-left-color: #0d6efd;
}

.subject-card.general {
  border-left-color: #6c757d;
}

.subject-card.residencia {
  border-left-color: #198754;
  background-color: #f8fff9;
}

.subject-card .card-title {
  color: #212529;
  font-weight: 600;
  font-size: 0.95rem;
}

.semester-column {
  min-height: 400px;
}

.semester-header {
  position: sticky;
  top: 0;
  z-index: 10;
  background: #ffffff;
  padding: 1rem;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
  margin-bottom: 1rem;
  border: 1px solid #dee2e6;
}
.semester-header.sem-1 { background-color: #0d6efd; color: #ffffff; border-color: #0b5ed7; }
.semester-header.sem-2 { background-color: #6610f2; color: #ffffff; border-color: #520dc2; }
.semester-header.sem-3 { background-color: #6f42c1; color: #ffffff; border-color: #59359a; }
.semester-header.sem-4 { background-color: #d63384; color: #ffffff; border-color: #b02569; }
.semester-header.sem-5 { background-color: #fd7e14; color: #ffffff; border-color: #dc6803; }
.semester-header.sem-6 { background-color: #ffc107; color: #212529; border-color: #ffb300; }
.semester-header.sem-7 { background-color: #20c997; color: #ffffff; border-color: #1aa97e; }
.semester-header.sem-8 { background-color: #0dcaf0; color: #212529; border-color: #0bb8db; }

.semester-header h5 {
  color: #212529;
  font-weight: 600;
}

.semester-header small {
  color: #6c757d;
  font-weight: 500;
}

.subject-credits {
  font-size: 0.75rem;
  font-weight: 600;
  background-color: #0d6efd;
  color: #ffffff;
}

.subject-code {
  font-size: 0.7rem;
  color: #6c757d;
  font-family: 'Courier New', monospace;
  font-weight: 600;
}

.card-header {
  font-weight: 600;
}

.alert-info {
  background-color: #cfe2ff;
  border-color: #b6d4fe;
  color: #084298;
}

.alert-info .alert-link {
  color: #052c65;
  font-weight: 600;
}
</style>

<script>
let isEditMode = false;
const basePath = '<?php echo $base; ?>';

document.addEventListener('DOMContentLoaded', function() {
  
  // Toggle Edit Mode
  const toggleBtn = document.getElementById('toggleEditMode');
  if (toggleBtn) {
    toggleBtn.addEventListener('click', function() {
      isEditMode = !isEditMode;
      this.innerHTML = isEditMode ? '<i class="fa-solid fa-check me-1"></i> Terminar Edición' : '<i class="fa-solid fa-edit me-1"></i> Editar Plan';
      this.classList.toggle('btn-primary');
      this.classList.toggle('btn-success');
      reloadCurrentTab();
    });
  }

  // 1. Load the initially active tab
  const activeTabPane = document.querySelector('.tab-pane.active');
  if (activeTabPane) {
    const container = activeTabPane.querySelector('[id^="curriculum-"]');
    if (container) loadCurriculum(container);
  }

  // 2. Listen for tab changes to lazy load others
  const tabEls = document.querySelectorAll('button[data-bs-toggle="tab"]');
  tabEls.forEach(tabEl => {
    tabEl.addEventListener('shown.bs.tab', event => {
      const targetId = event.target.getAttribute('data-bs-target');
      const targetPane = document.querySelector(targetId);
      if (targetPane) {
        const container = targetPane.querySelector('[id^="curriculum-"]');
        if (container) loadCurriculum(container);
      }
    });
  });
});

function loadCurriculum(container) {
    // Check if already loaded or loading (unless force reload)
    if (container.dataset.loading === 'true') return;
    if (container.dataset.loaded === 'true' && !container.dataset.forceReload) return;
    
    const careerClave = container.dataset.careerClave;
    if (!careerClave) return;
    
    container.dataset.loading = 'true';
    container.innerHTML = `
        <div class="col-12">
          <div class="d-flex justify-content-center align-items-center py-5">
            <div class="spinner-border text-primary me-3" role="status"></div>
            <span class="text-muted">Cargando plan de estudios...</span>
          </div>
        </div>
    `;
    
    fetch(`${basePath}/api/careers/curriculum?career=${encodeURIComponent(careerClave)}`)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          container.innerHTML = `<div class="col-12"><div class="alert alert-warning">${data.error}</div></div>`;
        } else {
          container.innerHTML = renderCurriculum(data, basePath);
        }
        container.dataset.loaded = 'true';
        delete container.dataset.forceReload;
      })
      .catch(error => {
        console.error('Error:', error);
        container.innerHTML = `<div class="col-12"><div class="alert alert-danger">Error al cargar el plan.</div></div>`;
      })
      .finally(() => {
        container.dataset.loading = 'false';
      });
}

function reloadCurrentTab() {
    const activeTabPane = document.querySelector('.tab-pane.active');
    if (activeTabPane) {
        const container = activeTabPane.querySelector('[id^="curriculum-"]');
        if (container) {
            container.dataset.forceReload = 'true';
            loadCurriculum(container);
        }
    }
}

function renderCurriculum(semesters, basePath) {
  if (!semesters || semesters.length === 0) {
    let emptyHtml = `
      <div class="col-12">
        <div class="alert alert-info">
          <i class="fa-solid fa-info-circle me-2"></i>
          <strong>Próximamente:</strong> El plan de estudios estará disponible pronto.
        </div>
      </div>
    `;
    // If edit mode, show button to add first semester
    if (isEditMode) {
        emptyHtml += `
            <div class="col-12 text-center">
                <button class="btn btn-primary" onclick="openAddModal(1, event)">
                    <i class="fa-solid fa-plus me-2"></i> Agregar Primera Materia
                </button>
            </div>
        `;
    }
    return emptyHtml;
  }
  
  let html = '';
  
  // Find max semester to ensure we show all columns up to max or 8
  let maxSem = 0;
  semesters.forEach(s => maxSem = Math.max(maxSem, s.semester));
  if (isEditMode) maxSem = Math.max(maxSem, 8); // Show up to 8 semesters in edit mode

  // Create map for easy access
  const semMap = {};
  semesters.forEach(s => semMap[s.semester] = s);

  for (let i = 1; i <= maxSem; i++) {
    const semester = semMap[i] || { semester: i, subjects: [] };
    const semesterNum = semester.semester;
    const subjects = semester.subjects || [];
    const isResidencias = semesterNum === 9;
    const headerClass = isResidencias ? 'bg-success text-white' : ('sem-' + (semesterNum % 9));
    
    let addBtn = '';
    if (isEditMode) {
        addBtn = `
            <div class="text-center mt-3 pt-2 border-top">
                <button class="btn btn-sm btn-outline-primary w-100" onclick="openAddModal(${semesterNum}, event)">
                    <i class="fa-solid fa-plus"></i> Agregar
                </button>
            </div>
        `;
    }

    html += `
      <div class="col-md-6 col-lg-4 col-xl-3 semester-column mb-4">
        <div class="semester-header ${headerClass}">
          <h5 class="mb-1">Semestre ${semesterNum}</h5>
          <small>${subjects.length} materia${subjects.length !== 1 ? 's' : ''}</small>
        </div>
        <div class="subjects-list">
          ${subjects.map(subject => renderSubjectCard(subject)).join('')}
        </div>
        ${addBtn}
      </div>
    `;
  }
  
  return html;
}

function renderSubjectCard(subject) {
  const typeClass = subject.type === 'Especialidad' ? 'specialty' : 'general';
  const typeLabel = subject.type;
  const typeColor = subject.type === 'Especialidad' ? 'primary' : 'secondary';
  
  let actions = '';
  if (isEditMode) {
    // Escape quotes for onclick
    const safeName = subject.name.replace(/'/g, "\\'");
    actions = `
        <div class="mt-2 d-flex justify-content-end gap-2 border-top pt-2" onclick="event.stopPropagation()">
            <button class="btn btn-sm btn-outline-warning" 
                onclick="openEditModal(${subject.mc_id}, ${subject.materia_id}, '${safeName}', ${subject.credits}, '${subject.type}', ${subject.parciales}, ${subject.semester || 0}, event)">
                <i class="fa-solid fa-edit"></i>
            </button>
            <button class="btn btn-sm btn-outline-danger" 
                onclick="deleteSubject(${subject.mc_id}, '${safeName}', event)">
                <i class="fa-solid fa-trash"></i>
            </button>
        </div>
    `;
  }
  
  return `
    <div class="card subject-card ${typeClass} mb-3" onclick="if(!isEditMode) showSubjectDetails('${subject.code}', '${subject.name.replace(/'/g, "\\'")}')">
      <div class="card-body p-3">
        <h6 class="card-title mb-2" style="color: #000000 !important;">${subject.name}</h6>
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <span class="subject-code text-uppercase">${subject.code}</span>
          <span class="badge subject-credits bg-${typeColor}">${subject.credits} créditos</span>
        </div>
        <div class="mt-2 d-flex justify-content-between align-items-center">
          <span class="badge bg-light text-dark border">${typeLabel}</span>
          ${subject.parciales ? `<span class="badge bg-info text-dark" title="Parciales">${subject.parciales} P</span>` : ''}
        </div>
        ${actions}
      </div>
    </div>
  `;
}

function showSubjectDetails(code, name) {
  alert(`Materia: ${name}\nClave: ${code}\n\nFuncionalidad de detalles en desarrollo.`);
}

// --- CRUD Operations ---

function openAddModal(semestre, event) {
    if(event) event.stopPropagation();
    
    // Get current career ID
    const activeTabPane = document.querySelector('.tab-pane.active');
    const container = activeTabPane.querySelector('[id^="curriculum-"]');
    const careerClave = container.dataset.careerClave;
    
    // Find career ID from tab ID (career-X)
    const careerId = activeTabPane.id.replace('career-', '');
    document.getElementById('add_carrera_id').value = careerId;
    
    // Set semester
    const semSelect = document.querySelector('#addSubjectForm select[name="semestre"]');
    semSelect.value = semestre;
    
    // Load available subjects
    const select = document.getElementById('add_materia_id');
    select.innerHTML = '<option value="">Cargando...</option>';
    
    fetch(`${basePath}/api/careers/curriculum/available-subjects?career=${encodeURIComponent(careerClave)}`)
        .then(res => res.json())
        .then(data => {
            select.innerHTML = '<option value="">Seleccione una materia...</option>';
            data.forEach(m => {
                select.innerHTML += `<option value="${m.id}">${m.nombre} (${m.clave}) - ${m.creditos} cr</option>`;
            });
        })
        .catch(err => {
            console.error(err);
            select.innerHTML = '<option value="">Error al cargar materias</option>';
        });
        
    const modal = new bootstrap.Modal(document.getElementById('addSubjectModal'));
    modal.show();
}

function saveAddSubject() {
    const form = document.getElementById('addSubjectForm');
    const data = Object.fromEntries(new FormData(form).entries());
    
    fetch(`${basePath}/api/careers/curriculum/add`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if(data.error) {
            alert('Error: ' + data.error);
        } else {
            bootstrap.Modal.getInstance(document.getElementById('addSubjectModal')).hide();
            reloadCurrentTab();
        }
    })
    .catch(err => alert('Error de red'));
}

function openEditModal(mcId, materiaId, nombre, creditos, tipo, parciales, semestre, event) {
    if(event) event.stopPropagation();
    
    document.getElementById('edit_mc_id').value = mcId;
    document.getElementById('edit_materia_id').value = materiaId;
    document.getElementById('edit_materia_nombre').value = nombre;
    document.getElementById('edit_creditos').value = creditos;
    document.getElementById('edit_tipo').value = tipo;
    document.getElementById('edit_parciales').value = parciales;
    document.getElementById('edit_semestre').value = semestre || 1;
    
    const modal = new bootstrap.Modal(document.getElementById('editSubjectModal'));
    modal.show();
}

function saveEditSubject() {
    const form = document.getElementById('editSubjectForm');
    const data = Object.fromEntries(new FormData(form).entries());
    
    fetch(`${basePath}/api/careers/curriculum/update`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    })
    .then(res => res.json())
    .then(data => {
        if(data.error) {
            alert('Error: ' + data.error);
        } else {
            bootstrap.Modal.getInstance(document.getElementById('editSubjectModal')).hide();
            reloadCurrentTab();
        }
    })
    .catch(err => alert('Error de red'));
}

function deleteSubject(mcId, nombre, event) {
    if(event) event.stopPropagation();
    
    if(!confirm(`¿Estás seguro de eliminar "${nombre}" del plan de estudios?`)) return;
    
    const csrf = document.querySelector('input[name="csrf_token"]').value;
    
    fetch(`${basePath}/api/careers/curriculum/remove`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ mc_id: mcId, csrf_token: csrf })
    })
    .then(res => res.json())
    .then(data => {
        if(data.error) {
            alert('Error: ' + data.error);
        } else {
            reloadCurrentTab();
        }
    })
    .catch(err => alert('Error de red'));
}
</script>


<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
