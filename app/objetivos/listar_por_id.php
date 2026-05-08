<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)$_SESSION['usuario']['empresa_id'];
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
JOIN usuarios_empresas ue
  ON ue.usuario_id = u.usuario_id
 AND ue.empresa_id = o.empresa_id
 AND ue.activo = 1
WHERE o.objetivo_id = ?
  AND o.empresa_id = ?
  AND u.activo = 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param(
    'ii',
    $objetivoId,
    $empresaId
);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $data
]);
exit;
