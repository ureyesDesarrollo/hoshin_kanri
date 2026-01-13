<?php
// Aprobar tarea (MySQLi)

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

try {
    $conn->begin_transaction();

    /**
     * 1) Verificar:
     * - tarea pertenece a empresa
     * - existe aprobación pendiente donde aprobador = usuario actual
     */
    $sql = "
    SELECT
      ta.aprobacion_id,
      ta.nivel,
      ta.aprobador_usuario_id,
      t.tarea_id,
      t.responsable_usuario_id AS tarea_responsable,
      t.estatus,
      t.completada,
      t.fecha_fin
    FROM tarea_aprobaciones ta
    JOIN tareas t ON t.tarea_id = ta.tarea_id
    JOIN milestones m ON m.milestone_id = t.milestone_id
    JOIN estrategias e ON e.estrategia_id = m.estrategia_id
    WHERE ta.tarea_id = ?
      AND ta.estatus = 1
      AND ta.aprobador_usuario_id = ?
      AND e.empresa_id = ?
    ORDER BY ta.solicitado_en DESC
    LIMIT 1
    ";

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception("Error prepare check: " . $conn->error);

    $stmt->bind_param('iii', $tareaId, $usuarioId, $empresaId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if (!$row) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'No autorizado o no hay aprobación pendiente']);
        exit;
    }

    $aprobacionId = (int)$row['aprobacion_id'];
    $tareaRespId  = (int)$row['tarea_responsable'];

    // Si ya está aprobada, no repetir
    $estatus = (int)$row['estatus'];
    if ($estatus === 4 || (int)$row['completada'] === 1) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'La tarea ya está aprobada/finalizada']);
        exit;
    }

    /**
     * 2) Cerrar aprobación pendiente -> aprobada
     */
    $sqlUpA = "
      UPDATE tarea_aprobaciones
      SET estatus = 2,
          resuelto_en = NOW()
      WHERE aprobacion_id = ?
        AND estatus = 1
    ";
    $stmt = $conn->prepare($sqlUpA);
    if (!$stmt) throw new Exception("Error prepare update aprobacion: " . $conn->error);

    $stmt->bind_param('i', $aprobacionId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Error execute update aprobacion: " . $stmt->error);
    }
    $stmt->close();

    /**
     * 3) Actualizar tarea -> aprobada
     */

    // Si esta fuera de tiempo , marcar como completada fuera de tiempo = 6
    $fechaActual = date('Y-m-d');
    $fechaFin    = $row['fecha_fin'];
    if ($fechaActual > $fechaFin) {
        $estatus = 6;
    } else {
        $estatus = 4;
    }
    $sqlUpT = "
      UPDATE tareas
      SET estatus = ?,
          completada = 1,
          completada_en = COALESCE(completada_en, NOW()),
          aprobada_en = NOW(),
          aprobado_por = ?
      WHERE tarea_id = ?
    ";
    $stmt = $conn->prepare($sqlUpT);
    if (!$stmt) throw new Exception("Error prepare update tarea: " . $conn->error);

    $stmt->bind_param('iii', $estatus, $usuarioId, $tareaId);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Error execute update tarea: " . $stmt->error);
    }
    $stmt->close();

    /**
     * 4) Evento timeline
     */
    $evtTipo = 'approved';
    $payload = json_encode([
        'tarea_id' => $tareaId,
        'aprobacion_id' => $aprobacionId,
        'aprobador_usuario_id' => $usuarioId
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

    /**
     * 5) Notificación al responsable de la tarea
     */
    $tipo   = 'tarea_aprobada';
    $titulo = 'Tarea aprobada';
    $cuerpo = 'Tu tarea fue aprobada.';
    $entTipo = 'tarea';
    $entId  = $tareaId;

    $dataJson = json_encode([
        'tarea_id' => $tareaId,
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

    $stmt->bind_param('issssis', $tareaRespId, $tipo, $titulo, $cuerpo, $entTipo, $entId, $dataJson);
    if (!$stmt->execute()) {
        $stmt->close();
        throw new Exception("Error execute insert notif: " . $stmt->error);
    }
    $stmt->close();

    /**
     * 6) Auditoría central
     */
    auditar($conn, $empresaId, 'tarea', $tareaId, 'APROBAR', $usuarioId);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Tarea aprobada',
        'data' => [
            'tarea_id' => $tareaId,
            'aprobacion_id' => $aprobacionId
        ]
    ], JSON_UNESCAPED_UNICODE);
    exit;
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => 'Error al aprobar',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
