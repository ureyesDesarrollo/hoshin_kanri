<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId = (int)($_POST['usuario_id'] ?? 0);
$hechoPor = (int)$_SESSION['usuario']['usuario_id'];

if ($usuarioId <= 0) {
  echo json_encode(['success' => false, 'message' => 'usuario_id inválido']);
  exit;
}

$sqlFind = "SELECT usuario_empresa_id
FROM usuarios_empresas
WHERE empresa_id = ?
  AND usuario_id = ?
  AND activo = 1
LIMIT 1";

$stmt = $conn->prepare($sqlFind);
$stmt->bind_param('ii', $empresaId, $usuarioId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
  echo json_encode(['success' => false, 'message' => 'Usuario no encontrado o ya inactivo']);
  exit;
}

$usuarioEmpresaId = (int)$row['usuario_empresa_id'];

$sql = "UPDATE usuarios_empresas ue
JOIN usuarios u ON u.usuario_id = ue.usuario_id
SET ue.activo = 0,
    u.activo = 0
WHERE ue.usuario_empresa_id = ?
  AND ue.empresa_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ii', $usuarioEmpresaId, $empresaId);

if (!$stmt->execute()) {
  echo json_encode(['success' => false, 'message' => 'Error al desactivar']);
  exit;
}

auditar($conn, $empresaId, 'usuario_empresa', $usuarioEmpresaId, 'DESACTIVAR', $hechoPor);

echo json_encode(['success' => true, 'message' => 'Usuario desactivado en la empresa']);
exit;
