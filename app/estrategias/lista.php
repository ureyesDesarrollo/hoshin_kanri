<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

/* =========================
   TOTAL DE REGISTROS
========================= */
$sqlTotal = "
SELECT COUNT(*) AS total
FROM estrategias e
WHERE e.empresa_id = ?
  AND e.vigente = 1
";

$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param('i', $empresaId);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];

/* =========================
   LISTADO PAGINADO
========================= */
$sql = "SELECT DISTINCT
  e.estrategia_id,
  e.titulo,
  e.prioridad,
  e.estatus,
  u.nombre_completo AS responsable,
  e.creado_en
FROM estrategias e
JOIN usuarios u ON u.usuario_id = e.responsable_usuario_id
WHERE e.empresa_id = ?
ORDER BY e.estrategia_id DESC
LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iii', $empresaId, $perPage, $offset);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
  'success' => true,
  'data' => $data,
  'pagination' => [
    'page'        => $page,
    'per_page'   => $perPage,
    'total'      => $total,
    'total_pages'=> (int)ceil($total / $perPage)
  ]
]);
exit;
