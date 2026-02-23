-- Migración para eliminar DDL en tiempo de ejecución desde controladores

-- Horarios de grupo
CREATE TABLE IF NOT EXISTS horarios_grupo (
  id INT AUTO_INCREMENT PRIMARY KEY,
  grupo_id INT NOT NULL,
  dia VARCHAR(10) NOT NULL,
  hora_inicio TIME NOT NULL,
  hora_fin TIME NOT NULL,
  salon VARCHAR(50) DEFAULT NULL,
  INDEX idx_grupo (grupo_id),
  CONSTRAINT fk_horarios_grupo_grupo FOREIGN KEY (grupo_id)
    REFERENCES grupos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Carreras base
CREATE TABLE IF NOT EXISTS carreras (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nombre VARCHAR(120) NOT NULL,
  clave VARCHAR(20) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Columnas de carrera en tablas existentes
ALTER TABLE materias ADD COLUMN IF NOT EXISTS carrera_id INT NULL;
ALTER TABLE grupos ADD COLUMN IF NOT EXISTS carrera_id INT NULL;

-- Campos adicionales en carreras
ALTER TABLE carreras ADD COLUMN IF NOT EXISTS descripcion TEXT AFTER nombre;
ALTER TABLE carreras ADD COLUMN IF NOT EXISTS duracion_semestres INT DEFAULT 9 AFTER descripcion;
ALTER TABLE carreras ADD COLUMN IF NOT EXISTS creditos_totales INT DEFAULT 240 AFTER duracion_semestres;
ALTER TABLE carreras ADD COLUMN IF NOT EXISTS activo TINYINT(1) DEFAULT 1 AFTER creditos_totales;

