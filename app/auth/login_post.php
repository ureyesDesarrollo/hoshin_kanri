<?php
require_once '../core/db.php';
require_once '../core/auth.php';

header('Content-Type: application/json; charset=utf-8');

$correo = $_POST['correo'] ?? '';
$pass   = $_POST['password'] ?? '';

if ($correo === '' || $pass === '') {
    echo json_encode([
        'success' => false,
        'error' => 'Datos incompletos'
    ]);
    exit;
}

$conn = db();

$sql = "SELECT
    u.usuario_id,
    u.nombre_completo,
    u.correo,
    u.password_hash,

    e.empresa_id,
    e.nombre AS empresa_nombre,

    r.rol_id,
    r.nombre AS rol

FROM usuarios u
JOIN usuarios_empresas ue
    ON ue.usuario_id = u.usuario_id
    AND ue.activo = 1
JOIN empresas e
    ON e.empresa_id = ue.empresa_id
    AND e.activa = 1
JOIN roles r
    ON r.rol_id = ue.rol_id

WHERE u.correo = ?
  AND u.activo = 1
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('s', $correo);
$stmt->execute();

$res = $stmt->get_result();
$u = $res->fetch_assoc();

if ($u && password_verify($pass, $u['password_hash'])) {

    auth_login([
        'usuario_id'     => (int)$u['usuario_id'],
        'nombre'         => $u['nombre_completo'],
        'correo'         => $u['correo'],

        // CONTEXTO DE NEGOCIO
        'empresa_id'     => (int)$u['empresa_id'],
        'empresa_nombre' => $u['empresa_nombre'],

        'rol_id'         => (int)$u['rol_id'],
        'rol'            => strtolower($u['rol']), // admin, director, etc.
    ]);

    echo json_encode([
        'success' => true
    ]);
    exit;
}

echo json_encode([
    'success' => false,
    'error' => 'Credenciales incorrectas'
]);
exit;
