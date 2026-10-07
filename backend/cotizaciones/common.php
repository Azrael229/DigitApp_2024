<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('comercial');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['cotizacion_status_csrf'])) {
    $_SESSION['cotizacion_status_csrf'] = bin2hex(random_bytes(32));
}

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

// Forma el texto vigente de una dirección maestra de empresa.
function cotizacionDireccionActual(array $direccion): string
{
    $primera = trim(implode(' ', array_filter([
        $direccion['calle'] ?? '',
        trim((string) ($direccion['numero_exterior'] ?? '')) !== '' ? 'No. ' . trim((string) $direccion['numero_exterior']) : '',
        trim((string) ($direccion['numero_interior'] ?? '')) !== '' ? 'Int. ' . trim((string) $direccion['numero_interior']) : '',
    ], static fn($valor) => trim((string) $valor) !== '')));
    $ubicacion = implode(', ', array_filter([
        trim((string) ($direccion['colonia'] ?? '')) !== '' ? 'Col. ' . trim((string) $direccion['colonia']) : '',
        $direccion['localidad'] ?? '', $direccion['municipio'] ?? '', $direccion['ciudad'] ?? '',
        $direccion['estado'] ?? '',
        trim((string) ($direccion['codigo_postal'] ?? '')) !== '' ? 'C.P. ' . trim((string) $direccion['codigo_postal']) : '',
        $direccion['pais'] ?? '',
    ], static fn($valor) => trim((string) $valor) !== ''));
    $texto = implode(', ', array_filter([$primera, $ubicacion, $direccion['entre_calles'] ?? '',
        $direccion['referencia'] ?? ''], static fn($valor) => trim((string) $valor) !== ''));
    return $texto !== '' ? $texto : trim((string) ($direccion['direccion_original'] ?? ''));
}

// Recupera una cotización con sus partidas, notas y oportunidad vinculada.
function cotizacionDetalle(mysqli $conexion, int $id): ?array
{
    $consulta = $conexion->prepare(
        'SELECT c.*,
                oc.oportunidad_id,
                o.numero_oportunidad,
                o.estatus AS oportunidad_estatus,
                ov.id AS orden_venta_id,
                ov.numero_venta,
                e.empresa AS empresa_actual,
                ct.nombre AS contacto_actual,
                ct.celular AS telefono_actual,
                ct.correo AS correo_actual,
                COALESCE(cd.nombre, ct.depto) AS departamento_actual,
                d.id AS direccion_actual_id,
                d.calle, d.numero_exterior, d.numero_interior, d.colonia, d.localidad,
                d.municipio, d.ciudad, d.estado, d.codigo_postal, d.pais,
                d.entre_calles, d.referencia, d.direccion_original
         FROM cotizaciones AS c
         LEFT JOIN empresas AS e ON e.id_e = c.empresa_id
         LEFT JOIN contactos AS ct ON ct.id = c.contacto_id
         LEFT JOIN catalogo_departamentos AS cd ON cd.id = ct.id_departamento
         LEFT JOIN empresa_direcciones AS d ON d.id = c.direccion_id AND d.empresa_id = c.empresa_id
         LEFT JOIN oportunidad_cotizaciones AS oc ON oc.cotizacion_id = c.id_coti
         LEFT JOIN oportunidades_comerciales AS o ON o.id = oc.oportunidad_id
         LEFT JOIN ordenes_venta AS ov ON ov.cotizacion_id = c.id_coti
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
    if ($cotizacion['empresa_actual'] !== null) {
        $cotizacion['cot_empresa'] = $cotizacion['empresa_actual'];
    }
    if ($cotizacion['contacto_actual'] !== null) {
        $cotizacion['cot_contacto'] = $cotizacion['contacto_actual'];
        $cotizacion['cot_telefono'] = $cotizacion['telefono_actual'] ?? '';
        $cotizacion['cot_correo'] = $cotizacion['correo_actual'] ?? '';
        $cotizacion['cot_departamento'] = $cotizacion['departamento_actual'] ?? '';
    }
    if ($cotizacion['direccion_id'] !== null) {
        $cotizacion['cot_direccion'] = cotizacionDireccionActual($cotizacion);
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
