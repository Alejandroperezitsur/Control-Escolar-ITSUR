-- Link subjects to careers and semesters
-- This creates the complete curriculum for all 7 careers


-- Get career IDs (assuming they're in order: ISC, II, IGE, IE, IM, IER, CP)
SET @isc_id = (SELECT id FROM carreras WHERE clave = 'ISC' OR clave = 'IC' LIMIT 1);
SET @ii_id = (SELECT id FROM carreras WHERE clave = 'II' LIMIT 1);
SET @ige_id = (SELECT id FROM carreras WHERE clave = 'IGE' LIMIT 1);
SET @ie_id = (SELECT id FROM carreras WHERE clave = 'IE' LIMIT 1);
SET @im_id = (SELECT id FROM carreras WHERE clave = 'IM' LIMIT 1);
SET @ier_id = (SELECT id FROM carreras WHERE clave = 'IER' LIMIT 1);
SET @cp_id = (SELECT id FROM carreras WHERE clave = 'CP' LIMIT 1);

-- ================================================================
-- INGENIERÍA EN SISTEMAS COMPUTACIONALES (ISC) - 9 SEMESTRES
-- ================================================================

-- SEMESTRE 1
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-1001'), @isc_id, 1, 5, 'especialidad'), -- Fundamentos de Programación
((SELECT id FROM materias WHERE clave = 'MAT-1001'), @isc_id, 1, 5, 'basica'), -- Cálculo Diferencial
((SELECT id FROM materias WHERE clave = 'MAT-1004'), @isc_id, 1, 4, 'basica'), -- Álgebra Lineal
((SELECT id FROM materias WHERE clave = 'QUI-1001'), @isc_id, 1, 4, 'basica'), -- Química
((SELECT id FROM materias WHERE clave = 'INV-1001'), @isc_id, 1, 4, 'basica'), -- Fundamentos de Investigación
((SELECT id FROM materias WHERE clave = 'ING-1001'), @isc_id, 1, 3, 'basica'); -- Inglés I

-- SEMESTRE 2
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-1002'), @isc_id, 2, 5, 'especialidad'), -- POO
((SELECT id FROM materias WHERE clave = 'MAT-1002'), @isc_id, 2, 5, 'basica'), -- Cálculo Integral
((SELECT id FROM materias WHERE clave = 'MAT-1006'), @isc_id, 2, 4, 'basica'), -- Probabilidad y Estadística
((SELECT id FROM materias WHERE clave = 'FIS-1001'), @isc_id, 2, 4, 'basica'), -- Física I
((SELECT id FROM materias WHERE clave = 'ISC-2003'), @isc_id, 2, 4, 'especialidad'), -- Arquitectura de Computadoras
((SELECT id FROM materias WHERE clave = 'ING-1002'), @isc_id, 2, 3, 'basica'); -- Inglés II

-- SEMESTRE 3
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-1003'), @isc_id, 3, 5, 'especialidad'), -- Estructura de Datos
((SELECT id FROM materias WHERE clave = 'ISC-2001'), @isc_id, 3, 5, 'especialidad'), -- Base de Datos
((SELECT id FROM materias WHERE clave = 'MAT-1003'), @isc_id, 3, 4, 'basica'), -- Cálculo Vectorial
((SELECT id FROM materias WHERE clave = 'FIS-1002'), @isc_id, 3, 4, 'basica'), -- Física II
((SELECT id FROM materias WHERE clave = 'ISC-1007'), @isc_id, 3, 4, 'especialidad'), -- Lenguajes y Autómatas I
((SELECT id FROM materias WHERE clave = 'ING-1003'), @isc_id, 3, 3, 'basica'); -- Inglés III

-- SEMESTRE 4
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-1004'), @isc_id, 4, 5, 'especialidad'), -- Tópicos Avanzados
((SELECT id FROM materias WHERE clave = 'ISC-2004'), @isc_id, 4, 5, 'especialidad'), -- Sistemas Operativos
((SELECT id FROM materias WHERE clave = 'ISC-2002'), @isc_id, 4, 4, 'especialidad'), -- Taller de BD
((SELECT id FROM materias WHERE clave = 'MAT-1005'), @isc_id, 4, 4, 'basica'), -- Ecuaciones Diferenciales
((SELECT id FROM materias WHERE clave = 'ISC-1008'), @isc_id, 4, 4, 'especialidad'), -- Lenguajes y Autómatas II
((SELECT id FROM materias WHERE clave = 'ING-1004'), @isc_id, 4, 3, 'basica'); -- Inglés IV

-- SEMESTRE 5
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-3001'), @isc_id, 5, 5, 'especialidad'), -- Ingeniería de Software
((SELECT id FROM materias WHERE clave = 'ISC-2005'), @isc_id, 5, 5, 'especialidad'), -- Redes de Computadoras
((SELECT id FROM materias WHERE clave = 'ISC-1005'), @isc_id, 5, 4, 'especialidad'), -- Programación Web
((SELECT id FROM materias WHERE clave = 'ISC-3006'), @isc_id, 5, 4, 'especialidad'), -- Principios Eléctricos
((SELECT id FROM materias WHERE clave = 'ETI-1001'), @isc_id, 5, 4, 'basica'), -- Ética
((SELECT id FROM materias WHERE clave = 'ING-1005'), @isc_id, 5, 3, 'basica'); -- Inglés V

-- SEMESTRE 6
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-3009'), @isc_id, 6, 5, 'especialidad'), -- Admin BD
((SELECT id FROM materias WHERE clave = 'ISC-2006'), @isc_id, 6, 5, 'especialidad'), -- Admin Redes
((SELECT id FROM materias WHERE clave = 'ISC-3003'), @isc_id, 6, 5, 'especialidad'), -- IA
((SELECT id FROM materias WHERE clave = 'ISC-3007'), @isc_id, 6, 4, 'especialidad'), -- Telecomunicaciones
((SELECT id FROM materias WHERE clave = 'DES-1001'), @isc_id, 6, 4, 'basica'), -- Desarrollo Sustentable
((SELECT id FROM materias WHERE clave = 'ING-1006'), @isc_id, 6, 3, 'basica'); -- Inglés VI

-- SEMESTRE 7
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-4002'), @isc_id, 7, 5, 'especialidad'), -- Desarrollo Web
((SELECT id FROM materias WHERE clave = 'ISC-4001'), @isc_id, 7, 5, 'especialidad'), -- Prog Móvil
((SELECT id FROM materias WHERE clave = 'ISC-3002'), @isc_id, 7, 4, 'especialidad'), -- Calidad Software
((SELECT id FROM materias WHERE clave = 'ISC-3005'), @isc_id, 7, 4, 'especialidad'), -- Simulación
((SELECT id FROM materias WHERE clave = 'INV-1002'), @isc_id, 7, 4, 'basica'), -- Taller Inv I
((SELECT id FROM materias WHERE clave = 'ING-1007'), @isc_id, 7, 3, 'basica'); -- Inglés VII

-- SEMESTRE 8
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ISC-4003'), @isc_id, 8, 5, 'especialidad'), -- Seguridad
((SELECT id FROM materias WHERE clave = 'ISC-3004'), @isc_id, 8, 4, 'especialidad'), -- Graficación
((SELECT id FROM materias WHERE clave = 'ISC-3008'), @isc_id, 8, 4, 'especialidad'), -- Conmutación
((SELECT id FROM materias WHERE clave = 'ADM-1001'), @isc_id, 8, 4, 'basica'), -- Gestión Proyectos
((SELECT id FROM materias WHERE clave = 'INV-1003'), @isc_id, 8, 4, 'basica'), -- Taller Inv II
((SELECT id FROM materias WHERE clave = 'ING-1008'), @isc_id, 8, 3, 'basica'), -- Inglés VIII
((SELECT id FROM materias WHERE clave = 'ING-1009'), @isc_id, 8, 3, 'basica'), -- Inglés IX
((SELECT id FROM materias WHERE clave = 'ING-1010'), @isc_id, 8, 3, 'basica'); -- Inglés X

-- SEMESTRE 9
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'RES-9001'), @isc_id, 9, 10, 'residencia'), -- Residencias
((SELECT id FROM materias WHERE clave = 'SER-9001'), @isc_id, 9, 10, 'residencia'); -- Servicio Social

