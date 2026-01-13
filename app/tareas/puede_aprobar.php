<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);
$tareaId   = (int)($_GET['tarea_id'] ?? 0);

if ($empresaId <= 0 || $usuarioId <= 0 || $tareaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$sql = "
SELECT ta.aprobacion_id
FROM tarea_aprobaciones ta
JOIN tareas t ON t.tarea_id = ta.tarea_id
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
WHERE ta.tarea_id = ?
  AND ta.estatus = 1
  AND ta.aprobador_usuario_id = ?
  AND e.empresa_id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iii', $tareaId, $usuarioId, $empresaId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode(['success' => true, 'data' => ['puede' => $row ? 1 : 0]], JSON_UNESCAPED_UNICODE);
