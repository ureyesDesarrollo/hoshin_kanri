<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$gerenteId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);

if ($empresaId <= 0 || $gerenteId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Sesión inválida'], JSON_UNESCAPED_UNICODE);
  exit;
}

/*
  Este endpoint lista a los RESPONSABLES de tareas que pertenecen a
  estrategias donde el gerente logueado es el responsable.

  Jerarquía:
    Estrategia (responsable_usuario_id = gerenteId)
      -> Milestone
         -> Tarea (responsable_usuario_id = usuarioResponsable)
*/

$sql = "
SELECT
  ru.usuario_id,
  ru.nombre_completo,
  rr.nombre AS rol,
  aa.area_id,
  aa.nombre AS area_nombre,

  /* =========================
     KPIs (EN VIVO)
  ========================= */

  COALESCE(COUNT(DISTINCT t.tarea_id), 0) AS total_tareas,

  COALESCE(COUNT(DISTINCT CASE
    WHEN (t.estatus = 4)
      OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
    THEN t.tarea_id
  END), 0) AS tareas_finalizadas,

  COALESCE(COUNT(DISTINCT CASE
    WHEN (t.estatus = 6)
      OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
    THEN t.tarea_id
  END), 0) AS tareas_completadas_tarde,

  COALESCE(COUNT(DISTINCT CASE
    WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
    THEN t.tarea_id
  END), 0) AS tareas_vencidas_abiertas,

  (
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
  ) AS tareas_vencidas_total,

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
  END AS porcentaje_general

FROM estrategias e
JOIN milestones m ON m.estrategia_id = e.estrategia_id
JOIN tareas t ON t.milestone_id = m.milestone_id

/* responsable de la tarea */
JOIN usuarios ru ON ru.usuario_id = t.responsable_usuario_id
JOIN usuarios_empresas ue ON ue.usuario_id = ru.usuario_id AND ue.empresa_id = e.empresa_id AND ue.activo = 1
JOIN roles rr ON rr.rol_id = ue.rol_id
LEFT JOIN areas aa ON aa.area_id = ue.area_id

WHERE e.empresa_id = ?
  AND e.responsable_usuario_id = ?  /* MIS ESTRATEGIAS como gerente */

GROUP BY
  ru.usuario_id,
  ru.nombre_completo,
  rr.nombre,
  aa.area_id,
  aa.nombre
ORDER BY porcentaje_general DESC
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt->bind_param('ii', $empresaId, $gerenteId);

if (!$stmt->execute()) {
  echo json_encode(['success' => false, 'message' => 'Error execute: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
  $stmt->close();
  exit;
}

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
exit;
