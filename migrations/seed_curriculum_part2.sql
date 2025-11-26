-- Curriculum assignment for remaining careers: IGE, IE, IM, IER, CP


SET @ige_id = (SELECT id FROM carreras WHERE clave = 'IGE' LIMIT 1);
SET @ie_id = (SELECT id FROM carreras WHERE clave = 'IE' LIMIT 1);
SET @im_id = (SELECT id FROM carreras WHERE clave = 'IM' LIMIT 1);
SET @ier_id = (SELECT id FROM carreras WHERE clave = 'IER' LIMIT 1);
SET @cp_id = (SELECT id FROM carreras WHERE clave = 'CP' LIMIT 1);

-- ================================================================
-- INGENIERÍA EN GESTIÓN EMPRESARIAL (IGE) - 9 SEMESTRES
-- ================================================================

-- SEMESTRE 1
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IGE-1001'), @ige_id, 1, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1001'), @ige_id, 1, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'QUI-1001'), @ige_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IGE-1002'), @ige_id, 1, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'INV-1001'), @ige_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1001'), @ige_id, 1, 3, 'basica');

-- SEMESTRE 2
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IGE-2001'), @ige_id, 2, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1002'), @ige_id, 2, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1006'), @ige_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IGE-1003'), @ige_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1004'), @ige_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1002'), @ige_id, 2, 3, 'basica');

-- SEMESTRE 3
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IGE-2002'), @ige_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IGE-2003'), @ige_id, 3, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1003'), @ige_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IGE-2004'), @ige_id, 3, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IGE-3001'), @ige_id, 3, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1003'), @ige_id, 3, 3, 'basica');

-- SEMESTRE 4
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IGE-3002'), @ige_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IGE-3003'), @ige_id, 4, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IGE-2005'), @ige_id, 4, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1005'), @ige_id, 4, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IGE-3004'), @ige_id, 4, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1004'), @ige_id, 4, 3, 'basica');

-- SEMESTRE 5
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IGE-3005'), @ige_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IGE-3006'), @ige_id, 5, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IGE-4002'), @ige_id, 5, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ETI-1001'), @ige_id, 5, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IGE-4004'), @ige_id, 5, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1005'), @ige_id, 5, 3, 'basica');

-- SEMESTRE 6
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IGE-3007'), @ige_id, 6, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IGE-4003'), @ige_id, 6, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IGE-4001'), @ige_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'DES-1001'), @ige_id, 6, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IGE-4006'), @ige_id, 6, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1006'), @ige_id, 6, 3, 'basica');

-- SEMESTRE 7
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IGE-4005'), @ige_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IGE-4007'), @ige_id, 7, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'INV-1002'), @ige_id, 7, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ADM-1001'), @ige_id, 7, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1007'), @ige_id, 7, 3, 'basica');

-- SEMESTRE 8
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'INV-1003'), @ige_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1008'), @ige_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1009'), @ige_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1010'), @ige_id, 8, 3, 'basica');

-- SEMESTRE 9
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'RES-9001'), @ige_id, 9, 10, 'residencia'),
((SELECT id FROM materias WHERE clave = 'SER-9001'), @ige_id, 9, 10, 'residencia');

-- ================================================================
-- INGENIERÍA ELECTRÓNICA (IE) - 9 SEMESTRES
-- ================================================================

-- SEMESTRE 1
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IE-1001'), @ie_id, 1, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1001'), @ie_id, 1, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1004'), @ie_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'QUI-1001'), @ie_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'INV-1001'), @ie_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1001'), @ie_id, 1, 3, 'basica');

-- SEMESTRE 2
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IE-1002'), @ie_id, 2, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1002'), @ie_id, 2, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1006'), @ie_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'FIS-1001'), @ie_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IE-2003'), @ie_id, 2, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1002'), @ie_id, 2, 3, 'basica');

-- SEMESTRE 3
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IE-2001'), @ie_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IE-2002'), @ie_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1003'), @ie_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'FIS-1002'), @ie_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IE-2004'), @ie_id, 3, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1003'), @ie_id, 3, 3, 'basica');

-- SEMESTRE 4
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IE-3001'), @ie_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IE-3003'), @ie_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1005'), @ie_id, 4, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IE-3004'), @ie_id, 4, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IE-3006'), @ie_id, 4, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1004'), @ie_id, 4, 3, 'basica');

