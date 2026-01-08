<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$tareaId   = (int)($_GET['id'] ?? 0);

if ($empresaId <= 0 || $tareaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$sql = "
SELECT
    t.tarea_id,
    t.milestone_id,
    m.estrategia_id,
    t.titulo,
    t.descripcion,
    t.responsable_usuario_id,
    t.fecha_inicio,
    t.fecha_fin,
    t.completada
FROM tareas t
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
WHERE t.tarea_id = ?
  AND e.empresa_id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param('ii', $tareaId, $empresaId);
$stmt->execute();

$data = $stmt->get_result()->fetch_assoc();

echo json_encode([
    'success' => (bool)$data,
    'data' => $data
], JSON_UNESCAPED_UNICODE);
exit;
