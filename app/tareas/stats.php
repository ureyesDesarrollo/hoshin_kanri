<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');

auth_require();

$conn = db();
$empresaId = (int)$_SESSION['usuario']['empresa_id'];

if ($empresaId <= 0) {
    echo json_encode(['success'=>false]);
    exit;
}

$data = [];

$sql = "
SELECT
    COUNT(*) AS total,
    SUM(completada = 0) AS activos,
    SUM(completada = 1) AS cerrados
FROM tareas";

$stmt = $conn->prepare($sql);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

$data['total']    = (int)$res['total'];
$data['activos']  = (int)$res['activos'];
$data['cerrados'] = (int)$res['cerrados'];

echo json_encode([
    'success' => true,
    'data' => $data
]);
exit;
