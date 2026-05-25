<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$milestoneId  = (int)($_POST['milestone_id'] ?? 0);
$titulo       = trim($_POST['titulo'] ?? '');
$descripcion  = trim($_POST['descripcion'] ?? '');
$responsable  = (int)($_POST['responsable_id'] ?? 0);
$fechaInicio  = $_POST['fecha_inicio'] ?? '';
$fechaFin     = $_POST['fecha_fin'] ?? '';
$tareaDependienteId = (int)($_POST['tarea_dependiente_id'] ?? 0);
$tipoRelacion = $_POST['tipo_relacion'] ?? 'FS';
$diasDesfase  = (int)($_POST['dias_desfase'] ?? 0);

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId = (int)$_SESSION['usuario']['usuario_id'];

if (
  $milestoneId <= 0 ||
  $titulo === '' ||
  $responsable <= 0 ||
  !$fechaInicio ||
  !$fechaFin
) {
  echo json_encode(['success' => false, 'message' => 'Datos inválidos']);
  exit;
}


$sqlMilestone = "SELECT milestone_id, estatus FROM milestones WHERE milestone_id = ?;";
$stmtMilestone = $conn->prepare($sqlMilestone);
$stmtMilestone->bind_param('i', $milestoneId);
$stmtMilestone->execute();
$resultMilestone = $stmtMilestone->get_result();
if ($resultMilestone->num_rows === 0) {
  echo json_encode(['success' => false, 'message' => 'Milestone inválido']);
  exit;
}
$stmtMilestone->close();

if ($resultMilestone->fetch_assoc()['estatus'] == 2) {
  $stmtMilestone = $conn->prepare("UPDATE milestones SET estatus = 1 WHERE milestone_id = ?");
  $stmtMilestone->bind_param('i', $milestoneId);
  $stmtMilestone->execute();
  $stmtMilestone->close();
}

// 2) Insertar tarea

$stmt = $conn->prepare("
INSERT INTO tareas (
    milestone_id,
    titulo,
    descripcion,
    responsable_usuario_id,
    fecha_inicio,
    fecha_fin,
    creado_por
) VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
  'ississi',
  $milestoneId,
  $titulo,
  $descripcion,
  $responsable,
  $fechaInicio,
  $fechaFin,
  $usuarioId
);

$stmt->execute();

$tareaId = $conn->insert_id;

auditar(
  $conn,
  $empresaId,
  'tarea',
  $tareaId,
  'CREAR',
  $usuarioId
);

// Guardar dependencia si se proporciona
if ($tareaDependienteId > 0) {
  // Verificar que la tarea dependiente existe
  $stmt = $conn->prepare("SELECT tarea_id FROM tareas WHERE tarea_id = ? LIMIT 1");
  $stmt->bind_param('i', $tareaDependienteId);
  $stmt->execute();
  if ($stmt->get_result()->num_rows > 0) {
    $stmt->close();
    
    // Insertar dependencia
    $stmt = $conn->prepare("
      INSERT INTO tarea_dependencias (
        tarea_id,
        tarea_dependiente_id,
        tipo_relacion,
        dias_desfase
      ) VALUES (?, ?, ?, ?)
    ");
    
    $stmt->bind_param('iisi', $tareaId, $tareaDependienteId, $tipoRelacion, $diasDesfase);
    $stmt->execute();
    $dependenciaId = $conn->insert_id;
    $stmt->close();
    
    // Auditar la dependencia
    auditar(
      $conn,
      $empresaId,
      'tarea_dependencia',
      $dependenciaId,
      'CREAR',
      $usuarioId
    );
  } else {
    $stmt->close();
  }
}

echo json_encode(['success' => true]);
exit;
