<?php
// Subir evidencia de tarea (MySQLi)
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../core/EmailSender.php';

auth_require();
$conn = db();

header('Content-Type: application/json; charset=utf-8');
// Antes de leer $_POST
$maxPost = 650 * 1024 * 1024; // ponlo igual a tu post_max_size real
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);

header('Content-Type: application/json; charset=utf-8');

// 1) detectar POST truncado por tamaño
$contentLength = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
if ($contentLength > 0 && empty($_POST) && empty($_FILES)) {
  echo json_encode(['success' => false, 'message' => 'Carga rechazada: excede límites del servidor (post_max_size/upload_max_filesize).']);
  exit;
}

// 2) validar archivo
if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
  echo json_encode(['success' => false, 'message' => 'No se recibió archivo']);
  exit;
}
if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  echo json_encode(['success' => false, 'message' => 'Error en carga del archivo', 'code' => $_FILES['file']['error']]);
  exit;
}

// 3) ya con archivo ok, validar tarea_id
$tareaId = (int)($_POST['tarea_id'] ?? 0);
if ($tareaId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Tarea inválida (tarea_id no llegó en POST)']);
  exit;
}



$tareaId   = (int)($_POST['tarea_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);

if ($tareaId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Tarea inválida']);
  exit;
}
if ($usuarioId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Usuario inválido']);
  exit;
}

// 1) Validar archivo
if (!isset($_FILES['file']) || !is_array($_FILES['file'])) {
  echo json_encode(['success' => false, 'message' => 'No se recibió archivo']);
  exit;
}
if ($_FILES['file']['error'] !== UPLOAD_ERR_OK) {
  echo json_encode(['success' => false, 'message' => 'Error en carga del archivo', 'code' => $_FILES['file']['error']]);
  exit;
}

// 2) Validaciones tamaño/extensión
$maxBytes = 500 * 1024 * 1024; // 500MB
$size = (int)($_FILES['file']['size'] ?? 0);

if ($size <= 0 || $size > $maxBytes) {
  echo json_encode(['success' => false, 'message' => 'Tamaño inválido o excede límite']);
  exit;
}

$nombreOriginal = (string)($_FILES['file']['name'] ?? '');
$ext = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));

$allowed = ['pdf', 'png', 'jpg', 'jpeg', 'docx', 'xlsx', 'pptx', 'txt', 'mp4', 'mp3'];
if ($ext === '' || !in_array($ext, $allowed, true)) {
  echo json_encode(['success' => false, 'message' => 'Extensión no permitida']);
  exit;
}

function sanitizeFileName(string $name): string
{
  $name = preg_replace('/[^\w\.-]/u', '_', $name);
  $name = preg_replace('/_+/', '_', $name);
  return trim($name, '._');
}

