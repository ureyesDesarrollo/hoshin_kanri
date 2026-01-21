<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/EmailSender.php';

auth_require();
$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$milestoneId = (int)($_POST['milestone_id'] ?? 0);
$prioridad = (int)($_POST['prioridad'] ?? 0);

$sql_detalle = "SELECT titulo FROM milestones m
                WHERE m.milestone_id = ?  LIMIT 1";
$stmt_detalle = $conn->prepare($sql_detalle);
$stmt_detalle->bind_param('i', $milestoneId);
$stmt_detalle->execute();
$result_detalle = $stmt_detalle->get_result();

if ($result_detalle->num_rows === 0) {
  echo json_encode(['success' => false, 'message' => 'Milestone no encontrado'], JSON_UNESCAPED_UNICODE);
  exit;
}

$row_detalle = $result_detalle->fetch_assoc();
$stmt_detalle->close();

$sql = "UPDATE milestones SET prioridad = ? WHERE milestone_id = ?";
$sql_tareas = "UPDATE tareas SET prioridad = ? WHERE milestone_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt->bind_param('ii', $prioridad, $milestoneId);

if (!$stmt->execute()) {
  echo json_encode(['success' => false, 'message' => 'Error execute: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
  $stmt->close();
  exit;
}

$stmt->close();

$stmt_tareas = $conn->prepare($sql_tareas);
if (!$stmt_tareas) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt_tareas->bind_param('ii', $prioridad, $milestoneId);

if (!$stmt_tareas->execute()) {
  echo json_encode(['success' => false, 'message' => 'Error execute: ' . $stmt_tareas->error], JSON_UNESCAPED_UNICODE);
  $stmt_tareas->close();
  exit;
}

$stmt_tareas->close();


function h($s)
{
  return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$tituloMilestone = $row_detalle['titulo'] ?? 'Milestone'; // si ya hiciste fetch_assoc()

$usuarioNombre = $_SESSION['usuario']['nombre_completo'] ?? ($_SESSION['usuario']['nombre'] ?? 'Sistema');
$usuarioCorreo = $_SESSION['usuario']['correo'] ?? '';
$cuando = date('Y-m-d H:i:s');

function prioridadLabel($p)
{
  // Ajusta si tienes catálogo real
  switch ((int)$p) {
    case 1:
      return 'Puede esperar';
    case 2:
      return 'Importante';
    case 3:
      return 'Prioritario';
    default:
      return 'Sin definir';
  }
}

$prioridadTxt = prioridadLabel($prioridad);

$subject = "🔁 Prioridad actualizada: " . $tituloMilestone . " → " . $prioridadTxt;

$body = "
<!doctype html>
<html>
<head>
  <meta charset='utf-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Segoe UI Emoji', Arial, sans-serif;
      background: linear-gradient(135deg, #f6f8fb 0%, #edf2f7 100%);
      margin: 0;
      padding: 40px 24px;
      color: #1a202c;
      line-height: 1.5;
    }

    .wrap {
      max-width: 680px;
      margin: 0 auto;
    }

    .card {
      background: #ffffff;
      border-radius: 20px;
      overflow: hidden;
      box-shadow:
        0 10px 25px rgba(0, 0, 0, 0.05),
        0 5px 10px rgba(0, 0, 0, 0.02);
      border: 1px solid #e2e8f0;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
    }

    .card:hover {
      transform: translateY(-2px);
      box-shadow:
        0 15px 30px rgba(0, 0, 0, 0.08),
        0 8px 15px rgba(0, 0, 0, 0.03);
    }

    .header {
      background: linear-gradient(135deg, #006ec7 0%, #004d8c 100%);
      color: #ffffff;
      padding: 32px 32px 28px;
      position: relative;
      overflow: hidden;
    }

    .header::before {
      content: '';
      position: absolute;
      top: -50%;
      right: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
      opacity: 0.15;
    }

    .header h1 {
      margin: 0 0 12px;
      font-size: 24px;
      font-weight: 700;
      letter-spacing: -0.01em;
      position: relative;
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .header h1::before {
      content: '🔄';
      font-size: 26px;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.1));
    }

    .header-subtitle {
      color: rgba(255, 255, 255, 0.9);
      font-size: 15px;
      line-height: 1.5;
      position: relative;
      max-width: 90%;
    }

    .content {
      padding: 36px 32px;
    }

    .grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 24px;
      margin-bottom: 12px;
    }

    .info-item {
      background: #f8fafc;
      border-radius: 14px;
      padding: 20px;
      border: 1px solid #e2e8f0;
      transition: background-color 0.2s ease;
    }

    .info-item:hover {
      background: #f1f5f9;
    }

    .info-item.highlight {
      background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
      border-color: #bae6fd;
    }

    .k {
      color: #64748b;
      font-size: 12px;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      font-weight: 700;
      margin-bottom: 10px;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .k::before {
      content: '';
      display: block;
      width: 4px;
      height: 16px;
      background: #006ec7;
      border-radius: 2px;
    }

    .v {
      font-size: 16px;
      font-weight: 500;
      color: #1e293b;
      line-height: 1.4;
    }

    .v b {
      color: #004d8c;
      font-weight: 600;
    }

    .badge {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 10px 16px;
      border-radius: 12px;
      font-weight: 700;
      font-size: 13px;
      letter-spacing: 0.02em;
      border: 2px solid transparent;
      transition: all 0.2s ease;
    }

    .badge i {
      font-style: normal;
      font-size: 14px;
    }

    .b-alta {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
      color: #991b1b;
      border-color: #fca5a5;
    }

    .b-alta::before {
      content: '🔥';
    }

    .b-media {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
      color: #92400e;
      border-color: #fbbf24;
    }

    .b-media::before {
      content: '⚡';
    }

    .b-baja {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
      color: #065f46;
      border-color: #34d399;
    }

    .b-baja::before {
      content: '🌿';
    }

    .b-none {
      background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
      color: #475569;
      border-color: #cbd5e1;
    }

    .b-none::before {
      content: '➖';
    }

    .note {
      background: linear-gradient(135deg, #fefce8 0%, #fef9c3 100%);
      border: 1px solid #fde047;
      border-radius: 14px;
      padding: 20px;
      margin-top: 32px;
      position: relative;
      overflow: hidden;
    }

    .note::before {
      content: '💡';
      position: absolute;
      top: 16px;
      left: 20px;
      font-size: 20px;
      opacity: 0.2;
    }

    .note-content {
      padding-left: 32px;
    }

    .note-title {
      color: #92400e;
      font-size: 14px;
      font-weight: 700;
      text-transform: uppercase;
      letter-spacing: 0.05em;
      margin-bottom: 6px;
    }

    .note-text {
      color: #854d0e;
      font-size: 14px;
      line-height: 1.5;
    }

    .footer {
      padding: 24px 32px;
      background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
      color: #cbd5e1;
      border-top: 1px solid #334155;
    }

    .footer-content {
      display: flex;
      justify-content: space-between;
      align-items: center;
    }

    .footer-logo {
      display: flex;
      align-items: center;
      gap: 12px;
      font-weight: 600;
      color: #f1f5f9;
      font-size: 16px;
    }

    .footer-logo::before {
      content: '🎯';
      font-size: 20px;
    }

    .footer-info {
      font-size: 13px;
      color: #94a3b8;
    }

    .separator {
      height: 1px;
      background: linear-gradient(90deg, transparent, #e2e8f0, transparent);
      margin: 28px 0;
    }

    a {
      color: #006ec7;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.2s ease;
      border-bottom: 1px dotted transparent;
    }

    a:hover {
      color: #004d8c;
      border-bottom-color: #006ec7;
    }

    .user-avatar {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 36px;
      height: 36px;
      background: linear-gradient(135deg, #006ec7, #004d8c);
      color: white;
      border-radius: 50%;
      font-weight: 600;
      margin-right: 10px;
      font-size: 14px;
    }

    @media (max-width: 640px) {
      body {
        padding: 20px 16px;
      }

      .header {
        padding: 24px 24px 20px;
      }

      .content {
        padding: 24px 20px;
      }

      .grid {
        grid-template-columns: 1fr;
        gap: 16px;
      }

      .info-item {
        padding: 16px;
      }

      .header h1 {
        font-size: 20px;
      }

      .header-subtitle {
        font-size: 14px;
      }

      .footer-content {
        flex-direction: column;
        gap: 12px;
        align-items: flex-start;
      }

      .note {
        padding: 16px;
      }
    }

    @media (max-width: 480px) {
      .badge {
        padding: 8px 14px;
        font-size: 12px;
      }

      .v {
        font-size: 15px;
      }
    }
  </style>
</head>
<body>
  <div class='wrap'>
    <div class='card'>
      <div class='header'>
        <h1>Actualización de Prioridad</h1>
        <div class='header-subtitle'>
          Se ha actualizado la prioridad del milestone y de todas sus tareas asociadas automáticamente.
        </div>
      </div>

      <div class='content'>
        <div class='grid'>
          <div class='info-item highlight'>
            <div class='k'>Milestone</div>
            <div class='v'><b>" . h($tituloMilestone) . "</b></div>
          </div>

          <div class='info-item'>
            <div class='k'>Nueva Prioridad</div>
            <div class='v'>";

// Generar badge según prioridad
if ($prioridadTxt === 'Prioritario') {
  $body .= "<span class='badge b-alta'><i>Prioritario</i></span>";
} elseif ($prioridadTxt === 'Importante') {
  $body .= "<span class='badge b-media'><i>Importante</i></span>";
} elseif ($prioridadTxt === 'Puede esperar') {
  $body .= "<span class='badge b-baja'><i>Puede esperar</i></span>";
} else {
  $body .= "<span class='badge b-none'><i>Sin prioridad definida</i></span>";
}

$body .= "</div>
          </div>
          <div class='info-item'>
            <div class='k'>Acción Realizada Por</div>
            <div class='v'>
              <div style='display: flex; align-items: center; margin-bottom: 8px;'>
                <span class='user-avatar'>" . substr(h($usuarioNombre), 0, 1) . "</span>
                <b>" . h($usuarioNombre) . "</b>
              </div>";

if ($usuarioCorreo) {
  $body .= "<div style='color: #64748b; font-size: 14px;'>
                  " . h($usuarioCorreo) . "
                </div>";
}

$body .= "</div>
          </div>

          <div class='info-item'>
            <div class='k'>Fecha y Hora</div>
            <div class='v'>
              <b>" . h($cuando) . "</b>
            </div>
          </div>

          <div class='info-item'>
            <div class='k'>Ámbito de Cambio</div>
            <div class='v'>
              ✅ Milestone principal<br>
              ✅ Tareas asociadas<br>
            </div>
          </div>
        </div>

        <div class='separator'></div>
      </div>

      <div class='footer'>
        <div class='footer-content'>
          <div class='footer-logo'>
            Hoshin Kanri
          </div>
          <div class='footer-info'>
            Notificación automática · " . date('Y') . " · Sistema de Gestión
          </div>
        </div>
      </div>
    </div>
  </div>
</body>
</html>";

$mail = new MailSender();
$ok = $mail->sendMail($subject, $body, ['desarrollo@progel.com.mx']);

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
exit;
