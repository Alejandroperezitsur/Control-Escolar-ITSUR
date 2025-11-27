<?php
require_once __DIR__ . '/../../app/init.php';
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
$role = $_SESSION['role'] ?? null;
?>
<aside class="app-sidebar">
  <div class="brand">
    <a href="<?php echo $base; ?>/app.php?r=/dashboard" class="brand-link">SICEnet · ITSUR</a>
    <div class="small text-muted">Sistema Integral de Control Escolar</div>
  </div>
  <nav class="menu">
    <?php if ($role === 'alumno'): ?>
      <div class="menu-section">
        <div class="menu-section-title">Operaciones Académicas</div>
        <ul>
          <li><a href="<?php echo $base; ?>/app.php?r=/alumno/calificaciones" data-icon="bi-journal-text">Kardex</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/alumno/carga" data-icon="bi-list-check">Mi Carga Académica</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/alumno/reticula" data-icon="bi-diagram-3">Retícula Académica</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/alumno/reinscripcion" data-icon="bi-arrow-repeat">Reinscripción</a></li>
        </ul>
      </div>
    <?php elseif ($role === 'admin'): ?>
      <div class="menu-section">
        <div class="menu-section-title">Operaciones Académicas</div>
        <ul>
          <li><a href="<?php echo $base; ?>/app.php?r=/reports" data-icon="bi-journal-text">Kardex</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/groups" data-icon="bi-list-check">Mi Carga Académica</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/careers" data-icon="bi-diagram-3">Retícula Académica</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/dashboard" data-icon="bi-arrow-repeat">Reinscripción</a></li>
        </ul>
      </div>
      <div class="menu-section">
        <div class="menu-section-title">Gestión</div>
        <ul>
          <!-- Entrada 'Grupos' removida del sidebar para evitar duplicados en la navegación superior -->
          <li><a href="<?php echo $base; ?>/app.php?r=/alumnos" data-icon="bi-people">Alumnos</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/professors" data-icon="bi-person-badge">Profesores</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/subjects" data-icon="bi-book">Materias</a></li>
        </ul>
      </div>
    <?php else: ?>
      <div class="menu-section">
        <div class="menu-section-title">Operaciones Académicas</div>
        <ul>
          <li><a href="<?php echo $base; ?>/app.php?r=/login" data-icon="bi-journal-text">Kardex</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/login" data-icon="bi-list-check">Mi Carga Académica</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/login" data-icon="bi-diagram-3">Retícula Académica</a></li>
          <li><a href="<?php echo $base; ?>/app.php?r=/login" data-icon="bi-arrow-repeat">Reinscripción</a></li>
        </ul>
      </div>
    <?php endif; ?>
    <div class="menu-section">
      <ul>
        <li><a href="<?php echo $base; ?>/app.php?r=/logout" data-icon="bi-box-arrow-right">Cerrar Sesión</a></li>
      </ul>
    </div>
  </nav>
</aside>
