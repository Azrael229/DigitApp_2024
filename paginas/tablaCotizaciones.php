
<?php
$prefijoRuta = '../';
require_once __DIR__ . '/../backend/auth/bootstrap.php';
$authUser = auth_require_permission('comercial');
$esAdministrador = ($authUser['rol'] ?? '') === 'administrador';
?>
<?php  require (__DIR__ . "/../construct/header.php")   ?>
<?php  require (__DIR__ . "/../backend/cotizaciones/query_all_cotizaciones.php")   ?>
<div class="container mt-5 mb-5 contain shadow-lg oportunidades-page" data-cot-status-csrf="<?= htmlspecialchars($_SESSION['cotizacion_status_csrf'], ENT_QUOTES, 'UTF-8') ?>"<?= $esAdministrador ? ' data-admin-delete-csrf="' . htmlspecialchars(auth_csrf(), ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
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
                <table id="example" class="table table-secondary table-striped align-middle mb-0 w-100 cotizaciones-directory-table">
                    <thead>
                        <tr>
                            <th scope="col" class="table-date-column">FECHA</th>
                            <th scope="col" class="cot-col-number">NÚMERO</th>
                            <th scope="col" class="cot-col-company">RAZÓN SOCIAL</th>
                            <th scope="col" class="cot-col-contact">CONTACTO</th>
                            <th scope="col" class="cot-col-email">CORREO ELECTRÓNICO</th>
                            <th scope="col" class="cot-col-amount">IMPORTE</th>
                            <th scope="col" class="cot-col-status">ESTATUS</th>
                            <?php if ($esAdministrador): ?><th scope="col" class="admin-delete-column">ELIMINAR</th><?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($result_cotizaciones as $row_coti): ?>
                        <tr>
                            <td class="table-date-column"><?php echo htmlspecialchars((string) ($row_coti['cot_fecha'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="cot-col-number">
                                <a class="entity-link fw-semibold" href="ver_cotizacion.php?id=<?= (int) $row_coti['id_coti'] ?>">
                                    <?= htmlspecialchars((string) (($row_coti['cot_numero'] ?? '') ?: $row_coti['id_coti']), ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td class="cot-col-company"><div class="table-text-two-lines"><?= htmlspecialchars((string) ($row_coti['cot_empresa'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></td>
                            <td class="cot-col-contact"><div class="table-text-two-lines"><?= htmlspecialchars((string) ($row_coti['cot_contacto'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div></td>
                            <td class="cot-col-email">
                                <div class="table-text-two-lines"><?= htmlspecialchars((string) (($row_coti['cot_correo'] ?? '') ?: 'Sin correo'), ENT_QUOTES, 'UTF-8') ?></div>
                            </td>
                            <td class="cot-col-amount" data-cot-total="<?= htmlspecialchars(number_format((float) $row_coti['cot_total'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" data-order="<?= htmlspecialchars(number_format((float) $row_coti['cot_total'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>">$ <?= htmlspecialchars(number_format((float) $row_coti['cot_total'], 2, '.', ' '), ENT_QUOTES, 'UTF-8') ?></td>
                            <?php $estatusClave = cotizacionEstatusClave($row_coti['cot_status'] ?? ''); ?>
                            <td class="cot-col-status"><select class="form-select form-select-sm cot-status-select" data-id="<?= (int) $row_coti['id_coti'] ?>" aria-label="Estatus de cotización <?= htmlspecialchars((string) ($row_coti['cot_numero'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?php foreach (COTIZACION_ESTATUS as $clave => $etiqueta): ?><option value="<?= $clave ?>"<?= $estatusClave === $clave ? ' selected' : '' ?>><?= htmlspecialchars($etiqueta, ENT_QUOTES, 'UTF-8') ?></option><?php endforeach; ?></select></td>
                            <?php if ($esAdministrador): ?><td class="admin-delete-column"><button type="button" class="btn btn-outline-danger btn-sm" data-admin-delete data-delete-entity="cotizacion" data-delete-id="<?= (int) $row_coti['id_coti'] ?>" data-delete-label="la cotización <?= htmlspecialchars((string) ($row_coti['cot_numero'] ?? $row_coti['id_coti']), ENT_QUOTES, 'UTF-8') ?>"><i class="bi bi-trash" aria-hidden="true"></i> Eliminar</button></td><?php endif; ?>
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

<script src="<?= $prefijoRuta ?>js/datatable-filters.js?v=20261008-2"></script>
<script src="<?= $prefijoRuta ?>js/datatable-config.js"></script>
<script src="<?= $prefijoRuta ?>js/cotizaciones-status.js?v=20261003-1"></script>
<?php if ($esAdministrador): ?><script src="<?= $prefijoRuta ?>js/admin-delete.js?v=20261009-1"></script><?php endif; ?>
<script src="<?= $prefijoRuta ?>js/tablaCotizacion.js?v=20261009-2"></script>
<?php  require (__DIR__ . "/../construct/footer.html")   ?>
