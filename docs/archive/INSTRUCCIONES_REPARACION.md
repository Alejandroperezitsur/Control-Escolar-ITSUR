# Instrucciones de Reparación Manual

Parece que hay un problema de configuración en el entorno local que impide que los scripts de reparación automática se ejecuten correctamente desde el código (posiblemente debido a diferencias en los drivers de PHP CLI vs Web).

Para solucionar esto definitivamente, he creado una herramienta de reparación web.

## Pasos a seguir:

1.  Abre la siguiente URL en tu navegador:
    `http://localhost/PWBII/Control-Escolar-ITSUR/public/fix_isc.php`

2.  Deberías ver un reporte en pantalla que dice **"DIAGNÓSTICO Y REPARACIÓN DE ISC"**.
    *   El script verificará si la carrera existe.
    *   Creará las materias faltantes.
    *   Insertará forzosamente el plan de estudios.

3.  Si al final ves el mensaje **"¡ÉXITO! La retícula ha sido reparada"**, entonces el problema está resuelto.

4.  Vuelve al Dashboard (`/dashboard`) y revisa la sección de Carreras. El plan de estudios de ISC debería aparecer ahora.
