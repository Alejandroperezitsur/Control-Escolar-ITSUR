<?php
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$containerFluid = true;
ob_start();
?>
<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="mb-1">Panel general</h3>
      <p class="text-muted mb-0">Resumen ejecutivo del sistema y accesos rápidos</p>
    </div>
  </div>

  <div class="row g-3 mb-4" id="admin-kpis">
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="flex-grow-1">
              <div class="text-muted small">Alumnos activos</div>
              <div class="h4 mb-0" id="kpi-alumnos">—</div>
            </div>
            <div class="ms-3 text-primary">
              <i class="fa-solid fa-user-graduate fa-2x" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="flex-grow-1">
              <div class="text-muted small">Profesores activos</div>
              <div class="h4 mb-0" id="kpi-profesores">—</div>
            </div>
            <div class="ms-3 text-primary">
              <i class="fa-solid fa-chalkboard-user fa-2x" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="flex-grow-1">
              <div class="text-muted small">Materias</div>
              <div class="h4 mb-0" id="kpi-materias">—</div>
            </div>
            <div class="ms-3 text-primary">
              <i class="fa-solid fa-book fa-2x" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card h-100">
        <div class="card-body">
          <div class="d-flex align-items-center mb-2">
            <div class="flex-grow-1">
              <div class="text-muted small">Carreras activas</div>
              <div class="h4 mb-0" id="kpi-carreras">—</div>
            </div>
            <div class="ms-3 text-primary">
              <i class="fa-solid fa-graduation-cap fa-2x" aria-hidden="true"></i>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row g-3 mb-4">
    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Promedio general</div>
          <div class="d-flex align-items-baseline">
            <span class="h3 mb-0" id="kpi-promedio">—</span>
          </div>
          <div class="text-muted small mt-2">
            Basado en todas las calificaciones registradas.
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Grupos con calificaciones</div>
          <div class="d-flex align-items-baseline">
            <span class="h3 mb-0" id="kpi-grupos">—</span>
          </div>
          <div class="text-muted small mt-2">
            Grupos que ya tienen al menos una evaluación registrada.
          </div>
        </div>
      </div>
    </div>
    <div class="col-12 col-md-4">
      <div class="card h-100">
        <div class="card-body">
          <div class="text-muted small mb-1">Pendientes de evaluación</div>
          <div class="d-flex align-items-baseline">
            <span class="h3 mb-0" id="kpi-pendientes">—</span>
          </div>
          <div class="text-muted small mt-2">
            Calificaciones sin final capturado en el sistema.
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center">
      <div>
        <h5 class="mb-0">Accesos rápidos</h5>
        <small class="text-muted">Navegación a los módulos operativos</small>
      </div>
    </div>
    <div class="card-body">
      <div class="row g-2 g-sm-3">
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <a href="<?php echo $base; ?>/alumnos" class="btn btn-primary w-100 mb-2" aria-label="Ir al módulo de alumnos">
            <i class="fa-solid fa-user-graduate me-2" aria-hidden="true"></i> Alumnos
          </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <a href="<?php echo $base; ?>/subjects" class="btn btn-primary w-100 mb-2" aria-label="Ir al módulo de materias">
            <i class="fa-solid fa-book me-2" aria-hidden="true"></i> Materias
          </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <a href="<?php echo $base; ?>/professors" class="btn btn-primary w-100 mb-2" aria-label="Ir al módulo de profesores">
            <i class="fa-solid fa-chalkboard-user me-2" aria-hidden="true"></i> Profesores
          </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <a href="<?php echo $base; ?>/groups" class="btn btn-primary w-100 mb-2" aria-label="Ir al módulo de grupos">
            <i class="fa-solid fa-users me-2" aria-hidden="true"></i> Grupos
          </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <a href="<?php echo $base; ?>/reports" class="btn btn-outline-primary w-100 mb-2" aria-label="Ir al módulo de reportes">
            <i class="fa-solid fa-chart-line me-2" aria-hidden="true"></i> Reportes
          </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <a href="<?php echo $base; ?>/careers" class="btn btn-outline-primary w-100 mb-2" aria-label="Ir al módulo de carreras">
            <i class="fa-solid fa-graduation-cap me-2" aria-hidden="true"></i> Carreras
          </a>
        </div>
        <div class="col-12 col-sm-6 col-md-4 col-xl-3">
          <a href="<?php echo $base; ?>/admin/settings" class="btn btn-outline-secondary w-100 mb-2" aria-label="Ir a ajustes de siembra y configuración">
            <i class="fa-solid fa-gear me-2" aria-hidden="true"></i> Ajustes
          </a>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  (function(){
    const alumnosEl = document.getElementById('kpi-alumnos');
    const profesoresEl = document.getElementById('kpi-profesores');
    const materiasEl = document.getElementById('kpi-materias');
    const carrerasEl = document.getElementById('kpi-carreras');
    const promedioEl = document.getElementById('kpi-promedio');
    const gruposEl = document.getElementById('kpi-grupos');
    const pendEl = document.getElementById('kpi-pendientes');

    function formatNumber(n) {
      return (typeof n === 'number' && !isNaN(n)) ? n.toLocaleString('es-MX') : '—';
    }

    function formatPromedio(v) {
      if (v === null || typeof v === 'undefined' || isNaN(Number(v))) return '—';
      return Number(v).toFixed(2);
    }

    fetch('/api/kpis/admin')
      .then(function(r){ return r.ok ? r.json() : Promise.reject(); })
      .then(function(data){
        if (alumnosEl) alumnosEl.textContent = formatNumber(data.alumnos ?? data.total_alumnos);
        if (profesoresEl) profesoresEl.textContent = formatNumber(data.profesores ?? data.total_profesores);
        if (materiasEl) materiasEl.textContent = formatNumber(data.materias ?? data.total_materias);
        if (carrerasEl) carrerasEl.textContent = formatNumber(data.carreras ?? data.total_carreras);
        if (promedioEl) promedioEl.textContent = formatPromedio(data.promedio ?? data.promedio_general);
        if (gruposEl) gruposEl.textContent = formatNumber(data.grupos ?? data.grupos_activos);
        if (pendEl) pendEl.textContent = formatNumber(data.pendientes_evaluacion ?? data.pendientes);
      })
      .catch(function(){
        if (typeof window.showToast === 'function') {
          window.showToast('error', 'No se pudieron cargar los indicadores del panel.');
        }
      });
  })();
</script>
<?php
$content = ob_get_clean();
include __DIR__ . '/../layout.php';
?>
