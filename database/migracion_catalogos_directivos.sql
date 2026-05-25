CREATE TABLE IF NOT EXISTS zonas_directivas (
  zona_directiva_id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id BIGINT UNSIGNED NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_zd_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(empresa_id),
  UNIQUE KEY uq_zd_empresa_nombre (empresa_id, nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

CREATE TABLE IF NOT EXISTS areas_directivas (
  area_directiva_id INT AUTO_INCREMENT PRIMARY KEY,
  empresa_id BIGINT UNSIGNED NOT NULL,
  zona_directiva_id INT NOT NULL,
  nombre VARCHAR(120) NOT NULL,
  activo TINYINT(1) NOT NULL DEFAULT 1,
  creado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_ad_empresa FOREIGN KEY (empresa_id) REFERENCES empresas(empresa_id),
  CONSTRAINT fk_ad_zona FOREIGN KEY (zona_directiva_id) REFERENCES zonas_directivas(zona_directiva_id),
  UNIQUE KEY uq_ad_empresa_zona_nombre (empresa_id, zona_directiva_id, nombre),
  KEY idx_ad_zona (zona_directiva_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

INSERT INTO zonas_directivas (empresa_id, nombre)
SELECT e.empresa_id, z.nombre
FROM empresas e
JOIN (
  SELECT 'Zona blanca' AS nombre
  UNION ALL SELECT 'Zona negra'
  UNION ALL SELECT 'Zona gris'
) z
LEFT JOIN zonas_directivas zd
  ON zd.empresa_id = e.empresa_id
 AND zd.nombre = z.nombre
WHERE zd.zona_directiva_id IS NULL;

ALTER TABLE proyectos_directivos
  ADD COLUMN zona_directiva_id INT NULL AFTER nombre_directivo,
  ADD COLUMN area_directiva_id INT NULL AFTER zona_directiva_id,
  ADD KEY idx_pd_zona_directiva (zona_directiva_id),
  ADD KEY idx_pd_area_directiva (area_directiva_id);

ALTER TABLE proyectos_directivos
  ADD CONSTRAINT fk_pd_zona_directiva FOREIGN KEY (zona_directiva_id) REFERENCES zonas_directivas(zona_directiva_id),
  ADD CONSTRAINT fk_pd_area_directiva FOREIGN KEY (area_directiva_id) REFERENCES areas_directivas(area_directiva_id);

CREATE OR REPLACE VIEW vista_proyectos_directivos AS
SELECT
  pd.proyecto_directivo_id,
  pd.empresa_id,
  pd.milestone_id,
  pd.nombre_directivo,
  pd.zona_directiva_id,
  pd.area_directiva_id,
  COALESCE(zd.nombre, pd.zona) AS zona,
  ad.nombre AS area,
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
    WHEN pd.presupuesto_aprobado > 0 AND pd.gasto_real > pd.presupuesto_aprobado THEN 'rojo'
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
LEFT JOIN zonas_directivas zd ON zd.zona_directiva_id = pd.zona_directiva_id
LEFT JOIN areas_directivas ad ON ad.area_directiva_id = pd.area_directiva_id
LEFT JOIN usuarios u ON u.usuario_id = m.responsable_usuario_id
LEFT JOIN tareas t ON t.milestone_id = m.milestone_id
GROUP BY
  pd.proyecto_directivo_id,
  pd.empresa_id,
  pd.milestone_id,
  pd.nombre_directivo,
  pd.zona_directiva_id,
  pd.area_directiva_id,
  zd.nombre,
  ad.nombre,
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
