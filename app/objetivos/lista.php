<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$periodoId = (int)($_GET['periodo_id'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

// Filtros (buscador)
$q           = trim((string)($_GET['q'] ?? ''));              // titulo
$responsable = trim((string)($_GET['responsable'] ?? ''));    // nombre_completo

$where = [];
$params = [];
$types  = "";

// Base obligatoria
$where[] = "o.empresa_id = ?";
$params[] = $empresaId;
$types .= "i";

// Periodo opcional
$where[] = "(? = 0 OR o.periodo_id = ?)";
$params[] = $periodoId;
$params[] = $periodoId;
$types .= "ii";

// Filtro por título
if ($q !== '') {
  $where[] = "o.titulo LIKE ?";
  $params[] = "%{$q}%";
  $types .= "s";
}

// Filtro por responsable
if ($responsable !== '') {
  $where[] = "u.nombre_completo LIKE ?";
  $params[] = "%{$responsable}%";
  $types .= "s";
}

$whereSql = "WHERE " . implode(" AND ", $where);

/* =========================
   TOTAL DE REGISTROS (con filtros)
========================= */
$sqlTotal = "
SELECT COUNT(*) AS total
FROM objetivos o
JOIN usuarios u ON u.usuario_id = o.responsable_usuario_id
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
SELECT
    o.objetivo_id,
    o.titulo,
    o.estatus,
    o.creado_en,
    u.nombre_completo AS responsable,
    u.correo AS responsable_email
FROM objetivos o
JOIN usuarios u ON u.usuario_id = o.responsable_usuario_id
{$whereSql}
ORDER BY o.creado_en DESC
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
    'responsable' => $responsable,
    'periodo_id' => $periodoId
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
