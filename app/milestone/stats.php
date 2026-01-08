<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)$_SESSION['usuario']['empresa_id'];

if ($empresaId <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$sql = "
SELECT
    COUNT(*) AS total,
    SUM(m.estatus = 1) AS activos,
    SUM(m.estatus = 2) AS cerrados
FROM milestones m
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
WHERE e.empresa_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $empresaId);
$stmt->execute();

$res = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => true,
    'data' => [
        'total'    => (int)$res['total'],
        'activos'  => (int)$res['activos'],
        'cerrados' => (int)$res['cerrados']
    ]
]);
exit;
