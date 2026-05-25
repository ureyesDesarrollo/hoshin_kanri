<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$usuarioId = (int)$_SESSION['usuario']['usuario_id'];
$empresaId = (int)$_SESSION['usuario']['empresa_id'];

$dependenciaId = (int)($_POST['dependencia_id'] ?? 0);
$tareaId = (int)($_POST['tarea_id'] ?? 0); // para auditoría

if ($dependenciaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de dependencia inválido']);
    exit;
}

// Verificar que la dependencia existe
$stmt = $conn->prepare("
    SELECT tarea_dependencia_id, tarea_id FROM tarea_dependencias 
    WHERE tarea_dependencia_id = ?
");
$stmt->bind_param('i', $dependenciaId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Dependencia no encontrada']);
    $stmt->close();
    exit;
}
$dependencia = $result->fetch_assoc();
$stmt->close();

// Eliminar la dependencia
$stmt = $conn->prepare("DELETE FROM tarea_dependencias WHERE tarea_dependencia_id = ?");
$stmt->bind_param('i', $dependenciaId);
$stmt->execute();
$stmt->close();

// Auditar
auditar(
    $conn,
    $empresaId,
    'tarea_dependencia',
    $dependenciaId,
    'ELIMINAR',
    $usuarioId
);

echo json_encode([
    'success' => true,
    'message' => 'Dependencia eliminada correctamente'
]);
exit;
