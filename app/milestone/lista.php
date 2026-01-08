<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 10;
$offset    = ($page - 1) * $perPage;

/* =========================
   TOTAL
========================= */
$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM milestones m
    JOIN estrategias e ON e.estrategia_id = m.estrategia_id
    WHERE e.empresa_id = ?
");
$stmt->bind_param('i', $empresaId);
$stmt->execute();
$total = (int)$stmt->get_result()->fetch_assoc()['total'];

/* =========================
   LISTADO
========================= */
$stmt = $conn->prepare("
    SELECT
        m.milestone_id,
        m.titulo,
        m.descripcion,
        m.estatus,
        m.creado_en,
        e.titulo AS estrategia,
        u.nombre_completo AS responsable
    FROM milestones m
    JOIN estrategias e ON e.estrategia_id = m.estrategia_id
    JOIN usuarios u ON u.usuario_id = m.responsable_usuario_id
    WHERE e.empresa_id = ?
    ORDER BY m.milestone_id DESC
    LIMIT ? OFFSET ?
");
$stmt->bind_param('iii', $empresaId, $perPage, $offset);
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
