# INSTRUCCIONES PARA IMPORTAR EN SERVIDOR REMOTO (InfinityFree)

## Tu Configuración Actual

**Host:** sicenet-itsur.kesug.com  
**phpMyAdmin:** https://php-myadmin.net/db_structure.php?db=if0_40512739_control_escolar  
**Base de datos:** `if0_40512739_control_escolar`  
**Usuario:** `if0_40512739`  
**Servidor MySQL:** `sql212.infinityfree.com:3306`

---

## PASO 1: Acceder a phpMyAdmin

1. Ve a tu panel de control de InfinityFree o accede directamente a:
   ```
   https://php-myadmin.net
   ```

2. Inicia sesión con:
   - **Usuario:** `if0_40512739`
   - **Contraseña:** `APcZEb123`
   - **Servidor:** `sql212.infinityfree.com`

3. Una vez dentro, selecciona la base de datos `if0_40512739_control_escolar` en el panel izquierdo

---

## PASO 2: Vaciar Base de Datos (si hay tablas antiguas)

Antes de importar, asegúrate de que la base de datos esté completamente vacía:

1. En phpMyAdmin, con la base de datos `if0_40512739_control_escolar` seleccionada
2. Haz clic en la pestaña **"SQL"** en la parte superior
3. Copia y pega este código para eliminar todas las tablas:

```sql
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS calificaciones_unidades;
DROP TABLE IF EXISTS calificaciones_finales;
DROP TABLE IF EXISTS calificaciones;
DROP TABLE IF EXISTS inscripciones;
DROP TABLE IF EXISTS horarios;
DROP TABLE IF EXISTS grupos;
DROP TABLE IF EXISTS materias_carrera;
DROP TABLE IF EXISTS alumnos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS materias;
DROP TABLE IF EXISTS carreras;

DROP VIEW IF EXISTS view_kardex;
DROP VIEW IF EXISTS view_carga_academica;
DROP VIEW IF EXISTS view_estadisticas_alumno;

SET FOREIGN_KEY_CHECKS = 1;
```

4. Haz clic en **"Continuar"** o **"Go"**

---

## PASO 3: Importar el Schema Completo

### Opción A: Importar Desde Archivo (RECOMENDADO)

1. En phpMyAdmin, asegúrate de tener seleccionada la base de datos `if0_40512739_control_escolar`

2. Haz clic en la pestaña **"Importar"** en la parte superior

3. Haz clic en **"Elegir archivo"** / **"Choose File"**

4. Navega a tu proyecto local y selecciona:
   ```
   C:\xampp\htdocs\PWBII\Control-Escolar-ITSUR\migrations\complete_real_system_schema.sql
   ```

5. **IMPORTANTE**: En las opciones de importación:
   - Formato: **SQL**
   - Conjunto de caracteres: **utf8mb4**
   - Deja las demás opciones por defecto

6. Haz clic en **"Continuar"** / **"Go"** al final de la página

7. Espera a que termine (puede tomar 10-30 segundos)

8. Si todo sale bien, verás un mensaje: **"Importación finalizada correctamente"**

### Opción B: Si el Archivo es Muy Grande (> 2MB en InfinityFree)

InfinityFree tiene límites de tamaño de importación. Si la opción A falla, usa este método:

1. Abre el archivo `complete_real_system_schema.sql` en un editor de texto

2. Copia TODO el contenido del archivo

3. En phpMyAdmin, ve a la pestaña **"SQL"**

4. Pega todo el contenido en el cuadro de texto

5. Haz clic en **"Continuar"** / **"Go"**

---

## PASO 4: Verificar que el Schema se Importó Correctamente

Después de la importación, verifica que se crearon las tablas y vistas:

### Verificar Tablas

En phpMyAdmin, con la base de datos seleccionada, deberías ver estas tablas:

- ✓ `alumnos`
- ✓ `calificaciones_finales` ← **NUEVA**
- ✓ `calificaciones_unidades` ← **NUEVA**
- ✓ `carreras`
- ✓ `grupos`
- ✓ `horarios`
- ✓ `inscripciones`
- ✓ `materias`
- ✓ `materias_carrera`
- ✓ `usuarios`

