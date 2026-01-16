<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);

if ($empresaId <= 0 || $usuarioId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Sesión inválida']);
  exit;
}

/*
  KPIs basados en tareas del colaborador.
  Cumplimiento = tareas completadas A TIEMPO / total de tareas
  (si se completa después de fecha_fin, NO cuenta para el porcentaje)
*/
$sql = "SELECT
    COUNT(x.tarea_id) AS total,

    /* Tareas completadas (todas, incluso tarde) */
    SUM(CASE WHEN x.completada = 1 THEN 1 ELSE 0 END) AS finalizadas,

    /* Pendientes (no completadas y no vencidas) */
    SUM(
        CASE
            WHEN x.completada = 0 AND x.fecha_fin >= CURDATE()
            THEN 1 ELSE 0
        END
    ) AS pendientes,

    /* Vencidas (no completadas y ya pasó la fecha) */
    SUM(
        CASE
            WHEN x.completada = 0 AND x.fecha_fin < CURDATE()
            THEN 1 ELSE 0
        END
    ) AS vencidas,

    /* Vencen hoy */
    SUM(
        CASE
            WHEN x.completada = 0 AND x.fecha_fin = CURDATE()
            THEN 1 ELSE 0
        END
    ) AS vence_hoy,

    /* Vencen en la próxima semana */
    SUM(
        CASE
            WHEN x.completada = 0
             AND x.fecha_fin BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            THEN 1 ELSE 0
        END
    ) AS semana,

    /* =====================================================
       PORCENTAJE DE CUMPLIMIENTO
       Completadas a tiempo / total
       ===================================================== */
    CASE
        WHEN COUNT(x.tarea_id) = 0 THEN 0
        ELSE ROUND(
            (
                SUM(
                    CASE
                        WHEN x.completada = 1
                         AND x.completada_en IS NOT NULL
                         AND DATE(x.completada_en) <= x.fecha_fin
                        THEN 1 ELSE 0
                    END
                ) / COUNT(x.tarea_id)
            ) * 100
        , 0)
    END AS porcentaje,

    /* =====================================================
       NIVEL DE COMPROMISO (Hoshin)
       ===================================================== */
    CASE
        WHEN (
            /* Completadas a tiempo */
            COALESCE(COUNT(DISTINCT CASE
                WHEN (x.estatus = 4)
                  OR (x.completada = 1 AND x.completada_en IS NOT NULL AND DATE(x.completada_en) <= x.fecha_fin)
                THEN x.tarea_id
            END), 0)

            +

            /* Vencidas sin completar */
            COALESCE(COUNT(DISTINCT CASE
                WHEN (x.estatus IN (1,2,3,5) AND x.completada = 0)
                  AND x.fecha_fin < CURDATE()
                THEN x.tarea_id
            END), 0)

            +

            /* Completadas tarde (estatus 6 o fecha > fin) */
            COALESCE(COUNT(DISTINCT CASE
                WHEN (x.estatus = 6)
                  OR (x.completada = 1 AND x.completada_en IS NOT NULL AND DATE(x.completada_en) > x.fecha_fin)
                THEN x.tarea_id
            END), 0)
        ) = 0 THEN 0

        ELSE ROUND(
            (
                COALESCE(COUNT(DISTINCT CASE
                    WHEN (x.estatus = 4)
                      OR (x.completada = 1 AND x.completada_en IS NOT NULL AND DATE(x.completada_en) <= x.fecha_fin)
                    THEN x.tarea_id
                END), 0)
                /
                (
                    COALESCE(COUNT(DISTINCT CASE
                        WHEN (x.estatus = 4)
                          OR (x.completada = 1 AND x.completada_en IS NOT NULL AND DATE(x.completada_en) <= x.fecha_fin)
                        THEN x.tarea_id
                    END), 0)

                    +
                    COALESCE(COUNT(DISTINCT CASE
                        WHEN (x.estatus IN (1,2,3,5) AND x.completada = 0)
                          AND x.fecha_fin < CURDATE()
                        THEN x.tarea_id
                    END), 0)

                    +
                    COALESCE(COUNT(DISTINCT CASE
                        WHEN (x.estatus = 6)
                          OR (x.completada = 1 AND x.completada_en IS NOT NULL AND DATE(x.completada_en) > x.fecha_fin)
                        THEN x.tarea_id
                    END), 0)
                )
            ) * 100
        , 0)
    END AS nivel_compromiso

FROM (
    SELECT DISTINCT
        t.tarea_id,
        t.completada,
        t.fecha_fin,
        t.completada_en,
        t.estatus
    FROM tareas t
    JOIN milestones m ON m.milestone_id = t.milestone_id
    JOIN estrategias e ON e.estrategia_id = m.estrategia_id

    /* Evita duplicados por objetivos */
    LEFT JOIN objetivo_estrategia oe ON oe.estrategia_id = e.estrategia_id
    LEFT JOIN objetivos o ON o.objetivo_id = oe.objetivo_id AND o.empresa_id = ?

    WHERE t.responsable_usuario_id = ?
      AND e.empresa_id = ?
) x;";

$stmt = $conn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error]);
  exit;
}

$stmt->bind_param('iii', $empresaId, $usuarioId, $empresaId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();

if (!$row) {
  $row = [
    'total' => 0,
    'finalizadas' => 0,
    'pendientes' => 0,
    'vencidas' => 0,
    'vence_hoy' => 0,
    'semana' => 0,
    'porcentaje' => 0
  ];
}

echo json_encode([
  'success' => true,
  'kpi' => [
    'total' => (int)($row['total'] ?? 0),
    'finalizadas' => (int)($row['finalizadas'] ?? 0),
    'pendientes' => (int)($row['pendientes'] ?? 0),
    'vencidas' => (int)($row['vencidas'] ?? 0),
    'vence_hoy' => (int)($row['vence_hoy'] ?? 0),
    'semana' => (int)($row['semana'] ?? 0),
    'porcentaje' => (int)($row['porcentaje'] ?? 0),
    'nivel_compromiso' => (int)($row['nivel_compromiso'] ?? 0)
  ]
], JSON_UNESCAPED_UNICODE);
exit;
