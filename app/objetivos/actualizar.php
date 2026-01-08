<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$objetivoId     = (int)($_POST['objetivo_id'] ?? 0);
$tituloNuevo    = trim($_POST['titulo'] ?? '');
$descripcionNueva = trim($_POST['descripcion'] ?? '');
$responsableNuevo = (int)($_POST['responsable_id'] ?? 0);

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId = (int)$_SESSION['usuario']['usuario_id'];

if ($objetivoId <= 0 || $tituloNuevo === '' || $responsableNuevo <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

/* =========================
   TRAER ESTADO ACTUAL
========================= */
$sql = "
SELECT titulo, descripcion, responsable_usuario_id
FROM objetivos
WHERE objetivo_id = ? AND empresa_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $objetivoId, $empresaId);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();

if (!$actual) {
    echo json_encode(['success' => false, 'message' => 'Objetivo no encontrado']);
    exit;
}

/* =========================
   ACTUALIZAR OBJETIVO
========================= */
$sql = "
UPDATE objetivos
SET titulo = ?, descripcion = ?, responsable_usuario_id = ?
WHERE objetivo_id = ? AND empresa_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ssiii',
    $tituloNuevo,
    $descripcionNueva,
    $responsableNuevo,
    $objetivoId,
    $empresaId
);
$stmt->execute();

auditar(
    $conn,
    $empresaId,
    'objetivo',
    $objetivoId,
    'EDITAR',
    $usuarioId,
    'titulo',
    $actual['titulo'],
    $tituloNuevo
);

if ($actual['responsable_usuario_id'] !== $responsableNuevo) {
    auditar(
        $conn,
        $empresaId,
        'objetivo',
        $objetivoId,
        'REASIGNAR',
        $usuarioId,
        'responsable_usuario_id',
        $actual['responsable_usuario_id'],
        $responsableNuevo,
        'Cambio organizacional'
    );
}

echo json_encode(['success' => true]);
exit;
