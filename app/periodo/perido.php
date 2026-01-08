<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');

auth_require();

$conn = db();

$sql = "
SELECT periodo_id, periodo
FROM periodo LIMIT 1";

$stmt = $conn->prepare($sql);
$stmt->execute();
$res = $stmt->get_result()->fetch_assoc();

