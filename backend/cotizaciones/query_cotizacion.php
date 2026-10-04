<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require __DIR__ . '/../../config/conexion.php';
require_once __DIR__ . '/common.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if ($id === false || $id === null) {
    http_response_code(422);
    echo json_encode(['error' => 'La cotización no es válida.'], JSON_UNESCAPED_UNICODE);
    $conexion->close();
    exit;
}

$conexion->set_charset('utf8mb4');
$cotizacion = cotizacionDetalle($conexion, (int) $id);
$conexion->close();
if ($cotizacion === null) {
    http_response_code(404);
    echo json_encode(['error' => 'La cotización no existe.'], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['cotizacion' => $cotizacion], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
