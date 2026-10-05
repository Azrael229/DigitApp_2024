<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        header('Allow: POST');
        throw new InvalidArgumentException('Método no permitido.');
    }

    require_once __DIR__ . '/common.php';
    if (!hash_equals($_SESSION['cotizacion_status_csrf'], (string) ($_POST['csrf'] ?? ''))) {
        http_response_code(403);
        echo json_encode(['error' => 'La sesión cambió. Recarga la página.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    $estatus = trim((string) ($_POST['estatus'] ?? ''));
    if ($id === false || !array_key_exists($estatus, COTIZACION_ESTATUS)) {
        throw new InvalidArgumentException('Selecciona una cotización y un estatus válidos.');
    }

    require __DIR__ . '/../../config/conexion.php';
    $conexion->set_charset('utf8mb4');
    $conexion->begin_transaction();

    $consulta = $conexion->prepare(
        'SELECT c.id_coti, oc.oportunidad_id, ov.id AS orden_venta_id
         FROM cotizaciones c
         LEFT JOIN oportunidad_cotizaciones oc ON oc.cotizacion_id = c.id_coti
         LEFT JOIN ordenes_venta ov ON ov.cotizacion_id = c.id_coti
         WHERE c.id_coti = ? FOR UPDATE'
    );
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $cotizacion = $consulta->get_result()->fetch_assoc();
    $consulta->close();
    if ($cotizacion === null) {
        throw new OutOfBoundsException('La cotización no existe.');
    }
    if ($cotizacion['orden_venta_id'] !== null && $estatus !== 'aceptada') {
        throw new InvalidArgumentException('Una cotización con orden de venta debe permanecer Aceptada.');
    }

    $actualizar = $conexion->prepare('UPDATE cotizaciones SET cot_status = ? WHERE id_coti = ?');
    $actualizar->bind_param('si', $estatus, $id);
    $actualizar->execute();
    $actualizar->close();

    if ($estatus === 'aceptada' && $cotizacion['oportunidad_id'] !== null) {
        $oportunidadId = (int) $cotizacion['oportunidad_id'];
        $actualizar = $conexion->prepare(
            "UPDATE oportunidades_comerciales SET estatus = 'ganada', updated_at = CURRENT_TIMESTAMP WHERE id = ?"
        );
        $actualizar->bind_param('i', $oportunidadId);
        $actualizar->execute();
        $actualizar->close();
    }

    $conexion->commit();
    echo json_encode([
        'id' => $id,
        'oportunidad_id' => $cotizacion['oportunidad_id'] === null ? null : (int) $cotizacion['oportunidad_id'],
        'estatus' => $estatus,
        'etiqueta' => COTIZACION_ESTATUS[$estatus],
        'oportunidad_ganada' => $estatus === 'aceptada' && $cotizacion['oportunidad_id'] !== null,
        'puede_crear_orden' => $estatus === 'aceptada'
            && $cotizacion['oportunidad_id'] !== null
            && $cotizacion['orden_venta_id'] === null,
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    if (isset($conexion) && $conexion instanceof mysqli) {
        try { $conexion->rollback(); } catch (Throwable) {}
    }
    $status = 500;
    $message = 'No se pudo actualizar el estatus de la cotización.';
    if ($error instanceof InvalidArgumentException) {
        $status = 422;
        $message = $error->getMessage();
    } elseif ($error instanceof OutOfBoundsException) {
        $status = 404;
        $message = $error->getMessage();
    } else {
        error_log('Cotización: cambio de estatus: ' . $error->getMessage());
    }
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}
