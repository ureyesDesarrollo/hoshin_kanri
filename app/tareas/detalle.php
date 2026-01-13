<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);
$tareaId   = (int)($_GET['tarea_id'] ?? 0);

if ($empresaId <= 0 || $usuarioId <= 0 || $tareaId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
  exit;
}

$sql = "
SELECT
  t.tarea_id,
  t.titulo AS tarea,
  t.descripcion,
  t.fecha_inicio,
  t.fecha_fin,
  t.completada,
  t.estatus,
  t.rechazo_motivo,
  t.creado_en,
  t.completada_en,

  t.responsable_usuario_id,
  ur.nombre_completo AS responsable_nombre,
  ur.correo AS responsable_correo,


  m.milestone_id,
  m.titulo AS milestone,
  m.descripcion AS milestone_desc,
  m.responsable_usuario_id AS milestone_responsable_id,
  ur2.nombre_completo AS responsable_milestone,

  e.estrategia_id,
  e.titulo AS estrategia,
  e.descripcion AS estrategia_desc,
  e.responsable_usuario_id AS estrategia_responsable_id,
  ur3.nombre_completo AS responsable_estrategia,

  COALESCE(o.objetivo_id, 0) AS objetivo_id,
  COALESCE(o.titulo, 'Sin objetivo') AS objetivo,
  COALESCE(o.descripcion, '') AS objetivo_desc,

  CASE
    WHEN t.estatus = 4 OR t.completada = 1 THEN 'FINALIZADA'
    WHEN t.estatus = 3 THEN 'EN_REVISION'
    WHEN t.estatus = 5 THEN 'RECHAZADA'
    WHEN t.fecha_fin < CURDATE() THEN 'ROJO'
    WHEN t.fecha_fin = CURDATE() THEN 'HOY'
    ELSE 'VERDE'
  END AS semaforo,

  CASE
    WHEN (t.estatus <> 4 AND t.completada = 0) AND t.fecha_fin < CURDATE()
      THEN DATEDIFF(CURDATE(), t.fecha_fin)
    ELSE 0
  END AS dias_atraso

FROM tareas t
JOIN usuarios ur ON ur.usuario_id = t.responsable_usuario_id
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
LEFT JOIN objetivo_estrategia oe ON oe.estrategia_id = e.estrategia_id
LEFT JOIN objetivos o ON o.objetivo_id = oe.objetivo_id AND o.empresa_id = e.empresa_id
LEFT JOIN usuarios ur2 ON ur2.usuario_id = m.responsable_usuario_id
LEFT JOIN usuarios ur3 ON ur3.usuario_id = e.responsable_usuario_id

WHERE t.tarea_id = ?
  AND e.empresa_id = ?
  AND (
  t.responsable_usuario_id = ?
  OR EXISTS (
    SELECT 1
    FROM tarea_aprobaciones ta
    WHERE ta.tarea_id = t.tarea_id
      AND ta.aprobador_usuario_id = ?
  )
)
LIMIT 1

";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
  exit;
}

$stmt->bind_param('iiii', $tareaId, $empresaId, $usuarioId, $usuarioId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
  echo json_encode(['success' => false, 'message' => 'No autorizado o no existe']);
  exit;
}

echo json_encode(['success' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
exit;
