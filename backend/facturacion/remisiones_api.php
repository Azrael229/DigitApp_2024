<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('facturacion');
header('Content-Type: application/json; charset=utf-8');

function remision_responder(int $status, array $payload): never
{
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $db = auth_db();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = $db->query("SELECT ov.id, ov.numero_venta, ov.fecha_generacion,
                COALESCE(NULLIF(TRIM(CONCAT_WS(' ', e.razon_social, e.regimen_capital)), ''), e.empresa, ov.empresa_nombre) AS cliente,
                e.rfc,
                COALESCE(NULLIF(TRIM(o.descripcion_corta), ''), NULLIF(TRIM(o.descripcion_larga), ''), ov.numero_venta) AS descripcion,
                c.cot_subtotal AS importe_sin_iva,
                COALESCE((SELECT DATE(MAX(s.created_at)) FROM orden_venta_seguimiento s
                    WHERE s.orden_venta_id = ov.id AND s.tipo = 'estatus' AND s.estatus = 'completada'), ov.fecha_generacion) AS fecha_venta
            FROM ordenes_venta ov
            JOIN cotizaciones c ON c.id_coti = ov.cotizacion_id
            JOIN oportunidades_comerciales o ON o.id = ov.oportunidad_id
            JOIN empresas e ON e.id_e = ov.empresa_id
            LEFT JOIN facturacion_remisiones r ON r.orden_venta_id = ov.id
            WHERE ov.estatus = 'completada' AND r.id IS NULL
            ORDER BY fecha_venta DESC, ov.id DESC");
        remision_responder(200, ['ordenes' => $rows->fetch_all(MYSQLI_ASSOC)]);
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        remision_responder(405, ['error' => 'Método no permitido.']);
    }
    $input = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($input) || !auth_verify_csrf($input['csrf'] ?? null)) {
        remision_responder(403, ['error' => 'La sesión expiró. Recarga la página e intenta de nuevo.']);
    }
    $orderId = filter_var($input['orden_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
    if ($orderId === false) {
        remision_responder(422, ['error' => 'Selecciona una orden de venta válida.']);
    }

    $db->begin_transaction();
    $stmt = $db->prepare("SELECT ov.id, ov.numero_venta, ov.fecha_generacion, ov.empresa_id, ov.empresa_nombre,
            c.cot_subtotal AS importe_sin_iva,
            COALESCE(NULLIF(TRIM(CONCAT_WS(' ', e.razon_social, e.regimen_capital)), ''), e.empresa, ov.empresa_nombre) AS cliente,
            e.rfc,
            COALESCE(NULLIF(TRIM(o.descripcion_corta), ''), NULLIF(TRIM(o.descripcion_larga), ''), ov.numero_venta) AS descripcion,
            COALESCE((SELECT DATE(MAX(s.created_at)) FROM orden_venta_seguimiento s
                WHERE s.orden_venta_id = ov.id AND s.tipo = 'estatus' AND s.estatus = 'completada'), ov.fecha_generacion) AS fecha_venta
        FROM ordenes_venta ov JOIN cotizaciones c ON c.id_coti = ov.cotizacion_id
        JOIN oportunidades_comerciales o ON o.id = ov.oportunidad_id
        JOIN empresas e ON e.id_e = ov.empresa_id
        WHERE ov.id = ? AND ov.estatus = 'completada'");
    $stmt->bind_param('i', $orderId);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$order) {
        $db->rollback();
        remision_responder(404, ['error' => 'La orden no existe o no está completada.']);
    }
    $stmt = $db->prepare('INSERT INTO facturacion_remisiones (orden_venta_id, fecha_venta, numero_venta, cliente, rfc, descripcion, importe_sin_iva, creado_por) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
    $creator = (int) ($_SESSION['auth_user_id'] ?? 0);
    $amount = (string) $order['importe_sin_iva'];
    $saleDate = (string) $order['fecha_venta'];
    $orderNumber = (string) $order['numero_venta'];
    $clientName = (string) $order['cliente'];
    $clientRfc = $order['rfc'] === null ? null : (string) $order['rfc'];
    $description = (string) $order['descripcion'];
    $stmt->bind_param('issssssi', $orderId, $saleDate, $orderNumber, $clientName, $clientRfc, $description, $amount, $creator);
    $stmt->execute();
    $id = $db->insert_id;
    $stmt->close();
    $db->commit();
    remision_responder(201, ['ok' => true, 'id' => $id]);
} catch (mysqli_sql_exception $error) {
    if (isset($db) && $db instanceof mysqli) $db->rollback();
    if ((int) $error->getCode() === 1062) remision_responder(409, ['error' => 'Esta orden ya está registrada como remisión.']);
    error_log('Facturación remisión: error de base de datos.');
    remision_responder(500, ['error' => 'No se pudo guardar la remisión.']);
} catch (Throwable $error) {
    if (isset($db) && $db instanceof mysqli) $db->rollback();
    error_log('Facturación remisión: error al procesar la solicitud.');
    remision_responder(500, ['error' => 'No se pudo procesar la solicitud.']);
}
