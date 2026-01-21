<?php

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$tareaId   = (int)($_POST['tarea_id'] ?? 0);
$comentario = $_POST['comentario'];

if (empty($comentario)) {
  exit;
}

$sql = "UPDATE tareas SET comentarios_responsable = ? WHERE tarea_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
  exit;
}

$stmt->bind_param("si", $comentario, $tareaId);
$stmt->execute();

if ($stmt->affected_rows < 0) {
  echo json_encode(['success' => false, 'message' => 'Error al actualizar comentario']);
  exit;
}

$stmt->close();

echo json_encode(['success' => true, 'message' => 'Comentario actualizado correctamente']);
