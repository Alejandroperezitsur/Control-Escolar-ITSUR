<?php
// Entry point moderno con router y vistas. Compatible con XAMPP.

// Autoload Composer si existe
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require __DIR__ . '/../vendor/autoload.php';
} else {
    // Autoloader PSR-4 simple para "App\\" → "src/"
    spl_autoload_register(function ($class) {
        $prefix = 'App\\';
        $baseDir = __DIR__ . '/../src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) { return; }
        $relativeClass = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
        if (file_exists($file)) { require $file; }
    });
}

// Reutiliza la conexión existente
require_once __DIR__ . '/../config/db.php';

use App\Kernel;
use App\Http\Router;
use App\Http\Middleware\AuthMiddleware;
use App\Http\Middleware\RateLimitMiddleware;
use App\Http\Middleware\PermissionMiddleware;
use App\Utils\Logger;
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ReportsController;
use App\Controllers\GradesController;
use App\Controllers\StudentsController;
use App\Controllers\HealthController;
use App\Controllers\Api\KpiController;
use App\Controllers\Api\StudentController;
use App\Controllers\Api\ProfessorController;
use App\Controllers\ChartsController;
use App\Controllers\CatalogsController;
use App\Controllers\ProfessorsController;
use App\Controllers\AdminSettingsController;
use App\Controllers\CareersController;
use App\Controllers\CiclosController;

Kernel::boot();

try {
    $pdo = \Database::getInstance()->getConnection();
} catch (\PDOException $e) {
    Logger::info('db_down', ['error' => $e->getMessage()]);
    http_response_code(503);
    header('Content-Type: text/html; charset=UTF-8');
    $scriptDir = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
    $base = $scriptDir;
    $p = strpos($scriptDir, '/public');
    if ($p !== false) { $base = substr($scriptDir, 0, $p + 7); }
    ?>
    <!doctype html>
    <html lang="es">
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Sistema en mantenimiento</title>
      <link href="<?php echo $base; ?>/assets/vendor/bootstrap.min.css" rel="stylesheet">
      <link href="<?php echo $base; ?>/assets/css/styles.css" rel="stylesheet">
    </head>
    <body class="bg-dark text-light d-flex align-items-center" style="min-height:100vh;">
      <div class="container text-center">
        <h1 class="mb-3">Sistema en mantenimiento</h1>
        <p class="mb-4">El sistema de control escolar no está disponible temporalmente. Intenta de nuevo más tarde.</p>
      </div>
    </body>
    </html>
    <?php
    exit;
}
PermissionMiddleware::boot($pdo);

$router = new Router();

// Dependencias
$userRepo = new UserRepository($pdo);
$userService = new UserService($userRepo);
$auth = new AuthController($userService);
$dashboard = new DashboardController();
$reports = new ReportsController($pdo);
$grades = new GradesController($pdo);
$students = new StudentsController($pdo);
$health = new HealthController($pdo);
$kpi = new KpiController($pdo);
$studentApi = new StudentController($pdo);
$professorApi = new ProfessorController($pdo);
$charts = new ChartsController($pdo);
$catalogs = new CatalogsController($pdo);
$professorsCtl = new ProfessorsController($pdo);
$adminSettings = new AdminSettingsController();
$careers = new CareersController($pdo);
$ciclos = new CiclosController($pdo);

// Rutas públicas
$router->get('/login', fn() => $auth->showLogin());
$router->post('/login', fn() => $auth->login(), [RateLimitMiddleware::limit('login', 20, 600)]);
$router->get('/logout', fn() => $auth->logout());
$router->get('/health', fn() => $health->index());

// Rutas autenticadas
$router->get('/', fn() => $dashboard->index(), [AuthMiddleware::requireAuth()]);
$router->get('/dashboard', fn() => $dashboard->index(), [AuthMiddleware::requireAuth()]);

