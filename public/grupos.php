<?php
header('Location: app.php?r=/groups');
exit;
?>
<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/capas/negocio/ControlAutenticacion.php';
require_once __DIR__ . '/../app/capas/negocio/ControlGrupos.php';
require_once __DIR__ . '/../app/capas/negocio/ControlMaterias.php';
require_once __DIR__ . '/../app/capas/negocio/ControlUsuarios.php';

use App\Capas\Negocio\ControlAutenticacion;
use App\Capas\Negocio\ControlGrupos;
use App\Capas\Negocio\ControlMaterias;
use App\Capas\Negocio\ControlUsuarios;

$controlAut = new ControlAutenticacion();
$controlAut->requerirAutenticacion();

$controlGrupos = new ControlGrupos();
$controlMaterias = new ControlMaterias();
$controlUsuarios = new ControlUsuarios();

$isAdmin = ($_SESSION['role'] ?? '') === 'admin';
$msg = '';
$error = '';
// Para edición
$editGrupo = null;

if ($isAdmin && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validación CSRF básica
    $tokenPost = $_POST['csrf_token'] ?? '';
    if (!$controlAut->validarTokenCSRF($tokenPost)) {
        $error = 'Token CSRF inválido';
    } else {
        $action = $_POST['action'] ?? 'create';
        if ($action === 'delete') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) {
                $error = 'ID inválido';
            } else {
                try {
                        $controlGrupos->obtenerConJoins(1,1); // sanity check
                        // intentamos eliminar usando el modelo subyacente vía adaptador de datos
                        // El adaptador aún no expone delete; usamos el modelo directamente como fallback
                        require_once __DIR__ . '/../app/models/Grupo.php';
                        $gm = new \Grupo();
                        $gm->delete($id);
                    $msg = 'Grupo eliminado';
                } catch (Throwable $e) {
                    $error = 'No se pudo eliminar (referencias existentes)';
                }
            }
        } else {
            $materia_id = filter_input(INPUT_POST, 'materia_id', FILTER_VALIDATE_INT);
            $profesor_id = filter_input(INPUT_POST, 'profesor_id', FILTER_VALIDATE_INT);
            $nombre = trim((string)filter_input(INPUT_POST, 'nombre', FILTER_UNSAFE_RAW));
            $ciclo = trim((string)filter_input(INPUT_POST, 'ciclo', FILTER_UNSAFE_RAW));
            $cupo = filter_input(INPUT_POST, 'cupo', FILTER_VALIDATE_INT);

            if (!$materia_id || !$profesor_id || $nombre === '' || !$cupo || $cupo < 1) {
                $error = 'Todos los campos son obligatorios y el cupo debe ser mayor a 0';
            } else {
                try {
                    if ($action === 'update') {
                        $id = (int)($_POST['id'] ?? 0);
                        if ($id <= 0) {
                            $error = 'ID inválido';
                        } else {
                            require_once __DIR__ . '/../app/models/Grupo.php';
                            $gm = new \Grupo();
                            $gm->update($id, [
                                'materia_id' => $materia_id,
                                'profesor_id' => $profesor_id,
                                'nombre' => $nombre,
                                'ciclo' => $ciclo ?: null,
                                'cupo' => $cupo
                            ]);
                            $msg = 'Grupo actualizado correctamente';
                        }
                    } else {
                        require_once __DIR__ . '/../app/models/Grupo.php';
                        $gm = new \Grupo();
                        $gm->create([
                            'materia_id' => $materia_id,
                            'profesor_id' => $profesor_id,
                            'nombre' => $nombre,
                            'ciclo' => $ciclo ?: null,
                            'cupo' => $cupo
                        ]);
                        $msg = 'Grupo creado correctamente';
                    }
                } catch (Throwable $e) {
                    $error = 'Error al guardar el grupo';
                }
            }
        }
    }
}

 $page = max(1, (int)($_GET['page'] ?? 1));
 $limit = 10;
 $profesorId = null;
 if (!$isAdmin) {
     $profesorId = $_SESSION['user_id'] ?? null;
 }
 $grupos = $controlGrupos->obtenerConJoins($page, $limit, $profesorId);
 // countWithFilter no está expuesto; usamos el modelo directo como fallback
 require_once __DIR__ . '/../app/models/Grupo.php';
 $gmodel = new \Grupo();
 $total = $gmodel->countWithFilter($profesorId);
 $totalPages = max(1, (int)ceil($total / $limit));
 $csrf_token = $controlAut->generarTokenCSRF();
