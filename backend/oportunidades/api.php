<?php
declare(strict_types=1);
require __DIR__ . '/common.php';
require_once __DIR__ . '/../helpers/folio_comercial.php';
require_once __DIR__ . '/../cotizaciones/common.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$transaction = false;

try {
    $method = $_SERVER['REQUEST_METHOD'];
    if (!in_array($method, ['GET', 'POST'], true)) {
        header('Allow: GET, POST');
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido.']);
        exit;
    }
    $input = $method === 'POST' ? $_POST : $_GET;
    $action = (string) ($input['action'] ?? 'list');
    $reads = ['list', 'get', 'companies', 'options', 'quotes'];
    $writes = ['save', 'link', 'unlink'];
    if (!in_array($action, $method === 'GET' ? $reads : $writes, true)) {
        throw new InvalidArgumentException('Acción no válida.');
    }
    if ($method === 'POST' && !hash_equals($_SESSION['oportunidades_csrf'], (string) ($input['csrf'] ?? ''))) {
        http_response_code(403);
        echo json_encode(['error' => 'La sesión del formulario cambió. Recarga la página.']);
        exit;
    }
    require __DIR__ . '/../../config/conexion.php';
    $conexion->set_charset('utf8mb4');
    $result = [];
    if ($action === 'companies') {
        $result['companies'] = op_rows($conexion, 'SELECT id_e AS id, empresa FROM empresas ORDER BY empresa, id_e');
    } elseif ($action === 'options') {
        $company = op_id($input['empresa_id'] ?? null);
        $result['contacts'] = op_rows($conexion, 'SELECT c.id, c.nombre, c.celular, c.correo,
            COALESCE(cd.nombre, c.depto) AS departamento, c.puesto, ec.es_principal
            FROM contactos c JOIN empresa_contactos ec ON ec.id_contacto = c.id
            LEFT JOIN catalogo_departamentos cd ON cd.id = c.id_departamento
            WHERE ec.id_empresa = ? AND ec.activo = 1 AND c.activo = 1
            ORDER BY ec.es_principal DESC, c.nombre, c.id', 'i', [$company]);
        $result['addresses'] = op_rows($conexion, 'SELECT * FROM empresa_direcciones WHERE empresa_id = ?
            ORDER BY es_principal DESC, tipo_direccion, id', 'i', [$company]);
    } elseif ($action === 'list') {
        $result['data'] = op_rows($conexion, 'SELECT o.*,
            COALESCE((SELECT SUM(c.cot_subtotal) FROM oportunidad_cotizaciones oc
                JOIN cotizaciones c ON c.id_coti = oc.cotizacion_id
                WHERE oc.oportunidad_id = o.id AND LOWER(c.cot_status) LIKE \'%aceptada%\'), o.importe) AS importe_vigente,
            u.nombre_completo AS colaborador,
            CONCAT_WS(\' \', COALESCE(NULLIF(TRIM(e.razon_social), \'\'), e.empresa), NULLIF(TRIM(e.regimen_capital), \'\')) AS empresa,
            c.nombre AS contacto
            FROM oportunidades_comerciales o JOIN empresas e ON e.id_e = o.empresa_id
            LEFT JOIN contactos c ON c.id = o.contacto_id
            LEFT JOIN usuarios u ON u.id = o.created_by ORDER BY o.created_at DESC, o.id DESC');
    } elseif ($action === 'get') {
        $result['opportunity'] = op_get($conexion, op_id($input['id'] ?? null));
    } elseif ($action === 'quotes') {
        $op = op_get($conexion, op_id($input['id'] ?? null));
        $result['linked'] = op_rows($conexion, 'SELECT c.*,
            CONCAT_WS(\' \', COALESCE(NULLIF(TRIM(e.razon_social), \'\'), e.empresa), NULLIF(TRIM(e.regimen_capital), \'\')) AS empresa_actual,
            ct.nombre AS contacto_actual, ct.celular AS telefono_actual,
            ct.correo AS correo_actual, COALESCE(cd.nombre, ct.depto) AS departamento_actual,
            d.id AS direccion_actual_id, d.calle, d.numero_exterior, d.numero_interior,
            d.colonia, d.localidad, d.municipio, d.ciudad, d.estado, d.codigo_postal,
            d.pais, d.entre_calles, d.referencia, d.direccion_original
            FROM oportunidad_cotizaciones oc
            JOIN cotizaciones c ON c.id_coti = oc.cotizacion_id
            LEFT JOIN empresas e ON e.id_e = c.empresa_id
            LEFT JOIN contactos ct ON ct.id = c.contacto_id
            LEFT JOIN catalogo_departamentos cd ON cd.id = ct.id_departamento
            LEFT JOIN empresa_direcciones d ON d.id = c.direccion_id AND d.empresa_id = c.empresa_id
            WHERE oc.oportunidad_id = ? ORDER BY c.id_coti DESC', 'i', [$op['id']]);
        foreach ($result['linked'] as &$quote) {
            if ($quote['empresa_actual'] !== null) { $quote['cot_empresa'] = $quote['empresa_actual']; }
            if ($quote['contacto_actual'] !== null) {
                $quote['cot_contacto'] = $quote['contacto_actual'];
                $quote['cot_telefono'] = $quote['telefono_actual'] ?? '';
                $quote['cot_correo'] = $quote['correo_actual'] ?? '';
                $quote['cot_departamento'] = $quote['departamento_actual'] ?? '';
            }
            if ($quote['direccion_id'] !== null && $quote['direccion_actual_id'] !== null) {
                $quote['cot_direccion'] = cotizacionDireccionActual($quote);
            }
            $file = trim((string) ($quote['cot_archivo'] ?? ''));
            $quote['pdf_disponible'] = $file !== '' && !preg_match('/[\\\\\/]/', $file)
                && preg_match('/\.pdf$/i', $file) === 1
                && is_file(__DIR__ . '/../../filesPDF/' . $file);
        }
        unset($quote);
        // Lista las cotizaciones por la relación estable con la empresa, sin depender de nombres que pueden cambiar.
        $result['available'] = op_rows($conexion, 'SELECT c.id_coti, c.cot_numero, c.cot_fecha, c.cot_total
            FROM cotizaciones c LEFT JOIN oportunidad_cotizaciones oc ON oc.cotizacion_id = c.id_coti
            WHERE c.empresa_id = ? AND oc.cotizacion_id IS NULL ORDER BY c.id_coti DESC', 'i', [$op['empresa_id']]);
    } else {
        $conexion->begin_transaction();
        $transaction = true;
        $id = op_id($input['id'] ?? '', $action === 'save');
        $old = $id ? op_get($conexion, $id, true) : null;
        if ($old && $action === 'save'
            && (string) ($input['version'] ?? '') !== (string) $old['version']) {
            throw new RuntimeException('Otra edición modificó esta oportunidad. Recarga la página antes de guardar.');
        }
        $actor = (int) ($_SESSION['auth_user_id'] ?? 0);
        if ($action === 'save') {
            $company = op_id($input['empresa_id'] ?? null);
            $contact = op_id($input['contacto_id'] ?? '', true);
            $address = op_id($input['direccion_id'] ?? '', true);
            op_relations($conexion, $company, $contact, $address);
            $date = (string) ($input['fecha'] ?? '');
            $parsedDate = DateTimeImmutable::createFromFormat('!Y-m-d', $date);
            if (!$parsedDate || $parsedDate->format('Y-m-d') !== $date || $date < '1000-01-01') {
                throw new InvalidArgumentException('La fecha no es válida.');
            }
            $short = trim((string) ($input['descripcion_corta'] ?? ''));
            $long = trim((string) ($input['descripcion_larga'] ?? ''));
            if (mb_strlen($short, 'UTF-8') > 150 || strlen($long) > 60000) {
                throw new InvalidArgumentException('La descripción excede la longitud permitida.');
            }
            $amount = trim((string) ($input['importe'] ?? '')) === '' ? '0.00' : op_amount($input['importe']);
            $status = $id ? (string) ($input['estatus'] ?? '') : 'preparacion';
            if (!isset(OP_ESTATUS[$status])) {
                throw new InvalidArgumentException('El estatus no es válido.');
            }
            if ($old && (int) $old['empresa_id'] !== $company
                && op_rows($conexion, 'SELECT cotizacion_id FROM oportunidad_cotizaciones WHERE oportunidad_id = ? LIMIT 1', 'i', [$id])) {
                throw new InvalidArgumentException('Desvincula las cotizaciones antes de cambiar la empresa de esta oportunidad.');
            }
            if ($id) {
                op_query($conexion, 'UPDATE oportunidades_comerciales SET fecha = ?, empresa_id = ?, contacto_id = ?,
                    direccion_id = ?, descripcion_corta = ?, descripcion_larga = ?, importe = ?, estatus = ?,
                    updated_by = ?, updated_at = CURRENT_TIMESTAMP, version = version + 1 WHERE id = ?',
                    'siiissssii', [$date, $company, $contact, $address, $short, $long, $amount, $status, $actor, $id])->close();
            } else {
                $numeroOportunidad = reservarFolioComercial($conexion, 'OPC', $date);
                op_query($conexion, 'INSERT INTO oportunidades_comerciales
                    (numero_oportunidad, fecha, empresa_id, contacto_id, direccion_id, descripcion_corta, descripcion_larga, importe, estatus, created_by, updated_by)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)', 'ssiiissssii',
                    [$numeroOportunidad, $date, $company, $contact, $address, $short, $long, $amount, $status, $actor, $actor])->close();
                $id = (int) $conexion->insert_id;
            }
            sincronizarEstatusOportunidadCotizaciones($conexion, $id);
        } else {
            $quoteId = op_id($input['cotizacion_id'] ?? null);
            if ($action === 'link') {
                $quote = op_rows($conexion, 'SELECT id_coti FROM cotizaciones WHERE id_coti = ? AND empresa_id = ? FOR UPDATE',
                    'ii', [$quoteId, $old['empresa_id']]);
                if (!$quote) {
                    throw new InvalidArgumentException('La cotización no corresponde a esta empresa.');
                }
                $existing = op_rows($conexion, 'SELECT oportunidad_id FROM oportunidad_cotizaciones WHERE cotizacion_id = ?', 'i', [$quoteId]);
                if ($existing && (int) $existing[0]['oportunidad_id'] !== $id) {
                    throw new RuntimeException('La cotización ya pertenece a otra oportunidad.');
                }
                if (!$existing) {
                    op_query($conexion, 'INSERT INTO oportunidad_cotizaciones (oportunidad_id, cotizacion_id) VALUES (?, ?)', 'ii', [$id, $quoteId])->close();
                }
            } else {
                op_query($conexion, 'DELETE FROM oportunidad_cotizaciones WHERE oportunidad_id = ? AND cotizacion_id = ?', 'ii', [$id, $quoteId])->close();
            }
            op_query($conexion, 'UPDATE oportunidades_comerciales SET updated_at = CURRENT_TIMESTAMP,
                updated_by = ?, version = version + 1 WHERE id = ?', 'ii', [$actor, $id])->close();
            sincronizarEstatusOportunidadCotizaciones($conexion, $id);
        }
        $result['opportunity'] = op_get($conexion, $id);
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
    if ($error instanceof InvalidArgumentException) {
        $status = 422; $message = $error->getMessage();
    } elseif ($error instanceof OutOfBoundsException) {
        $status = 404; $message = $error->getMessage();
    } elseif ($error instanceof mysqli_sql_exception) {
        if ($error->getCode() === 1146) {
            $status = 503; $message = 'El módulo de oportunidades aún no está habilitado en esta base de datos.';
        } else {
            error_log('Oportunidades: error SQL ' . $error->getCode());
        }
    } elseif ($error instanceof RuntimeException) {
        $status = 409; $message = $error->getMessage();
    } else {
        error_log('Oportunidades: ' . get_class($error));
    }
    http_response_code($status);
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}
