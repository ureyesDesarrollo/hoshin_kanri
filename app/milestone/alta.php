<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId = (int)$_SESSION['usuario']['usuario_id'];

$estrategiaId = (int)($_POST['estrategia_id'] ?? 0);
$titulo       = trim($_POST['titulo'] ?? '');
$descripcion  = trim($_POST['descripcion'] ?? '');
$responsable  = (int)($_POST['responsable_id'] ?? 0);
$prioridad    = (int)($_POST['prioridad'] ?? 2);

if ($estrategiaId <= 0 || $titulo === '' || $responsable <= 0) {
  echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
  exit;
}

$stmt = $conn->prepare("
    INSERT INTO milestones (
        estrategia_id,
        titulo,
        descripcion,
        responsable_usuario_id,
        estatus,
        prioridad,
        creado_por
    ) VALUES (?, ?, ?, ?, 1, ?, ?)
");

$stmt->bind_param(
  'issiii',
  $estrategiaId,
  $titulo,
  $descripcion,
  $responsable,
  $prioridad,
  $usuarioId
);

$stmt->execute();

if ($stmt->affected_rows !== 1) {
  echo json_encode(['success' => false, 'message' => 'No se pudo crear el milestone']);
  exit;
}

$milestoneId = $conn->insert_id;

auditar(
  $conn,
  $empresaId,
  'milestone',
  $milestoneId,
  'CREAR',
  $usuarioId
);

echo json_encode(['success' => true, 'milestone_id' => $milestoneId]);
exit;
