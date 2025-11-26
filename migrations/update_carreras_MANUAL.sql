

ALTER TABLE carreras 
ADD COLUMN descripcion TEXT AFTER nombre;

ALTER TABLE carreras 
ADD COLUMN duracion_semestres INT DEFAULT 9 AFTER descripcion;

ALTER TABLE carreras 
ADD COLUMN creditos_totales INT DEFAULT 240 AFTER duracion_semestres;

ALTER TABLE carreras 
ADD COLUMN activo TINYINT(1) DEFAULT 1 AFTER creditos_totales;

UPDATE carreras SET 
    descripcion = CASE 
        WHEN clave = 'ISC' OR clave = 'IC' THEN 'Profesionista capaz de diseñar, desarrollar e implementar sistemas computacionales aplicando las metodologías y tecnologías más recientes.'
        WHEN clave = 'II' THEN 'Profesionista capaz de diseñar, implementar y mejorar sistemas de producción de bienes y servicios.'
        WHEN clave = 'IGE' THEN 'Profesionista capaz de diseñar, crear y dirigir organizaciones competitivas con visión estratégica.'
        WHEN clave = 'IE' THEN 'Profesionista capaz de diseñar, desarrollar e innovar sistemas electrónicos para la solución de problemas en el sector productivo.'
        WHEN clave = 'IM' THEN 'Profesionista capaz de diseñar, construir y mantener sistemas mecatrónicos innovadores.'
        WHEN clave = 'IER' THEN 'Profesionista capaz de diseñar, implementar y evaluar proyectos de energía sustentable.'
        WHEN clave = 'CP' THEN 'Profesionista capaz de diseñar, implementar y evaluar sistemas de información financiera.'
        ELSE 'Descripción no disponible'
    END,
    duracion_semestres = 9,
    creditos_totales = 240,
    activo = 1;

SELECT id, nombre, clave, 
       SUBSTRING(descripcion, 1, 50) as descripcion_preview,
       duracion_semestres, 
       creditos_totales, 
       activo 
FROM carreras 
ORDER BY nombre;
