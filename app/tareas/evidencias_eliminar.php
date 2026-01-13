<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

auth_require();
$conn = db(); // mysqli

header('Content-Type: application/json; charset=utf-8');

$evidenciaId = (int)($_POST['evidencia_id'] ?? 0);
$usuarioId   = (int)($_SESSION['usuario']['usuario_id'] ?? 0);

if ($evidenciaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Evidencia inválida']);
    exit;
}
if ($usuarioId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Usuario inválido']);
    exit;
}

// 1) Obtener evidencia (y tarea_id para permisos)
$stmt = $conn->prepare("
  SELECT evidencia_id, tarea_id, carpeta_relativa, nombre_guardado, nombre_original
  FROM tarea_evidencias
  WHERE evidencia_id = ? AND eliminado = 0
  LIMIT 1
");
$stmt->bind_param("i", $evidenciaId);
$stmt->execute();
$res = $stmt->get_result();
$ev = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$ev) {
    echo json_encode(['success' => false, 'message' => 'Evidencia no encontrada o ya eliminada']);
    exit;
}


// 2) Construir ruta física del archivo
$NAS_BASE = '\\\\NAS01\\HK_EVIDENCIAS\\'; // AJUSTA
$path = $NAS_BASE . $ev['carpeta_relativa'] . $ev['nombre_guardado'];

// 3) Borrar archivo físico primero (para evitar basura)
if (is_file($path)) {
    if (!@unlink($path)) {
        echo json_encode([
            'success' => false,
            'message' => 'No se pudo borrar el archivo físico en NAS (permisos/bloqueo).'
        ]);
        exit;
    }
} else {
    // Si no existe el archivo, decide política:
    // a) permitir limpiar BD igual (recomendado para "sanear")
    // b) bloquear
    // Aquí elegimos "sanear": seguimos y eliminamos el registro lógico.
}

// 4) Actualizar BD (soft delete) + evento en transacción
try {
    $conn->begin_transaction();

    // Soft delete
    $stmt = $conn->prepare("
    UPDATE tarea_evidencias
    SET eliminado = 1, eliminado_en = NOW()
    WHERE evidencia_id = ? AND eliminado = 0
  ");
    if (!$stmt) throw new Exception("Prepare update evidencia: " . $conn->error);

    $stmt->bind_param("i", $evidenciaId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Execute update evidencia: " . $conn->error);
    }
    $stmt->close();

    // Evento
    $tipo = 'evidence_deleted';
    $payload = json_encode([
        'evidencia_id'    => (int)$ev['evidencia_id'],
        'nombre_original' => $ev['nombre_original'],
        'nombre_guardado' => $ev['nombre_guardado'],
        'carpeta_relativa' => $ev['carpeta_relativa']
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $stmt = $conn->prepare("
    INSERT INTO tarea_eventos (tarea_id, tipo, actor_usuario_id, payload_json)
    VALUES (?, ?, ?, ?)
  ");
    if (!$stmt) throw new Exception("Prepare insert evento: " . $conn->error);

    $tareaId = (int)$ev['tarea_id'];
    $stmt->bind_param("isis", $tareaId, $tipo, $usuarioId, $payload);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Execute insert evento: " . $conn->error);
    }
    $stmt->close();

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'Evidencia eliminada correctamente']);
    exit;
} catch (Throwable $e) {
    $conn->rollback();

    // OJO: aquí el archivo YA se borró (si existía).
    // Si quieres máxima consistencia, aquí podrías registrar un log para revisión.
    echo json_encode([
        'success' => false,
        'message' => 'Archivo borrado, pero falló la actualización en BD. Revisar.',
        'error'   => $e->getMessage()
    ]);
    exit;
}
