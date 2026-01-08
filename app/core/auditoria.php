<?php
/**
 * Registra un evento de auditoría
 *
 * @param mysqli $conn
 * @param int    $empresaId
 * @param string $entidad        objetivo | estrategia | milestone | tarea | rca
 * @param int    $entidadId
 * @param string $accion         CREAR | EDITAR | REASIGNAR | REPLANIFICAR | CERRAR | CANCELAR
 * @param int    $usuarioId
 * @param string|null $campo
 * @param string|null $valorAnterior
 * @param string|null $valorNuevo
 * @param string|null $motivo
 */
function auditar(
    mysqli $conn,
    int $empresaId,
    string $entidad,
    int $entidadId,
    string $accion,
    int $usuarioId,
    ?string $campo = null,
    ?string $valorAnterior = null,
    ?string $valorNuevo = null,
    ?string $motivo = null
) {
    $sql = "
    INSERT INTO auditoria (
        empresa_id,
        entidad,
        entidad_id,
        accion,
        campo,
        valor_anterior,
        valor_nuevo,
        motivo,
        usuario_id
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ";

    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        // Falla silenciosa controlada (no rompemos flujo productivo)
        return false;
    }

    $stmt->bind_param(
        'isisssssi',
        $empresaId,
        $entidad,
        $entidadId,
        $accion,
        $campo,
        $valorAnterior,
        $valorNuevo,
        $motivo,
        $usuarioId
    );

    return $stmt->execute();
}