-- SEMESTRE 5
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IE-3002'), @ie_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IE-3005'), @ie_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IE-4001'), @ie_id, 5, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ETI-1001'), @ie_id, 5, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IE-4002'), @ie_id, 5, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1005'), @ie_id, 5, 3, 'basica');

-- SEMESTRE 6
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IE-4003'), @ie_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IE-4004'), @ie_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IE-4006'), @ie_id, 6, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'DES-1001'), @ie_id, 6, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IE-4007'), @ie_id, 6, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1006'), @ie_id, 6, 3, 'basica');

-- SEMESTRE 7
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IE-4005'), @ie_id, 7, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IE-4008'), @ie_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IE-4009'), @ie_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'INV-1002'), @ie_id, 7, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1007'), @ie_id, 7, 3, 'basica');

-- SEMESTRE 8
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'ADM-1001'), @ie_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'INV-1003'), @ie_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1008'), @ie_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1009'), @ie_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1010'), @ie_id, 8, 3, 'basica');

-- SEMESTRE 9
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'RES-9001'), @ie_id, 9, 10, 'residencia'),
((SELECT id FROM materias WHERE clave = 'SER-9001'), @ie_id, 9, 10, 'residencia');

-- ================================================================
-- INGENIERÍA MECATRÓNICA (IM) - 9 SEMESTRES
-- ================================================================

-- SEMESTRE 1
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IM-1001'), @im_id, 1, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1001'), @im_id, 1, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1004'), @im_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'QUI-1001'), @im_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'INV-1001'), @im_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1001'), @im_id, 1, 3, 'basica');

-- SEMESTRE 2
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IM-1002'), @im_id, 2, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-1003'), @im_id, 2, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1002'), @im_id, 2, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1006'), @im_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'FIS-1001'), @im_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1002'), @im_id, 2, 3, 'basica');

-- SEMESTRE 3
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IM-1004'), @im_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-2001'), @im_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-2002'), @im_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1003'), @im_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'FIS-1002'), @im_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1003'), @im_id, 3, 3, 'basica');

-- SEMESTRE 4
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IM-2003'), @im_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-2004'), @im_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-2005'), @im_id, 4, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1005'), @im_id, 4, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IM-3001'), @im_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1004'), @im_id, 4, 3, 'basica');

-- SEMESTRE 5
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IM-3002'), @im_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-3003'), @im_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-3004'), @im_id, 5, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-3005'), @im_id, 5, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ETI-1001'), @im_id, 5, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1005'), @im_id, 5, 3, 'basica');

-- SEMESTRE 6
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IM-3006'), @im_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-4001'), @im_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-4002'), @im_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'DES-1001'), @im_id, 6, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1006'), @im_id, 6, 3, 'basica');

-- SEMESTRE 7
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IM-4003'), @im_id, 7, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-4004'), @im_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-4005'), @im_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'INV-1002'), @im_id, 7, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1007'), @im_id, 7, 3, 'basica');

-- SEMESTRE 8
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IM-4006'), @im_id, 8, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-4007'), @im_id, 8, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IM-4008'), @im_id, 8, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ADM-1001'), @im_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'INV-1003'), @im_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1008'), @im_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1009'), @im_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1010'), @im_id, 8, 3, 'basica');

-- SEMESTRE 9
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'RES-9001'), @im_id, 9, 10, 'residencia'),
((SELECT id FROM materias WHERE clave = 'SER-9001'), @im_id, 9, 10, 'residencia');

-- ================================================================
-- INGENIERÍA EN ENERGÍAS RENOVABLES (IER) - 9 SEMESTRES
-- ================================================================

-- SEMESTRE 1
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IER-1001'), @ier_id, 1, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1001'), @ier_id, 1, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1004'), @ier_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'QUI-1001'), @ier_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'INV-1001'), @ier_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1001'), @ier_id, 1, 3, 'basica');

-- SEMESTRE 2
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IER-2001'), @ier_id, 2, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1002'), @ier_id, 2, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1006'), @ier_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'FIS-1001'), @ier_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IER-2004'), @ier_id, 2, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1002'), @ier_id, 2, 3, 'basica');

