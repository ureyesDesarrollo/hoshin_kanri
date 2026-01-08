<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId    = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$estrategiaId = (int)($_GET['estrategia_id'] ?? 0);

if ($empresaId <= 0 || $estrategiaId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
  exit;
}

$sql = "
SELECT
  m.milestone_id,
  m.titulo
FROM milestones m
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
WHERE m.estrategia_id = ?
  AND e.empresa_id = ?
ORDER BY m.creado_en DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
  exit;
}

$stmt->bind_param('ii', $estrategiaId, $empresaId);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
