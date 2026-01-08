<?php
require_once __DIR__ . '/../core/db.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/auditoria.php';

header('Content-Type: application/json; charset=utf-8');
auth_require();

$conn = db();
$conn->begin_transaction();

try {
    $empresaId  = (int)$_SESSION['usuario']['empresa_id'];
    $usuarioId  = (int)$_SESSION['usuario']['usuario_id'];

    $titulo      = trim($_POST['titulo'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');
    $prioridad   = (int)($_POST['prioridad'] ?? 2);
    $responsable = (int)($_POST['responsable_id'] ?? 0);
    $objetivos   = $_POST['objetivos'] ?? [];

    if ($titulo === '' || empty($objetivos)) {
        throw new Exception('Datos incompletos');
    }

    /* 1️⃣ Crear estrategia */
    $stmt = $conn->prepare("
        INSERT INTO estrategias
        (empresa_id, titulo, descripcion, prioridad, responsable_usuario_id, creado_por)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'issiii',
        $empresaId,
        $titulo,
        $descripcion,
        $prioridad,
        $responsable,
        $usuarioId
    );
    $stmt->execute();

    $estrategiaId = $conn->insert_id;

    /* 2️⃣ Relacionar con objetivos */
    $stmtRel = $conn->prepare("
        INSERT INTO objetivo_estrategia
        (empresa_id, objetivo_id, estrategia_id)
        VALUES (?, ?, ?)
    ");

    foreach ($objetivos as $objetivoId) {
        $stmtRel->bind_param('iii', $empresaId, $objetivoId, $estrategiaId);
        $stmtRel->execute();
    }

    /* 3️⃣ Auditoría */
    auditar(
        $conn,
        $empresaId,
        'estrategia',
        $estrategiaId,
        'CREAR',
        $usuarioId,
        null,
        null,
        null,
        'Alta de estrategia'
    );

    $conn->commit();

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    $conn->rollback();
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
