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
$q           = trim((string)($_GET['q'] ?? ''));              // titulo milestone
$responsable = trim((string)($_GET['responsable'] ?? ''));    // nombre responsable

$where = [];
$params = [];
$types = "";

// Base (empresa viene por la estrategia)
$where[] = "e.empresa_id = ?";
$params[] = $empresaId;
$types .= "i";

// Por título milestone
if ($q !== '') {
  $where[] = "m.titulo LIKE ?";
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
SELECT COUNT(*) AS total
FROM milestones m
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
JOIN usuarios u ON u.usuario_id = m.responsable_usuario_id
{$whereSql}
";

$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];

/* =========================
   LISTADO (con filtros)
========================= */
$sql = "
SELECT
  m.milestone_id,
  m.titulo,
  m.descripcion,
  m.estatus,
  m.creado_en,
  u.correo AS responsable_email,
  u.nombre_completo AS responsable,
  e.titulo AS estrategia
FROM milestones m
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
JOIN usuarios u ON u.usuario_id = m.responsable_usuario_id
{$whereSql}
ORDER BY m.milestone_id DESC
LIMIT ? OFFSET ?
";

$paramsList = $params;
$typesList  = $types . "ii";
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
