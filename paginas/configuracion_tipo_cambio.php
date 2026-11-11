<?php
require __DIR__ . '/../backend/catalogo/common.php';
$authUser = auth_require_permission('productos');
if (($authUser['rol'] ?? '') !== 'administrador') {
    auth_deny(403, 'Solo un administrador puede configurar el tipo de cambio.');
}
$prefijoRuta = '../';
require __DIR__ . '/../construct/header.php';
?>
<main class="container mt-5 mb-5 contain shadow-lg empresa-form-page" data-exchange-rate-page data-csrf="<?= catalogo_escape($_SESSION['catalogo_comercial_csrf']) ?>">
    <div class="row align-items-center pt-3 pb-4 mb-4 empresa-detalle-header g-3">
        <div class="col">
            <p class="empresa-header-kicker mb-1">Catálogo comercial</p>
            <h1 class="h2 mb-1">Tipo de cambio</h1>
            <p class="empresa-header-subtitle mb-0">Configura la tasa manual vigente para convertir costos de productos en dólares.</p>
        </div>
        <div class="col-auto"><a href="productos.php" class="btn btn-secondary">Volver a productos</a></div>
    </div>

    <div id="exchange-rate-message" class="alert alert-info" role="status" aria-live="polite">Consultando la tasa vigente...</div>

    <section class="card mb-4 empresa-form-card">
        <div class="card-body empresa-card-body p-4">
            <div class="mb-4">
                <p class="empresa-section-kicker mb-1">Conversión de costos</p>
                <h2 class="h4 mb-1">Tasa central vigente</h2>
                <p class="text-muted mb-0">El valor se guarda como una sola tasa actual; los cambios anteriores no se conservan.</p>
            </div>
            <div class="row g-3 mb-4">
                <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Tasa actual (MXN por USD)</div><div id="exchange-rate-current" class="empresa-data-value">—</div></div>
                <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Última actualización</div><div id="exchange-rate-updated" class="empresa-data-value">—</div></div>
                <div class="col-md-4 empresa-data-field"><div class="empresa-data-label">Productos que se recalcularán</div><div id="exchange-rate-products" class="empresa-data-value">—</div></div>
            </div>
            <form id="exchange-rate-form" novalidate>
                <input type="hidden" id="exchange-rate-version">
                <input type="hidden" id="exchange-rate-product-count">
                <div class="mb-3">
                    <label class="form-label" for="exchange-rate-input">Nuevo tipo de cambio (MXN por USD) <span class="required-mark">*</span></label>
                    <input class="form-control" id="exchange-rate-input" type="number" min="0.000001" max="99999999.999999" step="0.000001" inputmode="decimal" required>
                    <div class="form-text">Captura hasta seis decimales. Esta pantalla no consulta fuentes externas.</div>
                </div>
                <div class="alert alert-secondary" role="note">
                    Se recalcularán el costo real y el precio calculado de los productos marcados en USD. El precio al público manual y las cotizaciones guardadas no cambiarán.
                </div>
                <div class="d-flex justify-content-end">
                    <button id="exchange-rate-apply" class="btn btn-success" type="submit" disabled>Aplicar tasa a productos USD</button>
                </div>
            </form>
        </div>
    </section>
</main>
<script src="../js/catalogo-tipo-cambio.js?v=20261111-2"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
