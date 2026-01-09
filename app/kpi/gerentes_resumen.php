<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);

if ($empresaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
    exit;
}

$desde = trim($_GET['desde'] ?? '');
$hasta = trim($_GET['hasta'] ?? '');

// default: últimas 8 semanas (domingo-sábado)
if ($desde === '' || $hasta === '') {
    $today = new DateTime('today');
    $dow = (int)$today->format('w');
    $end = (clone $today)->modify("-{$dow} days")->modify("+6 days");  // sábado de semana actual
    $start = (clone $end)->modify("-55 days"); // 8 semanas * 7 - 1 aprox
    // asegurar que start sea domingo
    $dowStart = (int)$start->format('w');
    $start = $start->modify("-{$dowStart} days");

    $desde = $start->format('Y-m-d');
    $hasta = $end->format('Y-m-d');
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $desde) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $hasta)) {
    echo json_encode(['success' => false, 'message' => 'Formato de fecha inválido (YYYY-MM-DD)']);
    exit;
}

/** Resumen general ponderado por usuario */
$sqlGeneral = "
SELECT
  u.usuario_id,
  u.nombre_completo,
  'GERENTE' AS rol,

  SUM(k.total_tareas) AS total_tareas,
  SUM(k.cumplidas_a_tiempo) AS cumplidas_a_tiempo,
  SUM(k.vencidas_no_cumplidas) AS vencidas_no_cumplidas,
  SUM(k.completadas_tarde) AS completadas_tarde,

  CASE
    WHEN SUM(k.total_tareas) = 0 THEN 0
    ELSE ROUND((SUM(k.cumplidas_a_tiempo) / SUM(k.total_tareas)) * 100, 0)
  END AS porcentaje_general

FROM kpi_responsable_semanal k
JOIN usuarios u ON u.usuario_id = k.usuario_id
WHERE k.empresa_id = ?
  AND k.semana_inicio BETWEEN ? AND ?
GROUP BY u.usuario_id
ORDER BY porcentaje_general ASC, total_tareas DESC
";

$stmt = $conn->prepare($sqlGeneral);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
    exit;
}
$stmt->bind_param('iss', $empresaId, $desde, $hasta);
$stmt->execute();
$general = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

/** Serie por semana (para gráfica) */
$sqlSerie = "
SELECT
  usuario_id,
  semana_inicio,
  semana_fin,
  total_tareas,
  cumplidas_a_tiempo,
  vencidas_no_cumplidas,
  completadas_tarde,
  porcentaje
FROM kpi_responsable_semanal
WHERE empresa_id = ?
  AND semana_inicio BETWEEN ? AND ?
ORDER BY usuario_id, semana_inicio
";

$stmt2 = $conn->prepare($sqlSerie);
if (!$stmt2) {
    echo json_encode(['success' => false, 'message' => 'Error prepare serie: ' . $conn->error]);
    exit;
}
$stmt2->bind_param('iss', $empresaId, $desde, $hasta);
$stmt2->execute();
$serie = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'range' => ['desde' => $desde, 'hasta' => $hasta],
    'general' => $general,
    'serie' => $serie
], JSON_UNESCAPED_UNICODE);
exit;