### Verificar Vistas

En phpMyAdmin, haz clic en el icono de **"Estructura"** o ejecuta este SQL para ver las vistas:

```sql
SHOW FULL TABLES WHERE Table_type = 'VIEW';
```

Deberías ver:
- ✓ `view_kardex`
- ✓ `view_carga_academica`
- ✓ `view_estadisticas_alumno`

---

## PASO 5: Generar Datos de Prueba

Ahora necesitas generar los datos de prueba. Como estás en un servidor remoto, tienes 2 opciones:

### Opción A: Ejecutar Script PHP Localmente (Conectando al Servidor Remoto)

1. Actualiza el archivo `config/config.php` para apuntar al servidor remoto:

```php
return array (
  'db' => 
  array (
    'host' => 'sql212.infinityfree.com',
    'name' => 'if0_40512739_control_escolar',
    'user' => 'if0_40512739',
    'pass' => 'APcZEb123',
    'port' => '3306',
  ),
  // ... resto de la configuración
);
```

2. Ejecuta el script desde tu computadora local:

```powershell
cd C:\xampp\htdocs\PWBII\Control-Escolar-ITSUR
C:\xampp\php\php.exe scripts\generate_realistic_student_data.php
```

**Nota**: Esto puede tardar más porque se conecta al servidor remoto.

### Opción B: Ejecutar SQL Directamente en phpMyAdmin

Si la Opción A no funciona (InfinityFree puede bloquear conexiones remotas), sube el script al servidor:

1. Sube el archivo `scripts/generate_realistic_student_data.php` a tu hosting mediante FTP o el File Manager

2. Accede al script desde tu navegador:
   ```
   https://sicenet-itsur.kesug.com/scripts/generate_realistic_student_data.php
   ```

3. El script se ejecutará y mostrará el resultado en el navegador

---

## PASO 6: Verificar Datos Importados

Ejecuta estas consultas en phpMyAdmin (pestaña SQL) para verificar:

```sql
-- Ver cuántas materias se crearon
SELECT COUNT(*) as total_materias FROM materias;
-- Debería mostrar ~36

-- Ver el alumno creado
SELECT * FROM alumnos WHERE matricula = 'S22121198';

-- Ver estadísticas del alumno
SELECT * FROM view_estadisticas_alumno WHERE matricula = 'S22121198';

-- Ver horario de la carga académica
SELECT * FROM view_carga_academica WHERE matricula = 'S22121198';
```

---

## PASO 7: Probar el Sistema

1. Ve a tu sitio web:
   ```
   https://sicenet-itsur.kesug.com
   ```

2. Inicia sesión con las credenciales del alumno:
   - **Usuario/Matrícula:** `S22121198`
   - **Contraseña:** `alumno123`

3. Navega a:
   - `/kardex` - Para ver el historial académico completo
   - `/carga-academica` - Para ver las materias del semestre actual

---

## Solución de Problemas

### Error: "MySQL server has gone away"
- InfinityFree tiene límite de tiempo de conexión
- Divide el script en partes más pequeñas o ejecuta el script PHP desde el servidor

### Error: "File size too large"
- Usa la Opción B del Paso 3 (copiar/pegar SQL directamente)

### Error: "Cannot connect to database"
- Verifica que las credenciales en `config.php` sean correctas
- InfinityFree puede bloquear conexiones remotas, usa la Opción B del Paso 5

### Las vistas no se crean
- Ejecuta manualmente los CREATE VIEW desde la pestaña SQL en phpMyAdmin
- Los puedes encontrar en el archivo `complete_real_system_schema.sql`

---

## Resumen de Credenciales

**Panel de Control / phpMyAdmin:**
- Usuario: `if0_40512739`
- Contraseña: `APcZEb123`

**Para Probar el Sistema:**
- Admin: `admin@itsur.edu.mx` / `admin123`
- Alumno: `S22121198` / `alumno123`
- Profesores: `[nombre]@itsur.edu.mx` / `profesor123`

---

## Archivos Importantes

- Schema: `migrations/complete_real_system_schema.sql`
- Datos: `scripts/generate_realistic_student_data.php`
- Configuración: `config/config.php`
