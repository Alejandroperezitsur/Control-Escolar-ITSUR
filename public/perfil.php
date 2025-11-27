<?php
header('Location: app.php?r=/dashboard');
exit;
?>
$isAlumno = ($_SESSION['role'] ?? '') === 'alumno';
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SICEnet · ITSUR — Mi Perfil</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <link href="assets/css/desktop-fixes.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<div class="app-shell">
  <!-- Sidebar eliminado: accesos centralizados en dashboard -->
  <main class="app-content">
    <?php $pageTitle = 'Mi Perfil'; ?>
    <div class="d-flex flex-column mb-3">
      <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
      <?php $breadcrumbs = [ ['label' => 'Inicio', 'url' => 'dashboard.php'], ['label' => $pageTitle, 'url' => null] ]; ?>
      <?php require __DIR__ . '/partials/breadcrumb.php'; ?>
    </div>
    <p class="text-muted">Información de tu cuenta y acceso.</p>

    <div class="row g-4">
      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-person-circle"></i> Datos</h5>
            <div class="table-responsive">
              <table class="table table-sm">
                <tbody>
                  <tr><th>Rol</th><td><?= htmlspecialchars(ucfirst($_SESSION['role'] ?? '')) ?></td></tr>
                  <?php if ($isAlumno): ?>
                  <tr><th>Nombre</th><td><?= htmlspecialchars(($user['nombre'] ?? '') . ' ' . ($user['apellido'] ?? '')) ?></td></tr>
                  <tr><th>Matrícula</th><td><?= htmlspecialchars($user['matricula'] ?? '') ?></td></tr>
                  <tr><th>Email</th><td><?= htmlspecialchars($user['email'] ?? '') ?></td></tr>
                  <tr><th>Estado</th><td><?= (int)($user['activo'] ?? 0) ? 'Activo' : 'Inactivo' ?></td></tr>
                  <?php else: ?>
                  <tr><th>Matrícula</th><td><?= htmlspecialchars($user['matricula'] ?? '') ?></td></tr>
                  <tr><th>Email</th><td><?= htmlspecialchars($user['email'] ?? ($_SESSION['user_email'] ?? '')) ?></td></tr>
                  <tr><th>Último acceso</th><td><?= htmlspecialchars($user['last_login'] ?? '-') ?></td></tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
            <?php if ($isAlumno): ?>
            <a class="btn btn-primary" href="kardex.php?matricula=<?= urlencode($user['matricula'] ?? '') ?>">
              <i class="bi bi-journal-text"></i> Ver mi Kardex
            </a>
            <a class="btn btn-outline-primary ms-2" href="mi_carga.php">
              <i class="bi bi-list-check"></i> Mi Carga Académica
            </a>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="col-md-6">
        <div class="card">
          <div class="card-body">
            <h5 class="card-title"><i class="bi bi-shield-lock"></i> Seguridad</h5>
            <p class="mb-2">Si notas actividad inusual, cambia tu contraseña o avisa al administrador.</p>
            <ul class="small text-muted mb-0">
              <li>No compartas tus credenciales.</li>
              <li>Cierra sesión en equipos compartidos.</li>
              <li>Usa contraseñas fuertes y únicas.</li>
            </ul>
            <div class="mt-3">
              <a href="cambiar_password.php" class="btn btn-outline-primary btn-sm">
                <i class="bi bi-key"></i> Cambiar contraseña
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