// 3) Obtener jerarquía por tarea_id: empresa/estrategia/milestone
$sql = "
    SELECT
      t.tarea_id,
      t.milestone_id,
      m.estrategia_id,
      e.empresa_id
    FROM tareas t
    JOIN milestones m  ON m.milestone_id = t.milestone_id
    JOIN estrategias e ON e.estrategia_id = m.estrategia_id
    WHERE t.tarea_id = ?
    LIMIT 1
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare jerarquía', 'error' => $conn->error]);
  exit;
}
$stmt->bind_param("i", $tareaId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res ? $res->fetch_assoc() : null;
$stmt->close();

if (!$row) {
  echo json_encode(['success' => false, 'message' => 'Tarea no encontrada']);
  exit;
}

$empresaId    = (int)$row['empresa_id'];
$estrategiaId = (int)$row['estrategia_id'];
$milestoneId  = (int)$row['milestone_id'];

// 4) Construir carpeta relativa y crear carpetas si no existen
$fecha = date('Y-m-d');

$carpetaRelativa =
  "empresa_{$empresaId}\\" .
  "estrategia_{$estrategiaId}\\" .
  "milestone_{$milestoneId}\\" .
  "tarea_{$tareaId}\\" .
  "{$fecha}\\";

$rutaFisica = NAS_BASE . $carpetaRelativa;

if (!is_dir($rutaFisica)) {
  if (!mkdir($rutaFisica, 0775, true)) {
    echo json_encode(['success' => false, 'message' => 'No se pudo crear carpeta en NAS']);
    exit;
  }
}

// 5) Generar nombre guardado y mover archivo a NAS
$baseName = sanitizeFileName(pathinfo($nombreOriginal, PATHINFO_FILENAME));
$nombreGuardado = time() . "_{$usuarioId}_{$baseName}.{$ext}";
$rutaArchivo = $rutaFisica . $nombreGuardado;

if (!move_uploaded_file($_FILES['file']['tmp_name'], $rutaArchivo)) {
  echo json_encode(['success' => false, 'message' => 'No se pudo guardar archivo en NAS']);
  exit;
}

// 6) Insert DB (evidencia + evento) con transacción; si falla, borrar archivo
$mime = (string)($_FILES['file']['type'] ?? '');

try {
  $conn->begin_transaction();

  // Insert evidencia
  $stmt = $conn->prepare("
        INSERT INTO tarea_evidencias
          (tarea_id, carpeta_relativa, nombre_guardado, nombre_original, mime_type, tamano_bytes, creado_por)
        VALUES
          (?, ?, ?, ?, ?, ?, ?)
    ");
  if (!$stmt) {
    throw new Exception("Error prepare insert evidencia: " . $conn->error);
  }

  // tipos: i (tarea_id), s, s, s, s, i (tamano), i (creado_por)
  $stmt->bind_param(
    "issssii",
    $tareaId,
    $carpetaRelativa,
    $nombreGuardado,
    $nombreOriginal,
    $mime,
    $size,
    $usuarioId
  );

  if (!$stmt->execute()) {
    $stmt->close();
    throw new Exception("Error execute insert evidencia: " . $conn->error);
  }
  $evidenciaId = (int)$conn->insert_id;
  $stmt->close();

  // Insert evento
  $payload = json_encode([
    'evidencia_id'     => $evidenciaId,
    'nombre_original'  => $nombreOriginal,
    'nombre_guardado'  => $nombreGuardado,
    'carpeta_relativa' => $carpetaRelativa
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

  $tipo = 'evidence_added';

  $stmt = $conn->prepare("
        INSERT INTO tarea_eventos (tarea_id, tipo, actor_usuario_id, payload_json)
        VALUES (?, ?, ?, ?)
    ");
  if (!$stmt) {
    throw new Exception("Error prepare insert evento: " . $conn->error);
  }

  $stmt->bind_param("isis", $tareaId, $tipo, $usuarioId, $payload);

  if (!$stmt->execute()) {
    $stmt->close();
    throw new Exception("Error execute insert evento: " . $conn->error);
  }
  $stmt->close();

  // Si tienes auditoría central:
  // auditoria_log($usuarioId, 'tarea_evidencia', 'create', $evidenciaId, $payload);

  $conn->commit();

  echo json_encode([
    'success' => true,
    'message' => 'Evidencia subida correctamente',
    'data' => [
      'evidencia_id' => $evidenciaId,
      'tarea_id' => $tareaId,
      'carpeta_relativa' => $carpetaRelativa,
      'nombre_guardado' => $nombreGuardado,
      'nombre_original' => $nombreOriginal,
      'mime_type' => $mime ?: null,
      'tamano_bytes' => $size
    ]
  ]);
  exit;
} catch (Throwable $e) {
  $conn->rollback();

  // Limpieza: borrar archivo físico si falló BD
  if (is_file($rutaArchivo)) {
    @unlink($rutaArchivo);
  }

  echo json_encode([
    'success' => false,
    'message' => 'Error guardando evidencia',
    'error' => $e->getMessage()
  ]);
  exit;
}
