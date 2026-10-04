<?php
declare(strict_types=1);

const COTIZACION_ESTATUS = [
    'preparacion' => 'En preparación',
    'enviada' => 'Enviada',
    'aceptada' => 'Aceptada',
    'rechazada' => 'Rechazada',
    'vencida' => 'Vencida',
    'cancelada' => 'Cancelada',
];

// Convierte estados históricos con HTML en una clave estructurada conocida.
function cotizacionEstatusClave($valor): string
{
    $texto = mb_strtolower(trim(strip_tags((string) $valor)), 'UTF-8');
    if (array_key_exists($texto, COTIZACION_ESTATUS)) {
        return $texto;
    }
    if (str_contains($texto, 'aceptada')) {
        return 'aceptada';
    }
    if (str_contains($texto, 'cancelada')) {
        return 'cancelada';
    }
    if (str_contains($texto, 'enviada')) {
        return 'enviada';
    }
    if (str_contains($texto, 'rechazada')) {
        return 'rechazada';
    }
    if (str_contains($texto, 'vencida')) {
        return 'vencida';
    }
    return 'preparacion';
}

function cotizacionEstatusEtiqueta($valor): string
{
    return COTIZACION_ESTATUS[cotizacionEstatusClave($valor)];
}

// Recupera una cotización con sus partidas, notas y oportunidad vinculada.
function cotizacionDetalle(mysqli $conexion, int $id): ?array
{
    $consulta = $conexion->prepare(
        'SELECT c.*,
                oc.oportunidad_id,
                o.numero_oportunidad
         FROM cotizaciones AS c
         LEFT JOIN oportunidad_cotizaciones AS oc ON oc.cotizacion_id = c.id_coti
         LEFT JOIN oportunidades_comerciales AS o ON o.id = oc.oportunidad_id
         WHERE c.id_coti = ?
         LIMIT 1'
    );
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $cotizacion = $consulta->get_result()->fetch_assoc();
    $consulta->close();
    if ($cotizacion === null) {
        return null;
    }

    $consulta = $conexion->prepare(
        'SELECT id, posicion, cantidad, unidad, descripcion, valor_unitario, importe
         FROM cotizacion_partidas
         WHERE cotizacion_id = ?
         ORDER BY posicion'
    );
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $cotizacion['partidas'] = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
    $consulta->close();

    $consulta = $conexion->prepare(
        'SELECT id, posicion, texto
         FROM cotizacion_notas
         WHERE cotizacion_id = ?
         ORDER BY posicion'
    );
    $consulta->bind_param('i', $id);
    $consulta->execute();
    $cotizacion['notas'] = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
    $consulta->close();

    return $cotizacion;
}
