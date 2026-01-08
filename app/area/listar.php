<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$sql = "
SELECT
  a.area_id,
  a.nombre
FROM areas a ORDER BY a.nombre ASC
";

$stmt = $conn->prepare($sql);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $data]);
exit;
