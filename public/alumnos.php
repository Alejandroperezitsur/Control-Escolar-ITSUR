<?php
header('Location: app.php?r=/alumnos');
exit;
?>
<?php
require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/capas/negocio/ControlAlumnos.php';

use App\Capas\Negocio\ControlAlumnos;

$auth = new AuthController();
$auth->requireAuth();
$auth->requireRole(['admin']);
$user = $auth->getCurrentUser();

$alumnoModel = new ControlAlumnos();
$message = '';
$error = '';
$editAlumno = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $token = $_POST['csrf_token'] ?? '';
  if (!$auth->validateCSRFToken($token)) {
        http_response_code(400);
        $error = 'CSRF inválido';
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
                try {
          $ok = $alumnoModel->delete($id);
                    if ($ok) {
                        $message = 'Alumno eliminado correctamente';
                    } else {
                        $error = 'No se pudo eliminar el alumno (referencias existentes o error).';
                    }
                } catch (Throwable $e) {
                    $error = 'Error al eliminar: ' . $e->getMessage();
                }
            } else {
                $error = 'ID inválido para eliminar';
            }
        } else if ($action === 'toggle_active') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $error = 'ID inválido para activar/desactivar';
            } else {
                try {
                    $al = $alumnoModel->find($id);
                    if ($al && isset($al['activo'])) {
                        $nuevo = (int)$al['activo'] ? 0 : 1;
                        $ok = $alumnoModel->update($id, ['activo' => $nuevo]);
                        if ($ok) {
                            $message = $nuevo ? 'Alumno activado' : 'Alumno desactivado';
                        } else {
                            $error = 'No se pudo cambiar el estado';
                        }
                    } else {
                        $error = 'Alumno no encontrado o columna activo ausente';
                    }
                } catch (Throwable $e) {
                    $error = 'Error al cambiar estado: ' . $e->getMessage();
                }
            }
        } else {
            $id = (int)($_POST['id'] ?? 0);
            $matricula = trim((string)($_POST['matricula'] ?? ''));
            $nombre = trim((string)($_POST['nombre'] ?? ''));
            $apellido = trim((string)($_POST['apellido'] ?? ''));
            $email = trim((string)($_POST['email'] ?? ''));
            $password = (string)($_POST['password'] ?? '');

            if ($action === 'create') {
                if ($matricula === '' || $nombre === '' || $apellido === '' || $email === '' || $password === '') {
                    $error = 'Todos los campos son obligatorios para registrar';
                } else {
                    try {
            $alumnoModel->create([
                            'matricula' => $matricula,
                            'nombre' => $nombre,
                            'apellido' => $apellido,
                            'email' => $email,
                            'password' => $password,
                            'activo' => 1,
                        ]);
                        $message = 'Alumno creado correctamente';
                    } catch (Throwable $e) {
                        $error = 'Error al crear el alumno: ' . $e->getMessage();
                    }
                }
            } else { // update
                if ($id <= 0) {
                    $error = 'ID inválido para actualizar';
                } else {
                    $data = [
                        'matricula' => $matricula,
                        'nombre' => $nombre,
                        'apellido' => $apellido,
                        'email' => $email,
                    ];
                    if ($password !== '') {
                        $data['password'] = $password;
                    }
                    try {
                        $ok = $alumnoModel->update($id, $data);
                        if ($ok) {
                            $message = 'Alumno actualizado correctamente';
                        } else {
                            $error = 'No se pudo actualizar el alumno';
                        }
                    } catch (Throwable $e) {
                        $error = 'Error al actualizar: ' . $e->getMessage();
                    }
                }
            }
        }
    }
}

$
// Cargar alumno para edición si corresponde
$editId = max(0, (int)($_GET['edit_id'] ?? 0));
if ($editId > 0) {
    $editAlumno = $alumnoModel->find($editId);
}

$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$q = trim((string)($_GET['q'] ?? ''));
$estado = (string)($_GET['estado'] ?? '');
$estado = ($estado === '1' || $estado === '0') ? $estado : '';
if ($q !== '') {
  if ($estado !== '') {
    $alumnos = $alumnoModel->searchByEstado($q, $estado, $page, $limit);
    $total = $alumnoModel->countSearchByEstado($q, $estado);
  } else {
    $alumnos = $alumnoModel->search($q, $page, $limit);
    $total = $alumnoModel->countSearch($q);
  }
} else {
  if ($estado !== '') {
    $alumnos = $alumnoModel->getAllByEstado($estado, $page, $limit);
    $total = $alumnoModel->countByEstado($estado);
  } else {
    $alumnos = $alumnoModel->getAll($page, $limit);
    $total = $alumnoModel->count();
  }
}
$totalPages = max(1, (int)ceil($total / $limit));
$csrf = $auth->generateCSRFToken();
// Base dinámico para assets y enlaces
$base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SICEnet · ITSUR — Alumnos</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <link href="assets/css/desktop-fixes.css" rel="stylesheet">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
</head>
<body data-theme="dark">
<?php require __DIR__ . '/partials/header.php'; ?>

