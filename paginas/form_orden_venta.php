<?php
require __DIR__ . '/../backend/ordenes_venta/common.php';
$prefijoRuta = '../';
require __DIR__ . '/../construct/header.php';
$orderId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$opportunityId = filter_input(INPUT_GET, 'oportunidad_id', FILTER_VALIDATE_INT);
$quoteId = filter_input(INPUT_GET, 'cotizacion_id', FILTER_VALIDATE_INT);
?>
<div class="container mt-5 mb-5 contain shadow-lg empresa-form-page"
     data-ov-csrf="<?= ov_escape($_SESSION['ordenes_venta_csrf']) ?>"
     data-order-id="<?= ov_escape($orderId ?: '') ?>"
     data-opportunity-id="<?= ov_escape($opportunityId ?: '') ?>"
     data-quote-id="<?= ov_escape($quoteId ?: '') ?>">
    <div class="row align-items-center pt-3 pb-4 mb-4 empresa-detalle-header">
        <div class="col"><p class="empresa-header-kicker mb-1">Gestión comercial</p><h1 id="ov-form-title" class="h2 mb-1"><?= $orderId ? 'Editar orden de venta' : 'Nueva orden de venta' ?></h1><p class="empresa-header-subtitle mb-0">Formaliza una cotización aceptada y prepara el alcance para las órdenes operativas.</p></div>
        <div class="col-12 col-md-auto mt-3 mt-md-0"><a href="ordenes_venta.php" class="btn btn-secondary"><i class="bi bi-arrow-left" aria-hidden="true"></i> Órdenes de venta</a></div>
    </div>
    <div id="mensaje_form_orden" class="alert alert-info" role="status" aria-live="polite">Preparando el formulario...</div>
    <form id="form_orden_venta" data-local-draft="1" class="d-none" novalidate>
        <input type="hidden" id="ov-id" value="<?= ov_escape($orderId ?: '') ?>">
        <input type="hidden" id="ov-version" value="">

        <section class="card mb-4 empresa-form-card"><div class="card-body empresa-card-body p-4">
            <div class="mb-4"><p class="empresa-section-kicker mb-1">Configuración</p><h2 class="h4 mb-1">Datos de la orden de venta</h2></div>
            <div class="row g-3">
                <div class="col-md-4"><label class="form-label" for="ov-fecha">Fecha de generación <span class="required-mark">*</span></label><input class="form-control" type="date" id="ov-fecha" required></div>
                <div class="col-md-8"><label class="form-label" for="ov-estatus">Estatus</label><select class="form-select" id="ov-estatus"><?php foreach (OV_ESTATUS as $key => $label): ?><option value="<?= ov_escape($key) ?>"><?= ov_escape($label) ?></option><?php endforeach; ?></select></div>
                <div class="col-12"><label class="form-label" for="ov-notas">Notas internas</label><textarea class="form-control" id="ov-notas" rows="3" maxlength="60000" placeholder="Información interna relacionada con esta orden de venta."></textarea></div>
            </div>
        </div></section>

        <section class="card mb-4 empresa-form-card"><div class="card-body empresa-card-body p-4">
            <div class="mb-4"><p class="empresa-section-kicker mb-1">Origen comercial</p><h2 class="h4 mb-1">Oportunidad y cotización</h2><p class="empresa-notes-help mb-0">Busca por número de cotización u oportunidad. Solo se muestran oportunidades Ganadas y cotizaciones Aceptadas, comenzando por las más recientes.</p></div>
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label" for="ov-quote">Número de cotización <span class="required-mark">*</span></label><select class="form-select" id="ov-quote" required><option value="">Buscar o seleccionar cotización</option></select></div>
                <div class="col-md-6"><label class="form-label" for="ov-opportunity">Oportunidad comercial <span class="required-mark">*</span></label><select class="form-select" id="ov-opportunity" required><option value="">Buscar o seleccionar oportunidad</option></select></div>
            </div>
        </div></section>

        <section class="card mb-4 empresa-form-card"><div class="card-body empresa-card-body p-4">
            <div class="mb-4"><p class="empresa-section-kicker mb-1">Datos relacionados · Cliente y proyecto</p><h2 id="ov-company" class="op-company-title mb-1">—</h2><p class="empresa-notes-help mb-0">Empresa relacionada con la oportunidad y la cotización seleccionadas.</p></div>
            <div class="row g-3">
                <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Contacto</div><div id="ov-contact" class="empresa-data-value">—</div></div>
                <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Correo electrónico</div><div id="ov-email" class="empresa-data-value">—</div></div>
                <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Teléfono</div><div id="ov-phone" class="empresa-data-value">—</div></div>
                <div class="col-12 empresa-data-field"><div class="empresa-data-label">Dirección</div><div id="ov-address" class="empresa-data-value ov-multiline">—</div></div>
                <div class="col-12 empresa-data-field"><div class="empresa-data-label">Descripción breve</div><div id="ov-short-description" class="empresa-data-value ov-multiline">—</div></div>
                <div class="col-12 empresa-data-field"><div class="empresa-data-label">Descripción larga</div><div id="ov-long-description" class="empresa-data-value ov-multiline">—</div></div>
                <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Importe sin IVA</div><div id="ov-amount" class="empresa-data-value">—</div></div>
            </div>
        </div></section>

        <section class="card mb-4 empresa-form-card"><div class="card-body empresa-card-body p-4">
            <div class="mb-3"><p class="empresa-section-kicker mb-1">Seguimiento</p><h2 class="h4 mb-1">Control del registro</h2></div>
            <div class="row g-3">
                <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Fecha de generación</div><div id="ov-tracking-generation" class="empresa-data-value">—</div></div>
                <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Actualizaciones</div><div id="ov-tracking-updates" class="empresa-data-value">Sin actualizaciones; se registrará al guardar.</div></div>
            </div>
        </div></section>
        <div class="d-flex flex-column flex-sm-row justify-content-sm-end gap-2 pb-4"><a href="ordenes_venta.php" class="btn btn-danger">Cancelar</a><button id="ov-save" class="btn btn-success" type="submit">Guardar orden de venta</button></div>
    </form>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../js/ordenes-venta-common.js?v=20261004-1"></script>
<script src="../js/formOrdenVenta.js?v=20261004-5"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
