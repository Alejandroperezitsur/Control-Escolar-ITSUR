<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo = Database::getInstance()->getConnection();
    
    echo "Alumnos: " . $pdo->query("SELECT count(*) FROM alumnos")->fetchColumn() . "\n";
    echo "Profesores: " . $pdo->query("SELECT count(*) FROM usuarios WHERE rol='profesor'")->fetchColumn() . "\n";
    echo "Materias: " . $pdo->query("SELECT count(*) FROM materias")->fetchColumn() . "\n";
    echo "Grupos: " . $pdo->query("SELECT count(*) FROM grupos")->fetchColumn() . "\n";
    echo "Inscripciones: " . $pdo->query("SELECT count(*) FROM inscripciones")->fetchColumn() . "\n";
    echo "Horarios: " . $pdo->query("SELECT count(*) FROM horarios")->fetchColumn() . "\n";
    
    // Check for nulls/orphans
    $orphans = $pdo->query("SELECT count(*) FROM alumnos WHERE carrera_id IS NULL")->fetchColumn();
    echo "Alumnos sin carrera: $orphans\n";
    
    $no_load = $pdo->query("SELECT count(*) FROM alumnos a LEFT JOIN inscripciones i ON a.id = i.alumno_id WHERE i.id IS NULL")->fetchColumn();
    echo "Alumnos sin carga: $no_load\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
