CREATE TABLE IF NOT EXISTS tipos_proyecto_directivos (
  tipo_proyecto_directivo_id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_tpd_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(empresa_id),
  UNIQUE KEY uq_tpd_empresa_nombre (empresa_id, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO tipos_proyecto_directivos (empresa_id, nombre)
SELECT e.empresa_id, t.nombre
FROM empresas e
JOIN (
  SELECT 'Inversión' AS nombre
  UNION ALL SELECT 'Mejora continua'
  UNION ALL SELECT 'Cumplimiento'
  UNION ALL SELECT 'Infraestructura'
  UNION ALL SELECT 'Ahorro'
  UNION ALL SELECT 'Sistemas'
) t
LEFT JOIN tipos_proyecto_directivos tpd
  ON tpd.empresa_id = e.empresa_id
 AND tpd.nombre = t.nombre
WHERE tpd.tipo_proyecto_directivo_id IS NULL;

ALTER TABLE proyectos_directivos
  ADD COLUMN tipo_proyecto_directivo_id INT NULL AFTER area_directiva_id,
  ADD KEY idx_pd_tipo_proyecto_directivo (tipo_proyecto_directivo_id);

ALTER TABLE proyectos_directivos
  ADD CONSTRAINT fk_pd_tipo_proyecto_directivo
  FOREIGN KEY (tipo_proyecto_directivo_id) REFERENCES tipos_proyecto_directivos(tipo_proyecto_directivo_id);

UPDATE proyectos_directivos pd
JOIN tipos_proyecto_directivos tpd
  ON tpd.empresa_id = pd.empresa_id
 AND tpd.nombre = pd.tipo_proyecto
SET pd.tipo_proyecto_directivo_id = tpd.tipo_proyecto_directivo_id
WHERE pd.tipo_proyecto IS NOT NULL
  AND pd.tipo_proyecto <> ''
  AND pd.tipo_proyecto_directivo_id IS NULL;

CREATE OR REPLACE VIEW vista_proyectos_directivos AS
SELECT
  pd.proyecto_directivo_id,
  pd.empresa_id,
  pd.milestone_id,
  pd.nombre_directivo,
  pd.zona_directiva_id,
  pd.area_directiva_id,
  pd.tipo_proyecto_directivo_id,
  COALESCE(zd.nombre, pd.zona) AS zona,
  ad.nombre AS area,
  COALESCE(tpd.nombre, pd.tipo_proyecto) AS tipo_proyecto,
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
  ar.area_id AS responsable_area_id,
  ar.nombre AS responsable_area,
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
    WHEN pd.presupuesto_aprobado > 0 AND pd.gasto_real > pd.presupuesto_aprobado THEN 'rojo'
    WHEN pd.presupuesto_aprobado > 0 AND (pd.gasto_real / pd.presupuesto_aprobado) >= 0.80 THEN 'amarillo'
    ELSE 'verde'
  END AS semaforo_presupuesto,
  CASE
    WHEN pd.estado_directivo = 'Cerrado' OR m.estatus = 2 THEN 'verde'
    WHEN COUNT(DISTINCT CASE WHEN t.completada = 0 AND t.fecha_fin < CURDATE() THEN t.tarea_id END) > 0 THEN 'rojo'
    WHEN COALESCE(pd.fecha_fin_objetivo, MAX(t.fecha_fin)) IS NOT NULL AND DATEDIFF(COALESCE(pd.fecha_fin_objetivo, MAX(t.fecha_fin)), CURDATE()) <= 7 THEN 'amarillo'
    ELSE 'verde'
  END AS semaforo_tiempo
FROM proyectos_directivos pd
JOIN milestones m ON m.milestone_id = pd.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id AND e.empresa_id = pd.empresa_id
LEFT JOIN zonas_directivas zd ON zd.zona_directiva_id = pd.zona_directiva_id
LEFT JOIN areas_directivas ad ON ad.area_directiva_id = pd.area_directiva_id
LEFT JOIN tipos_proyecto_directivos tpd ON tpd.tipo_proyecto_directivo_id = pd.tipo_proyecto_directivo_id
LEFT JOIN usuarios u ON u.usuario_id = m.responsable_usuario_id
LEFT JOIN usuarios_empresas ue_resp
  ON ue_resp.usuario_id = u.usuario_id
 AND ue_resp.empresa_id = pd.empresa_id
 AND ue_resp.activo = 1
LEFT JOIN areas ar ON ar.area_id = ue_resp.area_id
LEFT JOIN tareas t ON t.milestone_id = m.milestone_id
GROUP BY
  pd.proyecto_directivo_id,
  pd.empresa_id,
  pd.milestone_id,
  pd.nombre_directivo,
  pd.zona_directiva_id,
  pd.area_directiva_id,
  pd.tipo_proyecto_directivo_id,
  zd.nombre,
  ad.nombre,
  tpd.nombre,
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
  u.nombre_completo,
  ar.area_id,
  ar.nombre;
