<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);

if ($usuarioId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
    exit;
}

$sql = "UPDATE notificaciones SET leida=1, leida_en=NOW() WHERE usuario_id=? AND leida=0";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $usuarioId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok], JSON_UNESCAPED_UNICODE);
exit;
