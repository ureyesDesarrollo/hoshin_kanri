<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId   = (int)$_SESSION['usuario']['empresa_id'];
$milestoneId = (int)($_GET['id'] ?? 0);

if ($milestoneId <= 0) {
  echo json_encode([
    'success' => false,
    'message' => 'ID de milestone inválido'
  ]);
  exit;
}

$sql = "
SELECT
    m.milestone_id,
    m.estrategia_id,
    m.titulo,
    m.descripcion,
    m.responsable_usuario_id,
    m.estatus,
    m.prioridad,
    m.creado_en
FROM milestones m
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
WHERE
    m.milestone_id = ?
    AND e.empresa_id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $milestoneId, $empresaId);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
  echo json_encode([
    'success' => false,
    'message' => 'Milestone no encontrado'
  ]);
  exit;
}

echo json_encode([
  'success' => true,
  'data' => $data
]);
exit;
