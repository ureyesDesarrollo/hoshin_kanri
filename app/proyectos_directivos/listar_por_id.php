<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$id = (int)($_GET['id'] ?? 0);

if ($empresaId <= 0 || $id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $conn->prepare("
SELECT *
FROM vista_proyectos_directivos
WHERE proyecto_directivo_id = ? AND empresa_id = ?
LIMIT 1
");
$stmt->bind_param('ii', $id, $empresaId);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$data) {
  echo json_encode(['success' => false, 'message' => 'Proyecto directivo no encontrado'], JSON_UNESCAPED_UNICODE);
  exit;
}

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