-- ================================================================
-- INGENIERÍA INDUSTRIAL (II) - 9 SEMESTRES
-- ================================================================

-- SEMESTRE 1
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'II-1001'), @ii_id, 1, 4, 'especialidad'), -- Dibujo Industrial
((SELECT id FROM materias WHERE clave = 'MAT-1001'), @ii_id, 1, 5, 'basica'), -- Cálculo Diferencial
((SELECT id FROM materias WHERE clave = 'QUI-1001'), @ii_id, 1, 4, 'basica'), -- Química
((SELECT id FROM materias WHERE clave = 'II-1003'), @ii_id, 1, 4, 'especialidad'), -- Propiedades Materiales
((SELECT id FROM materias WHERE clave = 'INV-1001'), @ii_id, 1, 4, 'basica'), -- Fundamentos Investigación
((SELECT id FROM materias WHERE clave = 'ING-1001'), @ii_id, 1, 3, 'basica'); -- Inglés I

-- SEMESTRE 2
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'II-1002'), @ii_id, 2, 4, 'especialidad'), -- Metrología
((SELECT id FROM materias WHERE clave = 'MAT-1002'), @ii_id, 2, 5, 'basica'), -- Cálculo Integral
((SELECT id FROM materias WHERE clave = 'MAT-1006'), @ii_id, 2, 4, 'basica'), -- Probabilidad
((SELECT id FROM materias WHERE clave = 'FIS-1001'), @ii_id, 2, 4, 'basica'), -- Física I
((SELECT id FROM materias WHERE clave = 'II-1004'), @ii_id, 2, 4, 'especialidad'), -- Procesos Fabricación
((SELECT id FROM materias WHERE clave = 'ING-1002'), @ii_id, 2, 3, 'basica'); -- Inglés II

