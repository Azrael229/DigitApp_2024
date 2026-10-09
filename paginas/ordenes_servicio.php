<?php
require __DIR__ . '/../backend/ordenes_servicio/common.php';
$prefijoRuta = '../';
require __DIR__ . '/../construct/header.php';
?>
<div class="container mt-5 mb-5 contain shadow-lg oportunidades-page" data-ov-csrf="<?= ov_escape($_SESSION['ordenes_venta_csrf']) ?>">
    <div class="row align-items-center contacto-form-header g-3"><div class="col"><p class="contactos-kicker mb-1">Operación</p><h1 class="h2 mb-0">Órdenes de servicio</h1></div><div class="col-auto"><a href="ordenes_venta.php" class="btn btn-secondary"><i class="bi bi-receipt" aria-hidden="true"></i> Órdenes de venta</a></div></div>
    <div id="mensaje_servicios" class="alert d-none mt-4" role="status" aria-live="polite"></div>
    <div class="factura-resumen mt-4 mb-4"><div class="factura-resumen-item"><span class="factura-resumen-label">Órdenes filtradas</span><strong id="os-records">0</strong></div></div>
    <div class="table-responsive data-table-shell table-wide mt-4"><table id="tabla-ordenes-servicio" class="table table-striped align-middle mb-0 w-100"><thead><tr><th>Fecha</th><th>Orden de servicio</th><th>Orden de venta</th><th>Razón social</th><th>Contacto de entrega</th><th>Tipo</th><th>Estatus</th><th>Actualización</th></tr></thead><tbody></tbody></table></div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>
<script src="../js/datatable-filters.js?v=20261008-2"></script><script src="../js/datatable-config.js"></script>
<script src="../js/ordenes-servicio-common.js?v=20261009-1"></script><script src="../js/tablaOrdenesServicio.js?v=20261007-1"></script>
<?php require __DIR__ . '/../construct/footer.html'; ?>
