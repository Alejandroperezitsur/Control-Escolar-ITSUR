# INSTRUCCIONES PARA EJECUTAR LA TRANSFORMACIÓN DEL SISTEMA

## Paso 1: Importar el Schema Completo

1. Abre tu navegador y ve a: `http://localhost/phpmyadmin`
2. En el panel izquierdo, selecciona la base de datos `control_escolar` (o créala si no existe)
3. Haz clic en la pestaña "Importar" en la parte superior
4. Haz clic en "Elegir archivo" y selecciona:
   ```
   C:\xampp\htdocs\PWBII\Control-Escolar-ITSUR\migrations\complete_real_system_schema.sql
   ```
5. Haz clic en "Continuar" al final de la página
6. Espera a que termine la importación (debería decir "Importación finalizada correctamente")

## Paso 2: Verificar que el Schema se Creó Correctamente

Después de importar, verifica que se crearon las siguientes tablas y vistas en phpMyAdmin:

### Tablas:
- ✓ carreras
- ✓ usuarios
- ✓ materias
- ✓ materias_carrera
- ✓ grupos
- ✓ alumnos
- ✓ inscripciones
- ✓ horarios
- ✓ calificaciones_unidades (NUEVA)
- ✓ calificaciones_finales (NUEVA)

### Vistas:
- ✓ view_kardex
- ✓ view_carga_academica
- ✓ view_estadisticas_alumno

## Paso 3: Generar Datos de Prueba Realistas

Ejecuta el script de generación de datos (se creará a continuación):

```powershell
cd C:\xampp\htdocs\PWBII\Control-Escolar-ITSUR
C:\xampp\php\php.exe scripts\generate_realistic_student_data.php
```

Este script generará:
- Materias realistas de ISC con créditos apropiados
- Un alumno de 7mo semestre con historial completo
- Calificaciones por unidades para cada materia
- Horarios y aulas asignadas

## Paso 4: Probar el Sistema

Una vez que los datos estén generados, podrás:

1. **Ver el Kardex** - Login como alumno y navegar a la sección Kardex
2. **Ver la Carga Académica** - Ver materias actuales con horarios
3. **Capturar calificaciones por unidad** - Login como profesor y capturar calificaciones unidad por unidad

## Credenciales de Prueba

**Admin:**
- Email: admin@itsur.edu.mx
- Contraseña: admin123

**Alumno** (se creará automáticamente):
- Matrícula: S22121198 (o similar)
- Contraseña: alumno123

**Profesores** (se crearán automáticamente):
- Varios profesores con materias asignadas
- Contraseña: profesor123

## Nota Importante

El archivo `config/config.php` ya está configurado para usar MySQL local de XAMPP (127.0.0.1, root, sin contraseña). Si tu configuración de MySQL es diferente, edita ese archivo antes de continuar.
