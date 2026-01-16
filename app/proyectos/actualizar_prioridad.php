<?php

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';

auth_require();
$conn = db();

$empresaId = (int)($_SESSION['usuario']['empresa_id'] ?? 0);
$milestoneId = (int)($_POST['milestone_id'] ?? 0);
$prioridad = (int)($_POST['prioridad'] ?? 0);

$sql = "UPDATE milestones SET prioridad = ? WHERE milestone_id = ?";
$sql_tareas = "UPDATE tareas SET prioridad = ? WHERE milestone_id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt->bind_param('ii', $prioridad, $milestoneId);

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error execute: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
    $stmt->close();
    exit;
}

$stmt->close();

$stmt_tareas = $conn->prepare($sql_tareas);
if (!$stmt_tareas) {
    echo json_encode(['success' => false, 'message' => 'Error prepare: ' . $conn->error], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt_tareas->bind_param('ii', $prioridad, $milestoneId);

if (!$stmt_tareas->execute()) {
    echo json_encode(['success' => false, 'message' => 'Error execute: ' . $stmt_tareas->error], JSON_UNESCAPED_UNICODE);
    $stmt_tareas->close();
    exit;
}

$stmt_tareas->close();

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
exit;
