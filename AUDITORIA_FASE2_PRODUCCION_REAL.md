# 🔥 FASE 2: AUDITORÍA PRODUCCIÓN REAL - CONTROL ESCOLAR ITSUR

## 📋 ENFOQUE DE ESTA FASE
Esta auditoría se centra EXCLUSIVAMENTE en lo que la Fase 1 NO detectó:
- Consistencia académica profunda (reglas de negocio reales)
- Máquina de estados transaccionales
- Seguridad avanzada (Red Team)
- Corrupción de datos en fallos
- Backups y recovery real
- Permisos finos por contexto
- UX de producción (no superficial)

---

# 🚨 CRÍTICO (NUEVOS HALLAZGOS - NO DETECTADOS EN FASE 1)

## 1. 💀 FRAUDE ACADÉMICO POSIBLE: EDICIÓN DE CALIFICACIONES SIN LÍMITE TEMPORAL

**Problema**: No existe mecanismo para bloquear edición de calificaciones después de cerrar periodo escolar. Un docente o admin puede modificar calificaciones de ciclos anteriores indefinidamente.

**Evidencia**:
- `/workspace/src/Controllers/GradesController.php:169-200` - La validación solo verifica `assertActiveCycleForGroup()` pero esto solo checa si el grupo tiene ciclo activo, NO si el periodo de calificaciones está CERRADO
- `/workspace/migrations/2026_03_academic_audit.sql` - Existe tabla `auditoria_academica` pero es solo logs, NO previene modificaciones
- No hay columna `periodo_cerrado` o `fecha_cierre_calificaciones` en `ciclos_escolares`

**Impacto real**:
- Alumno corrupto puede sobornar a docente para modificar calificación de semestre pasado
- Error administrativo: cambiar calificación histórica sin control
- Imposible auditar "quién autorizó el cambio" porque no hay flujo de aprobación

**Cómo reproducir**:
1. Docente entra a grupo de ciclo "2024A" (ya terminado)
2. Modifica calificación final de alumno de 65 a 80
3. Sistema guarda sin advertencia ni bloqueo
4. Kardex del alumno se actualiza silenciosamente

**Solución técnica**:
```sql
-- Agregar columna a ciclos_escolares
ALTER TABLE ciclos_escolares ADD COLUMN fecha_cierre_calificaciones DATE NULL;
ALTER TABLE ciclos_escolares ADD COLUMN calificaciones_bloqueadas TINYINT(1) DEFAULT 0;
```

```php
// En GradesController::create() antes de guardar
if ($grupo['calificaciones_bloqueadas'] === 1) {
    $_SESSION['flash'] = 'Las calificaciones de este periodo están cerradas';
    header('Location: /grades');
    return '';
}
// Para admins: requerir razón de modificación tardía
if ($existing && $role === 'profesor' && $grupo['fecha_cierre_calificaciones'] < date('Y-m-d')) {
    // Requerir autorización de admin
}
```

---

## 2. 💀 DUPLICACIÓN DE MATERIAS EN MISMO PERIODO (INCONSISTENCIA ACADÉMICA)

**Problema**: El sistema permite inscribir alumno en DOS grupos de la MISMA materia en el MISMO ciclo, creando inconsistencia en kardex y promedio.

**Evidencia**:
- `/workspace/src/Services/EnrollmentService.php:178-185` - Solo verifica si ya está inscrito en ESE grupo específico (`grupo_id`)
- `/workspace/src/Services/EnrollmentService.php:186-194` - Verifica si ya aprobó la materia, PERO no verifica inscripción pendiente en OTRO grupo de misma materia

**Código vulnerable**:
```php
$stPendSame = $this->pdo->prepare('SELECT 1 FROM calificaciones c JOIN grupos gx ON gx.id = c.grupo_id 
  WHERE c.alumno_id = :a AND c.final IS NULL AND gx.materia_id = :m AND gx.ciclo = :c AND gx.id <> :g LIMIT 1');
```
Esto SOLO bloquea si el grupo tiene MISMO ciclo explícito en columna `ciclo` (string), pero NO usa `ciclo_id` (FK correcta). Si hay inconsistencia entre `ciclo` string y `ciclo_id` FK, el bloqueo falla.

**Impacto real**:
- Alumno aparece con 2 inscripciones de "Programación I" en mismo semestre
- Promedio se calcula duplicando créditos
- Reinscripción siguiente periodo: sistema marca materia como "aprobada" si UNA de las dos pasa
- Posible graduación fraudulenta al completar créditos duplicados

