<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');

auth_require();

$conn = db();

$data = [];

$sql = "SELECT
    u.usuario_id,
    u.nombre_completo,
    u.correo,
    u.password_hash,

    e.empresa_id,
    e.nombre AS empresa_nombre,

    r.rol_id,
    r.nombre AS rol

    a.area_id,
    a.nombre AS area_nombre

FROM usuarios u
JOIN usuarios_empresas ue
    ON ue.usuario_id = u.usuario_id
    AND ue.activo = 1
JOIN empresas e
    ON e.empresa_id = ue.empresa_id
    AND e.activa = 1
JOIN roles r
    ON r.rol_id = ue.rol_id
JOIN areas a
    ON a.area_id = ue.area_id
WHERE
    u.activo = 1
    AND ue.activo = 1
    AND e.activa = 1";

$stmt = $conn->prepare($sql);
$stmt->execute();
$res = $stmt->get_result();
$data = $res->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $data
]);
exit;
