<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/bootstrap.php';

date_default_timezone_set('America/Mexico_City');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
if (empty($_SESSION['catalogo_comercial_csrf'])) {
    $_SESSION['catalogo_comercial_csrf'] = bin2hex(random_bytes(32));
}

const CATALOGO_TIPOS = ['producto', 'servicio'];
const CATALOGO_UNIDADES = ['Pieza', 'Equipo', 'Servicio', 'Hora', 'Día', 'Viaje', 'Kit', 'Renta'];

function catalogo_escape($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function catalogo_query(mysqli $db, string $sql, string $types = '', array $params = []): mysqli_stmt
{
    $statement = $db->prepare($sql);
    if ($types !== '') {
        if (strlen($types) !== count($params)) {
            throw new LogicException('La consulta del catálogo no coincide con sus parámetros.');
        }
        $statement->bind_param($types, ...$params);
    }
    $statement->execute();
    return $statement;
}

function catalogo_rows(mysqli $db, string $sql, string $types = '', array $params = []): array
{
    $statement = catalogo_query($db, $sql, $types, $params);
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $rows;
}

function catalogo_id($value, bool $optional = false): ?int
{
    if ($optional && ($value === null || trim((string) $value) === '')) {
        return null;
    }
    $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($id === false) {
        throw new InvalidArgumentException('El identificador seleccionado no es válido.');
    }
    return (int) $id;
}

function catalogo_text($value, int $max, string $label, bool $required = false): ?string
{
    $text = trim((string) $value);
    if (($required && $text === '') || mb_strlen($text, 'UTF-8') > $max) {
        throw new InvalidArgumentException($label . ' no es válido.');
    }
    return $text === '' ? null : $text;
}

function catalogo_money($value, string $label, bool $required = false): ?string
{
    $text = trim((string) $value);
    if ($text === '') {
        if ($required) {
            throw new InvalidArgumentException('Completa ' . mb_strtolower($label, 'UTF-8') . '.');
        }
        return null;
    }
    if (!preg_match('/^\d{1,12}(?:\.\d{1,2})?$/D', $text)) {
        throw new InvalidArgumentException($label . ' debe ser un importe positivo con hasta dos decimales.');
    }
    return number_format((float) $text, 2, '.', '');
}

function catalogo_exchange_rate($value, bool $required): ?string
{
    $text = trim((string) $value);
    if ($text === '') {
        if ($required) {
            throw new InvalidArgumentException('Captura el tipo de cambio para convertir el costo en dólares.');
        }
        return null;
    }
    if (!preg_match('/^\d{1,8}(?:\.\d{1,6})?$/D', $text) || (float) $text <= 0) {
        throw new InvalidArgumentException('El tipo de cambio debe ser mayor que cero y usar hasta seis decimales.');
    }
    return number_format((float) $text, 6, '.', '');
}

// Calcula el precio con utilidad porcentual como margen o con una cantidad monetaria directa.
function catalogo_sale_price(float $cost, string $utilityType, float $utilityValue): float
{
    if ($utilityType === 'porcentaje') {
        if ($utilityValue >= 100) {
            throw new InvalidArgumentException('El factor de utilidad porcentual debe ser menor que 100%.');
        }
        return round($cost / (1 - $utilityValue / 100), 2);
    }
    return round($cost + $utilityValue, 2);
}

function catalogo_categories(mysqli $db, string $type, bool $activeOnly = true): array
{
    $sql = 'SELECT id, tipo, nombre, clave, activo FROM catalogo_categorias_comerciales WHERE tipo = ?';
    if ($activeOnly) {
        $sql .= ' AND activo = 1';
    }
    return catalogo_rows($db, $sql . ' ORDER BY nombre, id', 's', [$type]);
}

function catalogo_providers(mysqli $db): array
{
    return catalogo_rows($db, 'SELECT id_e AS id,
            CONCAT_WS(\' \', COALESCE(NULLIF(TRIM(razon_social), \'\'), empresa), NULLIF(TRIM(regimen_capital), \'\')) AS nombre
        FROM empresas WHERE rol IN (\'Proveedor\', \'Cliente-Proveedor\')
        ORDER BY nombre, id_e');
}

function catalogo_item(mysqli $db, int $id): ?array
{
    $rows = catalogo_rows($db, 'SELECT i.*, c.nombre AS categoria, c.clave AS categoria_clave,
            CONCAT_WS(\' \', COALESCE(NULLIF(TRIM(e.razon_social), \'\'), e.empresa), NULLIF(TRIM(e.regimen_capital), \'\')) AS proveedor
        FROM catalogo_items_comerciales i
        JOIN catalogo_categorias_comerciales c ON c.id = i.categoria_id
        LEFT JOIN empresas e ON e.id_e = i.proveedor_id
        WHERE i.id = ?', 'i', [$id]);
    if (!$rows) {
        return null;
    }
    $rows[0]['costos_operacion'] = catalogo_rows($db, 'SELECT id, posicion, concepto, importe
        FROM catalogo_item_costos_operacion WHERE catalogo_item_id = ? ORDER BY posicion, id', 'i', [$id]);
    return $rows[0];
}

function catalogo_items(mysqli $db, string $type, bool $activeOnly = false): array
{
    $sql = 'SELECT i.*, c.nombre AS categoria,
            CONCAT_WS(\' \', COALESCE(NULLIF(TRIM(e.razon_social), \'\'), e.empresa), NULLIF(TRIM(e.regimen_capital), \'\')) AS proveedor,
            i.costo_real AS precio_distribuidor
        FROM catalogo_items_comerciales i
        JOIN catalogo_categorias_comerciales c ON c.id = i.categoria_id
        LEFT JOIN empresas e ON e.id_e = i.proveedor_id
        WHERE i.tipo = ?';
    if ($activeOnly) {
        $sql .= ' AND i.activo = 1';
    }
    return catalogo_rows($db, $sql . ' ORDER BY i.codigo, i.id', 's', [$type]);
}

// Genera una clave breve y no repetida para una categoría creada desde el formulario.
function catalogo_category_key(mysqli $db, string $type, string $name): string
{
    $plain = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $name);
    $base = preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $plain));
    $base = substr($base !== '' ? $base : 'CAT', 0, 3);
    for ($suffix = 0; $suffix < 1000; $suffix++) {
        $candidate = $suffix === 0 ? $base : substr($base, 0, max(1, 8 - strlen((string) $suffix))) . $suffix;
        if (!catalogo_rows($db, 'SELECT id FROM catalogo_categorias_comerciales WHERE tipo = ? AND clave = ?',
            'ss', [$type, $candidate])) {
            return $candidate;
        }
    }
    throw new RuntimeException('No fue posible generar la clave de la categoría.');
}
