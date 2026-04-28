-- ==========================================
-- MIGRACIÓN: IDEMPOTENCIA Y RATE LIMITING
-- ==========================================

-- Tabla para idempotencia de requests (evita doble submit real)
CREATE TABLE IF NOT EXISTS idempotency_keys (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_key VARCHAR(64) NOT NULL UNIQUE,
    user_id INT NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    response_data JSON NULL,
    status_code INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    INDEX idx_request_key (request_key),
    INDEX idx_user_expires (user_id, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para cola de inscripciones (throttling)
CREATE TABLE IF NOT EXISTS enrollment_queue (
    id INT AUTO_INCREMENT PRIMARY KEY,
    alumno_id INT NOT NULL,
    grupo_id INT NOT NULL,
    request_id VARCHAR(64) NOT NULL UNIQUE,
    status ENUM('pending', 'processing', 'completed', 'failed') DEFAULT 'pending',
    result_message VARCHAR(255) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (alumno_id) REFERENCES alumnos(id),
    FOREIGN KEY (grupo_id) REFERENCES grupos(id),
    INDEX idx_status_created (status, created_at),
    INDEX idx_request_id (request_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Tabla para rate limiting
CREATE TABLE IF NOT EXISTS rate_limit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    ip_address VARCHAR(45) NOT NULL,
    endpoint VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_endpoint_time (user_id, endpoint, created_at),
    INDEX idx_ip_time (ip_address, created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- MIGRACIÓN: AUDITORÍA NO MANIPULABLE
-- ==========================================

-- Workflow de aprobación dual para cambios críticos
ALTER TABLE auditoria_academica 
ADD COLUMN requires_approval TINYINT(1) DEFAULT 0,
ADD COLUMN approval_status ENUM('pending', 'approved', 'rejected') DEFAULT NULL,
ADD COLUMN approved_by INT NULL,
ADD COLUMN approved_at TIMESTAMP NULL,
ADD COLUMN rejection_reason VARCHAR(255) NULL,
ADD COLUMN is_applied TINYINT(1) DEFAULT 0,
ADD FOREIGN KEY (approved_by) REFERENCES usuarios(id);

-- Tabla de logs inmutables (append-only)
CREATE TABLE IF NOT EXISTS audit_log_immutable (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    log_hash VARCHAR(64) NOT NULL UNIQUE,
    previous_hash VARCHAR(64) NULL,
    usuario_id INT NOT NULL,
    accion VARCHAR(50) NOT NULL,
    tabla_afectada VARCHAR(50) NOT NULL,
    registro_id INT NOT NULL,
    valores_anteriores JSON NOT NULL,
    valores_nuevos JSON NOT NULL,
    motivo VARCHAR(255) NULL,
    ip_address VARCHAR(45) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_log_hash (log_hash),
    INDEX idx_previous_hash (previous_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- CAMBIOS ADICIONALES DE INTEGRIDAD
-- ==========================================

-- Asegurar que deleted_at sea consistente
UPDATE alumnos SET deleted_at = NOW() WHERE activo = 0 AND deleted_at IS NULL;
UPDATE grupos SET deleted_at = NOW() WHERE estado = 'inactivo' AND deleted_at IS NULL;

-- Verificar índices críticos
CREATE INDEX IF NOT EXISTS idx_alumnos_deleted ON alumnos(deleted_at);
CREATE INDEX IF NOT EXISTS idx_grupos_deleted ON grupos(deleted_at);
CREATE INDEX IF NOT EXISTS idx_calificaciones_deleted ON calificaciones(deleted_at);
