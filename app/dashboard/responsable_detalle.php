<?php
// /hoshin_kanri/app/dashboard/responsable_detalle.php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_GET['usuario_id'] ?? 0);

if ($empresaId <= 0 || $usuarioId <= 0) {
  echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
  exit;
}

/* ============================================================
   1) RESUMEN POR RESPONSABLE (GERENTE)
   - total / finalizadas / pendientes / vencidas
   - % cumplimiento
   - semáforo general: ROJO si vencidas > 0, WARNING si total=0, VERDE si no
============================================================ */
$sqlResumen = "
SELECT
  u.usuario_id,
  u.nombre_completo,
  r.nombre AS rol,
  a.area_id,
  a.nombre AS area_nombre,

  COALESCE(COUNT(t.tarea_id), 0) AS total_tareas,
  COALESCE(SUM(CASE WHEN t.completada = 1 THEN 1 ELSE 0 END), 0) AS finalizadas,
  COALESCE(SUM(CASE WHEN t.completada = 0 AND t.fecha_fin >= CURDATE() THEN 1 ELSE 0 END), 0) AS pendientes,
  COALESCE(SUM(CASE WHEN t.completada = 0 AND t.fecha_fin < CURDATE() THEN 1 ELSE 0 END), 0) AS vencidas,

  CASE
    WHEN COUNT(t.tarea_id) = 0 THEN 0
    ELSE ROUND((SUM(CASE WHEN t.completada = 1 THEN 1 ELSE 0 END) / COUNT(t.tarea_id)) * 100, 0)
  END AS porcentaje
FROM usuarios u
JOIN usuarios_empresas ue
  ON ue.usuario_id = u.usuario_id
 AND ue.empresa_id = ?
 AND ue.activo = 1
JOIN roles r ON r.rol_id = ue.rol_id
LEFT JOIN estrategias e
  ON e.empresa_id = ue.empresa_id
 AND e.responsable_usuario_id = u.usuario_id
LEFT JOIN milestones m ON m.estrategia_id = e.estrategia_id
LEFT JOIN tareas t ON t.milestone_id = m.milestone_id
LEFT JOIN areas a ON a.area_id = ue.area_id
WHERE u.usuario_id = ?
  AND r.nombre = 'GERENTE'
GROUP BY u.usuario_id, u.nombre_completo, r.nombre
";

$stmt = $conn->prepare($sqlResumen);
$stmt->bind_param('ii', $empresaId, $usuarioId);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_assoc();

if (!$resumen) {
  echo json_encode(['success' => false, 'message' => 'Responsable no encontrado o no es GERENTE']);
  exit;
}

$semaforoGeneral = 'VERDE';
if ((int)$resumen['total_tareas'] === 0) $semaforoGeneral = 'WARNING';
if ((int)$resumen['vencidas'] > 0) $semaforoGeneral = 'ROJO';

/* ============================================================
   2) DETALLE JERÁRQUICO POR RESPONSABLE (GERENTE)
   Regla semáforo:
   - Tarea ROJA si: completada=0 y fecha_fin < CURDATE()
   - Milestone ROJO si tiene ≥1 tarea roja
   - Estrategia ROJO si tiene ≥1 milestone rojo
   - Objetivo ROJO si tiene ≥1 estrategia roja
============================================================ */
$sqlDetalle = "
SELECT
  COALESCE(o.objetivo_id, 0) AS objetivo_id,
  COALESCE(o.titulo, 'Sin objetivo') AS objetivo_titulo,

  e.estrategia_id,
  e.titulo AS estrategia_titulo,

  COALESCE(m.milestone_id, 0) AS milestone_id,
  COALESCE(m.titulo, 'Sin milestone') AS milestone_titulo,

  t.tarea_id,
  t.titulo AS tarea_titulo,
  t.fecha_inicio,
  t.fecha_fin,
  t.completada,

  CASE
    WHEN t.tarea_id IS NULL THEN 'SIN_TAREA'
    WHEN t.completada = 1 THEN 'VERDE'
    WHEN t.fecha_fin < CURDATE() THEN 'ROJO'
    ELSE 'VERDE'
  END AS semaforo_tarea,

  CASE
    WHEN t.tarea_id IS NOT NULL AND t.completada = 0 AND t.fecha_fin < CURDATE() THEN 1 ELSE 0
  END AS tarea_roja

