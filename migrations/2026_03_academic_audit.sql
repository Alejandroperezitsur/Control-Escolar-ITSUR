-- Tabla de auditoría académica y limpieza de triggers de ciclos

CREATE TABLE IF NOT EXISTS auditoria_academica (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  usuario_id INT NULL,
  accion VARCHAR(50) NOT NULL,
  entidad VARCHAR(50) NOT NULL,
  entidad_id INT NOT NULL,
  datos_anteriores JSON NULL,
  datos_nuevos JSON NULL,
  ip VARCHAR(45) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_entidad (entidad, entidad_id),
  INDEX idx_usuario (usuario_id),
  INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Eliminar triggers de ciclo activo automático para delegar a lógica de aplicación
DROP TRIGGER IF EXISTS trg_ciclos_single_active_before_insert;
DROP TRIGGER IF EXISTS trg_ciclos_single_active_before_update;