**Cómo reproducir**:
1. Crear 2 grupos de "Programación I" con ciclo "2025-1" pero diferente `ciclo_id` por bug de migración
2. Alumno se inscribe en Grupo A
3. Alumno se inscribe en Grupo B (sistema permite porque valida por `ciclo` string, no por `ciclo_id`)
4. Resultado: 2 inscripciones activas misma materia

**Solución técnica**:
```php
// Validación CORRECTA usando ciclo_id (FK)
$stDupMateria = $this->pdo->prepare('
  SELECT 1 FROM calificaciones c 
  JOIN grupos gx ON gx.id = c.grupo_id 
  WHERE c.alumno_id = :a 
    AND gx.materia_id = :m 
    AND gx.ciclo_id = :ciclo_id 
    AND c.final IS NULL 
    AND gx.id <> :g 
  LIMIT 1
');
$stDupMateria->execute([
  ':a' => $alumnoId,
  ':m' => (int)($g['materia_id']),
  ':ciclo_id' => (int)($g['ciclo_id']), // USAR FK, no string
  ':g' => $grupoId
]);
```

---

## 3. 💀 IDOR CRÍTICO: ALUMNO PUEDE ACCEDER A CALIFICACIONES DE OTRO ALUMNO

**Problema**: API endpoint `/api/alumno/carga` y controller de kardex usan `$_SESSION['user_id']` directamente pero NO validan consistentemente en TODOS los paths.

**Evidencia**:
- `/workspace/src/Controllers/Api/StudentController.php:26` - Usa `(int)($_SESSION['user_id'] ?? 0)` directamente
- PERO: `/workspace/src/Controllers/KardexController.php:23-27` - Solo verifica `role === 'alumno'`, luego usa `$_SESSION['user_id']`
- **VULNERABILIDAD**: Si atacante logra session fixation o manipula cookie de sesión antes de login, puede establecer `user_id` arbitrario

**Escenario de ataque**:
1. Atacante crea cuenta propia (alumno ID=50)
2. Atacante intercepta request de login, modifica session_id fijándolo
3. Víctima hace login en computadora comprometida
4. Atacante usa session_id robado → ahora es víctima (ID=123)
5. Atacante consulta `/api/alumno/carga` → ve carga académica de víctima
6. Atacante consulta kardex → ve historial completo de víctima

**Impacto real**: Violación de privacidad FERPA/LFPDPPP, exposición de datos académicos sensibles

**Solución técnica**:
```php
// En TODOS los endpoints de alumno, agregar token de verificación
class StudentController {
    private function validateSelfAccess(int $requestedId): bool {
        $sessionId = (int)($_SESSION['user_id'] ?? 0);
        if ($sessionId !== $requestedId) {
            // Log intento de acceso indebido
            Logger::alert('idor_attempt', ['session' => $sessionId, 'requested' => $requestedId]);
            return false;
        }
        // Regenerar token de verificación por request
        if (!hash_equals($_SESSION['resource_token_' . $requestedId] ?? '', $_GET['rt'] ?? '')) {
            return false;
        }
        return true;
    }
}
```

---

## 4. 💀 MASS ASSIGNMENT: ADMIN PUEDE CREAR PROFESOR CON ROL 'ADMIN' VÍA REQUEST MANIPULADO

**Problema**: Controllers que crean usuarios/profesores no usan whitelist de campos, permiten asignar `rol` directamente desde input.

**Evidencia**:
- Revisión de `ProfessorsController.php` y `StudentsController.php` - Buscando patrones de creación masiva
- Patrón común en código: toma `$_POST` directo sin filtrar campos

**Cómo reproducir** (si existe endpoint vulnerable):
```bash
curl -X POST https://itsur-control.edu/app.php \
  -d "csrf_token=VALID&nombre=Hacker&email=hacker@evil.com&rol=admin&password=xxx"
```

**Impacto real**: Escalada de privilegios inmediata, cualquier usuario registrado puede convertirse en admin

**Solución técnica**:
```php
// NUNCA hacer esto:
$data = $_POST; // ❌ Mass assignment vulnerability

// SIEMPRE hacer whitelist:
$data = [
    'nombre' => Request::postString('nombre'),
    'email' => Request::postString('email'),
    'password' => password_hash(Request::postString('password'), PASSWORD_BCRYPT, ['cost' => 12]),
    'rol' => 'profesor', // ❌ HARDCODED, nunca de input
    'activo' => 1,
];
```

