<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$page        = max(1, (int)($_GET['page'] ?? 1));
$perPage     = 10;
$offset      = ($page - 1) * $perPage;

/* =========================
   TOTAL
========================= */
$stmt = $conn->prepare("
SELECT COUNT(*) AS total
FROM tareas
");
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];

/* =========================
   LISTADO
========================= */
$stmt = $conn->prepare("
SELECT
    t.tarea_id,
    t.titulo,
    t.descripcion,
    t.fecha_inicio,
    t.fecha_fin,
    t.completada,
    t.completada_en,
    t.creado_en,
    u.nombre_completo AS responsable
FROM tareas t
JOIN usuarios u ON u.usuario_id = t.responsable_usuario_id
ORDER BY t.tarea_id DESC
LIMIT ? OFFSET ?
");

$stmt->bind_param('ii', $perPage, $offset);
$stmt->execute();

$data = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'success' => true,
    'data' => $data,
    'pagination' => [
        'page' => $page,
        'per_page' => $perPage,
        'total' => $total,
        'total_pages' => (int)ceil($total / $perPage)
    ]
]);
exit;
