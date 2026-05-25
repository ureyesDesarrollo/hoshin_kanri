<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);

if ($empresaId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Empresa inválida'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $conn->prepare("
SELECT DISTINCT
  responsable_area_id AS area_id,
  responsable_area AS nombre
FROM vista_proyectos_directivos
WHERE empresa_id = ?
  AND visible_en_direccion = 1
  AND responsable_area_id IS NOT NULL
  AND responsable_area IS NOT NULL
ORDER BY responsable_area ASC
");
$stmt->bind_param('i', $empresaId);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
