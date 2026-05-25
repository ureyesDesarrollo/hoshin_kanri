<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);

$stmt = $conn->prepare("
SELECT tipo_proyecto_directivo_id, nombre, activo
FROM tipos_proyecto_directivos
WHERE empresa_id = ?
ORDER BY nombre ASC
");
$stmt->bind_param('i', $empresaId);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
