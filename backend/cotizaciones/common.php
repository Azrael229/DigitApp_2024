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

// Mantiene la oportunidad alineada con la cotización relacionada más avanzada.
function sincronizarEstatusOportunidadCotizaciones(mysqli $conexion, int $oportunidadId): ?string
{
    $consulta = $conexion->prepare('SELECT c.cot_status FROM oportunidad_cotizaciones oc
        JOIN cotizaciones c ON c.id_coti = oc.cotizacion_id WHERE oc.oportunidad_id = ?');
    $consulta->bind_param('i', $oportunidadId);
    $consulta->execute();
    $filas = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
    $consulta->close();
    $estados = array_map(static fn(array $fila): string => cotizacionEstatusClave($fila['cot_status'] ?? ''), $filas);
    if ($estados === []) {
        $estatusOportunidad = 'preparacion';
    } elseif (in_array('aceptada', $estados, true)) {
        $estatusOportunidad = 'ganada';
    } elseif (in_array('enviada', $estados, true)) {
        $estatusOportunidad = 'negociacion';
    } elseif (in_array('preparacion', $estados, true)) {
        $estatusOportunidad = 'cotizada';
    } elseif (count(array_unique($estados)) === 1 && $estados[0] === 'cancelada') {
        $estatusOportunidad = 'cancelada';
    } else {
        $estatusOportunidad = 'perdida';
    }
    $actualizar = $conexion->prepare('UPDATE oportunidades_comerciales
        SET estatus = ?, updated_at = CURRENT_TIMESTAMP, version = version + 1 WHERE id = ? AND estatus <> ?');
    $actualizar->bind_param('sis', $estatusOportunidad, $oportunidadId, $estatusOportunidad);
    $actualizar->execute();
    $actualizar->close();
    return $estatusOportunidad;
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

// Enumera los datos que todavía impiden generar el PDF de una cotización en preparación.
function cotizacionPendientesParaPdf(array $cotizacion, array $partidas): array
{
    $pendientes = [];
    $fechaValida = static function ($valor): bool {
        $texto = trim((string) $valor);
        $fecha = DateTimeImmutable::createFromFormat('!Y-m-d', $texto);
        $errores = DateTimeImmutable::getLastErrors();
        return $fecha !== false && ($errores === false
            || ($errores['warning_count'] === 0 && $errores['error_count'] === 0));
    };
    if (!$fechaValida($cotizacion['cot_fecha'] ?? null)
        || !$fechaValida($cotizacion['cot_vigencia'] ?? null)
        || (string) $cotizacion['cot_vigencia'] < (string) $cotizacion['cot_fecha']) {
        $pendientes[] = 'fecha y vigencia válidas';
    }
    $entrega = (string) ($cotizacion['cot_tiempo_entrega_tipo'] ?? '');
    if (!in_array($entrega, ['inmediato', 'dias_habiles', 'semanas'], true)
        || ($entrega !== 'inmediato' && (int) ($cotizacion['cot_tiempo_entrega_cantidad'] ?? 0) < 1)) {
        $pendientes[] = 'tiempo de entrega';
    }
    if (!in_array((string) ($cotizacion['cot_condicion_entrega'] ?? ''),
        ['instalaciones_cliente', 'paqueteria', 'instalaciones_servicom'], true)) {
        $pendientes[] = 'condición de entrega';
    }
    $pago = (string) ($cotizacion['cot_condicion_pago_tipo'] ?? '');
    $pagoCompleto = in_array($pago, ['anticipado', 'anticipado_total'], true)
        || ($pago === 'anticipo_saldo'
            && (float) ($cotizacion['cot_pago_anticipo_porcentaje'] ?? 0) > 0
            && (float) ($cotizacion['cot_pago_anticipo_porcentaje'] ?? 0) < 100
            && in_array((string) ($cotizacion['cot_pago_saldo_momento'] ?? ''),
                ['al_finalizar', 'contra_aviso_entrega'], true))
        || ($pago === 'credito' && (int) ($cotizacion['cot_pago_credito_dias'] ?? 0) > 0);
    if (!$pagoCompleto) {
        $pendientes[] = 'condición de pago';
    }
    $garantia = (string) ($cotizacion['cot_garantia_tipo'] ?? '');
    $garantiaCompleta = $garantia === 'sin_garantia'
        || (in_array($garantia, ['producto', 'mano_obra'], true)
            && (int) ($cotizacion['cot_garantia_vigencia'] ?? 0) > 0
            && in_array((string) ($cotizacion['cot_garantia_unidad'] ?? ''), ['dias', 'meses'], true));
    if (!$garantiaCompleta) {
        $pendientes[] = 'garantía';
    }
    if (!in_array((string) ($cotizacion['cot_costos_envio'] ?? ''), ['incluye', 'no_incluye'], true)) {
        $pendientes[] = 'costos de envío';
    }
    if ($partidas === []) {
        $pendientes[] = 'al menos una partida';
    } else {
        foreach ($partidas as $partida) {
            if ((float) ($partida['cantidad'] ?? 0) <= 0
                || trim((string) ($partida['unidad'] ?? '')) === ''
                || trim((string) ($partida['descripcion'] ?? '')) === ''
                || $partida['valor_unitario'] === null
                || (float) $partida['valor_unitario'] < 0
                || $partida['importe'] === null) {
                $pendientes[] = 'partidas completas';
                break;
            }
            $cantidadSecundaria = $partida['cantidad_secundaria'] ?? null;
            $usaSegundoFactor = array_key_exists('unidad_secundaria', $partida)
                && $partida['unidad_secundaria'] !== null;
            if ($usaSegundoFactor && ((float) ($partida['cantidad_secundaria'] ?? 0) <= 0
                || trim((string) ($partida['unidad_secundaria'] ?? '')) === '')) {
                $pendientes[] = 'partidas completas';
                break;
            }
        }
    }
    return array_values(array_unique($pendientes));
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
                CONCAT_WS(\' \', COALESCE(NULLIF(TRIM(e.razon_social), \'\'), e.empresa), NULLIF(TRIM(e.regimen_capital), \'\')) AS empresa_actual,
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
        'SELECT id, posicion, catalogo_item_id, codigo_catalogo, tipo_catalogo,
                cantidad, unidad, cantidad_secundaria, unidad_secundaria,
                descripcion, valor_unitario, importe_bruto, descuento_tipo,
                descuento_valor, descuento_importe, importe
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