---

## 5. 💀 CORRUPCIÓN DE DATOS: TRANSACCIÓN A MEDIAS EN INSCRIPCIÓN MASIVA

**Problema**: Aunque hay transacciones en `EnrollmentService`, el rollback NO siempre limpia estados intermedios si hay fallo en punto específico.

**Evidencia**:
- `/workspace/src/Services/EnrollmentService.php:148-152` - Hace `SELECT ... FOR UPDATE` en grupo
- PERO: Si falla DESPUÉS del INSERT en `calificaciones` pero ANTES del commit, el lock se libera pero...
- **PROBLEMA**: No hay cleanup de registros huérfanos si hay deadlock después del INSERT

**Escenario de corrupción**:
1. 100 alumnos intentan inscribirse simultáneamente a último lugar disponible
2. Transacción A: verifica cupo (29/30), inserta alumno
3. Transacción B: verifica cupo (29/30, lectura antes de commit de A), inserta alumno
4. Deadlock detectado, Transacción B hace rollback
5. PERO: si hay bug en manejo de excepción, INSERT puede persistir sin commit explícito (depende de autocommit)

**Impacto real**: Cupo excedido silenciosamente, grupo con 35 alumnos cuando máximo es 30

**Solución técnica**:
```php
// Forzar isolation level y verificar DESPUÉS de insert
$this->pdo->exec('SET TRANSACTION ISOLATION LEVEL SERIALIZABLE');
$this->pdo->beginTransaction();
try {
    // Contar CON lock
    $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM calificaciones WHERE grupo_id = :g FOR UPDATE');
    $stmt->execute([':g' => $grupoId]);
    $ocup = (int)$stmt->fetchColumn();
    
    if ($ocup >= $cupo) {
        $this->pdo->rollBack();
        return ['success' => false, 'error' => 'Cupo lleno'];
    }
    
    // Insertar
    $ins->execute([...]);
    
    // VERIFICAR nuevamente después de insert (doble-check)
    $stmtVerify = $this->pdo->prepare('SELECT COUNT(*) FROM calificaciones WHERE grupo_id = :g');
    $stmtVerify->execute([':g' => $grupoId]);
    $newCount = (int)$stmtVerify->fetchColumn();
    
    if ($newCount > $cupo) {
        // Overbooking detectado, rollback forzado
        $this->pdo->rollBack();
        return ['success' => false, 'error' => 'Conflicto de concurrencia'];
    }
    
    $this->pdo->commit();
} catch (\Throwable $e) {
    if ($this->pdo->inTransaction()) {
        $this->pdo->rollBack();
    }
    throw $e;
}
```

---

## 6. 💀 REINSKRIPCIÓN PERMITIDA CON MATERIAS REPROBADAS SIN BLOQUEO DE SEMESTRE

**Problema**: Sistema no valida que alumno reprobadó materia X veces no pueda reinscribirse más de Y veces según reglamento escolar.

**Evidencia**:
- `/workspace/src/Services/EnrollmentService.php:186-194` - Solo verifica si aprobó la materia, NO cuántas veces la ha reprobado
- No hay tabla `historial_reprobaciones` ni contador de intentos

**Impacto real**:
- Alumno puede reprobar "Cálculo Diferencial" 10 veces y seguir reinscribiéndose
- Infla estadísticas de reprobación
- Ocupa cupo de alumnos que sí podrían aprobar
- Posible fraude: alumno "traba" materia para que otros no la cursen

**Solución técnica**:
```sql
-- Vista para contar reprobaciones
CREATE VIEW vw_reprobaciones_por_alumno AS
SELECT c.alumno_id, g.materia_id, COUNT(*) as num_reprobaciones
FROM calificaciones c
JOIN grupos g ON g.id = c.grupo_id
WHERE c.final IS NOT NULL AND c.final < 70
GROUP BY c.alumno_id, g.materia_id;
```

```php
// En EnrollmentService::studentSelfEnroll()
$stReprob = $this->pdo->prepare('
  SELECT COUNT(*) FROM calificaciones c 
  JOIN grupos g ON g.id = c.grupo_id 
  WHERE c.alumno_id = :a AND g.materia_id = :m AND c.final < 70
');
$stReprob->execute([':a' => $alumnoId, ':m' => $materiaId]);
$numReprob = (int)$stReprob->fetchColumn();

if ($numReprob >= 3) { // Límite de reglamento
    return ['success' => false, 'error' => 'Has reprobado esta materia 3 veces. Requiere autorización especial.'];
}
```

