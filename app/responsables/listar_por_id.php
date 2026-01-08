<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId = (int)($_GET['id'] ?? 0);

if ($usuarioId <= 0) {
  echo json_encode(['success' => false, 'message' => 'usuario_id inválido']);
  exit;
}

$sql = "SELECT
  u.usuario_id,
  u.nombre_completo,
  u.correo,
  u.activo AS usuario_activo,
  ue.usuario_empresa_id,
  ue.rol_id,
  r.nombre AS rol,
  ue.area_id,
  a.nombre AS area,
  ue.activo AS usuario_empresa_activo
FROM usuarios_empresas ue
JOIN usuarios u ON u.usuario_id = ue.usuario_id
JOIN roles r ON r.rol_id = ue.rol_id
LEFT JOIN areas a ON a.area_id = ue.area_id
WHERE ue.empresa_id = ?
  AND u.usuario_id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $empresaId, $usuarioId);
$stmt->execute();

$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
  echo json_encode(['success' => false, 'message' => 'Usuario no encontrado en esta empresa']);
  exit;
}

echo json_encode(['success' => true, 'data' => $row], JSON_UNESCAPED_UNICODE);
exit;
