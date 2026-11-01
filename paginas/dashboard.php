<?php
declare(strict_types=1);
$prefijoRuta = '../';
require __DIR__ . '/../backend/auth/bootstrap.php';
$authUser = auth_require_permission('dashboard');
require __DIR__ . '/../construct/header.php';
require __DIR__ . '/../backend/facturacion/query_xml_cfdi.php';
$remisionesDashboard = auth_db()->query('SELECT r.fecha_venta AS fecha,
    COALESCE(NULLIF(TRIM(CONCAT_WS(\' \', e.razon_social, e.regimen_capital)), \'\'), e.empresa, r.cliente) AS cliente,
    COALESCE(e.rfc, r.rfc) AS rfc, c.cot_subtotal AS importe_sin_iva
    FROM facturacion_remisiones r
    LEFT JOIN ordenes_venta ov ON ov.id = r.orden_venta_id
    LEFT JOIN empresas e ON e.id_e = ov.empresa_id
    LEFT JOIN cotizaciones c ON c.id_coti = ov.cotizacion_id')->fetch_all(MYSQLI_ASSOC);
$datosDashboardCfdi = array_map(static fn(array $factura): array => [
    'fecha' => $factura['fecha'],
    'cliente' => $factura['cliente'],
    'rfc' => $factura['rfc'],
    'importe_neto' => (float) ($factura['subtotal'] ?: 0),
], $facturas);
foreach ($remisionesDashboard as $remision) {
    $datosDashboardCfdi[] = ['fecha' => $remision['fecha'], 'cliente' => $remision['cliente'], 'rfc' => $remision['rfc'] ?? '', 'importe_neto' => (float) $remision['importe_sin_iva']];
}
?>
<main class="container mt-5 mb-5 contain shadow-lg dashboard-admin">
    <header class="row align-items-center contactos-header g-3 mb-4">
        <div class="col">
            <p class="contactos-kicker mb-1">Análisis del negocio</p>
            <h1 class="h2 mb-0">Dashboard</h1>
            <p class="text-body-secondary mb-0 mt-2">Indicadores de facturación, oportunidades, ventas y cartera comercial.</p>
        </div>
    </header>

    <div id="dashboard-error" class="alert alert-danger d-none" role="alert"></div>
    <section class="dashboard-admin-grid" aria-label="Indicadores históricos">
        <article class="dashboard-hero dashboard-admin-chart dashboard-admin-chart-wide"><h2 class="h5 text-white mb-3">Ventas mensuales · últimos 13 meses</h2><canvas id="dashboard-ventas-mensuales" aria-label="Ventas netas mensuales de los últimos 13 meses" role="img"></canvas></article>
        <article class="dashboard-hero dashboard-admin-chart"><h2 class="h5 text-white mb-3">Top 10 clientes por ventas netas</h2><canvas id="dashboard-top-clientes" aria-label="Top 10 clientes por nombre y ventas netas sin IVA" role="img"></canvas></article>
        <article class="dashboard-hero dashboard-admin-chart"><h2 class="h5 text-white mb-3">Ventas netas sin IVA por año</h2><canvas id="dashboard-ventas-anuales" aria-label="Ventas netas sin IVA por año" role="img"></canvas></article>
        <article class="dashboard-hero dashboard-admin-chart"><h2 class="h5 text-white mb-3">Top 10 meses por ventas netas</h2><canvas id="dashboard-top-meses" aria-label="Diez meses con mayores ventas netas, con posición, nombre de mes y año" role="img"></canvas></article>
    </section>

    <section class="dashboard-period-section mt-4" aria-labelledby="dashboard-period-title">
        <div class="dashboard-period-card card mb-4">
            <div class="card-body">
                <div class="row g-3 align-items-end">
                    <div class="col-12 col-lg"><h2 id="dashboard-period-title" class="h5 mb-1">Análisis diario por mes</h2><p class="dashboard-chart-note mb-0">Elige mes y año; ambos gráficos mostrarán todos los días de ese mes.</p></div>
                    <div class="col-12 col-sm-6 col-lg-3"><label for="dashboard-periodo" class="form-label">Mes y año</label><input id="dashboard-periodo" class="form-control" type="month" min="2026-09" required></div>
                    <div class="col-12 col-sm-6 col-lg-3"><label for="dashboard-estatus-oportunidad" class="form-label">Estatus de oportunidad</label><select id="dashboard-estatus-oportunidad" class="form-select"><option value="todos">Todos los estatus</option><option value="preparacion">Preparación</option><option value="cotizada">Cotizada</option><option value="negociacion">Negociación</option><option value="ganada">Ganada</option><option value="perdida">Perdida</option><option value="cancelada">Cancelada</option></select></div>
                </div>
            </div>
        </div>
        <div class="dashboard-period-charts" aria-label="Importes por día">
            <article class="dashboard-hero dashboard-admin-chart"><h2 class="h5 text-white mb-1">Importe de oportunidades comerciales</h2><p class="dashboard-chart-note">Importe por día; el filtro de estatus solo afecta esta gráfica.</p><canvas id="dashboard-oportunidades" aria-label="Importe diario de oportunidades comerciales en el mes seleccionado" role="img"></canvas></article>
            <article class="dashboard-hero dashboard-admin-chart"><h2 class="h5 text-white mb-1">Órdenes de venta completadas</h2><p class="dashboard-chart-note">Importes sin IVA por día de finalización.</p><canvas id="dashboard-ordenes" aria-label="Importe diario de órdenes de venta completadas en el mes seleccionado" role="img"></canvas></article>
        </div>
    </section>
</main>
<script>window.dashboardInvoiceData = <?= json_encode($datosDashboardCfdi, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../js/dashboard-charts.js?v=20261101-3"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
