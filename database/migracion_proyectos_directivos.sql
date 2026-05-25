CREATE TABLE IF NOT EXISTS proyectos_directivos (
  proyecto_directivo_id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id BIGINT UNSIGNED NOT NULL,
  milestone_id BIGINT UNSIGNED NOT NULL,
  nombre_directivo VARCHAR(180) NOT NULL,
  zona VARCHAR(120) NULL,
  tipo_proyecto VARCHAR(120) NULL,
  prioridad_directiva ENUM('Baja','Media','Alta','Critica') NOT NULL DEFAULT 'Media',
  estado_directivo ENUM('En evaluacion','Aprobado','En ejecucion','Pausado','Cerrado','Cancelado') NOT NULL DEFAULT 'En evaluacion',
  requiere_reporte_direccion TINYINT(1) NOT NULL DEFAULT 1,
  visible_en_direccion TINYINT(1) NOT NULL DEFAULT 1,
  inversion_estimada DECIMAL(14,2) NOT NULL DEFAULT 0,
  presupuesto_aprobado DECIMAL(14,2) NOT NULL DEFAULT 0,
  gasto_real DECIMAL(14,2) NOT NULL DEFAULT 0,
  beneficio_estimado DECIMAL(14,2) NOT NULL DEFAULT 0,
  beneficio_real DECIMAL(14,2) NOT NULL DEFAULT 0,
  fecha_inicio_directiva DATE NULL,
  fecha_fin_objetivo DATE NULL,
  notas_directivas TEXT NULL,
  creado_por BIGINT UNSIGNED NULL,
  actualizado_por BIGINT UNSIGNED NULL,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_pd_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(empresa_id),
  CONSTRAINT fk_pd_milestone FOREIGN KEY (milestone_id) REFERENCES milestones(milestone_id),
  CONSTRAINT fk_pd_creado_por FOREIGN KEY (creado_por) REFERENCES usuarios(usuario_id),
  CONSTRAINT fk_pd_actualizado_por FOREIGN KEY (actualizado_por) REFERENCES usuarios(usuario_id),
  UNIQUE KEY uq_pd_empresa_milestone (empresa_id, milestone_id),
  KEY idx_pd_empresa_visible (empresa_id, visible_en_direccion),
  KEY idx_pd_prioridad (prioridad_directiva),
  KEY idx_pd_estado (estado_directivo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS historial_prioridad_directiva (
  historial_id INT AUTO_INCREMENT PRIMARY KEY,
  proyecto_directivo_id INT NOT NULL,
  prioridad_anterior VARCHAR(40) NULL,
  prioridad_nueva VARCHAR(40) NOT NULL,
  motivo VARCHAR(255) NULL,
  comentarios TEXT NULL,
  cambiado_por BIGINT UNSIGNED NULL,
  cambiado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_hpd_proyecto FOREIGN KEY (proyecto_directivo_id) REFERENCES proyectos_directivos(proyecto_directivo_id) ON DELETE CASCADE,
  CONSTRAINT fk_hpd_usuario FOREIGN KEY (cambiado_por) REFERENCES usuarios(usuario_id),
  KEY idx_hpd_proyecto_fecha (proyecto_directivo_id, cambiado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE OR REPLACE VIEW vista_proyectos_directivos AS
SELECT
  pd.proyecto_directivo_id,
  pd.empresa_id,
  pd.milestone_id,
  pd.nombre_directivo,
  pd.zona,
  pd.tipo_proyecto,
  pd.prioridad_directiva,
  pd.estado_directivo,
  pd.requiere_reporte_direccion,
  pd.visible_en_direccion,
  pd.inversion_estimada,
  pd.presupuesto_aprobado,
  pd.gasto_real,
  pd.beneficio_estimado,
  pd.beneficio_real,
  pd.fecha_inicio_directiva,
  pd.fecha_fin_objetivo,
  pd.notas_directivas,
  m.titulo AS milestone,
  m.estatus AS milestone_estatus,
  e.estrategia_id,
  e.titulo AS estrategia,
  u.nombre_completo AS responsable,
  MIN(COALESCE(t.fecha_inicio, DATE(t.creado_en), t.fecha_fin)) AS fecha_inicio_operativa,
  MAX(t.fecha_fin) AS fecha_fin_operativa,
  COUNT(DISTINCT t.tarea_id) AS total_tareas,
  COUNT(DISTINCT CASE WHEN t.completada = 1 THEN t.tarea_id END) AS tareas_finalizadas,
  COUNT(DISTINCT CASE WHEN t.completada = 0 AND t.fecha_fin < CURDATE() THEN t.tarea_id END) AS tareas_vencidas,
  COUNT(DISTINCT CASE WHEN t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin THEN t.tarea_id END) AS tareas_completadas_tarde,
  CASE
    WHEN COUNT(DISTINCT t.tarea_id) = 0 THEN 0
    ELSE ROUND((COUNT(DISTINCT CASE WHEN t.completada = 1 THEN t.tarea_id END) / COUNT(DISTINCT t.tarea_id)) * 100, 2)
  END AS avance_real,
  CASE
    WHEN pd.presupuesto_aprobado <= 0 THEN 0
    ELSE ROUND(((pd.beneficio_estimado - pd.presupuesto_aprobado) / pd.presupuesto_aprobado) * 100, 2)
  END AS roi_estimado,
  CASE
    WHEN pd.gasto_real > pd.presupuesto_aprobado AND pd.presupuesto_aprobado > 0 THEN 'rojo'
    WHEN pd.presupuesto_aprobado > 0 AND (pd.gasto_real / pd.presupuesto_aprobado) >= 0.80 THEN 'amarillo'
    ELSE 'verde'
  END AS semaforo_presupuesto,
  CASE
    WHEN COUNT(DISTINCT CASE WHEN t.completada = 0 AND t.fecha_fin < CURDATE() THEN t.tarea_id END) > 0 THEN 'rojo'
    WHEN COALESCE(pd.fecha_fin_objetivo, MAX(t.fecha_fin)) IS NOT NULL AND DATEDIFF(COALESCE(pd.fecha_fin_objetivo, MAX(t.fecha_fin)), CURDATE()) <= 7 THEN 'amarillo'
    ELSE 'verde'
  END AS semaforo_tiempo
FROM proyectos_directivos pd
JOIN milestones m ON m.milestone_id = pd.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id AND e.empresa_id = pd.empresa_id
LEFT JOIN usuarios u ON u.usuario_id = m.responsable_usuario_id
LEFT JOIN tareas t ON t.milestone_id = m.milestone_id
GROUP BY
  pd.proyecto_directivo_id,
  pd.empresa_id,
  pd.milestone_id,
  pd.nombre_directivo,
  pd.zona,
  pd.tipo_proyecto,
  pd.prioridad_directiva,
  pd.estado_directivo,
  pd.requiere_reporte_direccion,
  pd.visible_en_direccion,
  pd.inversion_estimada,
  pd.presupuesto_aprobado,
  pd.gasto_real,
  pd.beneficio_estimado,
  pd.beneficio_real,
  pd.fecha_inicio_directiva,
  pd.fecha_fin_objetivo,
  pd.notas_directivas,
  m.titulo,
  m.estatus,
  e.estrategia_id,
  e.titulo,
  u.nombre_completo;
