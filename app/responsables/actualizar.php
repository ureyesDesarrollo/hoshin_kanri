<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$editadoPor = (int)$_SESSION['usuario']['usuario_id'];

$usuarioId = (int)($_POST['usuario_id'] ?? 0);
$nombre = trim($_POST['nombre_completo'] ?? '');
$correo = trim($_POST['correo'] ?? '');
$rolId = (int)($_POST['rol_id'] ?? 0);
$activo = isset($_POST['activo']) ? (int)$_POST['activo'] : null;
$password = trim((string)($_POST['password'] ?? ''));
$areaId = (int)($_POST['area_id'] ?? 0);

if ($usuarioId <= 0 || $nombre === '' || $correo === '' || $rolId <= 0 || $areaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos incompletos']);
    exit;
}

// Traer estado actual
$sqlCur = "
SELECT
  u.usuario_id,
  u.nombre_completo,
  u.correo,
  u.activo AS usuario_activo,
  ue.usuario_empresa_id,
  ue.rol_id,
  ue.area_id
FROM usuarios_empresas ue
JOIN usuarios u ON u.usuario_id = ue.usuario_id
WHERE ue.empresa_id = ?
  AND u.usuario_id = ?
LIMIT 1
";
$stmt = $conn->prepare($sqlCur);
$stmt->bind_param('ii', $empresaId, $usuarioId);
$stmt->execute();
$cur = $stmt->get_result()->fetch_assoc();

if (!$cur) {
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado en esta empresa']);
    exit;
}

$conn->begin_transaction();

try {
    // Evitar correo duplicado en la empresa (si cambió)
    if ($correo !== $cur['correo']) {
        $sqlDup = "
    SELECT 1
    FROM usuarios_empresas ue
    JOIN usuarios u ON u.usuario_id = ue.usuario_id
    WHERE ue.empresa_id = ?
      AND u.correo = ?
      AND u.usuario_id <> ?
    LIMIT 1";
        $stmt = $conn->prepare($sqlDup);
        $stmt->bind_param('isi', $empresaId, $correo, $usuarioId);
        $stmt->execute();
        if ($stmt->get_result()->fetch_assoc()) {
            throw new Exception('El correo ya existe en esta empresa');
        }
    }

    // Update usuarios
    if ($password !== '') {
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $sqlU = "UPDATE usuarios SET nombre_completo=?, correo=?, password_hash=?, activo=COALESCE(?, activo) WHERE usuario_id=?";
        $stmt = $conn->prepare($sqlU);
        $stmt->bind_param('sssii', $nombre, $correo, $hash, $activo, $usuarioId);
    } else {
        $sqlU = "UPDATE usuarios SET nombre_completo=?, correo=?, activo=COALESCE(?, activo) WHERE usuario_id=?";
        $stmt = $conn->prepare($sqlU);
        $stmt->bind_param('ssii', $nombre, $correo, $activo, $usuarioId);
    }

    if (!$stmt->execute()) throw new Exception('No se pudo actualizar usuario');

    // Auditoría cambios básicos (simple, sin comparar campo por campo)
    auditar($conn, $empresaId, 'usuario', $usuarioId, 'EDITAR', $editadoPor);

    // Update rol y area en usuarios_empresas (si cambió)
    $usuarioEmpresaId = (int)$cur['usuario_empresa_id'];
    if ((int)$cur['rol_id'] !== $rolId || (int)$cur['area_id'] !== $areaId) {
        // validar rol
        $stmt = $conn->prepare("SELECT rol_id FROM roles WHERE rol_id = ? LIMIT 1");
        $stmt->bind_param('i', $rolId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('rol_id inválido');
        }

        // validar area
        $stmt = $conn->prepare("SELECT area_id FROM areas WHERE area_id = ? LIMIT 1");
        $stmt->bind_param('i', $areaId);
        $stmt->execute();
        if (!$stmt->get_result()->fetch_assoc()) {
            throw new Exception('area_id inválido');
        }

        $stmt = $conn->prepare("UPDATE usuarios_empresas SET rol_id=?, area_id=? WHERE usuario_empresa_id=? AND empresa_id=?");
        $stmt->bind_param('iiii', $rolId, $areaId, $usuarioEmpresaId, $empresaId);
        if (!$stmt->execute()) throw new Exception('No se pudo actualizar rol y area');

        auditar($conn, $empresaId, 'usuario_empresa', $usuarioEmpresaId, 'EDITAR', $editadoPor);
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Usuario actualizado'], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    exit;
}