// Editar si viene edit_id
 $edit_id = (int)($_GET['edit_id'] ?? 0);
 if ($isAdmin && $edit_id > 0) {
     // usar adaptador si tuviera find, de lo contrario fallback al modelo
     require_once __DIR__ . '/../app/models/Grupo.php';
     $gm = new \Grupo();
     $editGrupo = $gm->find($edit_id) ?: null;
 }

// Datos para selects
$materias = $controlMaterias->listar(1, 100);
$profesores = $controlUsuarios->listar(1, 100, "WHERE rol = 'profesor' AND activo = 1");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
  <title>SICEnet · ITSUR — Grupos</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
  <link href="assets/css/styles.css" rel="stylesheet">
  <link href="assets/css/desktop-fixes.css" rel="stylesheet">
</head>
<body>
<?php require __DIR__ . '/partials/header.php'; ?>

<div class="container mt-4">
    <div class="row">
        <div class="col-12 mb-3">
          <div class="d-flex justify-content-between align-items-center">
            <div>
              <?php $pageTitle = 'Grupos'; ?>
              <h1 class="h3 mb-0"><?= $pageTitle ?></h1>
              <?php $breadcrumbs = [
                ['label' => 'Inicio', 'url' => 'dashboard.php'],
                ['label' => 'Gestión Académica', 'url' => null],
                ['label' => $pageTitle, 'url' => null],
              ]; ?>
              <?php require __DIR__ . '/partials/breadcrumb.php'; ?>
            </div>
            <div class="text-muted small">Mostrando <?= count($grupos) ?> de <?= (int)$total ?></div>
          </div>
        </div>
        <?php if ($isAdmin): ?>
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-grid-3x3"></i> Alta de Grupo</h5>
                    <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
                    <?php if ($error): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>
                    <form method="POST" class="needs-validation" novalidate>
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="action" value="<?= $editGrupo ? 'update' : 'create' ?>">
                        <?php if ($editGrupo): ?>
                        <input type="hidden" name="id" value="<?= (int)$editGrupo['id'] ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="materia_id" class="form-label">Materia</label>
                            <select class="form-select" id="materia_id" name="materia_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($materias as $m): ?>
                                <option value="<?= (int)$m['id'] ?>" <?= ($editGrupo && (int)$editGrupo['materia_id'] === (int)$m['id']) ? 'selected' : '' ?>><?= htmlspecialchars($m['nombre']) ?> (<?= htmlspecialchars($m['clave'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleccione una materia.</div>
                        </div>
                        <div class="mb-3">
                            <label for="profesor_id" class="form-label">Profesor</label>
                            <select class="form-select" id="profesor_id" name="profesor_id" required>
                                <option value="">Seleccione...</option>
                                <?php foreach ($profesores as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ($editGrupo && (int)$editGrupo['profesor_id'] === (int)$p['id']) ? 'selected' : '' ?>><?= htmlspecialchars($p['email']) ?> (<?= htmlspecialchars($p['matricula'] ?? '') ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback">Seleccione un profesor.</div>
                        </div>
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre del Grupo</label>
                            <input type="text" class="form-control" id="nombre" name="nombre" placeholder="Ej: GPO-101-A" value="<?= htmlspecialchars($editGrupo['nombre'] ?? '') ?>" required>
                            <div class="invalid-feedback">El nombre del grupo es obligatorio.</div>
                        </div>
                        <div class="mb-3">
                            <label for="ciclo" class="form-label">Ciclo</label>
                            <input type="text" class="form-control" id="ciclo" name="ciclo" placeholder="Ej: 2024A" value="<?= htmlspecialchars($editGrupo['ciclo'] ?? '') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="cupo" class="form-label">Cupo Máximo</label>
                            <input type="number" class="form-control" id="cupo" name="cupo" placeholder="30" min="1" max="100" value="<?= (int)($editGrupo['cupo'] ?? 30) ?>" required>
                            <div class="form-text">Número máximo de estudiantes que pueden inscribirse en este grupo.</div>
                            <div class="invalid-feedback">El cupo debe ser un número entre 1 y 100.</div>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= $editGrupo ? 'Actualizar' : 'Crear' ?></button>
                        <?php if ($editGrupo): ?>
                        <a href="grupos.php" class="btn btn-outline-secondary">Cancelar</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><i class="bi bi-list"></i> Grupos</h5>
                    <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
                      <div class="flex-grow-1" style="max-width: 320px;">
                        <input type="text" class="form-control" placeholder="Filtrar rápido en la tabla" data-quick-filter-for="#tabla-grupos">
                      </div>
                      <div>
                        <?php if ($isAdmin): ?>
                         <button class="btn btn-outline-primary btn-sm" data-export="csv" data-target="#tabla-grupos" data-filename="grupos.csv" data-timestamp="true"><i class="bi bi-filetype-csv"></i> Exportar CSV</button>
                         <button class="btn btn-outline-secondary btn-sm" data-export="pdf" data-target="#tabla-grupos"><i class="bi bi-filetype-pdf"></i> Exportar PDF</button>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="table-responsive">
                      <table id="tabla-grupos" class="table table-striped table-hover table-sort">
                          <thead><tr><th class="d-none d-sm-table-cell">ID</th><th>Grupo</th><th class="d-none d-md-table-cell">Ciclo</th><th>Materia</th><th class="d-none d-lg-table-cell">Profesor</th><th class="d-none d-md-table-cell">Cupo</th><?php if ($isAdmin): ?><th>Acciones</th><?php endif; ?></tr></thead>
                          <tbody>
                          <?php if (count($grupos) === 0): ?>
                            <tr class="empty-state-row"><td colspan="<?= $isAdmin ? 7 : 6 ?>" class="text-center text-muted">No hay grupos registrados</td></tr>
                          <?php endif; ?>
                          <?php foreach ($grupos as $g): ?>
                              <tr>
                                  <td class="d-none d-sm-table-cell"><?= (int)$g['id'] ?></td>
                                  <td><?= htmlspecialchars($g['nombre']) ?></td>
                                  <td class="d-none d-md-table-cell"><?= htmlspecialchars($g['ciclo'] ?? '') ?></td>
                                  <td><?= htmlspecialchars($g['materia_nombre']) ?> (<?= htmlspecialchars($g['materia_clave'] ?? '') ?>)</td>
                                  <td class="d-none d-lg-table-cell"><?= htmlspecialchars($g['profesor_email']) ?> (<?= htmlspecialchars($g['profesor_matricula'] ?? '') ?>)</td>
                                  <td class="d-none d-md-table-cell"><?= (int)($g['cupo'] ?? 30) ?></td>
                                  <?php if ($isAdmin): ?>
                                  <td>
                                      <div class="d-flex flex-column flex-sm-row gap-1">
                                        <a href="?edit_id=<?= (int)$g['id'] ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este grupo?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
            								<input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                                        </form>
                                      </div>
                                  </td>
                                  <?php endif; ?>
                              </tr>
                          <?php endforeach; ?>
                          </tbody>
                      </table>
                    </div>
                    <nav>
                        <ul class="pagination">
                            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                                <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $p ?>"><?= $p ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
// Validación de Bootstrap
(function(){
    'use strict';
    const forms = document.querySelectorAll('.needs-validation');
    Array.from(forms).forEach(form => {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });
})();
</script>
</body>
</html>
