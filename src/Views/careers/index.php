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
    <a href="<?php echo $base; ?>/dashboard" class="btn btn-outline-secondary">
      <i class="fa-solid fa-arrow-left me-1"></i> Volver al Dashboard
    </a>
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
// Lazy load curriculum data
document.addEventListener('DOMContentLoaded', function() {
  const basePath = '<?php echo $base; ?>';
  
  // Function to load curriculum for a specific container
  const loadCurriculum = (container) => {
    // Check if already loaded or loading
    if (container.dataset.loaded === 'true' || container.dataset.loading === 'true') return;
    
    const careerClave = container.dataset.careerClave;
    if (!careerClave) {
        container.innerHTML = `
            <div class="col-12">
                <div class="alert alert-warning">
                    <i class="fa-solid fa-exclamation-triangle me-2"></i>
                    No se encontró la clave de la carrera.
                </div>
            </div>
        `;
        return;
    }
    
    // Mark as loading
    container.dataset.loading = 'true';
    
    fetch(`${basePath}/api/careers/curriculum?career=${encodeURIComponent(careerClave)}`)
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          container.innerHTML = `
            <div class="col-12">
              <div class="alert alert-warning">
                <i class="fa-solid fa-exclamation-triangle me-2"></i>
                ${data.error}
              </div>
            </div>
          `;
        } else {
          container.innerHTML = renderCurriculum(data, basePath);
        }
        // Mark as loaded
        container.dataset.loaded = 'true';
      })
      .catch(error => {
        console.error('Error loading curriculum:', error);
        container.innerHTML = `
          <div class="col-12">
            <div class="alert alert-danger">
              <i class="fa-solid fa-exclamation-circle me-2"></i>
              Error al cargar el plan de estudios.
              <button class="btn btn-sm btn-outline-danger ms-3" onclick="location.reload()">Reintentar</button>
            </div>
          </div>
        `;
      })
      .finally(() => {
        container.dataset.loading = 'false';
      });
  };

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
      const targetId = event.target.getAttribute('data-bs-target'); // e.g. #career-1
      const targetPane = document.querySelector(targetId);
      if (targetPane) {
        const container = targetPane.querySelector('[id^="curriculum-"]');
        if (container) loadCurriculum(container);
      }
    });
  });
});

function renderCurriculum(semesters, basePath) {
  if (!semesters || semesters.length === 0) {
    return `
      <div class="col-12">
        <div class="alert alert-info">
          <i class="fa-solid fa-info-circle me-2"></i>
          <strong>Próximamente:</strong> El plan de estudios para esta carrera estará disponible pronto.
          Por ahora, puedes gestionar las materias desde el módulo de <a href="${basePath}/subjects" class="alert-link">Materias</a>.
        </div>
      </div>
    `;
  }
  
  let html = '';
  
  semesters.forEach(semester => {
    const semesterNum = semester.semester;
    const subjects = semester.subjects || [];
    const isResidencias = semesterNum === 9;
    const headerClass = isResidencias ? 'bg-success text-white' : ('sem-' + (semesterNum % 9));
    
    html += `
      <div class="col-md-6 col-lg-4 col-xl-3 semester-column">
        <div class="semester-header ${headerClass}">
          <h5 class="mb-1">Semestre ${semesterNum}</h5>
          <small>${subjects.length} materia${subjects.length !== 1 ? 's' : ''}</small>
          ${isResidencias ? '<div class="mt-2"><i class="fa-solid fa-star me-1"></i><strong>Requisitos:</strong> 10 niveles de Inglés + Servicio Social</div>' : ''}
        </div>
        <div class="subjects-list">
          ${subjects.map(subject => renderSubjectCard(subject, isResidencias)).join('')}
        </div>
      </div>
    `;
  });
  
  return html;
}

function renderSubjectCard(subject, isResidencias) {
  const typeClass = subject.type === 'especialidad' ? 'specialty' : 
                   subject.type === 'residencia' ? 'residencia' : 'general';
  const typeLabel = subject.type === 'especialidad' ? 'Especialidad' : 
                   subject.type === 'residencia' ? 'Residencia' : 'Básica';
  const typeColor = subject.type === 'especialidad' ? 'primary' : 
                   subject.type === 'residencia' ? 'success' : 'secondary';
  
  return `
    <div class="card subject-card ${typeClass} mb-3" onclick="showSubjectDetails('${subject.code}', '${subject.name.replace(/'/g, "\\'")}')" data-bs-toggle="tooltip" title="Ver detalles de ${subject.name}">
      <div class="card-body p-3">
        <h6 class="card-title mb-2" style="color: #000000 !important;">${subject.name}</h6>
        <div class="d-flex justify-content-between align-items-center flex-wrap">
          <span class="subject-code text-uppercase">${subject.code}</span>
          <span class="badge subject-credits bg-${typeColor}">${subject.credits} créditos</span>
        </div>
        <div class="mt-2">
          <span class="badge bg-light text-dark border">${typeLabel}</span>
        </div>
      </div>
    </div>
  `;
}

function showSubjectDetails(code, name) {
  alert(`Materia: ${name}\nClave: ${code}\n\nFuncionalidad de detalles en desarrollo.`);
}
</script>


<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
