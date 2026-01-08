<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$tareaId   = (int)($_POST['tarea_id'] ?? 0);
$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId = (int)$_SESSION['usuario']['usuario_id'];

$stmt = $conn->prepare("
UPDATE tareas
SET completada = 1,
    completada_en = NOW()
WHERE tarea_id = ?
");

$stmt->bind_param('i', $tareaId);
$stmt->execute();

auditar(
    $conn,
    $empresaId,
    'tarea',
    $tareaId,
    'COMPLETAR',
    $usuarioId
);

echo json_encode(['success'=>true]);
exit;
