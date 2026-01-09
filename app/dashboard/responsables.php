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
     TAREAS (en vivo) - opcional mantener
     Nota: con DISTINCT para evitar duplicados por joins
  ========================= */
  COALESCE(COUNT(DISTINCT t.tarea_id), 0) AS total_tareas,

  COALESCE(COUNT(DISTINCT CASE WHEN t.completada = 1 THEN t.tarea_id END), 0) AS tareas_finalizadas,

  COALESCE(COUNT(DISTINCT CASE
    WHEN t.completada = 0 AND t.fecha_fin >= CURDATE()
    THEN t.tarea_id END), 0) AS tareas_pendientes,

  COALESCE(COUNT(DISTINCT CASE
    WHEN t.completada = 0 AND t.fecha_fin < CURDATE()
    THEN t.tarea_id END), 0) AS tareas_vencidas,

  /* =========================
     KPI SEMANAL (semana actual) - ya con tu enfoque
  ========================= */
  ks.semana_inicio,
  ks.semana_fin,
  COALESCE(ks.total_tareas, 0) AS total_tareas_semana,
  COALESCE(ks.vencidas_no_cumplidas, 0) AS vencidas_semana,
  COALESCE(ks.completadas_tarde, 0) AS completadas_tarde_semana,
  COALESCE(ks.porcentaje, 0) AS porcentaje_cumplimiento,  -- ESTE ES EL KPI SEMANAL

  /* =========================
     KPI GENERAL (solo del año actual, ponderado y por fallas)
  ========================= */
  COALESCE(kg.porcentaje_general, 0) AS porcentaje_general,
  COALESCE(kg.total_tareas_hist, 0) AS total_tareas_hist,
  COALESCE(kg.fallas_hist, 0) AS fallas_hist

FROM usuarios u
JOIN usuarios_empresas ue ON ue.usuario_id = u.usuario_id
JOIN roles r ON r.rol_id = ue.rol_id
LEFT JOIN areas a ON a.area_id = ue.area_id

/* en vivo */
LEFT JOIN estrategias e
  ON e.responsable_usuario_id = u.usuario_id
 AND e.empresa_id = ue.empresa_id
LEFT JOIN milestones m ON m.estrategia_id = e.estrategia_id
LEFT JOIN tareas t ON t.milestone_id = m.milestone_id

/* semana actual domingo-sábado */
LEFT JOIN kpi_responsable_semanal ks
  ON ks.empresa_id = ue.empresa_id
 AND ks.usuario_id = u.usuario_id
 AND ks.semana_inicio = DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 1) DAY)
 AND ks.semana_fin   = DATE_ADD(DATE_SUB(CURDATE(), INTERVAL (DAYOFWEEK(CURDATE()) - 1) DAY), INTERVAL 6 DAY)

/* general del año actual (ponderado por fallas) */
LEFT JOIN (
  SELECT
    empresa_id,
    usuario_id,
    SUM(total_tareas) AS total_tareas_hist,
    SUM(vencidas_no_cumplidas + completadas_tarde) AS fallas_hist,
    CASE
      WHEN SUM(total_tareas) = 0 THEN 0
      ELSE ROUND(
        ((SUM(total_tareas) - SUM(vencidas_no_cumplidas + completadas_tarde)) / SUM(total_tareas)) * 100
      , 0)
    END AS porcentaje_general
  FROM kpi_responsable_semanal
  WHERE semana_inicio <> '0000-00-00'
    AND YEAR(semana_inicio) = YEAR(CURDATE())
  GROUP BY empresa_id, usuario_id
) kg
  ON kg.empresa_id = ue.empresa_id
 AND kg.usuario_id = u.usuario_id

WHERE ue.empresa_id = ?
  AND ue.activo = 1
  AND r.nombre = 'GERENTE'

/* IMPORTANTE: si hay múltiples tareas en vivo, agrupar solo por usuario para no duplicar filas */
GROUP BY u.usuario_id

ORDER BY porcentaje_cumplimiento ASC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
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
