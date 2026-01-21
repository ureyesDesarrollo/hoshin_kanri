<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

auth_require();
$conn = db();

$usuarioId = (int)($_GET['usuario_id'] ?? 0);
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
if ($empresaId <= 0) {
  $empresaId = 1;
}

if ($usuarioId <= 0) {
  echo json_encode(['success' => false, 'msg' => 'Usuario inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}
if ($empresaId <= 0) {
  echo json_encode(['success' => false, 'msg' => 'Empresa inválida (empresaId no disponible)'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ============================================================
   1) RESUMEN GLOBAL (POR RESPONSABLE DE TAREA)
   - aprobadas: estatus=4 OR completada_en <= fecha_fin
   - completadas_tarde: estatus=6 OR completada_en > fecha_fin
   - vencidas_abiertas: completada=0 AND fecha_fin < hoy
   - vencidas = vencidas_abiertas + completadas_tarde
   - porcentaje_compromiso = aprobadas / (aprobadas + vencidas_abiertas + completadas_tarde) * 100
============================================================ */
$sqlResumen = "
SELECT
  u.nombre_completo AS nombre,
  r.nombre AS rol,
  a.nombre AS area_nombre,

  /* Total de tareas del responsable */
  COALESCE(COUNT(DISTINCT t.tarea_id), 0) AS total,

  /* Aprobadas / a tiempo */
  COALESCE(COUNT(DISTINCT CASE
    WHEN (t.estatus = 4)
      OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
    THEN t.tarea_id END), 0) AS completadas_a_tiempo,

  /* Completadas tarde (cuenta como falla) */
  COALESCE(COUNT(DISTINCT CASE
    WHEN (t.estatus = 6)
      OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
    THEN t.tarea_id END), 0) AS completadas_tarde,

  /* Vencidas abiertas (no completadas y ya vencieron) */
  COALESCE(COUNT(DISTINCT CASE
    WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
    THEN t.tarea_id END), 0) AS vencidas_abiertas,

  /* Vencidas totales = abiertas + tarde */
  (
    COALESCE(COUNT(DISTINCT CASE
      WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
      THEN t.tarea_id END), 0)
    +
    COALESCE(COUNT(DISTINCT CASE
      WHEN (t.estatus = 6)
        OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
      THEN t.tarea_id END), 0)
  ) AS vencidas_total,

  /* Porcentaje compromiso */
  CASE
    WHEN (
      COALESCE(COUNT(DISTINCT CASE
        WHEN (t.estatus = 4)
          OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
        THEN t.tarea_id END), 0)
      +
      COALESCE(COUNT(DISTINCT CASE
        WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
        THEN t.tarea_id END), 0)
      +
      COALESCE(COUNT(DISTINCT CASE
        WHEN (t.estatus = 6)
          OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
        THEN t.tarea_id END), 0)
    ) = 0 THEN 0
    ELSE ROUND(
      (
        COALESCE(COUNT(DISTINCT CASE
          WHEN (t.estatus = 4)
            OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
          THEN t.tarea_id END), 0)
        /
        (
          COALESCE(COUNT(DISTINCT CASE
            WHEN (t.estatus = 4)
              OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) <= t.fecha_fin)
            THEN t.tarea_id END), 0)
          +
          COALESCE(COUNT(DISTINCT CASE
            WHEN (t.estatus IN (1,2,3,5) AND t.completada = 0) AND t.fecha_fin < CURDATE()
            THEN t.tarea_id END), 0)
          +
          COALESCE(COUNT(DISTINCT CASE
            WHEN (t.estatus = 6)
              OR (t.completada = 1 AND t.completada_en IS NOT NULL AND DATE(t.completada_en) > t.fecha_fin)
            THEN t.tarea_id END), 0)
        )
      ) * 100
    , 0)
  END AS porcentaje_compromiso

FROM usuarios u
JOIN usuarios_empresas ue
  ON ue.usuario_id = u.usuario_id
 AND ue.activo = 1
 AND ue.empresa_id = ?

JOIN roles r ON r.rol_id = ue.rol_id
LEFT JOIN areas a ON a.area_id = ue.area_id

JOIN tareas t
  ON t.responsable_usuario_id = u.usuario_id

JOIN milestones m
  ON m.milestone_id = t.milestone_id

JOIN estrategias e
  ON e.estrategia_id = m.estrategia_id
 AND e.empresa_id = ?

WHERE u.usuario_id = ?
GROUP BY u.usuario_id, u.nombre_completo, r.nombre, a.nombre
";

$stmt = $conn->prepare($sqlResumen);
if (!$stmt) {
  echo json_encode(['success' => false, 'msg' => 'Error prepare resumen: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt->bind_param('iii', $empresaId, $empresaId, $usuarioId);

if (!$stmt->execute()) {
  echo json_encode(['success' => false, 'msg' => 'Error execute resumen: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
  $stmt->close();
  exit;
}

$resumen = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

/* Valores desde SQL */
$total            = (int)($resumen['total'] ?? 0);
$aprobadas        = (int)($resumen['completadas_a_tiempo'] ?? 0);
$completadasTarde = (int)($resumen['completadas_tarde'] ?? 0);
$vencidasAbiertas = (int)($resumen['vencidas_abiertas'] ?? 0);
$vencidasTotal    = (int)($resumen['vencidas_total'] ?? 0);
$porcentaje       = (int)($resumen['porcentaje_compromiso'] ?? 0);

/* Pendientes: total - (completadas a tiempo + completadas tarde) */
$pendientes = max(0, $total - $aprobadas - $completadasTarde);

/* Semáforo (si hay fallas -> ROJO) */
$semaforo = 'VERDE';
if ($vencidasTotal > 0) $semaforo = 'ROJO';
elseif ($total === 0) $semaforo = 'WARNING';

/* ============================================================
   2) DETALLE JERÁRQUICO (POR RESPONSABLE DE TAREA)
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
  t.estatus,
  t.completada,
  t.completada_en

FROM tareas t
JOIN milestones m
  ON m.milestone_id = t.milestone_id

JOIN estrategias e
  ON e.estrategia_id = m.estrategia_id
 AND e.empresa_id = ?

LEFT JOIN objetivo_estrategia oe
  ON oe.estrategia_id = e.estrategia_id
LEFT JOIN objetivos o
  ON o.objetivo_id = oe.objetivo_id

WHERE t.responsable_usuario_id = ?
ORDER BY o.objetivo_id, e.estrategia_id, m.milestone_id, t.fecha_fin
";

$stmt2 = $conn->prepare($sqlDetalle);
if (!$stmt2) {
  echo json_encode(['success' => false, 'msg' => 'Error prepare detalle: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}
$stmt2->bind_param('ii', $empresaId, $usuarioId);

if (!$stmt2->execute()) {
  echo json_encode(['success' => false, 'msg' => 'Error execute detalle: ' . $stmt2->error], JSON_UNESCAPED_UNICODE);
  $stmt2->close();
  exit;
}

$rs = $stmt2->get_result();
if (!$rs) {
  echo json_encode(['success' => false, 'msg' => 'Error get_result detalle: ' . $stmt2->error], JSON_UNESCAPED_UNICODE);
  $stmt2->close();
  exit;
}

/* ============================================================
   3) ARMAR JERARQUÍA (estatus_txt + tipo_vencida) (misma lógica)
============================================================ */
$data = [];
$hoy = date('Y-m-d');
$seen = [];

while ($r = $rs->fetch_assoc()) {
  $estatus = (int)($r['estatus'] ?? 0);

  switch ($estatus) {
    case 1:
      $estatusTxt = 'Abierta';
      break;
    case 2:
      $estatusTxt = 'En progreso';
      break;
    case 3:
      $estatusTxt = 'En revisión';
      break;
    case 4:
      $estatusTxt = 'Aprobada';
      break;
    case 5:
      $estatusTxt = 'Rechazada';
      break;
    case 6:
      $estatusTxt = 'Completada fuera de tiempo';
      break;
    default:
      $estatusTxt = 'Sin estatus';
  }

  $fechaFin = $r['fecha_fin'] ?? null;
  $completada = (int)($r['completada'] ?? 0);
  $completadaEn = $r['completada_en'] ?? null;

  $tSem = 'VERDE';
  if ($estatus === 6) {
    $tSem = 'ROJO';
  } elseif ($completada === 1 && $completadaEn && $fechaFin && substr($completadaEn, 0, 10) > $fechaFin) {
    $tSem = 'ROJO';
  } elseif ($estatus !== 4 && $completada === 0 && $fechaFin && $fechaFin < $hoy) {
    $tSem = 'ROJO';
  }

  $tipoVencida = 'NINGUNA';
  if ($estatus === 6 || ($completada === 1 && $completadaEn && $fechaFin && substr($completadaEn, 0, 10) > $fechaFin)) {
    $tipoVencida = 'COMPLETADA_TARDE';
  } elseif ($estatus !== 4 && $completada === 0 && $fechaFin && $fechaFin < $hoy) {
    $tipoVencida = 'ABIERTA_VENCIDA';
  }

  $objId = (int)($r['objetivo_id'] ?? 0);
  $estId = (int)($r['estrategia_id'] ?? 0);
  $milId = (int)($r['milestone_id'] ?? 0);
  $tarId = (int)($r['tarea_id'] ?? 0);

  if (!isset($data[$objId])) {
    $data[$objId] = [
      'objetivo_id' => $objId,
      'objetivo' => $r['objetivo_titulo'] ?? 'Sin objetivo',
      'estrategias' => [],
      'rojas' => 0
    ];
  }

  if (!isset($data[$objId]['estrategias'][$estId])) {
    $data[$objId]['estrategias'][$estId] = [
      'estrategia_id' => $estId,
      'estrategia' => $r['estrategia_titulo'] ?? 'Sin estrategia',
      'milestones' => [],
      'rojas' => 0
    ];
  }

  if (!isset($data[$objId]['estrategias'][$estId]['milestones'][$milId])) {
    $data[$objId]['estrategias'][$estId]['milestones'][$milId] = [
      'milestone_id' => $milId,
      'milestone' => $r['milestone_titulo'] ?? 'Sin milestone',
      'tareas' => [],
      'rojas' => 0
    ];
  }

  if ($tarId > 0) {
    $key = $objId . '|' . $estId . '|' . $milId . '|' . $tarId;
    if (!isset($seen[$key])) {
      $seen[$key] = true;

      $data[$objId]['estrategias'][$estId]['milestones'][$milId]['tareas'][] = [
        'tarea_id' => $tarId,
        'tarea' => $r['tarea_titulo'] ?? '',
        'fecha_inicio' => $r['fecha_inicio'] ?? null,
        'fecha_fin' => $fechaFin,
        'estatus' => $estatus,
        'estatus_txt' => $estatusTxt,
        'completada' => $completada,
        'semaforo' => $tSem,
        'tipo_vencida' => $tipoVencida
      ];

      if ($tSem === 'ROJO') {
        $data[$objId]['rojas']++;
        $data[$objId]['estrategias'][$estId]['rojas']++;
        $data[$objId]['estrategias'][$estId]['milestones'][$milId]['rojas']++;
      }
    }
  }
}

$stmt2->close();

/* ============================================================
   4) NORMALIZAR
============================================================ */
$out = [];
foreach ($data as $o) {
  $o['semaforo'] = ($o['rojas'] ?? 0) > 0 ? 'ROJO' : 'VERDE';

  foreach ($o['estrategias'] as &$e) {
    $e['semaforo'] = ($e['rojas'] ?? 0) > 0 ? 'ROJO' : 'VERDE';
    foreach ($e['milestones'] as &$m) {
      $m['semaforo'] = ($m['rojas'] ?? 0) > 0 ? 'ROJO' : 'VERDE';
    }
    $e['milestones'] = array_values($e['milestones']);
  }

  $o['estrategias'] = array_values($o['estrategias']);
  $out[] = $o;
}

/* ============================================================
   5) RESPUESTA FINAL
============================================================ */
echo json_encode([
  'success' => true,
  'resumen' => [
    'nombre' => $resumen['nombre'] ?? '',
    'rol' => $resumen['rol'] ?? '',
    'area_nombre' => $resumen['area_nombre'] ?? '',

    'total' => $total,
    'completadas_a_tiempo' => $aprobadas,
    'completadas_tarde' => $completadasTarde,

    'vencidas_abiertas' => $vencidasAbiertas,
    'vencidas_total' => $vencidasTotal,

    'pendientes' => $pendientes,
    'porcentaje' => $porcentaje,
    'semaforo' => $semaforo
  ],
  'data' => $out
], JSON_UNESCAPED_UNICODE);
exit;
