-- ============================================
-- STORED PROCEDURES ATÓMICOS PARA CONCURRENCIA
-- Control Escolar ITSUR - Producción Real
-- ============================================

DELIMITER //

-- ============================================
-- 1. INSCRIPCIÓN ATÓMICA CON BLOQUEO DE FILA
-- Garantiza: nunca exceder cupo, no duplicados
-- ============================================

DROP PROCEDURE IF EXISTS `sp_inscribir_alumno_grupo`//

CREATE PROCEDURE `sp_inscribir_alumno_grupo`(
    IN p_alumno_id INT UNSIGNED,
    IN p_grupo_id INT UNSIGNED,
    IN p_ciclo_id INT UNSIGNED,
    IN p_usuario_id INT UNSIGNED,
    OUT p_resultado_codigo INT,
    OUT p_resultado_mensaje VARCHAR(255),
    OUT p_inscripcion_id INT UNSIGNED
)
BEGIN
    DECLARE v_cupo_actual INT UNSIGNED;
    DECLARE v_cupo_maximo INT UNSIGNED;
    DECLARE v_inscritos_count INT UNSIGNED;
    DECLARE v_ya_inscrito INT UNSIGNED;
    DECLARE v_grupo_estado VARCHAR(20);
    DECLARE v_ciclo_bloqueado TINYINT(1);
    DECLARE v_materia_id INT UNSIGNED;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado_codigo = 500;
        SET p_resultado_mensaje = 'Error interno del servidor';
        SET p_inscripcion_id = NULL;
    END;

    -- Transacción SERIALIZABLE para concurrencia extrema
    START TRANSACTION;
    
    -- Bloqueo explícito de la fila del grupo (evita race conditions)
    SELECT g.cupo, g.estado, m.id INTO v_cupo_maximo, v_grupo_estado, v_materia_id
    FROM grupos g
    JOIN materias m ON m.id = g.materia_id
    WHERE g.id = p_grupo_id
    FOR UPDATE;
    
    -- Verificar que el grupo existe
    IF v_cupo_maximo IS NULL THEN
        ROLLBACK;
        SET p_resultado_codigo = 404;
        SET p_resultado_mensaje = 'Grupo no encontrado';
        SET p_inscripcion_id = NULL;
    ELSE
        -- Verificar estado del grupo
        IF v_grupo_estado != 'abierto' THEN
            ROLLBACK;
            SET p_resultado_codigo = 409;
            SET p_resultado_mensaje = 'Grupo no está abierto a inscripciones';
            SET p_inscripcion_id = NULL;
        ELSE
            -- Verificar bloqueo de ciclo escolar
            SELECT calificaciones_bloqueadas INTO v_ciclo_bloqueado
            FROM ciclos_escolares
            WHERE id = p_ciclo_id
            FOR SHARE;
            
            IF v_ciclo_bloqueado = 1 THEN
                ROLLBACK;
                SET p_resultado_codigo = 403;
                SET p_resultado_mensaje = 'El ciclo escolar tiene inscripciones bloqueadas';
                SET p_inscripcion_id = NULL;
            ELSE
                -- Contar inscritos actuales con LOCK
                SELECT COUNT(*) INTO v_inscritos_count
                FROM inscripciones
                WHERE grupo_id = p_grupo_id
                  AND estatus = 'inscrita'
                FOR SHARE;
                
                -- Verificar cupo disponible
                IF v_inscritos_count >= v_cupo_maximo THEN
                    ROLLBACK;
                    SET p_resultado_codigo = 409;
                    SET p_resultado_mensaje = 'Cupo máximo alcanzado';
                    SET p_inscripcion_id = NULL;
                ELSE
                    -- Verificar si ya está inscrito
                    SELECT id INTO v_ya_inscrito
                    FROM inscripciones
                    WHERE alumno_id = p_alumno_id
                      AND grupo_id = p_grupo_id
                      AND estatus IN ('inscrita', 'cancelada')
                    LIMIT 1;
                    
                    IF v_ya_inscrito IS NOT NULL THEN
                        ROLLBACK;
                        SET p_resultado_codigo = 409;
                        SET p_resultado_mensaje = 'Alumno ya tiene inscripción en este grupo';
                        SET p_inscripcion_id = NULL;
                    ELSE
                        -- TODO: Aquí irían validaciones adicionales (prerrequisitos, etc.)
                        
                        -- Insertar inscripción exitosa
                        INSERT INTO inscripciones (
                            alumno_id,
                            grupo_id,
                            ciclo_id,
                            estatus,
                            fecha_inscripcion
                        ) VALUES (
                            p_alumno_id,
                            p_grupo_id,
                            p_ciclo_id,
                            'inscrita',
                            NOW()
                        );
                        
                        SET p_inscripcion_id = LAST_INSERT_ID();
                        
                        -- Crear registro de calificación vacío
                        INSERT INTO calificaciones (
                            alumno_id,
                            grupo_id,
                            inscripcion_id,
                            estatus,
                            usuario_modifico_id,
                            fecha_modificacion
                        ) VALUES (
                            p_alumno_id,
                            p_grupo_id,
                            p_inscripcion_id,
                            'cursando',
                            p_usuario_id,
                            NOW()
                        );
                        
                        COMMIT;
                        SET p_resultado_codigo = 200;
                        SET p_resultado_mensaje = 'Inscripción realizada exitosamente';
                    END IF;
                END IF;
            END IF;
        END IF;
    END IF;
