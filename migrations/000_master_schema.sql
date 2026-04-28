-- ============================================
-- MIGRACIÓN MAESTRA: SCHEMA COMPLETO DE PRODUCCIÓN
-- Control Escolar ITSUR - Versión 2026 Producción Real
-- ============================================
-- Esta migración REEMPLAZA todas las anteriores
-- Ejecutar en base de datos limpia o después de DROP TABLE
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = '+00:00';

-- ============================================
-- 1. TABLAS BASE (sin dependencias)
-- ============================================

-- Usuarios: ÚNICO punto de autenticación para TODOS
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `email` VARCHAR(100) NOT NULL,
  `password_hash` VARCHAR(255) NOT NULL,
  `rol` ENUM('admin', 'profesor', 'alumno') NOT NULL DEFAULT 'alumno',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `last_login` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuarios_email` (`email`),
  KEY `idx_usuarios_rol_activo` (`rol`, `activo`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Carreras
CREATE TABLE IF NOT EXISTS `carreras` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `clave` VARCHAR(20) NOT NULL,
  `descripcion` TEXT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_carreras_clave` (`clave`),
  KEY `idx_carreras_activo` (`activo`, `deleted_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ciclos Escolares
CREATE TABLE IF NOT EXISTS `ciclos_escolares` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(20) NOT NULL,
  `fecha_inicio` DATE NOT NULL,
  `fecha_fin` DATE NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 0,
  `calificaciones_bloqueadas` TINYINT(1) NOT NULL DEFAULT 0,
  `fecha_cierre_calificaciones` DATETIME NULL,
  `motivo_cierre` VARCHAR(255) NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_ciclos_nombre` (`nombre`),
  KEY `idx_ciclos_activo` (`activo`),
  KEY `idx_ciclos_fechas` (`fecha_inicio`, `fecha_fin`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Materias
CREATE TABLE IF NOT EXISTS `materias` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `clave` VARCHAR(20) NOT NULL,
  `creditos` INT UNSIGNED NOT NULL DEFAULT 8,
  `semestre` INT UNSIGNED NULL,
  `tipo` ENUM('obligatoria', 'optativa', 'electiva') NOT NULL DEFAULT 'obligatoria',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_materias_clave` (`clave`),
  KEY `idx_materias_activo` (`activo`, `deleted_at`),
  KEY `idx_materias_semestre` (`semestre`, `tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Prerrequisitos de materias
CREATE TABLE IF NOT EXISTS `materias_prerrequisitos` (
  `materia_id` INT UNSIGNED NOT NULL,
  `prerrequisito_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`materia_id`, `prerrequisito_id`),
  CONSTRAINT `fk_mpre_materia` FOREIGN KEY (`materia_id`) REFERENCES `materias`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mpre_prerrequisito` FOREIGN KEY (`prerrequisito_id`) REFERENCES `materias`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Relación materias-carreras
CREATE TABLE IF NOT EXISTS `materias_carrera` (
  `materia_id` INT UNSIGNED NOT NULL,
  `carrera_id` INT UNSIGNED NOT NULL,
  `orden_curricular` INT UNSIGNED NOT NULL,
  `obligatoria` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`materia_id`, `carrera_id`),
  CONSTRAINT `fk_mc_materia` FOREIGN KEY (`materia_id`) REFERENCES `materias`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_mc_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 2. PERFILES DE USUARIO (relacionados con usuarios)
-- ============================================

-- Profesores: EXTENSIÓN de usuario
CREATE TABLE IF NOT EXISTS `profesores` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido_paterno` VARCHAR(50) NOT NULL,
  `apellido_materno` VARCHAR(50) NULL,
  `telefono` VARCHAR(20) NULL,
  `especialidad` VARCHAR(100) NULL,
  `fecha_contratacion` DATE NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_profesores_user_id` (`user_id`),
  CONSTRAINT `fk_profesores_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `idx_profesores_activo` (`activo`, `deleted_at`),
  KEY `idx_profesores_nombre` (`apellido_paterno`, `apellido_materno`, `nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Alumnos: EXTENSIÓN de usuario
CREATE TABLE IF NOT EXISTS `alumnos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `carrera_id` INT UNSIGNED NULL,
  `matricula` VARCHAR(20) NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `apellido_paterno` VARCHAR(50) NOT NULL,
  `apellido_materno` VARCHAR(50) NULL,
  `fecha_nacimiento` DATE NULL,
  `genero` ENUM('M', 'F', 'O') NULL,
  `telefono` VARCHAR(20) NULL,
  `direccion` TEXT NULL,
  `foto_url` VARCHAR(255) NULL,
  `fecha_ingreso` DATE NOT NULL,
  `estatus` ENUM('activo', 'baja_temporal', 'baja_definitiva', 'egresado') NOT NULL DEFAULT 'activo',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_alumnos_user_id` (`user_id`),
  UNIQUE KEY `uk_alumnos_matricula` (`matricula`),
  CONSTRAINT `fk_alumnos_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_alumnos_carrera` FOREIGN KEY (`carrera_id`) REFERENCES `carreras`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  KEY `idx_alumnos_matricula` (`matricula`),
  KEY `idx_alumnos_activo` (`activo`, `estatus`, `deleted_at`),
  KEY `idx_alumnos_busqueda` (`apellido_paterno`, `apellido_materno`, `nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. GRUPOS Y HORARIOS
-- ============================================

-- Grupos
CREATE TABLE IF NOT EXISTS `grupos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `materia_id` INT UNSIGNED NOT NULL,
  `profesor_id` INT UNSIGNED NOT NULL,
  `ciclo_id` INT UNSIGNED NOT NULL,
  `nombre` VARCHAR(50) NOT NULL,
  `cupo` INT UNSIGNED NOT NULL DEFAULT 30,
  `horario` VARCHAR(100) NULL,
  `aula` VARCHAR(50) NULL,
  `estado` ENUM('abierto', 'cerrado', 'cancelado') NOT NULL DEFAULT 'abierto',
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_grupos_materia` FOREIGN KEY (`materia_id`) REFERENCES `materias`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_grupos_profesor` FOREIGN KEY (`profesor_id`) REFERENCES `profesores`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_grupos_ciclo` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos_escolares`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  KEY `idx_grupos_materia_ciclo` (`materia_id`, `ciclo_id`),
  KEY `idx_grupos_profesor_ciclo` (`profesor_id`, `ciclo_id`, `activo`),
  KEY `idx_grupos_estado` (`estado`, `activo`, `deleted_at`),
  CONSTRAINT `chk_grupos_cupo` CHECK (`cupo` > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. INSCRIPCIONES (tabla intermedia oficial)
-- ============================================

CREATE TABLE IF NOT EXISTS `inscripciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alumno_id` INT UNSIGNED NOT NULL,
  `grupo_id` INT UNSIGNED NOT NULL,
  `ciclo_id` INT UNSIGNED NOT NULL,
  `fecha_inscripcion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `estatus` ENUM('inscrita', 'cancelada', 'baja') NOT NULL DEFAULT 'inscrita',
  `motivo_cancelacion` VARCHAR(255) NULL,
  `usuario_cancela_id` INT UNSIGNED NULL,
  `fecha_cancelacion` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_inscripciones_alumno_grupo_ciclo` (`alumno_id`, `grupo_id`, `ciclo_id`),
  CONSTRAINT `fk_insc_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_insc_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_insc_ciclo` FOREIGN KEY (`ciclo_id`) REFERENCES `ciclos_escolares`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_insc_usuario_cancela` FOREIGN KEY (`usuario_cancela_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  KEY `idx_inscripciones_alumno_ciclo` (`alumno_id`, `ciclo_id`, `estatus`),
  KEY `idx_inscripciones_grupo` (`grupo_id`, `estatus`),
  KEY `idx_inscripciones_ciclo` (`ciclo_id`, `estatus`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. CALIFICACIONES (UNA SOLA FUENTE DE VERDAD)
-- ============================================

CREATE TABLE IF NOT EXISTS `calificaciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alumno_id` INT UNSIGNED NOT NULL,
  `grupo_id` INT UNSIGNED NOT NULL,
  `inscripcion_id` INT UNSIGNED NULL,
  `parcial1` DECIMAL(5,2) NULL DEFAULT NULL,
  `parcial2` DECIMAL(5,2) NULL DEFAULT NULL,
  `parcial3` DECIMAL(5,2) NULL DEFAULT NULL,
  `final` DECIMAL(5,2) NULL DEFAULT NULL,
  `ordinario` DECIMAL(5,2) NULL DEFAULT NULL,
  `extraordinario` DECIMAL(5,2) NULL DEFAULT NULL,
  `estatus` ENUM('cursando', 'acreditado', 'no_acreditado', 'abandono') NOT NULL DEFAULT 'cursando',
  `observaciones` TEXT NULL,
  `usuario_modifico_id` INT UNSIGNED NULL,
  `fecha_modificacion` TIMESTAMP NULL DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `deleted_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_calificaciones_alumno_grupo` (`alumno_id`, `grupo_id`),
  CONSTRAINT `fk_cal_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cal_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_cal_inscripcion` FOREIGN KEY (`inscripcion_id`) REFERENCES `inscripciones`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `fk_cal_usuario_modifico` FOREIGN KEY (`usuario_modifico_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  KEY `idx_calificaciones_alumno_estatus` (`alumno_id`, `estatus`, `deleted_at`),
  KEY `idx_calificaciones_grupo` (`grupo_id`, `estatus`),
  KEY `idx_calificaciones_reportes` (`alumno_id`, `grupo_id`, `estatus`, `deleted_at`),
  CONSTRAINT `chk_cal_parcial1` CHECK (`parcial1` IS NULL OR (`parcial1` >= 0 AND `parcial1` <= 100)),
  CONSTRAINT `chk_cal_parcial2` CHECK (`parcial2` IS NULL OR (`parcial2` >= 0 AND `parcial2` <= 100)),
  CONSTRAINT `chk_cal_parcial3` CHECK (`parcial3` IS NULL OR (`parcial3` >= 0 AND `parcial3` <= 100)),
  CONSTRAINT `chk_cal_final` CHECK (`final` IS NULL OR (`final` >= 0 AND `final` <= 100)),
  CONSTRAINT `chk_cal_ordinario` CHECK (`ordinario` IS NULL OR (`ordinario` >= 0 AND `ordinario` <= 100)),
  CONSTRAINT `chk_cal_extraordinario` CHECK (`extraordinario` IS NULL OR (`extraordinario` >= 0 AND `extraordinario` <= 100))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. AUDITORÍA Y SEGURIDAD
-- ============================================

-- Auditoría académica inmutable
CREATE TABLE IF NOT EXISTS `auditoria_academica` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id` INT UNSIGNED NOT NULL,
  `accion` VARCHAR(50) NOT NULL,
  `tabla_afectada` VARCHAR(50) NOT NULL,
  `registro_id` INT UNSIGNED NOT NULL,
  `valores_anteriores` JSON NOT NULL,
  `valores_nuevos` JSON NOT NULL,
  `motivo` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `requires_approval` TINYINT(1) NOT NULL DEFAULT 0,
  `approval_status` ENUM('pending', 'approved', 'rejected') NULL DEFAULT NULL,
  `approved_by` INT UNSIGNED NULL,
  `approved_at` TIMESTAMP NULL DEFAULT NULL,
  `rejection_reason` VARCHAR(255) NULL,
  `is_applied` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_aud_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `fk_aud_approved_by` FOREIGN KEY (`approved_by`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
  KEY `idx_auditoria_tabla_registro` (`tabla_afectada`, `registro_id`),
  KEY `idx_auditoria_usuario_accion` (`usuario_id`, `accion`, `created_at`),
  KEY `idx_auditoria_approval` (`requires_approval`, `approval_status`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Logs inmutables (blockchain-like)
CREATE TABLE IF NOT EXISTS `audit_log_immutable` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `log_hash` VARCHAR(64) NOT NULL,
  `previous_hash` VARCHAR(64) NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `accion` VARCHAR(50) NOT NULL,
  `tabla_afectada` VARCHAR(50) NOT NULL,
  `registro_id` INT UNSIGNED NOT NULL,
  `valores_anteriores` JSON NOT NULL,
  `valores_nuevos` JSON NOT NULL,
  `motivo` VARCHAR(255) NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_audit_log_hash` (`log_hash`),
  KEY `idx_audit_previous_hash` (`previous_hash`),
  KEY `idx_audit_usuario` (`usuario_id`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Idempotencia de requests
CREATE TABLE IF NOT EXISTS `idempotency_keys` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `request_key` VARCHAR(64) NOT NULL,
  `user_id` INT UNSIGNED NOT NULL,
  `endpoint` VARCHAR(100) NOT NULL,
  `response_data` JSON NULL,
  `status_code` INT UNSIGNED NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `expires_at` TIMESTAMP NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_idempotency_request_key` (`request_key`),
  KEY `idx_idempotency_user_expires` (`user_id`, `expires_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cola de inscripciones (throttling)
CREATE TABLE IF NOT EXISTS `enrollment_queue` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `alumno_id` INT UNSIGNED NOT NULL,
  `grupo_id` INT UNSIGNED NOT NULL,
  `request_id` VARCHAR(64) NOT NULL,
  `status` ENUM('pending', 'processing', 'completed', 'failed') NOT NULL DEFAULT 'pending',
  `result_message` VARCHAR(255) NULL,
  `error_details` TEXT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `processed_at` TIMESTAMP NULL DEFAULT NULL,
  `processed_by` INT UNSIGNED NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_enrollment_request_id` (`request_id`),
  CONSTRAINT `fk_eq_alumno` FOREIGN KEY (`alumno_id`) REFERENCES `alumnos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_eq_grupo` FOREIGN KEY (`grupo_id`) REFERENCES `grupos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `idx_enrollment_status_created` (`status`, `created_at`),
  KEY `idx_enrollment_alumno` (`alumno_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rate limiting
CREATE TABLE IF NOT EXISTS `rate_limit_log` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `endpoint` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_rate_user_endpoint_time` (`user_id`, `endpoint`, `created_at`),
  KEY `idx_rate_ip_time` (`ip_address`, `created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sesiones activas (para invalidación forzada)
CREATE TABLE IF NOT EXISTS `sesiones_activas` (
  `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  `user_id` INT UNSIGNED NOT NULL,
  `session_id` VARCHAR(128) NOT NULL,
  `csrf_token` VARCHAR(64) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `user_agent` VARCHAR(255) NULL,
  `last_activity` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `invalidated` TINYINT(1) NOT NULL DEFAULT 0,
  `invalidated_at` TIMESTAMP NULL DEFAULT NULL,
  `motivo_invalidacion` VARCHAR(255) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_sesiones_session_id` (`session_id`),
  CONSTRAINT `fk_sesiones_usuario` FOREIGN KEY (`user_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY `idx_sesiones_user_invalidated` (`user_id`, `invalidated`, `last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. PERMISOS Y ROLES
-- ============================================

CREATE TABLE IF NOT EXISTS `permisos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(100) NOT NULL,
  `descripcion` TEXT NULL,
  `modulo` VARCHAR(50) NOT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_permisos_nombre` (`nombre`),
  KEY `idx_permisos_modulo` (`modulo`, `activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `roles` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(50) NOT NULL,
  `descripcion` TEXT NULL,
  `activo` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_roles_nombre` (`nombre`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rol_permiso` (
  `rol_id` INT UNSIGNED NOT NULL,
  `permiso_id` INT UNSIGNED NOT NULL,
  PRIMARY KEY (`rol_id`, `permiso_id`),
  CONSTRAINT `fk_rp_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos`(`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `usuario_rol` (
  `usuario_id` INT UNSIGNED NOT NULL,
  `rol_id` INT UNSIGNED NOT NULL,
  `asignado_por` INT UNSIGNED NULL,
  `fecha_asignacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usuario_id`, `rol_id`),
  CONSTRAINT `fk_ur_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ur_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles`(`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_ur_asignado` FOREIGN KEY (`asignado_por`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- FIN DE LA MIGRACIÓN MAESTRA
-- ============================================
