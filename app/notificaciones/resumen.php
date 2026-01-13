<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);

if ($usuarioId <= 0 || $empresaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
    exit;
}

/* ===== COUNT unread ===== */
$sqlCount = "SELECT COUNT(*) AS c FROM notificaciones WHERE usuario_id=? AND leida=0";
$stmt = $conn->prepare($sqlCount);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL prepare count error: ' . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$r = $stmt->get_result()->fetch_assoc();
$unread = (int)($r['c'] ?? 0);
$stmt->close();

/* ===== LIST latest + CONTEXTO TAREA/MILESTONE/ESTRATEGIA + RESPONSABLE TAREA ===== */
$sql = "
SELECT
  n.notificacion_id,
  n.tipo,
  n.titulo,
  n.cuerpo,
  n.entidad_tipo,
  n.entidad_id,
  n.leida,
  n.creada_en,

  -- Contexto cuando la entidad es tarea
  t.titulo AS tarea_titulo,
  m.titulo AS milestone_titulo,
  e.titulo AS estrategia_titulo,
  ur.nombre_completo AS tarea_responsable

FROM notificaciones n
LEFT JOIN tareas t
  ON n.entidad_tipo = 'tarea' AND t.tarea_id = n.entidad_id
LEFT JOIN milestones m
  ON m.milestone_id = t.milestone_id
LEFT JOIN estrategias e
  ON e.estrategia_id = m.estrategia_id
LEFT JOIN usuarios ur
  ON ur.usuario_id = t.responsable_usuario_id

WHERE n.usuario_id=?
ORDER BY n.creada_en DESC
LIMIT 10
";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'SQL prepare list error: ' . $conn->error
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode([
    'success' => true,
    'data' => [
        'unread_count' => $unread,
        'items' => $items
    ]
], JSON_UNESCAPED_UNICODE);
exit;
