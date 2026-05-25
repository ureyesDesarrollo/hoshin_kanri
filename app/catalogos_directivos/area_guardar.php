<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$zonaId = (int)($_POST['zona_directiva_id'] ?? 0);
$nombre = trim((string)($_POST['nombre'] ?? ''));

if ($empresaId <= 0 || $zonaId <= 0 || $nombre === '') {
  echo json_encode(['success' => false, 'message' => 'Zona y área son obligatorias'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmtZona = $conn->prepare("
SELECT zona_directiva_id
FROM zonas_directivas
WHERE zona_directiva_id = ? AND empresa_id = ? AND activo = 1
LIMIT 1
");
$stmtZona->bind_param('ii', $zonaId, $empresaId);
$stmtZona->execute();
$zona = $stmtZona->get_result()->fetch_assoc();
$stmtZona->close();

if (!$zona) {
  echo json_encode(['success' => false, 'message' => 'Zona inválida'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $conn->prepare("
INSERT INTO areas_directivas (empresa_id, zona_directiva_id, nombre)
VALUES (?, ?, ?)
ON DUPLICATE KEY UPDATE activo = 1, actualizado_en = CURRENT_TIMESTAMP
");
$stmt->bind_param('iis', $empresaId, $zonaId, $nombre);
$stmt->execute();
$stmt->close();

echo json_encode(['success' => true, 'message' => 'Área guardada'], JSON_UNESCAPED_UNICODE);
exit;
