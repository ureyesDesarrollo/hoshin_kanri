<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');

auth_require();

$conn = db();

$titulo        = trim($_POST['titulo'] ?? '');
$descripcion   = trim($_POST['descripcion'] ?? '');
$periodoId     = (int)($_POST['periodo_id'] ?? 0);
$responsableId = (int)($_POST['responsable_id'] ?? 0);

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$creadoPor = (int)$_SESSION['usuario']['usuario_id'];

if ($titulo === '' || $periodoId <= 0 || $responsableId <= 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Datos incompletos'
    ]);
    exit;
}

$sql = "
INSERT INTO objetivos (
    empresa_id,
    periodo_id,
    titulo,
    descripcion,
    responsable_usuario_id,
    estatus,
    creado_por
) VALUES (?, ?, ?, ?, ?, ?, ?)
";

$estatus = 1;

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Error prepare: ' . $conn->error
    ]);
    exit;
}

$stmt->bind_param(
    'iissiii',
    $empresaId,
    $periodoId,
    $titulo,
    $descripcion,
    $responsableId,
    $estatus,
    $creadoPor
);

$ok = $stmt->execute();
$objetivoId = $conn->insert_id;
$usuarioId  = $creadoPor;

if (!$ok) {
    echo json_encode([
        'success' => false,
        'message' => 'Error execute: ' . $stmt->error
    ]);
    exit;
}

if ($stmt->affected_rows !== 1) {
    echo json_encode([
        'success' => false,
        'message' => 'No se insertó el registro (FK o datos inválidos)'
    ]);
    exit;
}

echo json_encode([
    'success' => true,
    'message' => 'Objetivo creado exitosamente'
]);

auditar(
    $conn,
    $empresaId,
    'objetivo',
    $objetivoId,
    'CREAR',
    $usuarioId
);

exit;
