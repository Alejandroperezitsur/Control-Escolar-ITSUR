# Corrección Forzada de Retícula ISC

## Problema Detectado
El plan de estudios de **Ingeniería en Sistemas Computacionales (ISC)** no se estaba cargando correctamente. Esto probablemente se debía a que las materias requeridas no existían en la base de datos, lo que provocaba un fallo silencioso al intentar insertar la retícula debido a restricciones de clave foránea.

## Solución Implementada
1.  **Nueva Migración Robusta (`migrations/force_full_isc_curriculum.sql`):**
    *   Este script **primero inserta todas las materias necesarias** en la tabla `materias` si no existen (usando `INSERT IGNORE`).
    *   Luego, inserta la relación de materias por semestre en `materias_carrera`.
    *   Esto asegura que no haya errores de dependencia.

2.  **Actualización del Controlador (`KpiController.php`):**
    *   Se mejoró la lógica de detección.
    *   Ahora verifica si el número de materias en la retícula de ISC es menor a 40 (un plan completo tiene ~54 materias).
    *   Si detecta que está incompleto, ejecuta automáticamente el script de corrección forzada.

## Instrucciones
Por favor, **recarga el Dashboard de Administrador** nuevamente. Esta acción disparará el nuevo script de corrección, asegurando que todas las materias se creen y se asignen correctamente a la carrera.
