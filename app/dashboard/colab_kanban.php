<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);

if ($empresaId <= 0 || $usuarioId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
  exit;
}

/*
  Trae tareas NO completadas del colaborador y las clasifica en buckets:
  - VENCIDA: fecha_fin < hoy
  - HOY: fecha_fin = hoy
  - SEMANA: fecha_fin entre hoy y +7 días
*/
$sql = "SELECT
  t.tarea_id,
  t.titulo AS tarea,
  t.descripcion,
  t.fecha_inicio,
  t.fecha_fin,
  t.completada,

  m.milestone_id,
  m.titulo AS milestone,

  e.estrategia_id,
  e.titulo AS estrategia,

  COALESCE(o.objetivo_id, 0) AS objetivo_id,
  COALESCE(o.titulo, 'Sin objetivo') AS objetivo,

  CASE
    WHEN t.completada = 1 THEN 'FINALIZADA'
    WHEN t.fecha_fin < CURDATE() THEN 'VENCIDA'
    WHEN t.fecha_fin = CURDATE() THEN 'HOY'
    WHEN t.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'SEMANA'
    ELSE 'PROXIMAS'
  END AS bucket

FROM tareas t
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id

LEFT JOIN (
    SELECT
      oe.estrategia_id,
      MIN(oe.objetivo_id) AS objetivo_id
    FROM objetivo_estrategia oe
    GROUP BY oe.estrategia_id
) oep ON oep.estrategia_id = e.estrategia_id

LEFT JOIN objetivos o
  ON o.objetivo_id = oep.objetivo_id
 AND o.empresa_id = ?

WHERE t.responsable_usuario_id = ?
  AND e.empresa_id = ?
  AND t.completada = 0

ORDER BY
  (t.fecha_fin < CURDATE()) DESC,
  t.fecha_fin ASC
LIMIT 80";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
  exit;
}

$stmt->bind_param('iii', $empresaId, $usuarioId, $empresaId);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$out = [
  'vencidas' => [],
  'hoy' => [],
  'semana' => []
];

foreach ($rows as $r) {
  if ($r['bucket'] === 'VENCIDA') $out['vencidas'][] = $r;
  else if ($r['bucket'] === 'HOY') $out['hoy'][] = $r;
  else if ($r['bucket'] === 'SEMANA') $out['semana'][] = $r;
}

echo json_encode([
  'success' => true,
  'data' => $out
], JSON_UNESCAPED_UNICODE);
exit;
