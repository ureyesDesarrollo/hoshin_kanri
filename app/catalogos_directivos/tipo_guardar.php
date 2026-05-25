<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$nombre = trim((string)($_POST['nombre'] ?? ''));

if ($empresaId <= 0 || $nombre === '') {
  echo json_encode(['success' => false, 'message' => 'El tipo de proyecto es obligatorio'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $conn->prepare("
INSERT INTO tipos_proyecto_directivos (empresa_id, nombre)
VALUES (?, ?)
ON DUPLICATE KEY UPDATE activo = 1, actualizado_en = CURRENT_TIMESTAMP
");
$stmt->bind_param('is', $empresaId, $nombre);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Tipo de proyecto guardado'], JSON_UNESCAPED_UNICODE);
exit;
