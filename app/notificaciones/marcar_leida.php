<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);
$notifId = (int)($_POST['notificacion_id'] ?? 0);

if ($usuarioId <= 0 || $notifId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$sql = "UPDATE notificaciones SET leida=1, leida_en=NOW() WHERE notificacion_id=? AND usuario_id=? LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $notifId, $usuarioId);
$ok = $stmt->execute();
$stmt->close();

echo json_encode(['success' => $ok], JSON_UNESCAPED_UNICODE);
exit;
