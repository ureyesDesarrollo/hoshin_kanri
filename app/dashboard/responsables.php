<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)$_SESSION['usuario']['empresa_id'];

$sql = "SELECT
    u.usuario_id,
    u.nombre_completo,
    r.nombre AS rol,
    a.area_id,
    a.nombre AS area_nombre,

    /* =========================
       TAREAS
    ========================= */
    COALESCE(COUNT(t.tarea_id), 0) AS total_tareas,

    COALESCE(SUM(
        CASE WHEN t.completada = 1 THEN 1 ELSE 0 END
    ), 0) AS tareas_finalizadas,

    COALESCE(SUM(
        CASE 
            WHEN t.completada = 0 
             AND t.fecha_fin >= CURDATE()
            THEN 1 ELSE 0 
        END
    ), 0) AS tareas_pendientes,

    COALESCE(SUM(
        CASE 
            WHEN t.completada = 0 
             AND t.fecha_fin < CURDATE()
            THEN 1 ELSE 0 
        END
    ), 0) AS tareas_vencidas,

    /* =========================
       PORCENTAJE
    ========================= */
    CASE 
        WHEN COUNT(t.tarea_id) = 0 THEN 0
        ELSE ROUND(
            (SUM(CASE WHEN t.completada = 1 THEN 1 ELSE 0 END) 
            / COUNT(t.tarea_id)) * 100
        , 0)
    END AS porcentaje_cumplimiento

FROM usuarios u
JOIN usuarios_empresas ue 
    ON ue.usuario_id = u.usuario_id
JOIN roles r 
    ON r.rol_id = ue.rol_id

LEFT JOIN estrategias e 
    ON e.responsable_usuario_id = u.usuario_id

LEFT JOIN milestones m 
    ON m.estrategia_id = e.estrategia_id

LEFT JOIN tareas t 
    ON t.milestone_id = m.milestone_id

LEFT JOIN areas a 
    ON a.area_id = ue.area_id

WHERE ue.empresa_id = ?
  AND ue.activo = 1
  AND r.nombre = 'GERENTE'

GROUP BY u.usuario_id
ORDER BY porcentaje_cumplimiento ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $empresaId);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $data
]);
exit;