// Admin
$router->get('/reports', fn() => $reports->index(), [PermissionMiddleware::requirePermission('view_reports')]);
$router->post('/reports/export/csv', fn() => $reports->exportCsv(), [PermissionMiddleware::requirePermission('export_reports')]);
$router->post('/reports/export/pdf', fn() => $reports->exportPdf(), [PermissionMiddleware::requirePermission('export_reports')]);
$router->post('/reports/export/zip', fn() => $reports->exportZip(), [PermissionMiddleware::requirePermission('export_reports')]);
$router->post('/reports/export/xlsx', fn() => $reports->exportXlsx(), [PermissionMiddleware::requirePermission('export_reports')]);
// Soporte GET para exportaciones (requiere CSRF vía query)
$router->get('/reports/export/csv', fn() => $reports->exportCsv(), [PermissionMiddleware::requirePermission('export_reports')]);
$router->get('/reports/export/pdf', fn() => $reports->exportPdf(), [PermissionMiddleware::requirePermission('export_reports')]);
$router->get('/reports/summary', fn() => $reports->summary(), [PermissionMiddleware::requirePermission('view_reports')]);
$router->get('/reports/tops', fn() => $reports->tops(), [PermissionMiddleware::requirePermission('view_reports')]);
$router->get('/api/kpis/admin', fn() => $kpi->admin(), [PermissionMiddleware::requirePermission('view_dashboard')]);
$router->get('/api/charts/promedios-materias', fn() => $charts->averagesBySubject(), [PermissionMiddleware::requirePermission('view_reports')]);
$router->get('/api/charts/promedios-ciclo', fn() => $charts->averagesByCycle(), [PermissionMiddleware::requirePermission('view_reports')]);
$router->get('/api/charts/desempeño-grupo', fn() => $charts->performanceByProfessorGroups(), [PermissionMiddleware::requirePermission('view_professor_kpis')]);
$router->get('/api/charts/reprobados', fn() => $charts->failRateBySubject(), [PermissionMiddleware::requirePermission('view_reports')]);

$router->get('/grades/group/export/csv', fn() => $grades->exportGroupCsv(), [PermissionMiddleware::requirePermission('edit_grades')]);
$router->get('/grades/group/export/pendingcsv', fn() => $grades->exportGroupPendingCsv(), [PermissionMiddleware::requirePermission('edit_grades')]);
$router->get('/grades/group/export/xlsx', fn() => $grades->exportGroupXlsx(), [PermissionMiddleware::requirePermission('edit_grades')]);

// Admin: Ajustes de siembra
$router->get('/admin/settings', fn() => $adminSettings->index(), [PermissionMiddleware::requirePermission('admin_settings')]);
$router->post('/admin/settings/save', fn() => $adminSettings->save(), [PermissionMiddleware::requirePermission('admin_settings')]);

// Ciclos escolares
$router->get('/ciclos', fn() => $ciclos->index(), [PermissionMiddleware::requirePermission('manage_cycles')]);
$router->get('/ciclos/create', fn() => $ciclos->create(), [PermissionMiddleware::requirePermission('manage_cycles')]);
$router->post('/ciclos/store', fn() => $ciclos->store(), [PermissionMiddleware::requirePermission('manage_cycles')]);
$router->post('/ciclos/activar', fn() => $ciclos->activar(), [PermissionMiddleware::requirePermission('manage_cycles')]);
$router->post('/ciclos/cerrar', fn() => $ciclos->cerrar(), [PermissionMiddleware::requirePermission('manage_cycles')]);

