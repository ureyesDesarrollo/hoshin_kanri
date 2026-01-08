<?php
require_once '../core/db.php';
require_once '../core/auth.php';

header('Content-Type: application/json; charset=utf-8');

auth_require();

$conn = db();
$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);

if ($empresaId <= 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Empresa no válida'
    ]);
    exit;
}

/*
  NOTA:
  Ajusta los WHERE si todavía no tienes empresa_id en alguna tabla.
*/

$data = [];

// Objetivos
$res = $conn->query("
    SELECT COUNT(*) AS total
    FROM objetivos
    WHERE empresa_id = {$empresaId}
");
$data['objetivos'] = (int)$res->fetch_assoc()['total'];

// Estrategias
$res = $conn->query("
    SELECT COUNT(*) AS total
    FROM estrategias
    WHERE empresa_id = {$empresaId} AND estatus = 1;
");
$data['estrategias'] = (int)$res->fetch_assoc()['total'];

// Milestones
$res = $conn->query("
    SELECT COUNT(*) AS total
    FROM milestones m
    JOIN estrategias e ON e.estrategia_id = m.estrategia_id
    WHERE e.empresa_id = {$empresaId}
");
$data['milestones'] = (int)$res->fetch_assoc()['total'];

// Tareas
$res = $conn->query("
    SELECT COUNT(*) AS total
    FROM tareas t
    JOIN milestones m ON m.milestone_id = t.milestone_id
    JOIN estrategias e ON e.estrategia_id = m.estrategia_id
    WHERE e.empresa_id = {$empresaId}
");
$data['tareas'] = (int)$res->fetch_assoc()['total'];

echo json_encode([
    'success' => true,
    'data' => $data
]);
exit;