-- SEMESTRE 3
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'II-2001'), @ii_id, 3, 4, 'especialidad'), -- Estadística Inf I
((SELECT id FROM materias WHERE clave = 'MAT-1003'), @ii_id, 3, 4, 'basica'), -- Cálculo Vectorial
((SELECT id FROM materias WHERE clave = 'MAT-1004'), @ii_id, 3, 4, 'basica'), -- Álgebra Lineal
((SELECT id FROM materias WHERE clave = 'FIS-1002'), @ii_id, 3, 4, 'basica'), -- Física II
((SELECT id FROM materias WHERE clave = 'II-2003'), @ii_id, 3, 5, 'especialidad'), -- Estudio Trabajo I
((SELECT id FROM materias WHERE clave = 'ING-1003'), @ii_id, 3, 3, 'basica'); -- Inglés III

-- SEMESTRE 4
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'II-2002'), @ii_id, 4, 4, 'especialidad'), -- Estadística Inf II
((SELECT id FROM materias WHERE clave = 'MAT-1005'), @ii_id, 4, 4, 'basica'), -- Ecuaciones Dif
((SELECT id FROM materias WHERE clave = 'II-2004'), @ii_id, 4, 5, 'especialidad'), -- Estudio Trabajo II
((SELECT id FROM materias WHERE clave = 'II-1005'), @ii_id, 4, 4, 'especialidad'), -- Electricidad
((SELECT id FROM materias WHERE clave = 'II-2005'), @ii_id, 4, 4, 'especialidad'), -- Ergonomía
((SELECT id FROM materias WHERE clave = 'ING-1004'), @ii_id, 4, 3, 'basica'); -- Inglés IV

