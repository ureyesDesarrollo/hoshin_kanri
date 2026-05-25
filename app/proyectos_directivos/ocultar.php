<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['id'] ?? $_SESSION['usuario']['usuario_id'] ?? 0);
$id = (int)($_POST['proyecto_directivo_id'] ?? 0);

if ($empresaId <= 0 || $id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $conn->prepare("
UPDATE proyectos_directivos
SET visible_en_direccion = 0, actualizado_por = ?
WHERE proyecto_directivo_id = ? AND empresa_id = ?
");
$stmt->bind_param('iii', $usuarioId, $id, $empresaId);
$stmt->execute();
$ok = $stmt->affected_rows >= 0;
$stmt->close();

echo json_encode(['success' => $ok, 'message' => $ok ? 'Proyecto ocultado de dirección' : 'No se pudo ocultar'], JSON_UNESCAPED_UNICODE);
exit;
