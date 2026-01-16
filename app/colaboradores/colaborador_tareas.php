<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_GET['usuario_id'] ?? 0);

if ($empresaId <= 0 || $usuarioId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
  exit;
}

/*
  Lista de tareas del colaborador (sin duplicar por objetivos).
  Si una estrategia tiene varios objetivos, NO duplicamos: agrupamos por t.tarea_id.
*/
$sql = "SELECT
  t.tarea_id,
  t.titulo AS tarea,
  t.descripcion,
  t.fecha_inicio,
  t.fecha_fin,
  t.completada,
  t.completada_en,

  m.milestone_id,
  m.titulo AS milestone,

  e.estrategia_id,
  e.titulo AS estrategia,

  /* si hay múltiples objetivos, mostramos uno (el primero) y puedes cambiarlo por GROUP_CONCAT si quieres */
  COALESCE(MIN(o.objetivo_id), 0) AS objetivo_id,
  COALESCE(MIN(o.titulo), 'Sin objetivo') AS objetivo,

  CASE
    WHEN t.completada = 1 THEN 'FINALIZADA'
    WHEN t.completada = 0 AND t.fecha_fin < CURDATE() THEN 'ROJO'
    WHEN t.completada = 0 AND t.fecha_fin = CURDATE() THEN 'HOY'
    ELSE 'VERDE'
  END AS semaforo,

  CASE
    WHEN t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin THEN 1
    ELSE 0
  END AS completada_tarde

FROM tareas t
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id

LEFT JOIN objetivo_estrategia oe ON oe.estrategia_id = e.estrategia_id
LEFT JOIN objetivos o ON o.objetivo_id = oe.objetivo_id AND o.empresa_id = e.empresa_id

WHERE e.empresa_id = ?
  AND t.responsable_usuario_id = ?

GROUP BY t.tarea_id
ORDER BY
  (t.completada = 0 AND t.fecha_fin < CURDATE()) DESC,
  t.fecha_fin ASC
LIMIT 200
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
  exit;
}

$stmt->bind_param('ii', $empresaId, $usuarioId);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
