# Instrucciones: Actualizar Tabla Carreras

## 🎯 Objetivo
Agregar las columnas faltantes (`descripcion`, `duracion_semestres`, `creditos_totales`, `activo`) a la tabla `carreras` existente.

## 📋 Opción 1: Ejecutar desde phpMyAdmin (RECOMENDADO)

1. **Abrir phpMyAdmin**
   - Ir a: `http://localhost/phpmyadmin`
   - Iniciar sesión (usualmente usuario `root` sin contraseña)

2. **Seleccionar la base de datos**
   - En el panel izquierdo, hacer clic en `control_escolar`

3. **Abrir el editor SQL**
   - Hacer clic en la pestaña "SQL" en la parte superior

4. **Copiar y pegar el siguiente código**:

```sql
USE control_escolar;

-- Agregar columna descripcion
ALTER TABLE carreras 
ADD COLUMN descripcion TEXT AFTER nombre;

-- Agregar columna duracion_semestres
ALTER TABLE carreras 
ADD COLUMN duracion_semestres INT DEFAULT 9 AFTER descripcion;

-- Agregar columna creditos_totales
ALTER TABLE carreras 
ADD COLUMN creditos_totales INT DEFAULT 240 AFTER duracion_semestres;

-- Agregar columna activo
ALTER TABLE carreras 
ADD COLUMN activo TINYINT(1) DEFAULT 1 AFTER creditos_totales;

-- Actualizar datos existentes con descripciones
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
```

5. **Hacer clic en "Continuar" o "Go"**

6. **Verificar el resultado**
   - Debería mostrar "Query OK" o similar
   - Ir a la tabla `carreras` y verificar que las nuevas columnas existen

---

## 📋 Opción 2: Desde línea de comandos MySQL

Si prefieres usar la terminal de MySQL:

```bash
# Abrir MySQL
mysql -u root control_escolar

# Luego, copiar y pegar el código SQL de arriba
```

---

## 📋 Opción 3: Importar archivo SQL

1. Ir a phpMyAdmin
2. Seleccionar base de datos `control_escolar`
3. Click en "Importar"
4. Seleccionar el archivo: `migrations/update_carreras_MANUAL.sql`
5. Click en "Continuar"

---

## ✅ Verificar que funcionó

Después de ejecutar la migración, ejecuta esta consulta para verificar:

```sql
SELECT id, nombre, clave, 
       SUBSTRING(descripcion, 1, 50) as descripcion_preview,
       duracion_semestres, 
       creditos_totales, 
       activo 
FROM carreras 
ORDER BY nombre;
```

Deberías ver todas las carreras con sus datos completos.

---

## 🔄 Después de actualizar la base de datos

1. **Recargar la página de carreras**
   - Ve a `/careers` en tu aplicación
   - Ya NO deberías ver los warnings
   - Deberías ver toda la información completa de cada carrera

2. **Si sigues viendo warnings**
   - Limpiar caché del navegador
   - Reiniciar Apache (desde XAMPP)
   - Verificar que la migración se ejecutó correctamente

---

## 🆘 Si algo sale mal

**Si ves error "Duplicate column name":**
- Significa que la columna ya existe
- Puedes omitir ese ALTER TABLE específico
- Continuar con los siguientes

**Si ves error "Table doesn't exist":**
- Asegúrate de que estás en la base de datos correcta: `USE control_escolar;`
- Verifica que la tabla `carreras` existe

**Si todavía ves warnings en la página:**
- Verifica que las columnas se agregaron correctamente:
  ```sql
  DESCRIBE carreras;
  ```
- Deberías ver las 4 nuevas columnas listadas

---

## 📁 Archivos de referencia

- **SQL Manual**: `migrations/update_carreras_MANUAL.sql`
- **SQL con IF NOT EXISTS**: `migrations/update_carreras_add_columns.sql`
- **Vista de Carreras**: `src/Views/careers/index.php`

---

**Fecha**: 2025-11-20  
**Estado**: Listo para ejecutar  
**Tiempo estimado**: 1-2 minutos
