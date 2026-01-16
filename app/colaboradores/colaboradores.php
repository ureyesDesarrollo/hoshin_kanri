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

/*
  KPIs "en vivo" por colaborador:
  - total tareas (distinct)
  - finalizadas
  - pendientes (no completada y fecha_fin >= hoy)
  - vencidas (no completada y fecha_fin < hoy)
  - porcentaje: 100 si no hay fallas; baja por vencidas abiertas + completadas tarde
*/
$sql = "SELECT
  u.usuario_id,
  u.nombre_completo,
  u.correo,
  r.nombre AS rol,
  a.area_id,
  a.nombre AS area_nombre,

  /* Total tareas asignadas al colaborador */
  COALESCE(COUNT(DISTINCT t.tarea_id), 0) AS total_tareas,

  /* Finalizadas */
  COALESCE(COUNT(DISTINCT CASE WHEN t.completada = 1 THEN t.tarea_id END), 0) AS finalizadas,

  /* Pendientes */
  COALESCE(COUNT(DISTINCT CASE
    WHEN t.completada = 0 AND t.fecha_fin >= CURDATE()
    THEN t.tarea_id END), 0) AS pendientes,

  /* Completadas tarde (cuenta como falla) */
  COALESCE(COUNT(DISTINCT CASE
    WHEN t.completada = 1
     AND t.completada_en IS NOT NULL
     AND DATE(t.completada_en) > t.fecha_fin
    THEN t.tarea_id END), 0) AS completadas_tarde,

  /* Vencidas = vencidas abiertas + completadas tarde */
  (
    COALESCE(COUNT(DISTINCT CASE
      WHEN t.completada = 0 AND t.fecha_fin < CURDATE()
      THEN t.tarea_id END), 0)
    +
    COALESCE(COUNT(DISTINCT CASE
      WHEN t.completada = 1
       AND t.completada_en IS NOT NULL
       AND DATE(t.completada_en) > t.fecha_fin
      THEN t.tarea_id END), 0)
  ) AS vencidas,

  CASE
    WHEN (
      COALESCE(COUNT(DISTINCT CASE
        WHEN (t.estatus = 4)
          OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
        THEN t.tarea_id
      END), 0)
      +
      COALESCE(COUNT(DISTINCT CASE
        WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
        THEN t.tarea_id
      END), 0)
      +
      COALESCE(COUNT(DISTINCT CASE
        WHEN (t.estatus = 6)
          OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
        THEN t.tarea_id
      END), 0)
    ) = 0 THEN 0
    ELSE ROUND(
      (
        COALESCE(COUNT(DISTINCT CASE
          WHEN (t.estatus = 4)
            OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
          THEN t.tarea_id
        END), 0)
        /
        (
          COALESCE(COUNT(DISTINCT CASE
            WHEN (t.estatus = 4)
              OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
            THEN t.tarea_id
          END), 0)
          +
          COALESCE(COUNT(DISTINCT CASE
            WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
            THEN t.tarea_id
          END), 0)
          +
          COALESCE(COUNT(DISTINCT CASE
            WHEN (t.estatus = 6)
              OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
            THEN t.tarea_id
          END), 0)
        )
      ) * 100
    , 0)
  END AS porcentaje_compromiso

FROM usuarios u
JOIN usuarios_empresas ue ON ue.usuario_id = u.usuario_id
JOIN roles r ON r.rol_id = ue.rol_id
LEFT JOIN areas a ON a.area_id = ue.area_id

/* tareas asignadas directamente al colaborador */
LEFT JOIN tareas t
  ON t.responsable_usuario_id = u.usuario_id

/* seguridad empresa por estrategia */
LEFT JOIN milestones m ON m.milestone_id = t.milestone_id
LEFT JOIN estrategias e ON e.estrategia_id = m.estrategia_id AND e.empresa_id = ue.empresa_id

WHERE ue.empresa_id = ?
  AND ue.activo = 1
  AND ue.rol_id NOT IN (1, 2)
  AND (t.tarea_id IS NULL OR e.empresa_id = ue.empresa_id)

GROUP BY u.usuario_id
ORDER BY u.nombre_completo ASC
";


$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
  exit;
}

$stmt->bind_param('i', $empresaId);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
