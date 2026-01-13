<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

auth_require();
$conn = db();

$usuarioId = (int)($_GET['usuario_id'] ?? 0);

if ($usuarioId <= 0) {
  echo json_encode(['success' => false, 'msg' => 'Usuario inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ============================================================
   1) RESUMEN GLOBAL DEL GERENTE
   Solo tareas de estrategias donde el usuario es responsable
   - Aprobadas (estatus=4)
   - Completadas tarde (estatus=6)
   - Vencidas abiertas (estatus IN 1,2,3,5 y fecha_fin < CURDATE())
   - Vencidas total = vencidas_abiertas + completadas_tarde
============================================================ */
$sqlResumen = "
SELECT
    u.nombre_completo AS nombre,
    r.nombre AS rol,
    a.nombre AS area_nombre,

    COUNT(DISTINCT t.tarea_id) AS total,

    COALESCE(SUM(t.estatus = 4),0) AS completadas_a_tiempo,
    COALESCE(SUM(t.estatus = 6),0) AS completadas_fuera_tiempo,

    COALESCE(SUM(t.estatus IN (1,2,3,5) AND t.fecha_fin < CURDATE()),0) AS vencidas_abiertas,

    (
      COALESCE(SUM(t.estatus IN (1,2,3,5) AND t.fecha_fin < CURDATE()),0)
      +
      COALESCE(SUM(t.estatus = 6),0)
    ) AS vencidas_total

FROM usuarios u
JOIN usuarios_empresas ue ON ue.usuario_id = u.usuario_id AND ue.activo = 1
JOIN roles r ON r.rol_id = ue.rol_id
LEFT JOIN areas a ON a.area_id = ue.area_id

JOIN estrategias e ON e.responsable_usuario_id = u.usuario_id
JOIN milestones m ON m.estrategia_id = e.estrategia_id
JOIN tareas t ON t.milestone_id = m.milestone_id

WHERE u.usuario_id = ?
";

$stmt = $conn->prepare($sqlResumen);
if (!$stmt) {
  echo json_encode(['success' => false, 'msg' => 'Error prepare resumen: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$resumen = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$total            = (int)($resumen['total'] ?? 0);
$aprobadas        = (int)($resumen['completadas_a_tiempo'] ?? 0);
$fueraTiempo      = (int)($resumen['completadas_fuera_tiempo'] ?? 0);
$vencidasAbiertas = (int)($resumen['vencidas_abiertas'] ?? 0);
$vencidasTotal    = (int)($resumen['vencidas_total'] ?? 0);

/*
  Pendientes = Total - (Aprobadas) - (Vencidas total)
  (vencidas total incluye abiertas vencidas + completadas tarde)
*/
$pendientes = max(0, $total - $aprobadas - $vencidasTotal);

/*
  % Rojo (tu lógica): rojas / (aprobadas + rojas)
  rojas = vencidas total
*/
$rojasTotal = $vencidasTotal;
$totalCumplimiento = $aprobadas + $rojasTotal;

$porcentaje = $totalCumplimiento > 0
  ? round(($aprobadas / $totalCumplimiento) * 100)
  : 0;


$semaforo = 'VERDE';
if ($rojasTotal > 0) $semaforo = 'ROJO';
elseif ($total === 0) $semaforo = 'WARNING';


/* ============================================================
   2) DETALLE JERÁRQUICO SIN DUPLICAR TAREAS
   Se asigna un objetivo representativo por estrategia
============================================================ */
$sqlDetalle = "
SELECT
    t.tarea_id,
    t.titulo AS tarea,
    t.fecha_inicio,
    t.fecha_fin,
    t.estatus,
    t.completada,

    m.milestone_id,
    m.titulo AS milestone,

    e.estrategia_id,
    e.titulo AS estrategia,

    o.objetivo_id,
    o.titulo AS objetivo

FROM estrategias e
JOIN milestones m ON m.estrategia_id = e.estrategia_id
JOIN tareas t ON t.milestone_id = m.milestone_id

LEFT JOIN (
    SELECT oe.estrategia_id, MIN(o.objetivo_id) AS objetivo_id, MIN(o.titulo) AS titulo
    FROM objetivo_estrategia oe
    JOIN objetivos o ON o.objetivo_id = oe.objetivo_id
    GROUP BY oe.estrategia_id
) o ON o.estrategia_id = e.estrategia_id

WHERE e.responsable_usuario_id = ?
ORDER BY o.titulo, e.titulo, m.titulo, t.fecha_fin
";

$stmt = $conn->prepare($sqlDetalle);
if (!$stmt) {
  echo json_encode(['success' => false, 'msg' => 'Error prepare detalle: ' . $conn->error], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmt->bind_param('i', $usuarioId);
$stmt->execute();
$rs = $stmt->get_result();

/* ============================================================
   3) ARMAR JERARQUÍA
============================================================ */
$data = [];
$hoy = date('Y-m-d');

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

  // Semáforo tarea: ROJO si completada tarde (6) o si no aprobada y ya venció
  $fechaFin = $r['fecha_fin'] ?? null;

  $tSem = 'VERDE';
  if ($estatus === 6) {
    $tSem = 'ROJO';
  } elseif ($estatus !== 4 && $fechaFin && $fechaFin < $hoy) {
    $tSem = 'ROJO';
  }

  // Tipo de vencida a nivel tarea (opcional para UI)
  $tipoVencida = 'NINGUNA';
  if ($estatus === 6) {
    $tipoVencida = 'COMPLETADA_TARDE';
  } elseif ($estatus !== 4 && $fechaFin && $fechaFin < $hoy) {
    $tipoVencida = 'ABIERTA_VENCIDA';
  }

  $objId = (int)($r['objetivo_id'] ?? 0);
  $estId = (int)($r['estrategia_id'] ?? 0);
  $milId = (int)($r['milestone_id'] ?? 0);

  if (!isset($data[$objId])) {
    $data[$objId] = [
      'objetivo' => $r['objetivo'] ?? 'Sin objetivo',
      'estrategias' => [],
      'rojas' => 0
    ];
  }

  if (!isset($data[$objId]['estrategias'][$estId])) {
    $data[$objId]['estrategias'][$estId] = [
      'estrategia' => $r['estrategia'] ?? 'Sin estrategia',
      'milestones' => [],
      'rojas' => 0
    ];
  }

  if (!isset($data[$objId]['estrategias'][$estId]['milestones'][$milId])) {
    $data[$objId]['estrategias'][$estId]['milestones'][$milId] = [
      'milestone' => $r['milestone'] ?? 'Sin milestone',
      'tareas' => [],
      'rojas' => 0
    ];
  }

  $data[$objId]['estrategias'][$estId]['milestones'][$milId]['tareas'][] = [
    'tarea_id' => (int)($r['tarea_id'] ?? 0),
    'tarea' => $r['tarea'] ?? '',
    'fecha_inicio' => $r['fecha_inicio'] ?? null,
    'fecha_fin' => $fechaFin,
    'estatus' => $estatus,
    'estatus_txt' => $estatusTxt,
    'completada' => (int)($r['completada'] ?? 0),
    'semaforo' => $tSem,
    'tipo_vencida' => $tipoVencida
  ];

  if ($tSem === 'ROJO') {
    $data[$objId]['rojas']++;
    $data[$objId]['estrategias'][$estId]['rojas']++;
    $data[$objId]['estrategias'][$estId]['milestones'][$milId]['rojas']++;
  }
}

$stmt->close();

/* ============================================================
   4) NORMALIZAR JERARQUÍA (arrays)
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
    'completadas_fuera_tiempo' => $fueraTiempo,

    // clave: separadas y total
    'vencidas_abiertas' => $vencidasAbiertas,
    'vencidas_total' => $vencidasTotal,

    'pendientes' => $pendientes,
    'porcentaje' => $porcentaje,
    'semaforo' => $semaforo
  ],
  'data' => $out
], JSON_UNESCAPED_UNICODE);
exit;
