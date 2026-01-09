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

/**
 * Semana domingo-sábado (por fecha_fin)
 */
$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');

if ($desde === '' || $hasta === '') {
  $today = new DateTime('today');
  $dow = (int)$today->format('w');         // 0=Domingo..6=Sábado
  $start = (clone $today)->modify("-{$dow} days"); // domingo
  $end   = (clone $start)->modify("+6 days");      // sábado

  $desde = $start->format('Y-m-d');
  $hasta = $end->format('Y-m-d');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
  echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido (YYYY-MM-DD)']);
  exit;
}

$sql = "
INSERT INTO kpi_responsable_semanal (
  empresa_id, usuario_id,
  semana_inicio, semana_fin,
  total_tareas,
  cumplidas_a_tiempo,
  vencidas_no_cumplidas,
  completadas_tarde,
  porcentaje
)
SELECT
  ? AS empresa_id,
  x.usuario_id,
  ? AS semana_inicio,
  ? AS semana_fin,

  /* total real */
  COUNT(x.tarea_id) AS total_tareas,

  /* a tiempo */
  SUM(CASE
    WHEN x.tarea_id IS NOT NULL
     AND x.completada = 1
     AND x.completada_en IS NOT NULL
     AND DATE(x.completada_en) <= x.fecha_fin
    THEN 1 ELSE 0
  END) AS cumplidas_a_tiempo,

  /* vencidas abiertas */
  SUM(CASE
    WHEN x.tarea_id IS NOT NULL
     AND x.completada = 0
     AND x.fecha_fin < CURDATE()
    THEN 1 ELSE 0
  END) AS vencidas_no_cumplidas,

  /* completadas tarde (cuentan como vencidas) */
  SUM(CASE
    WHEN x.tarea_id IS NOT NULL
     AND x.completada = 1
     AND x.completada_en IS NOT NULL
     AND DATE(x.completada_en) > x.fecha_fin
    THEN 1 ELSE 0
  END) AS completadas_tarde,

  /* KPI: 100% si no hay fallas; baja por vencidas abiertas + completadas tarde */
  CASE
    WHEN COUNT(x.tarea_id) = 0 THEN 0
    ELSE ROUND(
      (
        (COUNT(x.tarea_id) - (
          SUM(CASE
            WHEN x.tarea_id IS NOT NULL
             AND x.completada = 0
             AND x.fecha_fin < CURDATE()
            THEN 1 ELSE 0
          END)
          +
          SUM(CASE
            WHEN x.tarea_id IS NOT NULL
             AND x.completada = 1
             AND x.completada_en IS NOT NULL
             AND DATE(x.completada_en) > x.fecha_fin
            THEN 1 ELSE 0
          END)
        )) / COUNT(x.tarea_id)
      ) * 100
    , 0)
  END AS porcentaje

FROM (
  SELECT DISTINCT
    u.usuario_id,
    t.tarea_id,
    t.fecha_fin,
    t.completada,
    t.completada_en
  FROM usuarios u
  JOIN usuarios_empresas ue ON ue.usuario_id = u.usuario_id
  JOIN roles r ON r.rol_id = ue.rol_id

  JOIN estrategias e
    ON e.responsable_usuario_id = u.usuario_id
   AND e.empresa_id = ue.empresa_id

  LEFT JOIN milestones m
    ON m.estrategia_id = e.estrategia_id

  LEFT JOIN tareas t
    ON t.milestone_id = m.milestone_id
   AND t.fecha_fin BETWEEN ? AND ?

  WHERE ue.empresa_id = ?
    AND ue.activo = 1
    AND r.nombre = 'GERENTE'
) x
GROUP BY x.usuario_id

ON DUPLICATE KEY UPDATE
  semana_fin = VALUES(semana_fin),
  total_tareas = VALUES(total_tareas),
  cumplidas_a_tiempo = VALUES(cumplidas_a_tiempo),
  vencidas_no_cumplidas = VALUES(vencidas_no_cumplidas),
  completadas_tarde = VALUES(completadas_tarde),
  porcentaje = VALUES(porcentaje),
  generado_en = CURRENT_TIMESTAMP
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
  exit;
}

$stmt->bind_param('issssi', $empresaId, $desde, $hasta, $desde, $hasta, $empresaId);

$ok = $stmt->execute();
if (!$ok) {
  echo json_encode(['success' => false, 'message' => 'Error execute: ' . $stmt->error]);
  exit;
}

echo json_encode([
  'success' => true,
  'message' => 'KPI semanal generado/actualizado',
  'range' => ['desde' => $desde, 'hasta' => $hasta]
], JSON_UNESCAPED_UNICODE);
exit;
