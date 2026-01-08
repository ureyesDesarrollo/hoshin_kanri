<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId    = (int)$_SESSION['usuario']['empresa_id'];
$estrategiaId = (int)($_GET['id'] ?? 0);

if ($estrategiaId <= 0) {
    echo json_encode(['success'=>false,'message'=>'ID inválido']);
    exit;
}

/* Estrategia */
$stmt = $conn->prepare("
    SELECT
        estrategia_id,
        titulo,
        descripcion,
        prioridad,
        responsable_usuario_id
    FROM estrategias
    WHERE estrategia_id = ? AND empresa_id = ?
");
$stmt->bind_param('ii', $estrategiaId, $empresaId);
$stmt->execute();
$estrategia = $stmt->get_result()->fetch_assoc();

if (!$estrategia) {
    echo json_encode(['success'=>false,'message'=>'No encontrada']);
    exit;
}

/* Objetivos asociados */
$stmt = $conn->prepare("
    SELECT objetivo_id
    FROM objetivo_estrategia
    WHERE estrategia_id = ?
");
$stmt->bind_param('i', $estrategiaId);
$stmt->execute();

$objetivos = array_column(
    $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
    'objetivo_id'
);

echo json_encode([
    'success' => true,
    'data' => [
        'estrategia' => $estrategia,
        'objetivos'  => $objetivos
    ]
]);
exit;
