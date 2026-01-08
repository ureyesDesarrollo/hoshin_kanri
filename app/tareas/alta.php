<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$milestoneId  = (int)($_POST['milestone_id'] ?? 0);
$titulo       = trim($_POST['titulo'] ?? '');
$descripcion  = trim($_POST['descripcion'] ?? '');
$responsable  = (int)($_POST['responsable_id'] ?? 0);
$fechaInicio  = $_POST['fecha_inicio'] ?? '';
$fechaFin     = $_POST['fecha_fin'] ?? '';

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId = (int)$_SESSION['usuario']['usuario_id'];

if (
    $milestoneId <= 0 ||
    $titulo === '' ||
    $responsable <= 0 ||
    !$fechaInicio ||
    !$fechaFin
) {
    echo json_encode(['success'=>false,'message'=>'Datos inválidos']);
    exit;
}

$stmt = $conn->prepare("
INSERT INTO tareas (
    milestone_id,
    titulo,
    descripcion,
    responsable_usuario_id,
    fecha_inicio,
    fecha_fin,
    creado_por
) VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    'ississi',
    $milestoneId,
    $titulo,
    $descripcion,
    $responsable,
    $fechaInicio,
    $fechaFin,
    $usuarioId
);

$stmt->execute();

$tareaId = $conn->insert_id;

auditar(
    $conn,
    $empresaId,
    'tarea',
    $tareaId,
    'CREAR',
    $usuarioId
);

echo json_encode(['success'=>true]);
exit;
