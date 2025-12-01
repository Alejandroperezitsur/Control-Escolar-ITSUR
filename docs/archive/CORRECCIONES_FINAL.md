# Correcciones Realizadas

## 1. Corrección de Botones/Tabs en Carreras
**Archivo:** `src/Views/careers/index.php`
- **Problema:** Los botones de las pestañas (tabs) no funcionaban porque faltaba una etiqueta de cierre `</script>` al final del bloque de JavaScript. Esto impedía que se ejecutara el código JS encargado de inicializar los tabs y cargar el contenido dinámicamente.
- **Solución:** Se agregó la etiqueta `</script>` faltante.
- **Mejora Adicional:** Se corrigió el formato del mensaje de alerta en `showSubjectDetails` para usar saltos de línea correctos (`\n`).

## 2. Corrección de Conteo de Carreras
**Archivo:** `src/Controllers/CareersController.php`
- **Problema:** El método `getCareersCount` retornaba un valor fijo (`3`) en lugar del número real de carreras en la base de datos.
- **Solución:** Se actualizó el método para realizar una consulta `COUNT(*)` a la tabla `carreras`, devolviendo el número real de registros.

## Verificación
- Los tabs en la sección de Carreras ahora deberían responder a los clics y cargar el plan de estudios correspondiente.
- El contador de carreras (si se usa en alguna parte de la UI) ahora reflejará la cantidad real de carreras registradas.
