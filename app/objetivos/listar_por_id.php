<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$objetivoId = (int)$_GET['objetivo_id'];

$sql = "
SELECT
    o.objetivo_id,
    o.titulo,
    o.estatus,
    o.descripcion,
    u.nombre_completo AS responsable,
    u.correo AS responsable_email,
    o.responsable_usuario_id
FROM objetivos o
JOIN usuarios u ON u.usuario_id = o.responsable_usuario_id
WHERE o.objetivo_id = ?
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'i',
    $objetivoId
);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $data
]);
exit;
