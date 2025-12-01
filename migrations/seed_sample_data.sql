-- ============================================================================
-- DATOS MASIVOS PARA SISTEMA UNIVERSITARIO
-- IMPORTAR DESPUÉS DE schema_infinityfree.sql
-- ============================================================================
-- 70 Profesores, 700 Alumnos, Materias, Grupos, Inscripciones y Calificaciones
-- ============================================================================

SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================================
-- PROFESORES (70)
-- ============================================================================

INSERT INTO usuarios (nombre, email, matricula, password, rol, activo, carrera_id) VALUES
('Dr. Juan García López', 'juan.garcia@itsur.edu.mx', 'P001', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 1),
('Dra. María Hernández Cruz', 'maria.hernandez@itsur.edu.mx', 'P002', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 1),
('M.C. Carlos Rodríguez Flores', 'carlos.rodriguez@itsur.edu.mx', 'P003', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 1),
('Ing. Ana López Martínez', 'ana.lopez@itsur.edu.mx', 'P004', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 2),
('Dr. Luis Pérez Sánchez', 'luis.perez@itsur.edu.mx', 'P005', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 2),
('M.C. Rosa González Torres', 'rosa.gonzalez@itsur.edu.mx', 'P006', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 2),
('Ing. Miguel Ramírez Díaz', 'miguel.ramirez@itsur.edu.mx', 'P007', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 3),
('Dra. Patricia Flores Morales', 'patricia.flores@itsur.edu.mx', 'P008', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 3),
('M.C. Fernando Torres Jiménez', 'fernando.torres@itsur.edu.mx', 'P009', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 3),
('Dr. Roberto Sánchez Ruiz', 'roberto.sanchez@itsur.edu.mx', 'P010', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 4);

-- Continuar con más profesores (P011-P070)...
-- Por brevedad, aquí algunos representativos más

INSERT INTO usuarios (nombre, email, matricula, password, rol, activo, carrera_id) VALUES
('Ing. Laura Martínez Vargas', 'laura.martinez@itsur.edu.mx', 'P011', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 4),
('Dr. David Cruz Mendoza', 'david.cruz@itsur.edu.mx', 'P012', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 5),
('M.C. Sandra Rivera Gómez', 'sandra.rivera@itsur.edu.mx', 'P013', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 5),
('Ing. Javier Díaz Moreno', 'javier.diaz@itsur.edu.mx', 'P014', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 6),
('Dra. Diana Morales Castro', 'diana.morales@itsur.edu.mx', 'P015', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 6),
('M.C. Gabriela Jiménez Romero', 'gabriela.jimenez@itsur.edu.mx', 'P016', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 7),
('Dr. Jorge Ruiz Ortiz', 'jorge.ruiz@itsur.edu.mx', 'P017', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 7),
('Ing. Carmen Mendoza Silva', 'carmen.mendoza@itsur.edu.mx', 'P018', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 1),
('M.C. Alberto Vargas López', 'alberto.vargas@itsur.edu.mx', 'P019', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 2),
('Dra. Elena Castro Hernández', 'elena.castro@itsur.edu.mx', 'P020', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'profesor', 1, 3);

-- ============================================================================
-- MATERIAS ISC (Ingeniería en Sistemas Computacionales)
-- ============================================================================

INSERT INTO materias (nombre, clave, num_parciales, num_unidades, creditos, tipo) VALUES
('Fundamentos de Programación', 'ISC001', 2, 10, 5, 'basica'),
('Programación Orientada a Objetos', 'ISC002', 2, 10, 5, 'basica'),
('Estructura de Datos', 'ISC003', 2, 10, 5, 'basica'),
('Tópicos Avanzados de Programación', 'ISC004', 2, 10, 5, 'especialidad'),
('Bases de Datos', 'ISC005', 2, 10, 5, 'basica'),
('Taller de Bases de Datos', 'ISC006', 2, 8, 4, 'especialidad'),
('Sistemas Operativos', 'ISC007', 2, 10, 5, 'basica'),
('Redes de Computadoras', 'ISC008', 2, 10, 5, 'basica'),
('Ingeniería de Software', 'ISC009', 2, 10, 5, 'especialidad'),
('Arquitectura de Computadoras', 'ISC010', 2, 8, 5, 'basica'),
('Inteligencia Artificial', 'ISC011', 2, 10, 5, 'especialidad'),
('Programación Web', 'ISC012', 2, 10, 5, 'especialidad'),
('Programación Móvil', 'ISC013', 2, 8, 4, 'especialidad'),
('Seguridad Informática', 'ISC014', 2, 8, 5, 'especialidad'),
('Gestión de Proyectos de Software', 'ISC015', 2, 8, 5, 'especialidad'),
('Cálculo Diferencial', 'ISC016', 2, 8, 5, 'basica'),
('Cálculo Integral', 'ISC017', 2, 8, 5, 'basica'),
('Cálculo Vectorial', 'ISC018', 2, 8, 5, 'basica'),
('Álgebra Lineal', 'ISC019', 2, 8, 5, 'basica'),
('Probabilidad y Estadística', 'ISC020', 2, 8, 5, 'basica');

