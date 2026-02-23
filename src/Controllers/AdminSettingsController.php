<?php
namespace App\Controllers;

use App\Http\Request;

class AdminSettingsController
{
    public function index(): void
    {
        $cfg = @include __DIR__ . '/../../config/config.php';
        $csrf = $_SESSION['csrf_token'] ?? '';
        $minGroups = (int)($cfg['academic']['seed_min_groups_per_cycle'] ?? 2);
        $minGrades = (int)($cfg['academic']['seed_min_grades_per_group'] ?? 18);
        $pool = (int)($cfg['academic']['seed_students_pool'] ?? 40);
        ob_start();
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
        include __DIR__ . '/../Views/admin/settings.php';
        echo ob_get_clean();
    }

    public function save(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') { http_response_code(405); echo 'Método no permitido'; return; }
        $token = Request::postString('csrf_token', '');
        if ($token === null || $token === '' || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) { http_response_code(403); echo 'CSRF inválido'; return; }

        $minGroups = max(1, (int)(Request::postInt('seed_min_groups_per_cycle', 2) ?? 2));
        $minGrades = max(1, (int)(Request::postInt('seed_min_grades_per_group', 18) ?? 18));
        $pool = max(10, (int)(Request::postInt('seed_students_pool', 40) ?? 40));

        $cfg = @include __DIR__ . '/../../config/config.php';
        if (!is_array($cfg)) { $cfg = []; }
        $cfg['academic'] = $cfg['academic'] ?? [];
        $cfg['academic']['seed_min_groups_per_cycle'] = $minGroups;
        $cfg['academic']['seed_min_grades_per_group'] = $minGrades;
        $cfg['academic']['seed_students_pool'] = $pool;

        $code = '<?php' . "\n" . 'return ' . var_export($cfg, true) . ';' . "\n";
        $ok = @file_put_contents(__DIR__ . '/../../config/config.php', $code) !== false;
        $_SESSION['flash'] = $ok ? 'Ajustes guardados' : 'No se pudo guardar ajustes';
        $_SESSION['flash_type'] = $ok ? 'success' : 'danger';
        header('Location: /admin/settings');
    }
}