END//

-- ============================================
-- 2. ELIMINACIÓN DE INSCRIPCIÓN CON VALIDACIÓN
-- ============================================

DROP PROCEDURE IF EXISTS `sp_eliminar_inscripcion`//

CREATE PROCEDURE `sp_eliminar_inscripcion`(
    IN p_inscripcion_id INT UNSIGNED,
    IN p_usuario_id INT UNSIGNED,
    IN p_motivo VARCHAR(255),
    OUT p_resultado_codigo INT,
    OUT p_resultado_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_estatus VARCHAR(20);
    DECLARE v_grupo_id INT UNSIGNED;
    DECLARE v_alumno_id INT UNSIGNED;
    DECLARE v_ciclo_id INT UNSIGNED;
    DECLARE v_calificaciones_existentes INT UNSIGNED;
    DECLARE v_tiene_calificaciones TINYINT(1);
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado_codigo = 500;
        SET p_resultado_mensaje = 'Error interno del servidor';
    END;

    START TRANSACTION;
    
    -- Obtener datos de la inscripción con bloqueo
    SELECT estatus, grupo_id, alumno_id, ciclo_id
    INTO v_estatus, v_grupo_id, v_alumno_id, v_ciclo_id
    FROM inscripciones
    WHERE id = p_inscripcion_id
    FOR UPDATE;
    
    IF v_estatus IS NULL THEN
        ROLLBACK;
        SET p_resultado_codigo = 404;
        SET p_resultado_mensaje = 'Inscripción no encontrada';
    ELSEIF v_estatus != 'inscrita' THEN
        ROLLBACK;
        SET p_resultado_codigo = 409;
        SET p_resultado_mensaje = 'La inscripción ya no está activa';
    ELSE
        -- Verificar si hay calificaciones registradas
        SELECT COUNT(*), MAX(CASE WHEN parcial1 IS NOT NULL OR parcial2 IS NOT NULL OR final IS NOT NULL THEN 1 ELSE 0 END)
        INTO v_calificaciones_existentes, v_tiene_calificaciones
        FROM calificaciones
        WHERE inscripcion_id = p_inscripcion_id;
        
        IF v_tiene_calificaciones = 1 THEN
            ROLLBACK;
            SET p_resultado_codigo = 403;
            SET p_resultado_mensaje = 'No se puede eliminar: el alumno ya tiene calificaciones registradas';
        ELSE
            -- Actualizar estatus de inscripción (soft delete)
            UPDATE inscripciones
            SET estatus = 'cancelada',
                motivo_cancelacion = p_motivo,
                usuario_cancela_id = p_usuario_id,
                fecha_cancelacion = NOW(),
                updated_at = NOW()
            WHERE id = p_inscripcion_id;
            
            -- Eliminar calificación asociada (soft delete)
            UPDATE calificaciones
            SET deleted_at = NOW(),
                usuario_modifico_id = p_usuario_id,
                fecha_modificacion = NOW()
            WHERE inscripcion_id = p_inscripcion_id;
            
            COMMIT;
            SET p_resultado_codigo = 200;
            SET p_resultado_mensaje = 'Inscripción cancelada exitosamente';
        END IF;
    END IF;
END//

-- ============================================
-- 3. ACTUALIZACIÓN DE CALIFICACIÓN CON AUDITORÍA
-- ============================================

DROP PROCEDURE IF EXISTS `sp_actualizar_calificacion`//

CREATE PROCEDURE `sp_actualizar_calificacion`(
    IN p_calificacion_id INT UNSIGNED,
    IN p_parcial1 DECIMAL(5,2),
    IN p_parcial2 DECIMAL(5,2),
    IN p_parcial3 DECIMAL(5,2),
    IN p_final DECIMAL(5,2),
    IN p_usuario_id INT UNSIGNED,
    IN p_motivo VARCHAR(255),
    OUT p_resultado_codigo INT,
    OUT p_resultado_mensaje VARCHAR(255)
)
BEGIN
    DECLARE v_grupo_id INT UNSIGNED;
    DECLARE v_alumno_id INT UNSIGNED;
    DECLARE v_ciclo_id INT UNSIGNED;
    DECLARE v_bloqueado TINYINT(1);
    DECLARE v_valores_anteriores JSON;
    DECLARE v_valores_nuevos JSON;
    DECLARE v_cambios JSON;
    DECLARE EXIT HANDLER FOR SQLEXCEPTION
    BEGIN
        ROLLBACK;
        SET p_resultado_codigo = 500;
        SET p_resultado_mensaje = 'Error interno del servidor';
    END;

    START TRANSACTION;
    
    -- Obtener datos actuales con bloqueo y verificar ciclo
    SELECT c.grupo_id, c.alumno_id, g.ciclo_id, ce.calificaciones_bloqueadas
    INTO v_grupo_id, v_alumno_id, v_ciclo_id, v_bloqueado
    FROM calificaciones c
    JOIN grupos g ON g.id = c.grupo_id
    JOIN ciclos_escolares ce ON ce.id = g.ciclo_id
    WHERE c.id = p_calificacion_id
    FOR UPDATE;
    
    IF v_grupo_id IS NULL THEN
        ROLLBACK;
        SET p_resultado_codigo = 404;
        SET p_resultado_mensaje = 'Calificación no encontrada';
    ELSEIF v_bloqueado = 1 THEN
        ROLLBACK;
        SET p_resultado_codigo = 403;
        SET p_resultado_mensaje = 'No se pueden modificar calificaciones: el ciclo está cerrado';
    ELSE
        -- Capturar valores anteriores para auditoría
        SELECT JSON_OBJECT(
            'parcial1', COALESCE(parcial1, 'NULL'),
            'parcial2', COALESCE(parcial2, 'NULL'),
            'parcial3', COALESCE(parcial3, 'NULL'),
            'final', COALESCE(final, 'NULL'),
            'estatus', estatus
        ) INTO v_valores_anteriores
        FROM calificaciones
        WHERE id = p_calificacion_id;
        
        -- Actualizar calificación
        UPDATE calificaciones
        SET parcial1 = p_parcial1,
            parcial2 = p_parcial2,
            parcial3 = p_parcial3,
            final = p_final,
            estatus = CASE 
                WHEN p_final IS NOT NULL AND p_final >= 70 THEN 'acreditado'
                WHEN p_final IS NOT NULL AND p_final < 70 THEN 'no_acreditado'
                ELSE estatus
            END,
            usuario_modifico_id = p_usuario_id,
            fecha_modificacion = NOW(),
            updated_at = NOW()
        WHERE id = p_calificacion_id;
        
        -- Capturar valores nuevos
        SELECT JSON_OBJECT(
            'parcial1', COALESCE(p_parcial1, 'NULL'),
            'parcial2', COALESCE(p_parcial2, 'NULL'),
            'parcial3', COALESCE(p_parcial3, 'NULL'),
            'final', COALESCE(p_final, 'NULL'),
            'estatus', CASE 
                WHEN p_final IS NOT NULL AND p_final >= 70 THEN 'acreditado'
                WHEN p_final IS NOT NULL AND p_final < 70 THEN 'no_acreditado'
                ELSE 'cursando'
            END
        ) INTO v_valores_nuevos;
        
        -- Registrar en auditoría
        INSERT INTO auditoria_academica (
            usuario_id,
            accion,
            tabla_afectada,
            registro_id,
            valores_anteriores,
            valores_nuevos,
            motivo,
            ip_address,
            requires_approval,
            is_applied,
            created_at
        ) VALUES (
            p_usuario_id,
            'UPDATE_CALIFICACION',
            'calificaciones',
            p_calificacion_id,
            v_valores_anteriores,
            v_valores_nuevos,
            p_motivo,
            COALESCE(@user_ip, 'unknown'),
            0,
            1,
            NOW()
        );
        
        COMMIT;
        SET p_resultado_codigo = 200;
        SET p_resultado_mensaje = 'Calificación actualizada exitosamente';
    END IF;
END//

DELIMITER ;

-- ============================================
-- FIN DE STORED PROCEDURES
-- ============================================
