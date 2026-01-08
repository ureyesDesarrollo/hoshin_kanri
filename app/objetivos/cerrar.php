<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$objetivoId = (int)$_POST['objetivo_id'];
$empresaId  = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId  = (int)$_SESSION['usuario']['usuario_id'];

/* Validar que no haya estrategias abiertas */
$sql = "
SELECT COUNT(*) AS abiertas
FROM estrategias
WHERE objetivo_id = ?
  AND estatus = 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $objetivoId);
$stmt->execute();
$abiertas = (int)$stmt->get_result()->fetch_assoc()['abiertas'];

if ($abiertas > 0) {
    echo json_encode([
        'success' => false,
        'message' => 'Existen estrategias activas'
    ]);
    exit;
}

/* Cerrar objetivo */
$sql = "
UPDATE objetivos
SET estatus = 2
WHERE objetivo_id = ? AND empresa_id = ?
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al preparar cierre'
    ]);
    exit;
}

$stmt->bind_param('ii', $objetivoId, $empresaId);

if (!$stmt->execute()) {
    echo json_encode([
        'success' => false,
        'message' => 'Error al cerrar objetivo'
    ]);
    exit;
}

/* Auditoría */
auditar(
    $conn,
    $empresaId,
    'objetivo',
    $objetivoId,
    'CERRAR',
    $usuarioId,
    null,
    null,
    null,
    'Todas las estrategias cerradas'
);

echo json_encode(['success' => true]);
exit;