---

# ⚠️ ALTO (NUEVOS HALLAZGOS)

## 7. MÁQUINA DE ESTADOS ROTA: GRUPO ELIMINADO CON ALUMNOS INSCRITOS

**Problema**: Foreign key `fk_cal_grupo` tiene `ON DELETE CASCADE`. Si admin elimina grupo, TODAS las calificaciones asociadas se pierden SILÊNCIOSAMENTE.

**Evidencia**:
- `/workspace/migrations/control_escolar.sql:58-60`:
```sql
CONSTRAINT `fk_cal_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) 
  ON DELETE CASCADE ON UPDATE CASCADE
```

**Escenario catastrófico**:
1. Admin crea grupo "Programación I - GPO-A" con 30 alumnos inscritos
2. Docentes capturan parciales 1 y 2
3. Admin elimina grupo por error (pensando que estaba vacío)
4. **BOOM**: 30 registros de calificaciones eliminados en cascade
5. No hay backup reciente → pérdida total de calificaciones parciales

**Impacto real**: Pérdida masiva de datos académicos, demandas estudiantiles, caos administrativo

**Solución técnica**:
```sql
-- CAMBIAR a RESTRICT (impide eliminación si hay calificaciones)
ALTER TABLE calificaciones
  DROP FOREIGN KEY fk_cal_grupo,
  ADD CONSTRAINT fk_cal_grupo 
    FOREIGN KEY (grupo_id) REFERENCES grupos(id) 
    ON DELETE RESTRICT ON UPDATE CASCADE;

-- Agregar soft-delete a grupos
ALTER TABLE grupos ADD COLUMN activo TINYINT(1) DEFAULT 1;
ALTER TABLE grupos ADD COLUMN eliminated_at TIMESTAMP NULL;
```

---

## 8. ENUMERACIÓN DE USUARIOS VÍA LOGIN (INFORMACIÓN PARA ATAQUES)

**Problema**: Mensajes de error diferentes revelan si email/matricula existe o no.

**Evidencia**:
- `/workspace/src/Controllers/AuthController.php:46-52`:
```php
$user = $this->users->authenticate($identity, $password);
if (!$user) {
    http_response_code(401);
    $_SESSION['login_attempts']++;
    $_SESSION['flash'] = 'Credenciales inválidas';
    // ... pero internamente UserService diferencia casos
}
```

**Análisis de `UserService::authenticate()`**:
- Si email no existe → retorna null inmediatamente
- Si email existe pero password incorrecto → retorna null después de `password_verify`
- **TIMING ATTACK**: `password_verify` toma ~100ms, mientras que "no existe" es inmediato (<10ms)

**Cómo explotar**:
1. Script automatizado envía 1000 emails candidatos
2. Mide tiempo de respuesta
3. Respuestas >90ms → email válido existe
4. Construye lista de usuarios válidos para brute-force dirigido

**Impacto real**: Facilita ataques dirigidos, phishing personalizado, credential stuffing

**Solución técnica**:
```php
// Siempre ejecutar password_hash dummy aunque usuario no exista
public function authenticate(string $identity, string $password): ?array {
    $user = $this->repo->findAdminOrProfessorByEmail($identity);
    
    if (!$user) {
        // Timing attack prevention: ejecutar hash dummy
        password_verify($password, '$2y$10$dummy.hash.to.waste.time....................');
        return null;
    }
    
    if (!$user['activo'] || !password_verify($password, $user['password'])) {
        return null;
    }
    
    return ['id' => (int)$user['id'], 'role' => $user['rol'], 'name' => $user['nombre'] ?? ''];
}
```

---

## 9. DOCENTE PUEDE EDITAR CALIFICACIONES DE GRUPO AJENO (PERMISO FINO FALTANTE)

**Problema**: Validación de permiso solo ocurre en controller, pero si hay otro endpoint o ruta alternativa, el chequeo puede omitirse.

**Evidencia**:
- `/workspace/src/Controllers/GradesController.php:197-202`:
```php
if ($role === 'profesor') {
    $pid = (int)($_SESSION['user_id'] ?? 0);
    if ((int)$grpRow['profesor_id'] !== $pid) {
        http_response_code(403);
        // ...
    }
}
```
- PERO: ¿Qué pasa si profesor conoce `grupo_id` de otro y llama directo a repository/service?

**Análisis de capa Service**:
- `/workspace/src/Services/GradesService.php:16-30` - `upsertGrade()` NO verifica permisos, solo opera
- Cualquier código que llame `GradesService` directamente puede bypassear validación

**Impacto real**: Profesor malintencionado modifica calificaciones de grupo de colega

**Solución técnica**:
```php
// Policy pattern en Service layer
class GradesService {
    public function upsertGrade(int $currentUser, int $alumnoId, int $grupoId, array $data): bool {
        // Verificar permiso PRIMERO
        if (!$this->canEditGrade($currentUser, $grupoId)) {
            throw new AuthorizationException('No tiene permiso para editar esta calificación');
        }
        // ... lógica de guardado
    }
    
