<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId = (int)$_SESSION['usuario']['usuario_id'];

$milestoneId = (int)($_POST['milestone_id'] ?? 0);
$titulo      = trim($_POST['titulo'] ?? '');
$descripcion = trim($_POST['descripcion'] ?? '');
$responsable = (int)($_POST['responsable_id'] ?? 0);
$estrategia_id = (int)($_POST['estrategia_id'] ?? 0);
$estatus     = (int)($_POST['estatus'] ?? 1);
$prioridad   = (int)($_POST['prioridad'] ?? 2);

if ($milestoneId <= 0 || $titulo === '' || $responsable <= 0 || $estrategia_id <= 0) {
  echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
  exit;
}

/* Estado actual */
$stmt = $conn->prepare("
    SELECT titulo, descripcion, responsable_usuario_id, estatus, estrategia_id
    FROM milestones
    WHERE milestone_id = ?
");
$stmt->bind_param('i', $milestoneId);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();

/* Update */
$stmt = $conn->prepare("
    UPDATE milestones
    SET titulo = ?, descripcion = ?, responsable_usuario_id = ?, estatus = ?, estrategia_id = ?, prioridad = ?
    WHERE milestone_id = ?
");

$stmt->bind_param(
  'ssiiiii',
  $titulo,
  $descripcion,
  $responsable,
  $estatus,
  $estrategia_id,
  $prioridad,
  $milestoneId
);
$stmt->execute();

/* Auditoría */
$mapa = [
  'titulo' => $titulo,
  'descripcion' => $descripcion,
  'responsable_usuario_id' => (string)$responsable,
  'estatus' => (string)$estatus,
  'estrategia_id' => (string)$estrategia_id
];

foreach ($mapa as $campo => $nuevo) {
  if ((string)$actual[$campo] !== (string)$nuevo) {
    auditar(
      $conn,
      $empresaId,
      'milestone',
      $milestoneId,
      'EDITAR',
      $usuarioId,
      $campo,
      (string)$actual[$campo],
      (string)$nuevo
    );
  }
}

echo json_encode(['success' => true]);
exit;
