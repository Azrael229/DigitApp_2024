<?php
declare(strict_types=1);

require_once __DIR__ . '/../auth/bootstrap.php';
auth_require_permission('dashboard');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, private');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function dashboard_fetch(mysqli $db, string $sql, string $types, array $values): array
{
    $statement = $db->prepare($sql);
    if ($types !== '') {
        $statement->bind_param($types, ...$values);
    }
    $statement->execute();
    $rows = $statement->get_result()->fetch_all(MYSQLI_ASSOC);
    $statement->close();
    return $rows;
}

function dashboard_month(string $value): bool
{
    if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/D', $value)) {
        return false;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value . '-01');
    $errors = DateTimeImmutable::getLastErrors();
    return $date !== false
        && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))
        && $date->format('Y-m') === $value;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $month = trim((string) ($_GET['mes'] ?? ''));
    $status = (string) ($_GET['estatus_oportunidad'] ?? 'todos');
    if (!dashboard_month($month) || $month < '2026-09') {
        throw new InvalidArgumentException('Selecciona un mes válido a partir de septiembre de 2026.');
    }
    $from = $month . '-01';
    $to = (new DateTimeImmutable($from))->modify('last day of this month')->format('Y-m-d');
    $statuses = ['todos', 'preparacion', 'cotizada', 'negociacion', 'ganada', 'perdida', 'cancelada'];
    if (!in_array($status, $statuses, true)) {
        throw new InvalidArgumentException('Uno de los filtros no es válido.');
    }

    require __DIR__ . '/../../config/conexion.php';
    $conexion->set_charset('utf8mb4');

    $opportunitySql = 'SELECT DATE_FORMAT(o.fecha, \'%Y-%m-%d\') AS periodo, o.estatus,
            COUNT(*) AS cantidad, SUM(o.importe) AS importe
        FROM oportunidades_comerciales o
        WHERE o.fecha BETWEEN ? AND ?';
    $opportunityTypes = 'ss';
    $opportunityValues = [$from, $to];
    if ($status !== 'todos') {
        $opportunitySql .= ' AND o.estatus = ?';
        $opportunityTypes .= 's';
        $opportunityValues[] = $status;
    }
    $opportunitySql .= ' GROUP BY periodo, o.estatus ORDER BY periodo';
    $opportunities = dashboard_fetch($conexion, $opportunitySql, $opportunityTypes, $opportunityValues);

    $ordersSql = 'SELECT DATE_FORMAT(s.fecha_completada, \'%Y-%m-%d\') AS periodo,
            COUNT(*) AS cantidad, SUM(ov.importe_sin_iva) AS importe
        FROM ordenes_venta ov
        JOIN (
            SELECT orden_venta_id, MIN(created_at) AS fecha_completada
            FROM orden_venta_seguimiento
            WHERE tipo = \'estatus\' AND estatus = \'completada\'
            GROUP BY orden_venta_id
        ) s ON s.orden_venta_id = ov.id
        WHERE ov.estatus = \'completada\' AND DATE(s.fecha_completada) BETWEEN ? AND ?
        GROUP BY periodo ORDER BY periodo';
    $orders = dashboard_fetch($conexion, $ordersSql, 'ss', [$from, $to]);

    echo json_encode([
        'oportunidades' => $opportunities,
        'ordenes_venta' => $orders,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (InvalidArgumentException $error) {
    http_response_code(422);
    echo json_encode(['error' => $error->getMessage()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $error) {
    error_log('Dashboard de administración: ' . get_class($error) . ' en línea ' . $error->getLine());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron cargar los indicadores del Dashboard.'], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}
