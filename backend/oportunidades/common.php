<?php
declare(strict_types=1);

date_default_timezone_set('America/Mexico_City');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['oportunidades_csrf'])) {
    $_SESSION['oportunidades_csrf'] = bin2hex(random_bytes(32));
}

const OP_ESTATUS = [
    'preparacion' => 'En preparación', 'cotizada' => 'Cotizada',
    'negociacion' => 'En negociación', 'ganada' => 'Ganada',
    'perdida' => 'Perdida', 'cancelada' => 'Cancelada',
];

function op_escape($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function op_query(mysqli $db, string $sql, string $types = '', array $params = []): mysqli_stmt {
    $statement = $db->prepare($sql);
    if ($types !== '') {
        $statement->bind_param($types, ...$params);
    }
    $statement->execute();
    return $statement;
}

function op_rows(mysqli $db, string $sql, string $types = '', array $params = []): array {
    $statement = op_query($db, $sql, $types, $params);
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $rows;
}

function op_id($value, bool $optional = false): ?int {
    if ($optional && ($value === null || $value === '')) {
        return null;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 2147483647]]);
    if ($id === false) {
        throw new InvalidArgumentException('Identificador no válido.');
    }
    return $id;
}

function op_amount($value): string {
    $amount = trim((string) $value);
    if (!preg_match('/^\d{1,10}(?:\.\d{1,2})?$/D', $amount) || (float) $amount > 9999999999.99) {
        throw new InvalidArgumentException('El importe debe ser positivo o cero, con un máximo de dos decimales.');
    }
    return number_format((float) $amount, 2, '.', '');
}

function op_get(mysqli $db, int $id, bool $lock = false): array {
    if ($lock) {
        $locked = op_rows($db, 'SELECT id FROM oportunidades_comerciales WHERE id = ? FOR UPDATE', 'i', [$id]);
        if (!$locked) {
            throw new OutOfBoundsException('Oportunidad no encontrada.');
        }
    }
    $rows = op_rows($db, 'SELECT o.*, e.empresa, c.nombre AS contacto,
        d.alias AS direccion_alias, d.tipo_direccion, d.calle, d.numero_exterior, d.numero_interior,
        d.colonia, d.localidad, d.municipio, d.ciudad, d.estado, d.codigo_postal, d.pais,
        d.entre_calles, d.referencia, d.direccion_original, d.enlace_maps
        FROM oportunidades_comerciales o JOIN empresas e ON e.id_e = o.empresa_id
        LEFT JOIN contactos c ON c.id = o.contacto_id
        LEFT JOIN empresa_direcciones d ON d.id = o.direccion_id WHERE o.id = ?', 'i', [$id]);
    if (!$rows) {
        throw new OutOfBoundsException('Oportunidad no encontrada.');
    }
    return $rows[0];
}

function op_relations(mysqli $db, int $company, ?int $contact, ?int $address): void {
    if (!op_rows($db, 'SELECT id_e FROM empresas WHERE id_e = ?', 'i', [$company])) {
        throw new InvalidArgumentException('La empresa seleccionada no existe.');
    }
    if ($contact !== null && !op_rows($db, 'SELECT c.id FROM contactos c JOIN empresa_contactos ec
        ON ec.id_contacto = c.id WHERE c.id = ? AND ec.id_empresa = ? AND ec.activo = 1 AND c.activo = 1', 'ii', [$contact, $company])) {
        throw new InvalidArgumentException('El contacto no está activo o no pertenece a esta empresa.');
    }
    if ($address !== null && !op_rows($db, 'SELECT id FROM empresa_direcciones WHERE id = ? AND empresa_id = ?', 'ii', [$address, $company])) {
        throw new InvalidArgumentException('La dirección no pertenece a esta empresa.');
    }
}
