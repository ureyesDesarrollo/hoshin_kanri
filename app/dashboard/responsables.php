<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);

if ($empresaId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
  exit;
}

$sql = "
SELECT
  u.usuario_id,
  u.nombre_completo,
  r.nombre AS rol,
  a.area_id,
  a.nombre AS area_nombre,

  /* =========================
     KPIs (EN VIVO) SOLO LO NECESARIO
  ========================= */

  -- total de actividades ligadas a estrategias donde es responsable
  COALESCE(COUNT(DISTINCT t.tarea_id), 0) AS total_tareas,

  -- a tiempo (estatus=4 o completada_en <= fecha_fin)
  COALESCE(COUNT(DISTINCT CASE
    WHEN (t.estatus = 4)
      OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
    THEN t.tarea_id
  END), 0) AS tareas_finalizadas,

  -- completadas tarde (estatus 6 o completada_en > fecha_fin)
  COALESCE(COUNT(DISTINCT CASE
    WHEN (t.estatus = 6)
      OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
    THEN t.tarea_id
  END), 0) AS tareas_completadas_tarde,

  -- vencidas abiertas (no cerradas y ya vencieron)
  COALESCE(COUNT(DISTINCT CASE
    WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
    THEN t.tarea_id
  END), 0) AS tareas_vencidas_abiertas,

  -- vencidas total = vencidas abiertas + completadas tarde
  (
    COALESCE(COUNT(DISTINCT CASE
      WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
      THEN t.tarea_id
    END), 0)
    +
    COALESCE(COUNT(DISTINCT CASE
      WHEN (t.estatus = 6)
        OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
      THEN t.tarea_id
    END), 0)
  ) AS tareas_vencidas_total,

  -- % general (en vivo): a_tiempo / (a_tiempo + vencidas_abiertas + completadas_tarde)
  CASE
    WHEN (
      COALESCE(COUNT(DISTINCT CASE
        WHEN (t.estatus = 4)
          OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
        THEN t.tarea_id
      END), 0)
      +
      COALESCE(COUNT(DISTINCT CASE
        WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
        THEN t.tarea_id
      END), 0)
      +
      COALESCE(COUNT(DISTINCT CASE
        WHEN (t.estatus = 6)
          OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
        THEN t.tarea_id
      END), 0)
    ) = 0 THEN 0
    ELSE ROUND(
      (
        COALESCE(COUNT(DISTINCT CASE
          WHEN (t.estatus = 4)
            OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
          THEN t.tarea_id
        END), 0)
        /
        (
          COALESCE(COUNT(DISTINCT CASE
            WHEN (t.estatus = 4)
              OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
            THEN t.tarea_id
          END), 0)
          +
          COALESCE(COUNT(DISTINCT CASE
            WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
            THEN t.tarea_id
          END), 0)
          +
          COALESCE(COUNT(DISTINCT CASE
            WHEN (t.estatus = 6)
              OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
            THEN t.tarea_id
          END), 0)
        )
      ) * 100
    , 0)
  END AS porcentaje_general,

  /* =========================
     KPI SEMANAL (REGISTRADO)
     Sale de kpi_responsable_semanal
  ========================= */
  ks.semana_inicio,
  ks.semana_fin,
  COALESCE(ks.total_tareas, 0) AS total_tareas_semana,
  COALESCE(ks.cumplidas_a_tiempo, 0) AS cumplidas_a_tiempo_semana,
  COALESCE(ks.vencidas_no_cumplidas, 0) AS vencidas_semana,
  COALESCE(ks.completadas_tarde, 0) AS completadas_tarde_semana,
  COALESCE(ks.porcentaje, 0) AS porcentaje_semanal

FROM usuarios u
JOIN usuarios_empresas ue ON ue.usuario_id = u.usuario_id
JOIN roles r ON r.rol_id = ue.rol_id
LEFT JOIN areas a ON a.area_id = ue.area_id

/* tareas ligadas a estrategias donde el usuario es responsable */
LEFT JOIN estrategias e
  ON e.responsable_usuario_id = u.usuario_id
 AND e.empresa_id = ue.empresa_id
LEFT JOIN milestones m ON m.estrategia_id = e.estrategia_id
LEFT JOIN tareas t ON t.milestone_id = m.milestone_id

/* KPI semanal registrado */
LEFT JOIN kpi_responsable_semanal ks
  ON ks.empresa_id = ue.empresa_id
 AND ks.usuario_id = u.usuario_id
 AND ks.semana_inicio = DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 1) DAY)
 AND ks.semana_fin   = DATE_ADD(
        DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 1) DAY),
        INTERVAL 6 DAY
      )

WHERE ue.empresa_id = ?
  AND ue.activo = 1
  AND r.nombre = 'GERENTE'

GROUP BY u.usuario_id
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt->bind_param('i', $empresaId);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
  'success' => true,
  'data' => $data
], JSON_UNESCAPED_UNICODE);
exit;
