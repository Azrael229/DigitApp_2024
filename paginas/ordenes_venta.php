<?php
require __DIR__ . '/../backend/ordenes_venta/common.php';
$prefijoRuta = '../';
require __DIR__ . '/../construct/header.php';
?>
<div class="container mt-5 mb-5 contain shadow-lg oportunidades-page" data-ov-csrf="<?= ov_escape($_SESSION['ordenes_venta_csrf']) ?>">
    <div class="row align-items-center contacto-form-header g-3">
        <div class="col"><p class="contactos-kicker mb-1">Gestión comercial</p><h1 class="h2 mb-0">Órdenes de venta</h1></div>
        <div class="col-auto"><a href="form_orden_venta.php" class="btn btn-success"><i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva orden de venta</a></div>
    </div>
    <div id="mensaje_ordenes" class="alert d-none mt-4" role="status" aria-live="polite"></div>
    <div class="factura-resumen mt-4 mb-4">
        <div class="factura-resumen-item"><span class="factura-resumen-label">Órdenes filtradas</span><strong id="ov-registros">0</strong></div>
        <div class="factura-resumen-item factura-resumen-total"><span class="factura-resumen-label">Importe sin IVA · MXN</span><strong id="ov-subtotal">$0.00</strong></div>
    </div>
    <div class="table-responsive data-table-shell table-wide mt-4">
        <table id="tabla-ordenes-venta" class="table table-striped align-middle mb-0 w-100">
            <thead><tr><th>Fecha</th><th>Orden</th><th>Empresa</th><th>Contacto</th><th>Correo</th><th>Teléfono</th><th>Importe sin IVA</th><th>Estatus</th><th>Registro</th></tr></thead>
            <tbody></tbody>
        </table>
    </div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="../js/datatable-filters.js"></script>
<script src="../js/datatable-config.js"></script>
<script src="../js/ordenes-venta-common.js?v=20261004-1"></script>
<script src="../js/tablaOrdenesVenta.js?v=20261004-1"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
