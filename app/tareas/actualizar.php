<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$tareaId      = (int)($_POST['tarea_id'] ?? 0);
$titulo       = trim($_POST['titulo'] ?? '');
$descripcion  = trim($_POST['descripcion'] ?? '');
$responsable  = (int)($_POST['responsable_id'] ?? 0);
$fechaInicio  = $_POST['fecha_inicio'] ?? '';
$fechaFin     = $_POST['fecha_fin'] ?? '';

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId = (int)$_SESSION['usuario']['usuario_id'];

$stmt = $conn->prepare("
UPDATE tareas
SET
    titulo = ?,
    descripcion = ?,
    responsable_usuario_id = ?,
    fecha_inicio = ?,
    fecha_fin = ?
WHERE tarea_id = ?
");

$stmt->bind_param(
    'ssissi',
    $titulo,
    $descripcion,
    $responsable,
    $fechaInicio,
    $fechaFin,
    $tareaId
);

$stmt->execute();

auditar(
    $conn,
    $empresaId,
    'tarea',
    $tareaId,
    'EDITAR',
    $usuarioId
);

echo json_encode(['success'=>true]);
exit;
