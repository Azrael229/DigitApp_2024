<?php
declare(strict_types=1);
$prefijoRuta = '../';
require __DIR__ . '/../backend/auth/bootstrap.php';
$authUser = auth_require_permission('dashboard');
require __DIR__ . '/../construct/header.php';
require __DIR__ . '/../backend/facturacion/query_xml_cfdi.php';
$remisionesDashboard = auth_db()->query('SELECT fecha_venta AS fecha, cliente, rfc, importe_sin_iva FROM facturacion_remisiones')->fetch_all(MYSQLI_ASSOC);
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
    <section class="dashboard-period-card card mb-4" aria-labelledby="dashboard-period-title">
        <div class="card-body">
            <h2 id="dashboard-period-title" class="h5 mb-3">Periodo de análisis</h2>
            <div class="row g-3 align-items-end">
                <div class="col-12 col-sm-6 col-lg-3"><label for="dashboard-desde" class="form-label">Desde</label><input id="dashboard-desde" class="form-control" type="date" min="2026-09-01" required></div>
                <div class="col-12 col-sm-6 col-lg-3"><label for="dashboard-hasta" class="form-label">Hasta</label><input id="dashboard-hasta" class="form-control" type="date" required></div>
                <div class="col-12 col-sm-6 col-lg-3"><label for="dashboard-estatus-oportunidad" class="form-label">Estatus de oportunidad</label><select id="dashboard-estatus-oportunidad" class="form-select"><option value="todos">Todos los estatus</option><option value="preparacion">Preparación</option><option value="cotizada">Cotizada</option><option value="negociacion">Negociación</option><option value="ganada">Ganada</option><option value="perdida">Perdida</option><option value="cancelada">Cancelada</option></select></div>
                <div class="col-12 d-flex justify-content-end"><button id="dashboard-aplicar" class="btn btn-success" type="button"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Aplicar periodo</button></div>
            </div>
        </div>
    </section>

    <section class="dashboard-admin-grid" aria-label="Indicadores históricos">
        <article class="dashboard-hero dashboard-admin-chart dashboard-admin-chart-wide"><h2 class="h5 text-white mb-3">Ventas mensuales · últimos 13 meses</h2><canvas id="dashboard-ventas-mensuales" aria-label="Ventas netas mensuales de los últimos 13 meses" role="img"></canvas></article>
        <article class="dashboard-hero dashboard-admin-chart"><h2 class="h5 text-white mb-3">Top 10 clientes por ventas netas</h2><canvas id="dashboard-top-clientes" aria-label="Top 10 clientes por nombre y ventas netas sin IVA" role="img"></canvas></article>
        <article class="dashboard-hero dashboard-admin-chart"><h2 class="h5 text-white mb-3">Ventas netas sin IVA por año</h2><canvas id="dashboard-ventas-anuales" aria-label="Ventas netas sin IVA por año" role="img"></canvas></article>
        <article class="dashboard-hero dashboard-admin-chart"><h2 class="h5 text-white mb-3">Top 10 meses por ventas netas</h2><canvas id="dashboard-top-meses" aria-label="Diez meses con mayores ventas netas, con posición, nombre de mes y año" role="img"></canvas></article>
    </section>

    <section class="dashboard-period-charts mt-4" aria-label="Indicadores por periodo">
        <article class="dashboard-hero dashboard-admin-chart"><h2 class="h5 text-white mb-1">Importe de oportunidades comerciales</h2><p class="dashboard-chart-note">Importes agrupados por mes desde septiembre de 2026.</p><canvas id="dashboard-oportunidades" aria-label="Importe mensual de oportunidades comerciales desde septiembre de 2026" role="img"></canvas></article>
        <article class="dashboard-hero dashboard-admin-chart"><h2 class="h5 text-white mb-1">Órdenes de venta completadas</h2><p class="dashboard-chart-note">Importes sin IVA agrupados por mes desde septiembre de 2026.</p><canvas id="dashboard-ordenes" aria-label="Importe mensual de órdenes de venta completadas desde septiembre de 2026" role="img"></canvas></article>
    </section>
</main>
<script>window.dashboardInvoiceData = <?= json_encode($datosDashboardCfdi, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../js/dashboard-charts.js?v=20261101-1"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
