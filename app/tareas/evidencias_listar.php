<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../config.php';

auth_require();
$conn = db();

header('Content-Type: application/json; charset=utf-8');

$tareaId = (int)($_GET['tarea_id'] ?? 0);
if ($tareaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Tarea inválida']);
    exit;
}

$stmt = $conn->prepare("
  SELECT evidencia_id, nombre_original, mime_type, tamano_bytes, creado_por, creado_en
  FROM tarea_evidencias
  WHERE tarea_id = ? AND eliminado = 0
  ORDER BY creado_en DESC
");
$stmt->bind_param("i", $tareaId);
$stmt->execute();
$res = $stmt->get_result();

$rows = [];
while ($r = $res->fetch_assoc()) {
    $r['tamano_bytes'] = (int)$r['tamano_bytes'];
    $rows[] = $r;
}
$stmt->close();

echo json_encode(['success' => true, 'data' => $rows]);
