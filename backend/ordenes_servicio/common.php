<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('operacion');

require_once __DIR__ . '/../ordenes_venta/common.php';

const OS_TIPOS = [
    'calibracion' => 'Calibración',
    'entrega_equipo' => 'Entrega de equipo del cliente',
    'recoleccion_equipo' => 'Recolección de equipo',
    'recepcion_equipo' => 'Recepción de equipo',
    'mantenimiento_preventivo' => 'Mantenimiento preventivo',
    'ajuste' => 'Ajuste',
    'inspeccion' => 'Inspección',
    'diagnostico' => 'Diagnóstico',
    'correctivo' => 'Mantenimiento correctivo',
];

const OS_ESTATUS = [
    'pendiente' => 'Pendiente',
    'programada' => 'Programada',
    'en_ejecucion' => 'En ejecución',
    'ejecutada' => 'Ejecutada',
    'cancelada' => 'Cancelada',
];

// Convierte una dirección estructurada en una sola línea legible.
function os_address(array $row): string
{
    $parts = array_filter([
        trim((string) ($row['calle'] ?? '')) . ' ' . trim((string) ($row['numero_exterior'] ?? '')),
        trim((string) ($row['numero_interior'] ?? '')) !== '' ? 'Int. ' . trim((string) $row['numero_interior']) : '',
        $row['colonia'] ?? '', $row['localidad'] ?? '', $row['municipio'] ?? '',
        $row['ciudad'] ?? '', $row['estado'] ?? '', $row['codigo_postal'] ?? '', $row['pais'] ?? '',
    ], static fn($value) => trim((string) $value) !== '');
    return implode(', ', array_map('trim', $parts));
}

