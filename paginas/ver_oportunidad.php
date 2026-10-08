<?php
require __DIR__ . '/../backend/oportunidades/common.php';
$prefijoRuta = '../';
require __DIR__ . '/../construct/header.php';
?>
<div class="container mt-5 mb-5 contain shadow-lg empresa-detalle oportunidades-page detail-page" data-csrf="<?= op_escape($_SESSION['oportunidades_csrf']) ?>">
    <div class="row align-items-center pt-3 pb-4 mb-4 empresa-detalle-header">
        <div class="col empresa-header-copy">
            <p class="empresa-header-kicker mb-1">Gestión comercial</p>
            <h1 id="op-titulo" class="h2 mb-1 d-flex flex-wrap align-items-baseline gap-2">
                <span>Oportunidad comercial</span>
                <span id="op-numero" class="op-title-number"></span>
                <span id="op-estatus" class="badge op-status op-title-status d-none"></span>
            </h1>
            <p class="empresa-header-subtitle mb-0">Información general y actividad relacionada</p>
        </div>
        <div class="col-12 col-md-auto mt-3 mt-md-0 d-flex flex-column flex-sm-row gap-2 empresa-header-actions">
            <a id="op-crear-orden" class="btn btn-success d-none text-nowrap"><i class="bi bi-clipboard2-check" aria-hidden="true"></i> Crear orden de venta</a>
            <a id="op-editar" class="btn btn-secondary d-none text-nowrap"><i class="bi bi-pencil" aria-hidden="true"></i> Editar oportunidad</a>
            <a href="oportunidades.php" class="btn btn-secondary text-nowrap"><i class="bi bi-arrow-left" aria-hidden="true"></i> Oportunidades</a>
        </div>
    </div>
    <div id="mensaje_oportunidad" class="alert d-none mt-4" role="status" aria-live="polite"></div>
    <div id="op-detalle" class="d-none">
        <section class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="empresa-section-heading mb-3">
                    <p class="empresa-section-kicker mb-1">Empresa de la oportunidad</p>
                    <a id="op-empresa" class="op-company-title entity-link" href="#"></a>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-12 empresa-data-field">
                        <div class="empresa-data-label">Dirección</div>
                        <div id="op-direccion" class="empresa-data-value op-multiline"></div>
                    </div>
                    <div class="col-md-4 empresa-data-field">
                        <div class="empresa-data-label">Contacto</div>
                        <a id="op-contacto" class="empresa-data-value entity-link"></a>
                    </div>
                    <div class="col-md-4 empresa-data-field">
                        <div class="empresa-data-label">Teléfono</div>
                        <div id="op-contacto-telefono" class="empresa-data-value"></div>
                    </div>
                    <div class="col-md-4 empresa-data-field">
                        <div class="empresa-data-label">Correo electrónico</div>
                        <div id="op-contacto-correo" class="empresa-data-value"></div>
                    </div>
                </div>
            </div>
        </section>
        <section class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="empresa-section-heading mb-3"><p class="empresa-section-kicker mb-1">Alcance comercial</p><h2 class="h5 card-title mb-0">Descripción de la oportunidad</h2></div>
                <div class="row g-3 mt-1">
                    <div class="col-12 empresa-data-field"><div class="empresa-data-label">Descripción corta</div><div id="op-descripcion-corta" class="empresa-data-value op-project-title"></div></div>
                    <div class="col-12 empresa-data-field"><div class="empresa-data-label">Descripción detallada</div><div id="op-descripcion" class="empresa-data-value op-detail-copy"></div></div>
                    <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Fecha</div><div id="op-fecha" class="empresa-data-value"></div></div>
                    <div class="col-md-6 empresa-data-field"><div class="empresa-data-label">Importe propuesto sin IVA (MXN)</div><div id="op-importe" class="empresa-data-value"></div></div>
                </div>
            </div>
        </section>
        <section class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-start gap-3 mb-3 empresa-section-heading">
                    <div><p class="empresa-section-kicker mb-1">Historial comercial</p><h2 class="h5 card-title mb-1">Cotizaciones de esta oportunidad</h2><p class="empresa-notes-help mb-0">Las cotizaciones se vinculan explícitamente. Su importe no modifica el importe propuesto del proyecto.</p></div>
                    <a id="op-nueva-cotizacion" class="btn btn-secondary btn-sm d-none"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva cotización</a>
                </div>
                <div id="mensaje_cotizaciones" class="alert d-none" role="status" aria-live="polite"></div>
                <div class="table-responsive data-table-shell empresa-table-wrap table-wide"><table id="op-tabla-cotizaciones" class="table table-secondary table-striped align-middle mb-0 w-100"><thead><tr><th>Fecha</th><th>Cotización</th><th>Contacto</th><th>Importe registrado</th><th>Acción</th><th>Estatus</th></tr></thead><tbody id="op-cotizaciones"></tbody></table></div>
                <form id="op-vincular" class="row g-3 mt-3"><div class="col-md-9"><label for="op-cotizacion" class="form-label">Vincular una cotización de esta empresa</label><select id="op-cotizacion" class="form-select" required disabled><option value="">Seleccionar cotización</option></select></div><div class="col-md-3 d-flex align-items-end"><button id="op-vincular-boton" class="btn btn-secondary" disabled>Vincular</button></div></form>
            </div>
        </section>
        <section class="card mb-4 empresa-form-card empresa-detail-section">
            <div class="card-body empresa-card-body">
                <div class="empresa-section-heading mb-3"><p class="empresa-section-kicker mb-1">Seguimiento</p><h2 class="h5 card-title mb-0">Control del registro</h2></div>
                <div id="op-auditoria" class="empresa-data-value"></div>
            </div>
        </section>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../js/datatable-filters.js?v=20261008-2"></script>
<script src="../js/datatable-config.js"></script>
<script src="../js/oportunidades-common.js"></script>
<script src="../js/cotizaciones-status.js?v=20261003-1"></script>
<script src="../js/ver_oportunidad.js?v=20261004-1"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