-- SEMESTRE 3
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IER-2002'), @ier_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IER-2003'), @ier_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1003'), @ier_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'FIS-1002'), @ier_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IER-3001'), @ier_id, 3, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1003'), @ier_id, 3, 3, 'basica');

-- SEMESTRE 4
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IER-3002'), @ier_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IER-3003'), @ier_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1005'), @ier_id, 4, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'IER-3004'), @ier_id, 4, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ING-1004'), @ier_id, 4, 3, 'basica');

-- SEMESTRE 5
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IER-3005'), @ier_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IER-3006'), @ier_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IER-3007'), @ier_id, 5, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ETI-1001'), @ier_id, 5, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1005'), @ier_id, 5, 3, 'basica');

-- SEMESTRE 6
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IER-4001'), @ier_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IER-4002'), @ier_id, 6, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IER-4003'), @ier_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'DES-1001'), @ier_id, 6, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1006'), @ier_id, 6, 3, 'basica');

-- SEMESTRE 7
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IER-4004'), @ier_id, 7, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IER-4005'), @ier_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'IER-4006'), @ier_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'INV-1002'), @ier_id, 7, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1007'), @ier_id, 7, 3, 'basica');

-- SEMESTRE 8
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'IER-4007'), @ier_id, 8, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ADM-1001'), @ier_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'INV-1003'), @ier_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1008'), @ier_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1009'), @ier_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1010'), @ier_id, 8, 3, 'basica');

-- SEMESTRE 9
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'RES-9001'), @ier_id, 9, 10, 'residencia'),
((SELECT id FROM materias WHERE clave = 'SER-9001'), @ier_id, 9, 10, 'residencia');

-- ================================================================
-- CONTADOR PÚBLICO (CP) - 9 SEMESTRES
-- ================================================================

-- SEMESTRE 1
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-1001'), @cp_id, 1, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-1002'), @cp_id, 1, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1001'), @cp_id, 1, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'CP-1003'), @cp_id, 1, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'INV-1001'), @cp_id, 1, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1001'), @cp_id, 1, 3, 'basica');

-- SEMESTRE 2
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-2001'), @cp_id, 2, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-2006'), @cp_id, 2, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1002'), @cp_id, 2, 5, 'basica'),
((SELECT id FROM materias WHERE clave = 'MAT-1006'), @cp_id, 2, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1002'), @cp_id, 2, 3, 'basica');

-- SEMESTRE 3
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-2002'), @cp_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-2003'), @cp_id, 3, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-2005'), @cp_id, 3, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1004'), @cp_id, 3, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1003'), @cp_id, 3, 3, 'basica');

-- SEMESTRE 4
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-2004'), @cp_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-3004'), @cp_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-3006'), @cp_id, 4, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'MAT-1003'), @cp_id, 4, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1004'), @cp_id, 4, 3, 'basica');

-- SEMESTRE 5
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-3001'), @cp_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-3005'), @cp_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4001'), @cp_id, 5, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ETI-1001'), @cp_id, 5, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1005'), @cp_id, 5, 3, 'basica');

-- SEMESTRE 6
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-3002'), @cp_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-3007'), @cp_id, 6, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4005'), @cp_id, 6, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'DES-1001'), @cp_id, 6, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1006'), @cp_id, 6, 3, 'basica');

-- SEMESTRE 7
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-3003'), @cp_id, 7, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4003'), @cp_id, 7, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4006'), @cp_id, 7, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'INV-1002'), @cp_id, 7, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1007'), @cp_id, 7, 3, 'basica');

-- SEMESTRE 8
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'CP-4002'), @cp_id, 8, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4004'), @cp_id, 8, 5, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'CP-4007'), @cp_id, 8, 4, 'especialidad'),
((SELECT id FROM materias WHERE clave = 'ADM-1001'), @cp_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'INV-1003'), @cp_id, 8, 4, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1008'), @cp_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1009'), @cp_id, 8, 3, 'basica'),
((SELECT id FROM materias WHERE clave = 'ING-1010'), @cp_id, 8, 3, 'basica');

-- SEMESTRE 9
INSERT INTO materias_carrera (materia_id, carrera_id, semestre, creditos, tipo) VALUES
((SELECT id FROM materias WHERE clave = 'RES-9001'), @cp_id, 9, 10, 'residencia'),
((SELECT id FROM materias WHERE clave = 'SER-9001'), @cp_id, 9, 10, 'residencia');
