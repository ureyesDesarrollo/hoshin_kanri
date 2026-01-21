<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = min(50, max(5, (int)($_GET['limit'] ?? 15)));
$offset = ($page - 1) * $limit;

$f = $_GET['f'] ?? 'all';   // all|unread|revision|aprobadas|rechazadas|dup_nombre
$q = trim($_GET['q'] ?? '');

if ($usuarioId <= 0 || $empresaId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Sesión inválida'], JSON_UNESCAPED_UNICODE);
  exit;
}

/**
 * Importante:
 * Como el filtro dup_nombre y el search usan ur/t, el COUNT también debe incluir los JOINs,
 * si no, MySQL va a truenár por columnas desconocidas.
 */
$fromJoin = "
FROM notificaciones n
LEFT JOIN tareas t
  ON n.entidad_tipo = 'tarea' AND t.tarea_id = n.entidad_id
LEFT JOIN milestones m
  ON m.milestone_id = t.milestone_id
LEFT JOIN estrategias e
  ON e.estrategia_id = m.estrategia_id
LEFT JOIN usuarios ur
  ON ur.usuario_id = t.responsable_usuario_id
";

$where = "WHERE n.usuario_id = ?";
$params = [$usuarioId];
$types = "i";

if ($f === 'unread') {
  $where .= " AND n.leida = 0";
} elseif ($f === 'revision') {
  $where .= " AND n.tipo = 'tarea_en_revision'";
} elseif ($f === 'aprobadas') {
  $where .= " AND n.tipo = 'tarea_aprobada'";
} elseif ($f === 'rechazadas') {
  $where .= " AND n.tipo = 'tarea_rechazada'";
} elseif ($f === 'dup_nombre') {
  // TODAS las filas (notificaciones) cuyo responsable (ur.nombre_completo) esté duplicado en usuarios
  $where .= " AND ur.nombre_completo IN (
    SELECT u.nombre_completo
    FROM usuarios u
    WHERE u.nombre_completo IS NOT NULL AND u.nombre_completo <> ''
    GROUP BY u.nombre_completo
    HAVING COUNT(*) > 1
  )";
}

if ($q !== '') {
  $where .= " AND (n.titulo LIKE ? OR n.cuerpo LIKE ? OR ur.nombre_completo LIKE ?)";
  $types .= "sss";
  $params[] = "%$q%";
  $params[] = "%$q%";
  $params[] = "%$q%";
}

/* COUNT */
$sqlCount = "SELECT COUNT(DISTINCT n.notificacion_id) AS total $fromJoin $where";
$stmt = $conn->prepare($sqlCount);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Prepare count: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$stmt->close();

$totalPages = max(1, (int)ceil($total / $limit));

/* LIST + CONTEXTO */
$sql = "
SELECT
  n.notificacion_id,
  n.tipo,
  n.titulo,
  n.cuerpo,
  n.entidad_tipo,
  n.entidad_id,
  n.leida,
  n.leida_en,
  n.creada_en,

  t.titulo AS tarea_titulo,
  m.titulo AS milestone_titulo,
  e.titulo AS estrategia_titulo,
  ur.nombre_completo AS tarea_responsable

$fromJoin
$where
ORDER BY n.creada_en DESC
LIMIT $limit OFFSET $offset
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Prepare list: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt->bind_param($types, ...$params);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

/* UNREAD COUNT (para badge global) */
$sqlUnread = "SELECT COUNT(*) AS c FROM notificaciones WHERE usuario_id=? AND leida=0";
$stmt = $conn->prepare($sqlUnread);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Prepare unread: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt->bind_param("i", $usuarioId);
$stmt->execute();
$unread = (int)($stmt->get_result()->fetch_assoc()['c'] ?? 0);
$stmt->close();

echo json_encode([
  'success' => true,
  'data' => $items,
  'meta' => [
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'total_pages' => $totalPages,
    'unread_count' => $unread
  ]
], JSON_UNESCAPED_UNICODE);
exit;