// Obtiene la fotografía completa de una orden de servicio y sus equipos.
function os_get(mysqli $db, int $id, bool $lock = false): array
{
    if ($lock) {
        $locked = ov_rows($db, 'SELECT id FROM ordenes_servicio WHERE id = ? FOR UPDATE', 'i', [$id]);
        if (!$locked) {
            throw new OutOfBoundsException('Orden de servicio no encontrada.');
        }
    }
    $rows = ov_rows($db, 'SELECT os.*, ov.numero_venta, ov.empresa_id, ov.empresa_nombre,
            ov.contacto_id, ov.direccion_id, ov.oportunidad_id, ov.cotizacion_id,
            o.numero_oportunidad, o.descripcion_corta, o.descripcion_larga,
            c.cot_numero,
            COALESCE(os.entrega_puesto, ce.puesto) AS entrega_puesto_mostrado,
            COALESCE(os.entrega_departamento, ced.nombre, ce.depto) AS entrega_departamento_mostrado
        FROM ordenes_servicio os
        JOIN ordenes_venta ov ON ov.id = os.orden_venta_id
        JOIN oportunidades_comerciales o ON o.id = ov.oportunidad_id
        JOIN cotizaciones c ON c.id_coti = ov.cotizacion_id
        LEFT JOIN contactos ce ON ce.id = COALESCE(os.entrega_contacto_id, ov.contacto_id)
        LEFT JOIN catalogo_departamentos ced ON ced.id = ce.id_departamento
        WHERE os.id = ?', 'i', [$id]);
    if (!$rows) {
        throw new OutOfBoundsException('Orden de servicio no encontrada.');
    }
    $order = $rows[0];
    $companies = ov_rows($db, 'SELECT empresa, razon_social, regimen_capital, rfc, dir_fiscal,
            regimen_fiscal_codigo, regimen_fiscal_descripcion
        FROM empresas WHERE id_e = ?', 'i', [(int) $order['empresa_id']]);
    if ($companies) {
        $company = $companies[0];
        $legalName = trim(implode(' ', array_filter([
            $company['razon_social'] ?: $company['empresa'], $company['regimen_capital'],
        ], static fn($value) => trim((string) $value) !== '')));
        $order['empresa_nombre'] = $legalName;
        $order['fiscal_razon_social'] = $legalName;
        $order['fiscal_rfc'] = trim((string) $company['rfc']);
        $order['fiscal_regimen'] = trim(implode(' · ', array_filter([
            $company['regimen_fiscal_codigo'] ?? '', $company['regimen_fiscal_descripcion'] ?? '',
        ], static fn($value) => trim((string) $value) !== '')));
        $fiscalAddresses = ov_rows($db, 'SELECT * FROM empresa_direcciones
            WHERE empresa_id = ? AND tipo_direccion = \'fiscal\'
            ORDER BY es_principal DESC, id LIMIT 1', 'i', [(int) $order['empresa_id']]);
        $order['fiscal_direccion'] = $fiscalAddresses
            ? os_address($fiscalAddresses[0])
            : trim((string) $company['dir_fiscal']);
        $order['fiscal_direccion_alias'] = $fiscalAddresses
            ? trim((string) ($fiscalAddresses[0]['alias'] ?? ''))
            : '';
    }
    $deliveryContactId = (int) ($order['entrega_contacto_id'] ?: $order['contacto_id']);
    if ($deliveryContactId > 0) {
        $contacts = ov_rows($db, 'SELECT c.nombre, c.celular, c.correo, c.puesto,
                COALESCE(cd.nombre, c.depto) AS departamento
            FROM contactos c LEFT JOIN catalogo_departamentos cd ON cd.id = c.id_departamento
            WHERE c.id = ?', 'i', [$deliveryContactId]);
        if ($contacts) {
            $order['entrega_contacto'] = trim((string) $contacts[0]['nombre']);
            $order['entrega_telefono'] = trim((string) ($contacts[0]['celular'] ?? ''));
            $order['entrega_correo'] = trim((string) ($contacts[0]['correo'] ?? ''));
            $order['entrega_puesto_mostrado'] = trim((string) ($contacts[0]['puesto'] ?? ''));
            $order['entrega_departamento_mostrado'] = trim((string) ($contacts[0]['departamento'] ?? ''));
        }
    }
    $deliveryAddressId = (int) ($order['entrega_direccion_id'] ?: $order['direccion_id']);
    if ($deliveryAddressId > 0) {
        $addresses = ov_rows($db, 'SELECT * FROM empresa_direcciones WHERE id = ? AND empresa_id = ?',
            'ii', [$deliveryAddressId, (int) $order['empresa_id']]);
        if ($addresses) {
            $order['entrega_direccion'] = os_address($addresses[0]);
            $order['entrega_direccion_alias'] = trim((string) ($addresses[0]['alias'] ?? ''));
        }
    }
    $order['equipos'] = ov_rows($db, 'SELECT * FROM orden_servicio_equipos
        WHERE orden_servicio_id = ? ORDER BY posicion, id', 'i', [$id]);
    return $order;
}

// Reúne los datos fiscales, de entrega y equipos disponibles de una orden de venta.
function os_source(mysqli $db, int $saleOrderId): array
{
    $order = ov_get($db, $saleOrderId);
    $companies = ov_rows($db, 'SELECT empresa, razon_social, regimen_capital, rfc, dir_fiscal,
            regimen_fiscal_codigo, regimen_fiscal_descripcion
        FROM empresas WHERE id_e = ?', 'i', [$order['empresa_id']]);
    if (!$companies) {
        throw new OutOfBoundsException('La empresa relacionada ya no existe.');
    }
    $company = $companies[0];
    $fiscalAddresses = ov_rows($db, 'SELECT * FROM empresa_direcciones
        WHERE empresa_id = ? AND tipo_direccion = \'fiscal\'
        ORDER BY es_principal DESC, id ASC LIMIT 1', 'i', [$order['empresa_id']]);
    $fiscalAddress = $fiscalAddresses ? os_address($fiscalAddresses[0]) : trim((string) $company['dir_fiscal']);
    $fiscalAddressAlias = $fiscalAddresses ? trim((string) ($fiscalAddresses[0]['alias'] ?? '')) : '';
    $regime = trim(implode(' · ', array_filter([
        $company['regimen_fiscal_codigo'] ?? '', $company['regimen_fiscal_descripcion'] ?? '',
    ], static fn($value) => trim((string) $value) !== '')));
    $addresses = ov_rows($db, 'SELECT * FROM empresa_direcciones
        WHERE empresa_id = ? ORDER BY es_principal DESC, tipo_direccion, alias, id', 'i', [$order['empresa_id']]);
    foreach ($addresses as &$address) {
        $address['direccion_texto'] = os_address($address);
    }
    unset($address);
    $contacts = ov_rows($db, 'SELECT edc.direccion_id, c.id, c.nombre, c.celular, c.correo,
            c.puesto, COALESCE(cd.nombre, c.depto) AS departamento, edc.es_principal
        FROM empresa_direccion_contactos edc
        JOIN empresa_direcciones d ON d.id = edc.direccion_id
        JOIN contactos c ON c.id = edc.contacto_id
        LEFT JOIN catalogo_departamentos cd ON cd.id = c.id_departamento
        WHERE d.empresa_id = ? AND edc.activo = 1 AND c.activo = 1
        ORDER BY edc.direccion_id, edc.es_principal DESC, c.nombre, c.id', 'i', [$order['empresa_id']]);
    $equipment = ov_rows($db, 'SELECT ee.id, ee.ubicacion, ee.modelo, ee.identificacion,
            ee.numero_serie, ee.unidad, ee.capacidad_maxima, ee.division_real,
            ee.division_verificacion, ee.clase_exactitud, ee.estatus,
            cd.nombre AS descripcion, cm.nombre AS marca
        FROM empresa_equipos ee
        LEFT JOIN catalogo_descripciones_equipo cd ON cd.id = ee.descripcion_id
        LEFT JOIN catalogo_marcas_equipo cm ON cm.id = ee.marca_id
        WHERE ee.empresa_id = ? AND ee.estatus = \'activo\'
        ORDER BY cd.nombre, cm.nombre, ee.modelo, ee.id', 'i', [$order['empresa_id']]);
    return [
        'order' => $order,
        'fiscal' => [
            'razon_social' => trim(implode(' ', array_filter([
                $company['razon_social'] ?: $company['empresa'], $company['regimen_capital'],
            ], static fn($value) => trim((string) $value) !== ''))),
            'rfc' => trim((string) $company['rfc']),
            'regimen' => $regime,
            'direccion' => $fiscalAddress,
            'direccion_alias' => $fiscalAddressAlias,
        ],
        'delivery' => [
            'direccion_id' => $order['direccion_id'],
            'contacto_id' => $order['contacto_id'],
            'contacto' => trim((string) $order['contacto_nombre']),
            'telefono' => trim((string) $order['contacto_telefono']),
            'correo' => trim((string) $order['contacto_correo']),
            'direccion' => trim((string) $order['direccion_texto']),
            'direccion_alias' => trim((string) ($order['direccion_alias'] ?? '')),
        ],
        'addresses' => $addresses,
        'contacts' => $contacts,
        'equipment' => $equipment,
    ];
}
