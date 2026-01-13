<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db(); // mysqli
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);

if ($empresaId <= 0 || $usuarioId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

$sql = "
SELECT
  ta.aprobacion_id,
  ta.tarea_id,
  ta.nivel,
  ta.solicitado_en,
  t.titulo AS tarea_titulo,
  t.fecha_fin,
  u.nombre AS responsable_tarea
FROM tarea_aprobaciones ta
JOIN tareas t ON t.tarea_id = ta.tarea_id
JOIN usuarios u ON u.usuario_id = t.responsable_usuario_id
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
WHERE ta.estatus = 1
  AND ta.aprobador_usuario_id = ?
  AND e.empresa_id = ?
ORDER BY ta.solicitado_en DESC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $usuarioId, $empresaId);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) $rows[] = $r;
$stmt->close();

echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