-- SEMESTRE 5
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'II-3001'), @ii_id, 5, 4, 'especialidad'), -- Inv Operaciones I
((SELECT id FROM materias WHERE clave = 'II-3003'), @ii_id, 5, 5, 'especialidad'), -- Control Calidad
((SELECT id FROM materias WHERE clave = 'II-3004'), @ii_id, 5, 4, 'especialidad'), -- Sistemas Manufactura
((SELECT id FROM materias WHERE clave = 'II-2006'), @ii_id, 5, 4, 'especialidad'), -- Higiene y Seguridad
((SELECT id FROM materias WHERE clave = 'ETI-1001'), @ii_id, 5, 4, 'basica'), -- Ética
((SELECT id FROM materias WHERE clave = 'ING-1005'), @ii_id, 5, 3, 'basica'); -- Inglés V

-- SEMESTRE 6
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'II-3002'), @ii_id, 6, 4, 'especialidad'), -- Inv Operaciones II
((SELECT id FROM materias WHERE clave = 'II-3005'), @ii_id, 6, 4, 'especialidad'), -- Planeación Instalaciones
((SELECT id FROM materias WHERE clave = 'II-3006'), @ii_id, 6, 4, 'especialidad'), -- Gestión Costos
((SELECT id FROM materias WHERE clave = 'II-4001'), @ii_id, 6, 5, 'especialidad'), -- Admin Operaciones I
((SELECT id FROM materias WHERE clave = 'DES-1001'), @ii_id, 6, 4, 'basica'), -- Desarrollo Sustentable
((SELECT id FROM materias WHERE clave = 'ING-1006'), @ii_id, 6, 3, 'basica'); -- Inglés VI

-- SEMESTRE 7
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'II-4002'), @ii_id, 7, 5, 'especialidad'), -- Admin Operaciones II
((SELECT id FROM materias WHERE clave = 'II-4003'), @ii_id, 7, 4, 'especialidad'), -- Planeación Financiera
((SELECT id FROM materias WHERE clave = 'II-4004'), @ii_id, 7, 4, 'especialidad'), -- Mercadotecnia
((SELECT id FROM materias WHERE clave = 'II-4006'), @ii_id, 7, 4, 'especialidad'), -- Logística
((SELECT id FROM materias WHERE clave = 'INV-1002'), @ii_id, 7, 4, 'basica'), -- Taller Inv I
((SELECT id FROM materias WHERE clave = 'ING-1007'), @ii_id, 7, 3, 'basica'); -- Inglés VII

-- SEMESTRE 8
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'II-4007'), @ii_id, 8, 4, 'especialidad'), -- Manufactura Esbelta
((SELECT id FROM materias WHERE clave = 'II-4008'), @ii_id, 8, 4, 'especialidad'), -- Sistemas Gestión Calidad
((SELECT id FROM materias WHERE clave = 'II-4005'), @ii_id, 8, 4, 'especialidad'), -- Relaciones Industriales
((SELECT id FROM materias WHERE clave = 'ADM-1001'), @ii_id, 8, 4, 'basica'), -- Gestión Proyectos
((SELECT id FROM materias WHERE clave = 'INV-1003'), @ii_id, 8, 4, 'basica'), -- Taller Inv II
((SELECT id FROM materias WHERE clave = 'ING-1008'), @ii_id, 8, 3, 'basica'), -- Inglés VIII
((SELECT id FROM materias WHERE clave = 'ING-1009'), @ii_id, 8, 3, 'basica'), -- Inglés IX
((SELECT id FROM materias WHERE clave = 'ING-1010'), @ii_id, 8, 3, 'basica'); -- Inglés X

-- SEMESTRE 9
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'RES-9001'), @ii_id, 9, 10, 'residencia'),
((SELECT id FROM materias WHERE clave = 'SER-9001'), @ii_id, 9, 10, 'residencia');

-- ================================================================
-- Continue with remaining careers in next file due to length...
-- ================================================================
