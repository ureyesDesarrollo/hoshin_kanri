<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$usuarioId = (int)($_SESSION['usuario']['id'] ?? $_SESSION['usuario']['usuario_id'] ?? 0);

$id = (int)($_POST['proyecto_directivo_id'] ?? 0);
$milestoneId = (int)($_POST['milestone_id'] ?? 0);
$nombre = trim((string)($_POST['nombre_directivo'] ?? ''));
$zonaDirectivaId = (int)($_POST['zona_directiva_id'] ?? 0);
$areaDirectivaId = (int)($_POST['area_directiva_id'] ?? 0);
$tipoProyectoDirectivoId = (int)($_POST['tipo_proyecto_directivo_id'] ?? 0);
$zona = trim((string)($_POST['zona'] ?? ''));
$tipo = trim((string)($_POST['tipo_proyecto'] ?? ''));
$prioridad = trim((string)($_POST['prioridad_directiva'] ?? 'Media'));
$estado = trim((string)($_POST['estado_directivo'] ?? 'En evaluacion'));
$requiereReporte = (int)($_POST['requiere_reporte_direccion'] ?? 1);
$inversion = (float)($_POST['inversion_estimada'] ?? 0);
$presupuesto = (float)($_POST['presupuesto_aprobado'] ?? 0);
$gasto = (float)($_POST['gasto_real'] ?? 0);
$beneficioEstimado = (float)($_POST['beneficio_estimado'] ?? 0);
$beneficioReal = (float)($_POST['beneficio_real'] ?? 0);
$fechaInicio = trim((string)($_POST['fecha_inicio_directiva'] ?? ''));
$fechaFin = trim((string)($_POST['fecha_fin_objetivo'] ?? ''));
$notas = trim((string)($_POST['notas_directivas'] ?? ''));
$motivoPrioridad = trim((string)($_POST['motivo_prioridad'] ?? ''));

$prioridadesValidas = ['Baja', 'Media', 'Alta', 'Critica'];
$estadosValidos = ['En evaluacion', 'Aprobado', 'En ejecucion', 'Pausado', 'Cerrado', 'Cancelado'];

if ($empresaId <= 0 || $milestoneId <= 0 || $nombre === '') {
  echo json_encode(['success' => false, 'message' => 'Milestone y nombre son obligatorios'], JSON_UNESCAPED_UNICODE);
  exit;
}

if (!in_array($prioridad, $prioridadesValidas, true) || !in_array($estado, $estadosValidos, true)) {
  echo json_encode(['success' => false, 'message' => 'Prioridad o estado inválido'], JSON_UNESCAPED_UNICODE);
  exit;
}

