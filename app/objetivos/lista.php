<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$periodoId = (int)($_GET['periodo_id'] ?? 0);
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

/* =========================
   TOTAL DE REGISTROS
========================= */
$sqlTotal = "
SELECT COUNT(*) AS total
FROM objetivos
WHERE empresa_id = ?
  AND (? = 0 OR periodo_id = ?)
";

$stmt = $conn->prepare($sqlTotal);
$stmt->bind_param('iii', $empresaId, $periodoId, $periodoId);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];

/* =========================
   LISTADO PAGINADO
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
WHERE o.empresa_id = ?
  AND (? = 0 OR o.periodo_id = ?)
ORDER BY o.creado_en DESC
LIMIT ? OFFSET ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'iiiii',
    $empresaId,
    $periodoId,
    $periodoId,
    $perPage,
    $offset
);

$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $data,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => (int)ceil($total / $perPage)
    ]
]);
exit;
