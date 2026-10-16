<?php
declare(strict_types=1);

require __DIR__ . '/common.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$transaction = false;

// Restringe las altas y ediciones del catálogo al rol administrador.
function catalogo_require_admin(array $user): void
{
    if (($user['rol'] ?? '') !== 'administrador') {
        http_response_code(403);
        echo json_encode(['error' => 'Solo un administrador puede agregar o editar productos y servicios.'], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// Aplica un descuento porcentual o monetario al costo de origen.
function catalogo_discounted_cost(float $cost, string $type, float $value): float
{
    if ($type === 'porcentaje') {
        if ($value > 100) {
            throw new InvalidArgumentException('El descuento no puede exceder 100%.');
        }
        return round($cost * (1 - $value / 100), 2);
    }
    if ($value > $cost) {
        throw new InvalidArgumentException('El descuento monetario no puede exceder el costo.');
    }
    return round($cost - $value, 2);
}

// Valida los conceptos variables que integran el costo operativo de un servicio.
function catalogo_operation_costs(string $json): array
{
    $rows = json_decode($json !== '' ? $json : '[]', true, 50, JSON_THROW_ON_ERROR);
    if (!is_array($rows) || count($rows) > 50) {
        throw new InvalidArgumentException('Los costos de operación no son válidos.');
    }
    $result = [];
    foreach ($rows as $row) {
        $concept = catalogo_text($row['concepto'] ?? '', 120, 'El concepto del costo de operación');
        $amount = catalogo_money($row['importe'] ?? '', 'El importe del costo de operación');
        if ($concept === null && $amount === null) continue;
        if ($concept === null || $amount === null) {
            throw new InvalidArgumentException('Completa el concepto y el importe de cada costo de operación.');
        }
        $plain = strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $concept));
        if (str_contains($plain, 'viatic')) {
            throw new InvalidArgumentException('Los viáticos se cotizan por separado y no forman parte del costo de operación.');
        }
        $result[] = ['concepto' => $concept, 'importe' => $amount];
    }
    return $result;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        throw new InvalidArgumentException('Método no permitido.');
    }
    $input = $method === 'POST' ? $_POST : $_GET;
    $action = (string) ($input['action'] ?? 'form');
    $user = auth_require_login();
    if ($action === 'quote_items') {
        if (!auth_has_permission('comercial', $user)) {
            throw new RuntimeException('No tienes permiso para consultar el catálogo comercial.');
        }
    } else {
        auth_require_permission('productos');
        catalogo_require_admin($user);
    }
    if ($method === 'POST' && !hash_equals($_SESSION['catalogo_comercial_csrf'], (string) ($input['csrf'] ?? ''))) {
        http_response_code(403);
        echo json_encode(['error' => 'La sesión del formulario cambió. Recarga la página.']);
        exit;
    }

    require __DIR__ . '/../../config/conexion.php';
    $conexion->set_charset('utf8mb4');

    if ($method === 'GET' && $action === 'quote_items') {
        $result = ['items' => catalogo_rows($conexion, 'SELECT id, tipo, codigo, descripcion, unidad, precio_publico
            FROM catalogo_items_comerciales WHERE activo = 1 ORDER BY codigo, id')];
    } elseif ($method === 'GET' && $action === 'form') {
        $type = (string) ($input['type'] ?? '');
        if (!in_array($type, CATALOGO_TIPOS, true)) {
            throw new InvalidArgumentException('El tipo de catálogo no es válido.');
        }
        $id = catalogo_id($input['id'] ?? '', true);
        $item = $id ? catalogo_item($conexion, $id) : null;
        if ($id && (!$item || $item['tipo'] !== $type)) {
            throw new OutOfBoundsException('El registro solicitado no existe.');
        }
        $result = ['item' => $item, 'categories' => catalogo_categories($conexion, $type),
            'providers' => catalogo_providers($conexion), 'units' => CATALOGO_UNIDADES];
    } elseif ($method === 'POST' && $action === 'category_add') {
        $type = (string) ($input['type'] ?? '');
        if (!in_array($type, CATALOGO_TIPOS, true)) {
            throw new InvalidArgumentException('El tipo de categoría no es válido.');
        }
        $name = catalogo_text($input['name'] ?? '', 100, 'El nombre de la categoría', true);
        $existing = catalogo_rows($conexion, 'SELECT id, tipo, nombre, clave, activo
            FROM catalogo_categorias_comerciales WHERE tipo = ? AND nombre = ?', 'ss', [$type, $name]);
        if ($existing) {
            if (!(int) $existing[0]['activo']) {
                catalogo_query($conexion, 'UPDATE catalogo_categorias_comerciales SET activo = 1 WHERE id = ?',
                    'i', [(int) $existing[0]['id']])->close();
                $existing[0]['activo'] = 1;
            }
            $result = ['category' => $existing[0]];
        } else {
            $key = catalogo_category_key($conexion, $type, $name);
            catalogo_query($conexion, 'INSERT INTO catalogo_categorias_comerciales (tipo, nombre, clave)
                VALUES (?, ?, ?)', 'sss', [$type, $name, $key])->close();
            $result = ['category' => ['id' => $conexion->insert_id, 'tipo' => $type,
                'nombre' => $name, 'clave' => $key, 'activo' => 1]];
        }
    } elseif ($method === 'POST' && $action === 'save') {
        $type = (string) ($input['type'] ?? '');
        if (!in_array($type, CATALOGO_TIPOS, true)) {
            throw new InvalidArgumentException('El tipo de registro no es válido.');
        }
        $id = catalogo_id($input['id'] ?? '', true);
        $version = catalogo_id($input['version'] ?? '', true);
        $categoryId = catalogo_id($input['category_id'] ?? null);
        $providerId = catalogo_id($input['provider_id'] ?? '', true);
        $description = catalogo_text($input['description'] ?? '', 10000, 'La descripción', true);
        $unit = catalogo_text($input['unit'] ?? '', 30, 'La unidad', true);
        if (!in_array($unit, CATALOGO_UNIDADES, true)) {
            throw new InvalidArgumentException('Selecciona una unidad válida.');
        }
        $publicPrice = (float) catalogo_money($input['public_price'] ?? '', 'El precio público final', true);
        $thirdParty = $type === 'servicio' && (string) ($input['third_party'] ?? '') === '1';
        $discountType = (string) ($input['cost_discount_type'] ?? 'porcentaje');
        if (!in_array($discountType, ['porcentaje', 'monto'], true)) {
            throw new InvalidArgumentException('El tipo de descuento no es válido.');
        }
        $utilityType = (string) ($input['utility_type'] ?? 'porcentaje');
        if (!in_array($utilityType, ['porcentaje', 'monto'], true)) {
            throw new InvalidArgumentException('El tipo de utilidad no es válido.');
        }
        $discount = (float) catalogo_money($input['cost_discount'] ?? '0', 'El descuento', true);
        $utility = (float) catalogo_money($input['utility_value'] ?? '0', 'La utilidad', true);
        $active = (string) ($input['active'] ?? '1') === '0' ? 0 : 1;
        $categories = catalogo_rows($conexion, 'SELECT id, clave FROM catalogo_categorias_comerciales
            WHERE id = ? AND tipo = ? AND activo = 1', 'is', [$categoryId, $type]);
        if (!$categories) {
            throw new InvalidArgumentException('Selecciona una categoría vigente.');
        }
        if ($providerId !== null && !catalogo_rows($conexion, 'SELECT id_e FROM empresas
            WHERE id_e = ? AND rol IN (\'Proveedor\', \'Cliente-Proveedor\')', 'i', [$providerId])) {
            throw new InvalidArgumentException('El proveedor seleccionado no está disponible.');
        }
        if ($thirdParty && $providerId === null) {
            throw new InvalidArgumentException('Selecciona el proveedor del servicio de un tercero.');
        }
        $operationCosts = $type === 'servicio'
            ? catalogo_operation_costs((string) ($input['operation_costs'] ?? '[]')) : [];
        $operationTotal = round(array_sum(array_map(static fn(array $row): float => (float) $row['importe'], $operationCosts)), 2);
        if ($type === 'producto') {
            $currency = (string) ($input['currency'] ?? 'MXN');
            if (!in_array($currency, ['MXN', 'USD'], true)) {
                throw new InvalidArgumentException('Selecciona una moneda válida.');
            }
            $exchangeRate = catalogo_exchange_rate($input['exchange_rate'] ?? '', $currency === 'USD');
            $baseCost = (float) catalogo_money($input['base_cost'] ?? '', 'El costo de compra', true);
            $shippingCost = (float) catalogo_money($input['shipping_cost'] ?? '0', 'El costo de envío', true);
            $thirdPartyCost = null;
            $realCost = catalogo_discounted_cost($baseCost, $discountType, $discount);
            $realCost = round($realCost * ($currency === 'USD' ? (float) $exchangeRate : 1) + $shippingCost, 2);
        } else {
            $currency = 'MXN';
            $exchangeRate = null;
            $utilityType = 'porcentaje';
            $baseCost = 0.0;
            $shippingCost = 0.0;
            if ($thirdParty) {
                $thirdPartyCost = (float) catalogo_money($input['third_party_cost'] ?? '', 'El costo del tercero', true);
                $externalRealCost = catalogo_discounted_cost($thirdPartyCost, $discountType, $discount);
            } else {
                $thirdPartyCost = null;
                $discount = 0.0;
                $externalRealCost = 0.0;
            }
            $realCost = round($externalRealCost + $operationTotal, 2);
        }
        $calculatedPrice = catalogo_sale_price($realCost, $utilityType, $utility);

        $conexion->begin_transaction();
        $transaction = true;
        if ($id) {
            $current = catalogo_rows($conexion, 'SELECT id, tipo, version FROM catalogo_items_comerciales
                WHERE id = ? FOR UPDATE', 'i', [$id]);
            if (!$current || $current[0]['tipo'] !== $type) {
                throw new OutOfBoundsException('El registro solicitado no existe.');
            }
            if ((int) $version !== (int) $current[0]['version']) {
                throw new RuntimeException('Otra edición modificó este registro. Recarga antes de guardar.');
            }
            catalogo_query($conexion, 'UPDATE catalogo_items_comerciales SET categoria_id = ?, proveedor_id = ?,
                descripcion = ?, unidad = ?, moneda_costo = ?, tipo_cambio = ?, costo_base = ?, es_tercero = ?,
                costo_tercero = ?, descuento_costo_tipo = ?, descuento_costo = ?, costo_envio = ?, costo_real = ?,
                costo_operacion_total = ?, utilidad_tipo = ?, utilidad_valor = ?, precio_calculado = ?,
                precio_publico = ?, precio_actualizado_at = CURRENT_TIMESTAMP, activo = ?, version = version + 1
                WHERE id = ?', 'iisssssissssssssssii', [$categoryId, $providerId, $description, $unit, $currency,
                $exchangeRate, $baseCost, $thirdParty ? 1 : 0, $thirdPartyCost, $discountType, $discount,
                $shippingCost, $realCost, $operationTotal, $utilityType, $utility, $calculatedPrice, $publicPrice, $active, $id])->close();
        } else {
            $temporaryCode = 'TMP-' . bin2hex(random_bytes(8));
            catalogo_query($conexion, 'INSERT INTO catalogo_items_comerciales
                (tipo, codigo, categoria_id, proveedor_id, descripcion, unidad, moneda_costo, tipo_cambio,
                 costo_base, es_tercero, costo_tercero, descuento_costo_tipo, descuento_costo, costo_envio, costo_real,
                 costo_operacion_total, utilidad_tipo, utilidad_valor, precio_calculado, precio_publico,
                 precio_actualizado_at, maneja_inventario, activo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)',
                'ssiisssssissssssssssii', [$type, $temporaryCode, $categoryId, $providerId, $description, $unit,
                $currency, $exchangeRate, $baseCost, $thirdParty ? 1 : 0, $thirdPartyCost, $discountType,
                $discount, $shippingCost, $realCost, $operationTotal, $utilityType, $utility, $calculatedPrice, $publicPrice,
                $type === 'producto' ? 1 : 0, $active])->close();
            $id = (int) $conexion->insert_id;
            $prefix = $type === 'producto' ? 'PRD' : 'SRV';
            $code = sprintf('%s-%s-%05d', $prefix, $categories[0]['clave'], $id);
            catalogo_query($conexion, 'UPDATE catalogo_items_comerciales SET codigo = ? WHERE id = ?',
                'si', [$code, $id])->close();
        }
        catalogo_query($conexion, 'DELETE FROM catalogo_item_costos_operacion WHERE catalogo_item_id = ?', 'i', [$id])->close();
        foreach ($operationCosts as $position => $row) {
            catalogo_query($conexion, 'INSERT INTO catalogo_item_costos_operacion
                (catalogo_item_id, posicion, concepto, importe) VALUES (?, ?, ?, ?)', 'iiss',
                [$id, $position + 1, $row['concepto'], $row['importe']])->close();
        }
        $result = ['item' => catalogo_item($conexion, $id)];
        $conexion->commit();
        $transaction = false;
    } else {
        throw new InvalidArgumentException('Acción no válida.');
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    if ($transaction && isset($conexion)) {
        $conexion->rollback();
    }
    $status = 500;
    $message = 'No se pudo completar la operación del catálogo.';
    if ($error instanceof InvalidArgumentException || $error instanceof JsonException) {
        $status = 422;
        $message = $error->getMessage();
    } elseif ($error instanceof OutOfBoundsException) {
        $status = 404;
        $message = $error->getMessage();
    } elseif ($error instanceof RuntimeException) {
        $status = 409;
        $message = $error->getMessage();
    } else {
        error_log('Catálogo comercial: ' . get_class($error) . ' en línea ' . $error->getLine() . ': ' . $error->getMessage());
    }
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}
