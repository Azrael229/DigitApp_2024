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

    <section class="dashboard-opportunity-section mt-5" aria-labelledby="dashboard-opportunity-title">
        <h2 id="dashboard-opportunity-title" class="h4 mb-4">Actividad de oportunidades comerciales</h2>
        <div class="dashboard-opportunity-daily" aria-label="Oportunidades comerciales creadas por día">
            <article class="dashboard-hero dashboard-admin-chart dashboard-daily-chart"><h3 id="dashboard-oportunidades-mes-anterior-titulo" class="h5 text-white mb-3">Oportunidades creadas · mes anterior</h3><div class="dashboard-chart-canvas"><canvas id="dashboard-oportunidades-mes-anterior" aria-label="Cantidad de oportunidades creadas cada día del mes anterior" role="img"></canvas></div></article>
            <article class="dashboard-hero dashboard-admin-chart dashboard-daily-chart"><h3 id="dashboard-oportunidades-mes-actual-titulo" class="h5 text-white mb-3">Oportunidades creadas · mes actual</h3><div class="dashboard-chart-canvas"><canvas id="dashboard-oportunidades-mes-actual" aria-label="Cantidad de oportunidades creadas cada día del mes actual" role="img"></canvas></div></article>
        </div>
        <article class="dashboard-hero dashboard-admin-chart dashboard-monthly-opportunity-chart mt-4"><h3 class="h5 text-white mb-2">Oportunidades por mes y estado</h3><p class="text-white-50 mb-3">Cantidad creada durante los últimos 12 meses, agrupada por estado actual. El total mensual aparece al señalar una barra.</p><div class="dashboard-chart-canvas"><canvas id="dashboard-oportunidades-por-mes" aria-label="Cantidad mensual de oportunidades en negociación, ganadas y canceladas o perdidas" role="img"></canvas></div></article>
        <article class="dashboard-hero dashboard-admin-chart dashboard-opportunity-global-chart mt-4"><h3 id="dashboard-oportunidades-total-titulo" class="h5 text-white mb-2">Oportunidades comerciales · total global</h3><p class="text-white-50 mb-2">Tasa de bateo (ganadas ÷ total): <strong id="dashboard-oportunidades-tasa-bateo" class="dashboard-batting-average">0%</strong></p><p class="text-white-50 mb-3">Distribución global: negociación, ganadas y canceladas o perdidas.</p><div class="dashboard-chart-canvas"><canvas id="dashboard-oportunidades-globales" aria-label="Total global de oportunidades comerciales y cantidad en negociación, ganadas y canceladas o perdidas" role="img"></canvas></div></article>
    </section>
</main>
<script>window.dashboardInvoiceData = <?= json_encode($datosDashboardCfdi, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="../js/dashboard-charts.js?v=20261118-1"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
