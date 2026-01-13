<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../config.php';

auth_require();
$conn = db();

$evidenciaId = (int)($_GET['evidencia_id'] ?? 0);
if ($evidenciaId <= 0) {
    http_response_code(400);
    exit("Evidencia inválida");
}

// 1) Buscar evidencia + tarea_id (para permisos si aplicas)
$stmt = $conn->prepare("
  SELECT tarea_id, carpeta_relativa, nombre_guardado, nombre_original, mime_type
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
    http_response_code(404);
    exit("No existe");
}

// 2) (Recomendado) Validar permisos de usuario sobre la tarea aquí
// ejemplo: tarea_permitida($_SESSION['usuario']['usuario_id'], $ev['tarea_id'])

$path = NAS_BASE . $ev['carpeta_relativa'] . $ev['nombre_guardado'];

if (!is_file($path)) {
    http_response_code(404);
    exit("Archivo no encontrado en NAS");
}

$mime = $ev['mime_type'] ?: 'application/octet-stream';
$original = basename($ev['nombre_original']);

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $original . '"');
header('Content-Length: ' . filesize($path));

$fp = fopen($path, 'rb');
if (!$fp) {
    http_response_code(500);
    exit("No se pudo leer archivo");
}
fpassthru($fp);
fclose($fp);
exit;
