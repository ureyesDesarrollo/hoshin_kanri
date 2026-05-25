<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['id'] ?? $_SESSION['usuario']['usuario_id'] ?? 0);
$milestoneId = (int)($_POST['milestone_id'] ?? 0);

if ($empresaId <= 0 || $milestoneId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt = $conn->prepare("
SELECT
  m.milestone_id,
  m.titulo,
  m.prioridad,
  MIN(COALESCE(t.fecha_inicio, DATE(t.creado_en), t.fecha_fin)) AS fecha_inicio,
  MAX(t.fecha_fin) AS fecha_fin
FROM milestones m
JOIN estrategias e ON e.estrategia_id = m.estrategia_id AND e.empresa_id = ?
LEFT JOIN tareas t ON t.milestone_id = m.milestone_id
WHERE m.milestone_id = ?
GROUP BY m.milestone_id, m.titulo, m.prioridad
LIMIT 1
");
$stmt->bind_param('ii', $empresaId, $milestoneId);
$stmt->execute();
$milestone = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$milestone) {
  echo json_encode(['success' => false, 'message' => 'Milestone no encontrado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$prioridadMap = [
  '1' => 'Baja',
  '2' => 'Media',
  '3' => 'Alta',
];
$prioridad = $prioridadMap[(string)$milestone['prioridad']] ?? 'Media';
$fechaInicio = $milestone['fecha_inicio'] ?: null;
$fechaFin = $milestone['fecha_fin'] ?: null;

$stmtExistente = $conn->prepare("
SELECT proyecto_directivo_id
FROM proyectos_directivos
WHERE empresa_id = ? AND milestone_id = ?
LIMIT 1
");
$stmtExistente->bind_param('ii', $empresaId, $milestoneId);
$stmtExistente->execute();
$existente = $stmtExistente->get_result()->fetch_assoc();
$stmtExistente->close();

if ($existente) {
  $id = (int)$existente['proyecto_directivo_id'];
  $stmtUpdate = $conn->prepare("
    UPDATE proyectos_directivos
    SET visible_en_direccion = 1, requiere_reporte_direccion = 1, actualizado_por = ?
    WHERE proyecto_directivo_id = ? AND empresa_id = ?
  ");
  $stmtUpdate->bind_param('iii', $usuarioId, $id, $empresaId);
  $stmtUpdate->execute();
  $stmtUpdate->close();

  echo json_encode(['success' => true, 'message' => 'El milestone ya estaba en gestión directiva', 'id' => $id], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmtInsert = $conn->prepare("
INSERT INTO proyectos_directivos (
  empresa_id, milestone_id, nombre_directivo, prioridad_directiva,
  estado_directivo, requiere_reporte_direccion, fecha_inicio_directiva,
  fecha_fin_objetivo, creado_por, actualizado_por
) VALUES (?, ?, ?, ?, 'En evaluacion', 1, ?, ?, ?, ?)
");
$stmtInsert->bind_param(
  'iissssii',
  $empresaId,
  $milestoneId,
  $milestone['titulo'],
  $prioridad,
  $fechaInicio,
  $fechaFin,
  $usuarioId,
  $usuarioId
);
$stmtInsert->execute();
$id = (int)$conn->insert_id;
$stmtInsert->close();

$stmtHist = $conn->prepare("
INSERT INTO historial_prioridad_directiva (
  proyecto_directivo_id, prioridad_anterior, prioridad_nueva, motivo, cambiado_por
) VALUES (?, NULL, ?, 'Promoción inicial desde milestone', ?)
");
$stmtHist->bind_param('isi', $id, $prioridad, $usuarioId);
$stmtHist->execute();
$stmtHist->close();

echo json_encode(['success' => true, 'message' => 'Milestone enviado a gestión directiva', 'id' => $id], JSON_UNESCAPED_UNICODE);
exit;
