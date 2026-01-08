<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$creadoPor = (int)$_SESSION['usuario']['usuario_id'];

$nombre = trim($_POST['nombre_completo'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$password = (string)($_POST['password'] ?? '');
$rolId = (int)($_POST['rol_id'] ?? 0);
$activo = isset($_POST['activo']) ? (int)$_POST['activo'] : 1;
$areaId = (int)($_POST['area_id'] ?? 0);


if ($nombre === '' || $correo === '' || $password === '' || $rolId <= 0 || $areaId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
  exit;
}

// hash bcrypt (PHP)
$passwordHash = password_hash($password, PASSWORD_BCRYPT);

// 1) Validar rol existe
$stmt = $conn->prepare("SELECT rol_id FROM roles WHERE rol_id = ? LIMIT 1");
$stmt->bind_param('i', $rolId);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
  echo json_encode(['success' => false, 'message' => 'rol_id inválido']);
  exit;
}

// 2) Evitar correo duplicado en la empresa
$sqlDup = "
SELECT 1
FROM usuarios u
WHERE u.correo = ?
LIMIT 1
";
$stmt = $conn->prepare($sqlDup);
$stmt->bind_param('s', $correo);
$stmt->execute();
if ($stmt->get_result()->fetch_assoc()) {
  echo json_encode(['success' => false, 'message' => 'El correo ya existe']);
  exit;
}

// Transacción
$conn->begin_transaction();

try {
  // 3) Crear usuario
  $sqlU = "
    INSERT INTO usuarios (nombre_completo, correo, password_hash, activo)
    VALUES (?, ?, ?, ?)
  ";
  $stmt = $conn->prepare($sqlU);
  $stmt->bind_param('sssi', $nombre, $correo, $passwordHash, $activo);
  if (!$stmt->execute() || $stmt->affected_rows !== 1) {
    throw new Exception('No se pudo crear el usuario');
  }
  $usuarioId = (int)$conn->insert_id;

  // 4) Asignar a empresa
  $sqlUE = "
    INSERT INTO usuarios_empresas (empresa_id, usuario_id, rol_id, area_id, activo)
    VALUES (?, ?, ?, ?, 1)
  ";
  $stmt = $conn->prepare($sqlUE);
  $stmt->bind_param('iiii', $empresaId, $usuarioId, $rolId, $areaId);
  if (!$stmt->execute() || $stmt->affected_rows !== 1) {
    throw new Exception('No se pudo asignar el usuario a la empresa');
  }
  $usuarioEmpresaId = (int)$conn->insert_id;

  $conn->commit();

  // Auditoría (1) usuario creado
  auditar($conn, $empresaId, 'usuario', $usuarioId, 'CREAR', $creadoPor);

  // Auditoría (2) asignación creada
  auditar($conn, $empresaId, 'usuario_empresa', $usuarioEmpresaId, 'CREAR', $creadoPor);

  echo json_encode([
    'success' => true,
    'message' => 'Usuario creado y asignado',
    'usuario_id' => $usuarioId,
    'usuario_empresa_id' => $usuarioEmpresaId
  ], JSON_UNESCAPED_UNICODE);
  exit;
} catch (Throwable $e) {
  $conn->rollback();
  echo json_encode(['success' => false, 'message' => $e->getMessage()]);
  exit;
}
