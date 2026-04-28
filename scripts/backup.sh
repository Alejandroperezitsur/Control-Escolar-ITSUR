#!/bin/bash
# ==========================================
# SCRIPT DE BACKUP AUTOMATICO - CONTROL ESCOLAR ITSUR
# Configuracion para produccion
# ==========================================

DB_USER="${DB_USER:-root}"
DB_PASS="${DB_PASS:-}"
DB_NAME="${DB_NAME:-control_escolar_itsur}"
DB_HOST="${DB_HOST:-localhost}"
BACKUP_DIR="/var/backups/escolar"
RETENTION_DAYS=30
DATE=$(date +%Y%m%d_%H%M%S)

# Crear directorio si no existe
mkdir -p "$BACKUP_DIR"

echo "[$(date)] Iniciando backup de $DB_NAME..."

# Backup completo con transaccion consistente
mysqldump -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" \
    --single-transaction \
    --quick \
    --lock-tables=false \
    --routines \
    --triggers \
    --events \
    "$DB_NAME" | gzip > "$BACKUP_DIR/backup_$DATE.sql.gz"

if [ $? -eq 0 ]; then
    echo "[$(date)] Backup creado exitosamente: backup_$DATE.sql.gz" >> "$BACKUP_DIR/backup.log"
    
    # Verificar integridad del archivo
    if gzip -t "$BACKUP_DIR/backup_$DATE.sql.gz" 2>/dev/null; then
        SIZE=$(ls -lh "$BACKUP_DIR/backup_$DATE.sql.gz" | awk '{print $5}')
        echo "[$(date)] Integridad verificada. Tamaño: $SIZE" >> "$BACKUP_DIR/backup.log"
    else
        echo "[$(date)] ERROR: Archivo corrupto" >> "$BACKUP_DIR/backup.log"
        rm "$BACKUP_DIR/backup_$DATE.sql.gz"
        exit 1
    fi
else
    echo "[$(date)] ERROR en mysqldump" >> "$BACKUP_DIR/backup.log"
    exit 1
fi

# Limpieza de backups antiguos (retencion)
find "$BACKUP_DIR" -name "backup_*.sql.gz" -mtime +$RETENTION_DAYS -delete
echo "[$(date)] Backups antiguos eliminados (retencion: $RETENTION_DAYS dias)" >> "$BACKUP_DIR/backup.log"

# Copia a ubicacion remota (opcional, descomentar si aplica)
# rsync -avz "$BACKUP_DIR/backup_$DATE.sql.gz" user@remote:/backups/escolar/

echo "Backup completado exitosamente"
exit 0
