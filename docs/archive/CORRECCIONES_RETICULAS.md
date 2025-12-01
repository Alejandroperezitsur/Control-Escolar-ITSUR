# Actualización de Retículas (ISC y CP)

## Cambios Realizados

1.  **Creación de Migración de Corrección:**
    *   Se creó el archivo `migrations/seed_isc_cp_fix.sql`.
    *   Este archivo contiene las instrucciones SQL para insertar la retícula completa de **Ingeniería en Sistemas Computacionales (ISC)** y **Contador Público (CP)**.
    *   Se utilizó `INSERT IGNORE` para evitar errores si parte de los datos ya existían.
    *   Se asegura la integridad referencial obteniendo dinámicamente los IDs de las carreras y materias.

2.  **Automatización en el Controlador:**
    *   Se modificó `src/Controllers/Api/KpiController.php`.
    *   Se agregó una verificación específica en el método `admin()`:
        *   Verifica si existen materias registradas para ISC o CP.
        *   Si el conteo es 0 para alguna de ellas, ejecuta automáticamente el script `seed_isc_cp_fix.sql`.

## Cómo Aplicar los Cambios
Simplemente recarga el **Dashboard de Administrador**. Al cargar los KPIs, el sistema detectará si faltan las retículas y las creará automáticamente.

## Verificación
1.  Ve a la sección de **Carreras**.
2.  Selecciona la pestaña **Ingeniería en Sistemas Computacionales** o **Contador Público**.
3.  Deberías ver el plan de estudios completo (Semestres 1-9).
