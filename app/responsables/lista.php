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
FROM usuarios_empresas ue
JOIN usuarios u ON u.usuario_id = ue.usuario_id
WHERE ue.empresa_id = ?
  AND ue.activo = 1
  AND u.activo = 1";

$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param('i', $empresaId);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];

/* =========================
   LISTADO PAGINADO
========================= */

$sql = "SELECT
  u.usuario_id,
  u.nombre_completo,
  u.correo,
  u.activo AS usuario_global_activo,
  ue.usuario_empresa_id,
  ue.activo AS usuario_empresa_activo,
  LEAST(u.activo, ue.activo) AS usuario_activo,
  r.rol_id,
  r.nombre AS rol,
  u.creado_en
FROM usuarios_empresas ue
JOIN usuarios u ON u.usuario_id = ue.usuario_id
JOIN roles r ON r.rol_id = ue.rol_id
WHERE ue.empresa_id = ?
  AND ue.activo = 1
  AND u.activo = 1
ORDER BY u.nombre_completo ASC
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
    'total_pages' => (int)ceil($total / $perPage)
  ]
]);
exit;
