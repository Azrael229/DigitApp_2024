<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('comercial');

date_default_timezone_set('America/Mexico_City');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['ordenes_venta_csrf'])) {
    $_SESSION['ordenes_venta_csrf'] = bin2hex(random_bytes(32));
}

const OV_ESTATUS = [
    'pendiente' => 'Pendiente de planificación',
    'planificacion' => 'En planificación',
    'en_ejecucion' => 'En ejecución',
    'parcial' => 'Parcialmente atendida',
    'completada' => 'Completada',
    'cancelada' => 'Cancelada',
];

function ov_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function ov_query(mysqli $db, string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $statement = $db->prepare($sql);
    if ($types !== '') {
        if (strlen($types) !== count($params)) {
            throw new LogicException('La consulta interna declaró ' . strlen($types)
                . ' tipos para ' . count($params) . ' valores: ' . substr(preg_replace('/\s+/', ' ', $sql), 0, 90));
        }
        $statement->bind_param($types, ...$params);
    }
    $statement->execute();
    return $statement;
}

function ov_rows(mysqli $db, string $sql, string $types = '', array $params = []): array
{
    $statement = ov_query($db, $sql, $types, $params);
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $rows;
}

function ov_id($value, bool $optional = false): ?int
{
    if ($optional && ($value === null || $value === '')) {
        return null;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($id === false) {
        throw new InvalidArgumentException('Identificador no válido.');
    }
    return $id;
}

function ov_text($value, int $maxBytes, bool $required = false): ?string
{
    $text = trim((string) $value);
    if ($required && $text === '') {
        throw new InvalidArgumentException('Completa los campos obligatorios.');
    }
    if (strlen($text) > $maxBytes) {
        throw new InvalidArgumentException('Uno de los textos excede la longitud permitida.');
    }
    return $text === '' ? null : $text;
}

function ov_date($value): string
{
    $date = trim((string) $value);
    $parsed = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
    $errors = DateTimeImmutable::getLastErrors();
    if ($parsed === false || ($errors !== false && ($errors['warning_count'] || $errors['error_count']))
        || $parsed->format('Y-m-d') !== $date) {
        throw new InvalidArgumentException('La fecha de generación no es válida.');
    }
    return $date;
}

function ov_datetime($value): ?string
{
    $date = trim((string) $value);
    if ($date === '') {
        return null;
    }
    foreach (['!Y-m-d\\TH:i', '!Y-m-d H:i:s'] as $format) {
        $parsed = DateTimeImmutable::createFromFormat($format, $date);
        $errors = DateTimeImmutable::getLastErrors();
        if ($parsed !== false && ($errors === false || (!$errors['warning_count'] && !$errors['error_count']))) {
            return $parsed->format('Y-m-d H:i:s');
        }
    }
    throw new InvalidArgumentException('La fecha programada no es válida.');
}

function ov_address(array $row): string
{
    $parts = array_filter([
        trim((string) ($row['calle'] ?? '')) . ' ' . trim((string) ($row['numero_exterior'] ?? '')),
        trim((string) ($row['numero_interior'] ?? '')) !== '' ? 'Int. ' . trim((string) $row['numero_interior']) : '',
        $row['colonia'] ?? '', $row['localidad'] ?? '', $row['municipio'] ?? '', $row['ciudad'] ?? '',
        $row['estado'] ?? '', trim((string) ($row['codigo_postal'] ?? '')) !== '' ? 'C.P. ' . trim((string) $row['codigo_postal']) : '',
        $row['pais'] ?? '', $row['entre_calles'] ?? '', $row['referencia'] ?? '',
    ], static fn($value) => trim((string) $value) !== '');
    $address = implode(', ', array_map('trim', $parts));
    return $address !== '' ? $address : trim((string) ($row['direccion_original'] ?? ''));
}

function ov_company_contacts(mysqli $db, int $companyId): array
{
    return ov_rows($db, 'SELECT c.id, c.nombre, c.celular, c.correo, c.puesto,
            COALESCE(cd.nombre, c.depto) AS departamento, ec.es_principal
        FROM empresa_contactos ec
        JOIN contactos c ON c.id = ec.id_contacto
        LEFT JOIN catalogo_departamentos cd ON cd.id = c.id_departamento
        WHERE ec.id_empresa = ? AND ec.activo = 1 AND c.activo = 1
        ORDER BY ec.es_principal DESC, c.nombre, c.id', 'i', [$companyId]);
}

function ov_company_addresses(mysqli $db, int $companyId): array
{
    $addresses = ov_rows($db, 'SELECT id, tipo_direccion, alias, es_principal, calle,
            numero_exterior, numero_interior, colonia, localidad, municipio, ciudad,
            estado, codigo_postal, pais, entre_calles, referencia, direccion_original
        FROM empresa_direcciones WHERE empresa_id = ?
        ORDER BY es_principal DESC, tipo_direccion, alias, id', 'i', [$companyId]);
    foreach ($addresses as &$address) {
        $address['direccion_texto'] = ov_address($address);
    }
    unset($address);
    return $addresses;
}

function ov_get(mysqli $db, int $id, bool $lock = false): array
{
    if ($lock) {
        $locked = ov_rows($db, 'SELECT id FROM ordenes_venta WHERE id = ? FOR UPDATE', 'i', [$id]);
        if (!$locked) {
            throw new OutOfBoundsException('Orden de venta no encontrada.');
        }
    }
    $rows = ov_rows($db, 'SELECT ov.*, o.numero_oportunidad, o.descripcion_corta, o.descripcion_larga,
            c.cot_numero
        FROM ordenes_venta ov
        JOIN oportunidades_comerciales o ON o.id = ov.oportunidad_id
        JOIN cotizaciones c ON c.id_coti = ov.cotizacion_id
        WHERE ov.id = ?', 'i', [$id]);
    if (!$rows) {
        throw new OutOfBoundsException('Orden de venta no encontrada.');
    }
    $order = $rows[0];
    $companies = ov_rows($db, 'SELECT empresa, razon_social, regimen_capital FROM empresas WHERE id_e = ?', 'i', [(int) $order['empresa_id']]);
    if ($companies) {
        $order['empresa_nombre'] = trim(implode(' ', array_filter([
            $companies[0]['razon_social'] ?: $companies[0]['empresa'], $companies[0]['regimen_capital'],
        ], static fn($value) => trim((string) $value) !== '')));
    }
    if ($order['contacto_id'] !== null) {
        $contacts = ov_rows($db, 'SELECT nombre, celular, correo FROM contactos WHERE id = ?',
            'i', [(int) $order['contacto_id']]);
        if ($contacts) {
            $order['contacto_nombre'] = trim((string) $contacts[0]['nombre']);
            $order['contacto_telefono'] = trim((string) ($contacts[0]['celular'] ?? ''));
            $order['contacto_correo'] = trim((string) ($contacts[0]['correo'] ?? ''));
        }
    }
    if ($order['direccion_id'] !== null) {
        $addresses = ov_company_addresses($db, (int) $order['empresa_id']);
        foreach ($addresses as $address) {
            if ((int) $address['id'] === (int) $order['direccion_id']) {
                $order['direccion_texto'] = $address['direccion_texto'];
                break;
            }
        }
    }
    $order['conceptos'] = ov_rows($db, 'SELECT * FROM orden_venta_conceptos
        WHERE orden_venta_id = ? ORDER BY posicion, id', 'i', [$id]);
    $order['seguimiento'] = ov_rows($db, 'SELECT * FROM orden_venta_seguimiento
        WHERE orden_venta_id = ? ORDER BY created_at DESC, id DESC', 'i', [$id]);
    $order['ordenes_servicio'] = ov_rows($db, 'SELECT id, numero_servicio, fecha_generacion,
            tipo, estatus, created_at, updated_at
        FROM ordenes_servicio
        WHERE orden_venta_id = ?
        ORDER BY fecha_generacion DESC, id DESC', 'i', [$id]);
    return $order;
}

function ov_source(mysqli $db, int $opportunityId, int $quoteId, bool $lock = false): array
{
    $suffix = $lock ? ' FOR UPDATE' : '';
    $rows = ov_rows($db, 'SELECT o.id AS oportunidad_id, o.numero_oportunidad,
        o.descripcion_corta, o.descripcion_larga, o.estatus AS oportunidad_estatus,
        o.empresa_id, o.contacto_id, o.direccion_id, o.importe AS oportunidad_importe,
        e.empresa, c.nombre AS contacto, c.celular, c.correo,
        q.id_coti AS cotizacion_id, q.cot_numero, q.cot_status, q.cot_subtotal,
        q.cot_contacto, q.cot_telefono, q.cot_correo, q.cot_direccion,
        d.calle, d.numero_exterior, d.numero_interior, d.colonia, d.ciudad,
        d.estado, d.codigo_postal, d.pais
        FROM oportunidades_comerciales o
        JOIN oportunidad_cotizaciones oc ON oc.oportunidad_id = o.id
        JOIN cotizaciones q ON q.id_coti = oc.cotizacion_id AND q.empresa_id = o.empresa_id
        JOIN empresas e ON e.id_e = o.empresa_id
        LEFT JOIN contactos c ON c.id = o.contacto_id
        LEFT JOIN empresa_direcciones d ON d.id = o.direccion_id AND d.empresa_id = o.empresa_id
        WHERE o.id = ? AND q.id_coti = ?' . $suffix, 'ii', [$opportunityId, $quoteId]);
    if (!$rows) {
        throw new InvalidArgumentException('La cotización no pertenece a la oportunidad seleccionada.');
    }
    return $rows[0];
}
