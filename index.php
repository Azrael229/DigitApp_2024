
<?php  require ("construct/header.html")   ?>
<?php  require ("querys/query_all_cotizaciones.php")   ?>
<?php  require ("querys/query_all_notas.php")   ?>
<?php  require ("querys/query_xml_cfdi.php")   ?>

  
<!-- container -->
<div class="container mt-5 mb-5 contain shadow-lg">

    <!-- row de titulo -->
    <div class="row">
        <div class="col text-center mt-3 mb-5">
            <div class="dashboard-hero">
                <span class="dashboard-kicker">DigitApp</span>
                <h1>Panel de Administrador</h1>
                <p class="dashboard-subtitle mb-0">Tabla de facturación CFDI</p>
            </div>
        </div>
    </div>
    <!-- row de titulo -->

        <!-- row de Tabla facturacion -->
    <div class="row mb-4">
        <div class="col section-shell">
            <div class="row p-3 mt-2 gy-3">
                <div class="col-12">
                    <div class="d-flex flex-column align-items-center justify-content-center text-center h-100">
                        <h5 class="mb-2"><i class="bi bi-receipt-cutoff" id="ico_coti"></i></h5>
                        <h5 class="mb-0">Facturación CFDI</h5>
                    </div>
                </div>
                <div class="col-12">
                    <div class="d-flex flex-column flex-sm-row justify-content-center justify-content-lg-end align-items-center gap-2 pt-2">
                        <button type="button" class="btn btn-outline-success" onclick="abrirSelectorXml()">
                            Cargar XML
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="abrirSelectorPdf()">
                            Cargar PDF
                        </button>
                    </div>
                    <input type="file" id="input-xml-cfdi" class="d-none" accept=".xml,application/xml,text/xml">
                    <input type="file" id="input-pdf-cfdi" class="d-none" accept=".pdf,application/pdf">
                </div>
            </div>

            <div class="col p-4 factura-table-wrap">
                <?php if (empty($facturas)): ?>
                    <div class="alert alert-warning mb-0" role="alert">
                        No se encontraron archivos XML.
                    </div>
                <?php else: ?>
                    <div class="factura-resumen mb-4">
                        <div class="factura-resumen-item">
                            <span class="factura-resumen-label">Registros</span>
                            <strong id="card-registros"><?= count($facturas) ?></strong>
                        </div>
                        <div class="factura-resumen-item">
                            <span class="factura-resumen-label">Subtotal</span>
                            <strong id="card-subtotal"><?= htmlspecialchars(formatAmount((string) array_sum(array_map(static fn(array $factura): float => (float) ($factura['subtotal'] ?: 0), $facturas))), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="factura-resumen-item">
                            <span class="factura-resumen-label">IVA</span>
                            <strong id="card-iva"><?= htmlspecialchars(formatAmount((string) array_sum(array_map(static fn(array $factura): float => (float) ($factura['iva'] ?: 0), $facturas))), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                        <div class="factura-resumen-item factura-resumen-total">
                            <span class="factura-resumen-label">Total</span>
                            <strong id="card-total"><?= htmlspecialchars(formatAmount((string) array_sum(array_map(static fn(array $factura): float => (float) ($factura['total'] ?: 0), $facturas))), ENT_QUOTES, 'UTF-8') ?></strong>
                        </div>
                    </div>

                    <table id="example" class="table table-secondary table-striped align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Fecha</th>
                                <th>Cliente</th>
                                <th>Descripción</th>
                                <th>Subtotal</th>
                                <th>IVA</th>
                                <th>Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($facturas as $factura): ?>
                                <tr>
                                    <td data-order="<?= htmlspecialchars($factura['fecha'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(formatCfdiDate($factura['fecha']), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td><?= htmlspecialchars($factura['cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($factura['descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-end" data-value="<?= htmlspecialchars($factura['subtotal'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(formatAmount($factura['subtotal']), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="text-end" data-value="<?= htmlspecialchars($factura['iva'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(formatAmount($factura['iva']), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="text-end" data-value="<?= htmlspecialchars($factura['total'], ENT_QUOTES, 'UTF-8') ?>">
                                        <?= htmlspecialchars(formatAmount($factura['total']), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr class="factura-total-row">
                                <th colspan="3" class="text-end">Totales filtrados</th>
                                <th id="total-subtotal" class="text-end"></th>
                                <th id="total-iva" class="text-end"></th>
                                <th id="total-general" class="text-end"></th>
                            </tr>
                        </tfoot>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <!-- row de Tabla facturacion -->

    <!--Seccion calendario -->                  
    <!--Seccion calendario -->
</div>
<!-- container -->










<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Data Tables 1.13.7 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<!-- Data Tables 1.13.7 boostrap5 -->
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script src="js/datatable-filters.js"></script>
<script src="js/main.js"></script>
<script src="js/tablaFacturacion.js"></script>


<?php  require ("construct/footer.html")   ?>



