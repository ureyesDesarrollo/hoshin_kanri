<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$zonaId = (int)($_GET['zona_directiva_id'] ?? 0);

$where = 'ad.empresa_id = ?';
$types = 'i';
$params = [$empresaId];

if ($zonaId > 0) {
  $where .= ' AND ad.zona_directiva_id = ?';
  $types .= 'i';
  $params[] = $zonaId;
}

$stmt = $conn->prepare("
SELECT
  ad.area_directiva_id,
  ad.zona_directiva_id,
  ad.nombre,
  ad.activo,
  zd.nombre AS zona
FROM areas_directivas ad
JOIN zonas_directivas zd ON zd.zona_directiva_id = ad.zona_directiva_id
WHERE {$where}
ORDER BY zd.nombre ASC, ad.nombre ASC
");
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
