<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

auth_require();
$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);

if ($empresaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Empresa inválida'], JSON_UNESCAPED_UNICODE);
    exit;
}

$sql = "SELECT
    o.objetivo_id,
    o.titulo AS objetivo,

    m.milestone_id,
    m.estrategia_id,
    m.titulo AS milestone,
    m.prioridad,
    u.nombre_completo AS responsable,

    MIN(COALESCE(t.fecha_inicio, DATE(t.creado_en), t.fecha_fin)) AS fecha_inicio,
    MAX(t.fecha_fin) AS fecha_fin,

    MAX(MAX(t.fecha_fin)) OVER (PARTITION BY o.objetivo_id) AS objetivo_fecha_fin,

    COUNT(DISTINCT t.tarea_id) AS total_tareas,
    COUNT(DISTINCT CASE WHEN t.completada = 1 THEN t.tarea_id END) AS finalizadas,

    COUNT(DISTINCT CASE
        WHEN t.completada = 0 AND t.fecha_fin < CURDATE()
        THEN t.tarea_id
    END) AS vencidas,

    COUNT(DISTINCT CASE
        WHEN t.completada = 1
         AND t.completada_en IS NOT NULL
         AND DATE(t.completada_en) > t.fecha_fin
        THEN t.tarea_id
    END) AS completadas_tarde

FROM estrategias e
JOIN milestones m ON m.estrategia_id = e.estrategia_id
JOIN tareas t ON t.milestone_id = m.milestone_id
JOIN usuarios u ON m.responsable_usuario_id = u.usuario_id

/* objetivos por estrategia: usar JOIN (no LEFT) para que solo salgan esos objetivos */
JOIN objetivo_estrategia oe
  ON oe.estrategia_id = e.estrategia_id
 AND oe.objetivo_id IN (4,3,5,10,12)

JOIN objetivos o
  ON o.objetivo_id = oe.objetivo_id
 AND o.empresa_id = e.empresa_id

WHERE e.empresa_id = ?
  AND (
        o.objetivo_id IN (4,10,12)
        OR (o.objetivo_id IN (5,3) AND e.responsable_usuario_id IN (3,9,54) AND m.responsable_usuario_id NOT IN (17,19))
      )

GROUP BY
    o.objetivo_id, o.titulo,
    m.milestone_id, m.estrategia_id, m.titulo,
    u.nombre_completo

ORDER BY
    objetivo_fecha_fin ASC,
    fecha_fin ASC;
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('i', $empresaId);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error execute: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
    $stmt->close();
    exit;
}

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
