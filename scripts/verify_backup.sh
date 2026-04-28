#!/bin/bash
# ==========================================
# VERIFICACIÓN AUTOMÁTICA DE BACKUPS
# ==========================================
# Verifica que los backups sean válidos y restaurables
# Ejecutar diariamente vía cron
# ==========================================

set -e

DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DEFAULT_DB="control_escolar_itsur"
BACKUP_DIR="/var/backups/escolar"
LOG_FILE="/var/log/escolar/backup_verify.log"
ALERT_EMAIL="${ALERT_EMAIL:-admin@itsur.edu.mx}"

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

log() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

error_exit() {
    log "${RED}ERROR: $1${NC}"
    # Enviar alerta por email si está configurado
    if [ -n "$ALERT_EMAIL" ]; then
        echo "ERROR en verificación de backup: $1" | mail -s "[ALERTA] Backup ITSUR Fallido" "$ALERT_EMAIL"
    fi
    exit 1
}

success() {
    log "${GREEN}✓ $1${NC}"
}

mkdir -p "$(dirname $LOG_FILE)"

log "=========================================="
log "INICIANDO VERIFICACIÓN DE BACKUPS"
log "=========================================="

# Verificar que existe el directorio de backups
if [ ! -d "$BACKUP_DIR" ]; then
    error_exit "El directorio de backups no existe: $BACKUP_DIR"
fi

# Encontrar el backup más reciente
LATEST_BACKUP=$(find "$BACKUP_DIR" -name "backup_*.sql.gz" -type f -printf '%T@ %p\n' 2>/dev/null | sort -n | tail -1 | cut -d' ' -f2-)

if [ -z "$LATEST_BACKUP" ]; then
    error_exit "No se encontraron backups en $BACKUP_DIR"
fi

success "Backup más reciente: $LATEST_BACKUP"

# Verificar edad del backup (no mayor a 25 horas)
BACKUP_AGE=$(( $(date +%s) - $(stat -c %Y "$LATEST_BACKUP") ))
MAX_AGE=90000  # 25 horas en segundos

if [ "$BACKUP_AGE" -gt "$MAX_AGE" ]; then
    HOURS=$(( BACKUP_AGE / 3600 ))
    error_exit "El backup tiene más de 25 horas de antigüedad (${HOURS}h)"
fi

success "Edad del backup: $(( BACKUP_AGE / 3600 ))h"

# Verificar integridad del gzip
log "Verificando integridad del archivo..."
if ! gzip -t "$LATEST_BACKUP" 2>/dev/null; then
    error_exit "El backup está corrupto (gzip inválido)"
fi

success "Integridad del archivo verificada"

# Verificar tamaño mínimo (al menos 1KB)
MIN_SIZE=1024
ACTUAL_SIZE=$(stat -c %s "$LATEST_BACKUP")

if [ "$ACTUAL_SIZE" -lt "$MIN_SIZE" ]; then
    error_exit "El backup es demasiado pequeño (${ACTUAL_SIZE} bytes)"
fi

success "Tamaño del backup: ${ACTUAL_SIZE} bytes"

# Crear base de datos temporal para test de restore
TEST_DB="test_restore_$$"
TEMP_DIR="/tmp/backup_test_$$"

mkdir -p "$TEMP_DIR"

log "Creando entorno de prueba temporal..."

# Extraer backup a archivo temporal
gunzip -c "$LATEST_BACKUP" > "$TEMP_DIR/test.sql"

# Crear database temporal
mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "CREATE DATABASE IF NOT EXISTS $TEST_DB" 2>/dev/null

# Restaurar en database temporal
log "Restaurando backup en entorno de prueba..."
if ! mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$TEST_DB" < "$TEMP_DIR/test.sql" 2>/dev/null; then
    mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "DROP DATABASE IF EXISTS $TEST_DB" 2>/dev/null
    rm -rf "$TEMP_DIR"
    error_exit "Falló la restauración de prueba"
fi

success "Restauración de prueba completada"

# Verificar tablas críticas
log "Verificando tablas críticas..."
CRITICAL_TABLES="alumnos docentes grupos calificaciones ciclos_escolares usuarios auditoria_academica idempotency_keys"

TABLE_ERRORS=0
for table in $CRITICAL_TABLES; do
    COUNT=$(mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -N -e "SELECT COUNT(*) FROM $TEST_DB.$table" 2>/dev/null || echo "-1")
    
    if [ "$COUNT" = "-1" ]; then
        log "${RED}✗ Tabla $table: NO EXISTE${NC}"
        TABLE_ERRORS=$((TABLE_ERRORS + 1))
    else
        log "  Tabla $table: $COUNT registros"
    fi
done

if [ "$TABLE_ERRORS" -gt 0 ]; then
    mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "DROP DATABASE IF EXISTS $TEST_DB" 2>/dev/null
    rm -rf "$TEMP_DIR"
    error_exit "$TABLE_ERRORS tablas críticas faltantes o inaccesibles"
fi

success "Todas las tablas críticas verificadas"

# Verificar integridad referencial básica
log "Verificando integridad referencial..."
FK_CHECK=$(mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -N -e "
    SELECT COUNT(*) 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE CONSTRAINT_SCHEMA='$TEST_DB' 
    AND CONSTRAINT_TYPE='FOREIGN KEY'
" 2>/dev/null)

if [ "$FK_CHECK" -lt 5 ]; then
    log "${YELLOW}⚠ Advertencia: Solo se encontraron $FK_CHECK foreign keys${NC}"
else
    success "Integridad referencial verificada ($FK_CHECK FKs)"
fi

# Limpiar entorno de prueba
log "Limpiando entorno de prueba..."
mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "DROP DATABASE IF EXISTS $TEST_DB" 2>/dev/null
rm -rf "$TEMP_DIR"

success "Entorno de prueba limpiado"

# Verificar cadena de backups (últimos 7 días)
log "Verificando continuidad de backups (últimos 7 días)..."
DAYS_WITH_BACKUP=0

for i in {0..6}; do
    DATE_CHECK=$(date -d "$i days ago" +%Y%m%d)
    BACKUP_COUNT=$(find "$BACKUP_DIR" -name "backup_${DATE_CHECK}_*.sql.gz" -type f | wc -l)
    
    if [ "$BACKUP_COUNT" -gt 0 ]; then
        DAYS_WITH_BACKUP=$((DAYS_WITH_BACKUP + 1))
    fi
done

if [ "$DAYS_WITH_BACKUP" -lt 5 ]; then
    log "${YELLOW}⚠ Advertencia: Solo hay backups de $DAYS_WITH_BACKUP de los últimos 7 días${NC}"
else
    success "Continuidad de backups verificada ($DAYS_WITH_BACKUP/7 días)"
fi

log "=========================================="
success "VERIFICACIÓN COMPLETADA EXITOSAMENTE"
log "=========================================="
log ""
log "Resumen:"
log "  - Backup más reciente: $(basename $LATEST_BACKUP)"
log "  - Edad: $(( BACKUP_AGE / 3600 ))h"
log "  - Tamaño: ${ACTUAL_SIZE} bytes"
log "  - Tablas críticas: OK"
log "  - Integridad: OK"

exit 0
