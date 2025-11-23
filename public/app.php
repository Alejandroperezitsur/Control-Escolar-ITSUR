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
use App\Repositories\UserRepository;
use App\Services\UserService;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\ReportsController;
use App\Controllers\GradesController;
use App\Controllers\StudentsController;
use App\Controllers\Api\KpiController;
use App\Controllers\Api\StudentController;
use App\Controllers\Api\ProfessorController;
use App\Controllers\ChartsController;
use App\Controllers\CatalogsController;
use App\Controllers\ProfessorsController;
use App\Controllers\AdminSettingsController;
use App\Controllers\CareersController;

Kernel::boot();
$pdo = \Database::getInstance()->getConnection();

$router = new Router();

// Dependencias
$userRepo = new UserRepository($pdo);
$userService = new UserService($userRepo);
$auth = new AuthController($userService);
$dashboard = new DashboardController();
$reports = new ReportsController($pdo);
$grades = new GradesController($pdo);
$students = new StudentsController($pdo);
$kpi = new KpiController($pdo);
$studentApi = new StudentController($pdo);
$professorApi = new ProfessorController($pdo);
$charts = new ChartsController($pdo);
$catalogs = new CatalogsController($pdo);
$professorsCtl = new ProfessorsController($pdo);
$adminSettings = new AdminSettingsController();
$careers = new CareersController($pdo);

// Rutas públicas
$router->get('/login', fn() => $auth->showLogin());
$router->post('/login', fn() => $auth->login(), [RateLimitMiddleware::limit('login', 20, 600)]);
$router->get('/logout', fn() => $auth->logout());

// Rutas autenticadas
$router->get('/', fn() => $dashboard->index(), [AuthMiddleware::requireAuth()]);
$router->get('/dashboard', fn() => $dashboard->index(), [AuthMiddleware::requireAuth()]);