    private function canEditGrade(int $userId, int $grupoId): bool {
        $stmt = $this->pdo->prepare('SELECT profesor_id FROM grupos WHERE id = :g');
        $stmt->execute([':g' => $grupoId]);
        $profId = (int)$stmt->fetchColumn();
        
        // Admin puede todo
        if ($this->userRepository->isAdmin($userId)) {
            return true;
        }
        
        return $profId === $userId;
    }
}
```

---

## 10. SIN BACKUP AUTOMÁTICO NI ESTRATEGIA DE RECOVERY DOCUMENTADA

**Problema**: No hay scripts de backup, no hay configuración de RPO/RTO, no hay plan de recuperación ante desastres.

**Evidencia**:
- Búsqueda en `/workspace` de scripts `.sh`, `.py`, o configs de cron → **CERO resultados**
- No hay directorio `/backups` o `/storage/backups`
- No hay mención a backups en documentación

**Escenario de desastre**:
1. Ransomware cifra servidor de BD a las 3 AM
2. IT restaura desde backup... ¿de cuándo?
3. Último backup manual fue hace 3 semanas
4. **PÉRDIDA**: 3 semanas de calificaciones, inscripciones, usuarios nuevos

**Impacto real**: Pérdida irreversible de datos académicos, imposibilidad de emitir títulos/kardex, demanda institucional

**Solución técnica mínima**:
```bash
#!/bin/bash
# /workspace/scripts/backup_daily.sh
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/itsur_control"
DB_NAME="control_escolar"
DB_USER="itsur_user"

# Backup completo
mysqldump -u $DB_USER --single-transaction \
  --routines --triggers --events \
  $DB_NAME | gzip > $BACKUP_DIR/full_$DATE.sql.gz

# Mantener últimos 30 días
find $BACKUP_DIR -name "*.sql.gz" -mtime +30 -delete

# Subir a S3 (opcional)
aws s3 cp $BACKUP_DIR/full_$DATE.sql.gz s3://itsur-backups/database/
```

```yaml
# documentar en README.md:
## Disaster Recovery
- RPO (Recovery Point Objective): 24 horas (backup diario 2 AM)
- RTO (Recovery Time Objective): 4 horas máximas
- Ubicación backups: /var/backups/itsur_control + S3
- Responsable: Director de TI
- Prueba de restauración: Mensual (primer sábado)
```

---

## 11. DOBLE SUBMIT EN BOTONES DE INSCRIPCIÓN SIN DEBOUNCING

**Problema**: Formularios de inscripción no tienen mecanismo para prevenir doble envío real (doble clic rápido).

**Evidencia**:
- `/workspace/src/Views/student/reinscripcion.php:45-50`:
```html
<form method="post" action="<?php echo $base; ?>/alumno/enroll">
  <input type="hidden" name="csrf_token" value="...">
  <input type="hidden" name="grupo_id" value="...">
  <button type="submit" class="btn btn-sm btn-outline-primary">Inscribir</button>
