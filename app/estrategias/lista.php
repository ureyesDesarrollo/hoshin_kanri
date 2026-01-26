<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

// Filtros
$q           = trim((string)($_GET['q'] ?? ''));              // titulo
$responsable = trim((string)($_GET['responsable'] ?? ''));    // nombre_completo

$where = [];
$params = [];
$types = "";

// Base
$where[] = "e.empresa_id = ?";
$params[] = $empresaId;
$types .= "i";

$where[] = "e.vigente = 1";

// Por título
if ($q !== '') {
  $where[] = "e.titulo LIKE ?";
  $params[] = "%{$q}%";
  $types .= "s";
}

// Por responsable
if ($responsable !== '') {
  $where[] = "u.nombre_completo LIKE ?";
  $params[] = "%{$responsable}%";
  $types .= "s";
}

$whereSql = "WHERE " . implode(" AND ", $where);

/* =========================
   TOTAL (con filtros)
========================= */
$sqlTotal = "
SELECT COUNT(DISTINCT e.estrategia_id) AS total
FROM estrategias e
JOIN usuarios u ON u.usuario_id = e.responsable_usuario_id
{$whereSql}
";

$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];

/* =========================
   LISTADO PAGINADO (con filtros)
========================= */
$sql = "
SELECT DISTINCT
  e.estrategia_id,
  e.titulo,
  e.prioridad,
  e.estatus,
  u.nombre_completo AS responsable,
  e.creado_en
FROM estrategias e
JOIN usuarios u ON u.usuario_id = e.responsable_usuario_id
{$whereSql}
ORDER BY e.estrategia_id DESC
LIMIT ? OFFSET ?
";

$paramsList = $params;
$typesList = $types . "ii";
$paramsList[] = $perPage;
$paramsList[] = $offset;

$stmt = $conn->prepare($sql);
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
