

-- Get IDs
SET @isc_id = (SELECT id FROM carreras WHERE clave = 'ISC' OR clave = 'IC' LIMIT 1);
SET @cp_id = (SELECT id FROM carreras WHERE clave = 'CP' LIMIT 1);

-- ================================================================
-- INGENIERÍA EN SISTEMAS COMPUTACIONALES (ISC)
-- ================================================================

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

-- ================================================================
-- CONTADOR PÚBLICO (CP)
-- ================================================================

-- SEMESTRE 1
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-1001'), @cp_id, 1, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-1002'), @cp_id, 1, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1001'), @cp_id, 1, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'CP-1003'), @cp_id, 1, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'INV-1001'), @cp_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1001'), @cp_id, 1, 3, 'basica');

-- SEMESTRE 2
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-2001'), @cp_id, 2, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-2006'), @cp_id, 2, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1002'), @cp_id, 2, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1006'), @cp_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1002'), @cp_id, 2, 3, 'basica');

-- SEMESTRE 3
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-2002'), @cp_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-2003'), @cp_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-2005'), @cp_id, 3, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1004'), @cp_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1003'), @cp_id, 3, 3, 'basica');

-- SEMESTRE 4
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-2004'), @cp_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-3004'), @cp_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-3006'), @cp_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1003'), @cp_id, 4, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1004'), @cp_id, 4, 3, 'basica');

-- SEMESTRE 5
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-3001'), @cp_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-3005'), @cp_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4001'), @cp_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ETI-1001'), @cp_id, 5, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1005'), @cp_id, 5, 3, 'basica');

-- SEMESTRE 6
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-3002'), @cp_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-3007'), @cp_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4005'), @cp_id, 6, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'DES-1001'), @cp_id, 6, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1006'), @cp_id, 6, 3, 'basica');

-- SEMESTRE 7
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-3003'), @cp_id, 7, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4003'), @cp_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4006'), @cp_id, 7, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'INV-1002'), @cp_id, 7, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1007'), @cp_id, 7, 3, 'basica');

-- SEMESTRE 8
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-4002'), @cp_id, 8, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4004'), @cp_id, 8, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4007'), @cp_id, 8, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ADM-1001'), @cp_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'INV-1003'), @cp_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1008'), @cp_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1009'), @cp_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1010'), @cp_id, 8, 3, 'basica');

-- SEMESTRE 9
INSERT IGNORE INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'RES-9001'), @cp_id, 9, 10, 'residencia'),
((SELECT id FROM materias WHERE clave = 'SER-9001'), @cp_id, 9, 10, 'residencia');