<div class="app-shell">
  <!-- Sidebar eliminado: accesos centralizados en dashboard -->
  <main class="app-content">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <div>
      <?php $pageTitle = 'Alumnos'; ?>
      <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
      <?php $breadcrumbs = [
        ['label' => 'Inicio', 'url' => 'dashboard.php'],
        ['label' => 'Gestión Académica', 'url' => null],
        ['label' => $pageTitle, 'url' => null],
      ]; ?>
      <?php require __DIR__ . '/partials/breadcrumb.php'; ?>
      <div class="text-muted small">Mostrando <?= count($alumnos) ?> de <?= (int)$total ?></div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
      <form method="get" class="d-flex align-items-center" role="search">
        <input type="hidden" name="page" value="1">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control" placeholder="Buscar por matrícula, nombre o email">
        <select name="estado" class="form-select ms-2" style="max-width: 180px;">
          <option value="" <?= $estado === '' ? 'selected' : '' ?>>Todos</option>
          <option value="1" <?= $estado === '1' ? 'selected' : '' ?>>Activos</option>
          <option value="0" <?= $estado === '0' ? 'selected' : '' ?>>Inactivos</option>
        </select>
        <button class="btn btn-outline-primary ms-2" type="submit">Buscar</button>
        <?php if ($q !== '' || $estado !== ''): ?>
          <a href="alumnos.php" class="btn btn-outline-secondary ms-2">Limpiar</a>
        <?php endif; ?>
      </form>
        <a href="dashboard.php" class="btn btn-outline-secondary">Volver</a>
    </div>
  </div>

  <?php if ($message): ?>
    <div class="alert alert-success" role="alert"><?= htmlspecialchars($message) ?></div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="alert alert-danger" role="alert"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>

  <div class="card mb-4">
    <div class="card-header"><?= $editAlumno ? 'Editar Alumno' : 'Agregar Alumno' ?></div>
    <div class="card-body">
      <form method="post" class="row g-3" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
        <?php if ($editAlumno): ?>
          <input type="hidden" name="action" value="update">
          <input type="hidden" name="id" value="<?= (int)$editAlumno['id'] ?>">
        <?php else: ?>
          <input type="hidden" name="action" value="create">
        <?php endif; ?>
        <div class="col-md-3">
          <label class="form-label">Matrícula</label>
          <input type="text" name="matricula" class="form-control" required value="<?= $editAlumno ? htmlspecialchars($editAlumno['matricula']) : '' ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Nombre</label>
          <input type="text" name="nombre" class="form-control" required value="<?= $editAlumno ? htmlspecialchars($editAlumno['nombre']) : '' ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Apellido</label>
          <input type="text" name="apellido" class="form-control" required value="<?= $editAlumno ? htmlspecialchars($editAlumno['apellido']) : '' ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" required value="<?= $editAlumno ? htmlspecialchars($editAlumno['email']) : '' ?>">
        </div>
        <div class="col-md-3">
          <label class="form-label">Contraseña <?= $editAlumno ? '(dejar en blanco para conservar)' : '' ?></label>
          <input type="password" name="password" class="form-control" <?= $editAlumno ? '' : 'required' ?>>
        </div>
        <div class="col-12">
          <button type="submit" class="btn btn-primary"><?= $editAlumno ? 'Guardar cambios' : 'Guardar' ?></button>
          <?php if ($editAlumno): ?>
            <a href="alumnos.php" class="btn btn-secondary ms-2">Cancelar</a>
          <?php endif; ?>
        </div>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header">Listado</div>
    <div class="card-body">
      <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div class="flex-grow-1" style="max-width: 320px;">
          <input type="text" class="form-control" placeholder="Filtrar rápido en la tabla" data-quick-filter-for="#tabla-alumnos">
        </div>
         <button class="btn btn-outline-primary btn-sm" data-export="csv" data-target="#tabla-alumnos" data-filename="alumnos.csv" data-timestamp="true"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
        <button class="btn btn-outline-secondary btn-sm" data-export="pdf" data-target="#tabla-alumnos"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
      </div>
      <div class="table-responsive">
        <table id="tabla-alumnos" class="table table-striped table-hover table-sort">
          <thead>
            <tr>
              <th>ID</th>
              <th>Matrícula</th>
              <th>Nombre</th>
              <th>Apellido</th>
              <th>Email</th>
              <th>Activo</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
          <?php if (count($alumnos) === 0): ?>
            <tr class="empty-state-row"><td colspan="7" class="text-center text-muted">No hay resultados</td></tr>
          <?php endif; ?>
          <?php foreach ($alumnos as $a): ?>
            <tr>
              <td><?= (int)$a['id'] ?></td>
              <td><?= htmlspecialchars($a['matricula']) ?></td>
              <td><?= htmlspecialchars($a['nombre']) ?></td>
              <td><?= htmlspecialchars($a['apellido']) ?></td>
              <td><?= htmlspecialchars($a['email']) ?></td>
              <td><?= isset($a['activo']) ? ((int)$a['activo'] ? 'Sí' : 'No') : '—' ?></td>
              <td>
                <a class="btn btn-sm btn-outline-primary" href="alumnos.php?edit_id=<?= (int)$a['id'] ?>">Editar</a>
                <form method="post" class="d-inline" onsubmit="return confirm('¿Eliminar este alumno?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                </form>
                <form method="post" class="d-inline" onsubmit="return confirm('¿Cambiar estado de este alumno?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">
                  <input type="hidden" name="action" value="toggle_active">
                  <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                  <?php $isActive = isset($a['activo']) ? (int)$a['activo'] : null; ?>
                  <button type="submit" class="btn btn-sm <?= ($isActive === null) ? 'btn-outline-secondary' : (($isActive) ? 'btn-outline-warning' : 'btn-outline-success') ?>" <?= ($isActive === null) ? 'disabled' : '' ?>>
                    <?= ($isActive === null) ? 'N/A' : (($isActive) ? 'Desactivar' : 'Activar') ?>
                  </button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <nav>
        <ul class="pagination">
          <?php for ($i = 1; $i <= $totalPages; $i++): ?>
          <li class="page-item <?= $i === $page ? 'active' : '' ?>">
            <a class="page-link" href="?page=<?= $i ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?><?= $estado !== '' ? '&estado=' . $estado : '' ?>"><?= $i ?></a>
          </li>
          <?php endfor; ?>
        </ul>
      </nav>
    </div>
  </div>
  </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
</body>
</html>
