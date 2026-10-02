<?php
require __DIR__ . '/../backend/oportunidades/common.php';
$prefijoRuta = '../';
require __DIR__ . '/../construct/header.php';
?>
<div class="container mt-5 mb-5 contain shadow-lg oportunidades-page" data-csrf="<?= op_escape($_SESSION['oportunidades_csrf']) ?>">
    <div class="row align-items-center contacto-form-header g-3"><div class="col"><p class="contactos-kicker mb-1">Gestión comercial</p><h1 id="op-titulo" class="h2 mb-0">Detalle de oportunidad</h1></div><div class="col-auto d-flex gap-2"><a href="oportunidades.php" class="btn btn-outline-secondary">Volver</a><a id="op-editar" class="btn btn-secondary d-none">Editar</a></div></div>
    <div id="mensaje_oportunidad" class="alert d-none mt-4" role="status" aria-live="polite"></div>
    <div id="op-detalle" class="d-none">
        <section class="card border-0 shadow-sm contacto-form-card mt-4"><div class="card-body p-4 contacto-form-card-body"><div id="op-datos" class="row g-4"></div></div></section>
        <section class="card border-0 shadow-sm contacto-form-card mt-4"><div class="card-body p-4 contacto-form-card-body"><h2 class="h4">Descripción detallada</h2><p id="op-descripcion" class="op-multiline mb-0"></p></div></section>
        <section class="card border-0 shadow-sm contacto-form-card mt-4"><div class="card-body p-4 contacto-form-card-body"><h2 class="h4">Control del registro</h2><div id="op-auditoria" class="contactos-muted"></div></div></section>
        <section class="card border-0 shadow-sm contacto-form-card mt-4"><div class="card-body p-4 contacto-form-card-body">
            <h2 class="h4">Cotizaciones de esta oportunidad</h2><p class="contactos-muted">Las cotizaciones se vinculan explícitamente. Su importe no modifica el importe propuesto del proyecto.</p>
            <div id="mensaje_cotizaciones" class="alert d-none" role="status" aria-live="polite"></div>
            <div class="table-responsive data-table-shell"><table class="table table-secondary table-striped mb-0"><thead><tr><th>Fecha</th><th>Cotización</th><th>Importe registrado</th><th>PDF</th><th>Acción</th></tr></thead><tbody id="op-cotizaciones"></tbody></table></div>
            <form id="op-vincular" class="row g-3 mt-3"><div class="col-md-9"><label for="op-cotizacion" class="form-label">Vincular una cotización de esta empresa</label><select id="op-cotizacion" class="form-select" required disabled><option value="">Seleccionar cotización</option></select></div><div class="col-md-3 d-flex align-items-end"><button id="op-vincular-boton" class="btn btn-secondary" disabled>Vincular</button></div></form>
        </div></section>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="../js/oportunidades-common.js"></script>
<script src="../js/ver_oportunidad.js"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
