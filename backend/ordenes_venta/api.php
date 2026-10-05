<?php
declare(strict_types=1);

require __DIR__ . '/common.php';
require_once __DIR__ . '/../cotizaciones/common.php';
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
    $reads = ['list', 'get', 'sources', 'equipment'];
    $writes = ['save', 'followup'];
    if (!in_array($action, $method === 'GET' ? $reads : $writes, true)) {
        throw new InvalidArgumentException('Acción no válida.');
    }
    if ($method === 'POST' && !hash_equals($_SESSION['ordenes_venta_csrf'], (string) ($input['csrf'] ?? ''))) {
        http_response_code(403);
        echo json_encode(['error' => 'La sesión del formulario cambió. Recarga la página.']);
        exit;
    }

    require __DIR__ . '/../../config/conexion.php';
    $conexion->set_charset('utf8mb4');
    $result = [];

    if ($action === 'list') {
        $result['data'] = ov_rows($conexion, 'SELECT ov.*, o.numero_oportunidad, c.cot_numero
            FROM ordenes_venta ov
            JOIN oportunidades_comerciales o ON o.id = ov.oportunidad_id
            JOIN cotizaciones c ON c.id_coti = ov.cotizacion_id
            ORDER BY ov.created_at DESC, ov.id DESC');
    } elseif ($action === 'get') {
        $result['order'] = ov_get($conexion, ov_id($input['id'] ?? null));
    } elseif ($action === 'sources') {
        $sources = ov_rows($conexion, 'SELECT o.id AS oportunidad_id, o.numero_oportunidad,
            o.descripcion_corta, o.descripcion_larga, o.estatus AS oportunidad_estatus,
            o.empresa_id, o.contacto_id, o.direccion_id, e.empresa,
            c.nombre AS contacto, c.celular, c.correo,
            q.id_coti AS cotizacion_id, q.cot_numero, q.cot_status,
            q.cot_contacto, q.cot_telefono, q.cot_correo, q.cot_direccion,
            CASE WHEN q.cot_subtotal > 0 THEN q.cot_subtotal ELSE o.importe END AS importe_sin_iva,
            ov.id AS orden_id,
            d.calle, d.numero_exterior, d.numero_interior, d.colonia, d.ciudad,
            d.estado, d.codigo_postal, d.pais
            FROM oportunidades_comerciales o
            JOIN oportunidad_cotizaciones oc ON oc.oportunidad_id = o.id
            JOIN cotizaciones q ON q.id_coti = oc.cotizacion_id
            JOIN empresas e ON e.id_e = o.empresa_id
            LEFT JOIN contactos c ON c.id = o.contacto_id
            LEFT JOIN empresa_direcciones d ON d.id = o.direccion_id
            LEFT JOIN ordenes_venta ov ON ov.cotizacion_id = q.id_coti
            ORDER BY q.id_coti DESC, o.id DESC');
        foreach ($sources as &$source) {
            $source['eligible'] = $source['oportunidad_estatus'] === 'ganada'
                && cotizacionEstatusClave($source['cot_status']) === 'aceptada'
                && $source['orden_id'] === null;
        }
        unset($source);
        $result['sources'] = $sources;
    } elseif ($action === 'equipment') {
        $opportunityId = ov_id($input['oportunidad_id'] ?? null);
        $quoteId = ov_id($input['cotizacion_id'] ?? null);
        $source = ov_source($conexion, $opportunityId, $quoteId);
        $result['source'] = $source;
        $result['inventory'] = ov_rows($conexion, 'SELECT ee.id, ee.ubicacion, ee.modelo, ee.identificacion,
            ee.numero_serie, ee.unidad, ee.capacidad_maxima, ee.division_real,
            ee.division_verificacion, cd.nombre AS descripcion, cm.nombre AS marca
            FROM empresa_equipos ee
            LEFT JOIN catalogo_descripciones_equipo cd ON cd.id = ee.descripcion_id
            LEFT JOIN catalogo_marcas_equipo cm ON cm.id = ee.marca_id
            WHERE ee.empresa_id = ? AND ee.estatus = \'activo\'
            ORDER BY cd.nombre, cm.nombre, ee.modelo, ee.id', 'i', [$source['empresa_id']]);
        $result['quote_items'] = ov_rows($conexion, 'SELECT id, posicion, cantidad, unidad, descripcion
            FROM cotizacion_partidas WHERE cotizacion_id = ? ORDER BY posicion', 'i', [$quoteId]);
    } elseif ($action === 'followup') {
        $conexion->begin_transaction();
        $transaction = true;
        $id = ov_id($input['id'] ?? null);
        ov_get($conexion, $id, true);
        $note = ov_text($input['nota'] ?? '', 20000, true);
        ov_query($conexion, 'INSERT INTO orden_venta_seguimiento (orden_venta_id, tipo, nota)
            VALUES (?, \'nota\', ?)', 'is', [$id, $note])->close();
        ov_query($conexion, 'UPDATE ordenes_venta SET updated_at = CURRENT_TIMESTAMP,
            version = version + 1 WHERE id = ?', 'i', [$id])->close();
        $result['order'] = ov_get($conexion, $id);
        $conexion->commit();
        $transaction = false;
    } else {
        $conexion->begin_transaction();
        $transaction = true;
        $id = ov_id($input['id'] ?? '', true);
        $old = $id ? ov_get($conexion, $id, true) : null;
        if ($old && (string) ($input['version'] ?? '') !== (string) $old['version']) {
            throw new RuntimeException('Otra edición modificó esta orden. Recarga la página antes de guardar.');
        }

        $opportunityId = $old ? (int) $old['oportunidad_id'] : ov_id($input['oportunidad_id'] ?? null);
        $quoteId = $old ? (int) $old['cotizacion_id'] : ov_id($input['cotizacion_id'] ?? null);
        $source = ov_source($conexion, $opportunityId, $quoteId, true);
        if (!$old && $source['oportunidad_estatus'] !== 'ganada') {
            throw new InvalidArgumentException('La oportunidad debe estar marcada como Ganada.');
        }
        if (!$old && cotizacionEstatusClave($source['cot_status']) !== 'aceptada') {
            throw new InvalidArgumentException('La cotización debe estar marcada como Aceptada.');
        }
        if (!$old && ov_rows($conexion, 'SELECT id FROM ordenes_venta WHERE cotizacion_id = ? FOR UPDATE', 'i', [$quoteId])) {
            throw new RuntimeException('Esta cotización ya tiene una orden de venta.');
        }

        $generationDate = ov_date($input['fecha_generacion'] ?? '');
        $scheduledDate = array_key_exists('fecha_programada', $input)
            ? ov_datetime($input['fecha_programada'])
            : ($old['fecha_programada'] ?? null);
        $status = (string) ($input['estatus'] ?? 'pendiente');
        if (!isset(OV_ESTATUS[$status])) {
            throw new InvalidArgumentException('El estatus de la orden no es válido.');
        }
        $instructions = array_key_exists('instrucciones', $input)
            ? ov_text($input['instrucciones'], 60000)
            : ($old['instrucciones'] ?? null);
        $notes = ov_text($input['notas'] ?? '', 60000);
        $followup = ov_text($input['seguimiento'] ?? '', 20000);
        $items = null;
        if (array_key_exists('conceptos', $input)) {
            $items = json_decode((string) $input['conceptos'], true, 32, JSON_THROW_ON_ERROR);
            if (!is_array($items) || count($items) > 200) {
                throw new InvalidArgumentException('La selección de equipos o conceptos no es válida.');
            }
        }

        if ($old) {
            ov_query($conexion, 'UPDATE ordenes_venta SET fecha_generacion = ?, fecha_programada = ?,
                estatus = ?, instrucciones = ?, notas = ?, updated_at = CURRENT_TIMESTAMP,
                version = version + 1 WHERE id = ?', 'sssssi',
                [$generationDate, $scheduledDate, $status, $instructions, $notes, $id])->close();
            if ($items !== null) {
                ov_query($conexion, 'DELETE FROM orden_venta_conceptos WHERE orden_venta_id = ?', 'i', [$id])->close();
            }
            if ($old['estatus'] !== $status) {
                $statusNote = 'Cambio de ' . (OV_ESTATUS[$old['estatus']] ?? $old['estatus']) . ' a ' . OV_ESTATUS[$status] . '.';
                ov_query($conexion, 'INSERT INTO orden_venta_seguimiento
                    (orden_venta_id, tipo, estatus, nota) VALUES (?, \'estatus\', ?, ?)',
                    'iss', [$id, $status, $statusNote])->close();
            }
        } else {
            $number = reservarFolioComercial($conexion, 'OV', $generationDate);
            $companyName = trim((string) $source['empresa']);
            $contactName = trim((string) ($source['cot_contacto'] ?: $source['contacto']));
            $contactEmail = trim((string) ($source['cot_correo'] ?: $source['correo']));
            $contactPhone = trim((string) ($source['cot_telefono'] ?: $source['celular']));
            $address = ov_address($source);
            $amount = (float) $source['cot_subtotal'] > 0
                ? number_format((float) $source['cot_subtotal'], 2, '.', '')
                : number_format((float) $source['oportunidad_importe'], 2, '.', '');
            ov_query($conexion, 'INSERT INTO ordenes_venta
                (numero_venta, fecha_generacion, fecha_programada, oportunidad_id, cotizacion_id,
                 empresa_id, contacto_id, direccion_id, empresa_nombre, contacto_nombre,
                 contacto_correo, contacto_telefono, direccion_texto, importe_sin_iva,
                 estatus, instrucciones, notas)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                'sssiiiiisssssdsss', [$number, $generationDate, $scheduledDate,
                $opportunityId, $quoteId, $source['empresa_id'], $source['contacto_id'],
                $source['direccion_id'], $companyName, $contactName, $contactEmail, $contactPhone,
                $address, $amount, $status, $instructions, $notes])->close();
            $id = (int) $conexion->insert_id;
            ov_query($conexion, 'INSERT INTO orden_venta_seguimiento
                (orden_venta_id, tipo, estatus, nota) VALUES (?, \'creacion\', ?, ?)',
                'iss', [$id, $status, 'Orden de venta creada desde ' . $source['numero_oportunidad'] . ' y ' . $source['cot_numero'] . '.'])->close();
        }

        $position = 0;
        foreach ($items ?? [] as $item) {
            if (!is_array($item)) {
                throw new InvalidArgumentException('La selección de conceptos no es válida.');
            }
            $origin = (string) ($item['origen'] ?? '');
            $sourceId = ov_id($item['id'] ?? null);
            $scope = ov_text($item['alcance'] ?? '', 1000);
            $position++;
            if ($origin === 'inventario') {
                $rows = ov_rows($conexion, 'SELECT ee.*, cd.nombre AS descripcion, cm.nombre AS marca
                    FROM empresa_equipos ee
                    LEFT JOIN catalogo_descripciones_equipo cd ON cd.id = ee.descripcion_id
                    LEFT JOIN catalogo_marcas_equipo cm ON cm.id = ee.marca_id
                    WHERE ee.id = ? AND ee.empresa_id = ?', 'ii', [$sourceId, $source['empresa_id']]);
                if (!$rows) {
                    throw new InvalidArgumentException('Uno de los equipos no pertenece a la empresa.');
                }
                $equipment = $rows[0];
                ov_query($conexion, 'INSERT INTO orden_venta_conceptos
                    (orden_venta_id, posicion, origen, empresa_equipo_id, cantidad, descripcion,
                     marca, modelo, identificacion, numero_serie, ubicacion, unidad,
                     capacidad_maxima, division_real, division_verificacion, alcance_sugerido)
                    VALUES (?, ?, \'inventario\', ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    'iiisssssssssss', [$id, $position, $sourceId, $equipment['descripcion'] ?: 'Equipo',
                    $equipment['marca'], $equipment['modelo'], $equipment['identificacion'],
                    $equipment['numero_serie'], $equipment['ubicacion'], $equipment['unidad'],
                    $equipment['capacidad_maxima'], $equipment['division_real'],
                    $equipment['division_verificacion'], $scope])->close();
            } elseif ($origin === 'venta') {
                $rows = ov_rows($conexion, 'SELECT * FROM cotizacion_partidas WHERE id = ? AND cotizacion_id = ?',
                    'ii', [$sourceId, $quoteId]);
                if (!$rows) {
                    throw new InvalidArgumentException('Una partida no pertenece a la cotización.');
                }
                $quoteItem = $rows[0];
                ov_query($conexion, 'INSERT INTO orden_venta_conceptos
                    (orden_venta_id, posicion, origen, cotizacion_partida_id, cantidad,
                     descripcion, unidad, alcance_sugerido)
                    VALUES (?, ?, \'venta\', ?, ?, ?, ?, ?)', 'iiidsss',
                    [$id, $position, $sourceId, $quoteItem['cantidad'], $quoteItem['descripcion'],
                    $quoteItem['unidad'], $scope])->close();
            } else {
                throw new InvalidArgumentException('El origen de un equipo no es válido.');
            }
        }
        if ($followup !== null) {
            ov_query($conexion, 'INSERT INTO orden_venta_seguimiento
                (orden_venta_id, tipo, nota) VALUES (?, \'nota\', ?)', 'is', [$id, $followup])->close();
        }
        $result['order'] = ov_get($conexion, $id);
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
        $message = $error instanceof JsonException ? 'La selección de conceptos no es válida.' : $error->getMessage();
    } elseif ($error instanceof OutOfBoundsException) {
        $status = 404;
        $message = $error->getMessage();
    } elseif ($error instanceof RuntimeException) {
        $status = 409;
        $message = $error->getMessage();
    } elseif ($error instanceof mysqli_sql_exception) {
        if ($error->getCode() === 1146) {
            $status = 503;
            $message = 'El módulo de órdenes de venta aún no está habilitado en esta base de datos.';
        } else {
            error_log('Órdenes de venta: error SQL ' . $error->getCode());
        }
    } else {
        error_log('Órdenes de venta: ' . get_class($error) . ' en línea ' . $error->getLine()
            . ': ' . $error->getMessage());
    }
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}