// Gestión de profesores (migración de profesores.php)
$router->get('/professors', fn() => $professorsCtl->index(), [PermissionMiddleware::requirePermission('manage_professors')]);
$router->get('/professors/detail', fn() => $professorsCtl->show(), [PermissionMiddleware::requirePermission('manage_professors')]);
$router->post('/professors/create', fn() => $professorsCtl->create(), [PermissionMiddleware::requirePermission('manage_professors'), RateLimitMiddleware::limit('prof_create', 20, 600)]);
$router->post('/professors/delete', fn() => $professorsCtl->delete(), [PermissionMiddleware::requirePermission('manage_professors'), RateLimitMiddleware::limit('prof_delete', 20, 600)]);
// Actualizar profesores
$router->post('/professors/update', fn() => $professorsCtl->update(), [PermissionMiddleware::requirePermission('manage_professors'), RateLimitMiddleware::limit('prof_update', 20, 600)]);

// Carreras - Curriculum view
$router->get('/careers', fn() => $careers->index(), [PermissionMiddleware::requirePermission('manage_careers')]);
$router->get('/api/careers/count', fn() => $careers->getCareersCount(), [PermissionMiddleware::requirePermission('manage_careers')]);
$router->get('/api/careers/curriculum', fn() => $careers->getCurriculum(), [PermissionMiddleware::requirePermission('manage_careers')]);