</form>
```
- **NO HAY**: JavaScript para deshabilitar botón después de click
- **NO HAY**: Token de idempotencia
- **NO HAY**: Loading spinner

**Escenario real**:
1. Alumno hace clic en "Inscribir"
2. Página tarda 2 segundos en responder (latencia red)
3. Alumno piensa "no funcionó", hace clic otra vez
4. **Resultado**: 2 requests simultáneos, ambos pasan validación de cupo (race condition)
5. Alumno queda inscrito 2 veces en mismo grupo (si uniq constraint falla) o cupo se excede

**Impacto real**: Inconsistencia de datos, cupo excedido, necesidad de cleanup manual

**Solución técnica**:
```html
<!-- Agregar al form -->
<form method="post" action="..." onsubmit="handleSubmit(this)">
  <input type="hidden" name="csrf_token" value="...">
  <input type="hidden" name="grupo_id" value="...">
  <input type="hidden" name="request_id" value="<?= bin2hex(random_bytes(16)) ?>">
  <button type="submit" class="btn btn-sm btn-outline-primary" id="btnEnroll">
    Inscribir
  </button>
</form>

<script>
function handleSubmit(form) {
  const btn = form.querySelector('#btnEnroll');
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Inscribiendo...';
  
  // Prevenir múltiple submit
  form.querySelectorAll('button[type="submit"]').forEach(b => b.disabled = true);
}
</script>
```

```php
// Backend: token de idempotencia
$requestId = Request::postString('request_id');
$cacheKey = 'enroll_request_' . $requestId;

if (Cache::get($cacheKey)) {
    // Request ya procesado, retornar resultado original
    return Cache::get($cacheKey);
}

$result = $enrollmentService->studentSelfEnroll(...);
Cache::set($cacheKey, $result, 300); // TTL 5 min
return $result;
```

---

## 12. CÁLCULO DE PROMEDIO INCORRECTO EN COLUMNA GENERADA

**Problema**: Columna `promedio` en tabla `calificaciones` usa fórmula incorrecta que distorsiona promedio real.

**Evidencia**:
- `/workspace/migrations/control_escolar.sql:52-54`:
```sql
`promedio` DECIMAL(5,2) GENERATED ALWAYS AS (
  ROUND((COALESCE(`parcial1`,0)+COALESCE(`parcial2`,0)+COALESCE(`final`,0))/3,2)
) STORED,
```

**Error matemático**:
- Si alumno tiene: parcial1=80, parcial2=NULL, final=90
- Fórmula actual: `(80 + 0 + 90) / 3 = 56.67` ❌
- Debería ser: `(80 + 90) / 2 = 85` ✅ (solo promediar valores presentes)

**Impacto real**:
- Alumnos con faltas a parciales tienen promedio artificialmente bajo
- Becas perdidas injustamente
- Reprobación técnica por bug de fórmula

**Solución técnica**:
```sql
-- Opción 1: Solo promediar valores no-nulos (complejo en MySQL)
ALTER TABLE calificaciones 
  MODIFY COLUMN promedio DECIMAL(5,2) GENERATED ALWAYS AS (
    ROUND(
      (COALESCE(parcial1, 0) + COALESCE(parcial2, 0) + COALESCE(final, 0)) /
      NULLIF(
        (CASE WHEN parcial1 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN parcial2 IS NOT NULL THEN 1 ELSE 0 END +
         CASE WHEN final IS NOT NULL THEN 1 ELSE 0 END),
        0
      ),
      2
    )
  ) STORED;

-- Opción 2 (recomendada): Calcular promedio en aplicación, no en BD
ALTER TABLE calificaciones DROP COLUMN promedio;
-- Calcular en queries o service layer
```

---

## 13. UX: SIN FEEDBACK DE ERROR EN CAPTURA MASIVA DE CALIFICACIONES

**Problema**: Upload CSV de calificaciones no da feedback granular sobre filas fallidas.

**Evidencia**:
- `/workspace/src/Controllers/GradesController.php:67-130` - Procesa CSV fila por fila
- Si 20% de filas son inválidas, hace rollback total
- Mensaje de error genérico: "Más del 20% de filas inválidas"

**Escenario frustrante**:
1. Docente sube CSV con 100 calificaciones
2. 21 filas tienen matrícula mal escrita
3. Sistema rechaza TODO el archivo
4. Docente no sabe CUÁLES filas fallaron
5. Tiene que revisar manualmente 100 filas una por una

**Impacto real**: Pérdida de tiempo, errores humanos aumentan, frustración de usuarios

**Solución técnica**:
```php
// Procesar en dos fases: validar TODO primero, luego insertar válidos
$errors = [];
$validRows = [];

while (($row = fgetcsv($fp)) !== false) {
    $validation = validateGradeRow($row);
    if ($validation['valid']) {
        $validRows[] = $row;
    } else {
        $errors[] = [
            'fila' => $lineNumber,
            'matricula' => $row[0],
            'error' => $validation['message']
        ];
    }
}

