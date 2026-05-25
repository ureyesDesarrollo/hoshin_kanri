-- Tabla para gestionar dependencias entre tareas
-- Similar a MS Project, permite indicar que una tarea depende de otra

CREATE TABLE IF NOT EXISTS tarea_dependencias (
    tarea_dependencia_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tarea_id BIGINT UNSIGNED NOT NULL COMMENT 'Tarea que depende',
    tarea_dependiente_id BIGINT UNSIGNED NOT NULL COMMENT 'Tarea de la cual depende',
    tipo_relacion VARCHAR(50) DEFAULT 'FS' COMMENT 'FS=Fin-Inicio, SS=Inicio-Inicio, FF=Fin-Fin, SF=Inicio-Fin',
    dias_desfase INT DEFAULT 0 COMMENT 'Días de desfase entre tareas',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_dependencia (tarea_id, tarea_dependiente_id),
    FOREIGN KEY (tarea_id) REFERENCES tareas(tarea_id) ON DELETE CASCADE,
    FOREIGN KEY (tarea_dependiente_id) REFERENCES tareas(tarea_id) ON DELETE CASCADE,
    CHECK (tarea_id != tarea_dependiente_id),
    INDEX idx_tarea_id (tarea_id),
    INDEX idx_tarea_dependiente_id (tarea_dependiente_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabla de auditoría para cambios de dependencias (opcional pero recomendado)
CREATE TABLE IF NOT EXISTS tarea_dependencias_auditoria (
    auditoria_id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tarea_id BIGINT UNSIGNED NOT NULL,
    accion VARCHAR(50) NOT NULL COMMENT 'AGREGAR, ELIMINAR',
    tarea_dependiente_id BIGINT UNSIGNED,
    usuario_id BIGINT UNSIGNED,
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tarea_id (tarea_id),
    INDEX idx_usuario_id (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