// Catálogos (para selects dinámicos)
$router->get('/api/catalogs/subjects', fn() => $catalogs->subjects(), [PermissionMiddleware::requirePermission('manage_subjects')]);
$router->get('/api/catalogs/professors', fn() => $catalogs->professors(), [PermissionMiddleware::requirePermission('manage_professors')]);
$router->get('/api/catalogs/students', fn() => $catalogs->students(), [PermissionMiddleware::requirePermission('manage_students')]);
$router->get('/api/catalogs/groups', function () use ($catalogs) {
    $role = $_SESSION['role'] ?? '';
    // If profesor role, always use their own user_id
    if ($role === 'profesor') {
        $profId = (int)($_SESSION['user_id'] ?? 0);
    } else {
        // Admin can optionally filter by profesor parameter
        $profId = (int)($_GET['profesor'] ?? ($_SESSION['user_id'] ?? 0));
    }
    return $catalogs->groupsByProfessor($profId);
}, [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/catalogs/groups_all', fn() => $catalogs->groupsAll(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/catalogs/cycles', fn() => $catalogs->cycles(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/catalogs/group_students', fn() => $catalogs->groupStudents((int)($_GET['gid'] ?? 0)), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/catalogs/professor_students', function () use ($catalogs) {
    $role = $_SESSION['role'] ?? '';
    $pid = ($role === 'profesor') ? (int)($_SESSION['user_id'] ?? 0) : (int)($_GET['profesor'] ?? 0);
    return $catalogs->studentsByProfessor($pid);
}, [AuthMiddleware::requireAnyRole(['admin','profesor'])]);

// Profesor
$router->get('/grades', fn() => $grades->index(), [PermissionMiddleware::requirePermission('edit_grades')]);
$router->get('/grades/bulk', fn() => $grades->showBulkForm(), [PermissionMiddleware::requirePermission('bulk_upload_grades')]);
$router->post('/grades/bulk', fn() => $grades->processBulkUpload(), [PermissionMiddleware::requirePermission('bulk_upload_grades'), RateLimitMiddleware::limit('grades_bulk', 20, 600)]);
$router->get('/grades/bulk-log', fn() => $grades->downloadBulkLog(), [PermissionMiddleware::requirePermission('bulk_upload_grades')]);
$router->post('/grades/create', fn() => $grades->create(), [PermissionMiddleware::requirePermission('edit_grades'), RateLimitMiddleware::limit('grades_create', 30, 600)]);
$router->get('/grades/group', fn() => $grades->groupGrades(), [PermissionMiddleware::requirePermission('edit_grades')]);
$router->get('/api/grades/row', fn() => $grades->gradeRow(), [PermissionMiddleware::requirePermission('edit_grades')]);
$router->get('/api/kpis/profesor', fn() => $kpi->profesorDashboard((int)($_SESSION['user_id'] ?? 0)), [AuthMiddleware::requireRole('profesor')]);
$router->get('/api/profesor/perfil', fn() => $professorApi->perfil(), [AuthMiddleware::requireRole('profesor')]);

// Alumno - API del panel
$router->get('/api/alumno/carga', fn() => $studentApi->carga(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->get('/api/alumno/estadisticas', fn() => $studentApi->estadisticas(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->get('/api/alumno/chart', fn() => $studentApi->chart(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->get('/api/alumno/perfil', fn() => $studentApi->perfil(), [PermissionMiddleware::requirePermission('view_student_panel')]);

// Alumno - páginas dedicadas
$router->get('/alumno/calificaciones', fn() => (new App\Controllers\StudentsController($pdo))->myGrades(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->get('/alumno/calificaciones/export', fn() => (new App\Controllers\StudentsController($pdo))->exportMyGradesCsv(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->get('/api/alumno/calificaciones/resumen', fn() => (new App\Controllers\StudentsController($pdo))->myGradesSummary(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->get('/alumno/carga', fn() => (new App\Controllers\StudentsController($pdo))->myLoad(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->get('/alumno/pendientes', fn() => (new App\Controllers\StudentsController($pdo))->myPending(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->get('/api/alumno/materias', fn() => (new App\Controllers\StudentsController($pdo))->mySubjects(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->get('/alumno/reticula', fn() => (new App\Controllers\StudentsController($pdo))->myReticula(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->get('/alumno/reinscripcion', fn() => (new App\Controllers\StudentsController($pdo))->myReinscripcion(), [PermissionMiddleware::requirePermission('view_student_panel')]);
$router->post('/alumno/enroll', fn() => (new App\Controllers\StudentsController($pdo))->selfEnroll(), [PermissionMiddleware::requirePermission('self_enroll'), RateLimitMiddleware::limit('alumno_enroll', 20, 600)]);
$router->post('/alumno/unenroll', fn() => (new App\Controllers\StudentsController($pdo))->selfUnenroll(), [PermissionMiddleware::requirePermission('self_unenroll'), RateLimitMiddleware::limit('alumno_unenroll', 20, 600)]);

// Migración de alumnos.php → nueva ruta
$router->get('/alumnos', fn() => $students->index(), [PermissionMiddleware::requirePermission('manage_students')]);
$router->post('/alumnos/store', fn() => $students->store(), [PermissionMiddleware::requirePermission('manage_students')]);
    $router->post('/alumnos/update', fn() => $students->update(), [PermissionMiddleware::requirePermission('manage_students')]);
    $router->post('/alumnos/delete', fn() => $students->delete(), [PermissionMiddleware::requirePermission('manage_students')]);
    $router->get('/alumnos/get', fn() => $students->get(), [PermissionMiddleware::requirePermission('manage_students')]);
    $router->get('/alumnos/detalle', fn() => $students->show(), [PermissionMiddleware::requirePermission('manage_students')]);
    $router->post('/alumnos/enroll', fn() => $students->enroll(), [PermissionMiddleware::requirePermission('manage_students')]);
    $router->post('/alumnos/unenroll', fn() => $students->unenroll(), [PermissionMiddleware::requirePermission('manage_students')]);

// CRUD Subjects/Groups
$router->get('/subjects', fn() => (new App\Controllers\SubjectsController($pdo))->index(), [PermissionMiddleware::requirePermission('manage_subjects')]);
$router->get('/subjects/detail', fn() => (new App\Controllers\SubjectsController($pdo))->show(), [PermissionMiddleware::requirePermission('manage_subjects')]);
$router->get('/subjects/export/csv', fn() => (new App\Controllers\SubjectsController($pdo))->exportCsv(), [PermissionMiddleware::requirePermission('manage_subjects')]);
$router->get('/subjects/export/pdf', fn() => (new App\Controllers\SubjectsController($pdo))->exportPdf(), [PermissionMiddleware::requirePermission('manage_subjects')]);
$router->get('/subjects/export/xlsx', fn() => (new App\Controllers\SubjectsController($pdo))->exportXlsx(), [PermissionMiddleware::requirePermission('manage_subjects')]);
$router->post('/subjects/create', fn() => (new App\Controllers\SubjectsController($pdo))->create(), [PermissionMiddleware::requirePermission('manage_subjects'), RateLimitMiddleware::limit('subjects_create', 30, 600)]);
$router->post('/subjects/delete', fn() => (new App\Controllers\SubjectsController($pdo))->delete(), [PermissionMiddleware::requirePermission('manage_subjects'), RateLimitMiddleware::limit('subjects_delete', 30, 600)]);
// Actualizar materias
$router->post('/subjects/update', fn() => (new App\Controllers\SubjectsController($pdo))->update(), [PermissionMiddleware::requirePermission('manage_subjects'), RateLimitMiddleware::limit('subjects_update', 30, 600)]);
// Asociaciones materia <-> carrera
$router->post('/subjects/add_carrera', fn() => (new App\Controllers\SubjectsController($pdo))->addToCareer(), [PermissionMiddleware::requirePermission('manage_subjects'), RateLimitMiddleware::limit('subjects_add_carrera', 30, 600)]);
$router->post('/subjects/remove_carrera', fn() => (new App\Controllers\SubjectsController($pdo))->removeFromCareer(), [PermissionMiddleware::requirePermission('manage_subjects'), RateLimitMiddleware::limit('subjects_remove_carrera', 30, 600)]);

$router->get('/groups', fn() => (new App\Controllers\GroupsController($pdo))->index(), [PermissionMiddleware::requirePermission('manage_groups')]);
$router->post('/groups/create', fn() => (new App\Controllers\GroupsController($pdo))->create(), [PermissionMiddleware::requirePermission('manage_groups'), RateLimitMiddleware::limit('groups_create', 30, 600)]);
$router->post('/groups/update_professor', fn() => (new App\Controllers\GroupsController($pdo))->updateProfessor(), [PermissionMiddleware::requirePermission('manage_groups'), RateLimitMiddleware::limit('groups_update_professor', 30, 600)]);
$router->post('/groups/delete', fn() => (new App\Controllers\GroupsController($pdo))->delete(), [PermissionMiddleware::requirePermission('manage_groups'), RateLimitMiddleware::limit('groups_delete', 30, 600)]);
$router->get('/api/groups/schedules', fn() => (new App\Controllers\GroupsController($pdo))->schedules(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->post('/groups/schedules/add', fn() => (new App\Controllers\GroupsController($pdo))->addSchedule(), [PermissionMiddleware::requirePermission('manage_groups'), RateLimitMiddleware::limit('groups_schedule_add', 50, 600)]);
$router->post('/groups/schedules/delete', fn() => (new App\Controllers\GroupsController($pdo))->deleteSchedule(), [PermissionMiddleware::requirePermission('manage_groups'), RateLimitMiddleware::limit('groups_schedule_delete', 50, 600)]);

$router->get('/admin/seed/groups', fn() => (new App\Controllers\GroupsController($pdo))->seedDemo(), [PermissionMiddleware::requirePermission('manage_groups')]);

// Admin: pendientes de evaluación
$router->get('/admin/pendientes', fn() => (new App\Controllers\GradesController($pdo))->pending(), [PermissionMiddleware::requirePermission('edit_grades')]);

// Profesor: páginas dedicadas
$router->get('/profesor/grupos', fn() => (new App\Controllers\GroupsController($pdo))->mine(), [AuthMiddleware::requireRole('profesor')]);
$router->get('/profesor/alumnos', fn() => (new App\Controllers\StudentsController($pdo))->byProfessor(), [AuthMiddleware::requireRole('profesor')]);
$router->get('/profesor/pendientes', fn() => (new App\Controllers\GradesController($pdo))->pendingForProfessor(), [AuthMiddleware::requireRole('profesor')]);

// Despachar
$router->dispatch();
