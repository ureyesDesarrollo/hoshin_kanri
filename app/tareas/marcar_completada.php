<?php
// Enviar tarea a revisión (reemplaza "Completar tarea")

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';
require_once __DIR__ . '/../core/EmailSender.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

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

  $sqlMail = "
SELECT
  t.tarea_id,
  t.titulo AS tarea_titulo,
  t.fecha_fin,
  t.comentarios_responsable,

  m.titulo AS milestone_titulo,
  e.titulo AS estrategia_titulo,

  sol.nombre_completo AS solicitante_nombre,
  apr.usuario_id AS usuario_id,
  apr.nombre_completo AS aprobador_nombre,
  apr.correo AS aprobador_correo

FROM tareas t
JOIN milestones m ON m.milestone_id = t.milestone_id
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
JOIN usuarios sol ON sol.usuario_id = ?
JOIN usuarios apr ON apr.usuario_id = ?

WHERE t.tarea_id = ?
LIMIT 1
";

  $stmt = $conn->prepare($sqlMail);
  if (!$stmt) throw new Exception("Error prepare mail data: " . $conn->error);

  $stmt->bind_param('iii', $usuarioId, $aprobadorId, $tareaId);
  $stmt->execute();
  $mailRow = $stmt->get_result()->fetch_assoc();

  switch ($mailRow['usuario_id'] ?? 0) {
    case 32:
      $mailRow['aprobador_correo'] = 'analistacalidad@progel.com.mx';
      break;
    case 56:
      $mailRow['aprobador_correo'] = 'supervisormantenimiento@progel.com.mx';
      break;
    default:
      $mailRow['aprobador_correo'] = $mailRow['aprobador_correo'];
      break;
  }
  $stmt->close();

  // Guardar payload para enviar DESPUÉS del commit
  $mailPayload = [
    'to' => !empty($mailRow['aprobador_correo']) ? [$mailRow['aprobador_correo']] : [],
    'data' => $mailRow,
    'nivel' => $nivel
  ];

  $conn->commit();

  if (!empty($mailPayload['to'])) {
    $ms = new MailSender();
    $d = $mailPayload['data'];

    $baseUrl = 'http://192.168.1.105';

    $link = $baseUrl . '/hoshin_kanri/public/detalle.php?tarea_id=' . urlencode((string)$d['tarea_id']);

    $subject = "Tarea para revisión: " . ($d['tarea_titulo'] ?? ('#' . $tareaId));

    $html = "
<table width='100%' cellpadding='0' cellspacing='0' style='background:#f4f6f8;padding:20px'>
  <tr>
    <td align='center'>
      <table cellpadding='0' cellspacing='0' width='100%' style='max-width:720px;background:#ffffff;border-radius:8px;padding:20px;font-family:Arial,sans-serif;color:#111'>

        <tr>
          <td>
            <p>Hola <b>" . htmlspecialchars($d['aprobador_nombre'] ?? '', ENT_QUOTES, 'UTF-8') . "</b>,</p>

            <p>
              Has recibido una tarea a revisión
              (nivel: <b>" . htmlspecialchars($mailPayload['nivel'], ENT_QUOTES, 'UTF-8') . "</b>).
            </p>

            <p><b>Comentarios de la tarea:</b></p>

            <div style='font-style:italic;color:#555;border-left:4px solid #ddd;padding-left:10px;margin-bottom:16px'>
              " . nl2br(htmlspecialchars($d['comentarios_responsable'] ?? '', ENT_QUOTES, 'UTF-8')) . "
            </div>

            <table cellpadding='8' cellspacing='0' width='100%' style='border-collapse:collapse;border:1px solid #ddd'>
              <tr>
                <td style='background:#f6f6f6;width:180px'><b>Tarea</b></td>
                <td>" . htmlspecialchars($d['tarea_titulo'] ?? '', ENT_QUOTES, 'UTF-8') . " (ID: " . (int)$d['tarea_id'] . ")</td>
              </tr>
              <tr>
                <td style='background:#f6f6f6'><b>Milestone</b></td>
                <td>" . htmlspecialchars($d['milestone_titulo'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
              </tr>
              <tr>
                <td style='background:#f6f6f6'><b>Estrategia</b></td>
                <td>" . htmlspecialchars($d['estrategia_titulo'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
              </tr>
              <tr>
                <td style='background:#f6f6f6'><b>Fecha límite</b></td>
                <td>" . htmlspecialchars($d['fecha_fin'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
              </tr>
              <tr>
                <td style='background:#f6f6f6'><b>Solicitante</b></td>
                <td>" . htmlspecialchars($d['solicitante_nombre'] ?? '', ENT_QUOTES, 'UTF-8') . "</td>
              </tr>
            </table>

            <!-- BOTÓN -->
           <table role='presentation' cellpadding='0' cellspacing='0' style='margin-top:20px'>
            <tr>
              <td align='center' bgcolor='#006ec7' style='border-radius:8px'>
                <a href='" . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . "'
                  target='_blank'
                  style='display:inline-block;padding:12px 18px;font-family:Arial,sans-serif;font-size:14px;color:#ffffff;text-decoration:none;font-weight:bold'>
                  Revisar tarea
                </a>
              </td>
            </tr>
          </table>
            <p style='color:#777;font-size:12px;margin-top:20px'>
              Notificación automática · Hoshin Kanri
            </p>
          </td>
        </tr>

      </table>
    </td>
  </tr>
</table>
";

    // SOLO aprobador (sin CC)
    $ms->sendMail($subject, $html, $mailPayload['to']);
  }

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
