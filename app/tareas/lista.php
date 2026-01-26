<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
$offset  = ($page - 1) * $perPage;

// Filtros
$q            = trim((string)($_GET['q'] ?? ''));              // titulo
$responsable  = trim((string)($_GET['responsable'] ?? ''));    // nombre_completo

$where = [];
$params = [];
$types = "";

// Buscar por título
if ($q !== '') {
  $where[] = "t.titulo LIKE ?";
  $params[] = "%{$q}%";
  $types .= "s";
}

// Buscar por responsable (nombre)
if ($responsable !== '') {
  $where[] = "u.nombre_completo LIKE ?";
  $params[] = "%{$responsable}%";
  $types .= "s";
}

$whereSql = $where ? ("WHERE " . implode(" AND ", $where)) : "";

/* =========================
   TOTAL (con filtros)
========================= */
$sqlTotal = "
SELECT COUNT(*) AS total
FROM tareas t
JOIN usuarios u ON u.usuario_id = t.responsable_usuario_id
{$whereSql}
";
$stmt = $conn->prepare($sqlTotal);

if ($types !== "") {
  $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];

/* =========================
   LISTADO (con filtros)
========================= */
$sqlList = "
SELECT
  t.tarea_id,
  t.titulo,
  t.descripcion,
  t.fecha_inicio,
  t.fecha_fin,
  t.completada,
  t.completada_en,
  t.creado_en,
  u.nombre_completo AS responsable
FROM tareas t
JOIN usuarios u ON u.usuario_id = t.responsable_usuario_id
{$whereSql}
ORDER BY t.tarea_id DESC
LIMIT ? OFFSET ?
";
$stmt = $conn->prepare($sqlList);

// Agregamos limit/offset al final
$paramsList = $params;
$typesList  = $types . "ii";
$paramsList[] = $perPage;
$paramsList[] = $offset;

$stmt->bind_param($typesList, ...$paramsList);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
  'success' => true,
  'filters' => [
    'q' => $q,
    'responsable' => $responsable
  ],
  'data' => $data,
  'pagination' => [
    'page' => $page,
    'per_page' => $perPage,
    'total' => $total,
    'total_pages' => (int)ceil($total / $perPage)
  ]
]);
exit;