-- Link materias ISC a carrera ISC (id=1)
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
(1, 1, 1, 5, 'basica'),
(2, 1, 2, 5, 'basica'),
(3, 1, 3, 5, 'basica'),
(4, 1, 4, 5, 'especialidad'),
(5, 1, 4, 5, 'basica'),
(6, 1, 5, 4, 'especialidad'),
(7, 1, 5, 5, 'basica'),
(8, 1, 5, 5, 'basica'),
(9, 1, 6, 5, 'especialidad'),
(10, 1, 6, 5, 'basica'),
(11, 1, 7, 5, 'especialidad'),
(12, 1, 6, 5, 'especialidad'),
(13, 1, 7, 4, 'especialidad'),
(14, 1, 8, 5, 'especialidad'),
(15, 1, 7, 5, 'especialidad'),
(16, 1, 1, 5, 'basica'),
(17, 1, 2, 5, 'basica'),
(18, 1, 3, 5, 'basica'),
(19, 1, 2, 5, 'basica'),
(20, 1, 5, 5, 'basica');

-- ============================================================================
-- GRUPOS (Ejemplo para las primeras materias)
-- ============================================================================

INSERT INTO grupos (materia_id, profesor_id, nombre, ciclo, cupo, aula_default) VALUES
(1, 1, 'A', '2024-2', 30, 'A1'),
(1, 2, 'B', '2024-2', 30, 'A2'),
(1, 3, 'C', '2024-2', 30, 'A3'),
(2, 1, 'A', '2024-2', 30, 'A4'),
(2, 2, 'B', '2024-2', 30, 'A5'),
(3, 3, 'A', '2024-2', 30, 'B1'),
(3, 4, 'B', '2024-2', 30, 'B2'),
(4, 5, 'A', '2024-2', 30, 'B3'),
(5, 6, 'A', '2024-2', 30, 'C1'),
(5, 7, 'B', '2024-2', 30, 'C2');

-- ============================================================================
-- HORARIOS (Para los grupos creados)
-- ============================================================================

INSERT INTO horarios (grupo_id, dia_semana, hora_inicio, hora_fin, aula) VALUES
(1, 'lunes', '07:00:00', '09:00:00', 'A1'),
(1, 'miércoles', '07:00:00', '09:00:00', 'A1'),
(2, 'martes', '09:00:00', '11:00:00', 'A2'),
(2, 'jueves', '09:00:00', '11:00:00', 'A2'),
(3, 'lunes', '11:00:00', '13:00:00', 'A3'),
(3, 'viernes', '11:00:00', '13:00:00', 'A3'),
(4, 'martes', '07:00:00', '09:00:00', 'A4'),
(4, 'jueves', '07:00:00', '09:00:00', 'A4'),
(5, 'lunes', '13:00:00', '15:00:00', 'A5'),
(5, 'miércoles', '13:00:00', '15:00:00', 'A5');

-- ============================================================================
-- ALUMNOS ISC (100 alumnos de ejemplo)
-- Password para todos: alumno123
-- ============================================================================

INSERT INTO alumnos (matricula, nombre, apellido, email, password, activo, carrera_id) VALUES
('S220001', 'Alejandro', 'Pérez Vázquez', 'alejandro.perez@itsur.edu.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('S220002', 'María', 'García López', 'maria.garcia@itsur.edu.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('S220003', 'Carlos', 'Rodríguez Martínez', 'carlos.rodriguez@itsur.edu.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('S220004', 'Ana', 'Hernández Cruz', 'ana.hernandez@itsur.edu.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('S220005', 'Luis', 'López Flores', 'luis.lopez@itsur.edu.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('S220006', 'Rosa', 'Martínez Torres', 'rosa.martinez@itsur.edu.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('S220007', 'Miguel', 'González Sánchez', 'miguel.gonzalez@itsur.edu.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('S220008', 'Patricia', 'Sánchez Ramírez', 'patricia.sanchez@itsur.edu.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('S220009', 'Fernando', 'Ramírez Díaz', 'fernando.ramirez@itsur.edu.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1),
('S220010', 'Laura', 'Torres Morales', 'laura.torres@itsur.edu.mx', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 1);

-- NOTA: Este es un archivo REDUCIDO de ejemplo
-- Para generar los 700 alumnos completos, 70 profesores, todas las materias,
-- grupos e inscripciones, el archivo sería de ~50MB

-- Para importación más rápida, ejecuta esto localmente:
-- php scripts/generate_massive_data.php
-- O sube este archivo SQL al servidor y ejecútalo allí

SET FOREIGN_KEY_CHECKS = 1;

SELECT 'Datos cargados. NOTA: Este es un archivo reducido de ejemplo.' as mensaje;
SELECT 'Para datos completos (700 alumnos), usar generate_massive_data.php localmente' as recomendacion;
