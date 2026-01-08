<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$conn->begin_transaction();

$empresaId = (int)$_SESSION['usuario']['empresa_id'];
$usuarioId = (int)$_SESSION['usuario']['usuario_id'];

$estrategiaId = (int)($_POST['estrategia_id'] ?? 0);
$titulo       = trim($_POST['titulo'] ?? '');
$descripcion  = trim($_POST['descripcion'] ?? '');
$prioridad    = (int)($_POST['prioridad'] ?? 2);
$responsable  = (int)($_POST['responsable_id'] ?? 0);
$objetivos    = $_POST['objetivos'] ?? [];

if (
    $estrategiaId <= 0 ||
    $titulo === '' ||
    $responsable <= 0 ||
    !is_array($objetivos)
) {
    echo json_encode(['success'=>false,'message'=>'Datos inválidos']);
    exit;
}

/* =========================
   ESTADO ACTUAL
========================= */
$stmt = $conn->prepare("
    SELECT titulo, descripcion, prioridad, responsable_usuario_id
    FROM estrategias
    WHERE estrategia_id = ? AND empresa_id = ?
");
$stmt->bind_param('ii', $estrategiaId, $empresaId);
$stmt->execute();
$actual = $stmt->get_result()->fetch_assoc();

if (!$actual) {
    echo json_encode(['success'=>false,'message'=>'Estrategia no encontrada']);
    exit;
}

/* =========================
   UPDATE ESTRATEGIA
========================= */
$stmt = $conn->prepare("
    UPDATE estrategias
    SET titulo = ?, descripcion = ?, prioridad = ?, responsable_usuario_id = ?
    WHERE estrategia_id = ? AND empresa_id = ?
");
$stmt->bind_param(
    'ssiiii',
    $titulo,
    $descripcion,
    $prioridad,
    $responsable,
    $estrategiaId,
    $empresaId
);
$stmt->execute();

/* =========================
   AUDITORÍA CAMPOS
========================= */
$mapa = [
    'titulo' => $titulo,
    'descripcion' => $descripcion,
    'prioridad' => (string)$prioridad,
    'responsable_usuario_id' => (string)$responsable
];

foreach ($mapa as $campo => $nuevo) {
    if ((string)$actual[$campo] !== (string)$nuevo) {
        auditar(
            $conn,
            $empresaId,
            'estrategia',
            $estrategiaId,
            'EDITAR',
            $usuarioId,
            $campo,
            (string)$actual[$campo],
            (string)$nuevo
        );
    }
}

/* =========================
   SINCRONIZAR OBJETIVOS (N:N)
========================= */
$stmt = $conn->prepare("
    SELECT objetivo_id
    FROM objetivo_estrategia
    WHERE estrategia_id = ?
");
$stmt->bind_param('i', $estrategiaId);
$stmt->execute();
$actuales = array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'objetivo_id');

$nuevos   = array_map('intval', $objetivos);

$agregar  = array_diff($nuevos, $actuales);
$quitar   = array_diff($actuales, $nuevos);

/* Agregar relaciones */
$stmtInsert = $conn->prepare("
    INSERT INTO objetivo_estrategia (empresa_id, objetivo_id, estrategia_id)
    VALUES (?, ?, ?)
");

foreach ($agregar as $objId) {
    $stmtInsert->bind_param('iii', $empresaId, $objId, $estrategiaId);
    $stmtInsert->execute();

    auditar(
        $conn,
        $empresaId,
        'estrategia',
        $estrategiaId,
        'ASIGNAR_OBJETIVO',
        $usuarioId,
        'objetivo_id',
        null,
        (string)$objId
    );
}

/* Quitar relaciones */
$stmtDelete = $conn->prepare("
    DELETE FROM objetivo_estrategia
    WHERE estrategia_id = ? AND objetivo_id = ?
");

foreach ($quitar as $objId) {
    $stmtDelete->bind_param('ii', $estrategiaId, $objId);
    $stmtDelete->execute();

    auditar(
        $conn,
        $empresaId,
        'estrategia',
        $estrategiaId,
        'DESASIGNAR_OBJETIVO',
        $usuarioId,
        'objetivo_id',
        (string)$objId,
        null
    );
}

$conn->commit();

echo json_encode(['success'=>true]);
exit;
