<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);

$page  = max(1, (int)($_GET['page'] ?? 1));
$limit = min(24, max(6, (int)($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;

$filtro = $_GET['f'] ?? 'pendientes';       // all|pendientes|vencidas|hoy|semana|finalizadas
$q = trim($_GET['q'] ?? '');
$orden = $_GET['o'] ?? 'fecha_fin_asc';

if ($empresaId <= 0 || $usuarioId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
  exit;
}

/* WHERE base + seguridad empresa */
$where = "WHERE t.responsable_usuario_id = ? AND e.empresa_id = ?";
$params = [$usuarioId, $empresaId];
$types = "ii";

/* Filtros */
if ($filtro === 'pendientes') {
  $where .= " AND t.estatus IN (1,2) AND t.fecha_fin >= CURDATE()";
} elseif ($filtro === 'vencidas') {
  $where .= " AND t.estatus IN (1,2) AND t.fecha_fin < CURDATE()";
} elseif ($filtro === 'hoy') {
  $where .= " AND t.estatus IN (1,2) AND t.fecha_fin = CURDATE()";
} elseif ($filtro === 'semana') {
  $where .= " AND t.estatus IN (1,2) AND t.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
} elseif ($filtro === 'finalizadas') {
  // incluye legacy completada=1 aunque estatus no sea 4
  $where .= " AND (t.estatus = 4 OR t.completada = 1)";
} elseif ($filtro === 'revision') {
  $where .= " AND t.estatus = 3";
} elseif ($filtro === 'rechazadas') {
  $where .= " AND t.estatus = 5";
} else {
  // all: no agregar nada extra
}


/* Búsqueda */
if ($q !== '') {
  $where .= " AND t.titulo LIKE ?";
  $types .= "s";
  $params[] = "%" . $q . "%";
}

/* ORDER BY */
$orderBy = "ORDER BY t.fecha_fin ASC";
if ($orden === 'fecha_fin_desc') $orderBy = "ORDER BY t.fecha_fin DESC";
if ($orden === 'titulo_asc') $orderBy = "ORDER BY t.titulo ASC";

/* ========= COUNT total (para paginación) ========= */
$sqlCount = "
SELECT COUNT(DISTINCT t.tarea_id) AS total
FROM tareas t
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
$where
";
$stmt = $conn->prepare($sqlCount);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)($stmt->get_result()->fetch_assoc()['total'] ?? 0);
$totalPages = (int)ceil($total / $limit);

/* ========= LISTA ========= */
$sql = "
SELECT
  t.tarea_id,
  t.titulo AS tarea,
  t.descripcion,
  t.fecha_inicio,
  t.fecha_fin,
  t.completada,
  t.completada_en,
  t.estatus,
  t.prioridad,
  m.milestone_id,
  m.titulo AS milestone,
  m.responsable_usuario_id,

  u.nombre_completo AS responsable,

  e.estrategia_id,
  e.titulo AS estrategia,

  COALESCE(o.objetivo_id, 0) AS objetivo_id,
  COALESCE(o.titulo, 'Sin objetivo') AS objetivo,

  CASE
  WHEN t.estatus = 4 OR t.completada = 1 THEN 'FINALIZADA'
  WHEN t.estatus = 3 THEN 'REVISION'
  WHEN t.estatus = 5 THEN 'RECHAZADA'
  WHEN t.fecha_fin < CURDATE() THEN 'ROJO'
  WHEN t.fecha_fin = CURDATE() THEN 'HOY'
  ELSE 'VERDE'
END AS semaforo

FROM tareas t
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
LEFT JOIN objetivo_estrategia oe ON oe.estrategia_id = e.estrategia_id
LEFT JOIN objetivos o ON o.objetivo_id = oe.objetivo_id AND o.empresa_id = ?
LEFT JOIN usuarios u ON u.usuario_id = m.responsable_usuario_id
$where
GROUP BY t.tarea_id
$orderBy
LIMIT $limit OFFSET $offset
";

/* nota: agregamos empresaId al inicio por el LEFT JOIN a objetivos */
$paramsList = array_merge([$empresaId], $params);
$typesList = "i" . $types;

$stmt = $conn->prepare($sql);
$stmt->bind_param($typesList, ...$paramsList);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/* ========= KPI chips (anti-duplicados) ========= */
$sqlKpi = "
SELECT
  COUNT(x.tarea_id) AS total,
  SUM(CASE WHEN x.estatus=4 OR x.completada=1 THEN 1 ELSE 0 END) AS finalizadas,
  SUM(CASE WHEN x.estatus IN (1,2) AND x.fecha_fin >= CURDATE() THEN 1 ELSE 0 END) AS pendientes,
  SUM(CASE WHEN x.estatus IN (1,2) AND x.fecha_fin < CURDATE() THEN 1 ELSE 0 END) AS vencidas
FROM (
  SELECT DISTINCT t.tarea_id, t.completada, t.estatus, t.fecha_fin
  FROM tareas t
  JOIN milestones m ON m.milestone_id = t.milestone_id
  JOIN estrategias e ON e.estrategia_id = m.estrategia_id
  WHERE t.responsable_usuario_id = ?
    AND e.empresa_id = ?
) x
";

$stmt = $conn->prepare($sqlKpi);
$stmt->bind_param("ii", $usuarioId, $empresaId);
$stmt->execute();
$kpi = $stmt->get_result()->fetch_assoc() ?: ['total' => 0, 'finalizadas' => 0, 'pendientes' => 0, 'vencidas' => 0];

echo json_encode([
  'success' => true,
  'data' => $data,
  'kpi' => [
    'total' => (int)$kpi['total'],
    'finalizadas' => (int)$kpi['finalizadas'],
    'pendientes' => (int)$kpi['pendientes'],
    'vencidas' => (int)$kpi['vencidas'],
  ],
  'pagination' => [
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'total_pages' => max(1, $totalPages),
  ],
], JSON_UNESCAPED_UNICODE);
exit;
