<?php
$prefijoRuta = '../';
require __DIR__ . '/../backend/auth/bootstrap.php';
$authUser = auth_require_permission('facturacion');
require __DIR__ . '/../construct/header.php';
require __DIR__ . '/../backend/facturacion/query_xml_cfdi.php';
$stmtRemisiones = auth_db()->query('SELECT r.id, r.fecha_venta AS fecha,
    COALESCE(NULLIF(TRIM(CONCAT_WS(\' \', e.razon_social, e.regimen_capital)), \'\'), e.empresa, r.cliente) AS cliente,
    COALESCE(e.rfc, r.rfc) AS rfc, r.descripcion, c.cot_subtotal AS importe_sin_iva, r.numero_venta
    FROM facturacion_remisiones r
    LEFT JOIN ordenes_venta ov ON ov.id = r.orden_venta_id
    LEFT JOIN empresas e ON e.id_e = ov.empresa_id
    LEFT JOIN cotizaciones c ON c.id_coti = ov.cotizacion_id
    ORDER BY r.fecha_venta DESC, r.id DESC');
$remisiones = $stmtRemisiones ? $stmtRemisiones->fetch_all(MYSQLI_ASSOC) : [];
$datosVentasMensuales = array_map(static fn(array $factura): array => [
    'fecha' => $factura['fecha'], 'importe_neto' => (float) ($factura['subtotal'] ?: 0),
], $facturas);
foreach ($remisiones as $remision) {
    $datosVentasMensuales[] = ['fecha' => $remision['fecha'], 'importe_neto' => (float) $remision['importe_sin_iva']];
}
$registrosFacturacion = array_merge(array_map(static fn(array $factura): array => $factura + ['tipo' => 'CFDI', 'clave' => $factura['archivo']], $facturas), array_map(static fn(array $remision): array => [
    'fecha' => $remision['fecha'], 'cliente' => $remision['cliente'], 'descripcion' => $remision['descripcion'],
    'subtotal' => '', 'iva' => '', 'total' => $remision['importe_sin_iva'], 'tipo' => 'Remisión',
    'clave' => 'remision:' . $remision['id'], 'numero_venta' => $remision['numero_venta'],
], $remisiones));
usort($registrosFacturacion, static fn(array $a, array $b): int => (strtotime($b['fecha'] ?? '') ?: 0) <=> (strtotime($a['fecha'] ?? '') ?: 0));
?>
<div class="container mt-5 mb-5 contain shadow-lg">
    <div class="row align-items-center contactos-header g-3">
        <div class="col">
            <p class="contactos-kicker mb-1">Facturación</p>
            <h1 class="h2 mb-0">Facturación</h1>
        </div>
    </div>
    <?php if (auth_has_permission('facturacion_graficas', $authUser)): ?>
    <div class="row" id="zonaGraficas"><div class="col-12 text-center mt-3 mb-5"><div class="dashboard-hero dashboard-monthly-chart"><h2 class="h5 text-white mb-3">Ventas mensuales · últimos 13 meses</h2><canvas id="chartMeses" aria-label="Gráfica de ventas mensuales de los últimos 13 meses" role="img"></canvas></div></div></div>
    <?php endif; ?>
    <div class="row mb-4"><div class="col section-shell">
        <div class="row p-3 mt-2 gy-3"><div class="col-12"><div class="d-flex flex-column align-items-center justify-content-center text-center h-100"><h5 class="mb-2"><i class="bi bi-receipt-cutoff" id="ico_coti"></i></h5><h5 class="mb-0">Registros de ventas</h5></div></div>
        <div class="col-12"><div class="d-flex flex-column flex-sm-row justify-content-center justify-content-lg-end align-items-center gap-2 pt-2" data-page-header-actions><button type="button" class="btn btn-outline-success" id="abrir-remision">Remisión</button><button type="button" class="btn btn-outline-success" onclick="abrirSelectorXml()">Cargar XML</button><button type="button" class="btn btn-outline-success" onclick="abrirSelectorPdf()">Cargar PDF</button></div><input type="file" id="input-xml-cfdi" class="d-none" accept=".xml,application/xml,text/xml"><input type="file" id="input-pdf-cfdi" class="d-none" accept=".pdf,application/pdf"></div></div>
        <div class="col px-0 px-md-4 py-4">
            <div class="factura-resumen mb-4"><div class="factura-resumen-item"><span class="factura-resumen-label">Registros</span><strong id="card-registros">0</strong></div><div class="factura-resumen-item"><span class="factura-resumen-label">Subtotal CFDI</span><strong id="card-subtotal">0.00</strong></div><div class="factura-resumen-item"><span class="factura-resumen-label">IVA CFDI</span><strong id="card-iva">0.00</strong></div><div class="factura-resumen-item factura-resumen-total"><span class="factura-resumen-label">Total registrado</span><strong id="card-total">0.00</strong></div></div>
            <div class="d-flex flex-wrap align-items-end gap-3 mb-3" aria-label="Filtro de selección de facturación">
                <label class="form-label mb-0">Registros seleccionados<select id="filtro-seleccion" class="form-select"><option value="all">Todos</option><option value="selected">Seleccionados</option><option value="unselected">Sin seleccionar</option></select></label>
                <button id="seleccionar-visibles" type="button" class="btn btn-outline-secondary">Seleccionar filtrados</button><button id="limpiar-seleccion" type="button" class="btn btn-outline-secondary">Limpiar selección</button>
            </div>
            <div class="factura-table-wrap table-responsive data-table-shell"><table id="example" class="table table-secondary table-striped align-middle mb-0"><thead><tr><th><input id="seleccionar-pagina" type="checkbox" aria-label="Seleccionar registros de esta página"></th><th>Fecha</th><th>Cliente</th><th>Descripción</th><th>Subtotal CFDI</th><th>IVA CFDI</th><th>Total registrado</th><th>Tipo</th></tr></thead><tbody>
            <?php foreach ($registrosFacturacion as $factura): ?><tr data-record-key="<?= htmlspecialchars($factura['clave'], ENT_QUOTES, 'UTF-8') ?>"><td class="text-center"><input class="factura-seleccion" type="checkbox" aria-label="Seleccionar registro"></td><td data-order="<?= htmlspecialchars($factura['fecha'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(formatCfdiDate($factura['fecha']), ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($factura['cliente'], ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($factura['descripcion'] . (!empty($factura['numero_venta']) ? ' · ' . $factura['numero_venta'] : ''), ENT_QUOTES, 'UTF-8') ?></td><td class="text-end" data-value="<?= htmlspecialchars($factura['subtotal'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(formatAmount($factura['subtotal']), ENT_QUOTES, 'UTF-8') ?></td><td class="text-end" data-value="<?= htmlspecialchars($factura['iva'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(formatAmount($factura['iva']), ENT_QUOTES, 'UTF-8') ?></td><td class="text-end" data-value="<?= htmlspecialchars($factura['total'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(formatAmount($factura['total']), ENT_QUOTES, 'UTF-8') ?></td><td><?= htmlspecialchars($factura['tipo'], ENT_QUOTES, 'UTF-8') ?></td></tr><?php endforeach; ?>
            </tbody><tfoot><tr class="factura-total-row"><th colspan="4" class="text-end">Totales filtrados</th><th id="total-subtotal" class="text-end"></th><th id="total-iva" class="text-end"></th><th id="total-general" class="text-end"></th><th></th></tr></tfoot></table></div>
            <?php if (empty($registrosFacturacion)): ?><div class="alert alert-warning mt-3 mb-0">No se encontraron registros de ventas.</div><?php endif; ?></div>
    </div></div>
</div>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.js"></script><script src="https://cdn.datatables.net/1.13.7/js/dataTables.bootstrap5.min.js"></script><script src="../js/datatable-filters.js?v=20261008-2"></script><script src="../js/datatable-config.js"></script><script src="../js/main.js"></script><script src="../js/tablaFacturacion.js?v=20261031-1"></script>
<dialog id="dialog-remision" class="facturacion-remision-dialog"><form method="dialog"><div class="d-flex justify-content-between align-items-center mb-3"><h2 class="h5 mb-0">Agregar venta por remisión</h2><button class="btn-close" aria-label="Cerrar"></button></div><label for="orden-remision" class="form-label">Orden de venta completada</label><select id="orden-remision" class="form-select"><option value="">Cargando órdenes…</option></select><label for="fecha-remision" class="form-label mt-3">Fecha de registro</label><input id="fecha-remision" class="form-control" type="date" required><div id="detalle-remision" class="alert alert-light mt-3 d-none"></div><div id="error-remision" class="text-danger small mb-3" role="alert"></div><div class="d-flex justify-content-end gap-2"><button class="btn btn-outline-secondary" value="cancel">Cancelar</button><button id="confirmar-remision" class="btn btn-success" type="button" disabled>Confirmar registro</button></div></form></dialog>
<script>window.facturacionRemisionCsrf = <?= json_encode(auth_csrf()) ?>;</script><script src="../js/facturacion-remisiones.js?v=20261101-2"></script>
<?php if (auth_has_permission('facturacion_graficas', $authUser)): ?><script>window.facturacionDatosVentas = <?= json_encode($datosVentasMensuales, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script><script src="https://cdn.jsdelivr.net/npm/chart.js"></script><script src="../js/facturacion-charts.js?v=20261101-1"></script><?php endif; ?>
<?php require __DIR__ . '/../construct/footer.html'; ?>
