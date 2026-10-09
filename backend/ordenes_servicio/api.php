<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require_once __DIR__ . '/../helpers/folio_comercial.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$transaction = false;

try {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        throw new InvalidArgumentException('Método no permitido.');
    }
    $input = $method === 'POST' ? $_POST : $_GET;
    $action = (string) ($input['action'] ?? 'list');
    if (!in_array($action, $method === 'GET' ? ['list', 'get', 'source'] : ['save', 'status'], true)) {
        throw new InvalidArgumentException('Acción no válida.');
    }
    if ($method === 'POST' && !hash_equals($_SESSION['ordenes_venta_csrf'], (string) ($input['csrf'] ?? ''))) {
        http_response_code(403);
        echo json_encode(['error' => 'La sesión del formulario cambió. Recarga la página.']);
        exit;
    }

    require __DIR__ . '/../../config/conexion.php';
    $conexion->set_charset('utf8mb4');

    if ($action === 'list') {
        $result = ['data' => ov_rows($conexion, 'SELECT os.id, os.numero_servicio,
            os.fecha_generacion, os.tipo, os.estatus, os.version,
            COALESCE(ct.nombre, os.entrega_contacto) AS entrega_contacto,
            os.updated_at, ov.id AS orden_venta_id, ov.numero_venta,
            ov.empresa_id,
            CONCAT_WS(\' \', COALESCE(NULLIF(TRIM(e.razon_social), \'\'), e.empresa, ov.empresa_nombre), NULLIF(TRIM(e.regimen_capital), \'\')) AS empresa_nombre
            FROM ordenes_servicio os
            JOIN ordenes_venta ov ON ov.id = os.orden_venta_id
            LEFT JOIN empresas e ON e.id_e = ov.empresa_id
            LEFT JOIN contactos ct ON ct.id = COALESCE(os.entrega_contacto_id, ov.contacto_id)
            ORDER BY os.created_at DESC, os.id DESC')];
    } elseif ($action === 'get') {
        $order = os_get($conexion, ov_id($input['id'] ?? null));
        $source = os_source($conexion, (int) $order['orden_venta_id']);
        $result = [
            'order' => $order,
            'addresses' => $source['addresses'],
            'contacts' => $source['contacts'],
            'available_equipment' => $source['equipment'],
        ];
    } elseif ($action === 'source') {
        $result = os_source($conexion, ov_id($input['orden_venta_id'] ?? null));
    } elseif ($action === 'status') {
        $id = ov_id($input['id'] ?? null);
        $status = (string) ($input['estatus'] ?? '');
        if (!isset(OS_ESTATUS[$status])) {
            throw new InvalidArgumentException('El estatus de la orden de servicio no es válido.');
        }
        $conexion->begin_transaction();
        $transaction = true;
        $old = os_get($conexion, $id, true);
        if ((string) ($input['version'] ?? '') !== (string) $old['version']) {
            throw new RuntimeException('Otra edición modificó esta orden. Recarga la página antes de guardar.');
        }
        ov_query($conexion, 'UPDATE ordenes_servicio SET estatus = ?, updated_at = CURRENT_TIMESTAMP,
            version = version + 1 WHERE id = ?', 'si', [$status, $id])->close();
        if ($status === 'completada') {
            os_complete_sale_order($conexion, (int) $old['orden_venta_id']);
        }
        $result = ['order' => os_get($conexion, $id)];
        $conexion->commit();
        $transaction = false;
    } else {
        $id = ov_id($input['id'] ?? '', true);
        $generationDate = ov_date($input['fecha_generacion'] ?? '');
        $type = (string) ($input['tipo'] ?? '');
        $status = (string) ($input['estatus'] ?? 'pendiente');
        if (!isset(OS_TIPOS[$type])) {
            throw new InvalidArgumentException('Selecciona un tipo de servicio válido.');
        }
        if (!isset(OS_ESTATUS[$status])) {
            throw new InvalidArgumentException('El estatus de la orden de servicio no es válido.');
        }
        $deliveryAddressId = ov_id($input['entrega_direccion_id'] ?? null);
        $deliveryContactId = ov_id($input['entrega_contacto_id'] ?? null);
        $instructions = ov_text($input['instrucciones'] ?? '', 60000);
        $notes = ov_text($input['notas'] ?? '', 60000);
        $equipmentIds = json_decode((string) ($input['equipos'] ?? '[]'), true, 16, JSON_THROW_ON_ERROR);
        if (!is_array($equipmentIds) || count($equipmentIds) > 200) {
            throw new InvalidArgumentException('La selección de equipos no es válida.');
        }
        $equipmentIds = array_values(array_unique(array_map(static fn($value) => ov_id($value), $equipmentIds)));

        $conexion->begin_transaction();
        $transaction = true;
        $old = $id ? os_get($conexion, $id, true) : null;
        if ($old && (string) ($input['version'] ?? '') !== (string) $old['version']) {
            throw new RuntimeException('Otra edición modificó esta orden. Recarga la página antes de guardar.');
        }
        $saleOrderId = $old ? (int) $old['orden_venta_id'] : ov_id($input['orden_venta_id'] ?? null);
        ov_get($conexion, $saleOrderId, true);
        $source = os_source($conexion, $saleOrderId);
        $deliveryRows = ov_rows($conexion, 'SELECT d.*, c.nombre, c.celular, c.correo, c.puesto,
                COALESCE(cd.nombre, c.depto) AS departamento
            FROM empresa_direcciones d
            JOIN empresa_direccion_contactos edc ON edc.direccion_id = d.id AND edc.activo = 1
            JOIN contactos c ON c.id = edc.contacto_id AND c.activo = 1
            LEFT JOIN catalogo_departamentos cd ON cd.id = c.id_departamento
            WHERE d.id = ? AND d.empresa_id = ? AND c.id = ?', 'iii',
            [$deliveryAddressId, $source['order']['empresa_id'], $deliveryContactId]);
        if (!$deliveryRows) {
            throw new InvalidArgumentException('El contacto seleccionado no está asociado con la dirección de entrega.');
        }
        $delivery = $deliveryRows[0];
        $deliveryContact = trim((string) $delivery['nombre']);
        $deliveryPosition = trim((string) $delivery['puesto']);
        $deliveryDepartment = trim((string) $delivery['departamento']);
        $deliveryPhone = trim((string) $delivery['celular']);
        $deliveryEmail = trim((string) $delivery['correo']);
        $deliveryAddress = os_address($delivery);

        if ($old) {
            ov_query($conexion, 'UPDATE ordenes_servicio SET fecha_generacion = ?, tipo = ?,
                estatus = ?, entrega_direccion_id = ?, entrega_contacto_id = ?,
                entrega_contacto = ?, entrega_puesto = ?, entrega_departamento = ?,
                entrega_telefono = ?, entrega_correo = ?,
                entrega_direccion = ?, instrucciones = ?, notas = ?,
                updated_at = CURRENT_TIMESTAMP, version = version + 1 WHERE id = ?', 'sssiissssssssi',
                [$generationDate, $type, $status, $deliveryAddressId, $deliveryContactId,
                $deliveryContact, $deliveryPosition, $deliveryDepartment, $deliveryPhone, $deliveryEmail,
                $deliveryAddress, $instructions, $notes, $id])->close();
        } else {
            $number = reservarFolioComercial($conexion, 'OS', $generationDate);
            ov_query($conexion, 'INSERT INTO ordenes_servicio
                (numero_servicio, orden_venta_id, fecha_generacion, tipo, estatus,
                 fiscal_razon_social, fiscal_rfc, fiscal_regimen, fiscal_direccion,
                 entrega_direccion_id, entrega_contacto_id,
                 entrega_contacto, entrega_puesto, entrega_departamento,
                 entrega_telefono, entrega_correo, entrega_direccion,
                 instrucciones, notas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 'sisssssssiissssssss',
                [$number, $saleOrderId, $generationDate, $type, $status,
                $source['fiscal']['razon_social'], $source['fiscal']['rfc'],
                $source['fiscal']['regimen'], $source['fiscal']['direccion'],
                $deliveryAddressId, $deliveryContactId,
                $deliveryContact, $deliveryPosition, $deliveryDepartment,
                $deliveryPhone, $deliveryEmail, $deliveryAddress,
                $instructions, $notes])->close();
            $id = (int) $conexion->insert_id;
        }

        ov_query($conexion, 'DELETE FROM orden_servicio_equipos WHERE orden_servicio_id = ?', 'i', [$id])->close();
        $position = 0;
        foreach ($equipmentIds as $equipmentId) {
            $rows = ov_rows($conexion, 'SELECT ee.id, ee.ubicacion, ee.modelo, ee.identificacion,
                    ee.numero_serie, ee.unidad, ee.capacidad_maxima, ee.division_real,
                    ee.division_verificacion, ee.clase_exactitud,
                    cd.nombre AS descripcion, cm.nombre AS marca
                FROM empresa_equipos ee
                LEFT JOIN catalogo_descripciones_equipo cd ON cd.id = ee.descripcion_id
                LEFT JOIN catalogo_marcas_equipo cm ON cm.id = ee.marca_id
                WHERE ee.id = ? AND ee.empresa_id = ?', 'ii', [$equipmentId, $source['order']['empresa_id']]);
            if (!$rows) {
                throw new InvalidArgumentException('Uno de los equipos no pertenece a la empresa de la orden.');
            }
            $equipment = $rows[0];
            $position++;
            ov_query($conexion, 'INSERT INTO orden_servicio_equipos
                (orden_servicio_id, empresa_equipo_id, posicion, descripcion, marca, modelo,
                 identificacion, numero_serie, ubicacion, unidad, capacidad_maxima,
                 division_real, division_verificacion, clase_exactitud)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 'iiisssssssddds',
                [$id, $equipmentId, $position, $equipment['descripcion'] ?: 'Equipo',
                $equipment['marca'], $equipment['modelo'], $equipment['identificacion'],
                $equipment['numero_serie'], $equipment['ubicacion'], $equipment['unidad'],
                $equipment['capacidad_maxima'], $equipment['division_real'],
                $equipment['division_verificacion'], $equipment['clase_exactitud']])->close();
        }
        ov_query($conexion, 'UPDATE ordenes_venta SET updated_at = CURRENT_TIMESTAMP,
            version = version + 1 WHERE id = ?', 'i', [$saleOrderId])->close();
        if ($status === 'completada') {
            os_complete_sale_order($conexion, $saleOrderId);
        }
        $result = ['order' => os_get($conexion, $id)];
        $conexion->commit();
        $transaction = false;
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    if ($transaction && isset($conexion)) {
        $conexion->rollback();
    }
    $status = 500;
    $message = 'No se pudo completar la operación. Intenta nuevamente.';
    if ($error instanceof JsonException || $error instanceof InvalidArgumentException) {
        $status = 422;
        $message = $error instanceof JsonException ? 'La selección de equipos no es válida.' : $error->getMessage();
    } elseif ($error instanceof OutOfBoundsException) {
        $status = 404;
        $message = $error->getMessage();
    } elseif ($error instanceof RuntimeException) {
        $status = 409;
        $message = $error->getMessage();
    } elseif ($error instanceof mysqli_sql_exception) {
        error_log('Órdenes de servicio: error SQL ' . $error->getCode());
    } else {
        error_log('Órdenes de servicio: ' . get_class($error) . ' en línea ' . $error->getLine() . ': ' . $error->getMessage());
    }
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}
