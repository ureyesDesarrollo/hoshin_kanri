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

/* =========================
   1) DETALLE POR RESPONSABLE
========================= */
$sqlDetalle = "
SELECT
  ru.usuario_id,
  ru.nombre_completo,
  rr.nombre AS rol,
  aa.area_id,
  aa.nombre AS area_nombre,

  COUNT(DISTINCT t.tarea_id) AS total_tareas,

  COUNT(DISTINCT CASE
    WHEN (t.estatus = 4)
      OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
    THEN t.tarea_id
  END) AS tareas_finalizadas,

  COUNT(DISTINCT CASE
    WHEN (t.estatus = 6)
      OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
    THEN t.tarea_id
  END) AS tareas_completadas_tarde,

  COUNT(DISTINCT CASE
    WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
    THEN t.tarea_id
  END) AS tareas_vencidas_abiertas,

  (
    COUNT(DISTINCT CASE
      WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
      THEN t.tarea_id
    END)
    +
    COUNT(DISTINCT CASE
      WHEN (t.estatus = 6)
        OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
      THEN t.tarea_id
    END)
  ) AS tareas_vencidas_total,

  /* % individual = a_tiempo / (a_tiempo + vencidas + tarde) */
  CASE
    WHEN (
      COUNT(DISTINCT CASE
        WHEN (t.estatus = 4)
          OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
        THEN t.tarea_id
      END)
      +
      COUNT(DISTINCT CASE
        WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
        THEN t.tarea_id
      END)
      +
      COUNT(DISTINCT CASE
        WHEN (t.estatus = 6)
          OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
        THEN t.tarea_id
      END)
    ) = 0 THEN 0
    ELSE ROUND(
      (
        COUNT(DISTINCT CASE
          WHEN (t.estatus = 4)
            OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
          THEN t.tarea_id
        END)
        /
        (
          COUNT(DISTINCT CASE
            WHEN (t.estatus = 4)
              OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
            THEN t.tarea_id
          END)
          +
          COUNT(DISTINCT CASE
            WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
            THEN t.tarea_id
          END)
          +
          COUNT(DISTINCT CASE
            WHEN (t.estatus = 6)
              OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
            THEN t.tarea_id
          END)
        )
      ) * 100
    , 0)
  END AS porcentaje_general

FROM estrategias e
JOIN milestones m ON m.estrategia_id = e.estrategia_id
JOIN tareas t ON t.milestone_id = m.milestone_id

JOIN usuarios ru ON ru.usuario_id = t.responsable_usuario_id
JOIN usuarios_empresas ue ON ue.usuario_id = ru.usuario_id AND ue.empresa_id = e.empresa_id AND ue.activo = 1
JOIN roles rr ON rr.rol_id = ue.rol_id
LEFT JOIN areas aa ON aa.area_id = ue.area_id

WHERE e.empresa_id = ?
  AND e.responsable_usuario_id = ?

GROUP BY ru.usuario_id, ru.nombre_completo, rr.nombre, aa.area_id, aa.nombre
ORDER BY porcentaje_general DESC
";

/* =========================
   2) TOTAL EQUIPO (1 FILA)
========================= */
$sqlEquipo = "
SELECT
  COUNT(DISTINCT t.tarea_id) AS total_tareas,

  COUNT(DISTINCT CASE
    WHEN (t.estatus = 4)
      OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
    THEN t.tarea_id
  END) AS tareas_finalizadas,

  COUNT(DISTINCT CASE
    WHEN (t.estatus = 6)
      OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
    THEN t.tarea_id
  END) AS tareas_completadas_tarde,

  COUNT(DISTINCT CASE
    WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
    THEN t.tarea_id
  END) AS tareas_vencidas_abiertas,

  (
    COUNT(DISTINCT CASE
      WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
      THEN t.tarea_id
    END)
    +
    COUNT(DISTINCT CASE
      WHEN (t.estatus = 6)
        OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
      THEN t.tarea_id
    END)
  ) AS tareas_vencidas_total,

  CASE
    WHEN (
      COUNT(DISTINCT CASE
        WHEN (t.estatus = 4)
          OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
        THEN t.tarea_id
      END)
      +
      COUNT(DISTINCT CASE
        WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
        THEN t.tarea_id
      END)
      +
      COUNT(DISTINCT CASE
        WHEN (t.estatus = 6)
          OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
        THEN t.tarea_id
      END)
    ) = 0 THEN 0
    ELSE ROUND(
      (
        COUNT(DISTINCT CASE
          WHEN (t.estatus = 4)
            OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
          THEN t.tarea_id
        END)
        /
        (
          COUNT(DISTINCT CASE
            WHEN (t.estatus = 4)
              OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
            THEN t.tarea_id
          END)
          +
          COUNT(DISTINCT CASE
            WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
            THEN t.tarea_id
          END)
          +
          COUNT(DISTINCT CASE
            WHEN (t.estatus = 6)
              OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
            THEN t.tarea_id
          END)
        )
      ) * 100
    , 0)
  END AS porcentaje_equipo

FROM estrategias e
JOIN milestones m ON m.estrategia_id = e.estrategia_id
JOIN tareas t ON t.milestone_id = m.milestone_id
JOIN usuarios ru ON ru.usuario_id = t.responsable_usuario_id
JOIN usuarios_empresas ue ON ue.usuario_id = ru.usuario_id AND ue.empresa_id = e.empresa_id AND ue.activo = 1

WHERE e.empresa_id = ?
  AND e.responsable_usuario_id = ?
";

/* =========================
   Ejecutar queries
========================= */
$stmt = $conn->prepare($sqlDetalle);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare detalle: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt->bind_param('ii', $empresaId, $gerenteId);
$stmt->execute();
$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$stmt2 = $conn->prepare($sqlEquipo);
if (!$stmt2) {
  echo json_encode(['success' => false, 'message' => 'Error prepare equipo: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt2->bind_param('ii', $empresaId, $gerenteId);
$stmt2->execute();
$equipo = $stmt2->get_result()->fetch_assoc();
$stmt2->close();

$porcentajeEquipo = (int)($equipo['porcentaje_equipo'] ?? 0);

echo json_encode([
  'success' => true,
  'porcentaje_equipo' => $porcentajeEquipo,
  'equipo' => [
    'total_tareas' => (int)($equipo['total_tareas'] ?? 0),
    'tareas_finalizadas' => (int)($equipo['tareas_finalizadas'] ?? 0),
    'tareas_completadas_tarde' => (int)($equipo['tareas_completadas_tarde'] ?? 0),
    'tareas_vencidas_abiertas' => (int)($equipo['tareas_vencidas_abiertas'] ?? 0),
    'tareas_vencidas_total' => (int)($equipo['tareas_vencidas_total'] ?? 0),
  ],
  'data' => $data
], JSON_UNESCAPED_UNICODE);
exit;
