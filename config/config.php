<?php
return array (
  'db' => 
  array (
    'host' => 'sql212.infinityfree.com',
    'name' => 'if0_40512739_control_escolar',
    'user' => 'if0_40512739',
    'pass' => 'APcZEb123',
    'port' => '3306',
  ),
  'app' => 
  array (
    'name' => 'Control Escolar',
    'url' => 'http://localhost/PWBII/Control-Escolar-ITSUR',
    'timezone' => 'America/Mexico_City',
    'charset' => 'UTF-8',
    'debug' => true,
  ),
  'academic' => 
  array (
    'reinscripcion_windows' => 
    array (
      'enero' => 
      array (
        'inicio_dia' => 10,
        'fin_dia' => 14,
        'mes' => 'Enero',
      ),
      'agosto' => 
      array (
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
  array (
    'session_timeout' => 3600,
    'csrf_token_name' => 'csrf_token',
    'upload_max_size' => 5242880,
    'allowed_extensions' => 
    array (
      0 => 'jpg',
      1 => 'jpeg',
      2 => 'png',
    ),
  ),
  'modules' => 
  array (
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
