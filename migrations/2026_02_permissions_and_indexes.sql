CREATE TABLE IF NOT EXISTS permisos (
  id INT AUTO_INCREMENT PRIMARY KEY,
  clave VARCHAR(100) NOT NULL UNIQUE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS rol_permiso (
  rol VARCHAR(50) NOT NULL,
  permiso_id INT NOT NULL,
  PRIMARY KEY (rol, permiso_id),
  CONSTRAINT fk_rol_permiso_permiso FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO permisos (clave)
SELECT v.clave FROM (
  SELECT 'view_dashboard' AS clave UNION ALL
  SELECT 'view_reports' UNION ALL
  SELECT 'export_reports' UNION ALL
  SELECT 'manage_students' UNION ALL
  SELECT 'manage_subjects' UNION ALL
  SELECT 'manage_groups' UNION ALL
  SELECT 'edit_grades' UNION ALL
  SELECT 'bulk_upload_grades' UNION ALL
  SELECT 'view_professor_kpis' UNION ALL
  SELECT 'view_student_panel' UNION ALL
  SELECT 'self_enroll' UNION ALL
  SELECT 'self_unenroll' UNION ALL
  SELECT 'manage_careers' UNION ALL
  SELECT 'manage_cycles' UNION ALL
  SELECT 'manage_professors' UNION ALL
  SELECT 'admin_settings' UNION ALL
  SELECT 'view_health' 
) v
LEFT JOIN permisos p ON p.clave = v.clave
WHERE p.id IS NULL;

INSERT IGNORE INTO rol_permiso (rol, permiso_id)
SELECT 'admin', p.id FROM permisos p;

INSERT IGNORE INTO rol_permiso (rol, permiso_id)
SELECT 'profesor', p.id FROM permisos p WHERE p.clave IN (
  'view_dashboard',
  'view_reports',
  'export_reports',
  'edit_grades',
  'bulk_upload_grades',
  'view_professor_kpis'
);

INSERT IGNORE INTO rol_permiso (rol, permiso_id)
SELECT 'alumno', p.id FROM permisos p WHERE p.clave IN (
  'view_student_panel',
  'self_enroll',
  'self_unenroll'
);

CREATE INDEX IF NOT EXISTS idx_calificaciones_grupo_alumno ON calificaciones(grupo_id, alumno_id);
CREATE INDEX IF NOT EXISTS idx_calificaciones_alumno_grupo ON calificaciones(alumno_id, grupo_id);

CREATE INDEX IF NOT EXISTS idx_auditoria_entidad_fecha ON auditoria_academica(entidad, created_at);

