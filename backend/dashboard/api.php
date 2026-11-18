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

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        header('Allow: GET');
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    require __DIR__ . '/../../config/conexion.php';
    $conexion->set_charset('utf8mb4');

    $currentMonth = new DateTimeImmutable('first day of this month');
    $previousMonth = $currentMonth->modify('-1 month');
    $nextMonth = $currentMonth->modify('+1 month');
    $firstMonthlyMonth = $currentMonth->modify('-11 months');
    $from = $previousMonth->format('Y-m-d 00:00:00');
    $until = $nextMonth->format('Y-m-d 00:00:00');
    $dailySql = 'SELECT DATE_FORMAT(created_at, \'%Y-%m-%d\') AS dia, COUNT(*) AS cantidad
        FROM oportunidades_comerciales
        WHERE created_at >= ? AND created_at < ?
        GROUP BY dia ORDER BY dia';
    $dailyCounts = dashboard_fetch($conexion, $dailySql, 'ss', [$from, $until]);

    $monthlySql = 'SELECT DATE_FORMAT(created_at, \'%Y-%m\') AS mes, estatus, COUNT(*) AS cantidad
        FROM oportunidades_comerciales
        WHERE created_at >= ? AND created_at < ?
        GROUP BY mes, estatus ORDER BY mes, estatus';
    $monthlyCounts = dashboard_fetch(
        $conexion,
        $monthlySql,
        'ss',
        [$firstMonthlyMonth->format('Y-m-d 00:00:00'), $until]
    );

    $globalStatusSql = 'SELECT estatus, COUNT(*) AS cantidad
        FROM oportunidades_comerciales
        GROUP BY estatus ORDER BY estatus';
    $globalStatusCounts = dashboard_fetch($conexion, $globalStatusSql, '', []);

    echo json_encode([
        'mes_anterior' => $previousMonth->format('Y-m'),
        'mes_actual' => $currentMonth->format('Y-m'),
        'oportunidades_por_dia' => $dailyCounts,
        'oportunidades_por_mes' => $monthlyCounts,
        'oportunidades_por_estado_global' => $globalStatusCounts,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    error_log('Dashboard de administración: ' . get_class($error) . ' en línea ' . $error->getLine());
    http_response_code(500);
    echo json_encode(['error' => 'No se pudieron cargar los indicadores del Dashboard.'], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conexion) && $conexion instanceof mysqli) {
        $conexion->close();
    }
}
