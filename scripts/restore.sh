#!/bin/bash
# ==========================================
# SCRIPT DE RESTORE - PRODUCCIÓN REAL
# ==========================================
# Uso: ./restore.sh <backup_file.sql.gz> [database_name]
# ==========================================

set -e  # Salir inmediatamente si hay error

DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DEFAULT_DB="control_escolar_itsur"
BACKUP_DIR="/var/backups/escolar"
LOG_FILE="/var/log/escolar/restore.log"

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

log() {
    echo -e "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

error_exit() {
    log "${RED}ERROR: $1${NC}"
    exit 1
}

success() {
    log "${GREEN}✓ $1${NC}"
}

warning() {
    log "${YELLOW}⚠ $1${NC}"
}

# Validar argumentos
if [ $# -lt 1 ]; then
    echo "Uso: $0 <backup_file.sql.gz> [database_name]"
    echo "Ejemplo: $0 /var/backups/escolar/backup_20260415_020000.sql.gz"
    exit 1
fi

BACKUP_FILE="$1"
DB_NAME="${2:-$DEFAULT_DB}"

# Verificar que el archivo existe
if [ ! -f "$BACKUP_FILE" ]; then
    error_exit "El archivo de backup no existe: $BACKUP_FILE"
fi

# Verificar que es un gzip válido
if ! gzip -t "$BACKUP_FILE" 2>/dev/null; then
    error_exit "El archivo no es un gzip válido o está corrupto"
fi

log "=========================================="
log "INICIANDO RESTORE DE BASE DE DATOS"
log "=========================================="
log "Backup: $BACKUP_FILE"
log "Database: $DB_NAME"
log "Usuario: $DB_USER"

# Crear backup de seguridad antes de restaurar
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
PRE_RESTORE_BACKUP="$BACKUP_DIR/pre_restore_${TIMESTAMP}.sql.gz"

log "Creando backup de seguridad previo al restore..."
mkdir -p "$BACKUP_DIR"

if mysqldump -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} --single-transaction --quick "$DB_NAME" 2>/dev/null | gzip > "$PRE_RESTORE_BACKUP"; then
    success "Backup de seguridad creado: $PRE_RESTORE_BACKUP"
else
    warning "No se pudo crear backup de seguridad (¿la DB existe?)"
fi

# Verificar espacio en disco
REQUIRED_SPACE=$(du -k "$BACKUP_FILE" | cut -f1)
AVAILABLE_SPACE=$(df -k /var/lib/mysql | tail -1 | awk '{print $4}')

if [ "$REQUIRED_SPACE" -gt "$AVAILABLE_SPACE" ]; then
    error_exit "Espacio en disco insuficiente. Se requieren ${REQUIRED_SPACE}KB, disponibles: ${AVAILABLE_SPACE}KB"
fi

log "Espacio en disco verificado ✓"

# Restaurar backup
log "Restaurando base de datos..."

# Decomprimir y restaurar
if gunzip -c "$BACKUP_FILE" | mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} "$DB_NAME"; then
    success "Base de datos restaurada exitosamente"
else
    error_exit "Falló la restauración de la base de datos"
fi

# Verificar integridad
log "Verificando integridad de datos..."

TABLE_COUNT=$(mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -N -e "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='$DB_NAME'" 2>/dev/null)

if [ "$TABLE_COUNT" -gt 0 ]; then
    success "Verificación completada: $TABLE_COUNT tablas encontradas"
else
    error_exit "La base de datos parece estar vacía o corrupta"
fi

# Verificar tablas críticas
CRITICAL_TABLES="alumnos docentes grupos calificaciones ciclos_escolares usuarios auditoria_academica"

for table in $CRITICAL_TABLES; do
    COUNT=$(mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -N -e "SELECT COUNT(*) FROM $DB_NAME.$table" 2>/dev/null || echo "0")
    log "  Tabla $table: $COUNT registros"
done

# Analizar y optimizar tablas
log "Optimizando tablas..."
mysql -u "$DB_USER" ${DB_PASS:+-p"$DB_PASS"} -e "ANALYZE TABLE $DB_NAME.*" 2>/dev/null || warning "No se pudo analizar todas las tablas"

log "=========================================="
success "RESTORE COMPLETADO EXITOSAMENTE"
log "=========================================="
log ""
log "Próximos pasos recomendados:"
log "1. Verificar que la aplicación funcione correctamente"
log "2. Ejecutar tests de integración"
log "3. Monitorear logs de errores durante las próximas 24h"
log ""
log "Backup previo guardado en: $PRE_RESTORE_BACKUP"
log "(Conservar por al menos 7 días antes de eliminar)"

exit 0
