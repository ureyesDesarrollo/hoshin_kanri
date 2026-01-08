<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId  = (int)$_SESSION['usuario']['empresa_id'];

$sql = "
SELECT DISTINCT
    e.estrategia_id,
    e.titulo
FROM estrategias e
WHERE e.empresa_id = ?
ORDER BY e.creado_en DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $empresaId);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $data
]);
exit;
