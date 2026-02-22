<?php

$autoload = __DIR__ . '/../vendor/autoload.php';
if (is_file($autoload)) {
    require_once $autoload;
}

use App\Core\Env;

$dbHost = Env::get('DB_HOST', '127.0.0.1');
$dbPort = Env::get('DB_PORT', '3306');
$dbName = Env::get('DB_NAME', 'control_escolar');
$dbUser = Env::get('DB_USER', 'root');
$dbPass = Env::get('DB_PASS', '');

$dbHostWithPort = $dbHost;
if ($dbPort !== null && $dbPort !== '') {
    $dbHostWithPort = $dbHost . ':' . $dbPort;
}

$appUrl = Env::get('APP_URL', 'http://localhost/Control-Escolar-ITSUR');
$appDebugRaw = Env::get('APP_DEBUG', 'true');
$appDebug = filter_var($appDebugRaw, FILTER_VALIDATE_BOOLEAN);

$timezone = Env::get('APP_TIMEZONE', 'America/Mexico_City');
if ($timezone !== null && $timezone !== '') {
    date_default_timezone_set($timezone);
}

$appEnv = Env::get('APP_ENV', 'local');

return array(
  'db' =>
  array(
    'host' => $dbHostWithPort,
    'name' => $dbName,
    'user' => $dbUser,
    'pass' => $dbPass,
  ),
  'app' =>
  array(
    'name' => 'Control Escolar',
    'url' => $appUrl,
    'timezone' => date_default_timezone_get(),
    'charset' => 'UTF-8',
    'debug' => $appDebug,
    'env' => $appEnv,
  ),
  'academic' =>
  array(
    'reinscripcion_windows' =>
    array(
      'enero' =>
      array(
        'inicio_dia' => 10,
        'fin_dia' => 14,
        'mes' => 'Enero',
      ),
      'agosto' =>
      array(
        'inicio_dia' => 10,
        'fin_dia' => 14,
        'mes' => 'Agosto',
      ),
    ),
    'estatus_alumno_default' => 'Inscrito',
    'cupo_grupo_default' => 30,
    'seed_min_groups_per_cycle' => 3,
    'seed_min_grades_per_group' => 18,
    'seed_students_pool' => 40,
  ),
  'security' =>
  array(
    'session_timeout' => 3600,
    'csrf_token_name' => 'csrf_token',
    'upload_max_size' => 5242880,
    'allowed_extensions' =>
    array(
      0 => 'jpg',
      1 => 'jpeg',
      2 => 'png',
    ),
  ),
  'modules' =>
  array(
    'dashboard' => true,
    'alumnos' => true,
    'profesores' => true,
    'materias' => true,
    'grupos' => true,
    'calificaciones' => true,
    'kardex' => true,
    'mi_carga' => true,
    'reticula' => true,
    'reinscripcion' => true,
    'monitoreo_grupos' => true,
  ),
);
