<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$q = trim((string)($_GET['q'] ?? ''));
$prioridad = trim((string)($_GET['prioridad'] ?? ''));
$estado = trim((string)($_GET['estado'] ?? ''));
$zona = trim((string)($_GET['zona'] ?? ''));
$areaResponsableId = (int)($_GET['area_responsable_id'] ?? 0);
$tipoProyectoId = (int)($_GET['tipo_proyecto_id'] ?? 0);

if ($empresaId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Empresa inválida'], JSON_UNESCAPED_UNICODE);
  exit;
}

$where = ['empresa_id = ?', 'visible_en_direccion = 1'];
$params = [$empresaId];
$types = 'i';

if ($q !== '') {
  $where[] = '(nombre_directivo LIKE ? OR milestone LIKE ? OR estrategia LIKE ? OR responsable LIKE ?)';
  $like = "%{$q}%";
  array_push($params, $like, $like, $like, $like);
  $types .= 'ssss';
}

if ($prioridad !== '') {
  $where[] = 'prioridad_directiva = ?';
  $params[] = $prioridad;
  $types .= 's';
}

if ($estado !== '') {
  $where[] = 'estado_directivo = ?';
  $params[] = $estado;
  $types .= 's';
}

if ($zona !== '') {
  $where[] = 'zona LIKE ?';
  $params[] = "%{$zona}%";
  $types .= 's';
}

if ($areaResponsableId > 0) {
  $where[] = 'responsable_area_id = ?';
  $params[] = $areaResponsableId;
  $types .= 'i';
}

if ($tipoProyectoId > 0) {
  $where[] = 'tipo_proyecto_directivo_id = ?';
  $params[] = $tipoProyectoId;
  $types .= 'i';
}

$sql = "
SELECT *
FROM vista_proyectos_directivos
WHERE " . implode(' AND ', $where) . "
ORDER BY
  FIELD(prioridad_directiva, 'Critica','Alta','Media','Baja'),
  COALESCE(fecha_fin_objetivo, fecha_fin_operativa, '9999-12-31') ASC,
  proyecto_directivo_id DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