$stmtMilestone = $conn->prepare("
SELECT m.milestone_id
FROM milestones m
JOIN estrategias e ON e.estrategia_id = m.estrategia_id
WHERE m.milestone_id = ? AND e.empresa_id = ?
LIMIT 1
");
$stmtMilestone->bind_param('ii', $milestoneId, $empresaId);
$stmtMilestone->execute();
$existeMilestone = $stmtMilestone->get_result()->fetch_assoc();
$stmtMilestone->close();

if (!$existeMilestone) {
  echo json_encode(['success' => false, 'message' => 'Milestone no encontrado para esta empresa'], JSON_UNESCAPED_UNICODE);
  exit;
}

$fechaInicio = $fechaInicio !== '' ? $fechaInicio : null;
$fechaFin = $fechaFin !== '' ? $fechaFin : null;
$zonaDirectivaId = $zonaDirectivaId > 0 ? $zonaDirectivaId : null;
$areaDirectivaId = $areaDirectivaId > 0 ? $areaDirectivaId : null;
$tipoProyectoDirectivoId = $tipoProyectoDirectivoId > 0 ? $tipoProyectoDirectivoId : null;

if ($zonaDirectivaId !== null) {
  $stmtZona = $conn->prepare("
    SELECT nombre
    FROM zonas_directivas
    WHERE zona_directiva_id = ? AND empresa_id = ? AND activo = 1
    LIMIT 1
  ");
  $stmtZona->bind_param('ii', $zonaDirectivaId, $empresaId);
  $stmtZona->execute();
  $zonaRow = $stmtZona->get_result()->fetch_assoc();
  $stmtZona->close();

  if (!$zonaRow) {
    echo json_encode(['success' => false, 'message' => 'Zona inválida'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $zona = $zonaRow['nombre'];
}

if ($areaDirectivaId !== null) {
  $stmtArea = $conn->prepare("
    SELECT ad.nombre
    FROM areas_directivas ad
    WHERE ad.area_directiva_id = ?
      AND ad.empresa_id = ?
      AND ad.activo = 1
      AND (? IS NULL OR ad.zona_directiva_id = ?)
    LIMIT 1
  ");
  $stmtArea->bind_param('iiii', $areaDirectivaId, $empresaId, $zonaDirectivaId, $zonaDirectivaId);
  $stmtArea->execute();
  $areaRow = $stmtArea->get_result()->fetch_assoc();
  $stmtArea->close();

  if (!$areaRow) {
    echo json_encode(['success' => false, 'message' => 'Área inválida'], JSON_UNESCAPED_UNICODE);
    exit;
  }
}

if ($tipoProyectoDirectivoId !== null) {
  $stmtTipo = $conn->prepare("
    SELECT nombre
    FROM tipos_proyecto_directivos
    WHERE tipo_proyecto_directivo_id = ? AND empresa_id = ? AND activo = 1
    LIMIT 1
  ");
  $stmtTipo->bind_param('ii', $tipoProyectoDirectivoId, $empresaId);
  $stmtTipo->execute();
  $tipoRow = $stmtTipo->get_result()->fetch_assoc();
  $stmtTipo->close();

  if (!$tipoRow) {
    echo json_encode(['success' => false, 'message' => 'Tipo de proyecto inválido'], JSON_UNESCAPED_UNICODE);
    exit;
  }

  $tipo = $tipoRow['nombre'];
}

$conn->begin_transaction();

try {
  $prioridadAnterior = null;

  if ($id > 0) {
    $stmtActual = $conn->prepare("
      SELECT prioridad_directiva
      FROM proyectos_directivos
      WHERE proyecto_directivo_id = ? AND empresa_id = ?
      LIMIT 1
    ");
    $stmtActual->bind_param('ii', $id, $empresaId);
    $stmtActual->execute();
    $actual = $stmtActual->get_result()->fetch_assoc();
    $stmtActual->close();

    if (!$actual) {
      throw new Exception('Proyecto directivo no encontrado');
    }

    $prioridadAnterior = $actual['prioridad_directiva'];

    $stmt = $conn->prepare("
      UPDATE proyectos_directivos
      SET milestone_id = ?, nombre_directivo = ?, zona_directiva_id = ?, area_directiva_id = ?,
          tipo_proyecto_directivo_id = ?, zona = ?, tipo_proyecto = ?,
          prioridad_directiva = ?, estado_directivo = ?, requiere_reporte_direccion = ?,
          inversion_estimada = ?, presupuesto_aprobado = ?, gasto_real = ?,
          beneficio_estimado = ?, beneficio_real = ?, fecha_inicio_directiva = ?,
          fecha_fin_objetivo = ?, notas_directivas = ?, actualizado_por = ?
      WHERE proyecto_directivo_id = ? AND empresa_id = ?
    ");
    $stmt->bind_param(
      'isiiissssidddddsssiii',
      $milestoneId,
      $nombre,
      $zonaDirectivaId,
      $areaDirectivaId,
      $tipoProyectoDirectivoId,
      $zona,
      $tipo,
      $prioridad,
      $estado,
      $requiereReporte,
      $inversion,
      $presupuesto,
      $gasto,
      $beneficioEstimado,
      $beneficioReal,
      $fechaInicio,
      $fechaFin,
      $notas,
      $usuarioId,
      $id,
      $empresaId
    );
    $stmt->execute();
    $stmt->close();
  } else {
    $stmtExistente = $conn->prepare("
      SELECT proyecto_directivo_id, prioridad_directiva
      FROM proyectos_directivos
      WHERE empresa_id = ? AND milestone_id = ?
      LIMIT 1
    ");
    $stmtExistente->bind_param('ii', $empresaId, $milestoneId);
    $stmtExistente->execute();
    $existente = $stmtExistente->get_result()->fetch_assoc();
    $stmtExistente->close();

    if ($existente) {
      $id = (int)$existente['proyecto_directivo_id'];
      $prioridadAnterior = $existente['prioridad_directiva'];

      $stmt = $conn->prepare("
        UPDATE proyectos_directivos
        SET nombre_directivo = ?, zona_directiva_id = ?, area_directiva_id = ?, zona = ?,
            tipo_proyecto_directivo_id = ?, tipo_proyecto = ?, prioridad_directiva = ?,
            estado_directivo = ?, requiere_reporte_direccion = ?, visible_en_direccion = 1,
            inversion_estimada = ?, presupuesto_aprobado = ?, gasto_real = ?,
            beneficio_estimado = ?, beneficio_real = ?, fecha_inicio_directiva = ?,
            fecha_fin_objetivo = ?, notas_directivas = ?, actualizado_por = ?
        WHERE proyecto_directivo_id = ? AND empresa_id = ?
      ");
      $stmt->bind_param(
        'siisisssidddddsssiii',
        $nombre,
        $zonaDirectivaId,
        $areaDirectivaId,
        $zona,
        $tipoProyectoDirectivoId,
        $tipo,
        $prioridad,
        $estado,
        $requiereReporte,
        $inversion,
        $presupuesto,
        $gasto,
        $beneficioEstimado,
        $beneficioReal,
        $fechaInicio,
        $fechaFin,
        $notas,
        $usuarioId,
        $id,
        $empresaId
      );
      $stmt->execute();
      $stmt->close();
    } else {
      $stmt = $conn->prepare("
        INSERT INTO proyectos_directivos (
          empresa_id, milestone_id, nombre_directivo, zona_directiva_id, area_directiva_id,
          tipo_proyecto_directivo_id, zona, tipo_proyecto,
          prioridad_directiva, estado_directivo, requiere_reporte_direccion,
          inversion_estimada, presupuesto_aprobado, gasto_real,
          beneficio_estimado, beneficio_real, fecha_inicio_directiva,
          fecha_fin_objetivo, notas_directivas, creado_por, actualizado_por
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
      ");
      $stmt->bind_param(
        'iisiiissssidddddsssii',
        $empresaId,
        $milestoneId,
        $nombre,
        $zonaDirectivaId,
        $areaDirectivaId,
        $tipoProyectoDirectivoId,
        $zona,
        $tipo,
        $prioridad,
        $estado,
        $requiereReporte,
        $inversion,
        $presupuesto,
        $gasto,
        $beneficioEstimado,
        $beneficioReal,
        $fechaInicio,
        $fechaFin,
        $notas,
        $usuarioId,
        $usuarioId
      );
      $stmt->execute();
      $id = (int)$conn->insert_id;
      $stmt->close();
    }
  }

  if ($prioridadAnterior !== $prioridad) {
    $stmtHist = $conn->prepare("
      INSERT INTO historial_prioridad_directiva (
        proyecto_directivo_id, prioridad_anterior, prioridad_nueva, motivo, cambiado_por
      ) VALUES (?, ?, ?, ?, ?)
    ");
    $stmtHist->bind_param('isssi', $id, $prioridadAnterior, $prioridad, $motivoPrioridad, $usuarioId);
    $stmtHist->execute();
    $stmtHist->close();
  }

  $conn->commit();
  echo json_encode(['success' => true, 'message' => 'Proyecto directivo guardado', 'id' => $id], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  $conn->rollback();
  echo json_encode(['success' => false, 'message' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}

exit;
