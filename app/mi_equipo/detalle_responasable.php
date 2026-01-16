<?php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

auth_require();
$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$gerenteId = (int)($_SESSION['usuario']['usuario_id'] ?? 0);
$responsableId = (int)($_GET['responsable_id'] ?? 0); // colaborador

if ($empresaId <= 0 || $gerenteId <= 0 || $responsableId <= 0) {
    echo json_encode(['success' => false, 'msg' => 'Datos inválidos'], JSON_UNESCAPED_UNICODE);
    exit;
}


/* ============================================================
   1) RESUMEN del RESPONSABLE (colaborador) dentro de MIS estrategias
============================================================ */
$sqlResumen = "
SELECT
  ru.nombre_completo AS nombre,
  rr.nombre AS rol,
  aa.nombre AS area_nombre,

  COUNT(DISTINCT t.tarea_id) AS total,

  COALESCE(SUM(t.estatus = 4),0) AS completadas_a_tiempo,
  COALESCE(SUM(t.estatus = 6),0) AS completadas_fuera_tiempo,

  COALESCE(SUM(t.estatus IN (1,2,3,5) AND t.fecha_fin < CURDATE()),0) AS vencidas_abiertas,

  (
    COALESCE(SUM(t.estatus IN (1,2,3,5) AND t.fecha_fin < CURDATE()),0)
    +
    COALESCE(SUM(t.estatus = 6),0)
  ) AS vencidas_total

FROM estrategias e
JOIN milestones m ON m.estrategia_id = e.estrategia_id
JOIN tareas t ON t.milestone_id = m.milestone_id

JOIN usuarios ru ON ru.usuario_id = t.responsable_usuario_id
JOIN usuarios_empresas ue ON ue.usuario_id = ru.usuario_id AND ue.activo = 1 AND ue.empresa_id = e.empresa_id
JOIN roles rr ON rr.rol_id = ue.rol_id
LEFT JOIN areas aa ON aa.area_id = ue.area_id

WHERE e.empresa_id = ?
  AND e.responsable_usuario_id = ?   /* MIS estrategias (gerente) */
  AND t.responsable_usuario_id = ?   /* ESTE responsable */
GROUP BY ru.usuario_id, ru.nombre_completo, rr.nombre, aa.nombre
";

$stmt = $conn->prepare($sqlResumen);
if (!$stmt) {
    echo json_encode(['success' => false, 'msg' => 'Error prepare resumen: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('iii', $empresaId, $gerenteId, $responsableId);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'msg' => 'Error execute resumen: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
    $stmt->close();
    exit;
}

$resumen = $stmt->get_result()->fetch_assoc() ?: [];
$stmt->close();

$total            = (int)($resumen['total'] ?? 0);
$aprobadas        = (int)($resumen['completadas_a_tiempo'] ?? 0);
$fueraTiempo      = (int)($resumen['completadas_fuera_tiempo'] ?? 0);
$vencidasAbiertas = (int)($resumen['vencidas_abiertas'] ?? 0);
$vencidasTotal    = (int)($resumen['vencidas_total'] ?? 0);

$pendientes = max(0, $total - $aprobadas - $vencidasTotal);

$rojasTotal = $vencidasTotal;
$totalCumplimiento = $aprobadas + $rojasTotal;

$porcentaje = $totalCumplimiento > 0
    ? round(($aprobadas / $totalCumplimiento) * 100)
    : 0;

$semaforo = 'VERDE';
if ($rojasTotal > 0) $semaforo = 'ROJO';
elseif ($total === 0) $semaforo = 'WARNING';


/* ============================================================
   2) DETALLE jerárquico de ese responsable dentro de MIS estrategias
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
  t.completada

FROM estrategias e
LEFT JOIN objetivo_estrategia oe ON oe.estrategia_id = e.estrategia_id
LEFT JOIN objetivos o ON o.objetivo_id = oe.objetivo_id

LEFT JOIN milestones m ON m.estrategia_id = e.estrategia_id

LEFT JOIN tareas t
  ON t.milestone_id = m.milestone_id
 AND t.responsable_usuario_id = ?

WHERE e.empresa_id = ?
  AND e.responsable_usuario_id = ?

ORDER BY o.objetivo_id, e.estrategia_id, m.milestone_id, t.fecha_fin
";

$stmt2 = $conn->prepare($sqlDetalle);
if (!$stmt2) {
    echo json_encode(['success' => false, 'msg' => 'Error prepare detalle: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt2->bind_param('iii', $responsableId, $empresaId, $gerenteId);

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
   3) ARMAR JERARQUÍA + semáforo/estatus_txt
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

    $fechaFin = $r['fecha_fin'] ?? null;

    $tSem = 'VERDE';
    if ($estatus === 6) {
        $tSem = 'ROJO';
    } elseif ($estatus !== 4 && $fechaFin && $fechaFin < $hoy) {
        $tSem = 'ROJO';
    }

    $tipoVencida = 'NINGUNA';
    if ($estatus === 6) {
        $tipoVencida = 'COMPLETADA_TARDE';
    } elseif ($estatus !== 4 && $fechaFin && $fechaFin < $hoy) {
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
        $data[$objId]['estrategias'][$estId]['milestones'][$milId]['tareas'][] = [
            'tarea_id' => $tarId,
            'tarea' => $r['tarea_titulo'] ?? '',
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
        'completadas_fuera_tiempo' => $fueraTiempo,

        'vencidas_abiertas' => $vencidasAbiertas,
        'vencidas_total' => $vencidasTotal,

        'pendientes' => $pendientes,
        'porcentaje' => $porcentaje,
        'semaforo' => $semaforo
    ],
    'data' => $out
], JSON_UNESCAPED_UNICODE);
exit;
