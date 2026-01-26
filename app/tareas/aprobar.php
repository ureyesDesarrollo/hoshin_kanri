<?php
// Aprobar tarea (MySQLi)

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';
require_once __DIR__ . '/../core/EmailSender.php';

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
   * 3.1) Obtener información para correo
   */
  $sqlMail = "
    SELECT
        t.titulo AS nombre_tarea,
        t.fecha_fin,
        t.aprobada_en,
        u_resp.correo AS email_responsable,
        u_apr.nombre_completo AS nombre_aprobador
    FROM tareas t
    JOIN usuarios u_resp ON u_resp.usuario_id = t.responsable_usuario_id
    JOIN usuarios u_apr  ON u_apr.usuario_id = t.aprobado_por
    WHERE t.tarea_id = ?
    LIMIT 1
";

  $stmt = $conn->prepare($sqlMail);
  if (!$stmt) throw new Exception("Error prepare mail data: " . $conn->error);

  $stmt->bind_param('i', $tareaId);
  $stmt->execute();
  $mailData = $stmt->get_result()->fetch_assoc();
  $stmt->close();

  if (!$mailData) {
    throw new Exception('No se pudo obtener información para el correo');
  }

  $estadoTiempo = ($estatus === 6)
    ? 'Fuera de tiempo'
    : 'A tiempo';

  $nombreTarea     = $mailData['nombre_tarea'];
  $fechaAprobacion = $mailData['aprobada_en'];
  $nombreAprobador = $mailData['nombre_aprobador'];

  $correoBody = "
      <table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f6f8;padding:20px'>
  <tr>
    <td align='center'>
      <table cellpadding='0' cellspacing='0' width='100%' style='max-width:720px;background:#ffffff;border-radius:8px;padding:20px;font-family:Arial,sans-serif;color:#111'>

        <tr>
          <td>
            <p>Hola,</p>

            <p>
              Te informamos que la tarea
              <strong>" . htmlspecialchars($nombreTarea ?? '', ENT_QUOTES, 'UTF-8') . "</strong>
              ha sido <strong style='color:#27ae60'>aprobada exitosamente</strong>.
            </p>

            <table cellpadding='8' cellspacing='0' width='100%' style='border-collapse:collapse;border:1px solid #ddd;margin-bottom:20px'>
              <tr>
                <td style='background:#f6f6f6;width:180px'><b>Estado</b></td>
                <td>" . htmlspecialchars($estadoTiempo ?? '', ENT_QUOTES, 'UTF-8') . "</td>
              </tr>
              <tr>
                <td style='background:#f6f6f6'><b>Fecha de aprobación</b></td>
                <td>" . htmlspecialchars($fechaAprobacion ?? '', ENT_QUOTES, 'UTF-8') . "</td>
              </tr>
              <tr>
                <td style='background:#f6f6f6'><b>Aprobada por</b></td>
                <td>" . htmlspecialchars($nombreAprobador ?? '', ENT_QUOTES, 'UTF-8') . "</td>
              </tr>
            </table>

            <div style='background:#f0f9f0;color:#2d3436;border-left:4px solid #27ae60;padding:12px;margin-bottom:20px;border-radius:0 4px 4px 0'>
              <p style='margin:0;font-weight:bold'><i class='fas fa-check-circle' style='color:#27ae60;margin-right:5px'></i> Tarea finalizada</p>
              <p style='margin:8px 0 0 0'>La tarea se considera <strong>finalizada</strong> y quedó registrada en el sistema.</p>
            </div>

            <p style='background:#f8f9fa;padding:12px;border-radius:4px;border-left:4px solid #006ec7'>
              <strong>¡Gracias por tu trabajo y compromiso!</strong>
            </p>

            <!-- BOTÓN PARA VER TAREA FINALIZADA (opcional) -->
            <!--
            <table role='presentation' cellpadding='0' cellspacing='0' style='margin-top:20px'>
              <tr>
                <td align='center' bgcolor='#27ae60' style='border-radius:8px'>
                  <a href='" . htmlspecialchars($link ?? '#', ENT_QUOTES, 'UTF-8') . "'
                    target='_blank'
                    style='display:inline-block;padding:12px 18px;font-family:Arial,sans-serif;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold'>
                    Ver tarea finalizada
                  </a>
                </td>
              </tr>
            </table>
            -->

            <p style='color:#777;font-size:12px;margin-top:20px'>
              Saludos,<br>
              <strong>El equipo de gestión</strong><br>
              Notificación automática · Hoshin Kanri
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
      ";


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


  // Enviar correo al responsable
  $emailSender = new MailSender();
  $emailSender->sendMail(
    'Tarea aprobada',
    $correoBody,
    [$mailData['email_responsable']]
  );

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
