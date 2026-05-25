<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$tareaId = (int)($_GET['tarea_id'] ?? $_POST['tarea_id'] ?? 0);

if ($tareaId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de tarea inválido']);
    exit;
}

// Listar tareas de las cuales esta tarea depende (dependencias)
$sql = "
    SELECT 
        td.tarea_dependencia_id,
        td.tarea_id,
        td.tarea_dependiente_id,
        td.tipo_relacion,
        td.dias_desfase,
        td.creado_en,
        t.titulo as titulo_tarea_dependiente,
        t.fecha_fin as fecha_fin_dependiente,
        t.responsable_usuario_id,
        u.nombre_completo as nombre_responsable
    FROM tarea_dependencias td
    INNER JOIN tareas t ON td.tarea_dependiente_id = t.tarea_id
    INNER JOIN usuarios u ON t.responsable_usuario_id = u.usuario_id
    WHERE td.tarea_id = ?
    ORDER BY t.titulo ASC
";

$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $tareaId);
$stmt->execute();
$result = $stmt->get_result();

$dependencias = [];
while ($row = $result->fetch_assoc()) {
    $dependencias[] = $row;
}
$stmt->close();

// Listar tareas que dependen de esta tarea (dependientes)
$sql2 = "
    SELECT 
        td.tarea_dependencia_id,
        td.tarea_id,
        td.tarea_dependiente_id,
        td.tipo_relacion,
        td.dias_desfase,
        td.creado_en,
        t.titulo as titulo_tarea,
        t.fecha_fin as fecha_fin_tarea,
        t.responsable_usuario_id,
        u.nombre_completo as nombre_responsable
    FROM tarea_dependencias td
    INNER JOIN tareas t ON td.tarea_id = t.tarea_id
    INNER JOIN usuarios u ON t.responsable_usuario_id = u.usuario_id
    WHERE td.tarea_dependiente_id = ?
    ORDER BY t.titulo ASC
";

$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param('i', $tareaId);
$stmt2->execute();
$result2 = $stmt2->get_result();

$dependientes = [];
while ($row = $result2->fetch_assoc()) {
    $dependientes[] = $row;
}
$stmt2->close();

echo json_encode([
    'success' => true,
    'dependencias' => $dependencias, // Tareas de las que depende
    'dependientes' => $dependientes   // Tareas que dependen de esta
]);
exit;