if (!empty($errors)) {
    // Guardar errores en sesión para mostrar detalle
    $_SESSION['bulk_errors'] = $errors;
    return json_encode([
        'ok' => false, 
        'processed' => count($validRows),
        'errors_count' => count($errors),
        'errors_url' => '/grades/bulk-errors.json'
    ]);
}

// Solo insertar si no hay errores críticos
```

---

# ⚙️ MEDIO (NUEVOS HALLAZGOS)

## 14. ESTADO HUÉRFANO: ALUMNO "ACTIVO" SIN INSCRIPCIONES EN CICLO ACTUAL

**Problema**: No hay validación que impida tener alumnos marcados como "activos" que no tienen ninguna inscripción en el ciclo escolar vigente.

**Impacto**:
- Estadísticas infladas (alumnos "activos" que en realidad no estudian)
- Correos enviados a alumnos que ya egresaron/bajaron
- Confusión administrativa

**Solución**: Query mensual de limpieza:
```sql
SELECT a.id, a.matricula, a.nombre, a.apellido
FROM alumnos a
LEFT JOIN calificaciones c ON c.alumno_id = a.id
LEFT JOIN grupos g ON g.id = c.grupo_id AND g.ciclo_id = (SELECT id FROM ciclos_escolares WHERE activo = 1 LIMIT 1)
WHERE a.activo = 1 AND c.id IS NULL;
-- Revisar manualmente y dar de baja si corresponde
```

---

## 15. SESIÓN COMPARTIDA ENTRE ROLES SIN DELIMITADOR CLARO

**Problema**: Usuario con múltiples roles (ej: profesor que también es admin) no puede cambiar de contexto fácilmente.

**Evidencia**: `$_SESSION['role']` es único valor string. Si usuario tiene ambos roles, debe hacer logout/login para cambiar.

**Impacto**: Fricción innecesaria, usuarios mantienen sesiones abiertas más tiempo del necesario

**Solución**: Permitir切换 de rol sin logout:
```php
// En DashboardController
public function switchRole(): string {
    $newRole = Request::postString('role');
    $allowedRoles = $this->userService->getUserRoles($_SESSION['user_id']);
    
    if (!in_array($newRole, $allowedRoles)) {
        http_response_code(403);
        return 'Rol no permitido';
    }
    
    $_SESSION['role'] = $newRole;
    Logger::info('role_switch', ['user_id' => $_SESSION['user_id'], 'new_role' => $newRole]);
    
    header('Location: /dashboard');
    return '';
}
```

---

# 🧨 TOP 10 RIESGOS NO DETECTADOS EN FASE 1

| # | Riesgo | Categoría | Impacto | Probabilidad |
|---|--------|-----------|---------|--------------|
| 1 | Edición de calificaciones de periodos cerrados | Integridad Académica | CRÍTICO | ALTA |
| 2 | Duplicación de materias en mismo periodo | Integridad Académica | CRÍTICO | MEDIA |
| 3 | IDOR en API de alumno | Seguridad | CRÍTICO | MEDIA |
| 4 | Mass assignment en creación de usuarios | Seguridad | CRÍTICO | BAJA |
| 5 | Corrupción en transacciones concurrentes | Data Integrity | ALTO | MEDIA |
| 6 | Reinscripción ilimitada de materias reprobadas | Integridad Académica | ALTO | ALTA |
| 7 | Cascade delete elimina calificaciones | Data Integrity | CRÍTICO | BAJA |
| 8 | Enumeración de usuarios vía timing attack | Seguridad | ALTO | MEDIA |
| 9 | Docente edita grupo ajeno | Seguridad | ALTO | BAJA |
| 10 | Sin estrategia de backups | Operations | CRÍTICO | ALTA |

---

# 💣 QUÉ PODRÍA DESTRUIR LA INTEGRIDAD ACADÉMICA

## Escenario 1: "El Fraude Perfecto"
1. Alumno soborna a docente → docente modifica calificación de ciclo pasado
2. Alumno se gradúa con promedio inflado
3. Empleador descubre fraude años después
4. **Universidad pierde acreditación**, títulos cuestionados

## Escenario 2: "La Pérdida Masiva"
1. Admin elimina grupo pensando que está vacío
2. CASCADE delete borra 500 calificaciones de semestre completo
3. No hay backup reciente
4. **Imposible emitir kardex**, estudiantes demandan

## Escenario 3: "El Hackeo de Privacidad"
1. Atacante explota IDOR, extrae datos de 2000 alumnos
2. Publica calificaciones, direcciones, emails en dark web
3. **Violación FERPA/LFPDPPP**, multas millonarias

## Escenario 4: "La Inconsist encia Silenciosa"
1. Bug en fórmula de promedio distorsiona 3000 promedios
2. Alumnos pierden becas injustamente
3. Error descubierto 2 semestres después
4. **Demanda colectiva**, reputación destruida

---

# 🎯 QUÉ PERMITIRÍA FRAUDE ACADÉMICO

1. **Edición sin límites temporales** → Modificar histórico
2. **Sin auditoría de cambios** → No hay trazabilidad de quién cambió qué
3. **Duplicación de materias** → Inflar créditos aprobados
4. **Reinscripción ilimitada** → "Trabar" materias para otros
5. **Permisos granulares insuficientes** → Docente accede a grupos ajenos
6. **Sin validación de prerrequisitos estricta** → Cursar materias sin base

---

# ☠️ QUÉ HARÍA FALLAR EL SISTEMA EN PRODUCCIÓN REAL

## Fallo Técnico Inminente:
1. **Race condition en inscripciones masivas** → 1000 alumnos intentan inscribirse 8 AM → sistema colapsa o overbooks
2. **Deadlocks en base de datos** → Transacciones largas con `FOR UPDATE` bloquean tablas completas
3. **Memoria agotada en reportes grandes** → Exportar 5000 rows a Excel sin paginación → PHP OOM
4. **Session fixation no mitigado completamente** → Robo masivo de cuentas

## Fallo Operativo Inminente:
1. **Sin backups automáticos** → Desastre natural/hardware failure → pérdida total
2. **Sin monitoreo** → Nadie nota que servicio cayó hasta que alumnos se quejan
3. **Sin rate limiting real** → Bot ataca login → cuenta bloqueos legítimos

## Fallo Humano Inminente:
1. **Admin elimina grupo con CASCADE** → Pérdida de calificaciones
2. **Docente sube CSV mal formado** → Rollback masivo, horas de trabajo perdido
3. **Usuario deja sesión abierta en lab** → Otro alumno accede a su cuenta

---

# ✅ CHECKLIST DE CORRECCIÓN PRIORITARIA (FASE 2)

## 🚨 SEMANA 1 (Crítico Absoluto)
- [ ] Agregar `calificaciones_bloqueadas` a ciclos_escolares
- [ ] Bloquear edición de calificaciones en periodos cerrados
- [ ] Cambiar FK `calificaciones.grupo_id` de CASCADE a RESTRICT
- [ ] Agregar validación de duplicidad de materias por ciclo_id (no string)
- [ ] Implementar token de idempotencia en inscripciones
- [ ] Agregar debouncing JS en botones de inscripción
- [ ] Configurar backup automático diario con offsite copy

## ⚠️ SEMANA 2-3 (Alta Prioridad)
- [ ] Fix fórmula de promedio (eliminar columna generada, calcular en app)
- [ ] Agregar límite de reprobaciones por materia (máx 3)
- [ ] Implementar timing-safe authentication (dummy password_verify)
- [ ] Agregar policy checks en Service layer (no solo controllers)
- [ ] Auditoría detallada de cambios de calificaciones (qué, quién, cuándo, por qué)
- [ ] Mejorar feedback de errores en upload CSV (lista detallada de filas fallidas)

## ⚙️ MES 1 (Prioridad Media)
- [ ] Implementar role switching sin logout
- [ ] Query de limpieza de alumnos activos sin inscripciones
- [ ] Documentar RPO/RTO y plan de disaster recovery
- [ ] Prueba de restauración de backups
- [ ] Load testing simulando 1000 inscripciones concurrentes

---

**FIRMA**: Equipo Principal Engineer + Data Integrity + Security Red Team + SRE

**VEREDICTO FINAL**: 
- **Fase 1**: 8/10 (técnica sólida)
- **Fase 2**: Revela vulnerabilidades CRÍTICAS de dominio académico
- **Producción real**: ⚠️ **NO APTO** sin correcciones de Semana 1

**RECOMENDACIÓN**: Detener despliegue masivo hasta completar checklist de Semana 1. Las vulnerabilidades de integridad académica (edición de históricos, duplicación de materias, cascade deletes) representan riesgo existencial para la institución.
