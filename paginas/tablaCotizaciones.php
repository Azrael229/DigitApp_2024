
<?php $prefijoRuta = '../'; ?>
<?php  require (__DIR__ . "/../construct/header.php")   ?>
<?php  require (__DIR__ . "/../backend/cotizaciones/query_all_cotizaciones.php")   ?>
<div class="container mt-5 mb-5 contain shadow-lg oportunidades-page">
    <div class="row align-items-center contacto-form-header g-3">
            <div class="col">
                <p class="contactos-kicker mb-1">Gestión comercial</p>
                <h1 class="h2 mb-0">Cotizaciones</h1>
            </div>
            <div class="col-12 col-sm-auto">
                <a href="<?= $prefijoRuta ?>paginas/nuevaCotizacion.php" class="btn btn-success">
                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Nueva cotización
                </a>
            </div>
    </div>
    <div class="factura-resumen mt-4 mb-4">
        <div class="factura-resumen-item">
            <span class="factura-resumen-label">Cotizaciones filtradas</span>
            <strong id="cot-registros">0</strong>
        </div>
        <div class="factura-resumen-item factura-resumen-total">
            <span class="factura-resumen-label">Importe total filtrado · MXN</span>
            <strong id="cot-total-filtrado">$0.00</strong>
        </div>
    </div>
    <p class="contactos-muted">El importe incluye todas las páginas que coincidan con los filtros.</p>
    <div class="table-responsive data-table-shell table-wide mt-4 empresa-table-wrap">
                <table id="example" class="table table-secondary table-striped align-middle mb-0 w-100">
                    <thead>
                        <tr>
                            <th scope="col">NÚMERO</th>
                            <th scope="col">FECHA</th>
                            <th scope="col">EMPRESA</th>
                            <th scope="col">CONTACTO</th>
                            <th scope="col">CORREO ELECTRÓNICO</th>
                            <th scope="col">IMPORTE</th>
                            <th scope="col">ESTATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($result_cotizaciones as $row_coti): ?>
                        <tr>
                            <td>
                                <a class="entity-link fw-semibold" href="ver_cotizacion.php?id=<?= (int) $row_coti['id_coti'] ?>">
                                    <?= htmlspecialchars((string) (($row_coti['cot_numero'] ?? '') ?: $row_coti['id_coti']), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td><?php echo $row_coti['cot_fecha'] ?></td>
                            <td><?= htmlspecialchars((string) ($row_coti['cot_empresa'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($row_coti['cot_contacto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?= htmlspecialchars((string) (($row_coti['cot_correo'] ?? '') ?: 'Sin correo'), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td data-cot-total="<?= htmlspecialchars(number_format((float) $row_coti['cot_total'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" data-order="<?= htmlspecialchars(number_format((float) $row_coti['cot_total'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">$ <?= htmlspecialchars(number_format((float) $row_coti['cot_total'], 2, '.', ' '), ENT_QUOTES, 'UTF-8') ?></td>
                            <?php $estatusClave = cotizacionEstatusClave($row_coti['cot_status'] ?? ''); ?>
                            <td><span class="badge cot-status cot-status-<?= htmlspecialchars($estatusClave, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(cotizacionEstatusEtiqueta($estatusClave), ENT_QUOTES, 'UTF-8') ?></span></td>
                        </tr>
                        <?php endforeach;  ?>
                        
                    </tbody>
                </table>
    </div>
</div>










<!-- JQuery 3.7.1-->
<script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>

<!-- Data Tables 1.13.7 -->
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script>

<!-- Data Tables 1.13.7 boostrap5 -->
<script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script>

<script src="<?= $prefijoRuta ?>js/datatable-filters.js"></script>
<script src="<?= $prefijoRuta ?>js/datatable-config.js"></script>
<script src="<?= $prefijoRuta ?>js/tablaCotizacion.js?v=20261003-3"></script>
<?php  require (__DIR__ . "/../construct/footer.html")   ?>
