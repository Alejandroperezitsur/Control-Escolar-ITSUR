

-- 1. Insert Subjects (Materias) if they don't exist
INSERT IGNORE INTO materias (nombre, clave) VALUES
-- Matemáticas básicas
('Cálculo Diferencial', 'MAT-1001'),
('Cálculo Integral', 'MAT-1002'),
('Cálculo Vectorial', 'MAT-1003'),
('Álgebra Lineal', 'MAT-1004'),
('Ecuaciones Diferenciales', 'MAT-1005'),
('Probabilidad y Estadística', 'MAT-1006'),

-- Inglés (10 niveles)
('Inglés I', 'ING-1001'),
('Inglés II', 'ING-1002'),
('Inglés III', 'ING-1003'),
('Inglés IV', 'ING-1004'),
('Inglés V', 'ING-1005'),
('Inglés VI', 'ING-1006'),
('Inglés VII', 'ING-1007'),
('Inglés VIII', 'ING-1008'),
('Inglés IX', 'ING-1009'),
('Inglés X', 'ING-1010'),

-- Ciencias básicas
('Química', 'QUI-1001'),
('Física I', 'FIS-1001'),
('Física II', 'FIS-1002'),

-- Formación general
('Taller de Ética', 'ETI-1001'),
('Desarrollo Sustentable', 'DES-1001'),
('Fundamentos de Investigación', 'INV-1001'),
('Taller de Investigación I', 'INV-1002'),
('Taller de Investigación II', 'INV-1003'),
('Gestión de Proyectos', 'ADM-1001'),

-- Residencias (común a todas las carreras)
('Residencias Profesionales', 'RES-9001'),
('Servicio Social', 'SER-9001'),

-- ISC Subjects
('Fundamentos de Programación', 'ISC-1001'),
('Programación Orientada a Objetos', 'ISC-1002'),
('Estructura de Datos', 'ISC-1003'),
('Tópicos Avanzados de Programación', 'ISC-1004'),
('Programación Web', 'ISC-1005'),
('Programación Lógica y Funcional', 'ISC-1006'),
('Lenguajes y Autómatas I', 'ISC-1007'),
('Lenguajes y Autómatas II', 'ISC-1008'),
('Base de Datos', 'ISC-2001'),
('Taller de Base de Datos', 'ISC-2002'),
('Arquitectura de Computadoras', 'ISC-2003'),
('Sistemas Operativos', 'ISC-2004'),
('Redes de Computadoras', 'ISC-2005'),
('Administración de Redes', 'ISC-2006'),
('Ingeniería de Software', 'ISC-3001'),
('Calidad de Software', 'ISC-3002'),
('Inteligencia Artificial', 'ISC-3003'),
('Graficación', 'ISC-3004'),
('Simulación', 'ISC-3005'),
('Principios Eléctricos y Aplicaciones Digitales', 'ISC-3006'),
('Fundamentos de Telecomunicaciones', 'ISC-3007'),
('Conmutación y Enrutamiento de Redes', 'ISC-3008'),
('Administración de Bases de Datos', 'ISC-3009'),
('Programación Móvil', 'ISC-4001'),
('Desarrollo de Aplicaciones Web', 'ISC-4002'),
('Seguridad Informática', 'ISC-4003');

-- 2. Get ISC ID
SET @isc_id = (SELECT id FROM carreras WHERE clave = 'ISC' OR clave = 'IC' LIMIT 1);

-- 3. Insert Curriculum (Materias Carrera)
-- SEMESTRE 1
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-1001'), @isc_id, 1, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1001'), @isc_id, 1, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1004'), @isc_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'QUI-1001'), @isc_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'INV-1001'), @isc_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1001'), @isc_id, 1, 3, 'basica');

-- SEMESTRE 2
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-1002'), @isc_id, 2, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1002'), @isc_id, 2, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1006'), @isc_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'FIS-1001'), @isc_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ISC-2003'), @isc_id, 2, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1002'), @isc_id, 2, 3, 'basica');

-- SEMESTRE 3
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-1003'), @isc_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-2001'), @isc_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1003'), @isc_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'FIS-1002'), @isc_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ISC-1007'), @isc_id, 3, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1003'), @isc_id, 3, 3, 'basica');

-- SEMESTRE 4
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-1004'), @isc_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-2004'), @isc_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-2002'), @isc_id, 4, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1005'), @isc_id, 4, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ISC-1008'), @isc_id, 4, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1004'), @isc_id, 4, 3, 'basica');

-- SEMESTRE 5
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-3001'), @isc_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-2005'), @isc_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-1005'), @isc_id, 5, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-3006'), @isc_id, 5, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ETI-1001'), @isc_id, 5, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1005'), @isc_id, 5, 3, 'basica');

-- SEMESTRE 6
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-3009'), @isc_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-2006'), @isc_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-3003'), @isc_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-3007'), @isc_id, 6, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'DES-1001'), @isc_id, 6, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1006'), @isc_id, 6, 3, 'basica');

-- SEMESTRE 7
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-4002'), @isc_id, 7, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-4001'), @isc_id, 7, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-3002'), @isc_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-3005'), @isc_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'INV-1002'), @isc_id, 7, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1007'), @isc_id, 7, 3, 'basica');

-- SEMESTRE 8
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-4003'), @isc_id, 8, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-3004'), @isc_id, 8, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ISC-3008'), @isc_id, 8, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ADM-1001'), @isc_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'INV-1003'), @isc_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1008'), @isc_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1009'), @isc_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1010'), @isc_id, 8, 3, 'basica');

-- SEMESTRE 9
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'RES-9001'), @isc_id, 9, 10, 'residencia'),
((SELECT id FROM materias WHERE clave = 'SER-9001'), @isc_id, 9, 10, 'residencia');
