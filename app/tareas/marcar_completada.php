<?php
// Enviar tarea a revisión (reemplaza "Completar tarea")

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db(); // mysqli

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);
$tareaId   = (int)($_POST['tarea_id'] ?? 0);

if ($empresaId <= 0 || $usuarioId <= 0 || $tareaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
    exit;
}

/**
 * 1) Validar que la tarea sea del usuario y pertenezca a la empresa
 *    y además obtener la jerarquía para calcular aprobador.
 */
$sql = "
SELECT
  t.tarea_id,
  t.responsable_usuario_id AS tarea_responsable,
  t.estatus,
  t.completada,
  t.milestone_id,
  m.responsable_usuario_id AS milestone_responsable,
  m.estrategia_id,
  e.empresa_id,
  e.responsable_usuario_id AS estrategia_responsable
FROM tareas t
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
WHERE t.tarea_id = ?
  AND t.responsable_usuario_id = ?
  AND e.empresa_id = ?
LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
    exit;
}

$stmt->bind_param('iii', $tareaId, $usuarioId, $empresaId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// 2) Validaciones de estado (evitar doble envío)
$estatus = (int)($row['estatus'] ?? 1);
$completada = (int)($row['completada'] ?? 0);

if ($completada === 1 || $estatus === 4) {
    echo json_encode(['success' => false, 'message' => 'La tarea ya está aprobada/finalizada']);
    exit;
}
if ($estatus === 3) {
    echo json_encode(['success' => false, 'message' => 'La tarea ya está en revisión']);
    exit;
}

// 3) (Opcional pero recomendado) Validar que tenga al menos 1 evidencia antes de enviar a revisión
$sqlEv = "SELECT COUNT(*) AS c FROM tarea_evidencias WHERE tarea_id = ? AND eliminado = 0";
$stmt = $conn->prepare($sqlEv);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error prepare evidencias: ' . $conn->error]);
    exit;
}
$stmt->bind_param('i', $tareaId);
$stmt->execute();
$evRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

$evCount = (int)($evRow['c'] ?? 0);
if ($evCount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Debes subir al menos una evidencia antes de enviar a revisión']);
    exit;
}

// 4) Calcular aprobador según regla
$tareaResp     = (int)$row['tarea_responsable'];
$milestoneResp = (int)$row['milestone_responsable'];
$estrategiaResp = (int)$row['estrategia_responsable'];

if ($milestoneResp <= 0) {
    echo json_encode(['success' => false, 'message' => 'El milestone no tiene responsable asignado']);
    exit;
}
if ($estrategiaResp <= 0) {
    echo json_encode(['success' => false, 'message' => 'La estrategia no tiene responsable asignado']);
    exit;
}

$nivel = 'milestone';
$aprobadorId = $milestoneResp;

// Si el responsable de la tarea es el mismo que el del milestone, sube a estrategia
if ($tareaResp === $milestoneResp) {
    $nivel = 'estrategia';
    $aprobadorId = $estrategiaResp;
}

// 5) Transacción: actualizar tarea a revisión + crear aprobación pendiente + notificar + evento
try {
    $conn->begin_transaction();

    // 5.1 Actualizar tarea -> en revisión
    $sqlUp = "
      UPDATE tareas
      SET estatus = 3,
          enviada_a_revision_en = NOW()
      WHERE tarea_id = ?
        AND estatus <> 3
    ";
    $stmt = $conn->prepare($sqlUp);
    if (!$stmt) throw new Exception("Error prepare update tarea: " . $conn->error);

    $stmt->bind_param('i', $tareaId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Error execute update tarea: " . $stmt->error);
    }
    $stmt->close();

    // 5.2 Crear aprobación pendiente
    $sqlInsA = "
      INSERT INTO tarea_aprobaciones
        (tarea_id, nivel, solicitante_usuario_id, aprobador_usuario_id, estatus)
      VALUES
        (?, ?, ?, ?, 1)
    ";
    $stmt = $conn->prepare($sqlInsA);
    if (!$stmt) throw new Exception("Error prepare insert aprobacion: " . $conn->error);

    $stmt->bind_param('isii', $tareaId, $nivel, $usuarioId, $aprobadorId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Error execute insert aprobacion: " . $stmt->error);
    }
    $aprobacionId = (int)$conn->insert_id;
    $stmt->close();

    // 5.3 Notificación al aprobador
    $tipo   = 'tarea_en_revision';
    $titulo = 'Tarea en revisión';
    $cuerpo = "Se envió una tarea a revisión (nivel: {$nivel}).";
    $entTipo = 'tarea';
    $entId  = $tareaId;

    $dataJson = json_encode([
        'tarea_id' => $tareaId,
        'nivel' => $nivel,
        'aprobacion_id' => $aprobacionId
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $sqlNotif = "
      INSERT INTO notificaciones
        (usuario_id, tipo, titulo, cuerpo, entidad_tipo, entidad_id, data_json)
      VALUES
        (?, ?, ?, ?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($sqlNotif);
    if (!$stmt) throw new Exception("Error prepare insert notif: " . $conn->error);

    $stmt->bind_param('issssis', $aprobadorId, $tipo, $titulo, $cuerpo, $entTipo, $entId, $dataJson);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Error execute insert notif: " . $stmt->error);
    }
    $stmt->close();

    // 5.4 Evento en timeline
    $evtTipo = 'sent_review';
    $payload = json_encode([
        'tarea_id' => $tareaId,
        'aprobacion_id' => $aprobacionId,
        'nivel' => $nivel,
        'aprobador_usuario_id' => $aprobadorId,
        'evidencias' => $evCount
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    $sqlEvt = "
      INSERT INTO tarea_eventos (tarea_id, tipo, actor_usuario_id, payload_json)
      VALUES (?, ?, ?, ?)
    ";
    $stmt = $conn->prepare($sqlEvt);
    if (!$stmt) throw new Exception("Error prepare insert evento: " . $conn->error);

    $stmt->bind_param('isis', $tareaId, $evtTipo, $usuarioId, $payload);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Error execute insert evento: " . $stmt->error);
    }
    $stmt->close();

    // 5.5 Auditoría central (tu función actual)
    // Cambia 'CERRAR' por algo más correcto, ej: 'ENVIAR_REVISION'
    auditar($conn, $empresaId, 'tarea', $tareaId, 'ENVIAR_REVISION', $usuarioId);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Tarea enviada a revisión',
        'data' => [
            'tarea_id' => $tareaId,
            'nivel' => $nivel,
            'aprobador_usuario_id' => $aprobadorId,
            'aprobacion_id' => $aprobacionId
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error al enviar a revisión',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
