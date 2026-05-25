<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);

$stmt = $conn->prepare("
SELECT zona_directiva_id, nombre, activo
FROM zonas_directivas
WHERE empresa_id = ?
ORDER BY nombre ASC
");
$stmt->bind_param('i', $empresaId);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
