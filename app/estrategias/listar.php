<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId  = (int)$_SESSION['usuario']['empresa_id'];
$objetivoId = (int)($_GET['objetivo_id'] ?? 0);

if ($objetivoId <= 0) {
    echo json_encode(['success'=>false,'data'=>[]]);
    exit;
}

$sql = "
SELECT DISTINCT
    e.estrategia_id,
    e.titulo
FROM estrategias e
JOIN objetivo_estrategia oe 
    ON oe.estrategia_id = e.estrategia_id
WHERE e.empresa_id = ?
  AND oe.objetivo_id = ?
ORDER BY e.creado_en DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $empresaId, $objetivoId);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $data
]);
exit;