FROM estrategias e
LEFT JOIN objetivo_estrategia oe ON oe.estrategia_id = e.estrategia_id
LEFT JOIN objetivos o ON o.objetivo_id = oe.objetivo_id
LEFT JOIN milestones m ON m.estrategia_id = e.estrategia_id
LEFT JOIN tareas t ON t.milestone_id = m.milestone_id

WHERE e.empresa_id = ?
  AND e.responsable_usuario_id = ?

ORDER BY o.objetivo_id, e.estrategia_id, m.milestone_id, t.fecha_fin
";

$stmt2 = $conn->prepare($sqlDetalle);
$stmt2->bind_param('ii', $empresaId, $usuarioId);
$stmt2->execute();
$rows = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);

/* ============================================================
   3) ARMAR ESTRUCTURA OBJETIVO->ESTRATEGIA->MILESTONE->TAREAS
============================================================ */
$map = []; // objetivo_id => ...

foreach ($rows as $r) {
  $objId = (int)$r['objetivo_id'];
  $estId = (int)$r['estrategia_id'];
  $milId = (int)$r['milestone_id'];
  $tarId = isset($r['tarea_id']) ? (int)$r['tarea_id'] : 0;

  if (!isset($map[$objId])) {
    $map[$objId] = [
      'objetivo_id' => $objId,
      'objetivo' => $r['objetivo_titulo'],
      'semaforo' => 'VERDE',
      'rojas' => 0,
      'estrategias' => []
    ];
  }

  if (!isset($map[$objId]['estrategias'][$estId])) {
    $map[$objId]['estrategias'][$estId] = [
      'estrategia_id' => $estId,
      'estrategia' => $r['estrategia_titulo'],
      'semaforo' => 'VERDE',
      'rojas' => 0,
      'milestones' => []
    ];
  }

  if (!isset($map[$objId]['estrategias'][$estId]['milestones'][$milId])) {
    $map[$objId]['estrategias'][$estId]['milestones'][$milId] = [
      'milestone_id' => $milId,
      'milestone' => $r['milestone_titulo'],
      'semaforo' => 'VERDE',
      'rojas' => 0,
      'tareas' => []
    ];
  }

  if ($tarId > 0) {
    $map[$objId]['estrategias'][$estId]['milestones'][$milId]['tareas'][] = [
      'tarea_id' => $tarId,
      'tarea' => $r['tarea_titulo'],
      'fecha_inicio' => $r['fecha_inicio'],
      'fecha_fin' => $r['fecha_fin'],
      'completada' => (int)$r['completada'],
      'semaforo' => $r['semaforo_tarea'],
      'tarea_roja' => (int)$r['tarea_roja']
    ];
  }
}

/* ============================================================
   4) CALCULAR SEMÁFORO HEREDADO (ROJO si hay vencidas)
============================================================ */
$out = [];
foreach ($map as $obj) {
  // convertir estrategias y milestones a arrays y calcular
  $objRojas = 0;
  $estrategias = [];

  foreach ($obj['estrategias'] as $est) {
    $estRojas = 0;
    $milestones = [];

    foreach ($est['milestones'] as $mil) {
      $milRojas = 0;
      foreach ($mil['tareas'] as $t) {
        $milRojas += (int)($t['tarea_roja'] ?? 0);
      }
      $mil['rojas'] = $milRojas;
      $mil['semaforo'] = ($milRojas > 0) ? 'ROJO' : 'VERDE';

      $estRojas += $milRojas;
      $milestones[] = $mil;
    }

    $est['milestones'] = $milestones;
    $est['rojas'] = $estRojas;
    $est['semaforo'] = ($estRojas > 0) ? 'ROJO' : 'VERDE';

    $objRojas += $estRojas;
    $estrategias[] = $est;
  }

  $obj['estrategias'] = $estrategias;
  $obj['rojas'] = $objRojas;
  $obj['semaforo'] = ($objRojas > 0) ? 'ROJO' : 'VERDE';

  $out[] = $obj;
}

echo json_encode([
  'success' => true,
  'resumen' => [
    'usuario_id' => (int)$resumen['usuario_id'],
    'nombre' => $resumen['nombre_completo'],
    'rol' => $resumen['rol'],
    'area_nombre' => $resumen['area_nombre'],
    'total' => (int)$resumen['total_tareas'],
    'finalizadas' => (int)$resumen['finalizadas'],
    'pendientes' => (int)$resumen['pendientes'],
    'vencidas' => (int)$resumen['vencidas'],
    'porcentaje' => (int)$resumen['porcentaje'],
    'semaforo' => $semaforoGeneral
  ],
  'data' => $out
], JSON_UNESCAPED_UNICODE);

exit;
