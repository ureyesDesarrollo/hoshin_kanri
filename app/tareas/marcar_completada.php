<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);
$tareaId   = (int)($_POST['tarea_id'] ?? 0);

if ($empresaId <= 0 || $usuarioId <= 0 || $tareaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

/* Verifica que la tarea sea del usuario y pertenezca a la empresa */
$sqlCheck = "
SELECT t.tarea_id
FROM tareas t
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
WHERE t.tarea_id = ?
  AND t.responsable_usuario_id = ?
  AND e.empresa_id = ?
LIMIT 1
";

$stmt = $conn->prepare($sqlCheck);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param('iii', $tareaId, $usuarioId, $empresaId);
$stmt->execute();
$ok = $stmt->get_result()->fetch_assoc();

if (!$ok) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

/* Actualiza */
$sqlUp = "
UPDATE tareas
SET completada = 1,
    completada_en = NOW()
WHERE tarea_id = ?
  AND completada = 0
";

$stmt = $conn->prepare($sqlUp);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param('i', $tareaId);
$exec = $stmt->execute();

if (!$exec) {
    echo json_encode(['success' => false, 'message' => 'Error execute: ' . $stmt->error]);
    exit;
}

auditar($conn, $empresaId, 'tarea', $tareaId, 'CERRAR', $usuarioId);

echo json_encode([
    'success' => true,
    'message' => 'Tarea completada'
], JSON_UNESCAPED_UNICODE);
exit;
