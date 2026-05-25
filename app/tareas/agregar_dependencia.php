<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$usuarioId = (int)$_SESSION['usuario']['usuario_id'];
$empresaId = (int)$_SESSION['usuario']['empresa_id'];

$tareaId = (int)($_POST['tarea_id'] ?? 0);
$tareaDependienteId = (int)($_POST['tarea_dependiente_id'] ?? 0);
$tipoRelacion = $_POST['tipo_relacion'] ?? 'FS'; // FS, SS, FF, SF
$diasDesfase = (int)($_POST['dias_desfase'] ?? 0);

// Validación básica
if ($tareaId <= 0 || $tareaDependienteId <= 0) {
    echo json_encode(['success' => false, 'message' => 'IDs de tareas inválidos']);
    exit;
}

if ($tareaId === $tareaDependienteId) {
    echo json_encode(['success' => false, 'message' => 'Una tarea no puede depender de sí misma']);
    exit;
}

// Validar que ambas tareas existan
$stmt = $conn->prepare("SELECT tarea_id FROM tareas WHERE tarea_id = ? LIMIT 1");
$stmt->bind_param('i', $tareaId);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Tarea principal no existe']);
    $stmt->close();
    exit;
}
$stmt->close();

$stmt = $conn->prepare("SELECT tarea_id FROM tareas WHERE tarea_id = ? LIMIT 1");
$stmt->bind_param('i', $tareaDependienteId);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Tarea dependiente no existe']);
    $stmt->close();
    exit;
}
$stmt->close();

// Detectar dependencias circulares
function tieneDependenciaCircular($conn, $tareaId, $tareaDependienteId) {
    // Si la tarea dependiente ya depende (directa o indirectamente) de la tarea principal,
    // sería una dependencia circular
    $stmt = $conn->prepare("
        WITH RECURSIVE dependencias AS (
            SELECT tarea_dependiente_id FROM tarea_dependencias WHERE tarea_id = ?
            UNION ALL
            SELECT d.tarea_dependiente_id 
            FROM tarea_dependencias d
            INNER JOIN dependencias ON d.tarea_id = dependencias.tarea_dependiente_id
        )
        SELECT COUNT(*) as count FROM dependencias WHERE tarea_dependiente_id = ?
    ");
    $stmt->bind_param('ii', $tareaDependienteId, $tareaId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    return $result['count'] > 0;
}

// Verificar dependencia circular
if (tieneDependenciaCircular($conn, $tareaDependienteId, $tareaId)) {
    echo json_encode(['success' => false, 'message' => 'No se puede crear esta dependencia: causaría una referencia circular']);
    exit;
}

// Verificar si ya existe esta dependencia
$stmt = $conn->prepare("
    SELECT tarea_dependencia_id FROM tarea_dependencias 
    WHERE tarea_id = ? AND tarea_dependiente_id = ?
");
$stmt->bind_param('ii', $tareaId, $tareaDependienteId);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Esta dependencia ya existe']);
    $stmt->close();
    exit;
}
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

// Auditar
auditar(
    $conn,
    $empresaId,
    'tarea_dependencia',
    $dependenciaId,
    'CREAR',
    $usuarioId
);

echo json_encode([
    'success' => true,
    'message' => 'Dependencia agregada correctamente',
    'dependencia_id' => $dependenciaId
]);
exit;
