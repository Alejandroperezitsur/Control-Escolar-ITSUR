<?php
namespace App\Http;

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
use App\Controllers\PlanesController;
use App\Controllers\AulasController;
use App\Controllers\SubjectsController;
use App\Controllers\GroupsController;

class Routes {
    public static function register(Router $router, \PDO $pdo) {
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
        $planes = new PlanesController($pdo);
        $aulas = new AulasController($pdo);

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

        $router->get('/grades/group/export/csv', fn() => $grades->exportGroupCsv(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
        $router->get('/grades/group/export/pendingcsv', fn() => $grades->exportGroupPendingCsv(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
        $router->get('/grades/group/export/xlsx', fn() => $grades->exportGroupXlsx(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);

        // Admin: Ajustes de siembra
        $router->get('/admin/settings', fn() => $adminSettings->index(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/admin/settings/save', fn() => $adminSettings->save(), [AuthMiddleware::requireRole('admin')]);

        // Gestión de profesores (migración de profesores.php)
        $router->get('/professors', fn() => $professorsCtl->index(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/professors/detail', fn() => $professorsCtl->show(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/professors/create', fn() => $professorsCtl->create(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('prof_create', 20, 600)]);
        $router->post('/professors/delete', fn() => $professorsCtl->delete(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('prof_delete', 20, 600)]);
        // Actualizar profesores
        $router->post('/professors/update', fn() => $professorsCtl->update(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('prof_update', 20, 600)]);

        // Carreras - Curriculum view
        $router->get('/careers', fn() => $careers->index(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/api/careers/count', fn() => $careers->getCareersCount(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/api/careers/curriculum', fn() => $careers->getCurriculum(), [AuthMiddleware::requireRole('admin')]);

        // Curriculum CRUD (admin only)
        $router->get('/api/careers/curriculum/available-subjects', fn() => $careers->getAvailableSubjects(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/api/careers/curriculum/add', fn() => $careers->addSubjectToCurriculum(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('curriculum_add', 20, 600)]);
        $router->post('/api/careers/curriculum/update', fn() => $careers->updateSubjectInCurriculum(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('curriculum_update', 20, 600)]);
        $router->post('/api/careers/curriculum/remove', fn() => $careers->removeSubjectFromCurriculum(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('curriculum_remove', 20, 600)]);

        // Planes de Estudio
        $router->get('/planes', fn() => $planes->index(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/planes/store', fn() => $planes->store(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/planes/update', fn() => $planes->update(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/planes/delete', fn() => $planes->delete(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/planes/get', fn() => $planes->get(), [AuthMiddleware::requireRole('admin')]);

        // Aulas
        $router->get('/aulas', fn() => $aulas->index(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/aulas/store', fn() => $aulas->store(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/aulas/update', fn() => $aulas->update(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/aulas/delete', fn() => $aulas->delete(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/aulas/get', fn() => $aulas->get(), [AuthMiddleware::requireRole('admin')]);

        // Catálogos (para selects dinámicos)
        $router->get('/api/catalogs/subjects', fn() => $catalogs->subjects(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
        $router->get('/api/catalogs/professors', fn() => $catalogs->professors(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/api/catalogs/students', fn() => $catalogs->students(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
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
        $router->get('/grades', fn() => $grades->index(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
        $router->get('/grades/bulk', fn() => $grades->showBulkForm(), [AuthMiddleware::requireRole('profesor')]);
        $router->post('/grades/bulk', fn() => $grades->processBulkUpload(), [AuthMiddleware::requireRole('profesor'), RateLimitMiddleware::limit('grades_bulk', 20, 600)]);
        $router->get('/grades/bulk-log', fn() => $grades->downloadBulkLog(), [AuthMiddleware::requireRole('profesor')]);
        $router->post('/grades/create', fn() => $grades->create(), [AuthMiddleware::requireAnyRole(['admin','profesor']), RateLimitMiddleware::limit('grades_create', 30, 600)]);
        $router->get('/grades/group', fn() => $grades->groupGrades(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
        $router->get('/api/grades/row', fn() => $grades->gradeRow(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
        $router->get('/api/kpis/profesor', fn() => $kpi->profesorDashboard((int)($_SESSION['user_id'] ?? 0)), [AuthMiddleware::requireRole('profesor')]);
        $router->get('/api/profesor/perfil', fn() => $professorApi->perfil(), [AuthMiddleware::requireRole('profesor')]);

        // Alumno - API del panel
        $router->get('/api/alumno/carga', fn() => $studentApi->carga(), [AuthMiddleware::requireRole('alumno')]);
        $router->get('/api/alumno/estadisticas', fn() => $studentApi->estadisticas(), [AuthMiddleware::requireRole('alumno')]);
        $router->get('/api/alumno/chart', fn() => $studentApi->chart(), [AuthMiddleware::requireRole('alumno')]);
        $router->get('/api/alumno/perfil', fn() => $studentApi->perfil(), [AuthMiddleware::requireRole('alumno')]);

        // Alumno - páginas dedicadas
        $router->get('/alumno/calificaciones', fn() => (new StudentsController($pdo))->myGrades(), [AuthMiddleware::requireRole('alumno')]);
        $router->get('/alumno/calificaciones/export', fn() => (new StudentsController($pdo))->exportMyGradesCsv(), [AuthMiddleware::requireRole('alumno')]);
        $router->get('/api/alumno/calificaciones/resumen', fn() => (new StudentsController($pdo))->myGradesSummary(), [AuthMiddleware::requireRole('alumno')]);
        $router->get('/alumno/carga', fn() => (new StudentsController($pdo))->myLoad(), [AuthMiddleware::requireRole('alumno')]);
        $router->get('/alumno/pendientes', fn() => (new StudentsController($pdo))->myPending(), [AuthMiddleware::requireRole('alumno')]);
        $router->get('/api/alumno/materias', fn() => (new StudentsController($pdo))->mySubjects(), [AuthMiddleware::requireRole('alumno')]);
        $router->get('/alumno/reticula', fn() => (new StudentsController($pdo))->myReticula(), [AuthMiddleware::requireRole('alumno')]);
        $router->get('/alumno/reinscripcion', fn() => (new StudentsController($pdo))->myReinscripcion(), [AuthMiddleware::requireRole('alumno')]);
        $router->post('/alumno/enroll', fn() => (new StudentsController($pdo))->selfEnroll(), [AuthMiddleware::requireRole('alumno'), RateLimitMiddleware::limit('alumno_enroll', 20, 600)]);
        $router->post('/alumno/unenroll', fn() => (new StudentsController($pdo))->selfUnenroll(), [AuthMiddleware::requireRole('alumno'), RateLimitMiddleware::limit('alumno_unenroll', 20, 600)]);
        $router->get('/alumno/horarios', fn() => (new StudentsController($pdo))->mySchedule(), [AuthMiddleware::requireRole('alumno')]);


        // Migración de alumnos.php → nueva ruta
        $router->get('/alumnos', fn() => $students->index(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/alumnos/store', fn() => $students->store(), [AuthMiddleware::requireRole('admin')]);
            $router->post('/alumnos/update', fn() => $students->update(), [AuthMiddleware::requireRole('admin')]);
            $router->post('/alumnos/delete', fn() => $students->delete(), [AuthMiddleware::requireRole('admin')]);
            $router->post('/alumnos/bulk-delete', fn() => $students->bulkDelete(), [AuthMiddleware::requireRole('admin')]);
            $router->get('/alumnos/get', fn() => $students->get(), [AuthMiddleware::requireRole('admin')]);
            $router->get('/alumnos/detalle', fn() => $students->show(), [AuthMiddleware::requireRole('admin')]);
            $router->post('/alumnos/enroll', fn() => $students->enroll(), [AuthMiddleware::requireRole('admin')]);
            $router->post('/alumnos/unenroll', fn() => $students->unenroll(), [AuthMiddleware::requireRole('admin')]);

        // Student Kardex
        $router->get('/alumnos/kardex', fn() => $students->kardex(), [AuthMiddleware::requireRole('admin')]);

        // CRUD Subjects/Groups
        $router->get('/subjects', fn() => (new SubjectsController($pdo))->index(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/subjects/detail', fn() => (new SubjectsController($pdo))->show(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/subjects/export/csv', fn() => (new SubjectsController($pdo))->exportCsv(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/subjects/export/pdf', fn() => (new SubjectsController($pdo))->exportPdf(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/subjects/export/xlsx', fn() => (new SubjectsController($pdo))->exportXlsx(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/subjects/create', fn() => (new SubjectsController($pdo))->create(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('subjects_create', 30, 600)]);
        $router->post('/subjects/delete', fn() => (new SubjectsController($pdo))->delete(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('subjects_delete', 30, 600)]);
        $router->post('/subjects/bulk-delete', fn() => (new SubjectsController($pdo))->bulkDelete(), [AuthMiddleware::requireRole('admin')]);
        // Actualizar materias
        $router->post('/subjects/update', fn() => (new SubjectsController($pdo))->update(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('subjects_update', 30, 600)]);
        // Asociaciones materia <-> carrera
        $router->post('/subjects/add_carrera', fn() => (new SubjectsController($pdo))->addToCareer(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('subjects_add_carrera', 30, 600)]);
        $router->post('/subjects/remove_carrera', fn() => (new SubjectsController($pdo))->removeFromCareer(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('subjects_remove_carrera', 30, 600)]);

        $router->get('/groups', fn() => (new GroupsController($pdo))->index(), [AuthMiddleware::requireRole('admin')]);
        $router->post('/groups/create', fn() => (new GroupsController($pdo))->create(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('groups_create', 30, 600)]);
        $router->post('/groups/update_professor', fn() => (new GroupsController($pdo))->updateProfessor(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('groups_update_professor', 30, 600)]);
        $router->post('/groups/delete', fn() => (new GroupsController($pdo))->delete(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('groups_delete', 30, 600)]);
        $router->post('/groups/bulk-delete', fn() => (new GroupsController($pdo))->bulkDelete(), [AuthMiddleware::requireRole('admin')]);
        $router->get('/api/groups/schedules', fn() => (new GroupsController($pdo))->schedules(), [AuthMiddleware::requireAnyRole(['admin','profesor'])]);
        $router->post('/groups/schedules/add', fn() => (new GroupsController($pdo))->addSchedule(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('groups_schedule_add', 50, 600)]);
        $router->post('/groups/schedules/delete', fn() => (new GroupsController($pdo))->deleteSchedule(), [AuthMiddleware::requireRole('admin'), RateLimitMiddleware::limit('groups_schedule_delete', 50, 600)]);

        $router->get('/admin/seed/groups', fn() => (new GroupsController($pdo))->seedDemo(), [AuthMiddleware::requireRole('admin')]);

        // Admin: pendientes de evaluación
        $router->get('/admin/pendientes', fn() => (new GradesController($pdo))->pending(), [AuthMiddleware::requireRole('admin')]);

        // Profesor: páginas dedicadas
        $router->get('/profesor/grupos', fn() => (new GroupsController($pdo))->mine(), [AuthMiddleware::requireRole('profesor')]);
        $router->get('/profesor/alumnos', fn() => (new StudentsController($pdo))->byProfessor(), [AuthMiddleware::requireRole('profesor')]);
        $router->get('/profesor/pendientes', fn() => (new GradesController($pdo))->pendingForProfessor(), [AuthMiddleware::requireRole('profesor')]);
    }
}