// Admin
$router->get('/reports', fn() => $reports->index(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->post('/reports/export/csv', fn() => $reports->exportCsv(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->post('/reports/export/pdf', fn() => $reports->exportPdf(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->post('/reports/export/zip', fn() => $reports->exportZip(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->post('/reports/export/xlsx', fn() => $reports->exportXlsx(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
// Soporte GET para exportaciones (requiere CSRF vía query)
$router->get('/reports/export/csv', fn() => $reports->exportCsv(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/reports/export/pdf', fn() => $reports->exportPdf(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/reports/summary', fn() => $reports->summary(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/reports/tops', fn() => $reports->tops(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/kpis/admin', fn() => $kpi->admin(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/charts/promedios-materias', fn() => $charts->averagesBySubject(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/charts/promedios-ciclo', fn() => $charts->averagesByCycle(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/charts/desempeño-grupo', fn() => $charts->performanceByProfessorGroups(), [AuthMiddleware::requireRole('profesor')]);
$router->get('/api/charts/reprobados', fn() => $charts->failRateBySubject(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);

// Admin: Ajustes de siembra
$router->get('/admin/settings', fn() => $adminSettings->index(), [AuthMiddleware::requireRole('admin')]);
$router->post('/admin/settings/save', fn() => $adminSettings->save(), [AuthMiddleware::requireRole('admin')]);

// Gestión de profesores (migración de profesores.php)
$router->get('/professors', fn() => $professorsCtl->index(), [AuthMiddleware::requireRole('admin')]);
$router->post('/professors/create', fn() => $professorsCtl->create(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('prof_create', 20, 600)]);
$router->post('/professors/delete', fn() => $professorsCtl->delete(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('prof_delete', 20, 600)]);
// Actualizar profesores
$router->post('/professors/update', fn() => $professorsCtl->update(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('prof_update', 20, 600)]);

// Carreras - Curriculum view
$router->get('/careers', fn() => $careers->index(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/careers/count', fn() => $careers->getCareersCount(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/careers/curriculum', fn() => $careers->getCurriculum(), [AuthMiddleware::requireRole('admin')]);

// Catálogos (para selects dinámicos)
$router->get('/api/catalogs/subjects', fn() => $catalogs->subjects(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/catalogs/professors', fn() => $catalogs->professors(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/catalogs/students', fn() => $catalogs->students(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/catalogs/groups', function () use ($catalogs) {
    $profId = (int)($_GET['profesor'] ?? ($_SESSION['user_id'] ?? 0));
    return $catalogs->groupsByProfessor($profId);
}, [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/catalogs/groups_all', fn() => $catalogs->groupsAll(), [AuthMiddleware::requireRole('admin')]);
$router->get('/api/catalogs/cycles', fn() => $catalogs->cycles(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);

// Profesor
$router->get('/grades', fn() => $grades->index(), [AuthMiddleware::requireRole('profesor')]);
$router->get('/grades/bulk', fn() => $grades->showBulkForm(), [AuthMiddleware::requireRole('profesor')]);
$router->post('/grades/bulk', fn() => $grades->processBulkUpload(), [AuthMiddleware::requireRole('profesor'), RateLimitMiddleware::limit('grades_bulk', 20, 600)]);
$router->get('/grades/bulk-log', fn() => $grades->downloadBulkLog(), [AuthMiddleware::requireRole('profesor')]);
$router->post('/grades/create', fn() => $grades->create(), [AuthMiddleware::requireRole('profesor'), RateLimitMiddleware::limit('grades_create', 30, 600)]);
$router->get('/grades/group', fn() => $grades->groupGrades(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
$router->get('/api/kpis/profesor', fn() => $kpi->profesorDashboard((int)($_SESSION['user_id'] ?? 0)), [AuthMiddleware::requireRole('profesor')]);
$router->get('/api/profesor/perfil', fn() => $professorApi->perfil(), [AuthMiddleware::requireRole('profesor')]);

// Alumno - API del panel
$router->get('/api/alumno/carga', fn() => $studentApi->carga(), [AuthMiddleware::requireRole('alumno')]);
$router->get('/api/alumno/estadisticas', fn() => $studentApi->estadisticas(), [AuthMiddleware::requireRole('alumno')]);
$router->get('/api/alumno/chart', fn() => $studentApi->chart(), [AuthMiddleware::requireRole('alumno')]);
$router->get('/api/alumno/perfil', fn() => $studentApi->perfil(), [AuthMiddleware::requireRole('alumno')]);

// Alumno - páginas dedicadas
$router->get('/alumno/calificaciones', fn() => (new App\Controllers\StudentsController($pdo))->myGrades(), [AuthMiddleware::requireRole('alumno')]);
$router->get('/alumno/calificaciones/export', fn() => (new App\Controllers\StudentsController($pdo))->exportMyGradesCsv(), [AuthMiddleware::requireRole('alumno')]);
$router->get('/api/alumno/calificaciones/resumen', fn() => (new App\Controllers\StudentsController($pdo))->myGradesSummary(), [AuthMiddleware::requireRole('alumno')]);
$router->get('/alumno/carga', fn() => (new App\Controllers\StudentsController($pdo))->myLoad(), [AuthMiddleware::requireRole('alumno')]);
$router->get('/alumno/pendientes', fn() => (new App\Controllers\StudentsController($pdo))->myPending(), [AuthMiddleware::requireRole('alumno')]);
$router->get('/api/alumno/materias', fn() => (new App\Controllers\StudentsController($pdo))->mySubjects(), [AuthMiddleware::requireRole('alumno')]);

// Migración de alumnos.php → nueva ruta
$router->get('/alumnos', fn() => $students->index(), [AuthMiddleware::requireRole('admin')]);
$router->post('/alumnos/store', fn() => $students->store(), [AuthMiddleware::requireRole('admin')]);
    $router->post('/alumnos/update', fn() => $students->update(), [AuthMiddleware::requireRole('admin')]);
    $router->post('/alumnos/delete', fn() => $students->delete(), [AuthMiddleware::requireRole('admin')]);
    $router->get('/alumnos/get', fn() => $students->get(), [AuthMiddleware::requireRole('admin')]);

// CRUD Subjects/Groups
$router->get('/subjects', fn() => (new App\Controllers\SubjectsController($pdo))->index(), [AuthMiddleware::requireRole('admin')]);
$router->post('/subjects/create', fn() => (new App\Controllers\SubjectsController($pdo))->create(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('subjects_create', 30, 600)]);
$router->post('/subjects/delete', fn() => (new App\Controllers\SubjectsController($pdo))->delete(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('subjects_delete', 30, 600)]);
// Actualizar materias
$router->post('/subjects/update', fn() => (new App\Controllers\SubjectsController($pdo))->update(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('subjects_update', 30, 600)]);

$router->get('/groups', fn() => (new App\Controllers\GroupsController($pdo))->index(), [AuthMiddleware::requireRole('admin')]);
$router->post('/groups/create', fn() => (new App\Controllers\GroupsController($pdo))->create(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('groups_create', 30, 600)]);
$router->post('/groups/delete', fn() => (new App\Controllers\GroupsController($pdo))->delete(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('groups_delete', 30, 600)]);

$router->get('/admin/seed/groups', fn() => (new App\Controllers\GroupsController($pdo))->seedDemo(), [AuthMiddleware::requireRole('admin')]);

// Admin: pendientes de evaluación
$router->get('/admin/pendientes', fn() => (new App\Controllers\GradesController($pdo))->pending(), [AuthMiddleware::requireRole('admin')]);

// Profesor: páginas dedicadas
$router->get('/profesor/grupos', fn() => (new App\Controllers\GroupsController($pdo))->mine(), [AuthMiddleware::requireRole('profesor')]);
$router->get('/profesor/alumnos', fn() => (new App\Controllers\StudentsController($pdo))->byProfessor(), [AuthMiddleware::requireRole('profesor')]);
$router->get('/profesor/pendientes', fn() => (new App\Controllers\GradesController($pdo))->pendingForProfessor(), [AuthMiddleware::requireRole('profesor')]);

// Despachar
$router->dispatch();
